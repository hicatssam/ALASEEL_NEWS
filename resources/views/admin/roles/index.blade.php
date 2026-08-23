@extends('layouts.admin')
@section('title', 'الأدوار والصلاحيات')
@section('breadcrumb') الأدوار والصلاحيات @endsection
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
    <p style="color:#777">حدّد بوضوح ما يستطيع كل دور الوصول إليه.</p>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> دور جديد</a>
</div>
<div class="card"><div class="table-wrap"><table>
    <thead><tr><th>الدور</th><th>الوصف</th><th>المستخدمون</th><th>الصلاحيات</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>@foreach($roles as $role)<tr>
        <td><strong>{{ $role->name }}</strong><div style="font-size:11px;color:#999">{{ $role->slug }}</div></td>
        <td>{{ $role->description ?: '—' }}</td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td>
        <td><span class="badge {{ $role->status ? 'badge-success' : 'badge-secondary' }}">{{ $role->status ? 'فعّال' : 'معطّل' }}</span></td>
        <td><div style="display:flex;gap:6px"><a href="{{ route('admin.roles.edit',$role) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pen"></i></a>
        @if($role->slug !== 'super-admin')<form method="POST" action="{{ route('admin.roles.destroy',$role) }}" onsubmit="return confirm('حذف هذا الدور؟')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form>@endif</div></td>
    </tr>@endforeach</tbody>
</table></div></div>
@endsection
