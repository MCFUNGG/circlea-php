<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 创建一个连接
$connect = getDbConnection();

// Check connection
if (!$connect) {
    error_log("Database connection error: " . mysqli_connect_error());
    echo json_encode(["success" => false, "message" => "Unable to connect to the database"]);
    exit;
}

// Get POST data and validate
$memberId = isset($_POST["member_id"]) ? trim($_POST["member_id"]) : null;
$appCreator = isset($_POST["app_creator"]) ? trim($_POST["app_creator"]) : null;
$classLevelId = isset($_POST["class_level_id"]) ? trim($_POST["class_level_id"]) : null;
$description = isset($_POST["description"]) ? trim($_POST["description"]) : null;
$feePerHr = isset($_POST["fee_per_hr"]) ? trim($_POST["fee_per_hr"]) : null;
$selectedDatesJson = isset($_POST["selected_dates"]) ? $_POST["selected_dates"] : null;
$selectedDates = json_decode($selectedDatesJson, true);
$lessonPerWeek = isset($_POST["lessons_per_week"]) ? trim($_POST["lessons_per_week"]) : null;

// Check for required fields
if (is_null($memberId) || is_null($appCreator) || is_null($classLevelId) || is_null($description) || is_null($feePerHr)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Retrieve subject IDs from JSON
$subjectIdsJson = isset($_POST["subject_ids"]) ? $_POST["subject_ids"] : null;
$subjectIds = json_decode($subjectIdsJson, true);

// Check if subject IDs are valid
if (json_last_error() !== JSON_ERROR_NONE || empty($subjectIds) || !is_array($subjectIds)) {
    echo json_encode(["success" => false, "message" => "Subject IDs are required and must be valid."]);
    exit;
}

// Retrieve district IDs from JSON
$districtIdsJson = isset($_POST["district_ids"]) ? $_POST["district_ids"] : null;
$districtIds = json_decode($districtIdsJson, true);

// Check if district IDs are valid
if (json_last_error() !== JSON_ERROR_NONE || empty($districtIds) || !is_array($districtIds)) {
    echo json_encode(["success" => false, "message" => "District IDs are required and must be valid."]);
    exit;
}

// Sanitize inputs to prevent SQL injection
$memberId = mysqli_real_escape_string($connect, $memberId);
$appCreator = mysqli_real_escape_string($connect, $appCreator);
$classLevelId = mysqli_real_escape_string($connect, $classLevelId);
$description = mysqli_real_escape_string($connect, $description);
$feePerHr = mysqli_real_escape_string($connect, $feePerHr);

// Prepare insert query for the application table
$query = "INSERT INTO application (member_id, app_creator, class_level_id, description, feePerHr, status, lessonPerWeek)
          VALUES ('$memberId', '$appCreator', '$classLevelId', '$description', '$feePerHr',  'P', " . 
          ($lessonPerWeek ? "'$lessonPerWeek'" : "NULL") . ")";
          

if (!mysqli_query($connect, $query)) {
    echo json_encode(["success" => false, "message" => "Application submission failed: " . mysqli_error($connect)]);
    exit;
}

$applicationId = mysqli_insert_id($connect); // Get the last inserted application ID

// Insert multiple subjects into application_subject table
foreach ($subjectIds as $subjectId) {
    $subjectId = mysqli_real_escape_string($connect, $subjectId);
    $querySubject = "INSERT INTO application_subject (app_id, subject_id) VALUES ('$applicationId', '$subjectId')";
    
    if (!mysqli_query($connect, $querySubject)) {
        echo json_encode(["success" => false, "message" => "Failed to insert subject ID: $subjectId. " . mysqli_error($connect)]);
        exit;
    }
}

// Insert multiple districts into application_district table
foreach ($districtIds as $distId) {
    $distId = mysqli_real_escape_string($connect, $distId);
    $queryDistrict = "INSERT INTO application_district (app_id, district_id) VALUES ('$applicationId', '$distId')";
    
    if (!mysqli_query($connect, $queryDistrict)) {
        echo json_encode(["success" => false, "message" => "Failed to insert district ID: $distId. " . mysqli_error($connect)]);
        exit;
    }
}

// Validate selected dates
if (json_last_error() !== JSON_ERROR_NONE || empty($selectedDates) || !is_array($selectedDates)) {
    echo json_encode(["success" => false, "message" => "Selected dates are required and must be valid."]);
    exit;
}

// Initialize an array for times for each day of the week
$times = [
    'monday_time' => null,
    'tuesday_time' => null,
    'wednesday_time' => null,
    'thursday_time' => null,
    'friday_time' => null,
    'saturday_time' => null,
    'sunday_time' => null
];

// Populate the times array based on user selection
foreach ($selectedDates as $dateEntry) {
    list($day, $time) = explode(": ", $dateEntry);
    $time = mysqli_real_escape_string($connect, trim($time));
    
    switch (strtolower(trim($day))) {
        case 'monday':
            $times['monday_time'] = $time;
            break;
        case 'tuesday':
            $times['tuesday_time'] = $time;
            break;
        case 'wednesday':
            $times['wednesday_time'] = $time;
            break;
        case 'thursday':
            $times['thursday_time'] = $time;
            break;
        case 'friday':
            $times['friday_time'] = $time;
            break;
        case 'saturday':
            $times['saturday_time'] = $time;
            break;
        case 'sunday':
            $times['sunday_time'] = $time;
            break;
    }
}

// Construct the SQL query for dates
$queryDate = "INSERT INTO application_date (app_id, monday_time, tuesday_time, wednesday_time, thursday_time, friday_time, saturday_time, sunday_time)
    VALUES ('$applicationId', '{$times['monday_time']}', '{$times['tuesday_time']}', '{$times['wednesday_time']}', '{$times['thursday_time']}', '{$times['friday_time']}', '{$times['saturday_time']}', '{$times['sunday_time']}')";

// Execute the date insertion query
if (!mysqli_query($connect, $queryDate)) {
    echo json_encode(["success" => false, "message" => "Failed to insert application dates: " . mysqli_error($connect)]);
    exit;
}

// If everything is successful
echo json_encode(["success" => true, "message" => "Application submitted successfully!"]);

// Close database connection
mysqli_close($connect);
?>