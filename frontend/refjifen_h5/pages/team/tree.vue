<template>
	<view class="container">
        <!-- Root Node -->
        <view class="node-wrapper root-wrapper" v-if="team.root">
            <view class="node-card">
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
                    <view class="node-card sub-card">
                        <view class="node-name">{{ team.left.nickname || '用户' + team.left.id }}</view>
                        <view class="node-id">ID: {{ team.left.id }}</view>
                    </view>
                    <view class="node-stats sub-stats">
                        <view class="stat-row" style="color:#1890ff;">个人: {{ fmt(team.left.personal_performance) }}</view>
                        <view class="stat-row">L: {{ fmt(team.left.left_total) }}</view>
                        <view class="stat-row">R: {{ fmt(team.left.right_total) }}</view>
                    </view>
                </view>
                <view class="empty-node" v-else @click="onPlaceClick('L')" :class="{ 'highlight-empty': selectedPendingUser }">
                    <view class="plus-icon">+</view>
                    <view class="empty-text">左区空缺</view>
                </view>
            </view>
            
            <!-- Right Branch -->
            <view class="branch">
                <view class="line-connect-right"></view>
                <view class="node-wrapper" v-if="team.right">
                    <view class="node-card sub-card">
                        <view class="node-name">{{ team.right.nickname || '用户' + team.right.id }}</view>
                        <view class="node-id">ID: {{ team.right.id }}</view>
                    </view>
                    <view class="node-stats sub-stats">
                         <view class="stat-row" style="color:#1890ff;">个人: {{ fmt(team.right.personal_performance) }}</view>
                         <view class="stat-row">L: {{ fmt(team.right.left_total) }}</view>
                         <view class="stat-row">R: {{ fmt(team.right.right_total) }}</view>
                    </view>
                </view>
                 <view class="empty-node" v-else @click="onPlaceClick('R')" :class="{ 'highlight-empty': selectedPendingUser }">
                    <view class="plus-icon">+</view>
                    <view class="empty-text">右区空缺</view>
                </view>
            </view>
        </view>

        <!-- Pending List Drawer -->
        <view class="pending-drawer" :class="{ closed: !drawerOpen }">
            <view class="drawer-toggle" @click="drawerOpen = !drawerOpen">
                <text v-if="drawerOpen">></text>
                <text v-else><</text>
            </view>
            <view class="drawer-content">
                <view style="font-weight:bold;margin-bottom:10px;">待安置 ({{ pendingList.length }})</view>
                <view class="pending-list">
                    <view class="pending-item" v-for="item in pendingList" :key="item.id" 
                          :class="{ selected: selectedPendingUser && selectedPendingUser.id === item.id }"
                          @click="selectPending(item)">
                        <view class="p-name">{{ item.nickname || '未命名' }} (ID: {{item.id}})</view>
                        <view class="p-info">手机: {{ item.mobile }}</view>
                        <view class="p-info">注册时间: {{ item.created_at.substring(0,10) }}</view>
                    </view>
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
                pendingList: [],
                drawerOpen: false,
                selectedPendingUser: null
			}
		},
		onShow() {
            this.loadTeam();
            this.loadPending();
		},
		methods: {
            fmt(val) {
                return parseFloat(val || 0).toFixed(2);
            },
            async loadTeam() {
                try {
                     const res = await uni.$api('account.php?action=team');
                     if (res.code == 200) {
                         this.team = res.data;
                     }
                } catch(e){}
            },
            async loadPending() {
                try {
                    const res = await uni.$api('team.php?action=pending_list');
                    if(res.code == 200) {
                        this.pendingList = res.data;
                        if(this.pendingList.length > 0) {
                            this.drawerOpen = true; // Auto open if pending users exist
                        }
                    }
                } catch(e) {}
            },
            selectPending(item) {
                if(this.selectedPendingUser && this.selectedPendingUser.id === item.id) {
                    this.selectedPendingUser = null;
                } else {
                    this.selectedPendingUser = item;
                }
            },
            onPlaceClick(position) {
                if(!this.selectedPendingUser) {
                    return uni.showToast({ title: '请先从右侧选择待安置用户', icon: 'none' });
                }
                
                const targetParentId = this.team.root.id;
                const posName = position == 'L' ? '左区' : '右区';
                
                uni.showModal({
                    title: '确认安置',
                    content: `确认将用户 ${this.selectedPendingUser.nickname} 安置在 ${targetParentId} 的${posName}吗？`,
                    success: async (res) => {
                        if (res.confirm) {
                            try {
                                const apiRes = await uni.$api('team.php?action=place_user', 'POST', {
                                    target_user_id: this.selectedPendingUser.id,
                                    parent_id: targetParentId,
                                    position: position
                                });
                                if(apiRes.code == 200) {
                                    uni.showToast({ title: '安置成功' });
                                    this.selectedPendingUser = null;
                                    this.loadTeam();
                                    this.loadPending();
                                } else {
                                    uni.showToast({ title: apiRes.message, icon: 'none' });
                                }
                            } catch(e) {
                                uni.showToast({ title: '安置失败', icon: 'none' });
                            }
                        }
                    }
                });
            }
		}
	}
</script>

    /* Pending Drawer */
    .pending-drawer {
        position: fixed; top: 120px; right: 0; 
        background: #fff; border-radius: 12px 0 0 12px; 
        box-shadow: -2px 0 10px rgba(0,0,0,0.1); 
        padding: 10px; z-index: 99; transition: transform 0.3s;
        max-height: 60vh; overflow-y: auto;
    }
    .pending-drawer.closed { transform: translateX(100%); }
    .drawer-toggle { 
        position: absolute; left: -30px; top: 10px; 
        width: 30px; height: 30px; background: #1890ff; 
        color: #fff; border-radius: 4px 0 0 4px; 
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; 
    }
    
    .pending-item { 
        padding: 10px; border-bottom: 1px solid #eee; 
        display: flex; flex-direction: column; 
        width: 160px; box-sizing: border-box; 
    }
    .pending-item.selected { background: #e6f7ff; border: 1px solid #1890ff; border-radius: 4px; }
    .p-name { font-weight: bold; font-size: 14px; }
    .p-info { font-size: 12px; color: #666; }
    
    .highlight-empty { border: 2px dashed #1890ff; background: #e6f7ff; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
</style>
