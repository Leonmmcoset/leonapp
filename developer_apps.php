<?php
session_start();
require_once 'config.php';

// 验证开发者ID参数
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$developerId = intval($_GET['id']);

// 获取开发者信息
$sqlDeveloper = "SELECT username, social_links FROM developers WHERE id = $developerId";
$resultDeveloper = $conn->query($sqlDeveloper);
$developer = $resultDeveloper->fetch_assoc();

// 确定页面标题和开发者名称
if ($developer) {
    $developerName = htmlspecialchars($developer['username']);
    $pageTitle = $developerName . ' 的应用 - ' . APP_STORE_NAME;
} else {
    // 如果开发者ID为0或不存在，视为管理员
    $developerName = '管理员';
    $pageTitle = '管理员的应用 - ' . APP_STORE_NAME;
}

// 获取该开发者的所有应用
$sqlApps = "SELECT a.*, (SELECT AVG(rating) FROM reviews WHERE app_id = a.id) as avg_rating
           FROM apps a
           WHERE a.developer_id = $developerId";
$resultApps = $conn->query($sqlApps);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="styles.css">
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
</head>
<body class="page-transition">
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg">
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
                    <?php if (isset($_SESSION['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">管理</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <form id="searchForm" class="mb-4">
            <div class="row g-3">
                <div class="col-md-10">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="searchApp" placeholder="搜索应用" value="">
                        <label for="searchApp">搜索应用</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" style="width: calc(3.5rem + calc(var(--bs-border-width) * 2)); height: calc(3.5rem + calc(var(--bs-border-width) * 2))" type="submit" id="searchButton">搜索</button>
                </div>
            </div>
        </form>
        <h1><?php echo $developerName; ?> 的应用</h1>
        <hr>
        <?php if (isset($developer['social_links']) && !empty($developer['social_links'])): ?>
            <div class="mb-4">
                <h5>开发者链接</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    $socialLinks = explode(',', $developer['social_links']);
                    foreach ($socialLinks as $link):
                        $link = trim($link);
                        if (!empty($link)):
                    ?>
                        <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-link me-1"></i>
                            <?php echo parse_url($link, PHP_URL_HOST) ?: $link; ?>
                        </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($resultApps && $resultApps->num_rows > 0): ?>
            <div class="row">
                <?php while ($app = $resultApps->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 blur-bg">
                            <?php
                            // 获取应用的第一张图片
                            $sqlImage = "SELECT image_path FROM app_images WHERE app_id = ". $app['id'] ." LIMIT 1";
                            $resultImage = $conn->query($sqlImage);
                            $image = $resultImage ? $resultImage->fetch_assoc() : null;
                            $imagePath = $image ? $image['image_path'] : 'default-app.png';
                            ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($app['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($app['description'], 0, 100)); ?>...</p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        评分: <?php echo round($app['avg_rating'] ?? 0, 1); ?>/5
                                    </small>
                                </p>
                                <a href="app.php?id=<?php echo $app['id']; ?>" class="btn btn-primary">查看详情</a>
                                <button class="btn btn-outline-secondary mt-2" onclick="toggleFavorite(<?php echo $app['id']; ?>, '<?php echo addslashes(htmlspecialchars($app['name'])); ?>')">收藏</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <?php echo $developerName; ?> 暂无上传应用
            </div>
        <?php endif; ?>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('page-transition');

            // 搜索功能实现
            function performSearch(event) {
                event.preventDefault();
                const searchTerm = document.getElementById('searchApp').value.toLowerCase();
                const appCards = document.querySelectorAll('.col-md-4.mb-4');
                let hasResults = false;

                appCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('.card-text').textContent.toLowerCase();

                    if (title.includes(searchTerm) || description.includes(searchTerm)) {
                        card.style.display = '';
                        hasResults = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // 如果没有搜索结果，显示提示
                if (!hasResults) {
                    Swal.fire({
                        title: '未找到结果',
                        text: '没有找到与 "' + searchTerm + '" 匹配的应用',
                        icon: 'info',
                        confirmButtonText: '确定'
                    });
                }
            }

            // 添加表单提交事件监听
            document.getElementById('searchForm').addEventListener('submit', performSearch);
        });
    </script>
</body>
</html>