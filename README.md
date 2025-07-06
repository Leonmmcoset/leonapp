# App Store 项目

这是一个基于 PHP 7.4 的 App Store 项目，使用 Bootstrap 实现 Fluent Design 风格界面，数据存储采用 MySQL 数据库。

## 项目结构
```
app2/
├── config.php         # 配置文件，包含数据库和管理员信息
├── app_store.sql      # 数据库初始化 SQL 文件
├── index.php          # 首页
├── app.php            # App 信息页
├── admin.php          # App 管理页
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
- PHP 7.4
- MySQL
- Web 服务器（如 Apache 或 Nginx）

## 安装步骤
1. 创建项目目录并将代码复制到该目录下。
2. 修改 `config.php` 文件，配置 MySQL 数据库信息和管理员账号。
3. 执行 `app_store.sql` 文件，创建数据库和表结构。可以使用以下命令：
   ```sql
   mysql -u your_username -p your_database < app_store.sql
   ```
4. 创建 `files` 和 `images` 目录，并确保 Web 服务器对这些目录有写入权限。

## 功能说明
- **首页**：展示最新 App 列表，包含基本信息和评分。
- **App 信息页**：显示 App 详细信息、版本历史、预览图片和用户评价，支持用户评分。
- **管理页**：管理员可以添加、删除 App，上传 App 文件和预览图片。
- **API 接口**：提供 `/api` 获取 App 列表，`/api/app/<编号>` 获取单个 App 详细信息。

## 管理员登录
默认管理员账号信息在 `config.php` 中配置，登录后可访问管理页面。

## 注意事项
- 请确保 `files` 和 `images` 目录有足够的写入权限。
- 生产环境中建议修改管理员密码和数据库信息，保证系统安全。