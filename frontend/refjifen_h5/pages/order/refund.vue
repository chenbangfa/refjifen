<template>
	<view class="container">
        <view class="form-group">
            <view class="form-item">
                <text class="label">订单编号</text>
                <text class="value">{{ order_sn }}</text>
            </view>
            <view class="form-item">
                <text class="label">售后类型</text>
                <radio-group @change="onTypeChange" class="radio-group">
                    <label class="radio"><radio value="1" checked="true" color="#07C160" />仅退款</label>
                    <label class="radio"><radio value="2" color="#07C160" />退货退款</label>
                </radio-group>
            </view>
            <view class="form-item">
                <text class="label">申请原因</text>
                <input class="input" v-model="reason" placeholder="请输入申请原因" />
            </view>
        </view>
        
        <view class="upload-group">
            <view class="group-title">凭证图片 (可选)</view>
            <view class="img-list">
                <view class="img-item" v-for="(img, idx) in images" :key="idx">
                    <image :src="img" mode="aspectFill" @click="previewImage(img)"></image>
                    <view class="del-btn" @click="delImage(idx)">x</view>
                </view>
                <view class="upload-btn" @click="chooseImage" v-if="images.length < 3">
                    <text class="iconfont icon-camera">+</text>
                </view>
            </view>
        </view>
        
        <view class="submit-btn" @click="submit">提交申请</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				order_sn: '',
                type: '1',
                reason: '',
                images: []
			}
		},
		onLoad(options) {
            if(options.order_sn) {
                this.order_sn = options.order_sn;
            } else {
                uni.showToast({ title: '参数错误', icon:'none' });
                setTimeout(() => uni.navigateBack(), 1500);
            }
		},
		methods: {
            onTypeChange(e) {
                this.type = e.detail.value;
            },
            chooseImage() {
                uni.chooseImage({
                    count: 3 - this.images.length,
                    success: (res) => {
                        this.uploadImages(res.tempFilePaths);
                    }
                });
            },
            async uploadImages(paths) {
                const token = uni.getStorageSync('token');
                if (!token) return uni.showToast({ title: '请先登录', icon: 'none' });

                uni.showLoading({ title: '上传中' });
                for(let path of paths) {
                    try {
                        const uploadRes = await new Promise((resolve, reject) => {
                            uni.uploadFile({
                                url: 'https://ref.tajian.cc/backend/api/upload.php',
                                filePath: path,
                                name: 'file',
                                header: {
                                    'Authorization': 'Bearer ' + token
                                },
                                success: resolve,
                                fail: reject
                            });
                        });
                        
                        const data = JSON.parse(uploadRes.data);
                        if(data.code == 200) {
                            this.images.push(data.url);
                        } else {
                            uni.showToast({ title: data.message || '上传失败', icon: 'none' });
                        }
                    } catch(e){
                         uni.showToast({ title: '上传出错', icon: 'none' });
                    }
                }
                uni.hideLoading();
            },
            delImage(idx) {
                this.images.splice(idx, 1);
            },
            previewImage(url) {
                uni.previewImage({ urls: [url] });
            },
            async submit() {
                if(!this.reason) return uni.showToast({ title: '请填写原因', icon:'none'});
                
                uni.showLoading({ title: '提交中' });
                try {
                    const postData = {
                        order_sn: this.order_sn,
                        type: this.type,
                        reason: this.reason,
                        images: this.images.join(',')
                    };
                    const res = await uni.$api('mall.php?action=apply_refund', 'POST', postData);
                    uni.hideLoading();
                    if(res.code == 200) {
                        uni.showToast({ title: '提交成功' });
                        setTimeout(() => {
                            uni.navigateBack();
                        }, 1500);
                    } else {
                        uni.showToast({ title: res.message || '提交失败', icon:'none'});
                    }
                } catch(e) {
                    uni.hideLoading();
                    uni.showToast({ title: '网络错误', icon:'none'});
                }
            }
		}
	}
</script>

<style>
    .container { padding: 15px; background: #f5f5f5; min-height: 100vh; }
    
    .form-group { background: #fff; border-radius: 8px; padding: 0 15px; margin-bottom: 15px; }
    .form-item { display: flex; align-items: center; border-bottom: 1px solid #f9f9f9; padding: 15px 0; }
    .form-item:last-child { border-bottom: none; }
    .label { width: 80px; font-size: 14px; color: #333; }
    .value { font-size: 14px; color: #666; }
    .input { flex: 1; font-size: 14px; }
    
    .radio-group { display: flex; }
    .radio { margin-right: 20px; font-size: 14px; display: flex; align-items: center; }
    
    .upload-group { background: #fff; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
    .group-title { font-size: 14px; color: #333; margin-bottom: 15px; }
    .img-list { display: flex; flex-wrap: wrap; gap: 10px; }
    .img-item { width: 80px; height: 80px; position: relative; }
    .img-item image { width: 100%; height: 100%; border-radius: 4px; }
    .del-btn { position: absolute; top: -5px; right: -5px; width: 18px; height: 18px; background: rgba(0,0,0,0.5); color: #fff; border-radius: 50%; text-align: center; line-height: 16px; font-size: 12px; }
    .upload-btn { width: 80px; height: 80px; border: 1px dashed #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 24px; }
    
    .submit-btn { background: #07C160; color: #fff; text-align: center; height: 44px; line-height: 44px; border-radius: 22px; font-size: 16px; font-weight: bold; margin-top: 30px; }
</style>
