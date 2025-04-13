<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get match_id from POST request
$matchId = $_POST['match_id'];

if ($matchId === null) {
    echo json_encode(["success" => false, "message" => "Match ID not provided"]);
    exit;
}

$query = "UPDATE `match` 
          SET status = 'R' 
          WHERE match_id = '$matchId' AND status = 'WT'";
$result = mysqli_query($connect, $query);

if ($result) {
    // Check if any rows were actually updated
    if (mysqli_affected_rows($connect) > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Status updated successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No matching record found to update"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error updating status: " . mysqli_error($connect)
    ]);
}

mysqli_close($connect);
?>  