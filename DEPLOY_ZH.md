# 部署指南 (Deployment Guide)

## 1. 后端部署 (Backend)

### 第一步：准备数据库
1. 确保你的电脑上安装了 MySQL (或者使用 MAMP/XAMPP 等集成环境)。
2. 创建一个新的数据库，命名为 `refjifen` (你可以自定义名字，但要记得修改配置)。
3. 将项目目录下的 `backend/database.sql` 文件导入到该数据库中。

### 第二步：修改配置文件
1. 打开文件 `backend/config/database.php`。
2. 修改 `$host`, `$username`, `$password`, `$db_name` 为你本地数据库的真实信息。

### 第三步：启动 PHP 服务
如果你安装了 MAMP/XAMPP：
- 将 `backend` 文件夹复制到你的网站根目录（如 `htdocs` 或 `www`）。
- 确保可以通过浏览器访问，例如 `http://localhost/RefJifen/backend/api/auth.php`。

如果你想使用 Mac 自带的 PHP 开发服务（临时测试）：
1. 打开终端 (Terminal)。
2. 进入 backend 目录：`cd /Volumes/project/RefJifen/backend`
3. 启动服务：`php -S localhost:8080`
   - 此时你的 API 地址就是 `http://localhost:8080/api/`。

---

## 2. 前端部署 (Frontend)

### 第一步：安装环境
1. 下载并安装 **HBuilderX** (这是开发 UniApp 最好用的工具)。
2. 注册一个 DCloud 账号（用于打包，如果只是本地运行可以跳过，但推荐注册）。

### 第二步：配置 API 地址
1. 使用 HBuilderX 打开 `frontend/refjifen_h5` 文件夹。
2. 打开 `App.vue` 文件。
3. 找到代码中的 `baseUrl` 变量：
   ```javascript
   // 请将 http://localhost/RefJifen/backend/api/ 替换为你实际的后端地址
   // 如果你刚才用 php -S localhost:8080 启动的，这里就填 http://localhost:8080/api/
   const baseUrl = 'http://localhost/RefJifen/backend/api/'; 
   ```

### 第三步：运行项目
1. 在 HBuilderX 顶部菜单点击 **运行 (Run)** -> **运行到浏览器 (Run to Browser)** -> 选择 Chrome 或 Safari。
2. 如果没有任何报错，你就能看到 H5 界面了。

### 第四步：打包成 Android App
1. 在 HBuilderX 菜单点击 **发行 (Publish)** -> **原生App-云打包**。
2. 选择 "Android"。
3. 按照提示进行打包（通常需要等待几分钟），完成后会自动下载 `.apk` 安装包。

---

## 3. 服务器正式部署 (Production)

### 选项 A：使用 Git (推荐)
你提到有 Github 仓库，这是最稳健的方式。

**在本地提交代码：**
```bash
cd /Volumes/project/RefJifen
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/chenbangfa/refjifen.git
git push -u origin main
```

**在服务器上拉取：**
```bash
cd /www/wwwroot/your_site
git clone https://github.com/chenbangfa/refjifen.git .
```

### 关键注意：前端 H5 必须打包！
**不要直接上传 `frontend` 源码文件夹到服务器给用户访问！**

1. **打包 H5**：
   - 在 HBuilderX 顶部菜单点击 **发行 (Publish)** -> **网站-PC Web或手机H5**。
   - 填写网站标题和域名（如果不知道域名先填 `/`）。
   - 点击发行。
2. **上传文件**：
   - 打包成功后，控制台会显示路径，通常在 `frontend/refjifen_h5/unpackage/dist/build/h5`。
   - **只把这个 `h5` 文件夹里的内容** 上传到服务器的 `public` 或网站根目录。

### 最终服务器目录结构示例
假设你的网站根目录是 `/www/wwwroot/mysite/`：

```
/www/wwwroot/mysite/
├── backend/            <-- 后端PHP源码
│   ├── api/
│   ├── config/
│   └── ...
├── index.html          <-- 前端打包生成的入口 (来自 unpackage/.../h5)
├── static/             <-- 前端打包生成的资源 (来自 unpackage/.../h5)

---

## 4. 宝塔面板 (BT Panel) 部署教程

由于你已经有宝塔环境，部署非常简单：

### 第一步：新建站点
1. 登录宝塔面板 -> **网站** -> **添加站点**。
2. 域名填写：例如 `jifen.yourdomain.com`（建议使用二级域名）。
3. 数据库：选择 **创建MySQL**，设置用户名和密码（**记下来！**）。
4. PHP版本：选择 PHP 7.4 或 8.0 均可。
5. 提交。

### 第二步：上传代码（推荐 Git）
1. 进入该站点的根目录：
   ```bash
   cd /www/wwwroot/ref.tajian.cc
   ```
2. 删除目录下默认的 `index.html` / `404.html`。
3. 点击 **终端** 按钮，输入：
   ```bash
   git clone https://github.com/chenbangfa/refjifen.git .
   ```
   *(注意命令最后有个点 `.`，代表克隆到当前目录。如果目录非空，git可能会报错，需先清空或使用 `git init && git remote add... && git pull`)*
4. 这时你会看到 `backend` 文件夹出现在目录里。

### 第三步：配置数据库
1. 在宝塔面板 -> **数据库** -> 点击刚才创建的数据库的 **管理** (phpMyAdmin)。
2. 导入 `backend/database.sql` 文件。
3. 回到文件管理，编辑 `/www/wwwroot/ref.tajian.cc/backend/config/database.php`，填入刚才创建的数据库账号密码。

### 第四步：构建并上传前端 H5
*这一步最关键，不要搞错！*

1. **本地电脑修改配置**：
   - 打开 HBuilderX -> `frontend/refjifen_h5/App.vue`。
   - 确认 `baseUrl` 已改为：`http://ref.tajian.cc/backend/api/`。
2. **本地打包**：
   - HBuilderX -> 发行 -> 网站-PC Web或手机H5。
   - 填写标题，**运行的基础路径**填 `./`。
3. **上传**：
   - 打包完成后，找到生成的 `h5` 文件夹（里面有 static, index.html）。
   - 把 `h5` 文件夹里的**所有内容**，拖拽上传到宝塔目录 `/www/wwwroot/ref.tajian.cc/`。
   - **最终结构**应该长这样：
     ```
     /www/wwwroot/ref.tajian.cc/
     ├── index.html       (前端入口)
     ├── static/          (前端资源)
     ├── backend/         (后端文件夹)
     └── ...
     ```

### 第五步：访问测试
打开浏览器访问 `http://jifen.yourdomain.com`。
- 如果显示登录页，说明前端部署成功。
- 尝试登录/注册，如果提示成功，说明后端连接成功。

---

## 5. 日常开发与调试建议 (必读)

**你不需要每次改一句代码就打包上传一次！** 这样效率太低了。

### 推荐的开发流程：

1.  **本地开发 (Hot Reload)**：
    - 直接在 HBuilderX 里点击 **运行 -> 运行到浏览器**。
    - 此时你改动代码，浏览器会自动刷新，立即看到效果。
    - **API连接问题**：
        - 如果你想在本地前端连接 **本地后端**：将 `App.vue` 里的 baseUrl 设为 `http://localhost/...`
        - 如果你想在本地前端连接 **线上后端**：将 `App.vue` 里的 baseUrl 设为 `http://jifen.yourdomain.com/backend/api/` (线上地址)。

2.  **只有在以下情况才需要打包上传**：
    - 既然你要给客户通过域名访问了。
    - 或者你要生成 Android APK 安装包了。

    - 或者你要生成 Android APK 安装包了。

**技巧**：
你可以保持 `App.vue` 里指向线上 API，这样你在本地改前端样式，直接就能看到对接线上真实数据的效果，**无需上传前端代码**。

---

## 6. 常见故障排查 (Troubleshooting)

### Q1: 访问网站提示 404 或 403 Forbidden？
**原因 A**：你上传错位置了。
- 错误路径：`/www/wwwroot/ref.tajian.cc/h5/index.html` (多了一层 h5 文件夹)
- 正确路径：`/www/wwwroot/ref.tajian.cc/index.html` (必须在根目录)
**解决**：把 `h5` 文件夹里的所有内容剪切出来，放到根目录。

**原因 B**：权限问题。
- 确保宝塔里该网站目录权限是 `755`，所有者是 `www`。

### Q2: 网站显示了，但无法登录（API 请求失败）？
**原因 A**：混合内容错误 (Mixed Content)。
- 你的网站是 `https://...`，但 App.vue 里 API 写的是 `http://...`。
- 浏览器会阻止 HTTPS 网站请求 HTTP 接口。
**解决**：
1. 确保宝塔里已配置 SSL 证书（Let's Encrypt 免费申请）。
2. 把 `App.vue` 里的 baseUrl 改成 `https://ref.tajian.cc/backend/api/` (注意是 https)。
3. 重新打包 H5 并上传。

### Q3: 只是显示空白页？
**原因**：路由配置错误。
- 确保 HBuilderX 打包时，**运行的基础路径** 填的是 `./`。



