<template>
	<view class="container">
        <!-- 状态标签栏 (Full Width via negative margin) -->
        <view class="tabs">
            <view class="tab-item" :class="{active: status===''}" @click="switchStatus('')">全部</view>
            <view class="tab-item" :class="{active: status==='0'}" @click="switchStatus('0')">待付款</view>
            <view class="tab-item" :class="{active: status==='1'}" @click="switchStatus('1')">待发货</view>
            <view class="tab-item" :class="{active: status==='2'}" @click="switchStatus('2')">待收货</view>
            <view class="tab-item" :class="{active: status==='3'}" @click="switchStatus('3')">已完成</view>
        </view>
        
        <!-- 订单列表 -->
        <scroll-view scroll-y class="order-list" @scrolltolower="loadMore">
            <view class="order-card" v-for="item in list" :key="item.id">
                <view class="card-header">
                    <text class="order-no">订单号: {{ item.order_sn }}</text>
                    <text class="order-status">{{ getStatusText(item.status) }}</text>
                </view>
                
                <view class="goods-list" @click="goDetail(item.id)">
                     <view class="goods-item" v-for="(goods, idx) in item.goods_list" :key="idx">
                         <image :src="goods.image.split(',')[0]" mode="aspectFill" class="g-img"></image>
                         <view class="g-info">
                             <view class="g-title">{{ goods.title }}</view>
                             <view class="g-spec">{{ goods.unit || '默认规格' }}</view>
                             <view class="g-meta">
                                 <text class="g-price" v-if="item.pay_type=='balance'">¥{{ goods.price }}</text>
                                 <text class="g-price" v-else>{{ goods.price }}券</text>
                                 <text class="g-num">x{{ goods.num }}</text>
                             </view>
                         </view>
                     </view>
                </view>
                
                <view class="card-footer">
                    <view class="total-info">
                        共{{ item.total_num }}件商品 合计: 
                        <text class="total-price" v-if="item.pay_type=='balance'">¥{{ item.pay_price }}</text>
                        <text class="total-price" v-else>{{ item.pay_price }}券</text>
                    </view>
                    <view class="actions">
                        <view class="btn outline" v-if="item.status==0" @click="cancelOrder(item)">取消订单</view>
                        <view class="btn primary" v-if="item.status==0" @click="payOrder(item)">立即支付</view>
                        <view class="btn primary" v-if="item.status==2" @click="confirmReceipt(item)">确认收货</view>
                        <view class="btn primary" v-if="[1,2].includes(parseInt(item.status))" @click.stop="applyRefund(item)">申请售后</view>
                        <view class="btn outline" v-if="item.status==5" @click.stop="cancelRefund(item)">取消售后</view>
                        <view class="btn outline" v-if="['3','4'].includes(item.status)" @click="deleteOrder(item)">删除订单</view>
                    </view>
                </view>
            </view>
            
            <view v-if="list.length === 0 && !loading" class="empty-state">
                <image src="/static/empty_order.png" style="width:120px;height:120px;margin-bottom:10px;"></image>
                <text>暂无相关订单</text>
            </view>
             <view style="height: 60px;"></view>
        </scroll-view>

        <tab-bar current="pages/order/list"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
        components: { tabBar },
		data() {
			return {
				status: '', // ''=All, 0=Pending, 1=Paid/Ship, 2=Shipped, 3=Done
                list: [],
                page: 1,
                loading: false,
                hasMore: true
			}
		},
		onLoad(options) {
            if(typeof options.status !== 'undefined') {
                this.status = options.status;
            }
        },
		onShow() {
            this.page = 1;
            this.list = [];
            this.hasMore = true;
			this.loadData();
		},
		methods: {
            switchStatus(st) {
                if(this.status === st) return;
                this.status = st;
                this.page = 1;
                this.list = [];
                this.hasMore = true;
                this.loadData();
            },
            async loadData() {
                if(this.loading || !this.hasMore) return;
                this.loading = true;
                
                try {
                    const res = await uni.$api(`mall.php?action=my_orders&status=${this.status}&page=${this.page}`);
                    if(res.code == 200) {
                        if(this.page == 1) this.list = res.data;
                        else this.list = this.list.concat(res.data);
                        
                        // Simple pagination check found < limit?
                        if(res.data.length < 10) this.hasMore = false;
                        else this.page++;
                    }
                } catch(e) {
                } finally {
                    this.loading = false;
                }
            },
            getStatusText(st) {
                const map = { '0': '待付款', '1': '待发货', '2': '待收货', '3': '已完成', '4': '已取消', '5': '退款中' };
                return map[st] || '未知状态';
            },
            goDetail(id) {
                uni.navigateTo({ url: `/pages/order/detail?id=${id}` });
            },
            payOrder(item) {
                uni.navigateTo({ url: `/pages/mall/pay?order_id=${item.id}` });
            },
            cancelOrder(item) {
                 uni.showModal({
                     title:'提示', 
                     content:'确定要取消订单吗？',
                     success: (res) => {
                         if(res.confirm) {
                             // Call cancel API
                         }
                     }
                 });
            },
            confirmReceipt(item) {
                 uni.showModal({
                     title:'提示', 
                     content:'确认已收到货？',
                     success: async (res) => {
                         if(res.confirm) {
                            uni.showLoading({ title: '处理中' });
                            try {
                                const apiRes = await uni.$api('mall.php?action=confirm_receipt', 'POST', { id: item.id });
                                uni.hideLoading();
                                if (apiRes.code == 200) {
                                    uni.showToast({ title: '确认成功' });
                                    // Refresh List
                                    this.page = 1;
                                    this.list = [];
                                    this.hasMore = true;
                                    this.loadData();
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
            },
            applyRefund(item) {
                uni.navigateTo({ url: `/pages/order/refund?order_sn=${item.order_sn}` });
            },
            cancelRefund(item) {
                 uni.showModal({
                     title:'提示', 
                     content:'确定撤销售后申请吗？',
                     success: async (res) => {
                         if(res.confirm) {
                            uni.showLoading({ title: '处理中' });
                            try {
                                const apiRes = await uni.$api('mall.php?action=cancel_refund', 'POST', { order_sn: item.order_sn });
                                uni.hideLoading();
                                if (apiRes.code == 200) {
                                    uni.showToast({ title: '已撤销' });
                                    // Reset list
                                    this.page = 1;
                                    this.list = [];
                                    this.hasMore = true;
                                    this.loadData();
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
            },
            loadMore() {
                this.loadData();
            }
		}
	}
</script>

<style>
    /* Match Index Page Layout */
    .container { 
        padding: 10px; 
        padding-bottom: 60px;
        background: #f5f5f5; 
        height: 100vh;
        overflow: hidden;
        box-sizing: border-box;
        display: flex; 
        flex-direction: column; 
    }
    
    /* Tabs - Sticky and Full Width using negative margin to offset container padding */
    .tabs { 
        background: #fff; 
        display: flex; 
        height: 44px; 
        align-items: center; 
        position: sticky; 
        top: 0; 
        z-index: 99; 
        box-shadow: 0 1px 5px rgba(0,0,0,0.05); 
        margin: -10px -10px 10px -10px; /* Negative margin to stretch full width */
        padding: 0 10px; /* Optional: Inner padding if needed, or keeping tabs flush */
    }
    .tab-item { flex: 1; text-align: center; font-size: 14px; color: #666; height: 44px; line-height: 44px; position: relative; }
    .tab-item.active { color: #07C160; font-weight: bold; }
    .tab-item.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 20px; height: 3px; background: #07C160; border-radius: 2px; }
    
    .order-list { flex: 1; height: 0; } /* Flex height for scroll */
    
    .order-card { background: #fff; border-radius: 12px; margin-bottom: 12px; padding: 15px; overflow: hidden; }
    
    .card-header { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 12px; border-bottom: 1px solid #f9f9f9; padding-bottom: 8px; }
    .order-no { color: #999; }
    .order-status { color: #07C160; font-weight: bold; }
    
    .goods-item { display: flex; margin-bottom: 10px; }
    .g-img { width: 70px; height: 70px; border-radius: 6px; background: #eee; margin-right: 10px; }
    .g-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .g-title { font-size: 14px; color: #333; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .g-spec { font-size: 12px; color: #999; background: #f9f9f9; align-self: flex-start; padding: 2px 6px; border-radius: 4px; margin-top: 4px; }
    .g-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 5px; }
    .g-price { font-size: 15px; color: #333; font-weight: 500; }
    .g-num { font-size: 12px; color: #999; }
    
    .card-footer { border-top: 1px solid #f9f9f9; padding-top: 10px; text-align: right; }
    .total-info { font-size: 12px; color: #666; margin-bottom: 10px; }
    .total-price { font-size: 16px; color: #333; font-weight: bold; margin-left: 5px; }
    
    .actions { display: flex; justify-content: flex-end; }
    .btn { padding: 6px 14px; border-radius: 20px; font-size: 12px; margin-left: 10px; }
    .btn.outline { border: 1px solid #ddd; color: #666; }
    .btn.primary { background: #07C160; color: #fff; border: 1px solid #07C160; }
    
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding-top: 100px; color: #999; font-size: 14px; }
</style>
