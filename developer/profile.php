<?php
// 引入配置文件
require_once '../config.php';

session_start();

// 检查开发者是否已登录
if (!isset($_SESSION['developer_id'])) {
    header('Location: login.php');
    exit;
}

$developerId = $_SESSION['developer_id'];
$error = '';
$success = '';

// 检查数据库连接是否为 MySQLi 对象
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
} else {
    // 获取开发者信息
    $stmt = $conn->prepare('SELECT username, email, social_links FROM developers WHERE id = ?');
    if (!$stmt) {
        log_error('获取开发者信息查询准备失败: ' . $conn->error, __FILE__, __LINE__);
        $error = '获取开发者信息时发生错误，请稍后再试';
    } else {
        $stmt->bind_param('i', $developerId);
        if (!$stmt->execute()) {
            log_error('获取开发者信息查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
            $error = '获取开发者信息时发生错误，请稍后再试';
        } else {
            $result = $stmt->get_result();
            $developer = $result->fetch_assoc();
        }
    }

    // 处理表单提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newUsername = trim($_POST['username']);
        $newEmail = trim($_POST['email']);
        $newPassword = $_POST['password'];
        $newSocialLinks = trim($_POST['social_links']);

        // 更新用户名和邮箱
        $stmt = $conn->prepare('UPDATE developers SET username = ?, email = ?, social_links = ? WHERE id = ?');
        if (!$stmt) {
            log_error('更新开发者信息查询准备失败: ' . $conn->error, __FILE__, __LINE__);
            $error = '更新信息时发生错误，请稍后再试';
        } else {
            $stmt->bind_param('sssi', $newUsername, $newEmail, $newSocialLinks, $developerId);
            if (!$stmt->execute()) {
                log_error('更新开发者信息查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
                $error = '更新信息时发生错误，请稍后再试';
            } else {
                // 更新密码
                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE developers SET password = ? WHERE id = ?');
                    if (!$stmt) {
                        log_error('更新密码查询准备失败: ' . $conn->error, __FILE__, __LINE__);
                        $error = '更新密码时发生错误，请稍后再试';
                    } else {
                        $stmt->bind_param('si', $hashedPassword, $developerId);
                        if (!$stmt->execute()) {
                            log_error('更新密码查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
                            $error = '更新密码时发生错误，请稍后再试';
                        }
                    }
                }
                if (empty($error)) {
                    $success = '信息更新成功';
                    $_SESSION['developer_username'] = $newUsername;
                    // 重新获取开发者信息
                    $stmt = $conn->prepare('SELECT username, email, social_links FROM developers WHERE id = ?');
                    if ($stmt) {
                        $stmt->bind_param('i', $developerId);
                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            $developer = $result->fetch_assoc();
                        }
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
    <title>开发者信息 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">应用仪表盘</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload_app.php">上传应用</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">退出登录</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">开发者信息</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="profile-container mt-4">
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <h1>开发者信息</h1>
        <form method="post">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($developer['username']); ?>" placeholder="请输入用户名">
            </div>

            <div class="form-group">
                <label for="email">邮箱</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($developer['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">新密码 (留空则不修改)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="form-group">
                <label for="social_links">社交媒体链接 (多个链接用逗号分隔)</label>
                <input type="text" class="form-control" id="social_links" name="social_links" value="<?php echo htmlspecialchars($developer['social_links']); ?>" placeholder="请输入社交媒体链接，多个链接用逗号分隔">
            </div>
            <button type="submit" class="btn btn-primary">保存更改</button>
        </form>
    </div>
</body>
</html>
