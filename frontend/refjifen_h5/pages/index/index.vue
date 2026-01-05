<template>
	<view class="container">
        <view class="card">
            <view>Balance</view>
            <view class="big-num">{{ assets.balance || 0 }}</view>
        </view>
        
        <view class="card">
            <view>Traffic Points</view>
            <view class="big-num">{{ assets.traffic_points || 0 }}</view>
        </view>
        
        <view class="btn" @click="checkin" :disabled="loading">
            {{ loading ? 'Processing...' : 'Daily Checkin' }}
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				assets: {},
                loading: false
			}
		},
		onShow() {
			this.loadData();
		},
		methods: {
			async loadData() {
                try {
                     const res = await uni.$api('account.php?action=info');
                     if (res.code == 200) {
                         this.assets = res.data;
                     }
                } catch(e) {}
			},
            async checkin() {
                this.loading = true;
                try {
                    // Assuming we will implement this API
                    const res = await uni.$api('user_actions.php?action=checkin', 'POST'); 
                    uni.showToast({ title: res.message, icon: 'none' });
                    this.loadData();
                } catch(e) {
                    uni.showToast({ title: 'Failed', icon: 'none' });
                } finally {
                    this.loading = false;
                }
            }
		}
	}
</script>

<style>
    .card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 15px; text-align: center; }
    .big-num { font-size: 30px; font-weight: bold; color: #333; margin-top: 10px; }
</style>
