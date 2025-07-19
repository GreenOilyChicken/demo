<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;

/**
 * API 统一响应类
 * 
 * 提供统一的 JSON 响应格式，包括成功、错误、分页等响应
 */
class ApiResponse
{
	/**
	 * 成功响应
	 *
	 * @param mixed $data 响应数据
	 * @param string $message 响应消息
	 * @return JsonResponse
	 */
	public static function success($data = null, string $message = '操作成功'): JsonResponse
	{
		return response()->json([
			'success' => true,
			'data' => $data,
			'message' => $message,
			'code' => Response::HTTP_OK
		], Response::HTTP_OK);
	}

	/**
	 * 错误响应
	 *
	 * @param string $error 错误信息
	 * @param int $code HTTP状态码
	 * @param array $details 错误详情
	 * @return JsonResponse
	 */
	public static function error(string $error, int $code = Response::HTTP_BAD_REQUEST, array $details = []): JsonResponse
	{
		$response = [
			'success' => false,
			'error' => $error,
			'code' => $code
		];

		if (!empty($details)) {
			$response['details'] = $details;
		}

		return response()->json($response, $code);
	}

	/**
	 * 分页响应
	 *
	 * @param LengthAwarePaginator $paginator 分页对象
	 * @param string $message 响应消息
	 * @return JsonResponse
	 */
	public static function paginate(LengthAwarePaginator $paginator, string $message = '获取成功'): JsonResponse
	{
		return response()->json([
			'success' => true,
			'data' => [
				'items' => $paginator->items(),
				'pagination' => [
					'current_page' => $paginator->currentPage(),
					'last_page' => $paginator->lastPage(),
					'per_page' => $paginator->perPage(),
					'total' => $paginator->total(),
					'from' => $paginator->firstItem(),
					'to' => $paginator->lastItem(),
					'has_more_pages' => $paginator->hasMorePages(),
				]
			],
			'message' => $message,
			'code' => Response::HTTP_OK
		], Response::HTTP_OK);
	}

	/**
	 * 创建响应
	 *
	 * @param mixed $data 响应数据
	 * @param string $message 响应消息
	 * @return JsonResponse
	 */
	public static function created($data = null, string $message = '创建成功'): JsonResponse
	{
		return self::success($data, $message);
	}

	/**
	 * 无内容响应
	 *
	 * @param string $message 响应消息
	 * @return JsonResponse
	 */
	public static function noContent(string $message = '操作成功'): JsonResponse
	{
		return response()->json([
			'success' => true,
			'data' => null,
			'message' => $message,
			'code' => Response::HTTP_OK
		], Response::HTTP_OK);
	}

	/**
	 * 未找到响应 (404)
	 *
	 * @param string $message 错误信息
	 * @return JsonResponse
	 */
	public static function notFound(string $message = '资源未找到'): JsonResponse
	{
		return self::error($message, Response::HTTP_NOT_FOUND);
	}

	/**
	 * 未授权响应 (401)
	 *
	 * @param string $message 错误信息
	 * @return JsonResponse
	 */
	public static function unauthorized(string $message = '未授权访问'): JsonResponse
	{
		return self::error($message, Response::HTTP_UNAUTHORIZED);
	}

	/**
	 * 禁止访问响应 (403)
	 *
	 * @param string $message 错误信息
	 * @return JsonResponse
	 */
	public static function forbidden(string $message = '禁止访问'): JsonResponse
	{
		return self::error($message, Response::HTTP_FORBIDDEN);
	}

	/**
	 * 验证失败响应 (422)
	 *
	 * @param array $errors 验证错误详情
	 * @param string $message 错误信息
	 * @return JsonResponse
	 */
	public static function validationError(array $errors, string $message = '数据验证失败'): JsonResponse
	{
		return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
	}

	/**
	 * 服务器错误响应 (500)
	 *
	 * @param string $message 错误信息
	 * @return JsonResponse
	 */
	public static function serverError(string $message = '服务器内部错误'): JsonResponse
	{
		return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
	}
}
