<?php
require_once 'config.php';

// 验证App ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=无效的App ID');
    exit;
}
$appId = $_GET['id'];

// 获取App信息
$app = null;
$getAppSql = "SELECT * FROM apps WHERE id = ?";
$stmt = $conn->prepare($getAppSql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: index.php?error=App不存在');
    exit;
}
$app = $result->fetch_assoc();

// 获取所有版本
$versions = [];
$getVersionsSql = "SELECT * FROM app_versions WHERE app_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($getVersionsSql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $versions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app['name']); ?> - 版本历史</title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="styles.css">
    <style>
        .version-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .version-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .download-btn {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .download-btn:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
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
</head>
<body class="page-transition">
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="app.php?id=<?php echo $appId; ?>">返回App详情</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="mb-3 form-floating">
            <input type="text" class="form-control" id="searchVersion" placeholder="搜索版本">
            <label for="searchVersion">搜索版本</label>
        </div>
        <div class="row mb-4">
            <div class="col">
                <h1><?php echo htmlspecialchars($app['name']); ?> - 版本历史</h1>
                <p class="text-muted">查看和下载该应用的所有历史版本</p>
            </div>
        </div>

        <?php if (empty($versions)): ?>
            <div class="alert alert-info">
                暂无版本记录
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($versions as $version): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card version-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">版本 <?php echo htmlspecialchars($version['version']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">发布日期: <?php echo date('Y-m-d', strtotime($version['created_at'])); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($version['changelog'])); ?></p>
                                <button class="btn btn-outline-secondary mt-2" onclick="toggleFavorite(<?php echo $appId; ?>, '<?php echo addslashes(htmlspecialchars($app['name'])); ?>')">收藏</button>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">                                <a href="<?php echo htmlspecialchars($version['file_path']); ?>" class="btn btn-primary" download>下载</a>                                <small class="text-muted">文件大小: <?php echo $fileSize; ?></small>                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 收藏功能逻辑 -->
    <script>
        function toggleFavorite(appId, appName) {
            let favorites = JSON.parse(localStorage.getItem('appFavorites')) || {};
            
            if (favorites[appId]) {
                delete favorites[appId];
                alert('已取消收藏 ' + appName);
            } else {
                favorites[appId] = appName;
                alert('已收藏 ' + appName);
            }
            
            localStorage.setItem('appFavorites', JSON.stringify(favorites));
        }
    </script>
    
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