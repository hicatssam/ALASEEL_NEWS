<div class="form-group" style="margin-bottom:12px">
    <label for="tag-search" class="form-label">البحث عن وسم</label>
    <div style="position:relative">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#999;pointer-events:none"></i>
        <input
            type="search"
            id="tag-search"
            class="form-control"
            placeholder="اكتب اسم الوسم..."
            autocomplete="off"
            style="padding-right:38px"
        >
    </div>
    <small id="tag-search-summary" style="display:block;margin-top:6px;color:#888"></small>
</div>

<div id="tag-options" style="display:flex;flex-wrap:wrap;gap:6px;max-height:230px;overflow-y:auto;padding:2px">
    @forelse ($tags as $tag)
        <label class="tag-option" data-tag-name="{{ Str::lower($tag->name) }}">
            <input
                type="checkbox"
                name="tags[]"
                value="{{ $tag->id }}"
                @checked(in_array((string) $tag->id, $selectedTags, true))
            >
            {{ $tag->name }}
        </label>
    @empty
        <span style="font-size:13px;color:#888">لا توجد وسوم متاحة.</span>
    @endforelse
</div>

<div id="tag-search-empty" style="display:none;padding:12px;text-align:center;color:#888;font-size:13px">
    لا يوجد وسم مطابق. يمكنك إضافته من الحقل أدناه.
</div>

<div style="margin-top:14px">
    <label for="new-tags" class="form-label">إضافة وسوم جديدة</label>
    <input
        type="text"
        id="new-tags"
        name="new_tags"
        class="form-control @error('new_tags') is-invalid @enderror"
        value="{{ old('new_tags') }}"
        placeholder="مثال: تحقيقات خاصة، غزة، اقتصاد رقمي"
    >
    <small style="display:block;margin-top:6px;color:#888;line-height:1.6">
        اكتب وسمًا واحدًا أو عدة وسوم وافصل بينها بفاصلة، وسيتم إنشاؤها وربطها بالمحتوى تلقائيًا.
    </small>
</div>

@error('new_tags')<small class="field-error">{{ $message }}</small>@enderror
@error('tags')<small class="field-error">{{ $message }}</small>@enderror
@error('tags.*')<small class="field-error">{{ $message }}</small>@enderror

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('tag-search');
    const options = Array.from(document.querySelectorAll('#tag-options [data-tag-name]'));
    const empty = document.getElementById('tag-search-empty');
    const summary = document.getElementById('tag-search-summary');

    if (!search) return;

    const normalize = value => value
        .toLocaleLowerCase('ar')
        .normalize('NFKD')
        .replace(/[\u064B-\u065F\u0670]/g, '')
        .trim();

    const filterTags = () => {
        const query = normalize(search.value);
        let visible = 0;

        options.forEach(option => {
            const matches = !query || normalize(option.dataset.tagName).includes(query);
            option.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });

        empty.style.display = options.length && visible === 0 ? 'block' : 'none';
        summary.textContent = query
            ? `تم العثور على ${visible} وسم`
            : `${options.length} وسم متاح`;
    };

    search.addEventListener('input', filterTags);
    filterTags();
});
</script>
