@extends('layouts.app')

@section('title', 'Guide d\'utilisation')
@section('page-title', 'Guide d\'utilisation')

@push('styles')
<style>
    /* ==================== GUIDE PAGE ==================== */
    .guide-search-wrapper {
        position: relative;
        max-width: 500px;
    }

    .guide-search-wrapper .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
    }

    .guide-search-input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid var(--border-color, #e0d5c8);
        border-radius: 14px;
        background: var(--bg-card, #FAF6F0);
        font-size: 0.95rem;
        color: var(--text-primary);
        transition: all 0.3s ease;
        box-shadow: var(--neu-shadow-inset, inset 3px 3px 6px rgba(0,0,0,0.06), inset -3px -3px 6px rgba(255,255,255,0.7));
    }

    .guide-search-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(139, 35, 50, 0.1);
    }

    .guide-search-input::placeholder {
        color: var(--text-muted);
    }

    .guide-results-count {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 8px;
    }

    /* Sommaire */
    .guide-toc-card {
        background: var(--bg-card, #FAF6F0);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--neu-shadow, 5px 5px 15px rgba(0,0,0,0.08), -5px -5px 15px rgba(255,255,255,0.8));
        margin-bottom: 2rem;
    }

    .guide-toc-card h5 {
        color: var(--primary);
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .guide-toc-section-title {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        margin-top: 0.75rem;
    }

    .guide-toc-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.88rem;
        color: var(--text-primary);
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .guide-toc-link:hover {
        background: rgba(139, 35, 50, 0.08);
        color: var(--primary);
    }

    /* Sections */
    .guide-section {
        margin-bottom: 2rem;
    }

    .guide-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark, #6B1A28) 100%);
        border-radius: 14px 14px 0 0;
        color: #fff;
    }

    .guide-section-header h4 {
        margin: 0;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .guide-section-body {
        background: var(--bg-card, #FAF6F0);
        border-radius: 0 0 14px 14px;
        box-shadow: var(--neu-shadow, 5px 5px 15px rgba(0,0,0,0.08), -5px -5px 15px rgba(255,255,255,0.8));
        overflow: hidden;
    }

    /* Accordion entries */
    .guide-entry {
        border-bottom: 1px solid var(--border-color, #e0d5c8);
    }

    .guide-entry:last-child {
        border-bottom: none;
    }

    .guide-entry-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 1rem 1.25rem;
        cursor: pointer;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
        color: var(--text-primary);
        font-weight: 600;
        font-size: 0.95rem;
        transition: background 0.2s ease;
    }

    .guide-entry-header:hover {
        background: rgba(139, 35, 50, 0.04);
    }

    .guide-entry-header .chevron {
        margin-left: auto;
        transition: transform 0.3s ease;
        color: var(--text-muted);
    }

    .guide-entry-header[aria-expanded="true"] .chevron {
        transform: rotate(180deg);
    }

    .guide-entry-body {
        padding: 0 1.25rem 1.25rem 1.25rem;
    }

    .guide-entry-description {
        color: var(--text-secondary, #555);
        font-size: 0.92rem;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .guide-steps-title,
    .guide-tips-title {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .guide-steps-list {
        padding-left: 1.25rem;
        margin-bottom: 1rem;
    }

    .guide-steps-list li {
        font-size: 0.9rem;
        color: var(--text-primary);
        line-height: 1.7;
        margin-bottom: 0.25rem;
    }

    .guide-tips-box {
        background: rgba(212, 168, 75, 0.1);
        border-left: 3px solid #D4A84B;
        border-radius: 0 10px 10px 0;
        padding: 0.75rem 1rem;
    }

    .guide-tips-box ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .guide-tips-box li {
        font-size: 0.88rem;
        color: var(--text-primary);
        line-height: 1.6;
        padding-left: 20px;
        position: relative;
        margin-bottom: 0.25rem;
    }

    .guide-tips-box li::before {
        content: '\2728';
        position: absolute;
        left: 0;
    }

    /* No results */
    .guide-no-results {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
        display: none;
    }

    .guide-no-results i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        display: block;
        color: var(--border-color, #e0d5c8);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .guide-search-wrapper {
            max-width: 100%;
        }

        .guide-toc-card {
            padding: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Search bar --}}
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-5 gap-3">
        <div class="guide-search-wrapper flex-grow-1">
            <i class="ki-duotone ki-magnifier fs-4 search-icon"><i class="path1"></i><i class="path2"></i></i>
            <input type="text"
                   id="guideSearch"
                   class="guide-search-input"
                   placeholder="Rechercher dans le guide..."
                   autocomplete="off">
            <div id="guideResultsCount" class="guide-results-count" style="display:none;"></div>
        </div>
    </div>

    {{-- Table of contents --}}
    <div class="guide-toc-card" id="guideToc">
        <h5><i class="ki-duotone ki-book fs-4 me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>Sommaire</h5>
        <div class="row">
            @foreach($sections as $sectionKey => $section)
                <div class="col-md-4 col-sm-6 mb-2">
                    <div class="guide-toc-section-title">
                        <i class="ki-duotone {{ $section['icon'] }} fs-7 me-1">
                            @for($i = 1; $i <= $section['icon_paths']; $i++)
                                <i class="path{{ $i }}"></i>
                            @endfor
                        </i>
                        {{ $section['title'] }}
                    </div>
                    @foreach($section['entries'] as $entry)
                        <a href="#entry-{{ $entry['key'] }}" class="guide-toc-link d-block">
                            <i class="ki-duotone {{ $entry['icon'] }} fs-7">
                                @for($i = 1; $i <= $entry['icon_paths']; $i++)
                                    <i class="path{{ $i }}"></i>
                                @endfor
                            </i>
                            {{ $entry['title'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- No results message --}}
    <div class="guide-no-results" id="guideNoResults">
        <i class="ki-duotone ki-magnifier"><i class="path1"></i><i class="path2"></i></i>
        <p class="fs-6 fw-semibold mb-1">Aucun resultat trouve</p>
        <p class="fs-7">Essayez avec d'autres mots-cles.</p>
    </div>

    {{-- Sections --}}
    @foreach($sections as $sectionKey => $section)
        <div class="guide-section" id="section-{{ $sectionKey }}" data-guide-section>
            <div class="guide-section-header">
                <i class="ki-duotone {{ $section['icon'] }} fs-2">
                    @for($i = 1; $i <= $section['icon_paths']; $i++)
                        <i class="path{{ $i }}"></i>
                    @endfor
                </i>
                <h4>{{ $section['title'] }}</h4>
                <span class="badge bg-white bg-opacity-25 ms-auto guide-section-count">{{ count($section['entries']) }}</span>
            </div>
            <div class="guide-section-body">
                @foreach($section['entries'] as $entry)
                    <div class="guide-entry"
                         id="entry-{{ $entry['key'] }}"
                         data-guide-entry
                         data-guide-title="{{ mb_strtolower($entry['title']) }}"
                         data-guide-description="{{ mb_strtolower($entry['description']) }}">
                        <button class="guide-entry-header collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapse-{{ $entry['key'] }}"
                                aria-expanded="false"
                                aria-controls="collapse-{{ $entry['key'] }}">
                            <i class="ki-duotone {{ $entry['icon'] }} fs-3">
                                @for($i = 1; $i <= $entry['icon_paths']; $i++)
                                    <i class="path{{ $i }}"></i>
                                @endfor
                            </i>
                            <span>{{ $entry['title'] }}</span>
                            <i class="ki-duotone ki-down fs-5 chevron"><i class="path1"></i></i>
                        </button>
                        <div id="collapse-{{ $entry['key'] }}" class="collapse">
                            <div class="guide-entry-body">
                                <p class="guide-entry-description">{{ $entry['description'] }}</p>

                                @if(!empty($entry['steps']))
                                    <div class="guide-steps-title">Comment faire</div>
                                    <ol class="guide-steps-list">
                                        @foreach($entry['steps'] as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ol>
                                @endif

                                @if(!empty($entry['tips']))
                                    <div class="guide-tips-title">Astuces</div>
                                    <div class="guide-tips-box">
                                        <ul>
                                            @foreach($entry['tips'] as $tip)
                                                <li>{{ $tip }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('guideSearch');
    const resultsCount = document.getElementById('guideResultsCount');
    const noResults = document.getElementById('guideNoResults');
    const toc = document.getElementById('guideToc');
    const sections = document.querySelectorAll('[data-guide-section]');
    const entries = document.querySelectorAll('[data-guide-entry]');

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();

        if (!query) {
            // Reset: show everything
            entries.forEach(function (el) { el.style.display = ''; });
            sections.forEach(function (el) { el.style.display = ''; });
            toc.style.display = '';
            noResults.style.display = 'none';
            resultsCount.style.display = 'none';
            return;
        }

        toc.style.display = 'none';
        let visibleCount = 0;

        entries.forEach(function (entry) {
            const title = entry.getAttribute('data-guide-title') || '';
            const desc = entry.getAttribute('data-guide-description') || '';
            const text = entry.textContent.toLowerCase();
            const match = title.indexOf(query) !== -1
                       || desc.indexOf(query) !== -1
                       || text.indexOf(query) !== -1;

            entry.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Hide sections with no visible entries
        sections.forEach(function (section) {
            const visibleEntries = section.querySelectorAll('[data-guide-entry]:not([style*="display: none"])');
            section.style.display = visibleEntries.length > 0 ? '' : 'none';
        });

        // Update results count
        resultsCount.style.display = '';
        resultsCount.textContent = visibleCount + ' resultat' + (visibleCount !== 1 ? 's' : '') + ' pour "' + this.value.trim() + '"';

        // No results
        noResults.style.display = visibleCount === 0 ? '' : 'none';
    });

    // Smooth scroll for TOC links
    document.querySelectorAll('.guide-toc-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Open the accordion
                const collapseEl = target.querySelector('.collapse');
                if (collapseEl && !collapseEl.classList.contains('show')) {
                    const btn = target.querySelector('[data-bs-toggle="collapse"]');
                    if (btn) btn.click();
                }
            }
        });
    });
});
</script>
@endpush





