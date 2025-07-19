<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * 邮箱验证码服务
 * 
 * 使用Redis存储验证码，支持自动过期和频率限制
 */
class EmailVerificationService
{
	/**
	 * 验证码有效期（秒）
	 */
	const CODE_TTL = 300; // 5分钟

	/**
	 * 发送频率限制（秒）
	 */
	const SEND_LIMIT = 60; // 1分钟

	/**
	 * 生成并存储验证码
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @param string|null $ipAddress
	 * @return array
	 */
	public function generate(string $username, string $email, string $type = 'login', ?string $ipAddress = null): array
	{
		// 生成6位数字验证码
		$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

		// Redis键名
		$codeKey = $this->getCodeKey($username, $email, $type);
		$limitKey = $this->getLimitKey($username, $email, $type);

		// 存储验证码信息
		$data = [
			'code' => $code,
			'username' => $username,
			'email' => $email,
			'type' => $type,
			'ip_address' => $ipAddress,
			'created_at' => Carbon::now()->toISOString(),
		];

		// 存储到Redis，设置过期时间
		Redis::setex($codeKey, self::CODE_TTL, json_encode($data));

		// 设置发送频率限制
		Redis::setex($limitKey, self::SEND_LIMIT, 1);

		return [
			'code' => $code,
			'expires_in' => self::CODE_TTL,
			'created_at' => $data['created_at']
		];
	}

	/**
	 * 验证验证码
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $code
	 * @param string $type
	 * @return bool
	 */
	public function verify(string $username, string $email, string $code, string $type = 'login'): bool
	{
		$codeKey = $this->getCodeKey($username, $email, $type);

		// 从Redis获取验证码数据
		$data = Redis::get($codeKey);

		if (!$data) {
			return false;
		}

		$codeData = json_decode($data, true);

		// 验证验证码是否匹配
		if ($codeData['code'] === $code) {
			// 验证成功后删除验证码（一次性使用）
			Redis::del($codeKey);
			return true;
		}

		return false;
	}

	/**
	 * 检查是否可以发送新验证码
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @return bool
	 */
	public function canSendNew(string $username, string $email, string $type = 'login'): bool
	{
		$limitKey = $this->getLimitKey($username, $email, $type);

		// 检查是否在限制时间内
		return !Redis::exists($limitKey);
	}

	/**
	 * 获取验证码剩余有效时间
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @return int|null
	 */
	public function getTimeToLive(string $username, string $email, string $type = 'login'): ?int
	{
		$codeKey = $this->getCodeKey($username, $email, $type);

		$ttl = Redis::ttl($codeKey);

		return $ttl > 0 ? $ttl : null;
	}

	/**
	 * 获取发送限制剩余时间
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @return int|null
	 */
	public function getSendLimitTimeToLive(string $username, string $email, string $type = 'login'): ?int
	{
		$limitKey = $this->getLimitKey($username, $email, $type);

		$ttl = Redis::ttl($limitKey);

		return $ttl > 0 ? $ttl : null;
	}

	/**
	 * 清除验证码（用于管理或测试）
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @return bool
	 */
	public function clear(string $username, string $email, string $type = 'login'): bool
	{
		$codeKey = $this->getCodeKey($username, $email, $type);
		$limitKey = $this->getLimitKey($username, $email, $type);

		$result1 = Redis::del($codeKey);
		$result2 = Redis::del($limitKey);

		return $result1 || $result2;
	}

	/**
	 * 获取验证码存储键名
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @return string
	 */
	private function getCodeKey(string $username, string $email, string $type): string
	{
		return "email_verification_code:{$type}:{$username}:{$email}";
	}

	/**
	 * 获取发送限制键名
	 *
	 * @param string $username
	 * @param string $email
	 * @param string $type
	 * @return string
	 */
	private function getLimitKey(string $username, string $email, string $type): string
	{
		return "email_verification_limit:{$type}:{$username}:{$email}";
	}
}
