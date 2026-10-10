<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'إدارة التنبيهات العاجلة', 'slug' => 'manage-breaking-alerts', 'module' => 'content'],
            ['name' => 'إدارة الوسوم', 'slug' => 'manage-tags', 'module' => 'content'],
            ['name' => 'إدارة الصحفيين', 'slug' => 'manage-journalists', 'module' => 'content'],
            ['name' => 'إدارة الفيديوهات', 'slug' => 'manage-videos', 'module' => 'content'],
            ['name' => 'إدارة التعليقات', 'slug' => 'manage-comments', 'module' => 'content'],
            ['name' => 'إدارة البث المباشر', 'slug' => 'manage-live-streams', 'module' => 'content'],
            ['name' => 'إدارة من نحن والفريق', 'slug' => 'manage-about', 'module' => 'settings'],
            ['name' => 'إدارة رسائل التواصل', 'slug' => 'manage-contact', 'module' => 'communication'],
            ['name' => 'إدارة النشرة البريدية', 'slug' => 'manage-newsletter', 'module' => 'communication'],
            ['name' => 'عرض سجل النشاط', 'slug' => 'view-activity-logs', 'module' => 'reports'],
            ['name' => 'إدارة الإشعارات', 'slug' => 'manage-notifications', 'module' => 'communication'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $adminRoleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin'])
            ->pluck('id');
        $allPermissionIds = DB::table('permissions')->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            foreach ($allPermissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $editorRoleId = DB::table('roles')->where('slug', 'editor')->value('id');
        $editorSlugs = [
            'manage-breaking-alerts', 'manage-tags', 'manage-journalists',
            'manage-videos', 'manage-comments', 'manage-live-streams',
            'manage-about', 'manage-ads',
        ];

        if ($editorRoleId) {
            foreach (DB::table('permissions')->whereIn('slug', $editorSlugs)->pluck('id') as $permissionId) {
                DB::table('permission_role')->updateOrInsert([
                    'role_id' => $editorRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep permissions to avoid silently revoking production access on rollback.
    }
};
