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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .register-link {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>开发者登录</h2>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="登录">
        </form>
        <div class="register-link">
            还没有账号？<a href="register.php">注册</a>
        </div>
    </div>
</body>
</html>