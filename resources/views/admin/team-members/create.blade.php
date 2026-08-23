@extends('layouts.admin')

@section('title', 'إضافة عضو فريق')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:20px;flex-wrap:wrap">
    <div>
        <h2 style="margin:0;color:#1a1a2e;font-size:22px;font-weight:800">
            <i class="fa-solid fa-user-plus" style="color:#c9a84c"></i>
            إضافة عضو فريق
        </h2>
        <p style="margin:5px 0 0;color:#888;font-size:13px">
            سيظهر العضو تلقائيًا في صفحة من نحن عند تفعيل حالته.
        </p>
    </div>

    <a href="{{ route('admin.team-members.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-right"></i>
        العودة للقائمة
    </a>
</div>



<form id="team-member-form" action="{{ route('admin.team-members.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="team-form-grid">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i class="fa-solid fa-address-card" style="color:#c9a84c"></i>
                    بيانات العضو
                </span>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <label for="name">اسم عضو الفريق <span style="color:#e74c3c">*</span></label>
                    <input type="text" id="name" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" maxlength="255" required autofocus>
                    @error('name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="job_title">المسمى الوظيفي <span style="color:#e74c3c">*</span></label>
                    <input type="text" id="job_title" name="job_title"
                           class="form-control @error('job_title') is-invalid @enderror"
                           value="{{ old('job_title') }}" maxlength="255"
                           placeholder="مثال: رئيس التحرير" required>
                    @error('job_title')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label for="display_order">ترتيب الظهور</label>
                    <input type="number" id="display_order" name="display_order"
                           class="form-control @error('display_order') is-invalid @enderror"
                           value="{{ old('display_order', 0) }}" min="0" max="9999">
                    <small class="field-help">الرقم الأصغر يظهر أولًا في صفحة من نحن.</small>
                    @error('display_order')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i class="fa-solid fa-camera" style="color:#c9a84c"></i>
                        صورة العضو
                    </span>
                </div>

                <div class="card-body">
                    <div class="member-image-preview" id="member-image-preview">
                        <div class="member-preview-placeholder" id="member-preview-placeholder">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <img id="member-preview-image" alt="معاينة صورة عضو الفريق" hidden>
                    </div>

                    <div class="image-details" id="image-details" hidden>
                        <strong id="image-file-name"></strong>
                        <span id="image-file-meta"></span>
                    </div>

                    <div class="image-controls" id="image-controls" hidden>
                        <div class="control-row">
                            <label for="image-zoom">حجم الصورة داخل الإطار</label>
                            <output id="image-zoom-value" for="image-zoom">120%</output>
                        </div>
                        <input type="range" id="image-zoom" min="100" max="250" value="120" step="1">

                        <div class="control-row">
                            <label for="image-position-x">التحريك الأفقي</label>
                            <output id="image-position-x-value" for="image-position-x">50%</output>
                        </div>
                        <input type="range" id="image-position-x" min="0" max="100" value="50" step="1">

                        <div class="control-row">
                            <label for="image-position-y">التحريك العمودي</label>
                            <output id="image-position-y-value" for="image-position-y">50%</output>
                        </div>
                        <input type="range" id="image-position-y" min="0" max="100" value="50" step="1">

                        <button type="button" class="reset-image-button" id="reset-image-controls">
                            <i class="fa-solid fa-rotate-left"></i>
                            إعادة ضبط الصورة
                        </button>
                    </div>

                    <div class="form-group" style="margin-top:20px;margin-bottom:0">
                        <label for="image">اختيار الصورة <span style="color:#e74c3c">*</span></label>
                        <input type="file" id="image" name="image"
                               class="form-control @error('image') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                               required>
                        <small class="field-help">
                            يفضل استخدام صورة مربعة وواضحة. الحد الأقصى 5MB، ويمكنك ضبط موضعها بعد الاختيار.
                        </small>
                        @error('image')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i class="fa-solid fa-toggle-on" style="color:#c9a84c"></i>
                        حالة العضو
                    </span>
                </div>
                <div class="card-body">
                    <label class="status-switch-row" for="is_active">
                        <div>
                            <div style="font-weight:800;color:#1a1a2e;font-size:13px">عضو نشط</div>
                            <div style="font-size:11px;color:#999;margin-top:3px">يظهر العضو في صفحة من نحن.</div>
                        </div>
                        <span class="switch-control">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   @checked((bool) old('is_active', true))>
                            <span class="switch-slider"></span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
                <i class="fa-solid fa-floppy-disk"></i>
                حفظ عضو الفريق
            </button>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .team-form-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr);gap:20px;align-items:start}
    .field-error{margin-top:6px;color:#e74c3c;font-size:11.5px;font-weight:700}
    .field-help{display:block;margin-top:7px;color:#999;font-size:11px;line-height:1.7}
    .is-invalid{border-color:#e74c3c!important}
    .member-image-preview{position:relative;display:flex;width:190px;height:190px;align-items:center;justify-content:center;margin:0 auto;overflow:hidden;border:4px solid #fff;border-radius:50%;background:#f7f7f7;box-shadow:0 0 0 3px rgba(201,168,76,.55),0 12px 30px rgba(0,0,0,.14)}
    .member-image-preview img{position:absolute;display:block;max-width:none;max-height:none;transition:width .08s ease,height .08s ease,left .08s ease,top .08s ease;will-change:width,height,left,top}
    .member-preview-placeholder{display:flex;width:100%;height:100%;align-items:center;justify-content:center;color:#c9a84c;background:linear-gradient(135deg,#faf7ef,#f0e7cf);font-size:65px}
    .image-details{margin:16px auto 0;padding:10px 12px;max-width:320px;border:1px solid #eee;border-radius:8px;background:#fafafa;text-align:center;overflow:hidden}
    .image-details strong,.image-details span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .image-details strong{color:#333;font-size:12px}.image-details span{margin-top:4px;color:#888;font-size:11px}
    .image-controls{margin-top:18px;padding:15px;border:1px solid #eee;border-radius:10px;background:#fcfcfc}
    .control-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 5px}
    .control-row:not(:first-child){margin-top:13px}
    .control-row label{margin:0;color:#555;font-size:11.5px;font-weight:700}
    .control-row output{min-width:38px;color:#c9a84c;font-size:11px;font-weight:800;text-align:left}
    .image-controls input[type="range"]{width:100%;accent-color:#c9a84c;cursor:pointer}
    .reset-image-button{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;margin-top:14px;padding:8px;border:1px solid #ddd;border-radius:7px;background:#fff;color:#555;font-family:inherit;font-size:11.5px;font-weight:700;cursor:pointer}
    .reset-image-button:hover{border-color:#c9a84c;color:#9b7929}
    .status-switch-row{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0;cursor:pointer}
    .switch-control{position:relative;display:inline-block;width:48px;height:26px;flex-shrink:0}
    .switch-control input[type="checkbox"]{width:0;height:0;opacity:0}
    .switch-slider{position:absolute;inset:0;border-radius:50px;background:#d6d6d6;transition:.2s}
    .switch-slider::before{position:absolute;right:3px;bottom:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.2);content:"";transition:.2s}
    .switch-control input:checked + .switch-slider{background:#27ae60}
    .switch-control input:checked + .switch-slider::before{transform:translateX(-22px)}
    @media(max-width:850px){.team-form-grid{grid-template-columns:1fr}}
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.getElementById('image');
        const form = document.getElementById('team-member-form');
        const previewFrame = document.getElementById('member-image-preview');
        const previewImage = document.getElementById('member-preview-image');
        const placeholder = document.getElementById('member-preview-placeholder');
        const details = document.getElementById('image-details');
        const fileName = document.getElementById('image-file-name');
        const fileMeta = document.getElementById('image-file-meta');
        const controls = document.getElementById('image-controls');
        const zoom = document.getElementById('image-zoom');
        const positionX = document.getElementById('image-position-x');
        const positionY = document.getElementById('image-position-y');
        const zoomValue = document.getElementById('image-zoom-value');
        const positionXValue = document.getElementById('image-position-x-value');
        const positionYValue = document.getElementById('image-position-y-value');
        const resetButton = document.getElementById('reset-image-controls');
        let currentObjectUrl = null;
        let selectedFile = null;

        function applyPreviewSettings() {
            if (!previewImage.naturalWidth || !previewImage.naturalHeight) return;

            const frameSize = previewFrame.clientWidth;
            const baseScale = Math.max(
                frameSize / previewImage.naturalWidth,
                frameSize / previewImage.naturalHeight
            );
            const userScale = Number(zoom.value) / 100;
            const renderedWidth = previewImage.naturalWidth * baseScale * userScale;
            const renderedHeight = previewImage.naturalHeight * baseScale * userScale;
            const overflowX = Math.max(0, renderedWidth - frameSize);
            const overflowY = Math.max(0, renderedHeight - frameSize);

            previewImage.style.width = `${renderedWidth}px`;
            previewImage.style.height = `${renderedHeight}px`;
            previewImage.style.left = `${-overflowX * (Number(positionX.value) / 100)}px`;
            previewImage.style.top = `${-overflowY * (Number(positionY.value) / 100)}px`;

            zoomValue.textContent = `${zoom.value}%`;
            positionXValue.textContent = `${positionX.value}%`;
            positionYValue.textContent = `${positionY.value}%`;
        }

        function resetSettings() {
            zoom.value = 120;
            positionX.value = 50;
            positionY.value = 50;
            applyPreviewSettings();
        }

        [zoom, positionX, positionY].forEach(function (control) {
            control.addEventListener('input', applyPreviewSettings);
        });

        resetButton.addEventListener('click', resetSettings);

        fileInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                selectedFile = null;
                previewImage.hidden = true;
                placeholder.hidden = false;
                details.hidden = true;
                controls.hidden = true;
                return;
            }

            if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = URL.createObjectURL(file);
            selectedFile = file;
            resetSettings();

            previewImage.onload = function () {
                const sizeMb = (file.size / 1024 / 1024).toFixed(2);
                fileName.textContent = file.name;
                fileMeta.textContent = `${previewImage.naturalWidth} × ${previewImage.naturalHeight} بكسل — ${sizeMb} MB`;
                placeholder.hidden = true;
                previewImage.hidden = false;
                details.hidden = false;
                controls.hidden = false;
                applyPreviewSettings();
            };

            previewImage.src = currentObjectUrl;
        });

        form.addEventListener('submit', function (event) {
            if (!selectedFile || form.dataset.processing === 'true') return;

            event.preventDefault();
            form.dataset.processing = 'true';

            const outputSize = 1200;
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = outputSize;
            canvas.height = outputSize;

            const baseScale = Math.max(
                outputSize / previewImage.naturalWidth,
                outputSize / previewImage.naturalHeight
            );
            const finalScale = baseScale * (Number(zoom.value) / 100);
            const renderedWidth = previewImage.naturalWidth * finalScale;
            const renderedHeight = previewImage.naturalHeight * finalScale;
            const sourceX = -Math.max(0, renderedWidth - outputSize) * (Number(positionX.value) / 100);
            const sourceY = -Math.max(0, renderedHeight - outputSize) * (Number(positionY.value) / 100);

            context.drawImage(previewImage, sourceX, sourceY, renderedWidth, renderedHeight);
            canvas.toBlob(function (blob) {
                if (!blob) {
                    form.dataset.processing = 'false';
                    form.submit();
                    return;
                }

                const processedFile = new File([blob], 'team-member-' + Date.now() + '.webp', {
                    type: 'image/webp'
                });
                const transfer = new DataTransfer();
                transfer.items.add(processedFile);
                fileInput.files = transfer.files;
                form.submit();
            }, 'image/webp', 0.9);
        });

        window.addEventListener('resize', applyPreviewSettings);

        window.addEventListener('beforeunload', function () {
            if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
        });
    });
</script>
@endpush