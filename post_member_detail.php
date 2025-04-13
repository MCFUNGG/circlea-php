<?php
header("Content-Type: application/json"); // Set response type

// Database connection information
$host = "127.0.0.1";
$username = "root";
$password = ""; // Default password
$dbname = "system001";

// Get POST data
$memberId = trim($_POST["member_id"]); 
$addressDistrictId = trim($_POST["address_district_id"]); 
$address = trim($_POST["address"]); 
$dob = trim($_POST["dob"]); 
$profile = trim($_POST["profile"]); 
$description = trim($_POST["description"]); 
$version = 1; // Default version

$gender_input = trim($_POST["gender"]); // New field for gender
$gender = '';
if ($gender_input == 'Male') {
    $gender = 'M';
} elseif ($gender_input == 'Female') {
    $gender = 'F';
}

// Connect to the database
$connect = mysqli_connect($host, $username, $password, $dbname)
or die(json_encode(["success" => false, "message" => "Unable to connect to the database"]));

// Check if the member has an existing version
$queryVersion = "SELECT version FROM member_detail WHERE member_id = '$memberId' ORDER BY version DESC LIMIT 1";
$resultVersion = mysqli_query($connect, $queryVersion);

if ($resultVersion) {
    if (mysqli_num_rows($resultVersion) > 0) {
        // If there are existing records, fetch the latest version and increment it
        $row = mysqli_fetch_assoc($resultVersion);
        $version = $row['version'] + 1; // Increment version
    }
}

// Prepare SQL query to insert data
$queryDetails = "INSERT INTO member_detail (member_id, gender, address, address_district_id, dob, profile, description, version) 
                 VALUES ('$memberId', '$gender', '$address', '$addressDistrictId', '$dob', '$profile', '$description', '$version')";
$resultDetails = mysqli_query($connect, $queryDetails);

if ($resultDetails) {
    echo json_encode(["success" => true, "message" => "Application submitted successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to submit application: " . mysqli_error($connect)]);
}

// Close database connection
mysqli_close($connect);
?>