@extends('layouts.admin')

@section('title', __('admin.articles_index'))
@section('breadcrumb') {{ __('admin.articles_index') }} @endsection

@section('content')
<style>
    .articles-page-toolbar {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        margin-bottom:20px;
    }
    .articles-status-filters,
    .article-row-actions {
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
    }
    .article-actions-cell { width:176px; min-width:176px; }
    .article-row-actions { flex-wrap:nowrap; justify-content:center; }
    .article-row-actions form { display:flex; margin:0; }
    .article-row-actions .btn-icon {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:34px;
        min-width:34px;
        height:34px;
        padding:0;
        border-radius:7px;
        line-height:1;
    }
    .article-row-actions .btn-icon i { margin:0; font-size:12px; }
    .article-status-select { min-width:130px; padding:5px 8px; font-size:12px; }
    .articles-pagination {
        padding:16px;
        overflow:hidden;
        border-top:1px solid #edf0f4;
    }
    .articles-pagination nav { width:100%; }
    .articles-pagination-inner {
        display:flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        flex-wrap:wrap;
        width:100%;
    }
    .articles-page-link {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:36px;
        height:36px;
        padding:0 10px;
        color:#34384a;
        background:#fff;
        border:1px solid #dfe3e8;
        border-radius:7px;
        font-size:12px;
        font-weight:700;
        line-height:1;
        transition:.2s;
    }
    .articles-page-link:hover { color:#fff; background:#c9a84c; border-color:#c9a84c; }
    .articles-page-link.is-active { color:#fff; background:#c9a84c; border-color:#c9a84c; }
    .articles-page-link.is-disabled { color:#aeb4bf; background:#f5f6f8; pointer-events:none; }
    .articles-page-link i { margin:0; font-size:11px; }
    .articles-page-dots {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        width:28px;
        height:36px;
        color:#9298a5;
    }
    @media (max-width: 900px) {
        .articles-page-toolbar { align-items:stretch; flex-direction:column; }
        .articles-page-toolbar > .btn { align-self:flex-start; }
        .article-actions-cell { width:164px; min-width:164px; }
        .article-row-actions .btn-icon { width:31px; min-width:31px; height:31px; }
    }
</style>

@php
    $contentTabs = [
        'news' => ['الأخبار', 'fa-newspaper'],
        'article' => ['المقالات', 'fa-file-lines'],
        'story' => ['القصص', 'fa-book-open'],
        'report' => ['التقارير', 'fa-chart-column'],
        'opinion' => ['الآراء', 'fa-comments'],
    ];
@endphp
<div class="card" style="margin-bottom:16px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <span class="card-title"><i class="fa-solid fa-layer-group"></i> أنواع المحتوى</span>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> إضافة محتوى جديد</a>
    </div>
    <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;padding:12px">
        <a href="{{ route('admin.articles.index', request()->except('content_type','page')) }}" class="btn btn-sm {{ !request()->filled('content_type') ? 'btn-primary' : 'btn-outline' }}">
            <i class="fa-solid fa-border-all"></i> الكل
        </a>
        @foreach($contentTabs as $type => [$label, $icon])
            @if(!in_array($type, ['story','report','opinion'], true) || auth()->user()->isAdmin() || auth()->user()->hasRole('editor') || auth()->user()->hasPermission('manage-'.($type === 'story' ? 'stories' : ($type === 'opinion' ? 'opinions' : 'reports'))))
                <a href="{{ route('admin.articles.index', array_merge(request()->except('content_type','page'), ['content_type'=>$type])) }}" class="btn btn-sm {{ request('content_type') === $type ? 'btn-primary' : 'btn-outline' }}">
                    <i class="fa-solid {{ $icon }}"></i> {{ $label }}
                </a>
            @endif
        @endforeach
    </div>
</div>

<div class="articles-page-toolbar">
    <div class="articles-status-filters">
        @foreach([['all', __('admin.filter_all')], ['published', __('admin.status_published')], ['draft', __('admin.status_draft')], ['under_review', __('admin.status_review')], ['scheduled', __('admin.status_scheduled')], ['archived', __('admin.status_archived')]] as [$value, $label])
            <a
                href="{{ route('admin.articles.index', array_merge(request()->except('status', 'page'), ['status' => $value === 'all' ? null : $value])) }}"
                class="btn btn-sm {{ request('status') === $value || ($value === 'all' && !request('status')) ? 'btn-primary' : 'btn-outline' }}"
            >{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="{{ __('admin.placeholder_search_articles') }}" value="{{ request('search') }}" style="max-width:280px">
        <select name="category_id" class="form-control" style="max-width:180px">
            <option value="">{{ __('admin.filter_all_categories') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="journalist_id" class="form-control" style="max-width:180px">
            <option value="">{{ __('admin.filter_all_journalists') }}</option>
            @foreach($journalists as $journalist)
                <option value="{{ $journalist->id }}" @selected((string) request('journalist_id') === (string) $journalist->id)>{{ $journalist->name }}</option>
            @endforeach
        </select>
        <select name="content_type" class="form-control" style="max-width:160px">
            <option value="">كل أنواع المحتوى</option>
            @foreach($contentTypes as $value => $label)
                <option value="{{ $value }}" @selected(request('content_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary"><i class="fa-solid fa-search"></i> {{ __('admin.btn_search') }}</button>
        @if(request()->hasAny(['search', 'category_id', 'journalist_id', 'content_type']))
            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline">{{ __('admin.btn_reset') }}</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">{{ __('admin.articles_index') }} ({{ $articles->total() }})</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('admin.col_title') }}</th>
                    <th>{{ __('admin.col_category') }}</th>
                    <th>{{ __('admin.col_journalist') }}</th>
                    <th>{{ __('admin.col_status') }}</th>
                    <th>{{ __('admin.col_views') }}</th>
                    <th>{{ __('admin.col_date') }}</th>
                    <th class="article-actions-cell">{{ __('admin.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td style="font-size:12px;color:#999">{{ $article->id }}</td>
                        <td style="max-width:280px">
                            <div style="font-weight:600;font-size:13px;color:#1a1a2e;line-height:1.4">{{ Str::limit($article->title, 65) }}</div>
                            <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap">
                                <span class="badge badge-secondary" style="font-size:10px">{{ $article->content_type_label }}</span>
                                @if($article->is_breaking)<span class="badge badge-danger" style="font-size:10px">{{ __('admin.badge_breaking') }}</span>@endif
                                @if($article->is_featured)<span class="badge badge-gold" style="font-size:10px">{{ __('admin.badge_featured') }}</span>@endif
                                @if($article->is_editor_pick)<span class="badge badge-info" style="font-size:10px">{{ __('admin.badge_editor_pick') }}</span>@endif
                            </div>
                        </td>
                        <td><span class="badge badge-secondary">{{ $article->category?->name ?? '—' }}</span></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:9px;min-width:150px">
                                @if($article->journalist?->photo_url)
                                    <img src="{{ $article->journalist->photo_url }}" alt="{{ $article->journalist->name }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #c9a84c;flex-shrink:0">
                                @else
                                    <span style="width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:#f5ecd3;color:#9b741d;flex-shrink:0"><i class="fa-solid fa-user-pen"></i></span>
                                @endif
                                <span style="display:flex;flex-direction:column;gap:2px">
                                    @if($article->content_type === 'opinion')
                                        <small style="font-size:10px;color:#9b741d;font-weight:700">صاحب الرأي</small>
                                    @endif
                                    <strong style="font-size:12px;color:#1a1a2e">{{ $article->journalist?->name ?? 'غير محدد' }}</strong>
                                </span>
                            </div>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.articles.status', $article) }}">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-control article-status-select" onchange="this.form.submit()">
                                    @foreach(['draft' => __('admin.status_draft'), 'under_review' => __('admin.status_review'), 'approved' => __('admin.status_approved'), 'published' => __('admin.status_published'), 'scheduled' => __('admin.status_scheduled'), 'archived' => __('admin.status_archived'), 'rejected' => __('admin.status_rejected')] as $value => $label)
                                        <option value="{{ $value }}" @selected($article->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td style="font-weight:700;color:#c9a84c">{{ number_format($article->views) }}</td>
                        <td style="font-size:12px;color:#888;white-space:nowrap">{{ $article->created_at->format('Y/m/d') }}</td>
                        <td class="article-actions-cell">
                            <div class="article-row-actions">
                                <a href="{{ route('articles.show', $article->slug) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm btn-icon" title="{{ __('admin.btn_view') }}"><i class="fa-solid fa-eye"></i></a>
                                <a href="{{ route('admin.articles.show', $article) }}" class="btn btn-outline btn-sm btn-icon" title="{{ __('admin.btn_view') }}"><i class="fa-solid fa-circle-info"></i></a>
                                <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-primary btn-sm btn-icon" title="{{ __('admin.btn_edit') }}"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('{{ __('admin.confirm_delete_article') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm btn-icon" title="{{ __('admin.btn_delete') }}"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-newspaper"></i><p>{{ __('admin.empty_articles') }}</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <nav class="articles-pagination" aria-label="Pagination">
            <div class="articles-pagination-inner">
                @if($articles->onFirstPage())
                    <span class="articles-page-link is-disabled" aria-disabled="true"><i class="fa-solid fa-chevron-right"></i></span>
                @else
                    <a class="articles-page-link" href="{{ $articles->previousPageUrl() }}" rel="prev"><i class="fa-solid fa-chevron-right"></i></a>
                @endif

                @foreach($articles->getUrlRange(max(1, $articles->currentPage() - 2), min($articles->lastPage(), $articles->currentPage() + 2)) as $page => $url)
                    <a class="articles-page-link {{ $page === $articles->currentPage() ? 'is-active' : '' }}" href="{{ $url }}" @if($page === $articles->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                @endforeach

                @if($articles->hasMorePages())
                    <a class="articles-page-link" href="{{ $articles->nextPageUrl() }}" rel="next"><i class="fa-solid fa-chevron-left"></i></a>
                @else
                    <span class="articles-page-link is-disabled" aria-disabled="true"><i class="fa-solid fa-chevron-left"></i></span>
                @endif
            </div>
        </nav>
    @endif
</div>
@endsection
