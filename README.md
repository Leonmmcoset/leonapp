# App Store 项目

这是一个基于 PHP 7.4 的 App Store 项目，使用 Bootstrap 实现 Fluent Design 风格界面，数据存储采用 MySQL 数据库。

## 项目结构
```
app2/
├── config.php         # 配置文件，包含数据库和管理员信息
├── app_store.sql      # 数据库初始化 SQL 文件
├── index.php          # 首页
├── app.php            # App 信息页
├── admin/             # 管理员后台目录
│   ├── addapp.php
│   ├── deleteapp.php
│   ├── editapp.php
│   ├── index.php
│   ├── login.php
│   ├── manage_tags.php
│   ├── review_apps.php
│   └── system_info.php
├── developer/         # 开发者后台目录
│   ├── dashboard.php
│   ├── edit_app.php
│   ├── login.php
│   ├── logout.php
│   ├── profile.php
│   ├── register.php
│   └── upload_app.php
├── vendor/            # Composer 依赖
├── includes/          # 通用包含文件
├── api.php            # API 接口文件
├── styles.css         # 自定义 CSS 文件
├── images/            # 存储 App 预览图片和年龄分级 SVG
│   ├── age_3plus.svg
│   ├── age_7plus.svg
│   ├── age_12plus.svg
│   ├── age_17plus.svg
├── files/             # 存储 App 文件
```

## 环境要求
- PHP 7.4+
- MySQL 5.7+
- Composer
- Node.js (可选，用于前端资源构建)
- Web 服务器（如 Apache 或 Nginx）

## 安装步骤
1. 创建项目目录并将代码复制到该目录下。
2. 修改 `config.php` 文件，配置 MySQL 数据库信息、管理员账号和邮件服务设置。
3. 安装依赖包（将自动创建 `vendor` 目录并安装 PHPMailer 等必要依赖）：
   ```bash
   composer install
   ```
4. 登录 MySQL 数据库，创建名为'awa'的数据库：
   ```sql
   CREATE DATABASE awa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. 执行 `app_store.sql` 文件导入数据库结构：
   ```sql
   mysql -u your_username -p awa < app_store.sql
   ```
6. 创建 `files` 和 `images` 目录，并设置正确权限：
   ```bash
   mkdir -p files images
   chmod 755 files images
   ```

## 功能说明
- **首页**：展示最新 App 列表，包含基本信息和评分。
- **App 信息页**：显示 App 详细信息、版本历史、预览图片和用户评价，支持用户评分。
- **管理页**：管理员可以添加、删除 App，审核应用，管理标签和查看系统信息。
- **开发者后台**：开发者可以注册账号、管理应用、上传新版本和查看应用统计。
- **API 接口**：提供 `/api` 获取 App 列表，`/api/app/<编号>` 获取单个 App 详细信息。

## 管理员登录
默认管理员账号信息在 `config.php` 中配置，登录后可访问管理页面。

## 故障排除
- **数据库导入错误**：确保数据库名称为'awa'且已创建，检查SQL文件路径是否正确
- **权限问题**：确认 `files` 和 `images` 目录权限设置为755
- **邮件发送失败**：检查 `config.php` 中的SMTP配置，确保端口（通常465或587）和加密方式正确
- **类找不到错误**：运行 `composer install` 确保所有依赖已正确安装

## 注意事项
- 请确保 `files`、`images` 目录以及其子目录有足够的写入权限（推荐设置权限为755）。
- 生产环境中必须修改默认管理员密码和数据库连接信息，确保系统安全。
- 邮件服务配置：请在 `config.php` 中正确设置 SMTP 服务器地址、端口、用户名和密码，以确保开发者邮箱验证功能正常工作。