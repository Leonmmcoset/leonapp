<?php
// 引入配置文件
require_once '../config.php';

session_start();
$error = '';

if (isset($_GET['register_success']) && $_GET['register_success'] == 1) {
    $success = '注册成功，请登录';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = '邮箱和密码不能为空';
    } else {
        // 检查数据库连接是否为 MySQLi 对象
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
} else {
    $stmt = $conn->prepare('SELECT id, username, password FROM developers WHERE email = ?');
    if (!$stmt) {
        log_error('登录查询准备失败: ' . $conn->error, __FILE__, __LINE__);
        $error = '登录时发生错误，请稍后再试';
    } else {
        $stmt->bind_param('s', $email);
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
                $error = '邮箱或密码错误';
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
                <label for="email" class="form-label">邮箱</label>
                <input type="email" id="email" name="email" class="form-control" required>
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