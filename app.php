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

// 检查应用审核状态
if ($app['status'] != 'approved') {
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: "应用审核中",
            text: "该应用正在审核中，暂时无法访问。",
            icon: "info",
            confirmButtonText: "确定"
        }).then((result) => {
            if (result.isConfirmed) {
                window.history.back();
            }
        });
    });
    </script>';
}

// 处理评价加载请求
if (isset($_GET['action']) && $_GET['action'] === 'load_reviews') {
    header('Content-Type: text/html; charset=UTF-8');
    // 获取评论数据
    $sqlReviews = "SELECT * FROM reviews WHERE app_id = ? ORDER BY created_at DESC, id DESC LIMIT 10 OFFSET ?";
    $stmt = $conn->prepare($sqlReviews);
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $stmt->bind_param("ii", $appId, $offset);
    $stmt->execute();
    $resultReviews = $stmt->get_result();
    
    if (!$resultReviews) {
        die("Error fetching reviews: " . htmlspecialchars($conn->error));
    }
    
    while ($review = $resultReviews->fetch_assoc()) {
?>
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
<?php
    }
    exit;
}

// 获取App版本信息
$sqlVersions = "SELECT * FROM app_versions WHERE app_id = $appId ORDER BY created_at DESC"; 
$resultVersions = $conn->query($sqlVersions);

// 获取App预览图片
$sqlImages = "SELECT * FROM app_images WHERE app_id = $appId"; 
$resultImages = $conn->query($sqlImages);

// 获取评价总数
$sqlReviewCount = "SELECT COUNT(*) as total FROM reviews WHERE app_id = $appId";
$resultReviewCount = $conn->query($sqlReviewCount);
$reviewCount = $resultReviewCount->fetch_assoc()['total'];

// 分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$hasMore = ($page * $limit) < $reviewCount;

// 获取评价信息
$sqlReviews = "SELECT * FROM reviews WHERE app_id = $appId ORDER BY created_at DESC, id DESC LIMIT 10 OFFSET $offset"; 
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
                        'windows' => '<i class="fab fa-windows"></i>',
                        'macos' => '<i class="fab fa-apple"></i>',
                        'linux' => '<i class="fab fa-linux"></i>',
                        'android' => '<i class="fab fa-android"></i>',
                        'ios' => '<i class="fab fa-app-store-ios"></i>'
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
                        $icon = $platformIcons[strtolower($platform)] ?? '';
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
                    <h2>提交评价</h2>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="rating" class="form-label">评分</label>
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
                    <h2>评价</h2>
                    <div id="reviews-container">
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
                    <?php if ($hasMore): ?>
                        <button id="load-more" class="btn btn-secondary" data-page="<?php echo $page + 1; ?>">加载更多</button>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h2>评分分布</h2>
                    <div id="ratingChartSkeleton" class="skeleton-chart"></div>
                    <canvas id="ratingChart" width="400" height="200"></canvas>
                    <script>
                        // 加载更多评价功能
                        document.addEventListener('DOMContentLoaded', function() {
                            const loadMoreBtn = document.getElementById('load-more');
                            if (loadMoreBtn) {
                                loadMoreBtn.addEventListener('click', function() {
                                    const button = this;
                                    const page = parseInt(button.getAttribute('data-page'));
            const offset = (page - 1) * 10;
            const appId = <?php echo $appId; ?>;
                                    
                                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 加载中...';
                                    button.disabled = true;
                                    
                                    fetch(`app.php?id=${appId}&offset=${offset}&action=load_reviews`)
                                        .then(response => response.text())
                                        .then(html => {
                                            if (html.trim() === '') {
                                                button.style.display = 'none';
                                                return;
                                            }
                                            document.getElementById('reviews-container').insertAdjacentHTML('beforeend', html);
                                            button.innerHTML = '加载更多';
                                              button.disabled = false;
                                              button.setAttribute('data-page', parseInt(page) + 1);
                                        })
                                        .catch(error => {
                                            console.error('加载评价失败:', error);
                                            button.innerHTML = '加载更多';
                                            button.disabled = false;
                                        });
                                });
                            }
                        });

                        // 评分图表
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
                        document.getElementById('ratingChartSkeleton').style.display = 'none';
                    </script>
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