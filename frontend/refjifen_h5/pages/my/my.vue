<template>
	<view class="container">
        <view class="header">
            <view class="avatar">A</view>
            <view class="info">
                <view class="name">User: {{ user.id }}</view>
                <view class="sub">Level: {{ user.level == 1 ? 'Gold' : (user.level == 2 ? 'Diamond' : 'Normal') }}</view>
                <view class="sub">Role: {{ user.is_sub_account ? 'Sub Account' : 'Master Account' }}</view>
            </view>
        </view>
        
        <view class="menu">
            <view class="menu-item" @click="toggleAccount" v-if="user.has_sub_account || user.is_sub_account">
                Switch Account
            </view>
            <view class="menu-item" @click="createSub" v-if="!user.is_sub_account && !user.has_sub_account">
                Activate Sub Account
            </view>
            <view class="menu-item red" @click="logout">Logout</view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				user: {}
			}
		},
		onShow() {
            // Get latest complete info
            this.loadInfo();
		},
		methods: {
            async loadInfo() {
                 let res = await uni.$api('account.php?action=info');
                 if(res.code == 200) {
                     // Check if has sub account (simple logic: check linked)
                     // Implementation in account.php returns valid info
                     this.user = res.data;
                     this.user.has_sub_account = !!res.data.linked_mobile; 
                 }
            },
			async toggleAccount() {
                let res = await uni.$api('auth.php?action=switch_account', 'POST');
                if (res.code == 200) {
                    uni.setStorageSync('token', res.token);
                    uni.showToast({ title: 'Switched', icon: 'success' });
                    this.loadInfo();
                } else {
                    uni.showToast({ title: res.message, icon: 'none' });
                }
			},
            createSub() {
                // Navigate to a sub-account creation form (Reuse register or new page)
                // For simplicity, let's say we have a dialog or simple page.
                // Here: Just prompt or hardcode for demo. Real app needs page.
                uni.showModal({
                    title: 'Activate Sub Account',
                    content: 'This will create a new ID linked to your mobile.',
                    success: async (r) => {
                        if(r.confirm) {
                            // In real app, ask for position and password.
                            // Mocking call with default params
                            let res = await uni.$api('account.php?action=activate_sub', 'POST', {
                                sub_mobile: this.user.mobile + '_sub', // Auto-gen or ask
                                password: '123', // Ask user
                                position: 'L' // Ask user
                            });
                            uni.showToast({ title: res.message, icon: 'none' });
                            this.loadInfo();
                        }
                    }
                });
            },
            logout() {
                uni.removeStorageSync('token');
                uni.reLaunch({ url: '/pages/login/login' });
            }
		}
	}
</script>

<style>
    .header { display: flex; align-items: center; background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .avatar { width: 60px; height: 60px; background: #ddd; border-radius: 50%; text-align: center; line-height: 60px; margin-right: 15px; }
    .menu-item { background: #fff; padding: 15px; border-bottom: 1px solid #eee; }
    .red { color: red; }
</style>
