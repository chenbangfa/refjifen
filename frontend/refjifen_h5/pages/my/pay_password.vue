<template>
	<view class="container">
		<view class="form-group">
            <view class="tip-text">支付密码用于转账、提现等资金操作，请设置6位数字密码。</view>
            
            <view class="form-item">
                <text class="label">新密码</text>
                <input class="input" v-model="password" type="number" maxlength="6" password placeholder="请输入6位数字密码" />
            </view>
            <view class="form-item">
                <text class="label">确认密码</text>
                <input class="input" v-model="confirm_password" type="number" maxlength="6" password placeholder="请再次输入" />
            </view>
        </view>
        
        <view class="btn-area">
            <button class="save-btn" @click="save">确认设置</button>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				password: '',
                confirm_password: ''
			}
		},
		methods: {
            async save() {
                if(!this.password || this.password.length != 6) {
                    uni.showToast({title:'请输入6位数字', icon:'none'});
                    return;
                }
                if(this.password !== this.confirm_password) {
                     uni.showToast({title:'两次输入不一致', icon:'none'});
                     return;
                }
                
                uni.showLoading({title:'设置中'});
                try {
                    const res = await uni.$api('account.php?action=set_pay_password', 'POST', {
                        password: this.password
                    });
                    uni.hideLoading();
                    if(res.code == 200) {
                        uni.showToast({title:'设置成功'});
                        setTimeout(()=> uni.navigateBack(), 1500);
                    } else {
                         uni.showToast({title: res.message, icon:'none'});
                    }
                } catch(e) { uni.hideLoading(); }
            }
		}
	}
</script>

<style>
    .container { padding: 15px; }
    .tip-text { padding: 10px 15px; color: #888; font-size: 14px; }
    .form-group { background: #fff; border-radius: 12px; padding: 0 15px; margin-bottom: 20px; }
    .form-item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #f5f5f5; height: 50px; }
    .form-item:last-child { border-bottom: none; }
    
    .label { width: 80px; font-size: 16px; color: #333; }
    .input { flex: 1; font-size: 16px; color: #333; }
    
    .btn-area { margin-top: 30px; }
    .save-btn { background: #07C160; color: #fff; border-radius: 25px; height: 44px; line-height: 44px; font-size: 16px; }
    .save-btn:active { opacity: 0.9; }
</style>
