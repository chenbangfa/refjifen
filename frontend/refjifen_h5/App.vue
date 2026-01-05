<script>
	export default {
		onLaunch: function() {
			console.log('App Launch')
            // Global API Helper
            uni.$api = async (url, method = 'GET', data = {}) => {
                const token = uni.getStorageSync('token');
                const baseUrl = 'http://ref.tajian.cc/RefJifen/backend/api/'; // CHANGE THIS IN PROD
                
                return new Promise((resolve, reject) => {
                    uni.request({
                        url: baseUrl + url,
                        method: method,
                        data: data,
                        header: {
                            'Authorization': 'Bearer ' + token
                        },
                        success: (res) => {
                            if (res.data.code === 401) {
                                uni.reLaunch({ url: '/pages/login/login' });
                            }
                            resolve(res.data);
                        },
                        fail: (err) => {
                            reject(err);
                        }
                    });
                });
            };
		}
	}
</script>

<style>
	/* Global CSS */
	page { background-color: #f5f5f5; }
    .container { padding: 20px; }
    .btn { background: #007AFF; color: #fff; text-align: center; padding: 10px; border-radius: 5px; margin-top: 10px; }
    .input { background: #fff; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
</style>
