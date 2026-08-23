<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            ['name' => 'إدارة المقالات', 'slug' => 'manage-articles', 'module' => 'content'],
            ['name' => 'إدارة القصص', 'slug' => 'manage-stories', 'module' => 'content'],
            ['name' => 'إدارة التقارير', 'slug' => 'manage-reports', 'module' => 'content'],
            ['name' => 'إدارة الآراء', 'slug' => 'manage-opinions', 'module' => 'content'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['admin', 'super-admin', 'editor'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', collect($permissions)->pluck('slug'))->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // Keep assigned permissions to avoid unexpectedly removing production access.
    }
};
