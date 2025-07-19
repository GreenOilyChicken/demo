<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceCategory\StoreRequest;
use App\Http\Requests\ServiceCategory\UpdateRequest;
use App\Http\Responses\ApiResponse;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 服务分类控制器
 * 
 * 处理服务分类的增删改查功能
 */
class ServiceCategoryController extends Controller
{
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$this->middleware('auth:api');
		$this->middleware('permission:manage-services');
	}

	/**
	 * 获取分类列表（树形结构）
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function index(Request $request): JsonResponse
	{
		try {
			// 获取查询参数
			$includeDisabled = $request->boolean('include_disabled', false);
			$onlyTopLevel = $request->boolean('only_top_level', false);
			$level = $request->input('level');

			// 构建查询
			$query = ServiceCategory::query();

			// 是否包含禁用的分类
			if (!$includeDisabled) {
				$query->enabled();
			}

			// 是否只查询顶级分类
			if ($onlyTopLevel) {
				$query->topLevel();
			}

			// 按层级查询
			if ($level) {
				$query->byLevel((int)$level);
			}

			// 获取数据并排序
			$categories = $query->orderBy('sort_order', 'asc')
				->orderBy('id', 'asc')
				->get();

			// 构建树形结构
			$tree = $this->buildTree($categories);

			return ApiResponse::success([
				'categories' => $tree,
				'total' => $categories->count()
			], '获取分类列表成功');
		} catch (\Exception $e) {
			return ApiResponse::error('获取分类列表失败', 500);
		}
	}

	/**
	 * 获取单个分类详情
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	public function show(int $id): JsonResponse
	{
		try {
			$category = ServiceCategory::with(['parent', 'children'])->find($id);

			if (!$category) {
				return ApiResponse::error('分类不存在', 404);
			}

			return ApiResponse::success([
				'category' => $this->formatCategoryDetail($category)
			], '获取分类详情成功');
		} catch (\Exception $e) {
			return ApiResponse::error('获取分类详情失败', 500);
		}
	}

	/**
	 * 创建分类
	 *
	 * @param StoreRequest $request
	 * @return JsonResponse
	 */
	public function store(StoreRequest $request): JsonResponse
	{
		try {
			DB::beginTransaction();

			$data = $request->validated();

			// 计算层级
			if ($data['parent_id'] > 0) {
				$parent = ServiceCategory::find($data['parent_id']);
				$data['level'] = $parent->level + 1;
			} else {
				$data['level'] = 1;
			}

			// 创建分类
			$category = ServiceCategory::create($data);

			DB::commit();

			return ApiResponse::created([
				'category' => $this->formatCategoryDetail($category)
			], '创建分类成功');
		} catch (\Exception $e) {
			DB::rollBack();
			return ApiResponse::error('创建分类失败', 500);
		}
	}

	/**
	 * 更新分类
	 *
	 * @param UpdateRequest $request
	 * @param int $id
	 * @return JsonResponse
	 */
	public function update(UpdateRequest $request, int $id): JsonResponse
	{
		try {
			$category = ServiceCategory::find($id);

			if (!$category) {
				return ApiResponse::error('分类不存在', 404);
			}

			DB::beginTransaction();

			$data = $request->validated();

			// 如果修改了父级分类，需要重新计算层级
			if (isset($data['parent_id']) && $data['parent_id'] != $category->parent_id) {
				if ($data['parent_id'] > 0) {
					$parent = ServiceCategory::find($data['parent_id']);
					$newLevel = $parent->level + 1;
				} else {
					$newLevel = 1;
				}

				$data['level'] = $newLevel;

				// 更新所有子分类的层级
				$this->updateChildrenLevel($category, $newLevel - $category->level);
			}

			// 更新分类
			$category->update($data);

			DB::commit();

			return ApiResponse::success([
				'category' => $this->formatCategoryDetail($category->fresh())
			], '更新分类成功');
		} catch (\Exception $e) {
			DB::rollBack();
			return ApiResponse::error('更新分类失败', 500);
		}
	}

	/**
	 * 删除分类（软删除）
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	public function destroy(int $id): JsonResponse
	{
		try {
			$category = ServiceCategory::find($id);

			if (!$category) {
				return ApiResponse::error('分类不存在', 404);
			}

			// 检查是否有子分类
			if ($category->hasChildren()) {
				return ApiResponse::error('该分类下存在子分类，无法删除', 400);
			}

			DB::beginTransaction();

			// 软删除
			$category->softDelete();

			DB::commit();

			return ApiResponse::success([], '删除分类成功');
		} catch (\Exception $e) {
			DB::rollBack();
			return ApiResponse::error('删除分类失败', 500);
		}
	}

	/**
	 * 批量删除分类
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function batchDestroy(Request $request): JsonResponse
	{
		$request->validate([
			'ids' => 'required|array|min:1',
			'ids.*' => 'integer|exists:service_category,id,is_del,0'
		]);

		try {
			$ids = $request->input('ids');
			$categories = ServiceCategory::whereIn('id', $ids)->get();

			// 检查是否有分类存在子分类
			foreach ($categories as $category) {
				if ($category->hasChildren()) {
					return ApiResponse::error("分类\"{$category->name}\"下存在子分类，无法删除", 400);
				}
			}

			DB::beginTransaction();

			// 批量软删除
			ServiceCategory::whereIn('id', $ids)->update(['is_del' => true]);

			DB::commit();

			return ApiResponse::success([
				'deleted_count' => count($ids)
			], '批量删除分类成功');
		} catch (\Exception $e) {
			DB::rollBack();
			return ApiResponse::error('批量删除分类失败', 500);
		}
	}

	/**
	 * 启用/禁用分类
	 *
	 * @param Request $request
	 * @param int $id
	 * @return JsonResponse
	 */
	public function toggleStatus(Request $request, int $id): JsonResponse
	{
		$request->validate([
			'is_enabled' => 'required|boolean'
		]);

		try {
			$category = ServiceCategory::find($id);

			if (!$category) {
				return ApiResponse::error('分类不存在', 404);
			}

			$isEnabled = $request->boolean('is_enabled');

			// 如果要禁用分类，检查是否有启用的子分类
			if (!$isEnabled && $category->children()->where('is_enabled', true)->exists()) {
				return ApiResponse::error('该分类下存在启用的子分类，无法禁用', 400);
			}

			DB::beginTransaction();

			$category->update(['is_enabled' => $isEnabled]);

			// 如果禁用父级分类，同时禁用所有子分类
			if (!$isEnabled) {
				$this->disableAllChildren($category);
			}

			DB::commit();

			$status = $isEnabled ? '启用' : '禁用';
			return ApiResponse::success([
				'category' => $this->formatCategoryDetail($category->fresh())
			], "{$status}分类成功");
		} catch (\Exception $e) {
			DB::rollBack();
			return ApiResponse::error('操作失败', 500);
		}
	}

	/**
	 * 恢复软删除的分类
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	public function restore(int $id): JsonResponse
	{
		try {
			$category = ServiceCategory::onlyDeleted()->find($id);

			if (!$category) {
				return ApiResponse::error('分类不存在或未被删除', 404);
			}

			DB::beginTransaction();

			$category->restore();

			DB::commit();

			return ApiResponse::success([
				'category' => $this->formatCategoryDetail($category->fresh())
			], '恢复分类成功');
		} catch (\Exception $e) {
			DB::rollBack();
			return ApiResponse::error('恢复分类失败', 500);
		}
	}

	/**
	 * 构建树形结构
	 *
	 * @param \Illuminate\Database\Eloquent\Collection $categories
	 * @param int $parentId
	 * @return array
	 */
	private function buildTree($categories, int $parentId = 0): array
	{
		$tree = [];

		foreach ($categories as $category) {
			if ($category->parent_id == $parentId) {
				$categoryData = $this->formatCategory($category);
				$children = $this->buildTree($categories, $category->id);

				if (!empty($children)) {
					$categoryData['children'] = $children;
				}

				$tree[] = $categoryData;
			}
		}

		return $tree;
	}

	/**
	 * 格式化分类数据
	 *
	 * @param ServiceCategory $category
	 * @return array
	 */
	private function formatCategory(ServiceCategory $category): array
	{
		return [
			'id' => $category->id,
			'name' => $category->name,
			'parent_id' => $category->parent_id,
			'level' => $category->level,
			'sort_order' => $category->sort_order,
			'is_enabled' => $category->is_enabled,
			'icon' => $category->icon,
			'description' => $category->description,
			'created_at' => $category->created_at,
			'updated_at' => $category->updated_at,
		];
	}

	/**
	 * 格式化分类详情数据
	 *
	 * @param ServiceCategory $category
	 * @return array
	 */
	private function formatCategoryDetail(ServiceCategory $category): array
	{
		$data = $this->formatCategory($category);

		// 添加父级分类信息
		if ($category->parent) {
			$data['parent'] = [
				'id' => $category->parent->id,
				'name' => $category->parent->name,
				'level' => $category->parent->level,
			];
		}

		// 添加子分类信息
		if ($category->relationLoaded('children')) {
			$data['children_count'] = $category->children->count();
			$data['children'] = $category->children->map(function ($child) {
				return [
					'id' => $child->id,
					'name' => $child->name,
					'is_enabled' => $child->is_enabled,
				];
			});
		}

		return $data;
	}

	/**
	 * 递归更新子分类层级
	 *
	 * @param ServiceCategory $category
	 * @param int $levelDiff
	 * @return void
	 */
	private function updateChildrenLevel(ServiceCategory $category, int $levelDiff): void
	{
		if ($levelDiff == 0) {
			return;
		}

		$children = $category->children;

		foreach ($children as $child) {
			$child->update(['level' => $child->level + $levelDiff]);
			$this->updateChildrenLevel($child, $levelDiff);
		}
	}

	/**
	 * 递归禁用所有子分类
	 *
	 * @param ServiceCategory $category
	 * @return void
	 */
	private function disableAllChildren(ServiceCategory $category): void
	{
		$children = $category->children;

		foreach ($children as $child) {
			$child->update(['is_enabled' => false]);
			$this->disableAllChildren($child);
		}
	}
}
