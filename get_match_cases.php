<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 使用配置的连接函数获取数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

try {
    // Get match cases
    $query = "SELECT * FROM match_case ORDER BY match_id DESC";
    $result = mysqli_query($connect, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $matches = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $matches[] = $row;
        }
        echo json_encode([
            "success" => true,
            "data" => $matches
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No match cases found"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

// 关闭数据库连接
mysqli_close($connect);
?>