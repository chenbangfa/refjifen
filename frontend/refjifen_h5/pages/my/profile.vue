<template>
	<view class="container">
		<view class="form-group">
            <view class="form-item avatar-row" @click="changeAvatar">
                <text class="label">头像</text>
                <view class="right">
                    <image :src="user.avatar || '/static/my.png'" class="avatar"></image>
                    <text class="iconfont icon-right arrow"></text>
                </view>
            </view>
            <view class="form-item">
                <text class="label">姓名</text>
                <input class="input" v-model="user.nickname" placeholder="请输入姓名" />
            </view>
             <view class="form-item">
                <text class="label">手机号</text>
                <input class="input" v-model="user.mobile" placeholder="请输入手机号" type="number" />
            </view>
        </view>
        
        <view class="btn-area">
            <button class="save-btn" @click="save">保存修改</button>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				user: {
                    nickname: '',
                    mobile: '',
                    avatar: ''
                }
			}
		},
        onLoad() {
            this.fetchInfo();
        },
		methods: {
            async fetchInfo() {
                try {
                    const res = await uni.$api('account.php?action=info');
                    if(res.code == 200) {
                        this.user = res.data;
                    }
                } catch(e) {}
            },
            changeAvatar() {
                uni.chooseImage({
                    count: 1,
                    sizeType: ['compressed'],
                    success: (chooseImageRes) => {
                        const tempFilePaths = chooseImageRes.tempFilePaths;
                        const token = uni.getStorageSync('token');
                        
                        uni.showLoading({title: '上传中...'});
                        
                        uni.uploadFile({
                            url: 'https://ref.tajian.cc/backend/api/upload.php', 
                            filePath: tempFilePaths[0],
                            name: 'file',
                            header: {
                                'Authorization': 'Bearer ' + token
                            },
                            success: (uploadFileRes) => {
                                uni.hideLoading();
                                // uploadFileRes.data is a string (JSON), need parsing
                                try {
                                    const data = JSON.parse(uploadFileRes.data);
                                    if(data.code == 200) {
                                        this.user.avatar = data.url;
                                        uni.showToast({title: '上传/预览成功', icon: 'none'});
                                    } else {
                                        uni.showToast({title: data.message || '上传失败', icon: 'none'});
                                    }
                                } catch(e) {
                                    uni.showToast({title: '解析失败', icon: 'none'});
                                }
                            },
                            fail: (err) => {
                                uni.hideLoading();
                                uni.showToast({title: '网络错误', icon: 'none'});
                            }
                        });
                    }
                });
            },
            async save() {
                uni.showLoading({title:'保存中'});
                try {
                    const res = await uni.$api('account.php?action=update_profile', 'POST', {
                        nickname: this.user.nickname,
                        mobile: this.user.mobile,
                        avatar: this.user.avatar
                        // Note: Avatar being a local blob URL won't persuade to DB effectively without upload.
                        // Ignoring avatar persistence for now unless we implement file upload API.
                    });
                    uni.hideLoading();
                    if(res.code == 200) {
                        uni.showToast({title:'保存成功'});
                        setTimeout(()=> uni.navigateBack(), 1500);
                    } else {
                        uni.showToast({title: res.message, icon:'none'});
                    }
                } catch(e) { uni.hideLoading(); }
            }
		}
	}
</script>

<style>
    .container { padding: 15px; min-height: 100vh; background: linear-gradient(135deg, #B2EBF2 0%, #BBDEFB 100%); }
    .form-group { background: #fff; border-radius: 12px; padding: 0 15px; margin-bottom: 20px; }
    .form-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f5f5f5; height: 50px; }
    .form-item:last-child { border-bottom: none; }
    
    .label { font-size: 16px; color: #333; }
    .input { text-align: right; font-size: 16px; color: #333; }
    .right { display: flex; align-items: center; }
    .avatar { width: 40px; height: 40px; border-radius: 20px; margin-right: 10px; background:#eee; }
    .arrow { color: #ccc; font-size: 14px; }
    
    .btn-area { margin-top: 30px; }
    .save-btn { background: #07C160; color: #fff; border-radius: 25px; height: 44px; line-height: 44px; font-size: 16px; }
    .save-btn:active { opacity: 0.9; }
</style>
