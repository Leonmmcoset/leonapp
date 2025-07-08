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

## 快速启动指南
对于有经验的开发者，可按照以下步骤快速部署：
```cmd
# 1. 克隆项目并进入目录
git clone <repository-url> app2
cd app2

# 2. 创建并配置环境文件
copy config.example.php config.php
# 编辑config.php设置数据库和邮件信息

# 3. 创建数据库并导入结构
mysql -u root -p -e "CREATE DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p your_db_name < app_store.sql

# 4. 安装依赖并设置权限
composer install
icacls files /grant Users:(OI)(CI)W
icacls images /grant Users:(OI)(CI)W

# 5. 启动开发服务器
php -S localhost:8000
```
访问 http://localhost:8000 开始使用，管理员后台地址：http://localhost:8000/admin

## 详细安装教程

### 1. 环境准备
确保您的系统满足以下要求：
- PHP 7.4+（推荐PHP 8.0+）
- MySQL 5.7+ 或 MariaDB 10.2+
- Composer（PHP依赖管理工具）
- Web服务器（Apache/Nginx/IIS）或PHP内置服务器
- Git（可选，用于版本控制）

#### 检查PHP环境
打开命令提示符，输入以下命令验证PHP版本：
```cmd
php -v
# 应显示PHP 7.4.0或更高版本

# 检查必要扩展
php -m | findstr /i "mysqli pdo_mysql json curl fileinfo"
# 确保以上扩展均已安装
```

### 2. 获取项目代码
选择以下任一方式获取代码：

#### 方式一：使用Git克隆（推荐）
```cmd
git clone <repository-url> app2
cd app2
```

#### 方式二：手动下载
1. 从项目仓库下载ZIP压缩包
2. 解压到本地目录（如 `d:\app2`）
3. 打开命令提示符，进入项目目录：
   ```cmd
   cd d:\app2
   ```

### 3. 数据库配置

#### 创建数据库
1. 登录MySQL控制台：
   ```cmd
   mysql -u root -p
   ```
2. 创建数据库（将`your_db_name`替换为您喜欢的数据库名称）：
   ```sql
   CREATE DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit
   ```

#### 导入数据库结构
```cmd
mysql -u root -p your_db_name < app_store.sql
```
> **注意**：请确保在导入前替换命令中的`your_db_name`为您实际创建的数据库名称

### 4. 应用配置

#### 创建配置文件
如果项目中存在`config.example.php`：
```cmd
copy config.example.php config.php
```
如果不存在，请手动创建`config.php`文件并添加以下内容：
```php
<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_db_name');

define('APP_URL', 'http://localhost'); // 应用基础URL
define('ADMIN_EMAIL', 'admin@example.com'); // 默认管理员邮箱
define('ADMIN_PASSWORD', 'admin123'); // 默认管理员密码（首次登录后必须修改）

// 邮件服务配置（用于开发者注册验证）
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_email_password');
define('SMTP_ENCRYPTION', 'ssl'); // 通常为ssl或tls

define('DEBUG_MODE', true); // 开发环境设为true，生产环境设为false
?>
```

#### 配置参数说明
| 参数 | 说明 | 示例值 |
|------|------|--------|
| DB_HOST | 数据库主机地址 | localhost |
| DB_USER | 数据库用户名 | root |
| DB_PASS | 数据库密码 | your_actual_password |
| DB_NAME | 数据库名称 | app_store |
| APP_URL | 应用访问URL | http://localhost/app2 |
| DEBUG_MODE | 调试模式开关 | true/false |

### 5. 安装依赖
使用Composer安装项目依赖：
```cmd
composer install
```
> 如果没有安装Composer，请先从 https://getcomposer.org/ 下载并安装

### 6. 设置目录权限
项目需要对以下目录有写入权限：
- `files/`：存储上传的应用文件
- `images/`：存储应用截图和图标

#### 图形界面方式（推荐）
1. 在文件资源管理器中找到项目目录
2. 右键点击`files`文件夹，选择**属性**
3. 切换到**安全**选项卡，点击**编辑**
4. 选择当前用户，勾选**写入**权限，点击**确定**
5. 对`images`文件夹执行相同操作

#### 命令行方式
```cmd
icacls files /grant Users:(OI)(CI)W
icacls images /grant Users:(OI)(CI)W
```

### 7. 配置Web服务器

#### 选项A：使用PHP内置开发服务器（推荐用于开发）
```cmd
php -S localhost:8000
```
然后在浏览器中访问：http://localhost:8000

#### 选项B：配置Apache服务器
1. 确保`mod_rewrite`模块已启用
2. 创建虚拟主机配置：
```apache
<VirtualHost *:80>
    ServerName appstore.local
    DocumentRoot "d:/app2"
    <Directory "d:/app2">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
3. 修改`hosts`文件添加：`127.0.0.1 appstore.local`
4. 重启Apache，访问 http://appstore.local

#### 选项C：配置Nginx服务器
```nginx
server {
    listen 80;
    server_name appstore.local;
    root d:/app2;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 8. 首次使用与安全设置

#### 管理员账号初始化
1. 访问管理员登录页面：http://localhost:8000/admin/login.php
2. 使用默认账号登录：
   - 用户名：`admin@example.com`（来自config.php中的ADMIN_EMAIL）
   - 密码：`admin123`（来自config.php中的ADMIN_PASSWORD）
3. **重要**：登录后立即点击右上角头像，选择**修改密码**，设置强密码

#### 安全建议
- 生产环境中设置`define('DEBUG_MODE', false);`
- 定期备份数据库
- 不要将`config.php`提交到版本控制系统
- 保持PHP和所有依赖包为最新安全版本

## 使用教程

### 开发者功能

#### 注册开发者账号
1. 访问开发者注册页面：http://localhost:8000/developer/register.php
2. 填写注册信息，提交后系统会发送验证邮件
3. 点击邮件中的验证链接激活账号

#### 上传新应用
1. 登录开发者后台：http://localhost:8000/developer/login.php
2. 点击**上传新应用**按钮
3. 填写应用信息：
   - 应用名称、描述、版本号
   - 选择应用类别和年龄分级
   - 上传应用图标（推荐尺寸：512x512px）
   - 上传应用截图（最多5张）
   - 上传应用安装包（支持.zip格式）
4. 点击**提交审核**，等待管理员审核

#### 管理应用版本
1. 在开发者后台点击应用名称进入管理页面
2. 点击**发布新版本**添加应用更新
3. 填写版本变更说明和更新内容
4. 上传新版本安装包

### 管理员功能

#### 应用审核
1. 登录管理员后台：http://localhost:8000/admin/login.php
2. 点击**应用审核**菜单
3. 查看待审核应用列表，点击**查看详情**
4. 审核应用信息和安装包，点击**通过**或**拒绝**并填写反馈

#### 管理应用分类
1. 在管理员后台点击**分类管理**
2. 可以添加、编辑或删除应用分类
3. 设置分类排序和显示状态

#### 系统信息查看
1. 在管理员后台点击**系统信息**
2. 查看服务器环境、PHP配置和数据库状态
3. 监控应用总数、开发者数量和文件存储使用情况

### API使用指南

#### 获取应用列表
```http
GET /api.php?action=list&page=1&limit=10
```
返回JSON格式的应用列表数据

#### 获取应用详情
```http
GET /api.php?action=app&id=1
```
返回指定ID的应用详细信息

#### API响应格式
```json
{
  "success": true,
  "data": {},
  "message": "操作成功"
}
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