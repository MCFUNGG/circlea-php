<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get member_id from POST request
$memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null; // Make sure to assign the value correctly

if (!$memberId) {
    echo json_encode(["success" => false, "message" => "Member ID is required"]);
    exit;
}

// Query to get the maximum version for the given member_id
$queryVersion = "SELECT MAX(version) as max_version FROM member_detail WHERE member_id = '$memberId'";
$resultVersion = mysqli_query($connect, $queryVersion);

if (!$resultVersion) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

// Fetch the max version
$rowVersion = mysqli_fetch_assoc($resultVersion);
$maxVersion = $rowVersion['max_version'];

// Check if max version is null
if (is_null($maxVersion)) {
    echo json_encode(["success" => false, "message" => "No versions found for this member ID"]);
    exit;
}

// Prepare the SQL query using the retrieved member_id and max_version
$query = "SELECT Gender, Address, Address_District_id, DOB, profile, description
          FROM member_detail 
          WHERE member_id = '$memberId' AND version = '$maxVersion'"; 

// Execute the query
$result = mysqli_query($connect, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

// Check if any application data was found
if (mysqli_num_rows($result) > 0) {
    $applicationData = [];
    
    // Fetch all application data as an associative array
    while ($row = mysqli_fetch_assoc($result)) {
        $applicationData[] = $row;
    }
    
    // Return JSON response with application data
    echo json_encode([
        "success" => true,
        "data" => $applicationData // Return as an array
    ]);
} else {
    echo json_encode(["success" => false, "message" => "No application found for this member ID"]);
}

// Close the database connection
mysqli_close($connect);
?>