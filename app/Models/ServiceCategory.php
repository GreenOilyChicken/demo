<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * 服务分类模型
 * 
 * @property int $id
 * @property string $name
 * @property int $parent_id
 * @property int $level
 * @property int $sort_order
 * @property bool $is_enabled
 * @property string|null $icon
 * @property string|null $description
 * @property bool $is_del
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ServiceCategory extends Model
{
	use HasFactory;

	/**
	 * 数据表名称
	 *
	 * @var string
	 */
	protected $table = 'service_category';

	/**
	 * 可批量赋值的字段
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'name',
		'parent_id',
		'level',
		'sort_order',
		'is_enabled',
		'icon',
		'description',
	];

	/**
	 * 字段类型转换
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'parent_id' => 'integer',
		'level' => 'integer',
		'sort_order' => 'integer',
		'is_enabled' => 'boolean',
		'is_del' => 'boolean',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	/**
	 * 默认属性值
	 *
	 * @var array
	 */
	protected $attributes = [
		'parent_id' => 0,
		'level' => 1,
		'sort_order' => 0,
		'is_enabled' => true,
		'is_del' => false,
	];

	/**
	 * 全局查询作用域 - 排除软删除的记录
	 *
	 * @return void
	 */
	protected static function booted()
	{
		static::addGlobalScope('notDeleted', function (Builder $builder) {
			$builder->where('is_del', false);
		});
	}

	/**
	 * 父级分类关联
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function parent()
	{
		return $this->belongsTo(ServiceCategory::class, 'parent_id');
	}

	/**
	 * 子级分类关联
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function children()
	{
		return $this->hasMany(ServiceCategory::class, 'parent_id')
			->orderBy('sort_order', 'asc')
			->orderBy('id', 'asc');
	}

	/**
	 * 递归获取所有子级分类
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function childrenRecursive()
	{
		return $this->children()->with('childrenRecursive');
	}

	/**
	 * 查询作用域 - 启用的分类
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeEnabled(Builder $query): Builder
	{
		return $query->where('is_enabled', true);
	}

	/**
	 * 查询作用域 - 顶级分类
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeTopLevel(Builder $query): Builder
	{
		return $query->where('parent_id', 0);
	}

	/**
	 * 查询作用域 - 按层级查询
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param int $level
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeByLevel(Builder $query, int $level): Builder
	{
		return $query->where('level', $level);
	}

	/**
	 * 查询作用域 - 包含软删除的记录
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeWithDeleted(Builder $query): Builder
	{
		return $query->withoutGlobalScope('notDeleted');
	}

	/**
	 * 查询作用域 - 只查询软删除的记录
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeOnlyDeleted(Builder $query): Builder
	{
		return $query->withoutGlobalScope('notDeleted')->where('is_del', true);
	}

	/**
	 * 软删除
	 *
	 * @return bool
	 */
	public function softDelete(): bool
	{
		return $this->update(['is_del' => true]);
	}

	/**
	 * 恢复软删除
	 *
	 * @return bool
	 */
	public function restore(): bool
	{
		return $this->update(['is_del' => false]);
	}

	/**
	 * 检查是否为顶级分类
	 *
	 * @return bool
	 */
	public function isTopLevel(): bool
	{
		return $this->parent_id === 0;
	}

	/**
	 * 检查是否有子分类
	 *
	 * @return bool
	 */
	public function hasChildren(): bool
	{
		return $this->children()->count() > 0;
	}

	/**
	 * 获取所有祖先分类ID
	 *
	 * @return array
	 */
	public function getAncestorIds(): array
	{
		$ancestors = [];
		$current = $this->parent;

		while ($current) {
			$ancestors[] = $current->id;
			$current = $current->parent;
		}

		return array_reverse($ancestors);
	}

	/**
	 * 获取所有后代分类ID
	 *
	 * @return array
	 */
	public function getDescendantIds(): array
	{
		$descendants = [];

		foreach ($this->children as $child) {
			$descendants[] = $child->id;
			$descendants = array_merge($descendants, $child->getDescendantIds());
		}

		return $descendants;
	}
}
