<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private const EMAIL_DOMAIN = '@alaseelnews.com';

    public function index(Request $request)
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'ends_with:' . self::EMAIL_DOMAIN,
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                $this->strongPassword(),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'status' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'size:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ], $this->validationMessages());

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')
                ->store('users', 'public');
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $request->boolean('status');

        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user = User::create($validated);
        $user->roles()->sync($roles);

        ActivityLog::log(
            'create',
            'users',
            "Created user: {$user->email}"
        );

        Notification::create([
            'title' => 'مستخدم جديد: ' . $user->name,
            'message' => 'تم تسجيل مستخدم جديد بالبريد: ' . $user->email,
            'type' => 'user',
            'action_url' => route('admin.users.edit', $user, false),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function edit(User $user)
    {
        $user->load('roles');

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'ends_with:' . self::EMAIL_DOMAIN,
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                $this->strongPassword(),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'size:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ], $this->validationMessages());

        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        if ($request->boolean('remove_photo') && $user->photo) {
            $this->deleteLocalPhoto($user->photo);
            $validated['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            $this->deleteLocalPhoto($user->photo);

            $validated['photo'] = $request->file('photo')
                ->store('users', 'public');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['status'] = $request->boolean('status');
        unset($validated['remove_photo']);

        $user->update($validated);
        $user->roles()->sync($roles);

        ActivityLog::log(
            'update',
            'users',
            "Updated user: {$user->email}"
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'لا يمكنك حذف حسابك الحالي.');
        }

        $email = $user->email;

        $this->deleteLocalPhoto($user->photo);
        $user->roles()->detach();
        $user->delete();

        ActivityLog::log(
            'delete',
            'users',
            "Deleted user: {$email}"
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح.');
    }

    public function show(User $user)
    {
        $user->load('roles');

        return view('admin.users.show', compact('user'));
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with(
                'error',
                'لا يمكنك تعطيل حسابك الحالي.'
            );
        }

        $user->update([
            'status' => ! $user->status,
        ]);

        $statusText = $user->status ? 'تفعيل' : 'تعطيل';

        ActivityLog::log(
            'update',
            'users',
            "{$statusText} المستخدم: {$user->email}"
        );

        return back()->with(
            'success',
            "تم {$statusText} المستخدم بنجاح."
        );
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                $this->strongPassword(),
            ],
        ], $this->validationMessages());

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::log(
            'update',
            'users',
            "Changed password for user: {$user->email}"
        );

        return back()->with(
            'success',
            'تم تغيير كلمة مرور المستخدم بنجاح.'
        );
    }

    private function strongPassword(): Password
    {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    private function normalizeEmail(Request $request): void
    {
        if (! $request->filled('email')) {
            return;
        }

        $request->merge([
            'email' => Str::lower(
                trim($request->string('email')->toString())
            ),
        ]);
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'اسم المستخدم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.ends_with' => 'يجب أن ينتهي البريد الإلكتروني بـ ' . self::EMAIL_DOMAIN . '.',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقًا.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 12 حرفًا.',
            'password.letters' => 'يجب أن تحتوي كلمة المرور على حرف واحد على الأقل.',
            'password.mixed' => 'يجب أن تحتوي كلمة المرور على حرف كبير وحرف صغير على الأقل.',
            'password.numbers' => 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.',
            'password.symbols' => 'يجب أن تحتوي كلمة المرور على رمز خاص واحد على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }

    private function deleteLocalPhoto(?string $photo): void
    {
        if (
            empty($photo) ||
            str_starts_with($photo, 'http://') ||
            str_starts_with($photo, 'https://')
        ) {
            return;
        }

        $photoPath = ltrim($photo, '/');
        $photoPath = preg_replace('#^storage/#', '', $photoPath);

        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }
}
