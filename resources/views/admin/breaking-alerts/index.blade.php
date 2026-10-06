@extends('layouts.admin')

@section('title', 'الأخبار العاجلة')

@section('breadcrumb')
    الأخبار العاجلة
@endsection

@section('content')
<div style="display:grid;gap:20px">
    <div>
        <h1 style="margin:0;font-size:24px">تنبيهات الأخبار العاجلة</h1>
        <p style="margin:6px 0 0;color:#777;font-size:13px">أضف خبرًا عاجلًا يظهر أسفل الشاشة مع صوت التنبيه، ثم يختفي تلقائيًا بعد المدة المحددة.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-inline-start:18px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><strong>إضافة خبر عاجل</strong></div>
        <div style="padding:20px">
            <form method="POST" action="{{ route('admin.breaking-alerts.store') }}" style="display:grid;gap:16px">
                @csrf

                <div>
                    <label class="form-label" for="breaking-text">نص الخبر العاجل *</label>
                    <textarea id="breaking-text" name="text" class="form-control" rows="3" maxlength="500" required placeholder="اكتب الخبر العاجل بشكل مختصر وواضح...">{{ old('text') }}</textarea>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
                    <div>
                        <label class="form-label" for="breaking-link">رابط اختياري</label>
                        <input id="breaking-link" type="url" name="link" class="form-control" value="{{ old('link') }}" placeholder="https://...">
                    </div>

                    <div>
                        <label class="form-label" for="breaking-duration">مدة الظهور *</label>
                        <select id="breaking-duration" name="duration_minutes" class="form-control" required>
                            <option value="5" @selected(old('duration_minutes', 5) == 5)>5 دقائق</option>
                            <option value="10" @selected(old('duration_minutes') == 10)>10 دقائق</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="breaking-start">موعد البداية *</label>
                        <input id="breaking-start" type="datetime-local" name="starts_at" class="form-control"
                               min="{{ now('Asia/Hebron')->addMinute()->format('Y-m-d\\TH:i') }}"
                               value="{{ old('starts_at', now('Asia/Hebron')->addMinute()->format('Y-m-d\\TH:i')) }}" required>
                        <small style="display:block;margin-top:6px;color:#888">يجب اختيار وقت بعد الوقت الحالي (بتوقيت فلسطين).</small>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:22px;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="sound_enabled" value="1" @checked(old('sound_enabled', true))>
                        تشغيل صوت العاجل عند ظهوره
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                        نشره وتفعيله
                    </label>
                </div>

                <div><button class="btn btn-primary" type="submit"><i class="fa-solid fa-bolt"></i> نشر الخبر العاجل</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>سجل الأخبار العاجلة</strong></div>
        <div style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>النص</th>
                        <th>المدة</th>
                        <th>البداية</th>
                        <th>الانتهاء</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td style="min-width:300px;max-width:520px">{{ $alert->text }}</td>
                            <td>{{ $alert->duration_minutes }} دقائق</td>
                            <td>{{ $alert->starts_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $alert->expires_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if(!$alert->is_active)
                                    <span class="badge badge-secondary">متوقف</span>
                                @elseif($alert->expires_at?->isPast())
                                    <span class="badge badge-warning">منتهي</span>
                                @elseif($alert->starts_at?->isFuture())
                                    <span class="badge badge-info">مجدول</span>
                                @else
                                    <span class="badge badge-success">نشط</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px">
                                    <form method="POST" action="{{ route('admin.breaking-alerts.update', $alert) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="text" value="{{ $alert->text }}">
                                        <input type="hidden" name="link" value="{{ $alert->link }}">
                                        <input type="hidden" name="duration_minutes" value="{{ $alert->duration_minutes }}">
                                        <input type="hidden" name="starts_at" value="{{ now('Asia/Hebron')->addMinute()->format('Y-m-d\TH:i') }}">
                                        <input type="hidden" name="sound_enabled" value="{{ $alert->sound_enabled ? 1 : 0 }}">
                                        <input type="hidden" name="is_active" value="{{ $alert->is_active ? 0 : 1 }}">
                                        <button class="btn btn-sm btn-secondary" type="submit">{{ $alert->is_active ? 'إيقاف' : 'إعادة نشر' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.breaking-alerts.destroy', $alert) }}" onsubmit="return confirm('هل تريد حذف هذا الخبر العاجل؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:35px;color:#888">لا توجد أخبار عاجلة بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($alerts->hasPages())
            <div style="padding:15px">{{ $alerts->links() }}</div>
        @endif
    </div>
</div>
@endsection
