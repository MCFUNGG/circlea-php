<?php
header("Content-Type: application/json"); // Set response type

// Database connection information
$host = "127.0.0.1";
$username = "root";
$password = ""; // Default password
$dbname = "system001";

// Get POST data
$tutorAppId = trim($_POST["tutor_app_id"]); 
$psAppId = trim($_POST["ps_app_id"]); 
$tutorId = trim($_POST["tutor_id"]); 
$psId = trim($_POST["ps_id"]); 
$matchMark = trim($_POST["match_mark"]); 



// Connect to the database
$connect = mysqli_connect($host, $username, $password, $dbname)
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