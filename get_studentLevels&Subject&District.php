<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Fetch student levels
$levelsQuery = "SELECT class_level_id, class_level_name FROM class_level"; // Adjust the table name
$levelsResult = mysqli_query($connect, $levelsQuery);
if (!$levelsResult) {
    echo json_encode(["success" => false, "message" => "Q   uery failed: " . mysqli_error($connect)]);
    exit;
}

$levels = [];
while ($row = mysqli_fetch_assoc($levelsResult)) {
    $levels[] = $row;
}

// Repeat for subjects and districts
$subjectsQuery = "SELECT subject_id, subject_name FROM subject"; // Adjust the table name
$subjectsResult = mysqli_query($connect, $subjectsQuery);
if (!$subjectsResult) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

$subjects = [];
while ($row = mysqli_fetch_assoc($subjectsResult)) {
    $subjects[] = $row;
}

$districtsQuery = "SELECT district_id, district_name FROM district"; // Adjust the table name
$districtsResult = mysqli_query($connect, $districtsQuery);
if (!$districtsResult) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

$districts = [];
while ($row = mysqli_fetch_assoc($districtsResult)) {
    $districts[] = $row;
}

// Return JSON response
echo json_encode([
    "levels" => $levels,
    "subjects" => $subjects,
    "districts" => $districts
]);

mysqli_close($connect);
?>  