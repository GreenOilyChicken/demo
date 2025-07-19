<?php

namespace App\Http\Requests\ServiceCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;
use App\Models\ServiceCategory;

class StoreRequest extends FormRequest
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
			'name' => [
				'required',
				'string',
				'max:50',
				'unique:service_category,name,NULL,id,is_del,0'
			],
			'parent_id' => [
				'nullable',
				'integer',
				'min:0',
				'exists:service_category,id,is_del,0'
			],
			'sort_order' => [
				'nullable',
				'integer',
				'min:0'
			],
			'is_enabled' => [
				'nullable',
				'boolean'
			],
			'icon' => [
				'nullable',
				'string',
				'max:100',
				'url'
			],
			'description' => [
				'nullable',
				'string',
				'max:255'
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
			'name.required' => '分类名称不能为空',
			'name.string' => '分类名称必须是字符串',
			'name.max' => '分类名称最多50个字符',
			'name.unique' => '分类名称已存在',

			'parent_id.integer' => '父级分类ID必须是整数',
			'parent_id.min' => '父级分类ID不能小于0',
			'parent_id.exists' => '父级分类不存在',

			'sort_order.integer' => '排序值必须是整数',
			'sort_order.min' => '排序值不能小于0',

			'is_enabled.boolean' => '启用状态必须是布尔值',

			'icon.string' => '图标URL必须是字符串',
			'icon.max' => '图标URL最多100个字符',
			'icon.url' => '图标URL格式不正确',

			'description.string' => '描述必须是字符串',
			'description.max' => '描述最多255个字符',
		];
	}

	/**
	 * 配置验证器实例
	 */
	public function withValidator($validator)
	{
		$validator->after(function ($validator) {
			$parentId = $this->input('parent_id', 0);

			// 如果指定了父级分类，验证层级关系
			if ($parentId > 0) {
				$parent = ServiceCategory::find($parentId);
				if ($parent) {
					// 检查层级限制（最多3级）
					if ($parent->level >= 3) {
						$validator->errors()->add('parent_id', '分类层级不能超过3级');
					}

					// 检查父级分类是否启用
					if (!$parent->is_enabled) {
						$validator->errors()->add('parent_id', '父级分类未启用，无法在其下创建子分类');
					}
				}
			}
		});
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
			ApiResponse::error(
				'数据验证失败',
				422,
				$validator->errors()->toArray()
			)
		);
	}

	/**
	 * 准备验证数据
	 */
	protected function prepareForValidation()
	{
		// 如果没有指定parent_id，默认为0（顶级分类）
		if (!$this->has('parent_id')) {
			$this->merge(['parent_id' => 0]);
		}

		// 如果没有指定sort_order，默认为0
		if (!$this->has('sort_order')) {
			$this->merge(['sort_order' => 0]);
		}

		// 如果没有指定is_enabled，默认为true
		if (!$this->has('is_enabled')) {
			$this->merge(['is_enabled' => true]);
		}
	}
}
