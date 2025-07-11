<?php
require_once '../config.php';

// 检查管理员登录状态
session_start();

// 顶栏样式
echo '<style>
.navbar.scrolled {
    background-color: rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>';

// 导航栏
echo '<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">'. APP_STORE_NAME . '</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">首页</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">管理后台</a>
                </li>
            </ul>
        </div>
    </div>
</nav>';

// 为内容添加顶部内边距
echo '<div style="padding-top: 70px;">';
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin'] = [
                'id' => 1, // 配置文件中未定义管理员ID，使用默认值1
                'username' => $username
            ];
            header('Location: index.php');
            exit();
        } else {
            $error = '用户名或密码错误';
        }
    }

    // 显示登录表单
    echo '<!DOCTYPE html>';
    echo '<html lang="zh-CN">';
    echo '<head>';
    echo '    <meta charset="UTF-8">';
    echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '    <title>管理员登录 - '. APP_STORE_NAME . '</title>';
    echo '    <!-- Bootstrap CSS -->';
    echo '    <link rel="stylesheet" href="../css/animations.css">';
    echo '    <link href="../css/bootstrap.min.css" rel="stylesheet">';
    echo '    <!-- 自定义CSS -->';
    echo '    <link rel="stylesheet" href="../styles.css">';
    echo '    <!-- Fluent Design 模糊效果 -->';
    echo '    <style>';
    echo '        .blur-bg {';
    echo '            backdrop-filter: blur(10px);';
    echo '            background-color: rgba(255, 255, 255, 0.5);';
    echo '        }';
    echo '    </style>';
    echo '</head>';
    echo '<body>';
    echo '    <div class="container mt-5">';
    echo '        <div class="row justify-content-center">';
    echo '            <div class="col-md-6">';
    echo '                <div class="card blur-bg">';
    echo '                    <div class="card-header">管理员登录</div>';
    echo '                    <div class="card-body">';
    if (isset($error)) {
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "错误",
                text: "'. addslashes($error) . '",
            });
        </script>';
    }
    echo '                        <form method="post">';
    echo '                            <div class="mb-3">';
    echo '                                <label for="username" class="form-label">用户名</label>';
    echo '                                <input type="text" class="form-control" id="username" name="username" required>';
    echo '                            </div>';
    echo '                            <div class="mb-3">';
    echo '                                <label for="password" class="form-label">密码</label>';
    echo '                                <input type="password" class="form-control" id="password" name="password" required>';
    echo '                            </div>';
    echo '                            <button type="submit" class="btn btn-primary">登录</button>';
    echo '                        </form>';
    echo '                    </div>';
    echo '                </div>';
    echo '            </div>';
    echo '        </div>';
    echo '    </div>';
    echo '    <!-- Bootstrap JS Bundle with Popper -->';
    echo '    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
    echo '</body>';
    echo '</html>';
    exit;
}