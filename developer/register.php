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
                    <a class="nav-link" href="login.php">开发者登录</a>
                </li>
            </ul>
        </div>
    </div>
</nav>';

// 为内容添加顶部内边距
echo '<div style="padding-top: 70px;">';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = '用户名、邮箱和密码不能为空';
    } elseif (empty($_POST['agree'])) {
        $error = '必须同意 APP 审核标准才能注册';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } else {
        // 检查数据库连接是否为 PDO 对象
        if (!($conn instanceof mysqli)) {
              log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
              $error = '数据库连接错误，请检查配置';
          } else {
            try {
                $stmt = $conn->prepare('SELECT id FROM developers WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = '用户名或邮箱已被注册';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt = $conn->prepare('INSERT INTO developers (username, email, password) VALUES (?, ?, ?)');
                    if (!$insertStmt) {
                        log_error('插入准备失败: ' . $conn->error, __FILE__, __LINE__);
                        $error = '系统错误，请稍后再试';
                    } else {
                        $insertStmt->bind_param('sss', $username, $email, $hashedPassword);
                        if (!$insertStmt->execute()) {
                            log_error('插入执行失败: ' . $insertStmt->error, __FILE__, __LINE__);
                            $error = '系统错误，请稍后再试';
                        }
                    }

                    header('Location: login.php?register_success=1');
                    exit;
                }
            } catch (PDOException $e) {
                $error = '注册时发生错误，请稍后再试';
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
    <title>开发者注册</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5 col-md-4">
        <h2>开发者注册</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" required>
                <label for="username">用户名</label>
            </div>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" required>
                <label for="email">邮箱</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" required>
                <label for="password">密码</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
                <label class="form-check-label" for="agree">我已阅读并同意 <a href="/docs/app_review_standards.php" target="_blank">APP 审核标准</a></label>
            </div>
            <button type="submit" class="btn btn-primary w-100">注册</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>