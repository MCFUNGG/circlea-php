<?php
header("Content-Type: application/json");

$host = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "system001";

$connect = mysqli_connect($host, $username, $password, $dbname);

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
          SET status = 'P' 
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