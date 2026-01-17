<template>
	<view class="container">
        <!-- 顶部搜索 -->
        <view class="search-bar">
            <view class="search-box">
                <text class="iconfont icon-search"></text>
                <input type="text" v-model="keyword" placeholder="搜索商品" @confirm="goSearch" />
            </view>
        </view>
        
        <!-- 轮播图 -->
        <swiper class="banner" circular autoplay interval="3000" indicator-dots>
            <swiper-item v-for="(item, index) in banners" :key="index">
                <image :src="item" mode="aspectFill" class="banner-img"></image>
            </swiper-item>
        </swiper>

        <!-- 导航菜单 -->
        <view class="nav-grid" v-if="navItems.length > 0">
            <view class="nav-item" v-for="(item, index) in navItems" :key="index" @click="handleNav(item)">
                <image :src="item.icon" class="nav-icon" mode="aspectFill"></image>
                <text class="nav-name">{{ item.name }}</text>
            </view>
        </view>
        
        <!-- 会员专区 (Zone A) -->
        <view class="zone-section">
            <view class="zone-header header-a" @click="goCategory('A')">
                <view style="flex:1">
                    <text class="zone-title">会员专区</text>
                    <text class="zone-sub">余额购买 · 赠送流量分</text>
                </view>
                <view class="zone-more">更多 <text class="iconfont icon-right"></text></view>
            </view>
            <view class="product-grid">
                <view class="product-item" v-for="item in zoneA" :key="item.id" @click="goDetail(item)">
                    <image :src="item.image.split(',')[0]" mode="aspectFill" class="p-img"></image>
                    <view class="p-info">
                        <view class="p-title">{{ item.title }}</view>
                        <view class="p-meta">
                            <view class="p-price">¥{{ parseFloat(item.price) }}<text class="p-unit">/{{ item.unit||'件' }}</text></view>
                            <view class="p-sales">已售{{ item.sales }}</view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        
        <!-- 兑换专区 (Zone B) -->
        <view class="zone-section">
            <view class="zone-header header-b" @click="goCategory('B')">
                <view style="flex:1">
                    <text class="zone-title">兑换专区</text>
                    <text class="zone-sub">购物券兑换 · 超值好物</text>
                </view>
                 <view class="zone-more">更多 <text class="iconfont icon-right"></text></view>
            </view>
            <view class="product-grid">
                <view class="product-item" v-for="item in zoneB" :key="item.id" @click="goDetail(item)">
                    <image :src="item.image.split(',')[0]" mode="aspectFill" class="p-img"></image>
                    <view class="p-info">
                        <view class="p-title">{{ item.title }}</view>
                        <view class="p-meta">
                            <view class="p-price" style="color:#ff9800;">{{ parseFloat(item.price) }}<text class="p-unit" style="color:#ffcc80">券/{{ item.unit||'件' }}</text></view>
                            <view class="p-sales">已售{{ item.sales }}</view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <tab-bar current="pages/index/index"></tab-bar>
	</view>
</template>

<script>
    import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
        components: { tabBar },
		data() {
			return {
				keyword: '',
                banners: [],
                list: [],
                navItems: []
			}
		},
        computed: {
            zoneA() { return this.list.filter(i => i.zone == 'A'); },
            zoneB() { return this.list.filter(i => i.zone == 'B'); }
        },
		onShow() {
			this.loadProducts();
            this.loadBanners();
            this.loadNavItems();
		},
		methods: {
            async loadBanners() {
                 try {
                     const res = await uni.$api('mall.php?action=banners');
                     if(res.code == 200 && res.data.length > 0) {
                         this.banners = res.data.map(item => {
                             if(item.image.startsWith('/')) {
                                 return 'https://ref.tajian.cc' + item.image;
                             }
                             return item.image;
                         });
                     } else {
                         if(this.banners.length == 0) {
                            this.banners = [
                                'https://via.placeholder.com/600x300/07C160/fff?text=Banner1',
                                'https://via.placeholder.com/600x300/1890ff/fff?text=Banner2'
                            ];
                         }
                     }
                 } catch(e) {}
            },
            async loadNavItems() {
                try {
                    const res = await uni.$api('mall.php?action=nav_items');
                    if(res.code == 200) {
                         this.navItems = res.data.map(item => {
                             if(item.icon.startsWith('/')) {
                                 item.icon = 'https://ref.tajian.cc' + item.icon;
                             }
                             return item;
                         });
                    }
                } catch(e) {}
            },
            handleNav(item) {
                if (item.type == 1) { // Link
                    if (!item.content) return;
                    if (item.content.startsWith('http')) {
                        // H5 Open External
                        window.location.href = item.content;
                    } else {
                        uni.navigateTo({
                            url: item.content,
                            fail: () => { uni.switchTab({ url: item.content }); } 
                        });
                    }
                } else { // Popup
                    uni.showModal({
                        title: '提示',
                        content: item.content || '板块内容筹建中',
                        showCancel: false,
                        confirmText: '我知道了'
                    });
                }
            },
			async loadProducts() {
                try {
                     const res = await uni.$api('mall.php?action=products');
                     if (res.code == 200) {
                         this.list = res.data;
                     }
                 } catch(e) {}
			},
            goSearch() {
                if(this.keyword.trim()) {
                    uni.navigateTo({ url: `/pages/mall/search?keyword=${encodeURIComponent(this.keyword)}` });
                }
            },
            goDetail(item) {
                uni.navigateTo({ url: `/pages/mall/product?id=${item.id}` });
            },
            goCategory(zone) {
                uni.navigateTo({ url: `/pages/mall/category?zone=${zone}` });
            }
		}
	}
</script>

<style>
    /* Override global container padding */
    .container { 
        padding: 10px !important; 
        padding-bottom: 70px !important; 
        background: #f5f5f5; 
        min-height: 100vh;
        box-sizing: border-box; /* Ensure padding includes border */ 
    }
    
   
    .search-bar { 
        /* Use negative margin to span full width if we strictly follow "padding 10px" for container but want sticky header to not look keyline? 
           User showed a screenshot where search bar was full width (gray bg on white). 
           Actually, the "gray" was the search input, "white" was the bar. 
           If the white bar has 30px gap, it looks bad. 
           If I set 10px padding, gap is smaller. 
        */
        position: sticky; top: 0; z-index: 99; background: #fff; 
        margin: -10px -10px 10px -10px; /* Pull to edges */
        padding: 10px 15px;
    }
    
    .search-box { background: #f0f0f0; border-radius: 20px; height: 35px; display: flex; align-items: center; padding: 0 15px; }
    .icon-search { color: #999; margin-right: 5px; }
    .search-box input { flex: 1; font-size: 14px; }
    
    .banner { width: 100%; height: 180px; background: #fff; border-radius: 8px; overflow: hidden; margin-bottom: 10px; } /* Rounded corners for banner to match layout? User didn't ask but "inconsistent width" implies it might need to match card curvature? */
    .banner-img { width: 100%; height: 100%; }
    
    .zone-section { background: #fff; border-radius: 12px; margin-bottom: 15px; padding: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); overflow: hidden; }
    
    .zone-header { display: flex; align-items: center; justify-content: space-between; padding: 10px; margin: -10px -10px 10px -10px; }
    
    /* Header variants */
    .header-a { background: linear-gradient(90deg, #E8F5EB 0%, #ffffff 100%); } 
    .header-b { background: linear-gradient(90deg, #fff7e6 0%, #ffffff 100%); }
    
    .zone-title { font-size: 16px; font-weight: bold; color: #333; margin-right: 8px; }
    .zone-sub { font-size: 12px; color: #999; }
    .zone-more { font-size: 12px; color: #666; display: flex; align-items: center; }
    .zone-more .iconfont { font-size: 12px; margin-left: 2px; }
    
    .product-grid { display: flex; flex-wrap: wrap; justify-content: space-between; }
    .product-item { width: 48.5%; background: #fff; border-radius: 8px; margin-bottom: 10px; overflow: hidden; border: 1px solid #f9f9f9; box-shadow: none; }
    .p-img { width: 100%; height: 150px; background: #f5f5f5; }
    .p-info { padding: 8px; }
    .p-title { font-size: 14px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 5px; font-weight: bold; }
    .p-meta { display: flex; justify-content: space-between; align-items: flex-end; }
    .p-price { font-size: 16px; color: #07C160; font-weight: bold; line-height: 1; }
    .p-sales { font-size: 10px; color: #bbb; }
    .p-unit { font-size: 12px; color: #ccc; font-weight: normal; margin-left: 1px; }
</style>
