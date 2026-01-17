<template>
	<view class="container">
        <!-- 余额卡片 -->
		<view class="balance-card">
            <view class="label">当前余额</view>
            <view class="amount">{{ fmt(user.balance) }}</view>
             <view class="btn-row">
                 <view class="btn" @click="goService">客服</view>
                 <view class="btn outline" @click="goWithdraw">提现</view>
             </view>
		</view>

        <!-- 功能入口 -->
        <view class="menu-list">
             <view class="menu-item" @click="showTransfer = true">
                <view class="menu-left">
                     <text class="iconfont icon-swap menu-icon" style="color:#1890ff;"></text>
                    <text>站内转账</text>
                </view>
                <view class="menu-right">
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>
            <view class="menu-item" @click="goTransactions">
                <view class="menu-left">
                     <text class="iconfont icon-file-text menu-icon" style="color:#faad14;"></text>
                    <text>资金明细</text>
                </view>
                <view class="menu-right">
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>
        </view>

        <!-- 转账弹窗 -->
        <view class="modal-mask" v-if="showTransfer" @click="showTransfer = false">
            <view class="modal-content" @click.stop>
                <view class="modal-title">站内转账</view>
                <input class="input" v-model="transferForm.target_id" placeholder="对方ID" type="number" />
                <input class="input" v-model="transferForm.target_name" placeholder="对方姓名" />
                <input class="input" v-model="transferForm.target_mobile" placeholder="对方手机号" type="number" maxlength="11" />
                <input class="input" v-model="transferForm.amount" placeholder="转账金额 (200的倍数)" type="number" />
                <input class="input" v-model="transferForm.pay_password" placeholder="支付密码" password type="number" maxlength="6"/>
                <view class="modal-btn" @click="doTransfer">确认转账</view>
            </view>
        </view>
        
        <!-- 提现弹窗 -->
        <view class="modal-mask" v-if="showWithdrawModal" @click="showWithdrawModal = false">
            <view class="modal-content" @click.stop>
                <view class="modal-title">申请提现</view>
                <view class="tabs">
                    <view :class="['tab', withdrawForm.method=='bank'?'active':'']" @click="withdrawForm.method='bank'">银行卡</view>
                    <view :class="['tab', withdrawForm.method=='alipay'?'active':'']" @click="withdrawForm.method='alipay'">支付宝</view>
                    <view :class="['tab', withdrawForm.method=='wechat'?'active':'']" @click="withdrawForm.method='wechat'">微信</view>
                </view>
                
                <input class="input" v-model="withdrawForm.amount" placeholder="提现金额 (100的倍数)" type="number" />
                
                <template v-if="withdrawForm.method == 'bank'">
                    <input class="input" v-model="withdrawForm.details.realname" placeholder="收款人姓名" />
                    <input class="input" v-model="withdrawForm.details.bank_name" placeholder="开户行名称" />
                    <input class="input" v-model="withdrawForm.details.account_number" placeholder="银行卡号" type="number"/>
                </template>
                
                <template v-if="withdrawForm.method == 'alipay' || withdrawForm.method == 'wechat'">
                    <input class="input" v-model="withdrawForm.details.realname" placeholder="收款人姓名" />
                    <input class="input" v-model="withdrawForm.details.alipay_account" v-if="withdrawForm.method == 'alipay'" placeholder="支付宝账号" />
                    <input class="input" v-model="withdrawForm.details.wechat_account" v-if="withdrawForm.method == 'wechat'" placeholder="微信号" />
                    <view class="upload-box" @click="chooseImage">
                        <image v-if="localImg" :src="localImg" class="preview"></image>
                        <view v-else class="upload-tip">
                             <text class="iconfont icon-plus"></text>
                             <text>上传收款码</text>
                        </view>
                    </view>
                </template>

                 <input class="input" v-model="withdrawForm.pay_password" placeholder="支付密码" password type="number" maxlength="6"/>
                <view class="modal-btn" @click="doWithdraw">确认提现</view>
            </view>
        </view>

        <!-- 充值弹窗复用 -->
        <view class="modal-mask" v-if="showRechargeModal" @click="showRechargeModal = false">
            <view class="modal-content" @click.stop>
                <view class="modal-title">在线充值</view>
                <view class="recharge-info">
                    <view>公司账户：2402018409200341260</view>
                    <view>开户银行：中国工商银行股份有限公司阳金桥支行</view>
                    <view>公司户名：贵州豪熠商贸有限公司</view>
                    <view style="margin-top:10px;color:#f00;">充值请备注手机号</view>
                </view>
                <view class="modal-btn" @click="showRechargeModal = false" style="margin-top:20px;">我知道了</view>
            </view>
        </view>

	</view>
</template>

<script>
	export default {
		data() {
			return {
				user: {},
                showTransfer: false,
                showWithdrawModal: false,
                showRechargeModal: false,
                transferForm: {
                    target_id: '',
                    target_name: '',
                    target_mobile: '',
                    amount: '',
                    pay_password: ''
                },
                withdrawForm: {
                    amount: '',
                    method: 'bank',
                    pay_password: '',
                    details: {
                        realname: '',
                        bank_name: '',
                        account_number: '',
                        alipay_account: '',
                        wechat_account: '',
                        image_url: '' 
                    }
                },
                localImg: ''
			}
		},
		onShow() {
			this.fetchInfo();
		},
		methods: {
            fmt(val) { return parseFloat(val || 0).toFixed(2); },
			async fetchInfo() {
                try {
                    const res = await uni.$api('account.php?action=info');
                    if(res.code == 200) this.user = res.data;
                } catch(e) {}
			},
            openTransfer() {
                if(!this.user.has_pay_password) {
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
                this.showTransfer = true;
            },
            goTransactions() {
                uni.navigateTo({ url: '/pages/my/transactions' });
            },
            goWithdraw() {
                if(!this.user.has_pay_password) {
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
                this.showWithdrawModal = true;
            },
            goService() {
                uni.navigateTo({ url: '/pages/service/chat' });
            },
            async doTransfer() {
                if(!this.transferForm.target_id || !this.transferForm.target_name || !this.transferForm.target_mobile || !this.transferForm.amount || !this.transferForm.pay_password) {
                    uni.showToast({title:'请填写完整信息', icon:'none'}); return;
                }
                uni.showLoading({title:'提交中'});
                try {
                    const res = await uni.$api('assets.php?action=transfer', 'POST', this.transferForm);
                    uni.hideLoading();
                     if(res.code == 200) {
                         uni.showToast({title:'转账成功'});
                         this.showTransfer = false;
                         this.fetchInfo();
                     } else {
                         uni.showToast({title:res.message, icon:'none'});
                     }
                } catch(e) { uni.hideLoading(); }
            },
            chooseImage() {
                uni.chooseImage({
                    count: 1,
                    success: (res) => {
                        const tempFilePath = res.tempFilePaths[0];
                        this.localImg = tempFilePath;
                        uni.showLoading({title: '上传中...'});
                        
                        uni.uploadFile({
                            url: 'https://ref.tajian.cc/backend/api/upload.php', 
                            filePath: tempFilePath,
                            name: 'file',
                            header: {
                                'Authorization': 'Bearer ' + uni.getStorageSync('token')
                            },
                            success: (uploadFileRes) => {
                                uni.hideLoading();
                                try {
                                    const data = JSON.parse(uploadFileRes.data);
                                    if(data.code == 200) {
                                        this.withdrawForm.details.image_url = data.url;
                                        uni.showToast({title: '收款码上传成功'});
                                    } else {
                                        uni.showToast({title: data.message || '上传失败', icon: 'none'});
                                    }
                                } catch(e) {
                                    uni.showToast({title: '上传响应解析失败', icon: 'none'});
                                }
                            },
                            fail: () => {
                                uni.hideLoading();
                                uni.showToast({title: '上传请求失败', icon: 'none'});
                            }
                        });
                    }
                });
            },
            async doWithdraw() {
                if(!this.withdrawForm.amount || !this.withdrawForm.pay_password) {
                     uni.showToast({title:'请填写完整', icon:'none'}); return;
                }
                 if(this.withdrawForm.method == 'bank') {
                     if(!this.withdrawForm.details.realname || !this.withdrawForm.details.bank_name || !this.withdrawForm.details.account_number) {
                         uni.showToast({title:'请填写银行卡信息', icon:'none'}); return;
                     }
                 } else {
                     if(!this.withdrawForm.details.realname) {
                         uni.showToast({title:'请填写收款人姓名', icon:'none'}); return;
                     }
                     if(this.withdrawForm.method == 'alipay' && !this.withdrawForm.details.alipay_account) {
                         uni.showToast({title:'请填写支付宝账号', icon:'none'}); return;
                     }
                     if(this.withdrawForm.method == 'wechat' && !this.withdrawForm.details.wechat_account) {
                         uni.showToast({title:'请填写微信号', icon:'none'}); return;
                     }
                     if(!this.withdrawForm.details.image_url) {
                         uni.showToast({title:'请上传收款码', icon:'none'}); return;
                     }
                 }
                 
                 uni.showLoading({title:'申请中'});
                 try {
                     const res = await uni.$api('assets.php?action=withdraw', 'POST', this.withdrawForm);
                     uni.hideLoading();
                     if(res.code == 200) {
                         uni.showToast({title:'申请成功'});
                         this.showWithdrawModal = false;
                         this.fetchInfo();
                     } else {
                         uni.showToast({title:res.message, icon:'none'});
                     }
                 } catch(e) { uni.hideLoading(); }
            }
		}
	}
</script>

<style>
    .container { padding: 15px; }
    .balance-card { background: #07C160; color: #fff; padding: 30px 20px; border-radius: 12px; text-align: center; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(7, 193, 96, 0.3); }
    .balance-card .label { font-size: 14px; opacity: 0.9; margin-bottom: 10px; }
    .balance-card .amount { font-size: 36px; font-weight: bold; margin-bottom: 20px; }
    .btn-row { display: flex; justify-content: center; gap: 15px; }
    .btn { background: #fff; color: #07C160; padding: 8px 30px; border-radius: 20px; font-size: 14px; font-weight: bold; }
    .btn.outline { background: transparent; border: 1px solid #fff; color: #fff; }
    
    .menu-list { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .menu-item { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; font-size: 16px; color: #333; }
    .menu-item:last-child { border-bottom: none; }
    .menu-item:active { background: #f9f9f9; }
    
    .menu-left { display: flex; align-items: center; }
    .menu-right { display: flex; align-items: center; color: #999; }
    .menu-icon { font-size: 20px; margin-right: 10px; }
    
    .modal-mask { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: flex; justify-content: center; align-items: center; }
    .modal-content { background: #fff; width: 75%; padding: 25px; border-radius: 16px; text-align: center; }
    .modal-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #333; }
    .input { background: #f5f5f5; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: left; font-size: 14px; height: 40px; }
    .modal-btn { background: #07C160; color: #fff; padding: 12px; border-radius: 8px; font-size: 16px; margin-top: 10px; }
    
    .tabs { display: flex; margin-bottom: 15px; border-bottom: 1px solid #eee; }
    .tab { flex: 1; padding: 10px 0; font-size: 14px; color: #666; }
    .tab.active { color: #07C160; border-bottom: 2px solid #07C160; font-weight: bold; }
    
    .upload-box { width: 100%; height: 150px; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 1px dashed #ccc; }
    .upload-tip { color: #999; display: flex; flex-direction: column; align-items: center; }
    .preview { width: 100%; height: 100%; border-radius: 8px; }
    
    .recharge-info { text-align: left; margin: 10px 0; color: #555; font-size: 14px; line-height: 1.8; }
</style>
