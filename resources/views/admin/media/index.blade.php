@extends('layouts.admin')
@section('title','مكتبة الوسائط')
@section('breadcrumb') مكتبة الوسائط @endsection

@section('content')
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
  @php
    $totalSizeMB = number_format(($stats['total_size'] ?? 0) / 1048576, 1);
  @endphp

  <div class="stat-card" style="border-color:#c9a84c;padding:14px">
    <div class="stat-icon" style="background:rgba(201,168,76,.1);color:#c9a84c;width:38px;height:38px;font-size:16px">
      <i class="fa-solid fa-photo-film"></i>
    </div>
    <div>
      <div class="stat-value" style="font-size:18px">{{ $stats['total'] }}</div>
      <div class="stat-label">إجمالي الملفات</div>
    </div>
  </div>

  <div class="stat-card" style="border-color:#3498db;padding:14px">
    <div class="stat-icon" style="background:#eaf4fd;color:#3498db;width:38px;height:38px;font-size:16px">
      <i class="fa-solid fa-image"></i>
    </div>
    <div>
      <div class="stat-value" style="font-size:18px">{{ $stats['images'] }}</div>
      <div class="stat-label">صور</div>
    </div>
  </div>

  <div class="stat-card" style="border-color:#e74c3c;padding:14px">
    <div class="stat-icon" style="background:#fdf0f0;color:#e74c3c;width:38px;height:38px;font-size:16px">
      <i class="fa-solid fa-video"></i>
    </div>
    <div>
      <div class="stat-value" style="font-size:18px">{{ $stats['videos'] }}</div>
      <div class="stat-label">فيديوهات</div>
    </div>
  </div>

  <div class="stat-card" style="border-color:#27ae60;padding:14px">
    <div class="stat-icon" style="background:#eafaf1;color:#27ae60;width:38px;height:38px;font-size:16px">
      <i class="fa-solid fa-hdd"></i>
    </div>
    <div>
      <div class="stat-value" style="font-size:18px">{{ $totalSizeMB }} MB</div>
      <div class="stat-label">الحجم الكلي</div>
    </div>
  </div>
</div>

<div style="display:flex;justify-content:space-between;margin-bottom:16px;align-items:center">
  <div class="filter-bar" style="flex:1;margin-bottom:0;margin-left:12px">
    <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
      <select name="file_type" class="form-control" style="max-width:160px">
        <option value="">كل الأنواع</option>
        <option value="image" {{ request('file_type') == 'image' ? 'selected' : '' }}>صور</option>
        <option value="video" {{ request('file_type') == 'video' ? 'selected' : '' }}>فيديو</option>
        <option value="document" {{ request('file_type') == 'document' ? 'selected' : '' }}>مستندات</option>
      </select>

      <input
        type="text"
        name="search"
        class="form-control"
        placeholder="بحث..."
        value="{{ request('search') }}"
        style="max-width:200px"
      >

      <button class="btn btn-secondary" type="submit">
        <i class="fa-solid fa-search"></i>
      </button>
    </form>
  </div>

  <label class="btn btn-primary" style="cursor:pointer">
    <i class="fa-solid fa-upload"></i>
    رفع ملف

    <form
      id="uploadForm"
      method="POST"
      action="{{ route('admin.media.store') }}"
      enctype="multipart/form-data"
      style="display:none"
    >
      @csrf
      <input
        type="file"
        name="file"
        id="fileInput"
        onchange="document.getElementById('uploadForm').submit()"
      >
    </form>
  </label>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">الملفات ({{ $files->total() }})</span>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:700">
        <input type="checkbox" id="selectAllMedia">
        تحديد كل ملفات الصفحة
      </label>
      <span id="selectedMediaCount" style="font-size:12px;color:#777">لم يتم التحديد</span>
      <form id="bulkDeleteForm" method="POST" action="{{ route('admin.media.bulk-destroy') }}">
        @csrf @method('DELETE')
        <button type="submit" id="bulkDeleteButton" class="btn btn-danger btn-sm" disabled><i class="fa-solid fa-trash"></i> حذف المحدد</button>
      </form>
    </div>
  </div>

  <div class="card-body">
    @if($files->count())
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
        @foreach($files as $file)
          <div class="media-library-card" style="border:1px solid #e8e8e8;border-radius:10px;overflow:hidden;position:relative">
            <label style="position:absolute;z-index:2;top:7px;right:7px;background:#fff;padding:4px;border-radius:5px;box-shadow:0 1px 5px #0003">
              <input class="media-select-checkbox" type="checkbox" name="media_ids[]" value="{{ $file->id }}" form="bulkDeleteForm" aria-label="اختيار {{ $file->file_name }}">
            </label>
            <div style="height:120px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden">
              @if($file->file_type == 'image')
                <img
                  src="{{ $file->url }}"
                  alt="{{ $file->alt_text ?? $file->file_name }}"
                  style="width:100%;height:100%;object-fit:cover"
                  loading="lazy"
                  onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-image\' style=\'font-size:32px;color:#ccc\'></i>'"
                >
              @else
                <i
                  class="fa-solid {{ $file->file_type == 'video' ? 'fa-video' : ($file->file_type == 'audio' ? 'fa-music' : 'fa-file') }}"
                  style="font-size:32px;color:#ccc"
                ></i>
              @endif
            </div>

            <div style="padding:8px">
              <div style="font-size:11px;font-weight:600;color:#1a1a2e;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $file->file_name }}
              </div>

              <div style="font-size:10px;color:#aaa">
                {{ $file->formatted_size }}
              </div>

              <div style="display:flex;justify-content:flex-end;margin-top:6px">
                <form
                  method="POST"
                  action="{{ route('admin.media.destroy', $file) }}"
                  onsubmit="return confirm('حذف؟')"
                >
                  @csrf
                  @method('DELETE')

                  <button
                    class="btn btn-danger btn-sm btn-icon"
                    style="width:24px;height:24px;font-size:10px"
                    type="submit"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="empty-state">
        <i class="fa-solid fa-photo-film"></i>
        <p>لا توجد ملفات. ارفع ملفاً للبدء.</p>
      </div>
    @endif
  </div>

  @if($files->hasPages())
    <div style="padding:16px">
      {{ $files->links() }}
    </div>
  @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('bulkDeleteForm');
  const selectAll = document.getElementById('selectAllMedia');
  const checkboxes = [...document.querySelectorAll('.media-select-checkbox')];
  const button = document.getElementById('bulkDeleteButton');
  const count = document.getElementById('selectedMediaCount');

  const refresh = () => {
    const selected = checkboxes.filter(checkbox => checkbox.checked);
    button.disabled = selected.length === 0;
    count.textContent = selected.length ? `تم تحديد ${selected.length}` : 'لم يتم التحديد';
    selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
    selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
    checkboxes.forEach(checkbox => checkbox.closest('.media-library-card')?.classList.toggle('is-selected', checkbox.checked));
  };

  selectAll?.addEventListener('change', () => {
    checkboxes.forEach(checkbox => { checkbox.checked = selectAll.checked; });
    refresh();
  });
  checkboxes.forEach(checkbox => checkbox.addEventListener('change', refresh));
  form?.addEventListener('submit', function (event) {
    const selected = checkboxes.filter(checkbox => checkbox.checked);
    if (!selected.length || !confirm(`نقل ${selected.length} ملف/ملفات محددة إلى المحذوفات؟`)) event.preventDefault();
  });
  refresh();
});
</script>
<style>
.media-library-card{transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
.media-library-card.is-selected{border-color:#c9a84c!important;box-shadow:0 0 0 2px rgba(201,168,76,.22);transform:translateY(-2px)}
#bulkDeleteButton:disabled{opacity:.45;cursor:not-allowed}
</style>
@endsection
