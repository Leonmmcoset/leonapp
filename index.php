<?php
session_start();
require_once 'config.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die('数据库连接失败，请检查配置文件。');
} ?>
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
    <title><?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/animations.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                </ul>
            </div>
        </div>
    </nav>

    <?php
    // 获取最新公告
    $announcementQuery = "SELECT title, content FROM announcements ORDER BY created_at DESC LIMIT 1";
    $announcementResult = $conn->query($announcementQuery);
    $announcement = $announcementResult && $announcementResult->num_rows > 0 ? $announcementResult->fetch_assoc() : null;
    ?>
    <?php if ($announcement): ?>
        <div class="container mt-3">
            <div class="alert alert-info blur-bg">
                <h4 class="alert-heading"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mt-4">
        <form method="get" action="index.php" class="mb-4" onsubmit="return validateSearch();">
            <!-- Bootstrap JS Bundle with Popper -->
            <script src="js/bootstrap.bundle.min.js"></script>
            <script>
                function validateSearch() {
                    const searchInput = document.querySelector('input[name="search"]');
                    if (searchInput.value.trim() === '') {
                        Swal.fire({
                            title: '提示',
                            text: '请填写搜索名称后再进行搜索！',
                            icon: 'warning',
                            confirmButtonText: '确定'
                        });
                        return false;
                    }
                    return true;
                }
            </script>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" name="search" class="form-control" id="searchInput" placeholder="搜索应用..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <label for="searchInput">搜索应用</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select name="tag" class="form-select" id="tagSelect">
                            <option value="">所有标签</option>
                            <?php
                            $tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name");
                            $selectedTag = isset($_GET['tag']) ? $_GET['tag'] : '';
                            while ($tag = $tagResult->fetch_assoc()):
                                $selected = ($tag['id'] == $selectedTag) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $tag['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <label for="tagSelect">选择标签</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" style="width: calc(3.5rem + calc(var(--bs-border-width) * 2)); height: calc(3.5rem + calc(var(--bs-border-width) * 2))" type="submit">搜索</button>
                </div>
            </div>
        </form>
        <?php if (isset($_SESSION['user_id'])): ?>
            <h1>为你推荐</h1>
            <div class="row">
                <?php
                // 获取用户下载过的应用标签
                $userId = $_SESSION['user_id'];
                $tagSql = "SELECT DISTINCT t.id FROM tags t
                       JOIN app_tags at ON t.id = at.tag_id
                       JOIN app_versions av ON at.app_id = av.app_id
                       JOIN download_history dh ON av.id = dh.version_id
                       WHERE dh.user_id = ?";
                $tagStmt = $conn->prepare($tagSql);
                $tagStmt->bind_param('i', $userId);
                $tagStmt->execute();
                $tagResult = $tagStmt->get_result();
                $tagIds = [];
                while ($tag = $tagResult->fetch_assoc()) {
                    $tagIds[] = $tag['id'];
                }
                $tagStmt->close();

                // 获取用户已下载的应用
                $downloadedSql = "SELECT DISTINCT a.id FROM apps a
                              JOIN app_versions av ON a.id = av.app_id
                              JOIN download_history dh ON av.id = dh.version_id
                              WHERE dh.user_id = ?";
                $downloadedStmt = $conn->prepare($downloadedSql);
                $downloadedStmt->bind_param('i', $userId);
                $downloadedStmt->execute();
                $downloadedResult = $downloadedStmt->get_result();
                $downloadedIds = [];
                while ($app = $downloadedResult->fetch_assoc()) {
                    $downloadedIds[] = $app['id'];
                }
                $downloadedStmt->close();

                // 基于标签推荐应用
                if (!empty($tagIds)) {
                    $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                    $recommendSql = "SELECT a.id, a.name, a.description, a.age_rating, a.platforms, AVG(r.rating) as avg_rating
                                FROM apps a
                                LEFT JOIN reviews r ON a.id = r.app_id
                                JOIN app_tags at ON a.id = at.app_id
                                WHERE at.tag_id IN ($placeholders)
                                AND a.id NOT IN (" . (!empty($downloadedIds) ? implode(',', $downloadedIds) : '0') . ")
                                AND a.status = 'approved'
                                GROUP BY a.id
                                ORDER BY COUNT(at.tag_id) DESC
                                LIMIT 12";
                    $recommendStmt = $conn->prepare($recommendSql);
                    $types = str_repeat('i', count($tagIds));
                    $recommendStmt->bind_param($types, ...$tagIds);
                    $recommendStmt->execute();
                    $recommendResult = $recommendStmt->get_result();
                } else {
                    // 如果没有标签数据，显示热门应用
                    $recommendSql = "SELECT a.id, a.name, a.description, a.age_rating, AVG(r.rating) as avg_rating, SUM(av.download_count) as total_downloads
                                FROM apps a
                                LEFT JOIN reviews r ON a.id = r.app_id
                                LEFT JOIN app_versions av ON a.id = av.app_id
                                WHERE a.status = 'approved'
                                GROUP BY a.id
                                ORDER BY total_downloads DESC
                                LIMIT 12";
                    $recommendResult = $conn->query($recommendSql);
                }

                if ($recommendResult && $recommendResult->num_rows > 0) {
                    while ($row = $recommendResult->fetch_assoc()) {
                        echo '<div class="col-md-3 mb-4">';
                        echo '<div class="card blur-bg">';

                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>';
                        echo '<p class="card-text">' . substr(htmlspecialchars($row['description']), 0, 100) . '...</p>';
                        // 获取应用标签
                        $tagSql = "SELECT t.name FROM tags t JOIN app_tags at ON t.id = at.tag_id WHERE at.app_id = ?";
                        $tagStmt = $conn->prepare($tagSql);
                        $tagStmt->bind_param('i', $row['id']);
                        $tagStmt->execute();
                        $tagResult = $tagStmt->get_result();
                        $tags = [];
                        while ($tag = $tagResult->fetch_assoc()) {
                            $tags[] = htmlspecialchars($tag['name']);
                        }
                        $tagStmt->close();

                        // 获取应用适用平台
                        $platforms = json_decode($row['platforms'], true);
                        if (!is_array($platforms)) $platforms = [];
                        echo '<p class="card-text">标签: ' . implode(', ', $tags) . '</p>';
                        echo '<p class="card-text">平台: ' . implode(', ', $platforms) . '</p>';
                        echo '<p class="card-text">评分: ' . round($row['avg_rating'] ?? 0, 1) . '/5</p>';
                        echo '<a href="app.php?id=' . $row['id'] . '" class="btn btn-primary">查看详情</a>';
                        echo '<button class="btn btn-outline-secondary mt-2" onclick="toggleFavorite(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\')">收藏</button>';
                        echo '</div></div></div>';
                    }
                } else {
                    echo '<div class="col-12"><p class="text-center">暂无推荐内容</p></div>';
                }
                if (isset($recommendStmt)) $recommendStmt->close();
                ?>
            </div>
        <?php endif; ?>

        <h1>应用列表</h1>
        <div class="row" id="app-list">
            <!-- 这里将通过PHP动态加载应用列表 -->
            <?php
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sql = "SELECT apps.id, apps.name, apps.description, apps.age_rating, apps.platforms, AVG(reviews.rating) as avg_rating 
                    FROM apps 
                    LEFT JOIN reviews ON apps.id = reviews.app_id ";

            $conditions = [];
            $params = [];
            $paramTypes = '';

            // 标签筛选
            if (!empty($_GET['tag'])) {
                $sql .= "JOIN app_tags ON apps.id = app_tags.app_id 
                            JOIN tags ON app_tags.tag_id = tags.id ";
                $conditions[] = "app_tags.tag_id = ?";
                $tagId = $_GET['tag'];
                $params[] = &$tagId;
                $paramTypes .= 'i';
            }

            // 平台筛选
            if (!empty($_GET['platform'])) {
                // Removed platform condition - column does not exist
                $platform = $_GET['platform'];
                $params[] = &$platform;
                $paramTypes .= 's';
            }

            // 年龄分级筛选
            if (!empty($_GET['age_rating'])) {
                $conditions[] = "apps.age_rating = ?";
                $ageRating = $_GET['age_rating'];
                $params[] = &$ageRating;
                $paramTypes .= 's';
            }

            // 搜索关键词筛选
            if (!empty($search)) {
                $conditions[] = "(apps.name LIKE ? OR apps.description LIKE ?)";
                $searchTerm1 = "%$search%";
                $searchTerm2 = "%$search%";
                $params[] = &$searchTerm1;
                $params[] = &$searchTerm2;
                $paramTypes .= 'ss';
            }

            // 只显示已审核通过的应用
            $conditions[] = "apps.status = 'approved'";

            // 添加条件
            if (!empty($conditions)) {
                $sql .= "WHERE " . implode(" AND ", $conditions);
            }

            $sql .= "GROUP BY apps.id 
                     ORDER BY apps.created_at DESC";

            // 执行查询
            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    die('预处理语句失败: ' . $conn->error);
                }
                call_user_func_array([$stmt, 'bind_param'], array_merge([$paramTypes], $params));
                if (!$stmt->execute()) {
                    die('执行语句失败: ' . $stmt->error);
                }
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
                if (!$result) {
                    die('查询失败: ' . $conn->error);
                }
            }

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-3 mb-4 lazy-load" data-src="app.php?id=' . $row['id'] . '">';
                    echo '<div class="card blur-bg">';

                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . $row['name'] . '</h5>';
                    echo '<p class="card-text">' . substr($row['description'], 0, 100) . '...</p>';

                    // 获取应用标签
                    $tagSql = "SELECT t.name FROM tags t JOIN app_tags at ON t.id = at.tag_id WHERE at.app_id = ?";
                    $tagStmt = $conn->prepare($tagSql);
                    $tagStmt->bind_param('i', $row['id']);
                    $tagStmt->execute();
                    $tagResult = $tagStmt->get_result();
                    $tags = [];
                    while ($tag = $tagResult->fetch_assoc()) {
                        $tags[] = htmlspecialchars($tag['name']);
                    }
                    $tagStmt->close();

                    // 获取应用适用平台
                    $platforms = json_decode($row['platforms'], true);
                    if (!is_array($platforms)) $platforms = [];
                    echo '<p class="card-text">标签: ' . implode(', ', $tags) . '</p>';
                    echo '<p class="card-text">平台: ' . implode(', ', $platforms) . '</p>';
                    echo '<p class="card-text">评分: ' . round($row['avg_rating'], 1) . '/5</p>';
                    echo '<a href="app.php?id=' . $row['id'] . '" class="btn btn-primary">查看详情</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const lazyLoadItems = document.querySelectorAll(".lazy-load");

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                // 这里可以添加加载动画或其他操作
                                observer.unobserve(entry.target);
                            }
                        });
                    });

                    lazyLoadItems.forEach((item) => {
                        observer.observe(item);
                    });
                });
            </script>
            <?php
            ?>

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('page-transition');
        });
    </script>
</body>

</html>