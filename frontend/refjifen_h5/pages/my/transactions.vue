<template>
	<view class="container">
        <!-- Tabs -->
        <view class="tabs">
            <view class="tab-item" :class="{active: curType==''}" @click="switchTab('')">全部</view>
            <view class="tab-item" :class="{active: curType=='balance'}" @click="switchTab('balance')">余额</view>
            <view class="tab-item" :class="{active: curType=='traffic_points'}" @click="switchTab('traffic_points')">流量分</view>
            <view class="tab-item" :class="{active: curType=='vouchers'}" @click="switchTab('vouchers')">购物券</view>
        </view>

        <view v-if="logs.length == 0" class="empty">暂无资金明细</view>
        <view class="log-list">
            <view class="log-item" v-for="(item, index) in logs" :key="index">
                <view class="log-left">
                     <view class="log-type">{{ getTypeLabel(item.type, item.asset_type) }}</view>
                     <view class="log-time">{{ item.created_at }}</view>
                </view>
                <view class="log-right">
                    <view :class="['log-amount', getAmountClass(item.type)]">
                        {{ (getAmountClass(item.type) == 'plus' ? '+' : '-') + parseFloat(item.amount).toFixed(2) }}
                    </view>
                    <view class="log-memo">{{ item.memo }}</view>
                </view>
            </view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				logs: [],
                curType: ''
			}
		},
		onShow() {
			this.fetchLogs();
		},
		methods: {
            switchTab(type) {
                this.curType = type;
                this.fetchLogs();
            },
			async fetchLogs() {
                uni.showLoading();
                try {
                    let url = 'assets.php?action=transactions';
                    if(this.curType) url += '&asset_type='+this.curType;
                    
                    const res = await uni.$api(url);
                    uni.hideLoading();
                    if(res.code == 200) {
                        this.logs = res.data;
                    }
                } catch(e) { uni.hideLoading(); }
			},
            getTypeLabel(type, assetType) {
                const map = {
                    'recharge': '充值',
                    'release': '签到/释放',
                    'buy': '消费',
                    'transfer_in': '转入',
                    'transfer_out': '转出',
                    'withdraw': '提现',
                    'admin_recharge': '管理员充值',
                    'admin_deduct': '管理员扣除'
                };
                let label = map[type] || type;
                if(assetType) {
                    const amap = {'balance':'(余额)', 'traffic_points':'(流量分)', 'vouchers':'(购物券)'};
                    if(amap[assetType]) label += amap[assetType];
                }
                return label;
            },
            getAmountClass(type) {
                return (type == 'recharge' || type == 'release' || type == 'transfer_in' || type == 'admin_recharge') ? 'plus' : 'minus';
            }
		}
	}
</script>

<style>
    .container { padding: 10px; background: #f5f5f5; min-height: 100vh; }
    
    .tabs { display: flex; background: #fff; margin-bottom: 10px; border-radius: 8px; overflow: hidden; }
    .tab-item { flex: 1; text-align: center; height: 44px; line-height: 44px; font-size: 14px; color: #666; position: relative; }
    .tab-item.active { color: #07C160; font-weight: bold; }
    .tab-item.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 20px; height: 3px; background: #07C160; border-radius: 2px; }
    
    .empty { padding: 50px; text-align: center; color: #999; }
    .log-list { background: #fff; border-radius: 12px; overflow: hidden; }
    .log-item { padding: 15px; border-bottom: 1px solid #f9f9f9; display: flex; justify-content: space-between; align-items: center; }
    .log-left { display: flex; flex-direction: column; }
    .log-type { font-size: 16px; color: #333; margin-bottom: 5px; }
    .log-time { font-size: 12px; color: #999; }
    .log-right { text-align: right; }
    .log-amount { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
    .log-amount.plus { color: #faad14; }
    .log-amount.minus { color: #333; }
    .log-memo { font-size: 12px; color: #999; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
