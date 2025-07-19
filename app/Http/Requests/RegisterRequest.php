<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;

class RegisterRequest extends FormRequest
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
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:users,username'
            ],
            'name' => [
                'required',
                'string',
                'max:50'
            ],
            'email' => [
                'nullable',
                'email',
                'max:255'
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^1[3-9]\d{9}$/'
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:20',
                'confirmed'
            ],
            'password_confirmation' => [
                'required',
                'string'
            ]
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'username.required' => '用户名不能为空',
            'username.min' => '用户名至少3个字符',
            'username.max' => '用户名最多20个字符',
            'username.regex' => '用户名只能包含字母、数字和下划线',
            'username.unique' => '用户名已存在',

            'name.required' => '姓名不能为空',
            'name.max' => '姓名最多50个字符',

            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱最多255个字符',

            'phone.regex' => '手机号格式不正确',

            'password.required' => '密码不能为空',
            'password.min' => '密码至少6个字符',
            'password.max' => '密码最多20个字符',
            'password.confirmed' => '两次密码输入不一致',

            'password_confirmation.required' => '确认密码不能为空',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors()->toArray(),
                '数据验证失败'
            )
        );
    }
}
