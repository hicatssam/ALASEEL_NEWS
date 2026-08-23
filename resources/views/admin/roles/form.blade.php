@extends('layouts.admin')
@section('title', $role->exists ? 'تعديل الدور' : 'إضافة دور')
@section('breadcrumb') <a href="{{ route('admin.roles.index') }}">الأدوار</a> › {{ $role->exists ? 'تعديل' : 'إضافة' }} @endsection
@section('content')
<form method="POST" action="{{ $role->exists ? route('admin.roles.update',$role) : route('admin.roles.store') }}">@csrf @if($role->exists) @method('PUT') @endif
<div class="card" style="max-width:1000px"><div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
        <div class="form-group"><label class="form-label">اسم الدور *</label><input class="form-control" name="name" value="{{ old('name',$role->name) }}" required></div>
        <div class="form-group"><label class="form-label">المعرّف</label><input class="form-control" name="slug" value="{{ old('slug',$role->slug) }}" placeholder="editor"></div>
    </div>
    <div class="form-group"><label class="form-label">الوصف</label><textarea class="form-control" name="description" rows="2">{{ old('description',$role->description) }}</textarea></div>
    <label class="form-check" style="margin-bottom:20px"><input type="checkbox" name="status" value="1" @checked(old('status',$role->status ?? true))> الدور فعّال</label>
    <h3 style="margin-bottom:12px">الصلاحيات</h3>
    @php($selected = collect(old('permissions',$role->permissions->pluck('id')->all()))->map(fn($id)=>(int)$id)->all())
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
    @foreach($permissions as $module => $items)<div style="border:1px solid #e6e8ed;border-radius:10px;padding:14px"><strong style="display:block;margin-bottom:10px">{{ $module ?: 'عام' }}</strong>
        @foreach($items as $permission)<label class="form-check" style="margin:8px 0"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id,$selected,true))> {{ $permission->name }}</label>@endforeach
    </div>@endforeach</div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px"><a class="btn btn-outline" href="{{ route('admin.roles.index') }}">إلغاء</a><button class="btn btn-primary">حفظ</button></div>
</div></div></form>
@endsection
