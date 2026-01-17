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
				新国创是一个创新的社交新零售平台，致力于将传统电商与现代激励机制完美融合。我们精选全球优质好物，通过独特的积分赋能体系，让每一次消费都产生价值。
			</view>
			<view class="intro-text">
				在这里，消费者不仅是购买者，更是平台的获益者。通过分享与体验，您将开启全新的财富增值之旅。加入新国创，拥抱数字经济新未来！
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
