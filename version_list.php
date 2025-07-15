<?php
require_once 'config.php';

// 格式化文件大小函数
function formatFileSize($bytes) {
    if ($bytes === false) return '未知';
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 0) {
        return $bytes . ' B';
    } else {
        return '0 B';
    }
}


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
            <a href="index.php"><img src="/favicon.jpeg" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
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
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <?php
// 处理绝对路径和相对路径
// 统一路径格式为正斜杠并处理转义问题
// 使用系统目录分隔符标准化路径
// 修剪路径并标准化分隔符
$filePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($version['file_path']));
// 增强正则表达式以识别各种Windows绝对路径格式
// 识别带盘符和不带盘符的绝对路径格式
// 仅识别带盘符的Windows绝对路径格式
// 使用更可靠的绝对路径检测方法
// 仅将带盘符的路径视为绝对路径
// 识别带盘符或以目录分隔符开头的绝对路径
// 修正绝对路径识别，仅匹配带盘符和分隔符的格式
if (!preg_match('/^[A-Za-z]:[\\/]/i', $filePath) && strpos($filePath, DIRECTORY_SEPARATOR) !== 0) {
    // 非绝对路径则拼接文档根目录
    // 使用当前文件位置计算应用根目录，增强错误处理
$currentDir = realpath(dirname(__FILE__));
error_log('Version ID: ' . $version['id'] . ' - Current directory: ' . ($currentDir ?: 'Invalid path'));

// 根据实际应用结构调整目录层级 (向上两级)
$basePath = $currentDir ? realpath($currentDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') : false;
error_log('Version ID: ' . $version['id'] . ' - Calculated base path: ' . ($basePath ?: 'Failed to resolve'));

// 确保基础路径有效
if (!$basePath) {
    // 使用文档根目录作为备选
    $basePath = $_SERVER['DOCUMENT_ROOT'] ?? '';
    error_log('Version ID: ' . $version['id'] . ' - Fallback to DOCUMENT_ROOT: ' . $basePath);
}
error_log('Version ID: ' . $version['id'] . ' - Base path: ' . $basePath);
$filePath = $basePath . DIRECTORY_SEPARATOR . ltrim($filePath, DIRECTORY_SEPARATOR);
// 添加调试信息以跟踪路径问题
// 增强调试信息以全面跟踪路径问题
error_log('Version ID: ' . $version['id'] . ' - Original file path: ' . $version['file_path']);
error_log('Version ID: ' . $version['id'] . ' - Normalized path: ' . $filePath);
error_log('Version ID: ' . $version['id'] . ' - File exists: ' . (file_exists($filePath) ? 'Yes' : 'No'));
error_log('Version ID: ' . $version['id'] . ' - Real path: ' . realpath($filePath));
}
// 解析实际路径并处理可能的解析错误
$realPath = realpath($filePath);
$fileSize = $realPath !== false ? filesize($realPath) : false;
$sizeText = formatFileSize($fileSize);
?>
<a href="<?php echo htmlspecialchars($version['file_path']); ?>" class="btn btn-primary" download>下载（大小：<?php echo $sizeText; ?>)</a>
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
    <script src="/js/bootstrap.bundle.min.js"></script>
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