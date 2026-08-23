@extends('layouts.admin')

@section('title', 'تعديل عضو الفريق')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:20px;flex-wrap:wrap">
    <div>
        <h2 style="margin:0;color:#1a1a2e;font-size:22px;font-weight:800">
            <i class="fa-solid fa-user-pen" style="color:#c9a84c"></i>
            تعديل عضو الفريق
        </h2>

        <p style="margin:5px 0 0;color:#888;font-size:13px">
            تعديل بيانات {{ $teamMember->name }}.
        </p>
    </div>

    <a href="{{ route('admin.team-members.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-right"></i>
        العودة للقائمة
    </a>
</div>


@php
    $memberImageUrl = $teamMember->image_url;
@endphp

<form
    action="{{ route('admin.team-members.update', $teamMember) }}"
    method="POST"
    enctype="multipart/form-data"
>
    @csrf
    @method('PUT')

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
                    <label for="name">
                        اسم عضو الفريق
                        <span style="color:#e74c3c">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $teamMember->name) }}"
                        maxlength="255"
                        required
                        autofocus
                    >

                    @error('name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="job_title">
                        المسمى الوظيفي
                        <span style="color:#e74c3c">*</span>
                    </label>

                    <input
                        type="text"
                        id="job_title"
                        name="job_title"
                        class="form-control @error('job_title') is-invalid @enderror"
                        value="{{ old('job_title', $teamMember->job_title) }}"
                        maxlength="255"
                        required
                    >

                    @error('job_title')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label for="display_order">ترتيب الظهور</label>

                    <input
                        type="number"
                        id="display_order"
                        name="display_order"
                        class="form-control @error('display_order') is-invalid @enderror"
                        value="{{ old('display_order', $teamMember->display_order ?? 0) }}"
                        min="0"
                        max="9999"
                    >

                    <small class="field-help">
                        الرقم الأصغر يظهر أولًا في صفحة من نحن.
                    </small>

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
                        @if($memberImageUrl)
                            <img
                                class="current-member-image"
                                src="{{ $memberImageUrl }}"
                                alt="{{ $teamMember->name }}"
                            >
                        @else
                            <div class="member-preview-placeholder">
                                {{ mb_substr($teamMember->name ?: 'ع', 0, 1) }}
                            </div>
                        @endif
                    </div>

                    <div class="image-editor" id="image-editor" hidden>
                        <div class="image-control-row">
                            <label for="image-zoom">حجم الصورة</label>
                            <input type="range" id="image-zoom" min="1" max="3" step="0.01" value="1.2">
                            <output id="image-zoom-value">120%</output>
                        </div>

                        <div class="image-control-row">
                            <label for="image-position-x">تحريك أفقي</label>
                            <input type="range" id="image-position-x" min="-100" max="100" step="1" value="0">
                        </div>

                        <div class="image-control-row">
                            <label for="image-position-y">تحريك عمودي</label>
                            <input type="range" id="image-position-y" min="-100" max="100" step="1" value="0">
                        </div>

                        <div class="image-editor-footer">
                            <span id="image-file-info"></span>
                            <button type="button" class="btn btn-outline image-reset-button" id="image-reset">
                                <i class="fa-solid fa-rotate-left"></i>
                                إعادة الضبط
                            </button>
                        </div>

                        <small class="field-help">
                            اضبط الصورة داخل الدائرة. عند الحفظ سيتم رفع نسخة مربعة محسّنة بمقاس 1200 × 1200 بكسل.
                        </small>
                    </div>

                    <div class="form-group" style="margin-top:20px;margin-bottom:0">
                        <label for="image">استبدال الصورة</label>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            class="form-control @error('image') is-invalid @enderror"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                        >

                        <small class="field-help">
                            اترك الحقل فارغًا للاحتفاظ بالصورة الحالية.
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
                            <div style="font-weight:800;color:#1a1a2e;font-size:13px">
                                عضو نشط
                            </div>

                            <div style="font-size:11px;color:#999;margin-top:3px">
                                يظهر العضو في صفحة من نحن.
                            </div>
                        </div>

                        <span class="switch-control">
                            <input type="hidden" name="is_active" value="0">

                            <input
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                @checked((bool) old('is_active', $teamMember->is_active))
                            >

                            <span class="switch-slider"></span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
                <i class="fa-solid fa-floppy-disk"></i>
                حفظ التعديلات
            </button>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .team-form-grid {
        display:grid;
        grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr);
        gap:20px;
        align-items:start;
    }

    .field-error {
        margin-top:6px;
        color:#e74c3c;
        font-size:11.5px;
        font-weight:700;
    }

    .field-help {
        display:block;
        margin-top:7px;
        color:#999;
        font-size:11px;
        line-height:1.7;
    }

    .is-invalid {
        border-color:#e74c3c !important;
    }

    .member-image-preview {
        position:relative;
        display:flex;
        width:190px;
        height:190px;
        align-items:center;
        justify-content:center;
        margin:0 auto;
        overflow:hidden;
        border:4px solid #fff;
        border-radius:50%;
        background:#f7f7f7;
        box-shadow:0 0 0 3px rgba(201,168,76,.55),0 12px 30px rgba(0,0,0,.14);
    }

    .member-image-preview img {
        position:absolute;
        top:50%;
        left:50%;
        display:block;
        width:auto;
        height:auto;
        max-width:none;
        max-height:none;
        transform-origin:center center;
        transition:transform .06s linear;
        user-select:none;
        pointer-events:none;
        -webkit-user-drag:none;
    }

    .member-image-preview img.current-member-image {
        top:0;
        left:0;
        width:100%;
        height:100%;
        object-fit:cover;
        transform:none;
    }

    .image-editor {
        margin-top:20px;
        padding:14px;
        border:1px solid #ece6d5;
        border-radius:10px;
        background:#fcfaf4;
    }

    .image-control-row {
        display:grid;
        grid-template-columns:90px minmax(0,1fr) 45px;
        align-items:center;
        gap:10px;
        margin-bottom:12px;
    }

    .image-control-row label {
        margin:0;
        color:#555;
        font-size:12px;
        font-weight:800;
    }

    .image-control-row input[type="range"] {
        width:100%;
        accent-color:#c9a84c;
    }

    .image-control-row output {
        direction:ltr;
        color:#96752c;
        font-size:11px;
        font-weight:800;
        text-align:left;
    }

    .image-editor-footer {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        padding-top:4px;
    }

    #image-file-info {
        color:#777;
        font-size:11px;
        line-height:1.6;
    }

    .image-reset-button {
        flex-shrink:0;
        padding:7px 10px;
        font-size:11px;
    }

    .member-preview-placeholder {
        display:flex;
        width:100%;
        height:100%;
        align-items:center;
        justify-content:center;
        color:#fff;
        background:linear-gradient(135deg,#c9a84c,#96752c);
        font-size:55px;
        font-weight:800;
    }

    .status-switch-row {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        margin:0;
        cursor:pointer;
    }

    .switch-control {
        position:relative;
        display:inline-block;
        width:48px;
        height:26px;
        flex-shrink:0;
    }

    .switch-control input[type="checkbox"] {
        width:0;
        height:0;
        opacity:0;
    }

    .switch-slider {
        position:absolute;
        inset:0;
        border-radius:50px;
        background:#d6d6d6;
        transition:.2s;
    }

    .switch-slider::before {
        position:absolute;
        right:3px;
        bottom:3px;
        width:20px;
        height:20px;
        border-radius:50%;
        background:#fff;
        box-shadow:0 2px 6px rgba(0,0,0,.2);
        content:"";
        transition:.2s;
    }

    .switch-control input:checked + .switch-slider {
        background:#27ae60;
    }

    .switch-control input:checked + .switch-slider::before {
        transform:translateX(-22px);
    }

    @media(max-width:850px) {
        .team-form-grid {
            grid-template-columns:1fr;
        }

        .image-control-row {
            grid-template-columns:82px minmax(0,1fr) 40px;
        }

        .image-editor-footer {
            align-items:flex-start;
            flex-direction:column;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action*="team-members"]');
        const fileInput = document.getElementById('image');
        const preview = document.getElementById('member-image-preview');
        const editor = document.getElementById('image-editor');
        const zoomInput = document.getElementById('image-zoom');
        const positionXInput = document.getElementById('image-position-x');
        const positionYInput = document.getElementById('image-position-y');
        const zoomValue = document.getElementById('image-zoom-value');
        const fileInfo = document.getElementById('image-file-info');
        const resetButton = document.getElementById('image-reset');

        let sourceImage = null;
        let objectUrl = null;
        let isPreparingImage = false;

        function resetControls() {
            zoomInput.value = '1.2';
            positionXInput.value = '0';
            positionYInput.value = '0';
            updatePreviewTransform();
        }

        function updatePreviewTransform() {
            const image = preview?.querySelector('img');

            if (!image || !sourceImage || !preview.clientWidth || !preview.clientHeight) {
                return;
            }

            const zoom = Number(zoomInput.value);
            const positionX = Number(positionXInput.value) / 100;
            const positionY = Number(positionYInput.value) / 100;
            const frameWidth = preview.clientWidth;
            const frameHeight = preview.clientHeight;
            const coverScale = Math.max(
                frameWidth / sourceImage.naturalWidth,
                frameHeight / sourceImage.naturalHeight
            );
            const renderedWidth = sourceImage.naturalWidth * coverScale * zoom;
            const renderedHeight = sourceImage.naturalHeight * coverScale * zoom;
            const maxOffsetX = Math.max(0, (renderedWidth - frameWidth) / 2);
            const maxOffsetY = Math.max(0, (renderedHeight - frameHeight) / 2);
            const offsetX = positionX * maxOffsetX;
            const offsetY = positionY * maxOffsetY;

            image.style.width = `${renderedWidth}px`;
            image.style.height = `${renderedHeight}px`;
            image.style.transform = `translate(calc(-50% + ${offsetX}px), calc(-50% + ${offsetY}px))`;
            zoomValue.value = `${Math.round(zoom * 100)}%`;
            zoomValue.textContent = zoomValue.value;
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
        }

        fileInput?.addEventListener('change', function () {
            const file = this.files?.[0];

            if (!file || !file.type.startsWith('image/') || !preview) {
                editor.hidden = true;
                return;
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }

            objectUrl = URL.createObjectURL(file);
            sourceImage = new Image();

            sourceImage.onload = function () {
                preview.innerHTML = '';

                const image = document.createElement('img');
                image.src = objectUrl;
                image.alt = 'معاينة صورة عضو الفريق';
                image.draggable = false;
                preview.appendChild(image);

                fileInfo.textContent = `${file.name} — ${sourceImage.naturalWidth} × ${sourceImage.naturalHeight} — ${formatFileSize(file.size)}`;
                editor.hidden = false;
                resetControls();
            };

            sourceImage.onerror = function () {
                editor.hidden = true;
                alert('تعذر قراءة الصورة المختارة. جرّب صورة أخرى.');
            };

            sourceImage.src = objectUrl;
        });

        [zoomInput, positionXInput, positionYInput].forEach(function (control) {
            control?.addEventListener('input', updatePreviewTransform);
        });

        resetButton?.addEventListener('click', resetControls);

        form?.addEventListener('submit', function (event) {
            const originalFile = fileInput?.files?.[0];

            if (!originalFile || !sourceImage || isPreparingImage) {
                return;
            }

            event.preventDefault();
            isPreparingImage = true;

            const outputSize = 1200;
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            const zoom = Number(zoomInput.value);
            const positionX = Number(positionXInput.value) / 100;
            const positionY = Number(positionYInput.value) / 100;
            const baseScale = Math.max(
                outputSize / sourceImage.naturalWidth,
                outputSize / sourceImage.naturalHeight
            );
            const drawWidth = sourceImage.naturalWidth * baseScale * zoom;
            const drawHeight = sourceImage.naturalHeight * baseScale * zoom;
            const maxOffsetX = Math.max(0, (drawWidth - outputSize) / 2);
            const maxOffsetY = Math.max(0, (drawHeight - outputSize) / 2);
            const drawX = (outputSize - drawWidth) / 2 + (positionX * maxOffsetX);
            const drawY = (outputSize - drawHeight) / 2 + (positionY * maxOffsetY);

            canvas.width = outputSize;
            canvas.height = outputSize;
            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = 'high';
            context.drawImage(sourceImage, drawX, drawY, drawWidth, drawHeight);

            canvas.toBlob(function (blob) {
                if (!blob) {
                    isPreparingImage = false;
                    form.submit();
                    return;
                }

                const resizedFile = new File(
                    [blob],
                    `${originalFile.name.replace(/\\.[^.]+$/, '')}-1200.webp`,
                    { type: 'image/webp', lastModified: Date.now() }
                );
                const transfer = new DataTransfer();
                transfer.items.add(resizedFile);
                fileInput.files = transfer.files;
                form.submit();
            }, 'image/webp', 0.9);
        });

        window.addEventListener('beforeunload', function () {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
        });
    });
</script>
@endpush
