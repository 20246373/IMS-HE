@push('head')
<style>
 .account-grid{align-items:start;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr))}
 .account-card{padding:0;margin:0;border-radius:12px;min-width:0}
 .account-card summary{position:relative;display:grid;gap:.4rem;padding:1rem 2.6rem 1rem 1.2rem;border-radius:inherit;cursor:pointer;list-style:none}
 .account-card summary::-webkit-details-marker{display:none}
 .account-card summary:hover,.account-card[open] summary{background:#eff4fa}
 .account-card summary:focus-visible,.account-preview a:focus-visible{outline:2px solid #245184;outline-offset:2px}
 .account-card summary::after{content:"";position:absolute;right:1.25rem;top:1.35rem;width:.45rem;height:.45rem;border:solid #1f3a5f;border-width:0 2px 2px 0;transform:rotate(45deg)}
 .account-card[open] summary::after{transform:rotate(225deg)}
 .account-count{font-size:1.8rem;line-height:1.15;font-weight:700;font-variant-numeric:tabular-nums}
 .account-preview{padding:.4rem 1.2rem .9rem;font-size:.9rem}
 .account-preview ul{list-style:none;padding:0;margin:0}
 .account-preview li{padding:.45rem 0;border-bottom:1px solid #e4e9f0;overflow-wrap:anywhere}
 .account-preview p{margin:.5rem 0;color:#505d6e}
 .account-preview a{display:inline-flex;align-items:center;min-height:32px;color:#1f3a5f;font-weight:600;text-underline-offset:.18em}
</style>
@endpush

@php
    $accountCards = [
        'all' => ['User accounts', route('users.index'), 'Manage accounts'],
        'unassigned' => ['Unassigned accounts', route('users.index').($accountSummaries['unassigned']['count'] ? '#unassigned' : ''), 'Review assignments'],
        'locked' => ['Locked accounts', route('users.index'), 'Manage accounts'],
    ];
@endphp

<div class="grid account-grid" id="dashboard-accounts">
 @foreach($accountCards as $group => [$label, $url, $action])
    @php
        ['count' => $count, 'names' => $names] = $accountSummaries[$group];
    @endphp
    <details class="card account-card">
        <summary>
            <span>{{ $label }}</span>
            <strong @class(['account-count', 'low' => $group !== 'all' && $count > 0])>{{ number_format($count) }}</strong>
        </summary>
        <div class="account-preview">
            @if($names->isEmpty())
                <p>None yet.</p>
            @else
                <ul>
                    @foreach($names as $name)
                        <li>{{ $name }}</li>
                    @endforeach
                </ul>
                @if($count > $names->count())
                    <p>+{{ number_format($count - $names->count()) }} more</p>
                @endif
            @endif
            <a href="{{ $url }}">{{ $action }}</a>
        </div>
    </details>
 @endforeach
</div>

@push('scripts')
<script>
 for (const card of document.querySelectorAll('#dashboard-accounts details')) {
     const summary = card.querySelector('summary');
     let hoverPreview = false;

     card.addEventListener('pointerenter', (event) => {
         if (event.pointerType !== 'mouse' || card.open) return;
         hoverPreview = true;
         card.open = true;
     });

     card.addEventListener('pointerleave', () => {
         if (!hoverPreview) return;
         hoverPreview = false;
         if (!card.querySelector('.account-preview').contains(document.activeElement)) {
             card.open = false;
         }
     });

     summary.addEventListener('click', (event) => {
         if (!hoverPreview) return;
         event.preventDefault();
         hoverPreview = false;
     });

     document.addEventListener('keydown', (event) => {
         const hasFocus = card.contains(document.activeElement);
         if (event.key !== 'Escape' || !card.open || (!hoverPreview && !hasFocus)) return;
         if (hasFocus) summary.focus();
         hoverPreview = false;
         card.open = false;
     });
 }
</script>
@endpush
