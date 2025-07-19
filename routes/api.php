<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 认证相关路由（无需认证）
Route::prefix('auth')->group(function () {
    // 用户注册
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');

    // 用户名密码登录
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');

    // 邮箱验证码登录
    Route::post('email-login', [AuthController::class, 'emailLogin'])->name('api.auth.email-login');

    // 发送邮箱验证码
    Route::post('send-email-code', [AuthController::class, 'sendEmailCode'])->name('api.auth.send-email-code');

    // 检查用户名是否可用
    Route::get('check-username/{username}', [AuthController::class, 'checkUsername'])->name('api.auth.check-username');
});

// 需要JWT认证的路由
Route::middleware('auth:api')->group(function () {
    // 获取当前用户信息
    Route::get('user/me', [AuthController::class, 'me'])->name('api.user.me');

    // 用户登出
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    // 刷新Token
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    // 服务分类管理路由（需要manage-services权限）
    Route::prefix('service-categories')->name('api.service-categories.')->group(function () {
        // 基础CRUD操作
        Route::get('/', [ServiceCategoryController::class, 'index'])->name('index');
        Route::post('/', [ServiceCategoryController::class, 'store'])->name('store');
        Route::get('/{service_category}', [ServiceCategoryController::class, 'show'])->name('show');
        Route::put('/{service_category}', [ServiceCategoryController::class, 'update'])->name('update');
        Route::delete('/{service_category}', [ServiceCategoryController::class, 'destroy'])->name('destroy');

        // 批量操作
        Route::post('/batch-delete', [ServiceCategoryController::class, 'batchDestroy'])->name('batch-destroy');

        // 状态管理
        Route::put('/{service_category}/status', [ServiceCategoryController::class, 'toggleStatus'])->name('toggle-status');

        // 软删除恢复
        Route::post('/{service_category}/restore', [ServiceCategoryController::class, 'restore'])->name('restore');
    });

    // 权限测试相关路由
    Route::prefix('test')->group(function () {
        // 分配角色（超级管理员权限）
        Route::post('assign-role', [AuthController::class, 'assignRole'])
            ->middleware('role:super-admin')
            ->name('api.test.assign-role');

        // 获取用户权限信息
        Route::get('permissions', [AuthController::class, 'getUserPermissions'])
            ->name('api.test.permissions');

        // 管理员专用接口
        Route::get('admin-only', [AuthController::class, 'adminOnly'])
            ->middleware('role:admin|super-admin')
            ->name('api.test.admin-only');

        // 用户管理接口（需要管理用户权限）
        Route::get('manage-users', [AuthController::class, 'manageUsers'])
            ->middleware('permission:manage-users')
            ->name('api.test.manage-users');

        // // 服务管理接口（需要管理服务权限）
        // Route::get('manage-services', [AuthController::class, 'manageServices'])
        //     ->middleware('permission:manage-services')
        //     ->name('api.test.manage-services');
    });
});
