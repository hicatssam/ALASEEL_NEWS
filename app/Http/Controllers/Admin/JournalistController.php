<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journalist;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
class JournalistController extends Controller
{
    public function index(Request $request)
    {
        $query = Journalist::withCount('articles')->latest();
        if ($request->filled('search')) $query->where('name','like','%'.$request->search.'%');
        if ($request->filled('status')) $query->where('status', (bool)$request->status);
        $journalists = $query->paginate(20)->withQueryString();
        return view('admin.journalists.index', compact('journalists'));
    }

    public function create()
    {
        return view('admin.journalists.create');
    }

  public function store(Request $request)
{
   $validated = $request->validate([
    'name' => [
        'required',
        'string',
        'min:3',
        'max:255',
    ],

    'email' => [
        'required',
        'email:rfc',
        'max:255',
        Rule::unique('journalists', 'email'),
    ],

    'phone' => [
        'required',
        'string',
        'regex:/^[0-9+\-\s()]{7,20}$/',
    ],

    'job_title' => [
        'required',
        'string',
        'min:2',
        'max:255',
    ],

    'bio' => [
        'required',
        'string',
        'min:20',
        'max:5000',
    ],

    'photo_file' => [
        'nullable',
        'required_without:photo_url',
        'image',
        'mimes:jpg,jpeg,png,webp',
        'max:5120',
    ],

    'photo_url' => [
        'nullable',
        'required_without:photo_file',
        'url:http,https',
        'max:2048',
    ],

    'facebook' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'instagram' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'youtube' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'x_twitter' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'status' => [
        'nullable',
        'boolean',
    ],
], [
    'name.required' => 'اسم الصحفي مطلوب.',
    'name.min' => 'يجب ألا يقل اسم الصحفي عن 3 أحرف.',

    'email.required' => 'البريد الإلكتروني مطلوب.',
    'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
    'email.unique' => 'البريد الإلكتروني مستخدم لصحفي آخر.',

    'phone.required' => 'رقم الهاتف مطلوب.',
    'phone.regex' => 'صيغة رقم الهاتف غير صحيحة.',

    'job_title.required' => 'المسمى الوظيفي مطلوب.',

    'bio.required' => 'النبذة التعريفية مطلوبة.',
    'bio.min' => 'يجب ألا تقل النبذة التعريفية عن 20 حرفًا.',

    'photo_file.required_without' =>
        'يجب رفع صورة للصحفي أو إدخال رابط الصورة.',

    'photo_file.image' => 'الملف المرفوع يجب أن يكون صورة.',
    'photo_file.mimes' =>
        'يجب أن تكون الصورة بصيغة JPG أو JPEG أو PNG أو WEBP.',

    'photo_file.max' => 'يجب ألا يتجاوز حجم الصورة 5 ميجابايت.',

    'photo_url.required_without' =>
        'يجب إدخال رابط الصورة أو رفع صورة من الجهاز.',

    'photo_url.url' => 'رابط الصورة غير صحيح.',

    'facebook.url' => 'رابط فيسبوك غير صحيح.',
    'instagram.url' => 'رابط إنستغرام غير صحيح.',
    'youtube.url' => 'رابط يوتيوب غير صحيح.',
    'x_twitter.url' => 'رابط منصة X غير صحيح.',
]);


    $photo = $validated['photo_url'] ?? null;

    if ($request->hasFile('photo_file')) {
        $photo = $request
            ->file('photo_file')
            ->store('journalists', 'public');
    }

    Journalist::create([
        'user_id' => auth()->id(),
        'name' => $validated['name'],
        'email' => $validated['email'] ?? null,
        'phone' => $validated['phone'] ?? null,
        'photo' => $photo,
        'job_title' => $validated['job_title'] ?? null,
        'bio' => $validated['bio'] ?? null,
        'facebook' => $validated['facebook'] ?? null,
        'instagram' => $validated['instagram'] ?? null,
        'youtube' => $validated['youtube'] ?? null,
        'x_twitter' => $validated['x_twitter'] ?? null,
        'status' => $request->boolean('status'),
    ]);

    return redirect()
        ->route('admin.journalists.index')
        ->with('success', 'تمت إضافة الصحفي بنجاح.');
}


    public function show(Journalist $journalist)
    {
        $articles = $journalist->articles()->with('category')->latest()->paginate(15);
        return view('admin.journalists.show', compact('journalist','articles'));
    }

    public function edit(Journalist $journalist)
    {
        return view('admin.journalists.edit', compact('journalist'));
    }

    public function update(Request $request, Journalist $journalist)
{
    $validated = $request->validate([
    'name' => [
        'required',
        'string',
        'min:3',
        'max:255',
    ],

   'email' => [
    'required',
    'email:rfc',
    'max:255',
    Rule::unique('journalists', 'email')->ignore($journalist->id),
],

    'phone' => [
        'required',
        'string',
        'regex:/^[0-9+\-\s()]{7,20}$/',
    ],

    'job_title' => [
        'required',
        'string',
        'min:2',
        'max:255',
    ],

    'bio' => [
        'required',
        'string',
        'min:20',
        'max:5000',
    ],

    'photo_file' => [
        'nullable',
        'required_without:photo_url',
        'image',
        'mimes:jpg,jpeg,png,webp',
        'max:5120',
    ],

    'photo_url' => [
        'nullable',
        'required_without:photo_file',
        'url:http,https',
        'max:2048',
    ],

    'facebook' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'instagram' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'youtube' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'x_twitter' => [
        'nullable',
        'url:http,https',
        'max:2048',
    ],

    'status' => [
        'nullable',
        'boolean',
    ],
], [
    'name.required' => 'اسم الصحفي مطلوب.',
    'name.min' => 'يجب ألا يقل اسم الصحفي عن 3 أحرف.',

    'email.required' => 'البريد الإلكتروني مطلوب.',
    'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
    'email.unique' => 'البريد الإلكتروني مستخدم لصحفي آخر.',

    'phone.required' => 'رقم الهاتف مطلوب.',
    'phone.regex' => 'صيغة رقم الهاتف غير صحيحة.',

    'job_title.required' => 'المسمى الوظيفي مطلوب.',

    'bio.required' => 'النبذة التعريفية مطلوبة.',
    'bio.min' => 'يجب ألا تقل النبذة التعريفية عن 20 حرفًا.',

    'photo_file.required_without' =>
        'يجب رفع صورة للصحفي أو إدخال رابط الصورة.',

    'photo_file.image' => 'الملف المرفوع يجب أن يكون صورة.',
    'photo_file.mimes' =>
        'يجب أن تكون الصورة بصيغة JPG أو JPEG أو PNG أو WEBP.',

    'photo_file.max' => 'يجب ألا يتجاوز حجم الصورة 5 ميجابايت.',

    'photo_url.required_without' =>
        'يجب إدخال رابط الصورة أو رفع صورة من الجهاز.',

    'photo_url.url' => 'رابط الصورة غير صحيح.',

    'facebook.url' => 'رابط فيسبوك غير صحيح.',
    'instagram.url' => 'رابط إنستغرام غير صحيح.',
    'youtube.url' => 'رابط يوتيوب غير صحيح.',
    'x_twitter.url' => 'رابط منصة X غير صحيح.',
]);
    // الاحتفاظ بالصورة الحالية افتراضيًا
    $photo = $journalist->photo;

    // الأولوية للصورة المرفوعة من الجهاز
    if ($request->hasFile('photo_file')) {
        // حذف الصورة المحلية القديمة فقط
        if (
            $journalist->photo &&
            !filter_var($journalist->photo, FILTER_VALIDATE_URL)
        ) {
            $oldPhoto = preg_replace(
                '#^(public/|storage/)#',
                '',
                $journalist->photo
            );

            if (Storage::disk('public')->exists($oldPhoto)) {
                Storage::disk('public')->delete($oldPhoto);
            }
        }

        $photo = $request
            ->file('photo_file')
            ->store('journalists', 'public');
    } elseif ($request->filled('photo_url')) {
        // استخدام الرابط فقط إذا لم تُرفع صورة من الجهاز
        if (
            $journalist->photo &&
            !filter_var($journalist->photo, FILTER_VALIDATE_URL)
        ) {
            $oldPhoto = preg_replace(
                '#^(public/|storage/)#',
                '',
                $journalist->photo
            );

            if (Storage::disk('public')->exists($oldPhoto)) {
                Storage::disk('public')->delete($oldPhoto);
            }
        }

        $photo = $validated['photo_url'];
    }

    $journalist->update([
        'name' => $validated['name'],
        'email' => $validated['email'] ?? null,
        'phone' => $validated['phone'] ?? null,
        'photo' => $photo,
        'job_title' => $validated['job_title'] ?? null,
        'bio' => $validated['bio'] ?? null,
        'facebook' => $validated['facebook'] ?? null,
        'instagram' => $validated['instagram'] ?? null,
        'youtube' => $validated['youtube'] ?? null,
        'x_twitter' => $validated['x_twitter'] ?? null,
        'status' => $request->boolean('status'),
    ]);

    ActivityLog::log(
        'update',
        'journalists',
        "Updated journalist: {$journalist->name}"
    );

    return redirect()
        ->route('admin.journalists.index')
        ->with('success', 'تم تحديث بيانات الصحفي بنجاح.');
}

    public function destroy(Journalist $journalist)
    {
        ActivityLog::log('delete','journalists',"Deleted journalist: {$journalist->name}");
        $journalist->delete();
        return redirect()->route('admin.journalists.index')->with('success','تم حذف الصحفي بنجاح.');
    }
}
