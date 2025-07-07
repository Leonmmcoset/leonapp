<?php
session_start();
require_once 'config.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die('数据库连接失败，请检查配置文件。');
}?>
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
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <?php if (isset($_SESSION['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">管理</a>
                        </li>
                    <?php endif; ?>
</li>
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

    <div class="container mt-4">
        <form method="get" action="index.php" class="mb-4" onsubmit="return validateSearch();">
    <script>
    function validateSearch() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput.value.trim() === '') {
            alert('请填写搜索名称后再进行搜索！');
            return false;
        }
        return true;
    }
    </script>
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="搜索应用..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <select name="tag" class="form-select">
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
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">搜索</button>
                </div>
            </div>
        </form>
        <h1>最新应用</h1>
        <div class="row">
            <!-- 这里将通过PHP动态加载应用列表 -->
            <?php
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
            $offset = isset($_GET['page']) ? (intval($_GET['page']) - 1) * $limit : 0;
            $sql = "SELECT apps.id, apps.name, apps.description, apps.age_rating, AVG(reviews.rating) as avg_rating 
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
                  $conditions[] = "apps.platform = ?";
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
                     ORDER BY apps.created_at DESC 
                     LIMIT ? OFFSET ?";
            $limitVal = $limit;
            $offsetVal = $offset;
            $params[] = &$limitVal;
            $params[] = &$offsetVal;
            // 添加分页参数类型
              $paramTypes .= 'ii';
                      
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