<?php
// 引入配置文件
require_once '../config.php';

session_start();

$error = '';
$success = '';

// 检查是否提供了验证令牌
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = '无效的验证链接';
} else {
    $token = trim($_GET['token']);

    // 验证数据库连接
    if (!($conn instanceof mysqli)) {
        log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
        $error = '数据库连接错误，请稍后再试';
    } else {
        try {
            // 查询具有该令牌的未验证开发者
            $stmt = $conn->prepare('SELECT id FROM developers WHERE verification_token = ? AND is_verified = FALSE');
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                // 更新验证状态
                $updateStmt = $conn->prepare('UPDATE developers SET is_verified = TRUE, verified_at = NOW(), verification_token = NULL WHERE verification_token = ?');
                $updateStmt->bind_param('s', $token);
                $updateStmt->execute();
                $updateStmt->close();

                $success = '邮箱验证成功！现在您可以登录并创建应用了。';
            } else {
                $error = '验证链接无效或已过期';
            }
            $stmt->close();
        } catch (Exception $e) {
            log_error('邮箱验证失败: ' . $e->getMessage(), __FILE__, __LINE__);
            $error = '验证过程中发生错误，请稍后再试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮箱验证 - <?= APP_STORE_NAME ?></title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; padding: 70px 0; }
        .container { max-width: 500px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?= APP_STORE_NAME ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../index.php">首页</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">开发者登录</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4">邮箱验证</h2>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert"><?= $success ?></div>
            <a href="login.php" class="btn btn-primary">前往登录</a>
        <?php else: ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
            <a href="register.php" class="btn btn-secondary">重新注册</a>
        <?php endif; ?>
    </div>

    <script src="/js/bootstrap.bundle.min.js"></script>
</body>
</html>