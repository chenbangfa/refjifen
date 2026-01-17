<template>
	<view class="chat-container">
        <!-- Message List -->
		<scroll-view class="msg-list" scroll-y="true" :scroll-top="scrollTop" :scroll-with-animation="true" style="flex:1; height: 0;">
            <view class="msg-item" v-for="(item, index) in messages" :key="index" :class="item.sender">
                <!-- Avatar (always first in DOM, row-reverse handles positioning for user) -->
                <view class="avatar" :class="item.sender">
                    <text class="avatar-text">{{ item.sender == 'admin' ? '客服' : '我' }}</text>
                </view>
                
                <!-- Text Message -->
                <view class="content-bubble" v-if="item.type == 'text'">
                    {{ item.content }}
                </view>
                <!-- Image Message -->
                <view class="content-image" v-else-if="item.type == 'image'" @click="previewImage(item.content)">
                    <image :src="item.content" mode="widthFix"></image>
                </view>
            </view>
            <view style="height: 20px;" id="bottom"></view> <!-- Spacer -->
		</scroll-view>
        
        <!-- Input Area -->
        <view class="input-area">
            <view class="icon-btn" @click="chooseImage">
                 <svg viewBox="0 0 24 24" width="24" height="24">
                     <path fill="#666" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"></path>
                 </svg>
            </view>
            <input class="input-box" v-model="inputText" placeholder="输入消息..." confirm-type="send" @confirm="sendText" :adjust-position="true" />
            <view class="send-btn" @click="sendText">发送</view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				messages: [],
                inputText: '',
                scrollTop: 0,
                userAvatar: '',
                timer: null
			}
		},
		onLoad() {
            this.fetchHistory();
            this.startPolling();
		},
        onUnload() {
            this.stopPolling();
        },
		methods: {
			async fetchHistory() {
                try {
                    const res = await uni.$api('service.php?action=history');
                    if(res.code == 200) {
                        const newLen = res.data.length;
                        const oldLen = this.messages.length;
                        this.messages = res.data;
                        if(newLen > oldLen) {
                            this.scrollToBottom();
                        }
                    }
                } catch(e) {}
			},
            startPolling() {
                this.timer = setInterval(() => {
                    this.fetchHistory();
                }, 3000);
            },
            stopPolling() {
                if(this.timer) clearInterval(this.timer);
            },
            scrollToBottom() {
                this.scrollTop = this.scrollTop + 1;
                this.$nextTick(() => {
                    this.scrollTop = 9999999;
                });
            },
            async sendText() {
                if(!this.inputText.trim()) return;
                const txt = this.inputText;
                this.inputText = ''; // Clear immediately
                
                try {
                    await uni.$api('service.php?action=send', 'POST', { content: txt, type: 'text' });
                    this.fetchHistory();
                    this.scrollToBottom();
                } catch(e) {
                    uni.showToast({title:'发送失败', icon:'none'});
                }
            },
            chooseImage() {
                uni.chooseImage({
                    count: 1,
                    success: (res) => {
                        const path = res.tempFilePaths[0];
                        this.uploadAndSend(path);
                    }
                });
            },
            uploadAndSend(filePath) {
                uni.showLoading({title:'发送中...'});
                uni.uploadFile({
                    url: 'https://ref.tajian.cc/backend/api/service.php?action=upload',
                    filePath: filePath,
                    name: 'file',
                    header: {
                        'Authorization': 'Bearer ' + uni.getStorageSync('token')
                    },
                    success: (uploadRes) => {
                        uni.hideLoading();
                        try {
                            const data = JSON.parse(uploadRes.data);
                            if(data.code == 200) {
                                // Send as msg
                                uni.$api('service.php?action=send', 'POST', { content: data.data.url, type: 'image' })
                                    .then(() => {
                                        this.fetchHistory();
                                        this.scrollToBottom();
                                    });
                            } else {
                                uni.showToast({title: data.message || '上传失败', icon:'none'});
                            }
                        } catch(e) {
                             uni.showToast({title: '解析失败', icon:'none'});
                        }
                    },
                    fail: (err) => {
                        uni.hideLoading();
                        console.error(err);
                        uni.showToast({title:'上传出错', icon:'none'});
                    }
                });
            },
            previewImage(url) {
                uni.previewImage({ urls: [url] });
            }
		}
	}
</script>

<style>
    .chat-container { background: #f5f5f5; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
    .msg-list { flex: 1; height: 0; box-sizing: border-box; padding: 15px; }
    
    .msg-item { display: flex; margin-bottom: 20px; align-items: flex-start; }
    .msg-item.user { flex-direction: row-reverse; }
    
    .avatar { 
        width: 40px; 
        height: 40px; 
        border-radius: 4px; 
        background: linear-gradient(135deg, #07C160, #10B981); 
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .avatar-text {
        font-size: 12px;
        color: #fff;
        font-weight: bold;
    }
    .avatar.admin { background: linear-gradient(135deg, #3B82F6, #1D4ED8); margin-right: 10px; }
    .avatar.user { background: linear-gradient(135deg, #07C160, #10B981); margin-left: 10px; }
    
    .content-bubble { 
        max-width: 70%; 
        padding: 10px 14px; 
        border-radius: 8px; 
        font-size: 15px; 
        line-height: 1.4; 
        position: relative;
        word-break: break-all;
    }
    .msg-item.admin .content-bubble { background: #fff; color: #333; }
    .msg-item.user .content-bubble { background: #95ec69; color: #000; }
    
    .content-image { 
        max-width: 60%; 
        border-radius: 8px; 
        overflow: hidden;
    }
    .content-image image { 
        width: 100%;
        max-width: 200px;
        min-width: 80px;
        display: block;
        border-radius: 8px; 
        background: #eee;
    }
    
    .input-area { 
        min-height: 50px; 
        background: #f7f7f7; 
        border-top: 1px solid #ddd; 
        display: flex; 
        align-items: center; 
        padding: 5px 10px; 
        padding-bottom: constant(safe-area-inset-bottom);
        padding-bottom: env(safe-area-inset-bottom);
    }
    .input-box { flex: 1; height: 36px; background: #fff; border-radius: 4px; padding: 0 10px; font-size: 14px; margin-bottom: 0; }
    .send-btn { width: 60px; height: 36px; line-height: 36px; text-align: center; background: #07C160; color: #fff; border-radius: 4px; margin-left: 10px; font-size: 14px; }
    .icon-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
</style>
