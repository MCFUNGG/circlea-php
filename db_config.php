<?php
// Azure MySQL数据库配置
$host = "circlea-mysql-server.mysql.database.azure.com";
$username = "dfwuwerfsf";
$password = "b2WH3vN$5$LTAWXP";
$dbname = "circlea-db";

function getDbConnection() {
    global $host, $username, $password, $dbname;
    
    $connect = mysqli_init();
    mysqli_ssl_set($connect, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($connect, $host, $username, $password, $dbname, 3306, NULL, MYSQLI_CLIENT_SSL);
    
    if (!$connect) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // 设置字符集为utf8mb4
    mysqli_set_charset($connect, "utf8mb4");
    
    return $connect;
}
?> 