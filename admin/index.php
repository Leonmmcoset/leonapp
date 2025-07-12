<?php
require_once '../config.php';

session_start();

if (!isset($conn) || !$conn instanceof mysqli) {
    die('数据库连接失败，请检查配置文件。');
}

// 获取最新公告
$sql = 'SELECT title, content FROM announcements ORDER BY created_at DESC LIMIT 1';
$result = $conn->query($sql);
$announcement = $result ? $result->fetch_assoc() : null;
// 检查管理员登录状态
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// 处理退出登录
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    header('Location: login.php');
    exit;
}

// 获取App列表
$sqlApps = "SELECT * FROM apps WHERE status = 'approved' ORDER BY created_at DESC";
$resultApps = $conn->query($sqlApps);

if (!$resultApps) {
    error_log("Database query failed: " . $conn->error);
    echo '<script>Swal.fire("错误", "获取App列表失败，请联系管理员。", "error");</script>';
} else {
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
    <title>App管理 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="/favicon.ico">
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
            <a href="../index.php"><img src="/favicon.ico" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
            <a class="navbar-brand" href="../index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">App列表</a>
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
        <script>
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                icon: "success",
                title: "成功",
                text: "<?php echo addslashes($_GET['success']); ?>",
            });
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            Swal.fire({
                icon: "error",
                title: "错误",
                text: "<?php echo addslashes($_GET['error']); ?>",
            });
        <?php endif; ?>
        </script>
        <h2>App列表</h2>
        <div class="mb-3">
            <a href="manage_tags.php" class="btn btn-info">标签管理</a>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>年龄分级</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($app = $resultApps->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $app['id']; ?></td>
                        <td><?php echo htmlspecialchars($app['name']); ?></td>
                        <td><?php echo $app['age_rating']; ?></td>
                        <td><?php echo $app['created_at']; ?></td>
                        <td>
                            <a href="editapp.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                            <a href="manage_versions.php?app_id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-secondary">版本管理</a>
                            <a href="deleteapp.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); Swal.fire({
                                title: '确定要删除吗?',
                                text: '删除后将无法恢复!',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: '确定删除'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = this.href;
                                }
                            });">删除</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="/js/bootstrap.bundle.js"></script>
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
<?php 
}
?>