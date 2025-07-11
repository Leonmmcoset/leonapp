<?php
require_once '../config.php';
require_once 'login.php'; // 确保管理员已登录

// 处理标签添加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tag'])) {
    $name = trim($_POST['tag_name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            header('Location: manage_tags.php?success=标签添加成功');
            exit;
        } else {
            $error = '添加失败: ' . $conn->error;
        }
    } else {
        $error = '标签名称不能为空';
    }
}

// 处理标签删除
if (isset($_GET['delete'])) {
    $tagId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->bind_param("i", $tagId);
    if ($stmt->execute()) {
        header('Location: manage_tags.php?success=标签删除成功');
        exit;
    } else {
        $error = '删除失败: ' . $conn->error;
    }
}

// 处理标签编辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tag'])) {
    $tagId = intval($_POST['tag_id']);
    $name = trim($_POST['tag_name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE tags SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $tagId);
        if ($stmt->execute()) {
            header('Location: manage_tags.php?success=标签更新成功');
            exit;
        } else {
            $error = '更新失败: ' . $conn->error;
        }
    } else {
        $error = '标签名称不能为空';
    }
}

// 获取所有标签
$tagsResult = $conn->query("SELECT * FROM tags ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>标签管理 - 应用商店后台</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/animations.css">
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">标签管理</h1>
        <a href="index.php" class="btn btn-secondary mb-3">返回应用列表</a>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo $_GET['success']; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- 添加标签表单 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">添加新标签</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                        <label for="tag_name">标签名称</label>
                    </div>
                    <button type="submit" name="add_tag" class="btn btn-primary">添加标签</button>
                </form>
            </div>
        </div>

        <!-- 标签列表 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">现有标签</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>标签名称</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tag = $tagsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $tag['id']; ?></td>
                                <td><?php echo htmlspecialchars($tag['name']); ?></td>
                                <td><?php echo $tag['created_at']; ?></td>
                                <td>
                                    <!-- 编辑按钮触发模态框 -->
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $tag['id']; ?>">编辑</button>
                                    <a href="manage_tags.php?delete=<?php echo $tag['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除这个标签吗？关联的应用标签也会被删除。');">删除</a>
                                </td>
                            </tr>

                            <!-- 编辑标签模态框 -->
                            <div class="modal fade" id="editModal<?php echo $tag['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">编辑标签</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" id="edit_tag_name<?php echo $tag['id']; ?>" name="tag_name" value="<?php echo htmlspecialchars($tag['name']); ?>" required>
                                                    <label for="edit_tag_name<?php echo $tag['id']; ?>">标签名称</label>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                                    <button type="submit" name="edit_tag" class="btn btn-primary">保存修改</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>