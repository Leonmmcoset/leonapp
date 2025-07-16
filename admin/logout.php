<?php
require_once '../config.php';

session_start();
// 销毁所有会话变量
$_SESSION = [];

// 如果使用了基于cookie的会话，也需要删除cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 使用Sweet Alert弹窗提示并跳转登录页
echo '<script src="/js/sweetalert.js"></script>';
echo '<script>document.addEventListener("DOMContentLoaded", function() {
  Swal.fire({
    title: "登出成功",
    text: "您已安全登出系统",
    icon: "success",
    timer: 1500,
    showConfirmButton: false,
    target: document.body
  }).then(() => { window.location.href = "login.php"; });
});</script>';
exit();