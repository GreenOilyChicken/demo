<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'password' => 'required|string|min:6',
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
            'password.required' => '密码不能为空',
            'password.string' => '密码必须是字符串',
            'password.min' => '密码至少需要6个字符',
        ];
    }
}
