---
type: "always_apply"
---

# Laravel 家政服务后端 API 项目开发规范

## 项目概述

这是一个基于 Laravel 8.x 的家政服务后端 API 项目，使用 JWT 认证、角色权限管理等技术栈。

## 技术栈规范

-   **框架**: Laravel 8.x
-   **PHP 版本**: ^7.3|^8.0
-   **认证**: JWT
-   **权限管理**: spatie
-   **API 风格**: RESTful API
-   **数据库**: MySQL
-   **缓存**: Redis

## 代码结构规范

### 1. 控制器规范

-   所有 API 控制器必须继承 `App\Http\Controllers\Controller`
-   控制器命名使用单数形式，如 `UserController`, `OrderController`
-   每个控制器方法必须有明确的返回类型声明
-   使用资源控制器模式：index, show, store, update, destroy
-   控制器方法必须包含详细的 PHPDoc 注释

### 2. 模型规范

-   所有模型必须继承 `Illuminate\Database\Eloquent\Model`
-   模型文件放置在 `app/Models/` 目录下
-   必须定义 `$fillable` 或 `$guarded` 属性
-   敏感字段必须添加到 `$hidden` 属性
-   日期字段必须在 `$casts` 中声明类型
-   关联关系必须使用类型提示

### 3. 路由规范

-   API 路由统一定义在 `routes/api.php` 文件中
-   路由分组使用中间件：`auth:api`, `throttle:api`
-   路由命名使用点分隔符：`api.users.index`
-   版本化 API 使用前缀：`v1/`, `v2/`

### 4. 中间件规范

-   自定义中间件放置在 `app/Http/Middleware/` 目录
-   中间件必须在 `app/Http/Kernel.php` 中注册
-   使用描述性命名，如 `CheckUserRole`, `ValidateApiKey`

### 5. 请求验证规范

-   使用 Form Request 类进行数据验证
-   请求类放置在 `app/Http/Requests/` 目录
-   同一 Controller 中的 Request 放到同一文件夹下
-   验证规则必须详细且安全
-   错误消息必须用户友好且支持国际化

### 6. 响应格式规范

-   统一使用 app\Http\Responses\ApiResponse.php 响应

## 安全规范

### 1. 认证授权

-   API 接口必须进行身份认证
-   使用 JWT Token 进行用户认证

### 2. 数据验证

-   所有用户输入必须进行验证和过滤
-   防止 SQL 注入、XSS 攻击
-   文件上传必须验证类型和大小
-   敏感数据必须加密存储

### 3. 错误处理

-   不能暴露系统内部错误信息
-   记录详细的错误日志
-   统一的异常处理机制

## 代码质量规范

### 1. 编码标准

-   遵循 PSR-12 编码标准
-   使用有意义的变量和方法命名
-   代码必须有适当的注释
-   避免深层嵌套，最多 3 层

### 2. 性能优化

-   使用 Eloquent 关联查询避免 N+1 问题
-   合理使用缓存机制
-   数据库查询必须使用索引
-   大数据量操作使用分页

## 监控和日志规范

### 1. 日志记录

-   记录用户操作日志
-   记录系统错误和异常
-   记录 API 访问日志
-   敏感信息不能记录到日志

### 2. 性能监控

-   监控 API 响应时间
-   监控数据库查询性能
-   监控系统资源使用情况

记住：始终优先考虑代码的可读性、可维护性和安全性。遵循 Laravel 最佳实践，保持代码简洁清晰。
