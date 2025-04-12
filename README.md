# CircleA PHP API

此目录包含CircleA应用程序的PHP API文件，已针对Azure MySQL数据库进行了配置。

## 文件结构

- `db_config.php`: 数据库连接配置文件
- `*.php`: 各种API端点文件
- `update_db_connections.php`: 批量更新数据库连接的工具脚本（可选使用）

## 部署说明

### 1. 准备文件

所有PHP文件都已配置为使用Azure MySQL数据库。如果您需要更改连接信息，请修改`db_config.php`文件。

### 2. 部署到Azure App Service

将整个文件夹上传到Azure App Service的wwwroot目录中。有几种方式可以实现：

#### 使用FTP
1. 在Azure门户中，找到您的App Service
2. 在"部署中心"或"FTPS凭据"中获取FTP连接信息
3. 使用FileZilla等FTP客户端连接并上传文件

#### 使用ZIP部署
1. 将整个PHP目录压缩为ZIP文件
2. 在Azure门户中，选择您的App Service
3. 使用"部署中心"中的"手动部署"选项上传ZIP文件

### 3. 测试连接

部署完成后，访问以下URL测试数据库连接：

```
https://your-app-name.azurewebsites.net/php/get_json.php
```

## 故障排除

如果遇到连接问题：

1. 检查`db_config.php`中的连接信息是否正确
2. 确认Azure App Service中已启用PHP的mysqli扩展
3. 检查Azure MySQL服务器防火墙规则是否允许Azure服务连接
4. 查看错误日志（`php_error.log`）了解详细错误信息

## Android应用程序配置

更新Android应用程序中的API基础URL：

```java
// 旧URL（本地XAMPP）
String BASE_URL = "http://192.168.X.X/php/";

// 新URL（Azure App Service）
String BASE_URL = "https://circlea-app.azurewebsites.net/php/";
``` 