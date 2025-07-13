<?php
// 设置响应头为 Atom 格式
header('Content-Type: application/atom+xml; charset=UTF-8');

// 引入配置文件
require_once 'config.php';

// 创建数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// 检查连接是否成功
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 查询最新的 app 列表，按发布时间降序排列
$sql = "SELECT * FROM apps ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);

// 获取当前时间
$now = date('c');

// 生成 Atom 内容
$atom = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<feed xmlns=\"http://www.w3.org/2005/Atom\">
    <title>最新 App 列表</title>
    <link href=\"http://leonmmcoset.jjmm.ink:3232\"/>
leonmmcoset.jjmm.ink:3232
    <link href=\"http:///rss.php\" rel=\"self\"/>
    <updated>" . $now . "</updated>
    <id>http://leonmmcoset.jjmm.ink:3232</id>
    <author>
        <name>管理员</name>
    </author>
";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $atom .= "    <entry>
        <title>" . htmlspecialchars($row['name']) . "</title>
        <link href=\"http://leonmmcoset.jjmm.ink:3232/app.php?id=" . $row['id'] . "\"/>
        <id>http://leonmmcoset.jjmm.ink:3232/app.php?id=" . $row['id'] . "</id>
        <updated>" . date('c', strtotime($row['created_at'])) . "</updated>
        <summary type=\"html\">" . htmlspecialchars($row['description']) . "</summary>
    </entry>
";
    }
}

$atom .= "</feed>";

// 输出 Atom 内容
print $atom;

// 关闭数据库连接
$conn->close();