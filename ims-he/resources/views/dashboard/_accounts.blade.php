@push('head')
<style>
 /* Administrator dashboard: three equal cards, always showing the count and the first names. */
 .account-grid{align-items:stretch;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr))}
 .account-card{display:flex;flex-direction:column;margin:0;padding:1.1rem 1.2rem;border-radius:12px;min-width:0}
 .account-card-head{display:grid;gap:.3rem;padding-bottom:.75rem;border-bottom:1px solid #e4e9f0}
 .account-count{font-size:1.8rem;line-height:1.15;font-weight:700;font-variant-numeric:tabular-nums}
 .account-preview{display:flex;flex-direction:column;flex:1;padding-top:.3rem;font-size:.9rem}
 .account-preview ul{list-style:none;padding:0;margin:0}
 .account-preview li{padding:.45rem 0;border-bottom:1px solid #e4e9f0;overflow-wrap:anywhere}
 .account-preview p{margin:.5rem 0;color:#505d6e}
 .account-preview a{display:inline-flex;align-items:center;min-height:32px;margin-top:auto;padding-top:.5rem;color:#1f3a5f;font-weight:600;text-underline-offset:.18em}
 .account-preview a:focus-visible{outline:2px solid #245184;outline-offset:2px}
</style>
@endpush

@php
    $accountCards = [
        'all' => ['Employee accounts', route('users.index'), 'Manage accounts'],
        'unassigned' => ['Unassigned accounts', route('users.index').($accountSummaries['unassigned']['count'] ? '#unassigned' : ''), 'Review assignments'],
        'locked' => ['Locked accounts', route('users.index'), 'Manage accounts'],
    ];
@endphp

<div class="grid account-grid" id="dashboard-accounts">
 @foreach($accountCards as $group => [$label, $url, $action])
    @php
        ['count' => $count, 'names' => $names] = $accountSummaries[$group];
    @endphp
    <section class="card account-card">
        <div class="account-card-head">
            <span>{{ $label }}</span>
            <strong @class(['account-count', 'low' => $group !== 'all' && $count > 0])>{{ number_format($count) }}</strong>
        </div>
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
    </section>
 @endforeach
</div>
