<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];

// API根路径处理 - 返回可用端点信息
if (!isset($_GET['action']) || $_GET['action'] === '') {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'App Store API',
        'version' => '1.0',
        'endpoints' => [
            '/api?action=list' => '获取应用列表，支持search、platform、age_rating、tag、page、limit参数。search：搜索关键词；platform：平台；age_rating：年龄分级；tag：标签；page：页码；limit：每页数量',
            '/api?action=app&id=1' => '获取指定ID的应用详情，需传入app_id参数。包含应用基础信息、版本、图片、评价和标签信息',
            '/api?action=favorite' => '收藏应用（POST方法，需app_id和user_id参数）'
        ],
        'example' => 'GET /api?action=list&search=游戏&limit=10'
    ]);
    exit;
}


// 支持查询参数路由模式（不依赖URL重写）
// 以下为各API端点详细处理逻辑
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // 处理应用列表请求，支持多条件筛选和分页查询
    // 参数:
    //   search - 搜索关键词，可选
    //   platform - 平台，可选
    //   age_rating - 年龄分级，可选
    //   tag - 标签，可选
    //   page - 页码，可选，默认1
    //   limit - 每页数量，可选，默认10
    // 方法: GET
    if ($action === 'list' && $requestMethod === 'GET') {
        $sql = "SELECT apps.id, apps.name, apps.description, apps.age_rating, AVG(reviews.rating) as avg_rating 
                FROM apps 
                LEFT JOIN reviews ON apps.id = reviews.app_id";

        $conditions = [];
        $stmtParams = [];
        $paramTypes = '';
        
        // 搜索功能
        if (isset($_GET['search'])) {
            $search = '%' . $_GET['search'] . '%';
            $conditions[] = "(apps.name LIKE ? OR apps.description LIKE ?)";
            $stmtParams[] = &$search;
            $stmtParams[] = &$search;
            $paramTypes .= 'ss';
        }
        
        // 平台过滤
        if (isset($_GET['platform'])) {
            $platform = $_GET['platform'];
            // Removed platform condition - column does not exist
            $stmtParams[] = &$platform;
            $paramTypes .= 's';
        }
        
        // 年龄分级过滤
        if (isset($_GET['age_rating'])) {
            $ageRating = $_GET['age_rating'];
            $conditions[] = "apps.age_rating = ?";
            $stmtParams[] = &$ageRating;
            $paramTypes .= 's';
        }

        // 标签过滤
        if (isset($_GET['tag'])) {
            $tag = $_GET['tag'];
            $conditions[] = "apps.id IN (SELECT app_id FROM app_tags JOIN tags ON app_tags.tag_id = tags.id WHERE tags.name = ?)";
            $stmtParams[] = &$tag;
            $paramTypes .= 's';
        }
        
        // 分页参数处理
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // 添加分页
        $sql .= " GROUP BY apps.id, apps.name, apps.description, apps.age_rating ORDER BY apps.created_at DESC LIMIT ? OFFSET ?";
        $stmtParams[] = &$limit;
        $stmtParams[] = &$offset;
        $paramTypes .= 'ii';

        // 获取总数用于分页元数据
        $countSql = "SELECT COUNT(DISTINCT apps.id) as total FROM apps LEFT JOIN reviews ON apps.id = reviews.app_id";
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(" AND ", $conditions);
        }
        $countStmt = $conn->prepare($countSql);
        if ($paramTypes && count($stmtParams) > 2) {
            // 排除最后两个分页参数
            $countParams = array_slice($stmtParams, 0, -2);
            $countTypes = substr($paramTypes, 0, -2);
            call_user_func_array([$countStmt, 'bind_param'], array_merge([$countTypes], $countParams));
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($total / $limit);

        // 执行主查询
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit;
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$paramTypes], $stmtParams));
        $stmt->execute();
        $result = $stmt->get_result();

        $apps = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $apps[] = $row;
            }
        }

        // 返回带分页元数据的响应
        echo json_encode([
            'data' => $apps,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages
            ]
        ]);
        exit;
    }
    
    // 处理应用详情请求，获取指定ID应用的详细信息，包含基础信息、版本、图片、评价和标签信息
    // 参数: id - 应用ID，必需，数字类型
    // 方法: GET
    elseif ($action === 'app' && isset($_GET['id']) && is_numeric($_GET['id']) && $requestMethod === 'GET') {
        $appId = $_GET['id'];
        error_log("Requesting app details for ID: $appId");

        $sqlApp = "SELECT apps.id, apps.name, apps.description, apps.age_rating, apps.created_at, AVG(reviews.rating) as avg_rating 
                   FROM apps 
                   LEFT JOIN reviews ON apps.id = reviews.app_id 
                   WHERE apps.id = ? 
                   GROUP BY apps.id, apps.name, apps.description, apps.age_rating, apps.created_at";
        $stmt = $conn->prepare($sqlApp);
        $stmt->bind_param("i", $appId);
        $stmt->execute();
        $resultApp = $stmt->get_result();
        error_log("Executing prepared statement for app details");

        if (!$resultApp) {
            error_log("Database error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['error' => 'Database query failed']);
            exit;
        }

        $app = $resultApp->fetch_assoc();
        error_log("App found: " . ($app ? "Yes" : "No"));

        if ($app) {
            // 获取版本信息
            $sqlVersions = "SELECT * FROM app_versions WHERE app_id = $appId ORDER BY created_at DESC";
            $resultVersions = $conn->query($sqlVersions);
            $versions = [];
            while ($version = $resultVersions->fetch_assoc()) {
                $versions[] = $version;
            }
            $app['versions'] = $versions;

            // 获取图片信息
            $sqlImages = "SELECT * FROM app_images WHERE app_id = $appId";
            $resultImages = $conn->query($sqlImages);
            $images = [];
            while ($image = $resultImages->fetch_assoc()) {
                $images[] = $image;
            }
            $app['images'] = $images;

            // 获取评价信息
            $sqlReviews = "SELECT * FROM reviews WHERE app_id = $appId ORDER BY created_at DESC";
            $resultReviews = $conn->query($sqlReviews);
            $reviews = [];
            while ($review = $resultReviews->fetch_assoc()) {
                $reviews[] = $review;
            }
            $app['reviews'] = $reviews;

            // 获取应用标签
            $sqlTags = "SELECT tags.id, tags.name FROM app_tags JOIN tags ON app_tags.tag_id = tags.id WHERE app_tags.app_id = ?";
            $stmtTags = $conn->prepare($sqlTags);
            $stmtTags->bind_param("i", $appId);
            $stmtTags->execute();
            $resultTags = $stmtTags->get_result();
            $tags = [];
            while ($tag = $resultTags->fetch_assoc()) {
                $tags[] = $tag;
            }
            $app['tags'] = $tags;

            echo json_encode($app);
        } else {
            http_response_code(404);
            echo json_encode(['error' => "App with ID $appId not found", 'sql' => $sqlApp]);
        }
        exit;
    }
    
    // 处理用户收藏应用
    elseif ($action === 'favorite' && isset($_GET['app_id']) && is_numeric($_GET['app_id']) && isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $requestMethod === 'POST') {
        $appId = $_GET['app_id'];
        $userId = $_GET['user_id'];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, app_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $appId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'App added to favorites']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add to favorites']);
        }
        $stmt->close();
        exit;
    }
    
    // 获取用户收藏列表
    elseif ($action === 'favorites' && isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $requestMethod === 'GET') {
        $userId = $_GET['user_id'];
        
        $sql = "SELECT apps.* FROM user_favorites JOIN apps ON user_favorites.app_id = apps.id WHERE user_favorites.user_id = $userId";
        $result = $conn->query($sql);
        
        $favorites = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $favorites[] = $row;
            }
        }
        
        echo json_encode($favorites);
        exit;
    }

    // 获取所有标签
    elseif ($action === 'tags' && $requestMethod === 'GET') {
        $sql = "SELECT id, name FROM tags ORDER BY name";
        $result = $conn->query($sql);
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        echo json_encode($tags);
        exit;
    }
    
    // 获取应用推荐列表
    elseif ($action === 'recommendations' && $requestMethod === 'GET') {
        $sql = "SELECT apps.*, app_recommendations.reason FROM app_recommendations JOIN apps ON app_recommendations.app_id = apps.id";
        $result = $conn->query($sql);
        
        $recommendations = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = $row;
            }
        }
        
        echo json_encode($recommendations);
        exit;
    }
    
    // 获取热门应用排行榜
    elseif ($action === 'hot_apps' && $requestMethod === 'GET') {
        $sql = "SELECT apps.*, SUM(app_versions.download_count) as total_downloads FROM apps JOIN app_versions ON apps.id = app_versions.app_id GROUP BY apps.id ORDER BY total_downloads DESC LIMIT 10";
        $result = $conn->query($sql);
        
        $hotApps = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $hotApps[] = $row;
            }
        }
        
        echo json_encode($hotApps);
        exit;
    }
    
    // 提交用户反馈
    elseif ($action === 'feedback' && isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $requestMethod === 'POST') {
        $userId = $_GET['user_id'];
        $appId = isset($_GET['app_id']) && is_numeric($_GET['app_id']) ? $_GET['app_id'] : null;
        $content = $_POST['content'] ?? '';
        
        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Feedback content is required']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO user_feedback (user_id, app_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userId, $appId, $content);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit feedback']);
        }
        $stmt->close();
        exit;
    }
    
    // 处理下载请求
    elseif ($action === 'download' && isset($_GET['version_id']) && is_numeric($_GET['version_id']) && $requestMethod === 'GET') {
        $versionId = $_GET['version_id'];
        
        $stmt = $conn->prepare("SELECT * FROM app_versions WHERE id = ?");
        $stmt->bind_param("i", $versionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $version = $result->fetch_assoc();
            // 更新下载计数
            $updateStmt = $conn->prepare("UPDATE app_versions SET download_count = download_count + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $versionId);
            $updateStmt->execute();
            $updateStmt->close();
            $filePath = $version['file_path'];
            
            if (file_exists($filePath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Version not found']);
        }
        $stmt->close();
        exit;
    }
    
    // 获取公告列表（支持分页）
    elseif ($action === 'announcements' && $requestMethod === 'GET') {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM announcements";
        $countResult = $conn->query($countSql);
        $total = $countResult->fetch_assoc()['total'];
        
        // 获取分页数据
        $sql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $perPage);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
        
        // 返回分页数据和元信息
        echo json_encode([
            'data' => $announcements,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
        $stmt->close();
        exit;
    }
    
    // 获取最新公告
    elseif ($action === 'latest_announcement' && $requestMethod === 'GET') {
        $sql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1";
        $result = $conn->query($sql);
        
        $announcement = $result->fetch_assoc();
        echo json_encode($announcement);
        exit;
    }
    
    // 添加公告
    elseif ($action === 'add_announcement' && $requestMethod === 'POST') {
        if (!isset($_SESSION['admin']['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $adminId = $_SESSION['admin']['id'];
        
        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, admin_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $content, $adminId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add announcement']);
        }
        $stmt->close();
        exit;
    }
    
    // 删除公告
    elseif ($action === 'delete_announcement' && isset($_GET['id']) && is_numeric($_GET['id']) && $requestMethod === 'DELETE') {
        if (!isset($_SESSION['admin']['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $id = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete announcement']);
        }
        $stmt->close();
        exit;
    }
    
    // 更新公告
    elseif ($action === 'update_announcement' && isset($_GET['id']) && is_numeric($_GET['id']) && $requestMethod === 'PUT') {
        if (!isset($_SESSION['admin']['id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        parse_str(file_get_contents('php://input'), $putData);
        $id = $_GET['id'];
        $title = $putData['title'] ?? '';
        $content = $putData['content'] ?? '';
        
        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Announcement updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update announcement']);
        }
        $stmt->close();
        exit;
    }
    
    // 无效操作
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action or parameters']);
        exit;
    }
}

// 保留原路径路由逻辑作为兼容 fallback
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('/\.php$/', '', $path);
$pathParts = explode('/', trim($path, '/'));
error_log("Path parts: " . print_r($pathParts, true));

if ($pathParts[0] === 'api') {
    error_log("Processing API path request: " . print_r($pathParts, true));
    // 处理应用详情请求 /api/app/<id>
    if (count($pathParts) >= 3 && $pathParts[1] === 'app' && is_numeric($pathParts[2])) {
        $appId = $pathParts[2];
        error_log("Path-based app details request for ID: $appId");

        $sqlApp = "SELECT apps.id, apps.name, apps.description, apps.age_rating, apps.platform, apps.created_at, AVG(reviews.rating) as avg_rating 
                   FROM apps 
                   LEFT JOIN reviews ON apps.id = reviews.app_id 
                   WHERE apps.id = ? 
                   GROUP BY apps.id, apps.name, apps.description, apps.age_rating, apps.platform, apps.created_at";
        $stmt = $conn->prepare($sqlApp);
        $stmt->bind_param("i", $appId);
        $stmt->execute();
        $resultApp = $stmt->get_result();
        error_log("Executing prepared statement for path-based app details");

        if (!$resultApp) {
            error_log("Database error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['error' => 'Database query failed']);
            exit;
        }

        $app = $resultApp->fetch_assoc();
        error_log("App found via path: " . ($app ? "Yes" : "No"));

        if ($app) {
            // 获取版本信息
            $sqlVersions = "SELECT * FROM app_versions WHERE app_id = $appId ORDER BY created_at DESC";
            $resultVersions = $conn->query($sqlVersions);
            $versions = [];
            while ($version = $resultVersions->fetch_assoc()) {
                $versions[] = $version;
            }
            $app['versions'] = $versions;

            // 获取图片信息
            $sqlImages = "SELECT * FROM app_images WHERE app_id = $appId";
            $resultImages = $conn->query($sqlImages);
            $images = [];
            while ($image = $resultImages->fetch_assoc()) {
                $images[] = $image;
            }
            $app['images'] = $images;

            // 获取评价信息
            $sqlReviews = "SELECT * FROM reviews WHERE app_id = $appId ORDER BY created_at DESC";
            $resultReviews = $conn->query($sqlReviews);
            $reviews = [];
            while ($review = $resultReviews->fetch_assoc()) {
                $reviews[] = $review;
            }
            $app['reviews'] = $reviews;

            echo json_encode($app);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => 'Not found',
                'path' => $path,
                'path_parts' => $pathParts,
                'action' => isset($_GET['action']) ? $_GET['action'] : null
            ]);
        }
        exit;
    }
    // 处理其他API路径请求...
}

http_response_code(404);
echo json_encode([
    'error' => 'Not found',
    'path' => $path,
    'path_parts' => $pathParts,
    'action' => isset($_GET['action']) ? $_GET['action'] : null
]);
?>