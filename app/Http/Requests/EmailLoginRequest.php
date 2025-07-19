<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailLoginRequest extends FormRequest
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
            'code' => 'required|string|size:6',
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
            'code.required' => '验证码不能为空',
            'code.string' => '验证码必须是字符串',
            'code.size' => '验证码必须是6位',
        ];
    }
}
