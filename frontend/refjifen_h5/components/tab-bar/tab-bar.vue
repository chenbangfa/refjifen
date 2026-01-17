<template>
	<view class="tab-bar">
		<view class="tab-bar-item" v-for="(item, index) in list" :key="index" @click="switchTab(item)">
            <view class="icon-area">
                <svg viewBox="0 0 24 24" class="tab-icon" width="24" height="24">
                    <path :d="current === item.pagePath ? item.selectedIconPath : item.iconPath" :fill="current === item.pagePath ? '#07C160' : '#7A7E83'"></path>
                </svg>
            </view>
			<view class="tab-text" :class="{ 'active': current === item.pagePath }">{{ item.text }}</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			current: {
				type: String,
				default: ''
			}
		},
		data() {
			return {
                // Common Icons
                icons: {
                    home: "M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z",
                    home_active: "M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z", // Same shape, color changes
                    
                    member: "M12 2l-5.5 9h11L12 2zm0 3.8l2.6 4.2h-5.2L12 5.8zM5 13c-1.1 0-2 .9-2 2v4c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-4c0-1.1-.9-2-2-2H5zm0 2h14v4H5v-4z", // Crown-ish or Shop-ish. Let's use specific Crown
                    member_active: "M5 13c-1.1 0-2 .9-2 2v4c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-4c0-1.1-.9-2-2-2H5zm0 2h14v4H5v-4z M12 2L6.5 11h11L12 2z", // Filled

                    exchange: "M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15h-8v-2h8v2zm0-4h-8v-2h8v2zm0-4h-8V9h8v2zm-10 8H4v-2h6v2zm0-4H4v-2h6v2zm0-4H4V9h6v2z",
                    
                    order: "M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z",
                    
                    profile: "M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z",
                    
                    team: "M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z",
                    
                    message: "M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"
                },
                
                // Malls: Home, A(Member), B(Exchange), Order, Profile
                // Agent: Home(Agent), Team, Message, Profile(Agent)
			};
		},
		computed: {
            mallList() {
                return [
					{ pagePath: "pages/index/index", text: "首页", iconPath: this.icons.home, selectedIconPath: this.icons.home_active },
					{ pagePath: "pages/mall/category?zone=A", text: "会员专区", iconPath: "M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 11l-2.5-1.25L12 11zm0 2.5l-5-2.5-2 1L12 17l7-5-2-1-5 2.5z", selectedIconPath: "M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 11l-2.5-1.25L12 11zm0 2.5l-5-2.5-2 1L12 17l7-5-2-1-5 2.5z" }, // Diamond
					{ pagePath: "pages/mall/category?zone=B", text: "兑换专区", iconPath: this.icons.exchange, selectedIconPath: this.icons.exchange },
					{ pagePath: "pages/order/list", text: "订单", iconPath: this.icons.order, selectedIconPath: this.icons.order },
					{ pagePath: "pages/mall/profile", text: "我的", iconPath: this.icons.profile, selectedIconPath: this.icons.profile }
				];
            },
            agentList() {
                return [
					{ pagePath: "pages/agent/index", text: "首页", iconPath: this.icons.home, selectedIconPath: this.icons.home_active }, // Use Home for Agent Index too or Dashboard
					{ pagePath: "pages/team/tree", text: "团队", iconPath: this.icons.team, selectedIconPath: this.icons.team },
					{ pagePath: "pages/message/list", text: "消息", iconPath: this.icons.message, selectedIconPath: this.icons.message },
					{ pagePath: "pages/my/my", text: "我的", iconPath: this.icons.profile, selectedIconPath: this.icons.profile }
                ];
            },
			list() {
                const path = this.current;
                // Robust check for agent section
                if (path.includes('pages/agent') || path.includes('pages/team') || path.includes('pages/message') || path === 'pages/my/my') {
                     return this.agentList;
                }
				return this.mallList;
			}
		},
		methods: {
			switchTab(item) {
				const url = '/' + item.pagePath;
				uni.redirectTo({ url }); 
			}
		}
	}
</script>

<style>
	.tab-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		height: 50px;
		background: white;
		display: flex;
		padding-bottom: env(safe-area-inset-bottom);
        border-top: 1px solid #eee;
        z-index: 999;
	}

	.tab-bar-item {
		flex: 1;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		flex-direction: column;
	}
    
    .icon-area {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2px;
    }

	.tab-icon {
		width: 24px;
		height: 24px;
	}

	.tab-text {
		font-size: 10px;
        color: #7A7E83;
	}
    
    .tab-text.active {
        color: #07C160;
    }
</style>
