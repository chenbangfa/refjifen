<script>
	export default {
		onLaunch: function() {
			console.log('App Launch')
            
            // --- Global Route Guard ---
            const whiteList = [
                '/pages/login/login', 
                '/pages/login/register'
            ];
            
            const checkAuth = (url) => {
                if(!url) return true;
                let path = url.split('?')[0];
                if(!path.startsWith('/')) path = '/' + path;
                
                if (whiteList.includes(path)) return true;
                
                const token = uni.getStorageSync('token');
                if (!token) {
                    uni.reLaunch({ url: '/pages/login/login' });
                    return false;
                }
                return true;
            };
            
            ['navigateTo', 'redirectTo', 'switchTab', 'reLaunch'].forEach(item => {
                uni.addInterceptor(item, {
                    invoke(args) {
                        return checkAuth(args.url);
                    }
                });
            });

            // Global API Helper
            uni.$api = async (url, method = 'GET', data = {}) => {
                const token = uni.getStorageSync('token');
                const baseUrl = 'https://ref.tajian.cc/backend/api/'; // Production URL
                
                return new Promise((resolve, reject) => {
                    uni.request({
                        url: baseUrl + url,
                        method: method,
                        data: data,
                        header: {
                            'Authorization': 'Bearer ' + token,
                            'Content-Type': 'application/json'
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
		},
        onShow: function() {
            const token = uni.getStorageSync('token');
            if(!token) {
                const pages = getCurrentPages();
                if(pages.length > 0) {
                     const route = pages[pages.length - 1].route;
                     if(route != 'pages/login/login' && route != 'pages/login/register') {
                         uni.reLaunch({ url: '/pages/login/login' });
                     }
                }
            }
        }
	}
</script>

<style>
	/* Global CSS - WeChat Style */
	page { 
        background-color: #EDEDED; 
        font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', 'miui', 'Hiragino Sans GB', 'Microsoft Yahei', sans-serif;
        color: #333;
    }
    
    .container { 
        padding: 30px; 
        display: flex;
        flex-direction: column;
    }

    /* WECHAT BUTTON STYLE */
    .btn { 
        background-color: #07C160; 
        color: #fff; 
        text-align: center; 
        padding: 12px; 
        border-radius: 8px; 
        margin-top: 20px; 
        font-size: 16px; 
        font-weight: 600;
        transition: opacity 0.2s;
    }
    .btn:active {
        opacity: 0.8;
    }
    .btn[disabled] {
        background-color: #A5D6B6;
        color: #E8F5EB;
    }

    /* WECHAT INPUT STYLE */
    .input-group {
        background: #fff;
        border-radius: 8px;
        padding: 0 16px;
        margin-bottom: 16px;
    }
    .input-item {
        height: 56px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #f0f0f0;
    }
    .input-item:last-child {
        border-bottom: none;
    }
    .input { 
        flex: 1; 
        height: 100%; 
        font-size: 16px; 
        color: #333;
    }
    .input-label {
        width: 80px;
        font-size: 16px;
        color: #333;
    }
</style>
