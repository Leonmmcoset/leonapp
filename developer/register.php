<?php
// 引入配置文件
require_once '../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = '用户名、邮箱和密码不能为空';
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
        input[type="text"],
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
    </style>
</head>
<body>
    <div class="container">
        <h2>开发者注册</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">邮箱</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="注册">
        </form>
    </div>
</body>
</html>