<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$appId = $_GET['id'];

// 获取App信息
$sqlApp = "SELECT apps.*, apps.developer_id, developers.username as developer_name, AVG(reviews.rating) as avg_rating 
           FROM apps 
           LEFT JOIN developers ON apps.developer_id = developers.id
           LEFT JOIN reviews ON apps.id = reviews.app_id 
           WHERE apps.id = $appId 
           GROUP BY apps.id, apps.developer_id, developers.username"; 
$resultApp = $conn->query($sqlApp);
if (!$resultApp) {
    die("<h1>数据库查询错误</h1><p>错误信息: " . htmlspecialchars($conn->error) . "</p><p>SQL语句: " . htmlspecialchars($sqlApp) . "</p>");
}
$app = $resultApp->fetch_assoc();
$developerId = $app['developer_id'] ?? 0;
$developerName = ($developerId == 0) ? '管理员' : ($app['developer_name'] ?? '未知开发者');

if (!$app) {
    die("<h1>错误：应用不存在</h1><p>找不到ID为 $appId 的应用。请检查ID是否正确。</p>");
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

// 获取评分分布
$sqlRatingDistribution = "SELECT rating, COUNT(*) as count FROM reviews WHERE app_id = $appId GROUP BY rating ORDER BY rating DESC";
$resultRatingDistribution = $conn->query($sqlRatingDistribution);
$ratingDistribution = [];
while ($row = $resultRatingDistribution->fetch_assoc()) {
    $ratingDistribution[$row['rating']] = $row['count'];
}

// 处理评价提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = $_POST['rating'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    $insertSql = "INSERT INTO reviews (app_id, rating) VALUES ($appId, $rating)";    if ($conn->query($insertSql) === TRUE) {        header("Location: app.php?id=$appId");        exit;    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app['name']; ?> - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="css/all.min.css">
    <!-- 本地 Chart.js -->
    <script src="js/charts.js"></script>
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
                <p>适用平台: <?php
                    $platforms = json_decode($app['platforms'], true) ?? [];
                    $platformIcons = [
                        'Windows' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M2.5 1.5v10.5h10.5V1.5H2.5zm0 12v10.5h10.5V13.5H2.5zm11 0v10.5H21.5V13.5h-8zm0-12v10.5H21.5V1.5h-8z" fill="currentColor"/></svg>',
                        'Mac' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M20 18.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-16 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm14.5-17A2.5 2.5 0 0 1 23 4.5v13a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 5 17.5V4.5A2.5 2.5 0 0 1 7.5 2h13zm-13 15A1.5 1.5 0 0 0 6 17.5v9h12v-9a1.5 1.5 0 0 0-1.5-1.5h-9z" fill="currentColor"/></svg>',
                        'Linux' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 0 1-5.66-2.34l1.41-1.41A6 6 0 0 0 12 18a6 6 0 0 0 4.24-1.76l1.41 1.41A8 8 0 0 1 12 20zm0-14a2 2 0 1 1-2 2 2 2 0 0 1 2-2zm0 4a2 2 0 1 1 2 2 2 2 0 0 1-2-2z" fill="currentColor"/></svg>',
                        'Android' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M19.92 13.93a1 1 0 0 0-.84-.53h-.67a1 1 0 0 0-.93.62l-1.26 2.71a14.2 14.2 0 0 1-4.74 0l-1.26-2.71a1 1 0 0 0-.93-.62h-.67a1 1 0 0 0-.84.53l-1.6 3.46a1 1 0 0 0 .84 1.41h1.62a1 1 0 0 0 .93-.62l1.26-2.71a12.24 12.24 0 0 0 3.7 0l1.26 2.71a1 1 0 0 0 .93.62h1.62a1 1 0 0 0 .84-1.41zM7.5 10.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm9 0a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm4.5 4.5v-3a1 1 0 0 0-1-1h-1.5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1H20a1 1 0 0 0 1-1zm-18 0v-3a1 1 0 0 1 1-1H5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z" fill="currentColor"/></svg>',
                        'iOS' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16"><path d="M15.5 2h-7A3.5 3.5 0 0 0 5 5.5v13A3.5 3.5 0 0 0 8.5 22h7A3.5 3.5 0 0 0 19 18.5v-13A3.5 3.5 0 0 0 15.5 2zm-7 1A1.5 1.5 0 0 1 10 4.5v13A1.5 1.5 0 0 1 8.5 19h-1A1.5 1.5 0 0 1 6 17.5v-13A1.5 1.5 0 0 1 7.5 3zm7 1A1.5 1.5 0 0 1 17 4.5v13a1.5 1.5 0 0 1-1.5 1.5h-1A1.5 1.5 0 0 1 13 17.5v-13A1.5 1.5 0 0 1 14.5 3zm-3.5 11a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1zm0-4a1 1 0 0 1 1-1h1a1 1 0 0 1 0 2h-1a1 1 0 0 1-1-1z" fill="currentColor"/></svg>'
                    ];
                    $platformMap = [
                        'android' => 'Android',
                        'ios' => 'iOS',
                        'windows_win7' => 'Windows（Windows 7以上）',
                        'windows_xp' => 'Windows XP',
                        'macos' => 'MacOS',
                        'linux_arch' => 'Linux（适用于Arch Linux）',
                        'linux_ubuntu' => 'Linux（适用于Ubuntu）',
                    ];
                    
                    $platformTexts = [];
                    foreach ($platforms as $platform) {
                        $icon = $platformIcons[$platform] ?? $platformIcons[ucfirst($platform)] ?? '';
                        $readableName = $platformMap[strtolower($platform)] ?? ucfirst($platform);
                        $platformTexts[] = $icon . ' ' . $readableName;
                    }
                    echo implode(', ', $platformTexts);
                ?></p>
                <p>评分: <?php echo round($app['avg_rating'], 1); ?>/5</p>
                <p>开发者: <?php if ($developerId == 0 || empty($developerName)): ?>管理员<?php else: ?><a href="developer_apps.php?id=<?php echo $developerId; ?>"><?php echo htmlspecialchars($developerName); ?></a><?php endif; ?></p>
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
                                <?php
                                $rating = $review['rating'] !== null ? $review['rating'] : 0;
                                echo '<p class="card-text">评分: ';
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($rating)) {
                                        echo '<span class="fas fa-star text-warning"></span>';
                                    } elseif ($i - $rating <= 0.5) {
                                        echo '<span class="fas fa-star-half-alt text-warning"></span>';
                                    } else {
                                        echo '<span class="far fa-star text-warning"></span>';
                                    }
                                }
                                echo '</p>';
                                ?>
                                <p class="card-text"><small class="text-muted">评价时间: <?php echo $review['created_at']; ?></small></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="col-md-6">
                    <h2>评分分布</h2>
                    <canvas id="ratingChart" width="400" height="200"></canvas>
                    <script>
                        const ctx = document.getElementById('ratingChart').getContext('2d');
                        new Chart(ctx).Bar({
                            labels: ['5星', '4星', '3星', '2星', '1星'],
                            datasets: [
                            // {
                            //     label: '评分数量',
                            //     fillColor: 'rgba(75, 192, 192, 0.6)',
                            //     strokeColor: 'rgba(75, 192, 192, 1)',
                            //     highlightFill: 'rgba(75, 192, 192, 0.8)',
                            //     highlightStroke: 'rgba(75, 192, 192, 1)',
                            //     data: [
                            //         <?php echo $ratingDistribution[5] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[4] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[3] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[2] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[1] ?? 0; ?>
                            //     ]
                            // },
                            // {
                            //     label: '评分数量',
                            //     fillColor: 'rgba(153, 102, 255, 0.6)',
                            //     strokeColor: 'rgba(153, 102, 255, 1)',
                            //     highlightFill: 'rgba(153, 102, 255, 0.8)',
                            //     highlightStroke: 'rgba(153, 102, 255, 1)',
                            //     data: [
                            //         <?php echo $ratingDistribution[5] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[4] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[3] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[2] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[1] ?? 0; ?>
                            //     ]
                            // },
                            // {
                            //     label: '评分数量',
                            //     fillColor: 'rgba(255, 206, 86, 0.6)',
                            //     strokeColor: 'rgba(255, 206, 86, 1)',
                            //     highlightFill: 'rgba(255, 206, 86, 0.8)',
                            //     highlightStroke: 'rgba(255, 206, 86, 1)',
                            //     data: [
                            //         <?php echo $ratingDistribution[5] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[4] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[3] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[2] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[1] ?? 0; ?>
                            //     ]
                            // },
                            // {
                            //     label: '评分数量',
                            //     fillColor: 'rgba(255, 99, 132, 0.6)',
                            //     strokeColor: 'rgba(255, 99, 132, 1)',
                            //     highlightFill: 'rgba(255, 99, 132, 0.8)',
                            //     highlightStroke: 'rgba(255, 99, 132, 1)',
                            //     data: [
                            //         <?php echo $ratingDistribution[5] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[4] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[3] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[2] ?? 0; ?>,
                            //         <?php echo $ratingDistribution[1] ?? 0; ?>
                            //     ]
                            // },
                            {
                                label: '评分数量',
                                fillColor: 'rgba(54, 162, 235, 0.6)',
                                strokeColor: 'rgba(54, 162, 235, 1)',
                                highlightFill: 'rgba(54, 162, 235, 0.8)',
                                highlightStroke: 'rgba(54, 162, 235, 1)',
                                data: [
                                    <?php echo $ratingDistribution[5] ?? 0; ?>,
                                    <?php echo $ratingDistribution[4] ?? 0; ?>,
                                    <?php echo $ratingDistribution[3] ?? 0; ?>,
                                    <?php echo $ratingDistribution[2] ?? 0; ?>,
                                    <?php echo $ratingDistribution[1] ?? 0; ?>
                                ]
                            }
                        ]
                        }, {
                            scaleBeginAtZero: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        });
                    </script>
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