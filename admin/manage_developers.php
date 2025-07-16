<?php
require_once '../config.php';

// 设置会话cookie路径为根目录以确保跨目录访问
session_set_cookie_params(0, '/');
session_start();
// 检查是否已登录
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// 检查权限
if ($_SESSION['admin']['permission'] != 'all') {
    $redirect = $_SESSION['admin']['permission'] == 'say' ? 'announcements.php' : 'review_apps.php';
    header("Location: $redirect");
    exit();
}

// 处理退出登录
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    header('Location: login.php');
    exit;
}

// 导航栏
?>
<nav class="navbar navbar-expand-lg navbar-light blur-bg">
    <div class="container mt-4">
        <a href="../index.php"><img src="/favicon.jpeg" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
        <a class="navbar-brand" href="../index.php"><?php echo APP_STORE_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">App列表</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="addapp.php">添加App</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="review_apps.php">审核APP</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="manage_developers.php">管理开发者</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="confirmLogout()">退出登录</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
// 检查管理员权限
  // 设置会话cookie路径为根目录以确保跨目录访问
  session_set_cookie_params(0, '/');
  // 检查会话是否已启动，避免重复启动
  if (session_status() == PHP_SESSION_NONE) {
      if (!session_start()) {
          error_log('会话启动失败');
          header('Location: login.php');
          exit;
    
        error_log('会话启动失败');
      header('Location: login.php');
      exit;
  // 从数据库验证用户角色，确保权限检查准确性
  if (isset($_SESSION['user_id'])) {
      $userId = $_SESSION['user_id'];
      $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
      if (!$stmt) {
          error_log('Database prepare failed: ' . $conn->error);
          header('Location: login.php');
          exit;
      }
      $stmt->bind_param("i", $userId);
      if (!$stmt->execute()) {
          error_log('Query execution failed: ' . $stmt->error);
          header('Location: login.php');
          exit;
      }
      $result = $stmt->get_result();
      if (!$result) {
          error_log('Failed to get result: ' . $stmt->error);
          header('Location: login.php');
          exit;
      }
      $user = $result->fetch_assoc();
      
      if (!$user || $user['role'] !== 'admin') {
          error_log('用户 ' . $userId . ' 不是管理员，拒绝访问');
          header('Location: login.php');
          exit;
      }
  } else {
      error_log('未找到用户会话，重定向到登录页');
      header('Location: login.php');
      exit;
    }
}
}

// 处理删除用户请求
if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'developer'");
    if (!$stmt) {
        error_log('Database prepare failed: ' . $conn->error);
        header('Location: manage_developers.php?error=delete');
        exit;
    }
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        error_log('Delete query execution failed: ' . $stmt->error);
        header('Location: manage_developers.php?error=delete');
        exit;
    }
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    if ($affected_rows > 0) {
        header("Location: manage_developers.php?deleted=true");
    } else {
        error_log('No user deleted with ID: ' . $userId);
        header('Location: manage_developers.php?error=delete&user_id=' . $userId);
    }
    exit;
}

// 处理更新用户请求
if (isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    
    // 使用mysqli语法更新用户信息
$stmt = $conn->prepare("UPDATE developers SET username = ?, email = ? WHERE id = ?");
if (!$stmt) {
    $error = $conn->error ?? 'Unknown error';
    error_log("Prepare failed: $error");
    die("更新用户信息失败: $error");
}
$stmt->bind_param("ssi", $username, $email, $userId);
if (!$stmt->execute()) {
    $error = $stmt->error ?? 'Unknown error';
    error_log("Execute failed: $error");
    die("更新用户信息失败: $error");
}
$stmt->close();
header("Location: manage_developers.php?updated=true");
    exit;
}

// 获取所有开发者用户
$developers = [];
// 检查developers表是否存在
$tableExists = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'developers'");
if (!$tableExists || $tableExists->num_rows === 0) {
    error_log('Developers table does not exist');
    die('获取开发者列表失败: 开发者数据表不存在');
}

$sql = "SELECT * FROM developers ORDER BY id DESC";
$result = $conn->query($sql);
if (!$result) {
    error_log('Failed to fetch developers. SQL: ' . $sql . ', Error: ' . $conn->error);
    die('获取开发者列表失败: ' . $conn->error . ' (SQL: ' . $sql . ')');
}

// 检查是否有数据
$rowCount = $result->num_rows;
error_log('Developer query executed. Rows returned: ' . $rowCount);

while ($row = $result->fetch_assoc()) {
    $developers[] = $row;
}

// 获取要编辑的用户信息
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
$stmt = $conn->prepare("SELECT id, username, email FROM developers WHERE id = ?");
if (!$stmt) {
    error_log('Prepare failed for edit user: ' . $conn->error);
    die('获取编辑用户信息失败: ' . $conn->error);
}
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理开发者用户 - 应用商店管理</title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/css/all.min.css">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <!-- Fluent Design 模糊效果 -->
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

    </style>
    <script src="/js/sweetalert.js"></script>
    <script>
    function confirmLogout() {
        Swal.fire({
            title: '确定要登出吗?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '确定',
            cancelButtonText: '取消'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
    </script>
</head>
<body>
<!-- Bootstrap JS Bundle with Popper -->
<script src="/js/bootstrap.bundle.js"></script>
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
    <div class="container">
        <h1><i class="fas fa-users me-2"></i>管理开发者用户</h1>
<pre>调试信息:
查询SQL: <?php echo $sql; ?>
查询结果行数: <?php echo $rowCount; ?>
数据表存在: <?php echo $tableExists ? '是' : '否'; ?>
开发者数据: <?php print_r($developers); ?></pre>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">用户已成功删除</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">用户信息已成功更新</div>
        <?php endif; ?>
        
        <?php if ($editUser): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2><i class="fas fa-edit me-2"></i>编辑开发者用户</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="manage_developers.php">
                        <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                        <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
                <label for="username"><i class="fas fa-user me-2"></i>用户名</label>
            </div>
                        <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
                <label for="email"><i class="fas fa-envelope me-2"></i>邮箱</label>
            </div>
                        <button type="submit" name="update_user" class="btn btn-primary me-2"><i class="fas fa-save me-2"></i>更新用户</button>
                        <a href="manage_developers.php" class="btn btn-secondary"><i class="fas fa-times-circle me-2"></i>取消</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><i class="fas fa-id-card me-2"></i>ID</th>
                    <th><i class="fas fa-user me-2"></i>用户名</th>
                    <th><i class="fas fa-envelope me-2"></i>邮箱</th>
                    <th><i class="fas fa-clock me-2"></i>注册时间</th>
                    <th><i class="fas fa-cog me-2"></i>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($developers as $developer): ?>
                    <tr>
                        <td><?php echo $developer['id']; ?></td>
                        <td><?php echo htmlspecialchars($developer['username']); ?></td>
                        <td><?php echo htmlspecialchars($developer['email']); ?></td>
                        <td><?php echo $developer['created_at']; ?></td>
                        <td>
                            <a href="manage_developers.php?edit=<?php echo $developer['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>编辑</a>
                            <form method="post" action="manage_developers.php" style="display: inline-block;" onsubmit="return confirm('确定要删除这个用户吗？');">
                                <input type="hidden" name="user_id" value="<?php echo $developer['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt me-1"></i>删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($developers)): ?>
                    <tr>
                        <td colspan="5" class="text-center">暂无开发者数据</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>