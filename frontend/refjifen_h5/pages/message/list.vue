<template>
	<view class="container">
		<view class="header">
			<text class="title">通知公告</text>
		</view>

		<view class="list">
			<view v-for="(item, index) in list" :key="index" class="item" @click="goDetail(item.id)">
				<view class="item-content">
					<view class="item-title">{{ item.title }}</view>
					<view class="item-date">{{ item.created_at }}</view>
				</view>
				<view class="arrow">></view>
			</view>

			<view v-if="list.length === 0" class="empty">暂无公告</view>
		</view>
        
        <tab-bar current="pages/message/list"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
        components: { tabBar },
		data() {
			return {
				list: [],
				page: 1,
				loading: false
			};
		},
		onShow() {
			this.page = 1;
			this.list = [];
			this.fetchData();
		},
		onReachBottom() {
			this.fetchData();
		},
		methods: {
			async fetchData() {
				if (this.loading) return;
				this.loading = true;
				
				try {
                    const res = await uni.$api('articles.php?action=list&page=' + this.page);
                    if (res.code == 200) {
                        // API returns data list directly in res.data based on articles.php
                        // articles.php: json_out(200, "success", $list); -> res.data is the list
                        if (res.data && res.data.length > 0) {
                            this.list = this.list.concat(res.data);
                            this.page++;
                        }
                    }
                } catch(e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
			},
			goDetail(id) {
				uni.navigateTo({
					url: '/pages/message/detail?id=' + id
				});
			}
		}
	}
</script>

<style>
	.container {
		background-color: #f8f8f8;
		min-height: 100vh;
		padding: 0 !important;
		padding-bottom: 70px !important;
	}

	.header {
		padding: 10px;
		background: #fff;
		border-bottom: 1px solid #eee;
	}

	.title {
		font-size: 18px;
		font-weight: bold;
	}

	.list {
		padding: 10px;
	}

	.item {
		background: #fff;
		padding: 15px;
		margin-bottom: 10px;
		border-radius: 8px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.item-title {
		font-size: 16px;
		color: #333;
		margin-bottom: 5px;
	}

	.item-date {
		font-size: 12px;
		color: #999;
	}

	.arrow {
		color: #ccc;
		font-size: 18px;
	}
	
	.empty {
		text-align: center;
		padding: 50px;
		color: #999;
	}
</style>
