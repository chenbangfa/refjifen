<template>
	<view class="container">
        <!-- Top Controls -->
        <view class="top-controls">
            <view class="ctrl-btn back-parent" v-if="team.id && team.parent_id > 0" @click="loadTeam(team.parent_id)">返回上级</view>
            <view class="ctrl-btn back-top" v-if="team.id && team.id != myUserId" @click="loadTeam(myUserId)">返回我的</view>
        </view>

        <!-- Recursive Tree Chart -->
        <view class="tree-scroll-view">
            <tree-chart v-if="team && team.id" :node="team" :isRoot="true" @click="onNodeClick"></tree-chart>
            <view v-else class="loading-txt">加载中...</view>
        </view>

        <!-- Floating Button -->
        <view class="float-btn" v-if="pendingList.length > 0" @click="showPendingModal = true">
            <view class="float-txt">待安置</view>
            <view class="float-num">{{ pendingList.length }}</view>
        </view>

        <!-- Pending List Modal -->
        <view class="modal-overlay" v-if="showPendingModal" @click.self="showPendingModal = false">
            <view class="modal-card">
                <view class="modal-header">待安置列表</view>
                <scroll-view scroll-y class="modal-body list-body">
                    <view class="pending-item" v-for="item in pendingList" :key="item.id" @click="openPlaceForm(item)">
                        <view class="p-row">
                            <text class="p-name">{{ item.nickname || '未命名' }} (ID: {{item.id}})</text>
                            <text class="p-btn">安置</text>
                        </view>
                        <view class="p-info">手机: {{ item.mobile }}</view>
                        <view class="p-info">注册: {{ item.created_at.substring(0,10) }}</view>
                    </view>
                </scroll-view>
                <view class="modal-footer">
                    <view class="m-btn cancel" @click="showPendingModal = false">关闭</view>
                </view>
            </view>
        </view>

        <!-- Placement Form Modal -->
        <view class="modal-overlay" v-if="showPlaceForm" @click.self="showPlaceForm = false">
            <view class="modal-card">
                <view class="modal-header">安置用户</view>
                <view class="modal-body">
                    <view class="form-uinfo">
                        正在安置: <text style="color:#1890ff;font-weight:bold;">{{ currentPending.nickname }} (ID: {{ currentPending.id }})</text>
                    </view>
                    
                    <view class="form-label">上级ID (Parent ID)</view>
                    <input class="form-input" type="number" v-model="placeForm.parentId" placeholder="请输入要放置的上级ID" />
                    
                    <view class="form-label">放置区域 (Position)</view>
                    <radio-group class="form-radio-group" @change="placeForm.position = $event.detail.value">
                        <label class="radio-label">
                            <radio value="L" :checked="placeForm.position === 'L'" color="#1890ff" /> 左区 (Left)
                        </label>
                        <label class="radio-label">
                            <radio value="R" :checked="placeForm.position === 'R'" color="#1890ff" /> 右区 (Right)
                        </label>
                    </radio-group>
                </view>
                <view class="modal-footer">
                    <view class="m-btn cancel" @click="showPlaceForm = false">取消</view>
                    <view class="m-btn confirm" @click="submitPlacement">确认安置</view>
                </view>
            </view>
        </view>

        <tab-bar current="pages/team/tree"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
    import treeChart from '@/components/tree-chart/tree-chart.vue';
    
	export default {
        components: { tabBar, treeChart },
		data() {
			return {
				team: {},
                myUserId: 0, 
                pendingList: [],
                showPendingModal: false,
                showPlaceForm: false,
                currentPending: {},
                placeForm: {
                    parentId: '',
                    position: 'L'
                }
			}
		},
		onShow() {
            const user = uni.getStorageSync('user');
            if(user) this.myUserId = user.id;
            
            this.loadTeam(this.myUserId);
            this.loadPending();
		},
		methods: {
            async loadTeam(id) {
                if(!id) return;
                uni.showLoading({ title: '加载中' });
                try {
                     const res = await uni.$api(`account.php?action=team&id=${id}`);
                     uni.hideLoading();
                     if (res.code == 200) {
                         // API now returns the Root Node with recursive children
                         this.team = res.data;
                     } else {
                         uni.showToast({ title: res.message, icon: 'none' });
                     }
                } catch(e){
                    uni.hideLoading();
                }
            },
            onNodeClick(node) {
                // Click a node -> Drill down to view its subtree
                // But if it's already the current root, maybe go up? 
                // Currently only drill down.
                if(node && node.id) {
                    this.loadTeam(node.id);
                }
            },
            drillDown(id) {
                this.loadTeam(id);
            },
            async loadPending() {
                try {
                    const res = await uni.$api('team.php?action=pending_list');
                    if(res.code == 200) {
                        this.pendingList = res.data;
                    }
                } catch(e) {}
            },
            openPlaceForm(item) {
                this.currentPending = item;
                this.placeForm.parentId = ''; // Reset
                this.placeForm.position = 'L';
                this.showPlaceForm = true;
                this.showPendingModal = false; 
            },
            async submitPlacement() {
                if(!this.placeForm.parentId) return uni.showToast({ title: '请输入上级ID', icon: 'none' });
                
                uni.showLoading({ title: '处理中' });
                try {
                    const res = await uni.$api('team.php?action=place_user', 'POST', {
                        target_user_id: this.currentPending.id,
                        parent_id: this.placeForm.parentId,
                        position: this.placeForm.position
                    });
                    uni.hideLoading();
                    if(res.code == 200) {
                        uni.showToast({ title: '安置成功', icon: 'success' });
                        this.showPlaceForm = false;
                        this.loadPending();
                        this.loadTeam(this.team.id); // Reload view
                    } else {
                        uni.showToast({ title: res.message, icon: 'none' });
                    }
                } catch(e) {
                    uni.hideLoading();
                    uni.showToast({ title: '请求失败', icon: 'none' });
                }
            }
		}
	}
</script>

<style>
    .container { min-height: 100vh; background: #f5f7fa; padding-top: 40px; padding-bottom: 80px; position: relative; overflow-x: auto; }
    
    .top-controls { position: fixed; top: 10px; right: 20px; z-index: 99; display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }
    .ctrl-btn { font-size: 12px; color: #1890ff; padding: 4px 10px; border: 1px solid #1890ff; border-radius: 12px; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .back-parent { background: #e6f7ff; }

    .tree-scroll-view { width: 100%; min-height: 50vh; display: flex; justify-content: center; padding: 20px 0; overflow-x: auto; }
    .loading-txt { color: #999; margin-top: 50px; font-size: 14px; }

    /* Remove old node styles as they are now in tree-chart.vue or unused */
    
    /* Float Btn & Modals (Keep existing styles) */
    .float-btn { position: fixed; bottom: 80px; right: 20px; width: 60px; height: 60px; background: linear-gradient(135deg, #1890ff, #36cfc9); border-radius: 30px; box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4); display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; z-index: 100; }
    .float-txt { font-size: 10px; }
    .float-num { font-size: 16px; font-weight: bold; }

    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 200; display: flex; align-items: center; justify-content: center; }
    .modal-card { width: 85%; background: #fff; border-radius: 12px; overflow: hidden; animation: popIn 0.2s; }
    @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    .modal-header { padding: 15px; text-align: center; font-weight: bold; border-bottom: 1px solid #eee; background: #fafafa; }
    .modal-body { padding: 20px; }
    .list-body { max-height: 50vh; padding: 0; }

    .modal-footer { display: flex; border-top: 1px solid #eee; }
    .m-btn { flex: 1; text-align: center; padding: 15px 0; font-size: 16px; }
    .m-btn:active { background: #f0f0f0; }
    .m-btn.cancel { color: #999; border-right: 1px solid #eee; }
    .m-btn.confirm { color: #1890ff; font-weight: bold; }

    .pending-item { padding: 15px; border-bottom: 1px solid #f5f5f5; }
    .pending-item:last-child { border-bottom: none; }
    .p-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .p-name { font-weight: bold; font-size: 14px; }
    .p-btn { font-size: 12px; color: #fff; background: #1890ff; padding: 2px 10px; border-radius: 10px; }
    .p-info { font-size: 12px; color: #999; }

    .form-uinfo { text-align: center; margin-bottom: 20px; font-size: 14px; }
    .form-label { font-size: 14px; font-weight: bold; margin-bottom: 10px; color: #333; }
    .form-input { width: 100%; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 0 10px; box-sizing: border-box; font-size: 14px; margin-bottom: 20px; }
    .form-radio-group { display: flex; gap: 20px; }
    .radio-label { display: flex; align-items: center; font-size: 14px; }
</style>
