<?php
// 引入配置文件
require_once '../config.php';

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
        <a class="navbar-brand" href="../index.php">' . APP_STORE_NAME . '</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">首页</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">开发者注册</a>
                </li>
            </ul>
        </div>
    </div>
</nav>';

// 为内容添加顶部内边距
echo '<div style="padding-top: 70px;">';

session_start();
$error = '';

if (isset($_GET['register_success']) && $_GET['register_success'] == 1) {
    $success = '注册成功，请登录';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginId = trim($_POST['login_id']);
    $password = $_POST['password'];

    if (empty($loginId) || empty($password)) {
        $error = '邮箱/用户名和密码不能为空';
    } else {
        // 检查数据库连接是否为 MySQLi 对象
        if (!($conn instanceof mysqli)) {
            log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
            $error = '数据库连接错误，请检查配置';
        } else {
            $stmt = $conn->prepare('SELECT id, username, password FROM developers WHERE email = ? OR username = ?');
            if (!$stmt) {
                log_error('登录查询准备失败: ' . $conn->error, __FILE__, __LINE__);
                $error = '登录时发生错误，请稍后再试';
            } else {
                $stmt->bind_param('ss', $loginId, $loginId);
                if (!$stmt->execute()) {
                    log_error('登录查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
                    $error = '登录时发生错误，请稍后再试';
                } else {
                    $result = $stmt->get_result();
                    $developer = $result->fetch_assoc();
                    if ($developer && password_verify($password, $developer['password'])) {
                        $_SESSION['developer_id'] = $developer['id'];
                        $_SESSION['developer_username'] = $developer['username'];
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = '邮箱/用户名或密码错误';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开发者登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/animations.css">
    <style>
        body {
            background-color: #f4f4f4;
            padding: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container mt-5 col-md-4">
        <h2>开发者登录</h2>
        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="login_id" class="form-label">邮箱/用户名</label>
                <input type="text" id="login_id" name="login_id" class="form-control" placeholder="请输入邮箱或用户名" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">密码</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">登录</button>
        </form>
        <div class="text-center mt-3">
            还没有账号？<a href="register.php" class="text-decoration-none">注册</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>