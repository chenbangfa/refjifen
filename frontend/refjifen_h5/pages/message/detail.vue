<template>
	<view class="container">
		<view class="content-box">
			<view class="title">{{ article.title }}</view>
			<view class="date">{{ article.created_at }}</view>
			<view class="line"></view>
			<view class="rich-content">
				<rich-text :nodes="formatRichText(article.content)"></rich-text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
				article: {
					title: '',
					created_at: '',
					content: ''
				}
			};
		},
		onLoad(options) {
			if (options.id) {
				this.id = options.id;
				this.fetchDetail();
			}
		},
		methods: {
			async fetchDetail() {
                try {
                    const res = await uni.$api('articles.php?action=detail&id=' + this.id);
                    if (res.code == 200) {
                        this.article = res.data;
                    }
                } catch(e) {
                    console.error(e);
                }
			},
			formatRichText(html) {
				if (!html) return '';
				// Fix image width for rich-text component
				return html.replace(/<img[^>]*>/gi, function(match, capture) {
					match = match.replace(/style="[^"]+"/gi, '').replace(/style='[^']+'/gi, '');
					match = match.replace(/width="[^"]+"/gi, '').replace(/width='[^']+'/gi, '');
					match = match.replace(/height="[^"]+"/gi, '').replace(/height='[^']+'/gi, '');
					return match.replace(/<img/gi, '<img style="max-width:100%;height:auto;display:block;"');
				});
			}
		}
	}
</script>

<style>
	.container {
		background-color: #fff;
		min-height: 100vh;
		padding: 20px !important;
	}

	.content-box {
		
	}

	.title {
		font-size: 20px;
		font-weight: bold;
		color: #333;
		margin-bottom: 10px;
	}

	.date {
		font-size: 14px;
		color: #999;
		margin-bottom: 15px;
	}

	.line {
		height: 1px;
		background: #eee;
		margin-bottom: 20px;
	}

	.rich-content {
		font-size: 16px;
		line-height: 1.6;
		color: #333;
	}
</style>
