<template>
	<view class="container">
		<view class="title">Create Account</view>
        <input class="input" v-model="mobile" placeholder="Mobile Number" />
        <input class="input" v-model="password" password placeholder="Password" />
        <input class="input" v-model="invite_code" placeholder="Invite Code (Parent ID)" />
        
        <view class="row">
            <view class="label">Position:</view>
            <radio-group @change="onPosChange">
                <radio value="L" checked>Left</radio>
                <radio value="R">Right</radio>
            </radio-group>
        </view>
        
        <view class="btn" @click="register">Register</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				mobile: '',
                password: '',
                invite_code: '',
                position: 'L'
			}
		},
		methods: {
            onPosChange(evt) {
                this.position = evt.detail.value;
            },
			async register() {
                try {
                    const res = await uni.$api('auth.php?action=register', 'POST', {
                        mobile: this.mobile,
                        password: this.password,
                        invite_code: this.invite_code,
                        position: this.position
                    });
                    if (res.code == 200) {
                        uni.showToast({ title: 'Success', icon: 'success' });
                        setTimeout(() => {
                            uni.navigateBack();
                        }, 1500);
                    } else {
                        uni.showToast({ title: res.message, icon: 'none' });
                    }
                } catch(e) {
                     uni.showToast({ title: 'Error', icon: 'none' });
                }
			}
		}
	}
</script>

<style>
.row { display: flex; align-items: center; margin-bottom: 20px; }
.label { margin-right: 10px; }
</style>
