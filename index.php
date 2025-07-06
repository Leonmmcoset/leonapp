<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_STORE_NAME; ?></title>
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
            <a class="navbar-brand" href="#"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">首页</a>
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
        <form method="get" action="index.php" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="搜索应用..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button class="btn btn-primary" type="submit">搜索</button>
            </div>
        </form>
        <h1>最新应用</h1>
        <div class="row">
            <!-- 这里将通过PHP动态加载应用列表 -->
            <?php
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sql = "SELECT apps.id, apps.name, apps.description, apps.age_rating, AVG(reviews.rating) as avg_rating 
                    FROM apps 
                    LEFT JOIN reviews ON apps.id = reviews.app_id ";
            
            if (!empty($search)) {
                $sql .= "WHERE apps.name LIKE ? OR apps.description LIKE ? ";
            }
            
            $sql .= "GROUP BY apps.id 
                     ORDER BY apps.created_at DESC 
                     LIMIT 12";
                     
            if (!empty($search)) {
                $stmt = $conn->prepare($sql);
                $searchTerm = "%$search%";
                $stmt->bind_param("ss", $searchTerm, $searchTerm);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-3 mb-4">';
                    echo '<div class="card blur-bg">';
                    echo '<img src="images/default.png" class="card-img-top" alt="'. $row['name'] . '">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">'. $row['name'] . '</h5>';
                    echo '<p class="card-text">'. substr($row['description'], 0, 100) . '...</p>';
                    echo '<p class="card-text">评分: '. round($row['avg_rating'], 1) . '/5</p>';
                    echo '<a href="app.php?id='. $row['id'] . '" class="btn btn-primary">查看详情</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
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
</body>
</html>