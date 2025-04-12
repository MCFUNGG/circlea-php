<?php
// Azure MySQL数据库连接配置
$host = "circlea-mysql-server.mysql.database.azure.com";
$username = "dfwuwerfsf";
$password = "b2WH3vN$5$LTAWXP";
$dbname = "circlea-db";

// SSL配置 - Azure MySQL要求SSL连接
$ssl_options = array(
    "ssl_verify" => true
);

// 创建数据库连接函数
function getDbConnection() {
    global $host, $username, $password, $dbname, $ssl_options;
    
    // 创建连接
    $connect = mysqli_init();
    
    // 设置SSL选项
    mysqli_ssl_set($connect, NULL, NULL, NULL, NULL, NULL);
    
    // 连接到服务器
    mysqli_real_connect($connect, $host, $username, $password, $dbname, 3306, MYSQLI_CLIENT_SSL);
    
    // 检查连接
    if (mysqli_connect_errno()) {
        error_log("数据库连接失败: " . mysqli_connect_error());
        return false;
    }
    
    // 设置字符集
    mysqli_set_charset($connect, "utf8mb4");
    
    return $connect;
}
?> 