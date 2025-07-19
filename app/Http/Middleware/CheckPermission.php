<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Responses\ApiResponse;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // 检查用户是否已认证
        if (!auth('api')->check()) {
            return ApiResponse::error('未认证', 401);
        }

        echo $permission;

        // 检查用户是否有指定权限
        if (!Gate::allows()) {
            return ApiResponse::error('权限不足', 403);
        }

        return $next($request);
    }
}
