<template>
	<view class="container">
        <view class="grid">
            <view class="product" v-for="item in products" :key="item.id">
                <view class="p-name">{{ item.title }}</view>
                <view class="p-price">¥{{ item.price }}</view>
                <view class="p-zone">Zone {{ item.zone }}</view>
                <view class="buy-btn" @click="buy(item)">Buy</view>
            </view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				products: []
			}
		},
		onShow() {
            this.loadProducts();
		},
		methods: {
            async loadProducts() {
                const res = await uni.$api('mall.php?action=products');
                if (res.code == 200) {
                    this.products = res.data;
                }
            },
			buy(item) {
                uni.showModal({
                    title: 'Confirm Purchase',
                    content: `Buy ${item.title} for ${item.price}?`,
                    success: async (r) => {
                        if (r.confirm) {
                            const res = await uni.$api('mall.php?action=buy', 'POST', {
                                product_id: item.id,
                                pay_password: '123' // Mock
                            });
                             uni.showToast({ title: res.message, icon: 'none' });
                        }
                    }
                })
			}
		}
	}
</script>

<style>
    .grid { display: flex; flex-wrap: wrap; justify-content: space-between; }
    .product { width: 48%; background: #fff; padding: 10px; margin-bottom: 15px; border-radius: 5px; box-sizing: border-box; }
    .p-name { font-weight: bold; }
    .p-price { color: red; margin: 5px 0; }
    .buy-btn { background: #ff9900; color: #fff; text-align: center; padding: 5px; border-radius: 3px; }
</style>
