<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('admin.roles.form', ['role' => new Role(), 'permissions' => $permissions]);
    }

    public function store(Request $request)
    {
        $role = Role::create($this->validated($request));
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', 'تم إنشاء الدور وتعيين صلاحياته.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('admin.roles.form', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $role->update($this->validated($request, $role));
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', 'تم تحديث الدور والصلاحيات.');
    }

    public function destroy(Role $role)
    {
        abort_if($role->slug === 'super-admin', 422, 'لا يمكن حذف دور المدير العام.');
        abort_if($role->users()->exists(), 422, 'انقل المستخدمين من هذا الدور قبل حذفه.');
        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', 'تم حذف الدور.');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        if (! $request->filled('slug') && $request->filled('name')) {
            $request->merge(['slug' => Str::slug($request->string('name')->toString())]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
        $data['status'] = $request->boolean('status');
        unset($data['permissions']);

        return $data;
    }
}
