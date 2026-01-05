<template>
	<view class="container">
		<view class="title">Welcome Back</view>
        <input class="input" v-model="mobile" placeholder="Mobile Number" />
        <input class="input" v-model="password" password placeholder="Password" />
        <view class="btn" @click="login">Login</view>
        <view style="margin-top:20px; text-align:center;" @click="goRegister">Create a new account</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				mobile: '',
                password: ''
			}
		},
		methods: {
			async login() {
                try {
                    const res = await uni.$api('auth.php?action=login', 'POST', {
                        mobile: this.mobile,
                        password: this.password
                    });
                    if (res.code == 200) {
                        uni.setStorageSync('token', res.token);
                        uni.setStorageSync('user', res.user);
                        uni.switchTab({ url: '/pages/index/index' });
                    } else {
                        uni.showToast({ title: res.message, icon: 'none' });
                    }
                } catch(e) {
                     uni.showToast({ title: 'Error', icon: 'none' });
                }
			},
            goRegister() {
                uni.navigateTo({ url: '/pages/login/register' });
            }
		}
	}
</script>

<style>
.title { font-size: 24px; font-weight: bold; margin-bottom: 30px; text-align: center; }
</style>
