<template>
	<view class="agent-container">
		<!-- Hero Section -->
		<view class="hero">
			<view class="logo-box">
				<text class="logo-text">新国创</text>
			</view>
			<view class="hero-title">新国创 · 消费新纪元</view>
			<view class="hero-sub">共创 · 共享 · 共赢</view>
		</view>

		<!-- Intro Section -->
		<view class="intro-card">
			<view class="section-title">平台简介</view>
			<view class="intro-text">
				本公司以颠覆的模式创新为核心驱动力，聚焦汽车消费全产业链，打造"低门槛购车"全新消费场景。我们深度整合行业资源，精准链接政策红利，直击传统汽车市场价格不透明、消费门槛高、服务链条割裂等痛点，构建汽车销售、后市场服务、金融保险于一体的汽车新生态。
			</view>
			<view class="intro-text">
				公司秉持多方共赢的发展理念，向上赋能车企拓宽销路，向下让利消费者实现轻松购车，横向联动后市场服务商、金融机构等各行业合作伙伴，打通汽车消费"售前-售中-售后"全链路。我们致力于打破行业固有壁垒，重塑汽车消费价值体系，在存量竞争的市场格局中开辟全新赛道，引领汽车行业迈入生态化、普惠化发展新阶段。
			</view>
		</view>

		<!-- Action Area -->
		<view class="action-area">
			<button class="action-btn" @click="switchMode">前往商城购物</button>
		</view>

		<tab-bar current="pages/agent/index"></tab-bar>
	</view>
</template>

<script>
	import tabBar from '@/components/tab-bar/tab-bar.vue';
	export default {
		components: { tabBar },
		data() {
			return {};
		},
		methods: {
			switchMode() {
				uni.reLaunch({
					url: '/pages/index/index'
				});
			}
		}
	}
</script>

<style>
	.agent-container {
		min-height: 100vh;
		background-color: #f5f7fa;
		padding-bottom: 80px;
	}

	/* Hero Section */
	.hero {
		background: linear-gradient(135deg, #07C160 0%, #05a050 100%);
		padding: 60px 20px 80px;
		text-align: center;
		color: #fff;
		border-bottom-left-radius: 30px;
		border-bottom-right-radius: 30px;
	}

	.logo-box {
		width: 80px;
		height: 80px;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 20px;
		margin: 0 auto 20px;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 2px solid rgba(255, 255, 255, 0.3);
	}

	.logo-text {
		font-size: 24px;
		font-weight: bold;
		color: #fff;
	}

	.hero-title {
		font-size: 24px;
		font-weight: bold;
		margin-bottom: 10px;
		letter-spacing: 1px;
	}

	.hero-sub {
		font-size: 14px;
		opacity: 0.9;
		letter-spacing: 2px;
	}

	/* Intro Card */
	.intro-card {
		background: #fff;
		margin: -40px 20px 20px;
		padding: 30px 20px;
		border-radius: 12px;
		box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
	}

	.section-title {
		font-size: 18px;
		font-weight: bold;
		color: #333;
		margin-bottom: 20px;
		text-align: center;
		position: relative;
	}

	.section-title::after {
		content: '';
		display: block;
		width: 40px;
		height: 3px;
		background: #07C160;
		margin: 8px auto 0;
		border-radius: 2px;
	}

	.intro-text {
		font-size: 15px;
		color: #666;
		line-height: 1.8;
		margin-bottom: 15px;
		text-align: justify;
	}
    
    /* Action Area */
    .action-area {
        padding: 20px 40px;
    }
    
    .action-btn {
        background: #fff;
        color: #07C160;
        border: 1px solid #07C160;
        border-radius: 25px;
        font-size: 16px;
    }
    .action-btn:active {
        background: #f0fff5;
    }
</style>
