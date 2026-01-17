<template>
	<view class="cat-container">
        <!-- 左侧分类栏 -->
        <scroll-view scroll-y class="left-nav">
            <view class="nav-item" 
                :class="{active: currentId == item.id}" 
                v-for="item in categories" 
                :key="item.id"
                @click="switchCategory(item.id)">
                {{ item.name }}
            </view>
        </scroll-view>
        
        <!-- 右侧商品列表 -->
        <scroll-view scroll-y class="right-content" @scrolltolower="loadMore">
            <view class="zone-banner" v-if="zone">
                 <text v-if="zone=='A'">会员专区 (余额)</text>
                 <text v-else>兑换专区 (券)</text>
            </view>
            
            <view class="list-container">
                <view class="product-item" v-for="item in list" :key="item.id" @click="goProduct(item)">
                    <image :src="item.image.split(',')[0]" mode="aspectFill" class="p-img"></image>
                    <view class="p-info">
                        <view class="p-title">{{ item.title }}</view>
                        <view class="p-unit-sales">已售{{item.sales}}{{item.unit}}</view>
                        <view class="p-bottom">
                            <view class="p-price" v-if="zone=='A'">¥{{ parseFloat(item.price) }}<text class="p-unit">/{{ item.unit||'件' }}</text></view>
                            <view class="p-price" v-else style="color:#ff9800;">{{ parseFloat(item.price) }}<text class="p-unit" style="color:#ffcc80">券/{{ item.unit||'件' }}</text></view>
                            
                            <!-- Stepper Control -->
                            <view class="step-control" v-if="getItemQty(item) > 0">
                                <view class="step-btn" @click.stop="updateCart(item, -1)">-</view>
                                <view class="step-num">{{ getItemQty(item) }}</view>
                                <view class="step-btn add" @click.stop="updateCart(item, 1)">+</view>
                            </view>
                            <view class="cart-btn" v-else @click.stop="addToCart(item)">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="#fff" style="margin-right:2px;">
                                    <path d="M11 9h2V6h3V4h-3V1h-2v3H8v2h3v3zm-4 9c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zm-8.9-5h7.45c.75 0 1.41-.41 1.75-1.03l3.86-7.01L19.42 4l-3.87 7H8.53L4.27 2H1v2h2l3.6 7.59-1.35 2.44C4.52 14.37 4.84 15 5.5 15h11v-2H5.5l1.1-2z"></path>
                                </svg> 加购
                            </view>
                        </view>
                    </view>
                </view>
                <view v-if="list.length == 0" class="empty-tip">暂无商品</view>
                <view style="height: 60px;"></view><!-- Spacer for cart bar -->
            </view>
        </scroll-view>
        
        <!-- 底部购物车栏 -->
        <view class="cart-bar" v-if="totalCount > 0">
             <view class="cart-left" @click="showCartDetail = !showCartDetail">
                 <view class="cart-icon-wrap">
                     <svg viewBox="0 0 24 24" width="28" height="28" fill="#fff">
                         <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.59-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"></path>
                     </svg>
                     <view class="badge">{{ totalCount }}</view>
                 </view>
                 <view class="cart-total">
                     <text class="total-price" v-if="zone=='A'">¥{{ totalPrice }}</text>
                     <text class="total-price" v-else>{{ totalPrice }}券</text>
                     <text class="delivery-tip">免运费</text>
                 </view>
             </view>
             <view class="cart-right" @click="goCheckout">去结算</view>
        </view>
        
        <!-- 购物车详情弹窗 -->
        <view class="cart-modal-mask" v-if="showCartDetail" @click="showCartDetail = false"></view>
        <view class="cart-popup" v-if="showCartDetail">
            <view class="popup-header">
                <text>已选商品</text>
                <text class="clear-btn" @click="clearCart">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="#999" style="margin-right:2px; vertical-align: middle;">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                    </svg> 清空
                </text>
            </view>
            <scroll-view scroll-y class="popup-list">
                <view class="popup-item" v-for="(item, index) in cart" :key="index">
                    <view class="popup-name">{{ item.title }}</view>
                    <view class="popup-price" v-if="zone=='A'">¥{{ parseFloat(item.price) }}<text class="p-unit">/{{ item.unit||'件' }}</text></view>
                    <view class="popup-price" v-else>{{ parseFloat(item.price) }}<text class="p-unit">/{{ item.unit||'件' }}</text>券</view>
                    <view class="step-control">
                        <view class="step-btn" @click="updateCart(item, -1)">-</view>
                        <view class="step-num">{{ item.qty }}</view>
                        <view class="step-btn add" @click="updateCart(item, 1)">+</view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <tab-bar :current="currentPath"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
        components: { tabBar },
		data() {
			return {
				zone: '',
				categories: [],
				currentId: 0,
				list: [] ,
				cart: [], // { id, title, price, qty ... }
				showCartDetail: false
			}
		},
        computed: {
            currentPath() { return `pages/mall/category?zone=${this.zone}`; },
            totalCount() { return this.cart.reduce((s, i) => s + i.qty, 0); },
            totalPrice() { 
                let t = this.cart.reduce((s, i) => s + i.price * i.qty, 0); 
                return parseFloat(t).toFixed(2);
            }
        },
        onShow() {
            if(this.zone) {
                this.cart = uni.getStorageSync('cart_' + this.zone) || [];
            }
        },
		onLoad(options) {
			this.zone = options.zone || '';
            if(this.zone == 'A') {
                uni.setNavigationBarTitle({ title: '会员专区' });
            } else if(this.zone == 'B') {
                uni.setNavigationBarTitle({ title: '兑换专区' });
            }
            this.loadCategories();
            // Load initial cart
            if(this.zone) {
                this.cart = uni.getStorageSync('cart_' + this.zone) || [];
            }
		},
		methods: {
			async loadCategories() {
                try {
                     const res = await uni.$api('mall.php?action=categories');
                     if(res.code == 200) {
                         // Add 'All' category
                         this.categories = [{id: 0, name: '全部'}, ...res.data];
                         this.currentId = 0;
                         this.loadProducts();
                     }
                } catch(e) {}
			},
            switchCategory(id) {
                this.currentId = id;
                this.loadProducts();
            },
            async loadProducts() {
                try {
                    let url = `mall.php?action=products&zone=${this.zone}`;
                    if(this.currentId) url += `&category_id=${this.currentId}`;
                    
                    const res = await uni.$api(url);
                    if(res.code == 200) {
                        this.list = res.data;
                    }
                } catch(e) {}
            },
            addToCart(item) {
                let found = this.cart.find(c => c.id == item.id);
                if(found) {
                    found.qty++;
                } else {
                    this.cart.push({ ...item, qty: 1 });
                }
                uni.setStorageSync('cart_' + this.zone, this.cart);
            },
            updateCart(item, delta) {
                let found = this.cart.find(c => c.id == item.id);
                
                if(found) {
                     found.qty += delta;
                     if(found.qty <= 0) {
                         const idx = this.cart.indexOf(found);
                         this.cart.splice(idx, 1);
                         if(this.cart.length == 0) this.showCartDetail = false;
                     }
                } else if (delta > 0) {
                    this.addToCart(item);
                    return; 
                }
                uni.setStorageSync('cart_' + this.zone, this.cart);
            },
            getItemQty(item) {
                const found = this.cart.find(c => c.id == item.id);
                return found ? found.qty : 0;
            },
            clearCart() {
                this.cart = [];
                this.showCartDetail = false;
                uni.setStorageSync('cart_' + this.zone, this.cart);
            },
            goProduct(item) {
                uni.navigateTo({ url: `/pages/mall/product?id=${item.id}` });
            },
            loadMore() { },
            goCheckout() {
                // Save cart to global or storage
                uni.setStorageSync('checkout_cart', { zone: this.zone, items: this.cart });
                uni.navigateTo({ url: '/pages/mall/checkout' });
            }
		}
	}
</script>

<style>
    .cat-container { display: flex; height: 100vh; background: #fff; position: relative; padding-bottom: 50px; box-sizing: border-box; }
    
    .left-nav { width: 90px; height: 100%; background: #f8f8f8; padding-bottom: 50px; box-sizing: border-box; }
    .nav-item { height: 50px; line-height: 50px; text-align: center; font-size: 14px; color: #666; position: relative; }
    .nav-item.active { background: #fff; color: #07C160; font-weight: bold; }
    .nav-item.active::before { content: ''; position: absolute; left: 0; top: 15px; height: 20px; width: 4px; background: #07C160; }
    
    .right-content { flex: 1; height: 100%; padding: 10px; box-sizing: border-box; padding-bottom: 60px; }
    
    .zone-banner { background: #E8F5EB; color: #07C160; padding: 10px; border-radius: 8px; font-size: 14px; margin-bottom: 15px; font-weight: bold; text-align: center; }
    
    .product-item { display: flex; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; }
    .p-img { width: 90px; height: 90px; border-radius: 6px; margin-right: 10px; background: #eee; }
    .p-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .p-title { font-size: 14px; font-weight: bold; color: #333; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
    .p-unit-sales { font-size: 12px; color: #999; margin: 3px 0; }
    .p-bottom { display: flex; justify-content: space-between; align-items: center; }
    .p-price { font-size: 16px; color: #07C160; font-weight: bold; }
    .cart-btn { background: #07C160; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; }
    .cart-btn .iconfont { margin-right: 2px; font-size: 12px; }
    
    .empty-tip { text-align: center; margin-top: 50px; color: #999; font-size: 14px; }

    /* Cart Bar - Lifted up to avoid TabBar */
    .cart-bar { position: fixed; bottom: 50px; left: 0; width: 100%; height: 50px; background: #333; z-index: 1001; display: flex; color: #fff; }
    .cart-left { flex: 2; display: flex; align-items: center; padding-left: 20px; position: relative; }
    .cart-icon-wrap { position: relative; top: -10px; width: 50px; height: 50px; background: #07C160; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); margin-right: 15px; }
    .cart-icon-wrap .iconfont { font-size: 24px; color: #fff; }
    .badge { position: absolute; top: 0; right: 0; background: #ff4d4f; color:#fff; font-size: 10px; padding: 2px 6px; border-radius: 10px; line-height: 1; }
    .cart-total { display: flex; flex-direction: column; }
    .total-price { font-size: 18px; font-weight: bold; line-height: 1.2; }
    .delivery-tip { font-size: 10px; color: #999; }
    .cart-right { flex: 1; background: #07C160; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; }
    
    /* Cart Detail Modal - Lifted up */
    .cart-modal-mask { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .cart-popup { position: fixed; bottom: 100px; left: 0; width: 100%; background: #fff; z-index: 1000; border-radius: 12px 12px 0 0; padding-bottom: 20px; }

    /* Fix scrollview height issues with rigid layout */
    .left-nav, .right-content {
        padding-bottom: 100px; /* Ensure content clears cart bar + tab bar */
    }

    /* Stepper */
    .step-control { display: flex; align-items: center; }
    .step-btn { width: 24px; height: 24px; line-height: 22px; text-align: center; border: 1px solid #ccc; border-radius: 50%; color: #666; font-size: 16px; box-sizing: border-box; }
    .step-btn.add { background: #07C160; color: #fff; border-color: #07C160; line-height: 24px; }
    .step-num { width: 30px; text-align: center; font-size: 14px; color: #333; }

    /* Popup Items */
    .popup-header { display: flex; justify-content: space-between; padding: 10px 15px; background: #f5f5f5; font-size: 14px; color: #666; border-radius: 12px 12px 0 0; }
    .clear-btn { display: flex; align-items: center; }
    .clear-btn .iconfont { margin-right: 4px; font-size: 14px; }
    
    .popup-list { max-height: 300px; padding: 0 15px; box-sizing: border-box; }
    .popup-item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
    .popup-name { flex: 1; font-size: 14px; color: #333; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .popup-price { width: 80px; text-align: right; font-size: 14px; color: #ff4d4f; font-weight: bold; margin-right: 15px; }
    .p-unit { font-size: 12px; color: #ccc; font-weight: normal; margin-left: 1px; }
</style>
