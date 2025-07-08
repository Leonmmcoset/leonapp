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
define('SMTP_PORT', 25);
define('SMTP_USER', 'leonmmcoset@qq.com');
define('SMTP_PASSWORD', 'CXaWtRdekFAabUWZ');
define('SMTP_FROM', 'leonmmcoset@qq.com');
define('SMTP_FROM_NAME', 'LeonAPP 验证系统');

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
function log_error($message, $file = '', $line = '') {
    $log_entry = date('[Y-m-d H:i:s]') . ' Error: ' . $message;
    if (!empty($file)) {
        $log_entry .= ' in ' . $file;
    }
    if (!empty($line)) {
        $log_entry .= ' on line ' . $line;
    }
    $log_entry .= "\n";
    file_put_contents('d:\\app2\\logs\\error.log', $log_entry, FILE_APPEND);
}
?>