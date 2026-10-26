@php
    $release = \App\Support\ReleaseNotes::pendingFor(auth()->user());
@endphp
@if($release)
<style>
    .whats-new-overlay { align-items: center; }
    .whats-new {
        background: var(--bg-card);
        width: 100%;
        max-width: 100%;
        max-height: calc(100vh - 24px);
        overflow-y: auto;
        border-radius: 20px 20px 0 0;
        padding: 0;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.25);
        transform: translateY(100%);
        transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .whats-new-overlay.show .whats-new { transform: translateY(0); }

    .whats-new-head {
        position: relative;
        padding: 26px 24px 20px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: #fff;
        overflow: hidden;
    }
    .whats-new-head::after {
        content: '';
        position: absolute;
        right: -50px;
        top: -60px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }
    .whats-new-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--accent);
        margin-bottom: 8px;
    }
    .whats-new-kicker::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
    .whats-new-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
        color: #fff;
        position: relative;
    }
    .whats-new-intro { margin: 8px 0 0; font-size: 13px; color: rgba(255, 255, 255, 0.82); position: relative; }

    .whats-new-list { list-style: none; margin: 0; padding: 18px 20px 6px; display: flex; flex-direction: column; gap: 10px; }
    .whats-new-item {
        display: flex;
        gap: 14px;
        padding: 13px 14px;
        border-radius: 14px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
    }
    .whats-new-item-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-card);
        color: var(--primary);
        box-shadow: var(--neu-shadow-sm);
    }
    .whats-new-item-icon .ki-duotone { font-size: 20px; }
    .whats-new-item-title { font-size: 14.5px; font-weight: 700; color: var(--text-primary); margin-bottom: 3px; }
    .whats-new-item-text { font-size: 12.5px; line-height: 1.5; color: var(--text-secondary); }
    .whats-new-item-tag {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 7px;
        border-radius: 999px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        vertical-align: middle;
        background: var(--warning-light);
        color: var(--accent-dark);
    }

    .whats-new-actions { padding: 14px 20px calc(22px + env(safe-area-inset-bottom)); }
    .whats-new-actions .btn { width: 100%; min-height: 48px; font-weight: 700; border-radius: 12px; }

    @media (min-width: 576px) {
        .whats-new-overlay { padding: 20px; }
        .whats-new {
            max-width: 520px;
            border-radius: 18px;
            transform: scale(0.94) translateY(16px);
        }
        .whats-new-overlay.show .whats-new { transform: scale(1) translateY(0); }
        .whats-new-head { padding: 30px 30px 24px; }
        .whats-new-list { padding: 22px 26px 8px; }
        .whats-new-actions { padding: 16px 26px 26px; display: flex; justify-content: flex-end; }
        .whats-new-actions .btn { width: auto; min-width: 180px; }
    }
</style>

<div class="confirm-modal-overlay whats-new-overlay" id="whatsNewModal" role="dialog" aria-modal="true" aria-labelledby="whatsNewTitle">
    <div class="whats-new">
        <div class="whats-new-head">
            <span class="whats-new-kicker">Nouveautes</span>
            <h2 class="whats-new-title" id="whatsNewTitle">{{ $release['title'] }}</h2>
            <p class="whats-new-intro">{{ $release['intro'] }}</p>
        </div>
        <ul class="whats-new-list">
            @foreach($release['items'] as $item)
                <li class="whats-new-item">
                    <span class="whats-new-item-icon" aria-hidden="true">
                        <i class="ki-duotone {{ $item['icon'] }}">@for($i = 1; $i <= $item['paths']; $i++)<i class="path{{ $i }}"></i>@endfor</i>
                    </span>
                    <div>
                        <div class="whats-new-item-title">
                            {{ $item['title'] }}
                            @if(!empty($item['roles']))
                                <span class="whats-new-item-tag">Admin</span>
                            @endif
                        </div>
                        <div class="whats-new-item-text">{{ $item['text'] }}</div>
                    </div>
                </li>
            @endforeach
        </ul>
        <form class="whats-new-actions" action="{{ route('whats-new.seen') }}" method="POST" id="whatsNewForm" data-no-loading>
            @csrf
            <button type="submit" class="btn btn-itcj" id="whatsNewOk">C'est note, merci</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('whatsNewModal');
    var form = document.getElementById('whatsNewForm');
    if (!overlay || !form) return;

    var closed = false;

    function markSeen() {
        if (closed) return;
        closed = true;
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        setTimeout(function () { overlay.remove(); }, 250);

        // Enregistre sans recharger ; en cas d'echec reseau le formulaire
        // sera simplement represente a la prochaine page.
        var token = form.querySelector('input[name="_token"]').value;
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).catch(function () {});
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        markSeen();
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) markSeen();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('show')) markSeen();
    });

    // Laisse la page se poser avant d'ouvrir l'encart
    setTimeout(function () {
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        var panel = overlay.querySelector('.whats-new');
        if (panel) panel.scrollTop = 0;
        var btn = document.getElementById('whatsNewOk');
        if (btn) { try { btn.focus({ preventScroll: true }); } catch (err) { btn.focus(); } }
        if (panel) panel.scrollTop = 0;
    }, 600);
});
</script>
@endif
