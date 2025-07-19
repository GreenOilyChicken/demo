<?php

namespace App\Http\Requests\ServiceCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Responses\ApiResponse;
use App\Models\ServiceCategory;

class UpdateRequest extends FormRequest
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
		$categoryId = $this->route('service_category');

		return [
			'name' => [
				'sometimes',
				'required',
				'string',
				'max:50',
				"unique:service_category,name,{$categoryId},id,is_del,0"
			],
			'parent_id' => [
				'sometimes',
				'nullable',
				'integer',
				'min:0',
				'exists:service_category,id,is_del,0'
			],
			'sort_order' => [
				'sometimes',
				'nullable',
				'integer',
				'min:0'
			],
			'is_enabled' => [
				'sometimes',
				'nullable',
				'boolean'
			],
			'icon' => [
				'sometimes',
				'nullable',
				'string',
				'max:100',
				'url'
			],
			'description' => [
				'sometimes',
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
			$categoryId = $this->route('service_category');
			$parentId = $this->input('parent_id');

			// 如果指定了父级分类，验证层级关系
			if ($parentId !== null && $parentId > 0) {
				$parent = ServiceCategory::find($parentId);
				$currentCategory = ServiceCategory::find($categoryId);

				if ($parent && $currentCategory) {
					// 不能将自己设为父级
					if ($parentId == $categoryId) {
						$validator->errors()->add('parent_id', '不能将自己设为父级分类');
						return;
					}

					// 不能将自己的子孙设为父级（避免循环引用）
					$descendantIds = $currentCategory->getDescendantIds();
					if (in_array($parentId, $descendantIds)) {
						$validator->errors()->add('parent_id', '不能将子级分类设为父级分类');
						return;
					}

					// 检查层级限制（最多3级）
					if ($parent->level >= 3) {
						$validator->errors()->add('parent_id', '分类层级不能超过3级');
					}

					// 检查父级分类是否启用
					if (!$parent->is_enabled) {
						$validator->errors()->add('parent_id', '父级分类未启用，无法设为父级');
					}

					// 如果修改了父级，检查是否会导致子分类层级超限
					if ($currentCategory->hasChildren()) {
						$newLevel = $parent->level + 1;
						$maxChildLevel = $this->getMaxChildLevel($currentCategory);

						if ($newLevel + $maxChildLevel - $currentCategory->level > 3) {
							$validator->errors()->add('parent_id', '修改父级分类会导致子分类层级超过3级限制');
						}
					}
				}
			}

			// 如果要禁用分类，检查是否有启用的子分类
			if ($this->has('is_enabled') && !$this->input('is_enabled')) {
				$currentCategory = ServiceCategory::find($categoryId);
				if ($currentCategory && $currentCategory->children()->where('is_enabled', true)->exists()) {
					$validator->errors()->add('is_enabled', '该分类下存在启用的子分类，无法禁用');
				}
			}
		});
	}

	/**
	 * 获取分类下最深的子分类层级
	 *
	 * @param ServiceCategory $category
	 * @return int
	 */
	private function getMaxChildLevel(ServiceCategory $category): int
	{
		$maxLevel = $category->level;

		foreach ($category->children as $child) {
			$childMaxLevel = $this->getMaxChildLevel($child);
			if ($childMaxLevel > $maxLevel) {
				$maxLevel = $childMaxLevel;
			}
		}

		return $maxLevel;
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
}
