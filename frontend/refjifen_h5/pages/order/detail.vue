<template>
	<view class="container">
        <!-- 头部状态 (Gradient Background) -->
        <view class="header-status">
            <view class="status-title">{{ statusText }}</view>
            <view class="status-desc">{{ statusDesc }}</view>
        </view>
        
        <!-- 售后信息 -->
        <view class="card as-card" v-if="order.after_sales_info" style="margin-top: -30px; position: relative; z-index: 2;">
            <view class="as-title">售后进度</view>
            <view class="info-row">
                <text class="label">售后状态</text>
                <text class="value status-text">{{ getRefundStatus(order.after_sales_info.status) }}</text>
            </view>
            <view class="info-row">
                <text class="label">售后类型</text>
                <text class="value">{{ order.after_sales_info.type==1?'仅退款':'退货退款' }}</text>
            </view>
            <view class="info-row">
                <text class="label">申请原因</text>
                <text class="value">{{ order.after_sales_info.reason }}</text>
            </view>
             <view class="info-row" v-if="order.after_sales_info.admin_reply">
                <text class="label">商家回复</text>
                <text class="value highlight">{{ order.after_sales_info.admin_reply }}</text>
            </view>
            <view class="as-imgs" v-if="order.after_sales_info.images">
                <image v-for="(img,i) in order.after_sales_info.images.split(',')" :key="i" :src="img" mode="aspectFill" @click="previewImage(img)"></image>
            </view>
            <view class="as-actions" v-if="order.after_sales_info.status == 0">
                <view class="btn outline small" @click="cancelRefund">取消售后</view>
            </view>
        </view>

        <!-- 物流信息 (If shipped) -->
        <view class="card logistics-card" v-if="order.status >= 2" :style="{marginTop: order.after_sales_info ? '10px' : '-30px'}">
            <view class="l-icon"><text class="iconfont icon-delivery"></text></view>
            <view class="l-info">
                <view class="l-company">快递公司: {{ order.express_company || '未知' }}</view>
                <view class="l-no">快递单号: {{ order.tracking_number || '暂无' }}</view>
                <view class="l-time" v-if="order.delivery_time">发货时间: {{ order.delivery_time }}</view>
            </view>
        </view>
        
        <!-- 收货地址 -->
        <view class="card address-card">
            <view class="a-icon"><text class="iconfont icon-location"></text></view>
            <view class="a-info">
                <view class="user-row">
                    <text class="name">{{ address.name }}</text>
                    <text class="mobile">{{ address.mobile }}</text>
                </view>
                <view class="addr-text">
                    {{ address.province || '' }} {{ address.city || '' }} {{ address.district || '' }} {{ address.detail || address.address || '' }}
                </view>
            </view>
        </view>
        
        <!-- 商品列表 -->
        <view class="card goods-card">
            <view class="goods-item" v-for="(item, index) in order.goods_list" :key="index">
                <image :src="item.image.split(',')[0]" mode="aspectFill" class="g-img"></image>
                <view class="g-info">
                    <view class="g-title">{{ item.title }}</view>
                    <view class="g-meta">
                        <text class="g-price" v-if="order.pay_type=='balance'">¥{{ parseFloat(item.price) }}</text>
                        <text class="g-price" v-else>{{ parseFloat(item.price) }}券</text>
                        <text class="g-num">x{{ item.num }}</text>
                    </view>
                </view>
            </view>
            <view class="goods-total">
                <text>实付款:</text>
                <text class="price-big" v-if="order.pay_type=='balance'">¥{{ order.total_price }}</text>
                <text class="price-big" v-else>{{ order.total_price }}券</text>
            </view>
        </view>
        
        <!-- 订单信息 -->
        <view class="card order-info">
            <view class="info-row">
                <text class="label">订单编号</text>
                <text class="value">{{ order.order_sn }}</text>
            </view>
            <view class="info-row">
                <text class="label">下单时间</text>
                <text class="value">{{ order.created_at }}</text>
            </view>
            <view class="info-row" v-if="order.pay_time">
                <text class="label">支付时间</text>
                <text class="value">{{ order.pay_time }}</text>
            </view>
             <view class="info-row" v-if="order.remark">
                <text class="label">买家留言</text>
                <text class="value">{{ order.remark }}</text>
            </view>
        </view>
        
        <!-- 底部操作栏 -->
        <view class="footer-actions" v-if="order.status == 0 || order.status == 2">
            <view class="btn outline" v-if="order.status==0" @click="cancelOrder">取消订单</view>
            <view class="btn primary" v-if="order.status==0" @click="payOrder">立即支付</view>
            <view class="btn primary" v-if="order.status==2" @click="confirmReceipt">确认收货</view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
                order: {},
                address: {}
			}
		},
        computed: {
            statusText() {
                const map = { '0': '等待买家付款', '1': '等待卖家发货', '2': '卖家已发货', '3': '交易成功', '4': '交易关闭', '5': '售后中' };
                return map[this.order.status] || '';
            },
            statusDesc() {
                 const map = { 
                     '0': '剩23小时59分自动关闭', 
                     '1': '您的包裹正在打包中', 
                     '2': '您的包裹在路上了', 
                     '3': '感谢您的支持', 
                     '4': '订单已取消',
                     '5': '售后处理中'
                 };
                 return map[this.order.status] || '';
            }
        },
		onLoad(options) {
			this.id = options.id;
            this.loadDetail();
		},
		methods: {
            getRefundStatus(st) {
                 return ['审核中', '已同意', '已拒绝', '已取消'][st] || '未知';
            },
            cancelRefund() {
                uni.showModal({
                    title: '提示', content: '确定撤销售后申请吗？',
                    success: async (res) => {
                        if(res.confirm) {
                             uni.showLoading({title:'处理中'});
                             try {
                                 const apiRes = await uni.$api('mall.php?action=cancel_refund', 'POST', { order_sn: this.order.order_sn });
                                 uni.hideLoading();
                                 if(apiRes.code == 200) {
                                     uni.showToast({title:'撤销成功'});
                                     this.loadDetail();
                                 } else {
                                     uni.showToast({title:apiRes.message, icon:'none'});
                                 }
                             } catch(e){uni.hideLoading();}
                        }
                    }
                })
            },
            previewImage(url) {
                uni.previewImage({ urls: [url] });
            },
			async loadDetail() {
                try {
                    const res = await uni.$api(`mall.php?action=order_detail&id=${this.id}`);
                    if(res.code == 200) {
                        this.order = res.data;
                        this.address = res.data.receiver_info || {};
                    }
                } catch(e) {}
			},
            payOrder() {
                uni.showToast({title: '跳转支付...', icon:'none'});
                // Implement pay jump
            },
            cancelOrder() {
                uni.showToast({title: '功能开发中', icon:'none'});
            },
            confirmReceipt() {
                uni.showModal({
                    title: '确认收货',
                    content: '确认已收到商品？',
                    success: async (res) => {
                        if (res.confirm) {
                            uni.showLoading({ title: '处理中' });
                            try {
                                const apiRes = await uni.$api('mall.php?action=confirm_receipt', 'POST', { id: this.id });
                                uni.hideLoading();
                                if (apiRes.code == 200) {
                                    uni.showToast({ title: '确认成功' });
                                    this.loadDetail();
                                } else {
                                    uni.showToast({ title: apiRes.message || '操作失败', icon: 'none' });
                                }
                            } catch (e) {
                                uni.hideLoading();
                                uni.showToast({ title: '网络错误', icon: 'none' });
                            }
                        }
                    }
                });
            }
		}
	}
</script>

<style>
    .container { padding: 10px; padding-bottom: 80px; background: #f5f5f5; min-height: 100vh; box-sizing: border-box; }
    
    .header-status { background: linear-gradient(90deg, #ff9000, #ff5000); color: #fff; padding: 30px 20px; margin: -10px -10px 10px -10px; padding-bottom: 40px; }
    .status-title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
    .status-desc { font-size: 12px; opacity: 0.9; }
    
    .card { background: #fff; border-radius: 10px; margin-bottom: 10px; padding: 15px; }
    
    /* Address */
    .address-card { display: flex; align-items: center; margin-top: -30px; position: relative; z-index: 1; }
    .a-icon { margin-right: 15px; color: #ff5000; font-size: 20px; }
    .user-row { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
    .user-row .mobile { font-size: 14px; color: #666; font-weight: normal; margin-left: 10px; }
    .addr-text { font-size: 13px; color: #333; line-height: 1.4; }
    
    /* Logistics */
    .logistics-card { display: flex; align-items: center; margin-top: -30px; position: relative; z-index: 1; }
    .logistics-card + .address-card { margin-top: 10px; } /* If both exist, fix margin */
    .l-icon { margin-right: 15px; color: #07C160; font-size: 20px; }
    .l-info { font-size: 13px; }
    .l-company { font-weight: bold; margin-bottom: 2px; }
    .l-no { color: #666; margin-bottom: 2px; }
    .l-time { color: #999; font-size: 12px; }
    
    /* Goods */
    .goods-item { display: flex; margin-bottom: 15px; }
    .g-img { width: 80px; height: 80px; border-radius: 6px; background: #eee; margin-right: 10px; }
    .g-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; height: 80px; }
    .g-title { font-size: 14px; color: #333; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .g-meta { display: flex; justify-content: space-between; align-items: center; }
    .g-price { font-size: 14px; color: #333; }
    .g-num { font-size: 12px; color: #999; }
    
    .goods-total { border-top: 1px solid #f9f9f9; padding-top: 10px; text-align: right; display: flex; justify-content: flex-end; align-items: center; font-size: 14px; }
    .price-big { font-size: 18px; color: #ff5000; font-weight: bold; margin-left: 5px; }
    
    /* Info */
    .info-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; color: #666; }
    
    /* Footer */
    .footer-actions { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; padding: 10px 15px; box-shadow: 0 -1px 5px rgba(0,0,0,0.05); display: flex; justify-content: flex-end; box-sizing: border-box; }
    .btn { padding: 6px 18px; border-radius: 20px; font-size: 13px; margin-left: 10px; }
    .btn.outline { border: 1px solid #ddd; color: #666; }
    .btn.primary { background: #ff5000; color: #fff; border: 1px solid #ff5000; }
    
    .as-title { font-weight: bold; font-size: 15px; margin-bottom: 10px; color: #333; border-bottom: 1px solid #f9f9f9; padding-bottom: 8px; }
    .status-text { color: #ff5000; font-weight: bold; }
    .highlight { color: #ff5000; }
    .as-card + .address-card { margin-top: 10px; }
    
    .as-imgs { display: flex; flex-wrap: wrap; margin-top: 10px; }
    .as-imgs image { width: 60px; height: 60px; border-radius: 4px; margin-right: 10px; margin-bottom: 10px; background:#f0f0f0; }
    .as-actions { display: flex; justify-content: flex-end; border-top: 1px solid #f9f9f9; padding-top: 10px; margin-top: 5px; }
    .btn.small { font-size: 12px; padding: 4px 12px; height: auto; border-radius: 15px; }
</style>
