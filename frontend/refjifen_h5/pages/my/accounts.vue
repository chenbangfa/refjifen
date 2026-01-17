<template>
	<view class="container">
		<view class="account-list">
            <view 
                class="account-item" 
                v-for="(item, index) in accounts" 
                :key="index"
                @click="switchAcc(item)"
                :class="{active: item.is_current}"
            >
                <view class="acc-left">
                    <image src="/static/my.png" class="acc-avatar"></image>
                    <view class="acc-info">
                        <view class="acc-name">
                            {{ item.nickname || '未命名' }} 
                            <text class="tag" v-if="item.is_sub_account == 1">子</text>
                            <text class="tag master" v-else>主</text>
                        </view>
                        <view class="acc-detail">ID: {{ item.id }} | 手机: {{ maskMobile(item.mobile) }}</view>
                        <view class="acc-detail" v-if="item.invite_code">邀请码: {{ item.invite_code }}</view> 
                        <!-- Note: invite_code might not be returned strictly as a field unless aliased to id, 
                             usually ID is the invite code in this system. -->
                    </view>
                </view>
                <view class="acc-right">
                    <view class="current-badge" v-if="item.is_current">当前使用</view>
                    <view class="switch-btn" v-else>切换</view>
                </view>
            </view>
        </view>
        
        <view class="footer-action">
            <view class="add-btn" @click="addAccount">+ 添加/激活新账号</view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				accounts: [],
                redirect: ''
			}
		},
        onLoad(options) {
            this.redirect = options.redirect || '';
        },
        onShow() {
            this.loadAccounts();
        },
		methods: {
            async loadAccounts() {
                uni.showLoading({title:'加载中'});
                try {
                    const res = await uni.$api('auth.php?action=list_accounts');
                    uni.hideLoading();
                    if(res.code == 200) {
                        this.accounts = res.data;
                    }
                } catch(e) {
                    uni.hideLoading();
                }
            },
            maskMobile(mobile) {
                return mobile ? mobile.substr(0,3)+'****'+mobile.substr(7) : '';
            },
            async switchAcc(item) {
                if(item.is_current) return;
                
                uni.showLoading({title:'切换中...'});
                try {
                    const res = await uni.$api('auth.php?action=quick_login', 'POST', {
                        target_id: item.id
                    });
                    uni.hideLoading();
                    if(res.code == 200) {
                        uni.setStorageSync('token', res.token);
                        uni.showToast({title:'切换成功', icon:'success'});
                        setTimeout(() => {
                            let url = this.redirect ? decodeURIComponent(this.redirect) : '/pages/my/my';
                            uni.reLaunch({url: url});
                        }, 1000);
                    } else {
                        uni.showToast({title: res.message, icon:'none'});
                    }
                } catch(e) { uni.hideLoading(); }
            },
            addAccount() {
                // Determine if we are Master or Sub to decide what "add" means
                // Currently "Add Account" usually means "Activate Sub" if Master
                // Or "Register New" if completely new? 
                // User said "Add Account" button.
                // Let's offer options or just go to Activate Sub if Master.
                // Or maybe just generic "Register" but that creates a parallel tree unless linked.
                // Given the context "One Mobile Two IDs", "Add" likely means "Activate Sub".
                uni.showActionSheet({
                    itemList: ['激活子账号', '注册新账号'],
                    success: (res) => {
                        if(res.tapIndex == 0) {
                             // Go to simple activate input or page
                             // For now reusing the simple modal logic from my.vue? 
                             // Better to have a dedicated page, but for now invoke logic or prompt
                             this.triggerActivate();
                        } else {
                             uni.navigateTo({url: '/pages/login/register'});
                        }
                    }
                });
            },
            triggerActivate() {
                 uni.showModal({
                    title: '激活子账号',
                    editable: true,
                    placeholderText: '设置子账号密码',
                    success: async (res) => {
                        if (res.confirm && res.content) {
                             this.doActivate(res.content);
                        }
                    }
                })
            },
            async doActivate(password) {
                uni.showLoading({title:'激活中'});
                 try {
                     // Need current user info to know mobile, or rely on backend to know current user's mobile + sub logic
                     // auth.php doesn't expose 'activate_sub', account.php does.
                     // We need to know 'position'. Default L.
                     // We need 'sub_mobile'. Assuming same mobile.
                     
                     // Get current user mobile first? Or just pass empty and let backend handle?
                     // Backend `account.php` needs `sub_mobile`.
                     // Let's fetch info first or just guess.
                     // To be safe, let's just use the `activate_sub` action in `account.php` 
                     // BUT wait, `account.php` requires `sub_mobile` param.
                     // Quick fix: User enters password, we assume mobile is same as session mobile? 
                     // But we don't have session mobile easily here without fetching info.
                     
                     // Better UX: Prompt for activation is risky without clear UI.
                     // Let's just alert user to go to 'My' page to activate for now? 
                     // Or just implement it. 
                     
                     // Implementing:
                     let infoRes = await uni.$api('account.php?action=info');
                     if(infoRes.code != 200) return;
                     let mobile = infoRes.data.mobile;
                     
                     const res = await uni.$api('account.php?action=activate_sub', 'POST', {
                        sub_mobile: mobile, 
                        password: password,
                        position: 'L'
                    });
                    uni.hideLoading();
                    if(res.code == 200) {
                        uni.showToast({title: '激活成功', icon:'success'});
                        this.loadAccounts();
                    } else {
                        uni.showToast({title: res.message, icon:'none'});
                    }
                 } catch(e) { uni.hideLoading(); }
            }
		}
	}
</script>

<style>
    .container { padding-bottom: 80px; }
    .account-list { padding: 15px; }
    .account-item {
        background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid transparent;
    }
    .account-item.active { border-color: #07C160; background: #E8F5EB; }
    
    .acc-left { display: flex; align-items: center; }
    .acc-avatar { width: 50px; height: 50px; border-radius: 25px; background: #eee; margin-right: 15px; }
    .acc-name { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 5px; }
    .tag { font-size: 10px; padding: 1px 4px; border-radius: 4px; margin-left: 5px; background: #FF9800; color: #fff; }
    .tag.master { background: #07C160; }
    
    .acc-detail { font-size: 12px; color: #999; }
    
    .acc-right { text-align: right; }
    .current-badge { font-size: 12px; color: #07C160; font-weight: bold; }
    .switch-btn { padding: 5px 12px; background: #fff; border: 1px solid #ddd; border-radius: 15px; font-size: 12px; color: #666; }
    
    .footer-action { position: fixed; bottom: 0; left: 0; width: 100%; padding: 15px; background: #fff; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); box-sizing: border-box; }
    .add-btn { background: #07C160; color: #fff; text-align: center; padding: 12px; border-radius: 8px; font-size: 16px; font-weight: bold; }
</style>
