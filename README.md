# App Store 项目

这是一个基于 PHP 7.4 的 App Store 项目，使用 Bootstrap 实现 Fluent Design 风格界面，数据存储采用 MySQL 数据库。项目各页面顶栏已添加 logo 图片，路径为 `/favicon.jpeg`，点击可跳转至首页，图片设置了高度 30px、右边距 10px 以及圆角样式 `var(--border-radius)`。

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
.git clone <repository-url> app2
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
2. 解压到本地目录（如 `c:\web\app2`）
3. 打开命令提示符，进入项目目录：
   ```cmd
   cd c:\web\app2
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

## API 文档
### 根路径 `/api`
- **方法**：GET
- **功能**：返回可用端点信息
- **响应示例**：
```json
{
    "status": "success",
    "message": "App Store API",
    "version": "1.0",
    "endpoints": {
        "/api?action=list": "获取应用列表，支持search、platform、age_rating、tag、page、limit参数。search：搜索关键词；platform：平台；age_rating：年龄分级；tag：标签；page：页码；limit：每页数量",
        "/api?action=app&id=1": "获取指定ID的应用详情，需传入app_id参数。包含应用基础信息、版本、图片、评价和标签信息",
        "/api?action=favorite": "收藏应用（POST方法，需app_id和user_id参数）"
    },
    "example": "GET /api?action=list&search=游戏&limit=10"
}
```

### 应用列表 `/api?action=list`
- **方法**：GET
- **功能**：获取应用列表，支持多条件筛选和分页查询
- **参数**：
  - `search`：搜索关键词，可选
  - `platform`：平台，可选
  - `age_rating`：年龄分级，可选
  - `tag`：标签，可选
  - `page`：页码，可选，默认1
  - `limit`：每页数量，可选，默认10
- **响应示例**：
```json
{
    "data": [
        {
            "id": "1",
            "name": "示例应用",
            "description": "这是一个示例应用",
            "age_rating": "3+",
            "avg_rating": "4.5"
        }
    ],
    "pagination": {
        "total": 100,
        "page": 1,
        "limit": 10,
        "totalPages": 10
    }
}
```

### 应用详情 `/api?action=app&id={id}`
- **方法**：GET
- **功能**：获取指定ID应用的详细信息，包含基础信息、版本、图片、评价和标签信息
- **参数**：
  - `id`：应用ID，必需，数字类型
- **响应示例**：响应包含应用基础信息、版本、图片、评价和标签信息。