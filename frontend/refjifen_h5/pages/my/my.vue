<template>
	<view class="container">
        <!-- 头部用户信息 -->
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
                    <view class="nickname">
                        {{ user.nickname || '未设置姓名' }}
                    </view>
                    <view class="uid">ID: {{ user.id }} <text class="tag">{{ user.is_sub_account ? '子账号' : '主账号' }}</text></view>
                    <view class="level">等级: {{ getLevelName(user.level) }}</view>
                </view>
            </view>
            <!-- 切换账户按钮 (Style as transparent icon btn) -->
            <view class="switch-btn-transparent" @click="goAccounts">
                <svg viewBox="0 0 24 24" width="24" height="24">
                   <path fill="#006064" d="M6.99 11L3 15l3.99 4v-3H14v-2H6.99v-3zM21 9l-3.99-4v3H10v2h7.01v3L21 9z"></path>
                </svg>
                <text>切换</text>
            </view>
		</view>

        <!-- 快捷功能区 (左右布局) -->
        <view class="quick-actions">
            <!-- 左侧：每日签到 -->
            <view class="quick-item" @click="doCheckin">
                <view class="quick-left">
                    <view class="quick-title">每日签到</view>
                    <view class="quick-desc" v-if="!user.checked_in">领取今日奖励</view>
                    <view class="quick-desc" v-else>今日已签到</view>
                </view>
                <svg viewBox="0 0 24 24" width="48" height="48" class="quick-icon">
                    <defs>
                        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#00E5FF;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#2979FF;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <path fill="url(#grad1)" d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7v-5z"></path>
                </svg>
            </view>
            
            <!-- 右侧：在线客服 -->
            <view class="quick-item" @click="goService">
                <view class="quick-left">
                    <view class="quick-title">在线客服</view>
                    <view class="quick-desc">专业客服 极速响应</view>
                </view>
                <svg viewBox="0 0 24 24" width="48" height="48" class="quick-icon">
                    <defs>
                        <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#00C853;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#69F0AE;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <path fill="url(#grad2)" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"></path>
                    <path fill="#fff" d="M7 9h10v2H7zm0-3h10v2H7z" opacity="0.6"/>
                </svg>
            </view>
        </view>

        <!-- 我的账户资产 -->
        <view class="section">
            <view class="section-title">我的账户</view>
            <view class="asset-grid" @click="goBalance">
                <view class="asset-item">
                    <view class="asset-value">{{ fmt(user.balance) }}</view>
                    <view class="asset-label">余额</view>
                </view>
                <view class="asset-item">
                    <view class="asset-value">{{ fmt(user.traffic_points) }}</view>
                    <view class="asset-label">流量分</view>
                </view>
                <view class="asset-item">
                    <view class="asset-value">{{ fmt(user.vouchers) }}</view>
                    <view class="asset-label">购物券</view>
                </view>
            </view>
        </view>

        <!-- 我的社区业绩 -->
        <view class="section">
            <view class="section-title">我的社区</view>
            <view class="perm-grid">
                <view class="perm-row">
                    <view class="perm-item">
                        <view class="perm-val">{{ fmt(user.personal_performance) }}</view>
                        <view class="perm-lbl">个人累计业绩</view>
                    </view>
                    <view class="perm-item">
                        <view class="perm-val">{{ fmt(user.community_performance) }}</view>
                        <view class="perm-lbl">社区总累计</view>
                    </view>
                </view>
                <view class="perm-row highlight">
                    <view class="perm-item">
                        <view class="perm-val">{{ fmt(user.left_total) }}</view>
                        <view class="perm-lbl">左区累计</view>
                    </view>
                    <view class="perm-item">
                        <view class="perm-val">{{ fmt(user.right_total) }}</view>
                        <view class="perm-lbl">右区累计</view>
                    </view>
                </view>
            </view>
        </view>
        
        <view class="menu-list">
            <view class="menu-item" @click="goTeam" v-if="user.level > 0">
                <view class="menu-left">
                    <svg viewBox="0 0 24 24" width="20" height="20" class="menu-icon" style="margin-right:10px;">
                        <path fill="#1890ff" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
                    </svg>
                    <text>我的粉丝</text>
                </view>
                <view class="menu-right">
                    <text class="menu-info">{{ user.fans_count || 0 }}人</text>
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>
            <view class="menu-item" @click="showInvite" v-if="user.level > 0">
                <view class="menu-left">
                     <svg viewBox="0 0 24 24" width="20" height="20" class="menu-icon" style="margin-right:10px;">
                        <path fill="#faad14" d="M3 3h8v8H3V3zm6 6V5H5v4h4zm-6 8h8v8H3v-8zm6 6v-4H5v4h4zm8-14h8v8h-8V3zm6 6V5h-4v4h4zM13 13h2v2h-2v-2zm-2 2h2v2h-2v-2zm-2 2h2v2H9v-2zm2 2h2v2h-2v-2zm4-4h2v2h-2v-2zm2 2h2v2h-2v-2zm-2 2h2v2h-2v-2zm4-4h2v2h-2v-2zm0 2h-2v2h-2v2h4v-4z"></path>
                     </svg>
                    <text>我的邀请码</text>
                </view>
                <view class="menu-right">
                    <text class="menu-info">{{ user.invite_code }}</text>
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>
            
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
        
        <view class="switch-link" @click="goMall">转入商城购物</view>
        
        <!-- 邀请码弹窗 -->
        <view class="modal-mask" v-if="showInviteModal" @click="closeInvite">
            <view class="modal-content" @click.stop>
                <view class="modal-title">邀请好友注册</view>
                <image class="qr-img" :src="qrCodeUrl" mode="aspectFit"></image>
                <view class="invite-code">我的邀请码: <text style="color:#07C160;font-size:20px;">{{ user.invite_code }}</text></view>
                <view class="modal-btn" @click="copyLink">复制注册链接</view>
            </view>
        </view>

        <!-- 充值弹窗 -->
        <view class="modal-mask" v-if="showRechargeModal" @click="() => showRechargeModal = false">
            <view class="modal-content" @click.stop>
                <view class="modal-title">在线充值</view>
                <view class="recharge-info">
                    <view>公司账户：2402018409200341260</view>
                    <view>开户银行：中国工商银行股份有限公司阳金桥支行</view>
                    <view>公司户名：贵州豪熠商贸有限公司</view>
                    <view style="margin-top:10px;color:#f00;">充值请备注手机号</view>
                </view>
                <view class="contact-info" style="margin-top:20px;border-top:1px solid #eee;padding-top:10px;">
                    <view>客服电话：18685042281</view>
                </view>
                <view class="modal-btn" @click="() => showRechargeModal = false" style="margin-top:20px;">我知道了</view>
            </view>
        </view>

        <tab-bar current="pages/my/my"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
        components: { tabBar },
		data() {
			return {
				user: {},
                showInviteModal: false,
                showRechargeModal: false,
                qrCodeUrl: ''
			}
		},
		onShow() {
			this.fetchInfo();
		},
		methods: {
            fmt(val) {
                return parseFloat(val || 0).toFixed(2);
            },
			async fetchInfo() {
                try {
                    const res = await uni.$api('account.php?action=info');
                    if(res.code == 200) {
                        this.user = res.data;
                        const link = `http://ref.tajian.cc/#/pages/login/register?invite_code=${this.user.invite_code}`;
                        this.qrCodeUrl = `https://quickchart.io/qr?text=${encodeURIComponent(link)}&size=300`;
                    }
                } catch(e) { console.error(e); }
			},
            getLevelName(level) {
                const map = { 0: '普通会员', 1: 'VIP会员', 2: '金卡会员', 3: '钻卡会员' };
                return map[level] || '未知';
            },
            goAccounts() {
                uni.navigateTo({ url: '/pages/my/accounts' });
            },
            goBalance() {
                uni.navigateTo({ url: '/pages/my/balance' });
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
            goService() {
                uni.navigateTo({ url: '/pages/service/chat' });
            },
            showRecharge() {
                this.showRechargeModal = true;
            },
            async doCheckin() {
                uni.showLoading({title:'签到中...'});
                try {
                    const res = await uni.$api('user_actions.php?action=checkin', 'POST'); 
                    uni.hideLoading();
                    
                    if(res.code == 200) {
                         uni.showToast({title: '签到成功 +'+res.data.release_amount, icon:'success'});
                         this.fetchInfo();
                    } else {
                        // Show backend specific message (e.g. "流量分不足", "今日已签到", "业绩未达标")
                        uni.showToast({title: res.message || '签到失败', icon:'none', duration: 2500});
                    }
                } catch(e) { 
                    uni.hideLoading();
                    uni.showToast({title: '网络请求错误', icon:'none'});
                }
            },
            goMall() {
                uni.reLaunch({ url: '/pages/index/index' });
            },
            goTeam() {
                uni.navigateTo({ url: '/pages/team/tree' });
            },
            goBalance() {
                uni.navigateTo({ url: '/pages/my/balance' });
            },
            showInvite() {
                this.showInviteModal = true;
                const link = `http://ref.tajian.cc/#/pages/login/register?invite_code=${this.user.invite_code}`;
                this.qrCodeUrl = `https://quickchart.io/qr?text=${encodeURIComponent(link)}&size=300`;
            },
            closeInvite() {
                this.showInviteModal = false;
            },
            logout() {
                uni.removeStorageSync('token');
                uni.reLaunch({ url: '/pages/login/login' });
            },
            copyLink() {
                 const link = `http://ref.tajian.cc/#/pages/login/register?invite_code=${this.user.invite_code}`;
                 uni.setClipboardData({
                     data: link,
                     success: () => uni.showToast({title:'复制成功'})
                 });
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
    .avatar { width: 100%; height: 100%; border-radius: 50%; background: #eee; display: block; object-fit: cover; }
    
    .nickname { font-size: 20px; font-weight: bold; margin-bottom: 5px; color: #004D40; letter-spacing: 0.5px; }
    .uid { font-size: 13px; color: #006064; display: flex; align-items: center; opacity: 0.8; }
    .tag { background: rgba(0, 229, 255, 0.2); color: #006064; padding: 1px 6px; border-radius: 10px; font-size: 10px; margin-left: 8px; border: 1px solid rgba(0, 96, 100, 0.1); }
    .tag.frozen { background: rgba(255, 0, 0, 0.1); color: #ff0000; border-color: rgba(255, 0, 0, 0.2); }
    .level { font-size: 12px; color: #FF6D00; margin-top: 4px; font-weight: bold; }

    .switch-btn-transparent { 
        display: flex; 
        flex-direction: column; /* Keep vertical but tighten spacing */
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
    
    .quick-actions { display: flex; justify-content: space-between; margin-bottom: 15px; }
    .quick-item { width: 47%; background: #fff; padding: 15px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .quick-title { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 2px; }
    .quick-desc { font-size: 12px; color: #999; }
    .quick-icon { width: 48px; height: 48px; }
    
    .section { background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-left: 3px solid #07C160; padding-left: 10px; color: #333; }
    
    .asset-grid { display: flex; justify-content: space-between; }
    .asset-item { text-align: center; flex: 1; }
    .asset-value { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 5px; }
    .asset-label { font-size: 12px; color: #888; }
    
    .perm-grid { background: #f9f9f9; border-radius: 8px; padding: 10px; }
    .perm-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
    .perm-row:last-child { border-bottom: none; }
    .perm-item { flex: 1; text-align: center; }
    .perm-val { font-size: 16px; font-weight: bold; color: #333; }
    .perm-lbl { font-size: 12px; color: #999; margin-top: 2px; }
    .highlight .perm-val { color: #07C160; }
    
    .menu-list { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .menu-item { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; font-size: 16px; color: #333; }
    .menu-item:last-child { border-bottom: none; }
    .menu-item:active { background: #f9f9f9; }
    .arrow { color: #ccc; }
    
    .menu-left { display: flex; align-items: center; }
    .menu-right { display: flex; align-items: center; color: #999; font-size: 14px; }
    .menu-icon { font-size: 20px; margin-right: 10px; }
    
    .modal-mask { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: flex; justify-content: center; align-items: center; }
    .modal-content { background: #fff; width: 70%; padding: 30px; border-radius: 16px; text-align: center; position: relative; }
    .modal-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; }
    .qr-img { width: 220px; height: 220px; margin-bottom: 15px; display: block; margin: 0 auto 15px; background:#f0f0f0; } 
    .invite-code { font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #333; }
    .modal-btn { background: #07C160; color: #fff; padding: 12px; border-radius: 8px; font-size: 16px; }
    
    .recharge-info { text-align: left; margin: 10px 0; color: #555; font-size: 14px; line-height: 1.8; }
    .contact-info { color: #888; font-size: 12px; }
    .switch-link { text-align: center; margin-top: 30px; margin-bottom: 30px; color: #576b95; font-size: 14px; }
</style>

<style>
    .user-card { position: relative; background: #fff; padding: 20px; display: flex; align-items: center; justify-content: space-between; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .avatar-area { display: flex; align-items: center; flex: 1; }
    .avatar { width: 60px; height: 60px; border-radius: 30px; background: #eee; }
    .nickname { font-size: 18px; font-weight: bold; margin-bottom: 5px; color: #333; }
    .uid { font-size: 12px; color: #888; display: flex; align-items: center; }
    .tag { background: #E8F5EB; color: #07C160; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px; }
    .level { font-size: 12px; color: #FF9800; margin-top: 2px; }
    .arrow-right { color: #ccc; margin-left: 10px; }
    
    .switch-btn-corner { font-size: 12px; color: #07C160; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .switch-btn-corner .iconfont { font-size: 20px; margin-bottom: 2px; }
    
    .action-bar { background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .action-left { display: flex; align-items: center; font-size: 16px; color: #333; }
    .action-icon { font-size: 20px; margin-right: 10px; }
    .action-right { display: flex; align-items: center; font-size: 14px; color: #999; }
    .tip { margin-right: 5px; font-size: 12px; color: #ff9800; }

    .section { background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .section-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-left: 3px solid #07C160; padding-left: 10px; color: #333; }
    
    .asset-grid { display: flex; justify-content: space-between; }
    .asset-item { text-align: center; flex: 1; }
    .asset-value { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 5px; }
    .asset-label { font-size: 12px; color: #888; }
    
    .perm-grid { background: #f9f9f9; border-radius: 8px; padding: 10px; }
    .perm-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
    .perm-row:last-child { border-bottom: none; }
    .perm-item { flex: 1; text-align: center; }
    .perm-val { font-size: 16px; font-weight: bold; color: #333; }
    .perm-lbl { font-size: 12px; color: #999; margin-top: 2px; }
    .highlight .perm-val { color: #07C160; }
    
    .menu-list { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .menu-item { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; font-size: 16px; color: #333; }
    .menu-item:last-child { border-bottom: none; }
    .menu-item:active { background: #f9f9f9; }
    .arrow { color: #ccc; }
    
    .menu-left { display: flex; align-items: center; }
    .menu-right { display: flex; align-items: center; color: #999; font-size: 14px; }
    .menu-icon { font-size: 20px; margin-right: 10px; }
    
    .modal-mask { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: flex; justify-content: center; align-items: center; }
    .modal-content { background: #fff; width: 70%; padding: 30px; border-radius: 16px; text-align: center; position: relative; }
    .modal-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; }
    .qr-img { width: 220px; height: 220px; margin-bottom: 15px; display: block; margin: 0 auto 15px; background:#f0f0f0; } 
    .invite-code { font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #333; }
    .modal-btn { background: #07C160; color: #fff; padding: 12px; border-radius: 8px; font-size: 16px; }
</style>
