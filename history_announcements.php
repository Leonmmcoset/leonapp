<?php
// 包含配置文件
require_once 'config.php';

// 使用config.php中的数据库连接
if (!$conn) {
    echo '<script>swal("错误", "数据库连接失败", "error");</script>';
    exit;
}

// 查询所有公告，按发布时间倒序
$sql = "SELECT id, title, content, created_at FROM announcements ORDER BY created_at DESC";
$result = $conn->query($sql);

?>
<?php
session_start();
require_once 'config.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die('数据库连接失败，请检查配置文件。');
}?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>历史公告 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/css/all.min.css">
    <script src="/js/sweetalert.js"></script>
    <!-- Fluent Design 模糊效果 -->
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
        .page-transition {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <style>
        .announcement-item {
            margin-bottom: 2rem;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 0.3rem;
        }
        .announcement-item h3 {
            margin-bottom: 0.5rem;
            color: #343a40;
        }
        .date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .content {
            color: #495057;
            line-height: 1.6;
        }
    </style>
</head>
<body class="page-transition">
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg">
        <div class="container">
            <a href="index.php"><img src="/favicon.jpeg" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
            <a class="navbar-brand" href="#"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <?php if (isset($_SESSION['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">管理</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="tags.php">标签</a>
                    </li>
                    <?php if (isset($_SESSION['developer_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="developer/dashboard.php">进入面板</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="developer/register.php">开发者注册</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="developer/login.php">开发者登录</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="http://leonmmcoset.jjmm.ink:3232/app.php?id=36">下载LeonAPP手机版</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="http://leonmmcoset.jjmm.ink:3232/app.php?id=41">下载LeonAPP电脑版</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="thanks.php">鸣谢</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <h1 class="mb-4">历史公告</h1>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="announcements-list">
                <?php while($announcement = $result->fetch_assoc()): ?>
                    <div class="announcement-item">
                        <h3><?php echo htmlspecialchars($announcement['title'], ENT_QUOTES); ?></h3>
                        <p class="date"><?php echo htmlspecialchars($announcement['created_at'], ENT_QUOTES); ?></p>
                        <div class="content"><?php echo nl2br(htmlspecialchars($announcement['content'], ENT_QUOTES)); ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                暂无历史公告记录。
            </div>
        <?php endif; ?>
    </div>

    <script src="js/bootstrap.bundle.js"></script>
</body>
</html>