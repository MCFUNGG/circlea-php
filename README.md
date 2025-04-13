# CircleA PHP API

CircleA应用程序的PHP API后端，用于连接Android客户端和Azure MySQL数据库。

## 项目结构

```
php/
├── api/              # API端点文件
│   ├── auth/        # 认证相关
│   ├── tutor/       # 教师相关
│   └── student/     # 学生相关
├── config/          # 配置文件
└── utils/           # 工具函数
```

## 主要功能

- 用户认证和授权
- 教师申请管理
- 学生预约管理
- 支付处理
- 评分系统

## 部署要求

- PHP 7.4+
- MySQL 8.0+
- SSL支持

## 部署步骤

1. 配置数据库连接
   - 复制 `db_config.php` 到服务器
   - 更新数据库连接信息

2. 配置Azure App Service
   - 启用PHP扩展：
     - mysqli
     - openssl
     - json

3. 上传文件
   - 使用FTP或Git部署到Azure App Service
   - 确保文件权限正确

4. 验证部署
   - 访问 `test_connection.php` 检查数据库连接
   - 测试关键API端点

## 安全注意事项

- 所有API端点都需要有效的认证
- 数据库连接使用SSL
- 密码使用bcrypt加密
- 使用参数化查询防止SQL注入

## API文档

### 认证API

#### 登录
- 端点：`login.php`
- 方法：POST
- 参数：
  - email
  - password

#### 创建账户
- 端点：`create_account.php`
- 方法：POST
- 参数：
  - username
  - email
  - phone
  - password

### 教师API

#### 提交申请
- 端点：`post_application.php`
- 方法：POST
- 参数：
  - member_id
  - app_creator
  - class_level_id
  - description
  - fee_per_hr
  - subject_ids
  - district_ids
  - selected_dates

## 联系方式

如有问题，请联系项目维护者。 