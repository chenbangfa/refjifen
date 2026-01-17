<template>
	<view class="container">
        <!-- Top Controls -->
        <view class="top-controls" v-if="team.root && team.root.id != myUserId">
            <view class="back-btn" @click="loadTeam(myUserId)">返回顶部</view>
        </view>

        <!-- Root Node -->
        <view class="node-wrapper root-wrapper" v-if="team.root">
            <view class="node-card" @click="drillDown(team.root.id)">
                <view class="node-avatar">
                   <svg viewBox="0 0 24 24" width="24" height="24"><path fill="#1890ff" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                </view>
                <view class="node-info">
                    <view class="node-name">{{ team.root.nickname || '用户' + team.root.id }}</view>
                    <view class="node-id">ID: {{ team.root.id }}</view>
                </view>
            </view>
            <!-- Stats -->
            <view class="node-stats">
                <view class="stat-box left">
                    <view class="stat-val">{{ fmt(team.root.left_total) }}</view>
                    <view class="stat-lbl">左区业绩</view>
                </view>
                <view class="stat-box center" style="border-right:1px solid #eee;">
                    <view class="stat-val" style="color:#1890ff;">{{ fmt(team.root.personal_performance) }}</view>
                    <view class="stat-lbl">个人业绩</view>
                </view>
                <view class="stat-box right">
                    <view class="stat-val">{{ fmt(team.root.right_total) }}</view>
                    <view class="stat-lbl">右区业绩</view>
                </view>
            </view>
        </view>
        
        <!-- Branches -->
        <view class="branches">
            <!-- Left Branch -->
            <view class="branch">
                <view class="line-connect-left"></view>
                <view class="node-wrapper" v-if="team.left">
                    <view class="node-card sub-card" @click="drillDown(team.left.id)">
                        <view class="node-name">{{ team.left.nickname || '用户' + team.left.id }}</view>
                        <view class="node-id">ID: {{ team.left.id }}</view>
                    </view>
                    <view class="node-stats sub-stats">
                        <view class="stat-row" style="color:#1890ff;">个人: {{ fmt(team.left.personal_performance) }}</view>
                        <view class="stat-row">L: {{ fmt(team.left.left_total) }}</view>
                        <view class="stat-row">R: {{ fmt(team.left.right_total) }}</view>
                    </view>
                </view>
                <view class="empty-node" v-else>
                    <view class="plus-icon">+</view>
                    <view class="empty-text">左区空缺</view>
                </view>
            </view>
            
            <!-- Right Branch -->
            <view class="branch">
                <view class="line-connect-right"></view>
                <view class="node-wrapper" v-if="team.right">
                    <view class="node-card sub-card" @click="drillDown(team.right.id)">
                        <view class="node-name">{{ team.right.nickname || '用户' + team.right.id }}</view>
                        <view class="node-id">ID: {{ team.right.id }}</view>
                    </view>
                    <view class="node-stats sub-stats">
                         <view class="stat-row" style="color:#1890ff;">个人: {{ fmt(team.right.personal_performance) }}</view>
                         <view class="stat-row">L: {{ fmt(team.right.left_total) }}</view>
                         <view class="stat-row">R: {{ fmt(team.right.right_total) }}</view>
                    </view>
                </view>
                 <view class="empty-node" v-else>
                    <view class="plus-icon">+</view>
                    <view class="empty-text">右区空缺</view>
                </view>
            </view>
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
	export default {
        components: { tabBar },
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
            // Get My ID from storage or API check
            const user = uni.getStorageSync('user');
            if(user) this.myUserId = user.id;
            
            this.loadTeam(this.myUserId);
            this.loadPending();
		},
		methods: {
            fmt(val) {
                return parseFloat(val || 0).toFixed(2);
            },
            async loadTeam(id) {
                if(!id) return;
                try {
                     const res = await uni.$api(`account.php?action=team&id=${id}`);
                     if (res.code == 200) {
                         this.team = res.data;
                     } else {
                         uni.showToast({ title: res.message, icon: 'none' });
                     }
                } catch(e){}
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
                // Close list modal? Or keep it open behind? Better close or stack.
                // Let's stack visually, but maybe close list to keep it simple.
                // Keeping list open might be confusing with Z-index. Let's close list for now.
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
                        this.loadTeam(this.team.root.id); // Reload current view
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
    .container { padding: 20px; min-height: 100vh; background: #f5f7fa; padding-top: 40px; padding-bottom: 80px; }
    
    .top-controls { position: absolute; top: 10px; right: 20px; z-index: 5; }
    .back-btn { font-size: 12px; color: #1890ff; padding: 4px 10px; border: 1px solid #1890ff; border-radius: 12px; background: #fff; }

    .node-wrapper { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; }
    
    .root-wrapper { margin-bottom: 40px; }
    .root-wrapper::after { content: ''; position: absolute; bottom: -40px; left: 50%; width: 2px; height: 40px; background: #ccc; z-index: 1; margin-left: -1px; }

    .node-card { background: #fff; border-radius: 8px; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; min-width: 160px; justify-content: center; transition: 0.2s; }
    .node-card:active { transform: scale(0.98); background: #f0faff; }

    .node-avatar { width: 36px; height: 36px; background: #e6f7ff; border-radius: 18px; display: flex; justify-content: center; align-items: center; margin-right: 10px; color: #1890ff; }
    .node-info { text-align: left; }
    .node-name { font-weight: bold; font-size: 14px; color: #333; }
    .node-id { font-size: 12px; color: #999; }
    
    .node-stats { display: flex; margin-top: -10px; background: #fff; border-radius: 20px; padding: 5px 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); border: 1px solid #eee; z-index: 3; position: relative; top: 10px; }
    .stat-box { text-align: center; padding: 0 10px; }
    .stat-box.left { border-right: 1px solid #eee; }
    .stat-val { color: #52c41a; font-weight: bold; font-size: 14px; }
    .right .stat-val { color: #f5222d; }
    .stat-lbl { font-size: 10px; color: #999; }
    
    /* Branches */
    .branches { display: flex; justify-content: space-between; position: relative; padding: 0 2%; }
    .branches::before { content: ''; position: absolute; top: 0; left: 25%; width: 50%; height: 2px; background: #ccc; z-index: 1; }

    .branch { width: 48%; display: flex; flex-direction: column; align-items: center; position: relative; padding-top: 30px; }
    .branch::before { content: ''; position: absolute; top: 0; left: 50%; width: 2px; height: 30px; background: #ccc; margin-left: -1px; }
    
    .sub-card { min-width: 120px; padding: 10px; flex-direction: column; text-align: center; }
    .sub-card .node-info { text-align: center; }
    
    .sub-stats { flex-direction: column; margin-top: 0; padding: 5px; width: 100%; box-sizing: border-box; top: 5px; min-width: 100px; }
    .stat-row { font-size: 12px; color: #666; margin: 2px 0; display:flex; justify-content: space-between; width: 100%; }
    
    .empty-node { width: 60px; height: 60px; border: 2px dashed #ddd; border-radius: 30px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: #fff; opacity: 0.5; }
    .plus-icon { font-size: 20px; color: #ccc; }
    .empty-text { font-size: 10px; color: #ccc; margin-top: 2px; }

    /* Floating Button */
    .float-btn { position: fixed; bottom: 80px; right: 20px; width: 60px; height: 60px; background: linear-gradient(135deg, #1890ff, #36cfc9); border-radius: 30px; box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4); display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; z-index: 100; }
    .float-txt { font-size: 10px; }
    .float-num { font-size: 16px; font-weight: bold; }

    /* Unified Modal Styles */
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

    /* Pending List Items */
    .pending-item { padding: 15px; border-bottom: 1px solid #f5f5f5; }
    .pending-item:last-child { border-bottom: none; }
    .p-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .p-name { font-weight: bold; font-size: 14px; }
    .p-btn { font-size: 12px; color: #fff; background: #1890ff; padding: 2px 10px; border-radius: 10px; }
    .p-info { font-size: 12px; color: #999; }

    /* Form Styles */
    .form-uinfo { text-align: center; margin-bottom: 20px; font-size: 14px; }
    .form-label { font-size: 14px; font-weight: bold; margin-bottom: 10px; color: #333; }
    .form-input { width: 100%; height: 40px; border: 1px solid #ddd; border-radius: 6px; padding: 0 10px; box-sizing: border-box; font-size: 14px; margin-bottom: 20px; }
    .form-radio-group { display: flex; gap: 20px; }
    .radio-label { display: flex; align-items: center; font-size: 14px; }
</style>
