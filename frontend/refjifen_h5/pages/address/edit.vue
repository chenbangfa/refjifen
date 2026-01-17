<template>
	<view class="container">
        <view class="form-group">
            <view class="form-item">
                <text class="label">收货人</text>
                <input class="input" v-model="form.name" placeholder="请填写收货人姓名" />
            </view>
            <view class="form-item">
                <text class="label">手机号码</text>
                <input class="input" v-model="form.mobile" type="number" placeholder="请填写收货人手机号" />
            </view>
            <view class="form-item">
                <text class="label">省市区</text>
                <input class="input" v-model="regionStr" placeholder="省 市 区 (空格分隔)" />
            </view>
            <view class="form-item">
                <text class="label">详细地址</text>
                <input class="input" v-model="form.detail" placeholder="街道门牌信息" />
            </view>
            <view class="form-item switch-item">
                <text class="label">设为默认</text>
                <switch :checked="form.is_default" color="#07C160" @change="e => form.is_default = e.detail.value" />
            </view>
        </view>
        
        <view class="btn save" @click="save">保存</view>
        <view class="btn del" v-if="form.id" @click="del">删除</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				form: { id: 0, name: '', mobile: '', province: '', city: '', district: '', detail: '', is_default: false },
                regionStr: ''
			}
		},
		onLoad() {
			const item = uni.getStorageSync('edit_address');
            if(item) {
                this.form = { ...item, is_default: item.is_default == 1 };
                this.regionStr = `${item.province} ${item.city} ${item.district}`.trim();
            }
		},
		methods: {
			async save() {
                if(!this.form.name || !this.form.mobile || !this.form.detail) return uni.showToast({ title: '请完善信息', icon: 'none' });
                
                // Parse region
                const parts = this.regionStr.split(' ');
                this.form.province = parts[0] || '';
                this.form.city = parts[1] || '';
                this.form.district = parts[2] || '';
                
                try {
                    const res = await uni.$api('address.php?action=save', 'POST', this.form);
                    if(res.code == 200) {
                        uni.showToast({ title: '已保存' });
                        setTimeout(() => uni.navigateBack(), 1000);
                    } else {
                         uni.showToast({ title: res.message, icon: 'none' });
                    }
                } catch(e) {}
			},
            async del() {
                try {
                     const res = await uni.$api(`address.php?action=delete&id=${this.form.id}`);
                     if(res.code == 200) {
                         uni.showToast({ title: '已删除' });
                         setTimeout(() => uni.navigateBack(), 1000);
                     }
                } catch(e) {}
            }
		}
	}
</script>

<style>
    .container { padding: 10px; }
    .form-group { background: #fff; border-radius: 8px; padding: 0 15px; margin-bottom: 20px; }
    .form-item { display: flex; align-items: center; border-bottom: 1px solid #f9f9f9; height: 50px; }
    .form-item:last-child { border-bottom: none; }
    .label { width: 80px; font-size: 15px; color: #333; }
    .input { flex: 1; font-size: 15px; }
    
    .switch-item { justify-content: space-between; }
    
    .btn { height: 45px; line-height: 45px; text-align: center; border-radius: 25px; font-size: 16px; margin-bottom: 15px; }
    .btn.save { background: #07C160; color: #fff; }
    .btn.del { background: #fff; color: #ff4d4f; border: 1px solid #eee; }
</style>
