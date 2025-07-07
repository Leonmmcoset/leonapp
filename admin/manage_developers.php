<?php
require_once '../config.php';
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
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_developers.php?deleted=true");
    exit;
}

// 处理更新用户请求
if (isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ? AND role = 'developer'");
    $stmt->bind_param("ssi", $username, $email, $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_developers.php?updated=true");
    exit;
}

// 获取所有开发者用户
$developers = [];
$result = $conn->query("SELECT id, username, email, created_at FROM users WHERE role = 'developer' ORDER BY created_at DESC");
if (!$result) {
    error_log('Failed to fetch developers: ' . $conn->error);
    die('获取开发者列表失败，请稍后重试');
}
while ($row = $result->fetch_assoc()) {
    $developers[] = $row;
}

// 获取要编辑的用户信息
$editUser = null;
if (isset($_GET['edit'])) {
    $editUserId = $_GET['edit'];
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ? AND role = 'developer'");
    $stmt->bind_param("i", $editUserId);
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
    <link rel="stylesheet" href="../styles.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .user-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .action-btn {
            padding: 6px 12px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .edit-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>管理开发者用户</h1>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="message success">用户已成功删除</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="message success">用户信息已成功更新</div>
        <?php endif; ?>
        
        <?php if ($editUser): ?>
            <div class="edit-form">
                <h2>编辑开发者用户</h2>
                <form method="post" action="manage_developers.php">
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">邮箱</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_user" class="submit-btn">更新用户</button>
                    <a href="manage_developers.php" class="action-btn">取消</a>
                </form>
            </div>
        <?php endif; ?>
        
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>注册时间</th>
                    <th>操作</th>
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
                            <a href="manage_developers.php?edit=<?php echo $developer['id']; ?>" class="action-btn edit-btn">编辑</a>
                            <form method="post" action="manage_developers.php" style="display: inline-block;" onsubmit="return confirm('确定要删除这个用户吗？');">
                                <input type="hidden" name="user_id" value="<?php echo $developer['id']; ?>">
                                <button type="submit" name="delete_user" class="action-btn delete-btn">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($developers)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">暂无开发者用户</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>