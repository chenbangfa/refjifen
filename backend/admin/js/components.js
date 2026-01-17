window.SideMenu = {
    props: ['active'],
    template: `
    <div class="sidebar-wrapper">
        <div class="sidebar-overlay" @click="closeSidebar"></div>
        <div class="sidebar">
            <div class="sidebar-title">新国创</div>
            <a href="users.html" class="nav-item" :class="{active: active=='users'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                用户管理
            </a>
            <a href="withdrawals.html" class="nav-item" :class="{active: active=='withdrawals'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M5 8h14V6H5v2zm0 4h14v-2H5v2zm0 4h14v-2H5v2zm0 4h14v-2H5v2zM5 4h14V2H5v2z"></path></svg>
                提现审核
            </a>
            <a href="categories.html" class="nav-item" :class="{active: active=='categories'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"></path></svg>
                商品分类
            </a>
            <a href="products.html" class="nav-item" :class="{active: active=='products'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"></path></svg>
                商品管理
            </a>
            <a href="orders.html" class="nav-item" :class="{active: active=='orders'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"></path></svg>
                订单列表
            </a>
            <a href="nav_items.html" class="nav-item" :class="{active: active=='nav_items'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M4 8h4V4H4v4zm6 12h4v-4h-4v4zm-6 0h4v-4H4v4zm0-6h4v-4H4v4zm6 0h4v-4h-4v4zm6-10v4h4V4h-4zm-6 4h4V4h-4v4zm6 6h4v-4h-4v4zm0 6h4v-4h-4v4z"></path></svg>
                首页导航
            </a>
            <a href="banners.html" class="nav-item" :class="{active: active=='banners'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"></path></svg>
                轮播图管理
            </a>
            <a href="reward_rules.html" class="nav-item" :class="{active: active=='reward_rules'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.15-1.46-3.27-3.4h1.96c.1 1.05 1.18 1.91 2.53 1.91 1.38 0 2.53-.95 2.53-2.16 0-1.26-1.15-1.84-2.53-2.22-1.92-.51-4.48-1-4.48-3.9 0-1.82 1.38-2.98 2.76-3.35V3h2.67v1.9c1.71.35 3.15 1.48 3.27 3.42h-1.95c-.15-1.04-1.16-1.91-2.58-1.91-1.38 0-2.59.95-2.59 2.16 0 1.25 1.15 1.83 2.59 2.22 1.92.51 4.47 1 4.47 3.9 0 1.84-1.38 2.99-2.76 3.4z"></path></svg>
                赠送规则管理
            </a>
            <a href="rules.html" class="nav-item" :class="{active: active=='rules'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"></path></svg>
                动态返佣规则
            </a>
            <a href="articles.html" class="nav-item" :class="{active: active=='articles'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"></path></svg>
                通知公告
            </a>
            <a href="chat.html" class="nav-item" :class="{active: active=='chat'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg>
                客服咨询
            </a>
            <a href="password.html" class="nav-item" :class="{active: active=='password'}">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2z"></path></svg>
                修改密码
            </a>
            <div class="nav-item logout-btn" @click="handleLogout">
                <svg viewBox="0 0 24 24" width="18" height="18" style="margin-right:10px;fill:currentColor"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"></path></svg>
                退出登录
            </div>
        </div>
    </div>
    `,
    setup() {
        const handleLogout = async () => {
            if (confirm('确定要退出登录吗？')) {
                localStorage.removeItem('admin_login');
                window.location.href = 'login.html';
            }
        };
        const closeSidebar = () => {
            document.querySelector('.sidebar').classList.remove('open');
            document.querySelector('.sidebar-overlay').classList.remove('show');
        };
        return { handleLogout, closeSidebar };
    }
};

window.LayoutHeader = {
    props: ['title'],
    template: `
    <div class="header">
        <div style="display:flex;align-items:center;">
            <div class="mobile-menu-btn" @click="toggleSidebar" style="margin-right:15px;cursor:pointer;display:none;">
                <svg viewBox="0 0 24 24" width="24" height="24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"></path></svg>
            </div>
            <h2>{{ title }}</h2>
        </div>
        <div class="user-info">Admin</div>
    </div>
    `,
    setup() {
        const toggleSidebar = () => {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            if (sidebar) sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('show');
        };
        return { toggleSidebar };
    }
};
