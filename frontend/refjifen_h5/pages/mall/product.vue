<template>
	<view class="container">
		<!-- 商品图片 -->
        <image :src="product.image ? product.image.split(',')[0] : ''" mode="aspectFill" class="p-image"></image>
        
        <!-- 商品信息 -->
        <view class="info-card">
            <view class="price-row">
                <view class="price" v-if="product.zone == 'A'">¥<text class="num">{{ fmt(product.price) }}</text><text class="p-unit">/{{ product.unit||'件' }}</text></view>
                <view class="price" v-else><text class="num">{{ fmt(product.price) }}</text><text class="p-unit" style="font-size:12px;color:#ccc;font-weight:normal">/{{ product.unit||'件' }}</text> 券</view>
                <view class="stock">库存: {{ product.stock }}</view>
            </view>
            <view class="title">{{ product.title }}</view>
            <view class="sales">已售 {{ product.sales }}{{ product.unit }}</view>
        </view>
        
        <!-- 商品详情 -->
        <view class="detail-card">
            <view class="section-title">商品详情</view>
            <rich-text :nodes="product.description || '<p>暂无详情</p>'"></rich-text>
        </view>
        
        <!-- 底部操作栏 -->
        <view class="bottom-bar">
             <view class="bar-btn cart" @click="addToCart">加入购物车</view>
             <view class="bar-btn buy" @click="buyNow">立即购买</view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
                product: {}
			}
		},
		onLoad(options) {
			this.id = options.id || 0;
            this.loadProduct();
		},
		methods: {
            fmt(val) { return parseFloat(val||0).toFixed(2); },
            async loadProduct() {
                uni.showLoading({title:'加载中'});
                const res = await uni.$api('mall.php?action=product_detail&id='+this.id);
                uni.hideLoading();
                if(res.code == 200) {
                    this.product = res.data;
                    if(this.product.description) {
                         this.product.description = this.product.description.replace(/\<img/gi, '<img style="max-width:100% !important;height:auto;display:block;"');
                    }
                } else {
                    uni.showToast({title: res.message, icon:'none'});
                }
            },
            addToCart() {
                // Add to Storage Cart
                let cartKey = 'cart_' + this.product.zone;
                let cart = uni.getStorageSync(cartKey) || [];
                
                let found = cart.find(c => c.id == this.product.id);
                if(found) {
                    found.qty++;
                } else {
                    cart.push({ ...this.product, qty: 1 });
                }
                
                uni.setStorageSync(cartKey, cart);
                
                uni.showToast({title: '已加入购物车', icon:'success'});
                
                // Redirect to Category Page (Zone)
                setTimeout(() => {
                    // Navigate to Category with Zone
                    // Since specific request: "跳转到对应的pages/mall/category页面"
                    // And Category page handles "Load cart from storage" (Step 1708 plan)
                    uni.reLaunch({ url: `/pages/mall/category?zone=${this.product.zone}` });
                }, 800);
            },
            buyNow() {
                // Prepare item for Checkout
                // Checkout expects 'checkout_cart' in storage
                let items = [{ ...this.product, qty: 1 }];
                uni.setStorageSync('checkout_cart', { zone: this.product.zone, items: items });
                
                uni.navigateTo({ url: '/pages/mall/checkout' });
            }
		}
	}
</script>

<style>
    .container { padding: 10px; padding-bottom: 80px; background: #f8f8f8; min-height: 100vh; box-sizing: border-box; }
    .p-image { width: 100%; height: 375px; background: #fff; border-radius: 12px; margin-bottom: 10px; }
    
    .info-card { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 12px; }
    .price-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
    .price { color: #ff4d4f; font-size: 14px; font-weight: bold; }
    .price .num { font-size: 24px; }
    .stock { color: #999; font-size: 12px; }
    .title { font-size: 16px; font-weight: bold; color: #333; line-height: 1.4; margin-bottom: 10px; }
    .sales { color: #999; font-size: 12px; }
    
    .detail-card { background: #fff; padding: 15px; min-height: 200px; border-radius: 12px; }
    .section-title { font-size: 15px; font-weight: bold; margin-bottom: 15px; padding-left: 10px; border-left: 3px solid #07C160; }
    
    .bottom-bar { position: fixed; bottom: 0; left: 0; width: 100%; height: 60px; background: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); box-sizing: border-box; }
    
    .bar-btn { flex: 1; height: 40px; line-height: 40px; text-align: center; border-radius: 20px; font-size: 14px; color: #fff; margin: 0 5px; }
    .bar-btn.cart { background: #ff9800; }
    .bar-btn.buy { background: #ff4d4f; }
    .p-unit { font-size: 12px; color: #ccc; font-weight: normal; margin-left: 1px; vertical-align: bottom; }
</style>
