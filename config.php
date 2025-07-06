<?php
// MySQL 配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'awa');
define('DB_USER', 'root');
define('DB_PASSWORD', 'ewewew');

// App Store 名称
define('APP_STORE_NAME', 'LeonAPP');

// 管理员账号
define('ADMIN_USERNAME', 'Admin');
define('ADMIN_PASSWORD', '123456');

// 数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die('数据库连接失败: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// 设置时区
date_default_timezone_set('Asia/Shanghai');
?>