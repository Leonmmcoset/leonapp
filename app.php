<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$appId = $_GET['id'];

// 获取App信息
$sqlApp = "SELECT apps.*, AVG(reviews.rating) as avg_rating 
           FROM apps 
           LEFT JOIN reviews ON apps.id = reviews.app_id 
           WHERE apps.id = $appId 
           GROUP BY apps.id"; 
$resultApp = $conn->query($sqlApp);
$app = $resultApp->fetch_assoc();

if (!$app) {
    header('Location: index.php');
    exit;
}

// 获取App版本信息
$sqlVersions = "SELECT * FROM app_versions WHERE app_id = $appId ORDER BY created_at DESC"; 
$resultVersions = $conn->query($sqlVersions);

// 获取App预览图片
$sqlImages = "SELECT * FROM app_images WHERE app_id = $appId"; 
$resultImages = $conn->query($sqlImages);

// 获取评价信息
$sqlReviews = "SELECT * FROM reviews WHERE app_id = $appId ORDER BY created_at DESC"; 
$resultReviews = $conn->query($sqlReviews);

// 处理评价提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = $_POST['rating'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    $insertSql = "INSERT INTO reviews (app_id, ip_address, rating) VALUES ($appId, '$ipAddress', $rating)";
    if ($conn->query($insertSql) === TRUE) {
        header('Location: app.php?id=$appId');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app['name']; ?> - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="styles.css">
    <!-- Fluent Design 模糊效果 -->
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
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
        <div class="row">
            <div class="col-md-6">
                <h1><?php echo $app['name']; ?></h1>
                <p class="lead"><?php echo $app['description']; ?></p>
                <p>年龄分级: <?php echo $app['age_rating']; ?></p>
    <?php if (!empty($app['age_rating_description'])): ?>
    <div class="age-rating-description">
        <h4>年龄分级说明</h4>
        <p><?php echo nl2br(htmlspecialchars($app['age_rating_description'])); ?></p>
    </div>
    <?php endif; ?>
                <p>适用平台: <?php echo implode(', ', json_decode($app['platforms'], true) ?? []); ?></p>
                <p>评分: <?php echo round($app['avg_rating'], 1); ?>/5</p>
            </div>
            <div class="col-md-6">
                <div id="imageCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php 
                        $first = true;
                        while ($image = $resultImages->fetch_assoc()) {
                            $active = $first ? 'active' : '';
                            echo '<div class="carousel-item '. $active . '">';
                            echo '<img src="'. $image['image_path'] . '" class="d-block w-100" alt="App Image">';
                            echo '</div>';
                            $first = false;
                        }
                        ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h2>版本历史</h2>
                <?php while ($version = $resultVersions->fetch_assoc()): ?>
                    <div class="card mb-3 blur-bg">
                        <div class="card-body">
                            <h5 class="card-title">版本 <?php echo $version['version']; ?></h5>
                            <p class="card-text"><?php echo $version['changelog']; ?></p>
<a href="<?php echo htmlspecialchars($version['file_path']); ?>" class="btn btn-primary btn-lg" download>立即下载</a>
                            <a href="version_list.php?id=<?php echo $app['id']; ?>" class="btn btn-outline-secondary">查看版本历史</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <h2>评价</h2>
                <?php while ($review = $resultReviews->fetch_assoc()): ?>
                    <div class="card mb-3 blur-bg">
                        <div class="card-body">
                            <p class="card-text">评分: <?php echo $review['rating']; ?>/5</p>
                            <p class="card-text"><small class="text-muted">评价时间: <?php echo $review['created_at']; ?></small></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="col-md-6">
                <h2>提交评价</h2>
                <form method="post">
                    <div class="mb-3">
                        <label for="rating" class="form-label">评分 (1-5星)</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option value="1">1星</option>
                            <option value="2">2星</option>
                            <option value="3">3星</option>
                            <option value="4">4星</option>
                            <option value="5">5星</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">提交评价</button>
                </form>
            </div>
        </div>
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
</body>
</html>