<?php
session_start();
require_once 'config.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die('数据库连接失败，请检查配置文件。');
}

$tagId = isset($_GET['tag']) ? intval($_GET['tag']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
$offset = isset($_GET['page']) ? (intval($_GET['page']) - 1) * $limit : 0;

$sql = "SELECT apps.id, apps.name, apps.description, apps.age_rating, AVG(reviews.rating) as avg_rating 
        FROM apps 
        LEFT JOIN reviews ON apps.id = reviews.app_id ";

$conditions = [];
$params = [];
$paramTypes = '';

if ($tagId) {
    $sql .= "JOIN app_tags ON apps.id = app_tags.app_id 
              JOIN tags ON app_tags.tag_id = tags.id ";
    $conditions[] = "app_tags.tag_id = ?";
    $params[] = $tagId;
    $paramTypes .= 'i';
}

if (!empty($search)) {
    $conditions[] = "(apps.name LIKE ? OR apps.description LIKE ?)";
    $searchTerm1 = "%$search%";
    $searchTerm2 = "%$search%";
    $params[] = $searchTerm1;
    $params[] = $searchTerm2;
    $paramTypes .= 'ss';
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY apps.id 
         ORDER BY apps.created_at DESC 
         LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$paramTypes .= 'ii';

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('预处理语句失败: ' . $conn->error);
    }
    $stmt->bind_param($paramTypes, ...$params);
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

$tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>应用标签</title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
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
                    <li class="nav-item">
                        <a class="nav-link active" href="tags.php">标签</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>应用标签</h1>
        <div class="mb-4">
            <?php $tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name"); ?>
            <?php while ($tag = $tagResult->fetch_assoc()): ?>
                <a href="tags.php?tag=<?php echo $tag['id']; ?>" class="btn btn-outline-primary me-2 mb-2">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </a>
            <?php endwhile; ?>
        </div>
        <form method="get" action="tags.php" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="搜索应用..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">搜索</button>
                </div>
            </div>
        </form>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card blur-bg">
                            <img src="images/default.png" class="card-img-top" alt="<?php echo $row['name']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                <p class="card-text"><?php echo substr($row['description'], 0, 100); ?>...</p>
                                <p class="card-text">评分: <?php echo round($row['avg_rating'], 1); ?>/5</p>
                                <a href="app.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">查看详情</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">未找到相关应用。</p>
                </div>
            <?php endif; ?>
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