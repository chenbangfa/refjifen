<template>
	<view class="container">
        <!-- Address Section -->
        <view class="address-box" @click="selectAddress">
            <view class="addr-info" v-if="address">
                <view class="addr-user">
                    <text class="name">{{ address.name }}</text>
                    <text class="mobile">{{ address.mobile }}</text>
                </view>
                <view class="addr-detail">{{ address.province }} {{ address.city }} {{ address.district }} {{ address.detail }}</view>
            </view>
            <view class="addr-empty" v-else>
                <text>请选择收货地址</text>
                <text class="iconfont icon-right"></text>
            </view>
            <view class="addr-line"></view>
        </view>
        
        <!-- Product List -->
        <view class="order-list">
            <view class="product-item" v-for="item in cart.items" :key="item.id">
                <image :src="item.image.split(',')[0]" mode="aspectFill" class="p-img"></image>
                <view class="p-info">
                    <view class="p-title">{{ item.title }}</view>
                    <view class="p-meta">
                        <view class="p-price" v-if="cart.zone=='A'">¥{{ parseFloat(item.price) }}<text class="p-unit">/{{ item.unit||'件' }}</text></view>
                        <view class="p-price" v-else style="color:#ff9800;">{{ parseFloat(item.price) }}<text class="p-unit" style="color:#ffcc80">券/{{ item.unit||'件' }}</text></view>
                        <view class="p-qty">x {{ item.qty }}{{ item.unit }}</view>
                    </view>
                </view>
            </view>
        </view>
        
        <!-- Remarks -->
        <view class="cell-group">
            <view class="cell-item">
                <text class="label">备注</text>
                <input class="input" v-model="remark" placeholder="选填，请先和商家协商一致" />
            </view>
        </view>
        
        <!-- Footer -->
        <view class="footer-bar">
            <view class="total-info">
                <text>合计:</text> 
                <text class="total-price" v-if="cart.zone=='A'">¥{{ totalPrice }}</text>
                <text class="total-price" v-else>{{ totalPrice }}券</text>
            </view>
            <view class="est-points" v-if="cart.zone=='A' && estPoints > 0">
                <text>预估赠送: {{ estPoints }} ({{ estRatio }}倍)</text>
            </view>
            <view class="pay-btn" @click="prePay">立即支付</view>
        </view>
        
        <!-- Password Keyboard (Simple Modal) -->
        <view class="pwd-mask" v-if="showPwd" @click="showPwd=false"></view>
        <view class="pwd-modal" v-if="showPwd">
            <view class="pwd-title">输入支付密码</view>
            <input type="password" v-model="password" class="pwd-input" focus maxlength="6" placeholder="请输入6位支付密码" />
            <view class="pwd-btns">
                <view class="btn cancel" @click="showPwd=false">取消</view>
                <view class="btn confirm" @click="doPay">确定</view>
            </view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				cart: { zone: '', items: [] },
                address: null,
                remark: '',
                showPwd: false,
                password: '',
                rules: [],
                user: {}
			}
		},
		computed: {
            totalPrice() {
                if(!this.cart.items) return 0;
                let t = this.cart.items.reduce((s, i) => s + i.price * i.qty, 0);
                return parseFloat(t).toFixed(2);
            },
            estRatio() {
                if(!this.rules || !this.rules.length) return 0.0;
                let total = parseFloat(this.totalPrice);
                // Find highest rule where min_amount <= total
                // Rules expected to be sorted ASC
                let ratio = 0.0;
                for(let r of this.rules) {
                    if(total >= parseFloat(r.min_amount)) {
                        ratio = parseFloat(r.ratio);
                    }
                }
                return ratio;
            },
            estPoints() {
                return (parseFloat(this.totalPrice) * this.estRatio).toFixed(2);
            }
        },
        onShow() {
            // Check if address selected from list
            const selAddr = uni.getStorageSync('selected_address');
            if(selAddr) {
                this.address = selAddr;
                uni.removeStorageSync('selected_address');
            } else if(!this.address) {
                this.loadDefaultAddress();
            }
            this.loadRules();
            this.fetchUserInfo();
        },
		onLoad() {
			const c = uni.getStorageSync('checkout_cart');
            if(c) {
                this.cart = c;
            } else {
                uni.navigateBack();
            }
		},
		methods: {
            async loadRules() {
                try {
                    const res = await uni.$api('mall.php?action=config');
                    if(res.code == 200) {
                        this.rules = res.data.reward_rules || [];
                    }
                } catch(e){}
            },
            async fetchUserInfo() {
                try {
                    const res = await uni.$api('account.php?action=info');
                    if(res.code == 200) {
                        this.user = res.data;
                    }
                } catch(e) {}
            },
			async loadDefaultAddress() {
                try {
                     const res = await uni.$api('address.php?action=list');
                     if(res.code == 200 && res.data.length > 0) {
                         this.address = res.data[0]; // First one (sorted by default)
                     }
                } catch(e) {}
			},
            selectAddress() {
                uni.navigateTo({ url: '/pages/address/list?select=1' });
            },
            prePay() {
                if(!this.address) return uni.showToast({ title: '请选择收货地址', icon:'none'});
                
                if(this.user && !this.user.has_pay_password) {
                     uni.showModal({
                         title: '提示',
                         content: '请先设置支付密码',
                         confirmText: '去设置',
                         success: (res) => {
                             if(res.confirm) uni.navigateTo({ url: '/pages/my/pay_password' });
                         }
                     });
                     return;
                }
                
                this.password = '';
                this.showPwd = true;
            },
            async doPay() {
                if(!this.password) return uni.showToast({ title: '请输入密码', icon:'none'});
                
                uni.showLoading({ title: '支付中' });
                try {
                    const postData = {
                        items: this.cart.items,
                        zone: this.cart.zone,
                        pay_password: this.password,
                        receiver_info: this.address,
                        remark: this.remark
                    };
                    
                    const res = await uni.$api('mall.php?action=buy', 'POST', postData);
                    uni.hideLoading();
                    
                    if(res.code == 200) {
                        uni.showToast({ title: '支付成功' });
                        // Clear cart
                        uni.removeStorageSync('checkout_cart');
                        // Navigate to Success or Order List
                        setTimeout(() => {
                            uni.reLaunch({ url: '/pages/mall/profile' });
                        }, 1500);
                    } else {
                        uni.showToast({ title: res.message || '支付失败', icon:'none' });
                    }
                    this.showPwd = false;
                } catch(e) {
                    uni.hideLoading();
                    uni.showToast({ title: '请求失败', icon:'none' });
                }
            }
		}
	}
</script>

<style>
    .container { padding: 10px; padding-bottom: 60px; }
    
    .address-box { background: #fff; border-radius: 8px; padding: 15px; margin-bottom: 12px; position: relative; overflow: hidden; }
    .addr-info { display: flex; flex-direction: column; }
    .addr-user { margin-bottom: 5px; font-size: 16px; font-weight: bold; }
    .addr-user .mobile { margin-left: 10px; color: #666; font-size: 14px; font-weight: normal; }
    .addr-detail { color: #333; font-size: 14px; line-height: 1.4; }
    .addr-empty { height: 40px; display: flex; align-items: center; justify-content: center; color: #666; }
    .addr-line { position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: repeating-linear-gradient(-45deg, #ff6c6c 0, #ff6c6c 20px, #fff 0, #fff 25px, #1989fa 0, #1989fa 45px, #fff 0, #fff 50px); }
    
    .order-list { background: #fff; border-radius: 8px; margin-bottom: 12px; padding: 0 10px; }
    .product-item { display: flex; padding: 15px 0; border-bottom: 1px solid #f9f9f9; }
    .product-item:last-child { border-bottom: none; }
    .p-img { width: 80px; height: 80px; border-radius: 6px; background: #eee; margin-right: 10px; }
    .p-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .p-title { font-size: 14px; color: #333; line-height: 1.4; }
    .p-meta { display: flex; justify-content: space-between; align-items: center; }
    .p-price { font-size: 16px; font-weight: bold; color: #333; }
    .p-qty { color: #999; font-size: 13px; }
    
    .cell-group { background: #fff; border-radius: 8px; padding: 0 15px; margin-bottom: 12px; }
    .cell-item { display: flex; align-items: center; height: 50px; }
    .label { width: 60px; font-size: 14px; color: #333; }
    .input { flex: 1; font-size: 14px; }
    
    .footer-bar { position: fixed; bottom: 0; left: 0; width: 100%; height: 50px; background: #fff; display: flex; align-items: center; padding: 0 15px; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); box-sizing: border-box; justify-content: space-between; z-index: 100; }
    .total-info { font-size: 14px; color: #333; }
    .est-points { position: absolute; top: -30px; left: 0; width: 100%; background: #fdf6ec; color: #e6a23c; font-size: 12px; height: 30px; display: flex; align-items: center; padding: 0 15px; box-sizing: border-box; }
    .total-price { font-size: 18px; color: #ff4d4f; font-weight: bold; margin-left: 5px; }
    .pay-btn { background: #07C160; color: #fff; padding: 8px 25px; border-radius: 20px; font-size: 14px; font-weight: bold; }
    
    /* Pwd Modal */
    .pwd-mask { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
    .pwd-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; background: #fff; border-radius: 12px; padding: 20px; z-index: 1000; text-align: center; }
    .pwd-title { font-size: 16px; font-weight: bold; margin-bottom: 20px; }
    .pwd-input { border: 1px solid #ddd; height: 40px; border-radius: 4px; padding: 0 10px; margin-bottom: 20px; text-align: center; }
    .pwd-btns { display: flex; justify-content: space-between; }
    .pwd-btns .btn { width: 48%; padding: 10px 0; border-radius: 4px; font-size: 14px; }
    .btn.cancel { background: #f5f5f5; color: #666; }
    .btn.confirm { background: #07C160; color: #fff; }
    .p-unit { font-size: 12px; color: #ccc; font-weight: normal; margin-left: 1px; }
</style>
