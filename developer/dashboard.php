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
$developerUsername = $_SESSION['developer_username'];

// 检查数据库连接是否为 MySQLi 对象
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
} else {
    // 获取开发者的应用列表
    $apps = [];
    $stmt = $conn->prepare('SELECT id, name, status, rejection_reason FROM apps WHERE developer_id = ?');
    if (!$stmt) {
        log_error('获取应用列表查询准备失败: ' . $conn->error, __FILE__, __LINE__);
        $error = '获取应用列表时发生错误，请稍后再试';
    } else {
        $stmt->bind_param('i', $developerId);
        if (!$stmt->execute()) {
            log_error('获取应用列表查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
            $error = '获取应用列表时发生错误，请稍后再试';
        } else {
            $result = $stmt->get_result();
            $apps = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>开发者仪表盘 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
        .app-card {
            margin-bottom: 1rem;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        .app-list {
            margin-top: 20px;
        }
        .app-item {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .status-pending {
            color: orange;
        }
        .status-approved {
            color: green;
        }
        .status-rejected {
            color: red;
        }
        .action-buttons {
            margin-top: 10px;
        }
        .action-buttons a {
            display: inline-block;
            padding: 5px 10px;
            background-color: #007BFF;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            margin-right: 10px;
        }
        .action-buttons a:hover {
            background-color: #0056b3;
        }
        .add-app {
            margin-bottom: 20px;
        }
        .add-app a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
        }
        .add-app a:hover {
            background-color: #218838;
        }
        .logout {
            text-align: right;
        }
        .logout a {
            color: #dc3545;
            text-decoration: none;
        }
        .logout a:hover {
            text-decoration: underline;
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
                        <a class="nav-link active" aria-current="page" href="dashboard.php">应用仪表盘</a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" href="upload_app.php">上传应用</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">更改信息</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">退出登录</a>
                </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container mt-4">
        <?php 
        $rejectedApps = array_filter($apps, function($app) {
            return $app['status'] === 'rejected';
        });
        if (!empty($rejectedApps)): 
        ?>
            <div class="alert alert-danger">
                <strong>提醒:</strong> 您有 <?php echo count($rejectedApps); ?> 个应用未通过审核，请查看详情。
            </div>
        <?php endif; ?>

        <h1>欢迎，<?php echo htmlspecialchars($developerUsername); ?>！</h1>
        <div class="add-app">
            <a href="upload_app.php">上传新应用</a>
        </div>
        <?php if (isset($error)): ?>
            <div style="color: red;"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="app-list">
            <h2>我的应用</h2>
            <?php if (empty($apps)): ?>
                <p>您还没有上传任何应用。</p>
            <?php else: ?>
                <?php foreach ($apps as $app): ?>
                    <div class="card app-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($app['name']); ?></h5>
                            <p class="card-text">
                                状态: 
                                <?php if ($app['status'] === 'approved'): ?>
                                    <span class="badge bg-success">已通过</span>
                                <?php elseif ($app['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">未通过</span>
                                    <div class="alert alert-warning mt-2">
                                        拒绝原因: <?php echo htmlspecialchars($app['rejection_reason']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-warning">待审核</span>
                                <?php endif; ?>
                            </p>
                            <div class="action-buttons">
                                <a href="edit_app.php?id=<?php echo $app['id']; ?>" class="btn btn-primary">编辑</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>