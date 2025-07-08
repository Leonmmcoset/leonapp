<?php
// MySQL 配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'awa');
define('DB_USER', 'root');
define('DB_PASSWORD', 'ewewew');

// App Store 名称
define('APP_STORE_NAME', 'LeonAPP');

// SMTP邮件配置
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 25); // 163邮箱推荐使用587端口
define('SMTP_ENCRYPTION', 'tls'); // 启用TLS加密
define('SMTP_USERNAME', 'leonmmcoset@163.com'); // 使用完整邮箱地址作为用户名
define('SMTP_PASSWORD', 'YFgYVFn6ZaVY9qiV');
define('SMTP_FROM_EMAIL', 'leonmmcoset@163.com');
define('SMTP_FROM_NAME', 'leonmmcoset@163.com');

// 管理员账号
define('ADMIN_USERNAME', 'Admin');
define('ADMIN_PASSWORD', '123456');

// 数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    $error_msg = '数据库连接失败: ' . $conn->connect_error;
    log_error($error_msg, __FILE__, __LINE__);
    die($error_msg);
}
$conn->set_charset('utf8mb4');

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 错误日志记录函数

?>