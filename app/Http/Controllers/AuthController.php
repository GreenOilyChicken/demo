<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\EmailLoginRequest;
use App\Http\Requests\SendEmailCodeRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * 认证控制器
 * 
 * 处理用户注册、登录等认证相关功能
 */
class AuthController extends Controller
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 认证中间件已在路由层面配置，此处不需要重复设置
    }

    /**
     * 用户注册
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // 创建用户
        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password, // 密码会在模型中自动加密
            'status' => 1, // 默认激活状态
        ]);

        // 返回用户信息和Token
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'created_at' => $user->created_at,
        ];

        return ApiResponse::created($userData, '注册成功');
    }

    /**
     * 用户名密码登录
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['username', 'password']);

        try {
            // 尝试使用用户名登录
            $user = User::where('username', $credentials['username'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return ApiResponse::error('用户名或密码错误', 401);
            }

            // 检查用户状态
            if (!$user->isActive()) {
                return ApiResponse::error('账户已被禁用', 403);
            }

            // 生成JWT Token
            $token = JWTAuth::fromUser($user);

            return $this->respondWithToken($token, $user, '登录成功');
        } catch (JWTException $e) {
            return ApiResponse::error('Token生成失败', 500);
        }
    }

    /**
     * 邮箱验证码登录
     *
     * @param EmailLoginRequest $request
     * @return JsonResponse
     */
    public function emailLogin(EmailLoginRequest $request): JsonResponse
    {
        $username = $request->username;
        $email = $request->email;
        $code = $request->code;

        // 验证验证码
        $emailService = new EmailVerificationService();
        if (!$emailService->verify($username, $email, $code, 'login')) {
            return ApiResponse::error('验证码错误或已过期', 400);
        }

        // 查找用户（使用用户名和邮箱双重验证）
        $user = User::where('username', $username)
            ->where('email', $email)
            ->first();

        if (!$user) {
            return ApiResponse::error('用户名和邮箱不匹配', 404);
        }

        // 检查用户状态
        if (!$user->isActive()) {
            return ApiResponse::error('账户已被禁用', 403);
        }

        try {
            // 生成JWT Token
            $token = JWTAuth::fromUser($user);

            return $this->respondWithToken($token, $user, '登录成功');
        } catch (JWTException $e) {
            return ApiResponse::error('Token生成失败', 500);
        }
    }

    /**
     * 发送邮箱验证码
     *
     * @param SendEmailCodeRequest $request
     * @return JsonResponse
     */
    public function sendEmailCode(SendEmailCodeRequest $request): JsonResponse
    {
        $username = $request->username;
        $email = $request->email;
        $type = $request->type;

        // 检查是否可以发送新验证码
        $emailService = new EmailVerificationService();
        if (!$emailService->canSendNew($username, $email, $type)) {
            $remainingTime = $emailService->getSendLimitTimeToLive($username, $email, $type);
            return ApiResponse::error("请等待{$remainingTime}秒后再试", 429);
        }

        // 生成验证码
        $verificationCode = $emailService->generate($username, $email, $type, $request->ip());

        // TODO: 这里应该发送邮件，暂时返回验证码用于测试
        // 在生产环境中，应该使用邮件服务发送验证码
        Mail::to($email)->send(new VerificationCodeMail($verificationCode['code']));

        return ApiResponse::success([
            'message' => '验证码已发送',
            'expires_in' => $verificationCode['expires_in']
        ], '验证码发送成功');
    }

    /**
     * 获取当前用户信息
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'created_at' => $user->created_at,
        ];

        return ApiResponse::success($userData, '获取用户信息成功');
    }

    /**
     * 刷新Token
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh();
            $user = auth('api')->user();

            return $this->respondWithToken($token, $user, 'Token刷新成功');
        } catch (JWTException $e) {
            return ApiResponse::error('Token刷新失败', 401);
        }
    }

    /**
     * 用户登出
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();

            return ApiResponse::success([], '登出成功');
        } catch (JWTException $e) {
            return ApiResponse::error('登出失败', 500);
        }
    }

    /**
     * 检查用户名是否可用
     *
     * @param string $username
     * @return JsonResponse
     */
    public function checkUsername(string $username): JsonResponse
    {
        $exists = User::where('username', $username)->exists();

        return ApiResponse::success([
            'available' => !$exists,
            'message' => $exists ? '用户名已存在' : '用户名可用'
        ], '检查完成');
    }

    /**
     * 统一Token响应格式
     *
     * @param string $token
     * @param User $user
     * @param string $message
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, User $user, string $message): JsonResponse
    {
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'created_at' => $user->created_at,
        ];

        return ApiResponse::success([
            'user' => $userData,
            'token' => $token,
            'token_type' => 'Bearer'
        ], $message);
    }

    /**
     * 分配角色给用户（测试用）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->assignRole($request->role);

        return ApiResponse::success([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $request->role,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ], '角色分配成功');
    }

    /**
     * 获取用户权限信息（测试用）
     *
     * @return JsonResponse
     */
    public function getUserPermissions(): JsonResponse
    {
        $user = auth('api')->user();

        return ApiResponse::success([
            'user_id' => $user->id,
            'username' => $user->username,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')
        ], '权限信息获取成功');
    }

    /**
     * 管理员专用接口（需要管理员权限）
     *
     * @return JsonResponse
     */
    public function adminOnly(): JsonResponse
    {
        return ApiResponse::success([
            'message' => '欢迎管理员！',
            'user' => auth('api')->user()->username,
            'access_time' => now()->toDateTimeString()
        ], '管理员接口访问成功');
    }

    /**
     * 用户管理接口（需要用户管理权限）
     *
     * @return JsonResponse
     */
    public function manageUsers(): JsonResponse
    {
        $users = User::select('id', 'username', 'name', 'email', 'status', 'created_at')
            ->with('roles:name')
            ->get();

        return ApiResponse::success([
            'users' => $users,
            'total' => $users->count()
        ], '用户列表获取成功');
    }

    /**
     * 服务管理接口（需要服务管理权限）
     *
     * @return JsonResponse
     */
    public function manageServices(): JsonResponse
    {
        return ApiResponse::success([
            'message' => '这是服务管理接口',
            'user' => auth('api')->user()->username,
            'permissions_required' => 'manage-services'
        ], '服务管理接口访问成功');
    }
}
