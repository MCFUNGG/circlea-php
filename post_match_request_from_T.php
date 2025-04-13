<?php
header("Content-Type: application/json"); // Set response type

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

// Get POST data
$tutorAppId = trim($_POST["tutor_app_id"]); 
$psAppId = trim($_POST["ps_app_id"]); 
$tutorId = trim($_POST["tutor_id"]); 
$psId = trim($_POST["ps_id"]); 
$matchMark = trim($_POST["match_mark"]); 



// Connect to the database
or die(json_encode(["success" => false, "message" => "Unable to connect to the database"]));


$query = "INSERT INTO `match` (match_creator,tutor_id , tutor_app_id, ps_id, ps_app_id, match_mark, status) 
                 VALUES ('T','$tutorId','$tutorAppId','$psId','$psAppId', '$matchMark', 'WPS')";
$result = mysqli_query($connect, $query);

if ($result) {
    echo json_encode(["success" => true, "message" => "Application submitted successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to submit application: " . mysqli_error($connect)]);
}

// Close database connection
mysqli_close($connect);
?>