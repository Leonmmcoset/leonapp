<?php
require_once '../config.php';

// 检查管理员登录状态
session_start();
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin'] = true;
            header('Location: index.php');
            exit;
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
    echo '    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
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
        echo '                        <div class="alert alert-danger">'. $error . '</div>';
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