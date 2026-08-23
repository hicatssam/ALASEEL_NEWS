<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            ['name' => 'إدارة القصص', 'slug' => 'manage-stories', 'module' => 'content'],
            ['name' => 'إدارة التقارير', 'slug' => 'manage-reports', 'module' => 'content'],
            ['name' => 'إدارة الآراء', 'slug' => 'manage-opinions', 'module' => 'content'],
            ['name' => 'إدارة الأدوار والصلاحيات', 'slug' => 'manage-roles', 'module' => 'users'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['super-admin', 'editor'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', collect($permissions)->pluck('slug'))->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        $slugs = ['manage-stories', 'manage-reports', 'manage-opinions', 'manage-roles'];
        $ids = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
