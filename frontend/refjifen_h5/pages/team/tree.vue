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
                <view class="empty-node" v-else>
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
                 <view class="empty-node" v-else>
                    <view class="plus-icon">+</view>
                    <view class="empty-text">右区空缺</view>
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
				team: {}
			}
		},
		onShow() {
            this.loadTeam();
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
            }
		}
	}
</script>

<style>
    .container { padding: 20px; min-height: 100vh; background: #f5f7fa; padding-top: 40px; }
    
    .node-wrapper { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; }
    
    .root-wrapper { margin-bottom: 40px; }
    .root-wrapper::after { content: ''; position: absolute; bottom: -40px; left: 50%; width: 2px; height: 40px; background: #ccc; z-index: 1; margin-left: -1px; }

    .node-card { background: #fff; border-radius: 8px; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; min-width: 160px; justify-content: center; }
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
    /* Connecting horizontal line */
    .branches::before { content: ''; position: absolute; top: 0; left: 25%; width: 50%; height: 2px; background: #ccc; z-index: 1; }

    .branch { width: 48%; display: flex; flex-direction: column; align-items: center; position: relative; padding-top: 30px; }
    .branch::before { content: ''; position: absolute; top: 0; left: 50%; width: 2px; height: 30px; background: #ccc; margin-left: -1px; }
    
    .sub-card { min-width: 120px; padding: 10px; flex-direction: column; text-align: center; }
    .sub-card .node-info { text-align: center; }
    
    .sub-stats { flex-direction: column; margin-top: 0; padding: 5px; width: 100%; box-sizing: border-box; top: 5px; min-width: 100px; }
    .stat-row { font-size: 12px; color: #666; margin: 2px 0; display:flex; justify-content: space-between; width: 100%; }
    
    .empty-node { width: 60px; height: 60px; border: 2px dashed #ddd; border-radius: 30px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: #fff; }
    .plus-icon { font-size: 20px; color: #ccc; }
    .empty-text { font-size: 10px; color: #ccc; margin-top: 2px; }
</style>
