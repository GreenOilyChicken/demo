<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendEmailCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 确保API请求返回JSON响应
     *
     * @return bool
     */
    public function wantsJson()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type' => 'required|string|in:login,reset_password',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'username.required' => '用户名不能为空',
            'username.string' => '用户名必须是字符串',
            'username.max' => '用户名不能超过255个字符',
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱不能超过255个字符',

            'type.required' => '验证码类型不能为空',
            'type.string' => '验证码类型必须是字符串',
            'type.in' => '验证码类型不正确',
        ];
    }

    /**
     * 配置验证器实例
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $username = $this->input('username');
            $email = $this->input('email');

            // 验证用户名和邮箱的组合是否存在
            $user = \App\Models\User::where('username', $username)
                ->where('email', $email)
                ->first();

            if (!$user) {
                $validator->errors()->add('user', '用户名和邮箱不匹配或用户不存在');
            }
        });
    }
}
