<template>
	<view class="container">
        <view class="header-bg"></view>
        <view class="content-wrapper">
            <view class="logo-area">
                <view class="logo-box">
                    <svg viewBox="0 0 24 24" width="40" height="40">
                         <path fill="#fff" d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 11l-2.5-1.25L12 11zm0 2.5l-5-2.5-2 1L12 17l7-5-2-1-5 2.5z"></path>
                    </svg>
                </view>
                <view class="app-name">新国创</view>
                <view class="app-slogan">创建您的账号</view>
            </view>

            <view class="form-card">
                <view class="input-item">
                    <view class="icon-box">
                       <svg viewBox="0 0 24 24" width="20" height="20"><path fill="#999" d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"></path></svg>
                    </view>
                    <input class="input" v-model="mobile" type="number" placeholder="请输入手机号" placeholder-class="placeholder" maxlength="11" />
                </view>
                <view class="input-item">
                    <view class="icon-box">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="#999" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"></path></svg>
                    </view>
                    <input class="input" v-model="password" password placeholder="设置登录密码" placeholder-class="placeholder" />
                </view>
                <view class="input-item">
                    <view class="icon-box">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="#999" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"></path></svg>
                    </view>
                    <input class="input" v-model="confirm_password" password placeholder="确认登录密码" placeholder-class="placeholder" />
                </view>
                <view class="input-item">
                    <view class="icon-box">
                         <svg viewBox="0 0 24 24" width="20" height="20"><path fill="#999" d="M3 3h8v8H3V3zm6 6V5H5v4h4zm-6 8h8v8H3v-8zm6 6v-4H5v4h4zm8-14h8v8h-8V3zm6 6V5h-4v4h4zM13 13h2v2h-2v-2zm-2 2h2v2h-2v-2zm-2 2h2v2H9v-2zm2 2h2v2h-2v-2zm4-4h2v2h-2v-2zm2 2h2v2h-2v-2zm-2 2h2v2h-2v-2zm4-4h2v2h-2v-2zm0 2h-2v2h-2v2h4v-4z"></path></svg>
                    </view>
                    <input class="input" v-model="invite_code" type="number" placeholder="请输入邀请码 (选填)" placeholder-class="placeholder" :disabled="invite_disabled" />
                </view>

                <view class="btn-area">
                    <button class="login-btn" @click="register" :loading="loading">立即注册</button>
                    <view class="register-link" @click="goLogin">
                        已有账号？<text style="color:#07C160;">返回登录</text>
                    </view>
                </view>
            </view>
            
            <view class="protocol-tips">
                注册即代表同意《用户协议》与《隐私政策》
            </view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				mobile: '',
                password: '',
                confirm_password: '',
                invite_code: '',
                loading: false,
                invite_disabled: false
			}
		},
        onLoad(options) {
            if(options.invite_code) {
                this.invite_code = options.invite_code;
                this.invite_disabled = true;
            }
        },
		methods: {
			async register() {
                if(!this.mobile || !this.password || !this.confirm_password) {
                    return uni.showToast({ title: '请填写完整信息', icon: 'none' });
                }
                
                if(this.password !== this.confirm_password) {
                    return uni.showToast({ title: '两次密码输入不一致', icon: 'none' });
                }
                
                this.loading = true;
                try {
                    const res = await uni.$api('auth.php?action=register', 'POST', {
                        mobile: this.mobile,
                        password: this.password,
                        invite_code: this.invite_code,
                        position: 'L' // Default to Left
                    });
                    this.loading = false;
                     
                    if (res.code == 200) {
                        uni.showToast({ title: '注册成功', icon: 'success' });
                        setTimeout(() => {
                             uni.reLaunch({ url: '/pages/login/login' });
                        }, 1500);
                    } else {
                        uni.showToast({ title: res.message || '注册失败', icon: 'none' });
                    }
                } catch(e) {
                     this.loading = false;
                     uni.showToast({ title: '网络连接失败', icon: 'none' });
                }
			},
            goLogin() {
                uni.redirectTo({ url: '/pages/login/login' });
            }
		}
	}
</script>

<style>
    .container { min-height: 100vh; background: #f5f7fa; position: relative; overflow: hidden; }
    
    .header-bg { position: absolute; top: 0; left: 0; width: 100%; height: 240px; background: linear-gradient(135deg, #07C160 0%, #36cb98 100%); border-radius: 0 0 30% 30%; z-index: 1; }
    
    .content-wrapper { position: relative; z-index: 2; padding: 20px 10px; }
    
    .logo-area { display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; margin-top: 10px; }
    .logo-box { width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 20px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.3); }
    .app-name { font-size: 26px; font-weight: bold; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .app-slogan { font-size: 14px; color: rgba(255,255,255,0.9); margin-top: 5px; }

    .form-card { background: #fff; border-radius: 16px; padding: 30px 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    
    .input-item { display: flex; align-items: center; border-bottom: 1px solid #f0f0f0;transition: 0.3s; }
    .input-item:focus-within { border-bottom-color: #07C160; }
    
    .icon-box { margin-right: 15px; display: flex; align-items: center; opacity: 0.6; }
    .input-item:focus-within .icon-box { opacity: 1; color: #07C160; }
    .input-item:focus-within .icon-box path { fill: #07C160; }
    
    .input { flex: 1; font-size: 16px; color: #333; height: 30px; }
    .placeholder { color: #ccc; font-size: 14px; }
    
    .login-btn { width: 100%; height: 48px; line-height: 48px; background: linear-gradient(90deg, #07C160, #36cb98); color: #fff; font-size: 16px; border-radius: 24px; border: none; box-shadow: 0 4px 12px rgba(7, 193, 96, 0.3); margin-top: 10px; }
    .login-btn:active { opacity: 0.9; transform: scale(0.98); }
    .login-btn::after { border: none; }
    
    .register-link { text-align: center; margin-top: 25px; font-size: 14px; color: #666; }
    
    .protocol-tips { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
</style>
