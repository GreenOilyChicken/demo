<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 重置缓存的角色和权限
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 创建权限
        $permissions = [
            // 用户管理
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // 家政服务管理
            'manage-services',
            'view-services',
            'create-services',
            'edit-services',
            'delete-services',

            // 订单管理
            'manage-orders',
            'view-orders',
            'create-orders',
            'edit-orders',
            'delete-orders',
            'assign-orders',

            // 评价管理
            'manage-reviews',
            'view-reviews',
            'edit-reviews',
            'delete-reviews',

            // 财务管理
            'manage-finances',
            'view-finances',

            // 系统设置
            'manage-settings',
            'view-analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 创建角色并分配权限

        // 超级管理员 - 拥有所有权限
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 管理员 - 拥有大部分权限
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'manage-users',
            'view-users',
            'create-users',
            'edit-users',
            'manage-services',
            'view-services',
            'create-services',
            'edit-services',
            'manage-orders',
            'view-orders',
            'create-orders',
            'edit-orders',
            'assign-orders',
            'manage-reviews',
            'view-reviews',
            'edit-reviews',
            'view-finances',
            'view-analytics',
        ]);

        // 家政服务员 - 主要处理订单
        $housekeeper = Role::create(['name' => 'housekeeper']);
        $housekeeper->givePermissionTo([
            'view-orders',
            'edit-orders',
            'view-services',
        ]);

        // 普通用户 - 基本权限
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view-services',
            'create-orders',
            'view-orders',
        ]);

        // 客服 - 处理用户问题
        $support = Role::create(['name' => 'support']);
        $support->givePermissionTo([
            'view-users',
            'view-orders',
            'edit-orders',
            'view-reviews',
            'edit-reviews',
        ]);

        echo "角色和权限创建完成！\n";
        echo "创建的角色：super-admin, admin, housekeeper, user, support\n";
        echo "创建的权限：" . count($permissions) . " 个权限\n";
    }
}
