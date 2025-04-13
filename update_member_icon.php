<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

$memberId = trim($_POST["member_id"]);
$file = $_FILES["file"];
$targetDir = "D:/xampp/htdocs/FYP/images/"; // 绝对路径

or die(json_encode(["success" => false, "message" => "Unable to connect to the database"]));

// 获取当前用户最新版本的信息，准备插入新记录
$queryVersion = "SELECT * FROM member_detail WHERE member_id = '$memberId' ORDER BY version DESC LIMIT 1";
$resultVersion = mysqli_query($connect, $queryVersion);

if ($resultVersion) {
    $row = mysqli_fetch_assoc($resultVersion);
    $version = ($row['version'] ?? 0) + 1; // 增加版本号

    // 获取其他用户信息
    $gender = $row['Gender'];
    $address = $row['Address'];
    $addressDistrictId = $row['Address_District_id'];
    $dob = $row['DOB'];
    $description = $row['description'];
    $status = $row['status'];

    // 插入新记录但不插入文件名
    $queryInsert = "INSERT INTO member_detail (member_id, version, Gender, Address, Address_District_id, DOB, description, status) 
                    VALUES ('$memberId', '$version', '$gender', '$address', '$addressDistrictId', '$dob', '$description', '$status')";
    $resultInsert = mysqli_query($connect, $queryInsert);

    if ($resultInsert) {
        // 获取刚插入记录的自增 ID
        $newId = mysqli_insert_id($connect);
        
        // 生成文件名
        $fileExtension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $uniqueFileName = "icon_" . $newId . "." . $fileExtension; // 使用自增 ID 生成唯一文件名
        $targetFile = $targetDir . $uniqueFileName;

        // 移动上传的文件
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            // 更新刚插入记录的文件名，存储相对路径
            $relativePath = "/FYP/images/" . $uniqueFileName; // 相对路径
            $queryUpdate = "UPDATE member_detail SET profile = '$relativePath' WHERE member_detail_id = '$newId'";
            $resultUpdate = mysqli_query($connect, $queryUpdate);

            if ($resultUpdate) {
                echo json_encode(["success" => true, "message" => "Icon updated to version $version successfully!"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update icon path: " . mysqli_error($connect)]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Error uploading file."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to insert member detail: " . mysqli_error($connect)]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Failed to fetch version: " . mysqli_error($connect)]);
}

mysqli_close($connect);
?>