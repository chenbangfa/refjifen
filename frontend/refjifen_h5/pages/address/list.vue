<template>
	<view class="container">
        <view class="addr-list">
            <view class="addr-item" v-for="item in list" :key="item.id" @click="select(item)">
                <view class="info">
                    <view class="row1">
                        <text class="tag" v-if="item.is_default">默认</text>
                        <text class="name">{{ item.name }}</text>
                        <text class="mobile">{{ item.mobile }}</text>
                    </view>
                    <view class="row2">{{ item.province }} {{ item.city }} {{ item.district }} {{ item.detail }}</view>
                </view>
                <view class="edit-btn" @click.stop="goEdit(item)">
                    <text class="iconfont icon-edit">编辑</text>
                </view>
            </view>
        </view>
        
        <view class="add-btn" @click="goEdit(null)">
            + 新建收货地址
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				list: [],
                isSelect: false
			}
		},
		onLoad(options) {
			this.isSelect = options.select == 1;
		},
        onShow() {
            this.loadList();
        },
		methods: {
			async loadList() {
                try {
                     const res = await uni.$api('address.php?action=list');
                     if(res.code == 200) {
                         this.list = res.data;
                     }
                } catch(e) {}
			},
            select(item) {
                if(this.isSelect) {
                    uni.setStorageSync('selected_address', item);
                    uni.navigateBack();
                }
            },
            goEdit(item) {
                let url = '/pages/address/edit';
                if(item) {
                    uni.setStorageSync('edit_address', item);
                } else {
                    uni.removeStorageSync('edit_address');
                }
                uni.navigateTo({ url });
            }
		}
	}
</script>

<style>
    .container { padding: 10px; padding-bottom: 70px; }
    
    .addr-item { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
    .info { flex: 1; }
    .row1 { display: flex; align-items: center; margin-bottom: 8px; }
    .name { font-size: 16px; font-weight: bold; margin-right: 10px; }
    .mobile { color: #666; font-size: 14px; }
    .tag { background: #07C160; color: #fff; font-size: 10px; padding: 1px 4px; border-radius: 4px; margin-right: 5px; }
    .row2 { color: #333; font-size: 14px; line-height: 1.4; }
    
    .edit-btn { padding-left: 15px; border-left: 1px solid #eee; margin-left: 10px; color: #999; font-size: 12px; width: 40px; text-align: center; }
    
    .add-btn { position: fixed; bottom: 20px; left: 5%; width: 90%; height: 45px; background: #07C160; color: #fff; text-align: center; line-height: 45px; border-radius: 25px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 10px rgba(7, 193, 96, 0.3); }
</style>
