<template>
	<view class="container">
        <!-- 顶部搜索 -->
        <view class="search-bar">
            <view class="search-box">
                <text class="iconfont icon-search"></text>
                <input type="text" v-model="keyword" placeholder="搜索商品" focus confirm-type="search" @confirm="doSearch" />
            </view>
        </view>
        
        <!-- 搜索结果 -->
        <view class="result-area" v-if="list.length > 0">
            <view class="product-grid">
                <view class="product-item" v-for="item in list" :key="item.id" @click="goDetail(item)">
                    <image :src="item.image ? (item.image.startsWith('http') ? item.image : (apiBase + item.image.split(',')[0])) : ''" mode="aspectFill" class="p-img"></image>
                    <view class="p-info">
                        <view class="p-title">{{ item.title }}</view>
                        <view class="p-meta">
                            <view class="p-price" :style="{color: item.zone == 'B' ? '#ff9800' : '#07C160'}">
                                {{ parseFloat(item.price) }}
                                <text class="p-unit" v-if="item.zone == 'B'" style="color:#ffcc80">券/{{ item.unit||'件' }}</text>
                                <text class="p-unit" v-else>/{{ item.unit||'件' }}</text>
                            </view>
                            <view class="p-sales">已售{{ item.sales }}</view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        
        <!-- 空状态 -->
        <view class="empty-state" v-else-if="searched">
            <text class="iconfont icon-empty" style="font-size: 48px; color: #ddd;"></text>
            <text style="color: #999; margin-top: 10px;">未找到相关商品</text>
        </view>
        
	</view>
</template>

<script>
	export default {
		data() {
			return {
				keyword: '',
                list: [],
                searched: false,
                apiBase: 'https://ref.tajian.cc' // Simplify image path handling if needed, or use full URL from backend usually
			}
		},
		onLoad(options) {
            if(options.keyword) {
                this.keyword = options.keyword;
                this.doSearch();
            }
		},
		methods: {
			async doSearch() {
                if(!this.keyword.trim()) return;
                
                uni.showLoading({ title: '搜索中...' });
                this.searched = false;
                
                try {
                     const res = await uni.$api(`mall.php?action=products&search=${encodeURIComponent(this.keyword)}`);
                     uni.hideLoading();
                     if (res.code == 200) {
                         this.list = res.data.map(item => {
                             // Handle image path if it's relative
                             if(item.image && item.image.startsWith('/')) {
                                 item.image = this.apiBase + item.image;
                             }
                             return item;
                         });
                         this.searched = true;
                     }
                 } catch(e) {
                     uni.hideLoading();
                     uni.showToast({ title: '搜索失败', icon: 'none' });
                 }
			},
            goDetail(item) {
                uni.navigateTo({ url: `/pages/mall/product?id=${item.id}` });
            }
		}
	}
</script>

<style>
    .container { 
        padding: 10px; 
        background: #f5f5f5; 
        min-height: 100vh;
        box-sizing: border-box; 
    }
    
    .search-bar { 
        position: sticky; top: 0; z-index: 99; background: #fff; 
        margin: -10px -10px 10px -10px; 
        padding: 10px 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .search-box { background: #f0f0f0; border-radius: 20px; height: 35px; display: flex; align-items: center; padding: 0 15px; }
    .icon-search { color: #999; margin-right: 5px; }
    .search-box input { flex: 1; font-size: 14px; }
    
    .product-grid { display: flex; flex-wrap: wrap; justify-content: space-between; }
    .product-item { width: 48.5%; background: #fff; border-radius: 8px; margin-bottom: 10px; overflow: hidden; border: 1px solid #f9f9f9; }
    .p-img { width: 100%; height: 150px; background: #f5f5f5; }
    .p-info { padding: 8px; }
    .p-title { font-size: 14px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 5px; font-weight: bold; }
    .p-meta { display: flex; justify-content: space-between; align-items: flex-end; }
    .p-price { font-size: 16px; font-weight: bold; line-height: 1; }
    .p-sales { font-size: 10px; color: #bbb; }
    .p-unit { font-size: 12px; color: #ccc; font-weight: normal; margin-left: 1px; }
    
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding-top: 100px; }
</style>
