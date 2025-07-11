<?php
require_once '../config.php';
session_start();
// 检查管理员登录状态
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['id'])) {
    header('Location: login.php');
    exit;
}

// 处理公告发布
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $admin_id = $_SESSION['admin']['id'];

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare('INSERT INTO announcements (title, content, admin_id) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $title, $content, $admin_id);
        if ($stmt->execute()) {
            header('Location: announcements.php?success=公告发布成功');
            exit;
        } else {
            $error = '公告发布失败: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $error = '标题和内容不能为空';
    }
}

// 获取公告列表
$sql = 'SELECT a.*, ad.username FROM announcements a JOIN admins ad ON a.admin_id = ad.id ORDER BY a.created_at DESC';
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<style>
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
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告管理 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/animations.css">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <!-- Fluent Design 模糊效果 -->
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="page-transition">
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
                        <a class="nav-link" href="index.php">App列表</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="addapp.php">添加App</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="review_apps.php">审核APP</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_developers.php">管理开发者</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system_info.php">系统信息</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">公告管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true">退出登录</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                icon: "success",
                title: "成功",
                text: "<?php echo addslashes($_GET['success']); ?>",
            });
        <?php endif; ?>
        <?php if (isset($error)): ?>
            Swal.fire({
                icon: "error",
                title: "错误",
                text: "<?php echo addslashes($error); ?>",
            });
        <?php endif; ?>
        </script>

        <h2>发布公告</h2>
        <form method="post">
            <div class="mb-3">
                <label for="title" class="form-label">标题</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">内容</label>
                <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">发布</button>
        </form>

        <h2 class="mt-4">公告列表</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>标题</th>
                    <th>发布者</th>
                    <th>发布时间</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 导航栏滚动效果
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('page-transition');
        });
    </script>
</body>
</html>