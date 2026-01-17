<template>
	<view class="container" style="padding: 10px; padding-bottom: 80px;">
        <!-- 背景装饰 -->
        <view class="bg-circle circle-1"></view>
        <view class="bg-circle circle-2"></view>
        
        <!-- 头部用户信息 -->
		<view class="user-header">
            <view class="avatar-area" @click="goProfile">
                <view class="avatar-wrapper">
                    <image class="avatar" :src="user.avatar || '/static/my.png'"></image>
                </view>
                <view class="user-info">
                    <view class="nickname">{{ user.nickname || '未设置姓名' }}</view>
                    <view class="uid">UID: {{ user.invite_code }} <text class="tag">{{ user.is_sub_account ? '子账号' : '主账号' }}</text></view>
                    <view class="level">等级: {{ getLevelName(user.level) }}</view>
                </view>
            </view>
            <!-- 切换账户按钮 -->
            <view class="switch-btn-transparent" @click="goAccounts">
                <text class="iconfont icon-exchange" style="font-size:20px; color: #006064;"></text>
                <text>切换</text>
            </view>
		</view>

        <!-- 我的账户资产 (纯展示，无点击跳转以避免进入提现页) -->
        <view class="section">
            <view class="section-title">我的账户</view>
            <view class="asset-grid">
                <view class="asset-item">
                    <view class="asset-value">{{ fmt(user.balance) }}</view>
                    <view class="asset-label">余额</view>
                </view>
                <view class="asset-item">
                    <view class="asset-value">{{ fmt(user.traffic_points) }}</view>
                    <view class="asset-label">流量分 <text v-if="user.pending_points > 0" style="color:#ff4d4f;font-size:10px;">(待+{{fmt(user.pending_points)}})</text></view>
                </view>
                <view class="asset-item">
                    <view class="asset-value">{{ fmt(user.vouchers) }}</view>
                    <view class="asset-label">购物券</view>
                </view>
            </view>
        </view>
        
        <!-- 订单统计 -->
        <view class="section">
            <view class="section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;" @click="goOrder('')">
                <view class="section-title" style="margin-bottom:0;border:none;padding-left:0;">我的订单</view>
                <view class="more-link" style="font-size:12px;color:#999;">全部订单 <text class="iconfont icon-right"></text></view>
            </view>
            <view class="stats-grid" style="display:flex;justify-content:space-between;">
                <view class="stats-item" @click="goOrder('0')" style="text-align:center;position:relative;">
                    <svg viewBox="0 0 24 24" width="28" height="28" class="stats-icon" style="display:block;margin:0 auto 5px;">
                        <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1h-9a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path>
                    </svg>
                    <text class="stats-label" style="font-size:12px;color:#666;">待付款</text>
                    <view class="badge-num" v-if="orderCounts[0]">{{ orderCounts[0] }}</view>
                </view>
                <view class="stats-item" @click="goOrder('1')" style="text-align:center;position:relative;">
                    <svg viewBox="0 0 24 24" width="28" height="28" class="stats-icon" style="display:block;margin:0 auto 5px;">
                        <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20 12V8H6a2 2 0 0 1-2-2 2 2 0 0 1 2-2h12v4h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8h18zM2 12h20"></path>
                        <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 12v4"></path>
                    </svg>
                    <text class="stats-label" style="font-size:12px;color:#666;">待发货</text>
                    <view class="badge-num" v-if="orderCounts[1]">{{ orderCounts[1] }}</view>
                </view>
                <view class="stats-item" @click="goOrder('2')" style="text-align:center;position:relative;">
                    <svg viewBox="0 0 24 24" width="28" height="28" class="stats-icon" style="display:block;margin:0 auto 5px;">
                         <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M1 3h15v13H1z"></path>
                         <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M16 8h4l3 3v5h-7V8z"></path>
                         <circle cx="5.5" cy="18.5" r="2.5" fill="none" stroke="#00BCD4" stroke-width="2"></circle>
                         <circle cx="18.5" cy="18.5" r="2.5" fill="none" stroke="#00BCD4" stroke-width="2"></circle>
                    </svg>
                    <text class="stats-label" style="font-size:12px;color:#666;">待收货</text>
                    <view class="badge-num" v-if="orderCounts[2]">{{ orderCounts[2] }}</view>
                </view>
                <view class="stats-item" @click="goOrder('3')" style="text-align:center;position:relative;">
                    <svg viewBox="0 0 24 24" width="28" height="28" class="stats-icon" style="display:block;margin:0 auto 5px;">
                        <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 11l3 3L22 4"></path>
                        <path fill="none" stroke="#00BCD4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <text class="stats-label" style="font-size:12px;color:#666;">已完成</text>
                </view>
            </view>
        </view>
        
        <!-- 常用功能 -->
        <view class="menu-list">
            <view class="menu-item" @click="goProfile">
                <view class="menu-left">
                     <svg viewBox="0 0 24 24" width="20" height="20" class="menu-icon" style="margin-right:10px;">
                        <path fill="#722ed1" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                     </svg>
                    <text>我的资料</text>
                </view>
                <view class="menu-right">
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>
             <view class="menu-item" @click="goAddress">
                <view class="menu-left">
                     <svg viewBox="0 0 24 24" width="20" height="20" class="menu-icon" style="margin-right:10px;">
                        <path fill="#ff7a45" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path>
                     </svg>
                    <text>收货地址</text>
                </view>
                <view class="menu-right">
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>

             <view class="menu-item" @click="goPayPassword">
                <view class="menu-left">
                     <svg viewBox="0 0 24 24" width="20" height="20" class="menu-icon" style="margin-right:10px;">
                        <path fill="#52c41a" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"></path>
                     </svg>
                    <text>支付密码</text>
                </view>
                <view class="menu-right">
                    <text class="tip" v-if="!user.has_pay_password">未设置</text>
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>

            <view class="menu-item" @click="logout" style="margin-top: 20px;">
                <view class="menu-left">
                     <svg viewBox="0 0 24 24" width="20" height="20" class="menu-icon" style="margin-right:10px;">
                        <path fill="#ff4d4f" d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"></path>
                     </svg>
                    <text style="color: #ff4d4f;">退出登录</text>
                </view>
            </view>
        </view>
        
        <view class="switch-link" @click="goAgent">转入代理中心</view>
        
        <tab-bar current="pages/mall/profile"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
        components: { tabBar },
		data() {
			return {
				user: {},
                orderCounts: {}
			}
		},
		onShow() {
			this.fetchInfo();
            this.fetchOrderCounts();
		},
		methods: {
            fmt(val) {
                return parseFloat(val || 0).toFixed(2);
            },
            async fetchOrderCounts() {
                 try {
                     const res = await uni.$api('mall.php?action=order_counts');
                     if(res.code == 200) {
                         this.orderCounts = res.data;
                     }
                 } catch(e) {}
            },
			async fetchInfo() {
                try {
                    const res = await uni.$api('account.php?action=info');
                    if(res.code == 200) {
                        this.user = res.data;
                    }
                } catch(e) { console.error(e); }
			},
            getLevelName(level) {
                const map = { 0: '普通会员', 1: 'VIP会员', 2: '金卡会员', 3: '钻卡会员' };
                return map[level] || '未知';
            },
            goAccounts() {
                uni.navigateTo({ url: '/pages/my/accounts?redirect=' + encodeURIComponent('/pages/mall/profile') });
            },
            goOrder(status) {
                uni.navigateTo({ url: `/pages/order/list?status=${status}` });
            },
            goAgent() {
                uni.reLaunch({ url: '/pages/my/my' });
            },
            goProfile() {
                uni.navigateTo({ url: '/pages/my/profile' });
            },
            goAddress() {
                uni.navigateTo({ url: '/pages/address/list' });
            },
            goPayPassword() {
                uni.navigateTo({ url: '/pages/my/pay_password' });
            },
            logout() {
                uni.removeStorageSync('token');
                uni.reLaunch({ url: '/pages/login/login' });
            }
		}
	}
</script>

<style>
    .container { position: relative; padding: 10px; padding-bottom: 80px; min-height: 100vh; background: linear-gradient(135deg, #00BCD4 0%, #2196F3 100%); overflow: hidden; }
    
    /* Background Circles */
    .bg-circle { position: absolute; border-radius: 50%; opacity: 0.2; z-index: 0; }
    .circle-1 { width: 200px; height: 200px; background: #00E5FF; top: -50px; right: -50px; filter: blur(40px); }
    .circle-2 { width: 150px; height: 150px; background: #2979FF; top: 50px; left: -30px; filter: blur(30px); }

    /* Transparent User Header */
    .user-header { position: relative; z-index: 1; padding: 30px 15px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
    
    .avatar-area { display: flex; align-items: center; }

    .avatar-wrapper { 
        width: 76px; height: 76px; box-sizing: border-box;
        padding: 4px; 
        background: #fff; 
        border-radius: 50%; 
        border: 2px solid rgba(0, 229, 255, 0.6); 
        margin-right: 15px;
        box-shadow: 0 0 15px rgba(0, 229, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar { width: 60px; height: 60px; border-radius: 50%; background: #eee; display: block; }
    
    .nickname { font-size: 20px; font-weight: bold; margin-bottom: 5px; color: #004D40; letter-spacing: 0.5px; }
    .uid { font-size: 13px; color: #fff; display: flex; align-items: center; opacity: 0.8; }
    .tag { background: rgba(0, 229, 255, 0.2); color: #006064; padding: 1px 6px; border-radius: 10px; font-size: 10px; margin-left: 8px; border: 1px solid rgba(0, 96, 100, 0.1); }
    .level { font-size: 12px; color: #FF6D00; margin-top: 4px; font-weight: bold; }

    .switch-btn-transparent { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        font-size: 12px; 
        color: #006064; 
        opacity: 0.9;
        background: rgba(255,255,255,0.25);
        padding: 6px 10px;
        border-radius: 12px;
        backdrop-filter: blur(5px);
    }
    
    .section { background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-left: 3px solid #07C160; padding-left: 10px; color: #333; }
    
    .asset-grid { display: flex; justify-content: space-between; }
    .asset-item { text-align: center; flex: 1; }
    .asset-value { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 5px; }
    .asset-label { font-size: 12px; color: #888; }
    
    .menu-list { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .menu-item { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; font-size: 16px; color: #333; }
    .menu-item:last-child { border-bottom: none; }
    .menu-item:active { background: #f9f9f9; }
    .arrow { color: #ccc; }
    
    .menu-left { display: flex; align-items: center; }
    .menu-right { display: flex; align-items: center; color: #999; font-size: 14px; }
    .menu-icon { font-size: 20px; margin-right: 10px; }
    
    .badge-num { position: absolute; top: -5px; right: 15px; background: #ff4d4f; color: #fff; font-size: 10px; padding: 0 4px; border-radius: 10px; min-width: 14px; text-align: center; line-height: 14px; height: 14px; }
    .switch-link { text-align: center; margin-top: 30px; margin-bottom: 30px; color: #fff; font-size: 14px; }
</style>
