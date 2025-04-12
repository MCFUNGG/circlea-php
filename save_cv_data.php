<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "system001";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Get POST data
$member_id = $_POST['member_id'] ?? '';
$contact = $_POST['contact'] ?? '';
$skills = $_POST['skills'] ?? '';
$education = $_POST['education'] ?? '';
$language = $_POST['language'] ?? '';
$other = $_POST['other'] ?? '';
$image_data = $_POST['image'] ?? '';

if (empty($member_id)) {
    die(json_encode([
        'success' => false,
        'message' => 'Member ID is required'
    ]));
}

// Create directory if it doesn't exist
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/ScanCV/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$image_filename = 'CV_' . $member_id . '_' . time() . '.jpg';
$image_path = 'uploads/' . $image_filename;
$full_path = $upload_dir . $image_filename;

// Decode and save image
$image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image_data));
if (file_put_contents($full_path, $image_data)) {
    // Prepare SQL statement
    $sql = "INSERT INTO cv_data (member_id, contact, skills, education, language, other, cv_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            contact = VALUES(contact),
            skills = VALUES(skills),
            education = VALUES(education),
            language = VALUES(language),
            other = VALUES(other),
            cv_path = VALUES(cv_path)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $member_id, $contact, $skills, $education, $language, $other, $image_path);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'CV data and image saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error saving CV data: ' . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error saving image file'
    ]);
}

$conn->close();
?>