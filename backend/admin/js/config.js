window.apiBase = 'https://ref.tajian.cc/backend/api/admin.php';
window.API_BASE_URL = window.apiBase;

// Check Login Status on Page Load
window.checkLogin = function () {
    if (!localStorage.getItem('admin_login') && !window.location.href.includes('login.html')) {
        window.location.href = 'login.html';
    }
}

// Global Axios Wrapper
window.req = async (action, data = {}, method = 'POST') => {
    try {
        const res = await axios({
            url: `${window.apiBase}?action=${action}`,
            method: method,
            data: data
        });
        if (res.data.code == 401) {
            localStorage.removeItem('admin_login');
            window.location.href = 'login.html';
            return null;
        }
        if (res.data.code != 200) {
            ElementPlus.ElMessage.error(res.data.message);
            return null;
        }
        return res.data;
    } catch (e) {
        ElementPlus.ElMessage.error('网络错误或接口异常');
        return null;
    }
};

// Common Formatter Utils
window.formatUtils = {
    methodMap: { bank: '银行卡', alipay: '支付宝', wechat: '微信' },
    levelMap: ['普通', 'VIP', '金卡', '钻卡'],
    formatLevel(lv) { return ['普通', 'VIP', '金卡', '钻卡'][lv] || lv; },
    formatDetails(json) {
        const trans = {
            bank_name: '银行名称',
            account_no: '银行账号',
            account_name: '开户人',
            alipay_account: '支付宝账号',
            real_name: '真实姓名',
            wechat_account: '微信号',
            realname: '真实姓名',
            account_number: '账号',
            image: '收款码',
            image_url: '收款码'
        };
        try {
            const obj = JSON.parse(json);
            return Object.entries(obj).map(([k, v]) => `${trans[k] || k}: ${v}`).join('\n');
        } catch (e) { return json; }
    }
};
