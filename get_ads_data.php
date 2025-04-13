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
    // Get all active advertisements
    $query = $connect->prepare("SELECT ad_id, title, description, image_url, status FROM ads WHERE status = 'active' ORDER BY sort_order DESC");
    $query->execute();
    $result = $query->get_result();

    if ($result && $result->num_rows > 0) {
        $advertisements = [];
        
        while ($row = $result->fetch_assoc()) {
            // Add data to results
            $advertisements[] = [
                'ad_id' => $row['ad_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'image_url' => $row['image_url']  // This will be like ./FYP/ads/1743480844_等等網取畫面 2024-10-03 105745.png
            ];
        }

        // Return collected advertisement data
        echo json_encode([
            "success" => true,
            "data" => $advertisements
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No active advertisements found"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

// Close database connection
mysqli_close($connect);
?>