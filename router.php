<?php
// 路由脚本，用于PHP内置服务器
// 将所有/api开头的请求转发到api.php
if (preg_match('/^\/api/', $_SERVER['REQUEST_URI'])) {
    include 'api.php';
    exit;
}

// 其他请求按正常方式处理
return false;