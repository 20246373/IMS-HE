<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'IMS-HE') - Ibay Enterprises</title>
<style>
 :root{--brand:#1f3a5f;--brand-dark:#162b47;--brand-soft:#eaf0f8;--brand-mid:#c3d3e8;--bg:#f4f5f7;--line:#e3e5e8;--text:#222;--muted:#6b7686;--red:#a33}
 *{box-sizing:border-box}
 body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;margin:0;background:var(--bg);color:var(--text);line-height:1.45}
 a{color:var(--brand)}
 .wrap{max-width:1200px;margin:0 auto;padding:0 1rem}
 .sp{flex:1}

 /* top utility bar */
 .topbar{background:var(--brand-dark);color:#cfdaea;font-size:.8rem}
 .topbar .wrap{display:flex;align-items:center;gap:.8rem;padding-top:.35rem;padding-bottom:.35rem}
 .topbar a,.topbar button.link{color:#fff;text-decoration:none;background:none;border:0;padding:0;font-size:.8rem;cursor:pointer}
 .topbar a:hover,.topbar button.link:hover{text-decoration:underline}
 .topbar .sep{width:1px;height:12px;background:#5d7595}

 /* main header */
 .header{background:var(--brand);position:sticky;top:0;z-index:20;box-shadow:0 2px 6px #0003}
 .header .wrap{display:flex;align-items:center;gap:1.2rem;padding-top:.8rem;padding-bottom:.8rem}
 .brand{display:flex;align-items:center;gap:.6rem;color:#fff;text-decoration:none;font-weight:700;font-size:1.3rem;letter-spacing:.02em}
 .logo{width:38px;height:38px;border-radius:9px;background:#fff;color:var(--brand);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800}
 .search{flex:1;display:flex;background:#fff;border-radius:6px;padding:3px;max-width:720px}
 .search input{flex:1;border:0;outline:0;padding:.55rem .8rem;font-size:.95rem;background:transparent;margin:0;min-width:0}

 .search input:focus{outline:0}
 .search button{background:var(--brand);border-radius:5px;padding:0 1.1rem;display:flex;align-items:center}
 .search button:hover{background:var(--brand-dark)}
 .cart{position:relative;color:#fff;display:flex;padding:.4rem}
 .cart .count{position:absolute;top:-4px;right:-6px;background:#fff;color:var(--brand);border-radius:99px;font-size:.7rem;font-weight:700;min-width:19px;height:19px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--brand)}
 .hlink{color:#fff;text-decoration:none;font-size:.9rem;border:1px solid #ffffff66;padding:.35rem .8rem;border-radius:6px}
 .hlink:hover{background:#ffffff1f}

 /* category strip */
 .catstrip{background:#fff;border-bottom:1px solid var(--line)}
 .catstrip .wrap{display:flex;gap:.4rem;overflow-x:auto;padding-top:.5rem;padding-bottom:.5rem;scrollbar-width:thin}
 .catstrip a{white-space:nowrap;text-decoration:none;font-size:.86rem;padding:.3rem .85rem;border-radius:99px;color:var(--brand);border:1px solid transparent}
 .catstrip a:hover{background:var(--brand-soft)}
 .catstrip a.on{background:var(--brand);color:#fff}

 /* layout */
 .layout.staff{display:flex;align-items:flex-start;min-height:calc(100vh - 110px)}
 aside{width:220px;flex-shrink:0;background:#fff;min-height:calc(100vh - 110px);border-right:1px solid var(--line);padding:.8rem 0;position:sticky;top:62px;align-self:flex-start}
 aside h4{margin:1rem 1.1rem .3rem;font-size:.7rem;text-transform:uppercase;color:var(--muted);letter-spacing:.08em}
 aside a{display:block;padding:.5rem 1.1rem;color:var(--brand);text-decoration:none;font-size:.92rem;border-left:3px solid transparent}
 aside a:hover{background:var(--brand-soft)}
 aside a.on{background:var(--brand-soft);font-weight:600;border-left-color:var(--brand)}
 main{flex:1;min-width:0;padding:1.2rem 1rem}
 .layout.staff main{max-width:1100px}
 .layout.shop main{max-width:1200px;margin:0 auto;width:100%}

 /* cards, forms, tables */
 .card{background:#fff;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;box-shadow:0 1px 3px #1f3a5f14}
 .card h3{margin-top:0;color:var(--brand)}
 h2{color:var(--brand)}
 input,select,textarea{padding:.5rem .65rem;margin:.2rem 0;max-width:100%;border:1px solid #cdd5e0;border-radius:6px;font:inherit;background:#fff}
 input:focus,select:focus,textarea:focus{outline:2px solid var(--brand-mid);border-color:var(--brand)}
 label{display:block;margin-top:.6rem;font-size:.86rem;color:#445}
 form > label > input,form > label > select,form > label > textarea{display:block;width:100%}
 button,.btn{background:var(--brand);color:#fff;border:0;border-radius:6px;padding:.5rem 1rem;cursor:pointer;text-decoration:none;display:inline-block;font:inherit;font-size:.9rem;transition:background .15s,transform .05s}
 button:hover,.btn:hover{background:var(--brand-dark)}
 button:active,.btn:active{transform:translateY(1px)}
 button:disabled{background:#9aa5b4;cursor:not-allowed}
 .btn.outline{background:#fff;color:var(--brand);border:1px solid var(--brand)}
 .btn.outline:hover{background:var(--brand-soft)}
 .btn.big{padding:.7rem 1.6rem;font-size:1rem}
 .danger{background:var(--red)}.danger:hover{background:#862a2a}.muted{background:#667}.muted:hover{background:#4d5666}
 .ok{background:#e6f4ea;color:#1c5a2e;padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #2e9d52}
 .err{background:#fdecea;padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;color:#8a1f17;border-left:4px solid var(--red)}
 table{width:100%;border-collapse:collapse}
 th,td{padding:.6rem .6rem;border-bottom:1px solid #eef0f3;text-align:left}
 th{font-size:.74rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);background:#fafbfd}
 table tr:hover td{background:#f8fafd}
 table tr.lowrow td{background:#fdecea}
 .low{color:var(--red);font-weight:600}
 .badge{display:inline-block;padding:.12rem .6rem;border-radius:99px;font-size:.76rem;background:#e3e5e8;color:#445}
 .badge.red{background:#fdecea;color:#8a1f17}.badge.green{background:#e6f4ea;color:#1c6b32}.badge.amber{background:#fff4d6;color:#8a6200}
 .inline{display:inline}.row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}

 /* dashboard stats */
 .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1rem}
 .stat{background:#fff;border-radius:10px;padding:1rem 1.2rem;box-shadow:0 1px 3px #1f3a5f14;border-left:5px solid var(--brand)}
 .stat.alert{border-left-color:var(--red)}
 .stat .lbl{display:block;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
 .stat .val{display:block;font-size:1.9rem;font-weight:700;color:var(--brand);margin:.15rem 0}
 .stat.alert .val{color:var(--red)}

 /* pager */
 .pager{display:flex;gap:.3rem;justify-content:center;margin:1rem 0;flex-wrap:wrap}
 .pager a,.pager span{min-width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:6px;text-decoration:none;font-size:.9rem;padding:0 .5rem}
 .pager a{background:#fff;color:var(--brand);border:1px solid var(--line)}.pager a:hover{background:var(--brand-soft)}
 .pager .current{background:var(--brand);color:#fff}.pager .disabled,.pager .gap{color:#aab;background:transparent}

 /* storefront */
 .hero{background:linear-gradient(120deg,var(--brand-dark),var(--brand) 60%,#2f5688);color:#fff;border-radius:12px;padding:2.4rem 2rem;margin-bottom:1.2rem;position:relative;overflow:hidden}
 .hero::after{content:"";position:absolute;right:-60px;top:-60px;width:260px;height:260px;border-radius:50%;background:#ffffff14}
 .hero::before{content:"";position:absolute;right:90px;bottom:-90px;width:200px;height:200px;border-radius:50%;background:#ffffff0d}
 .hero h1{margin:0 0 .4rem;font-size:2rem;color:#fff}.hero p{margin:0 0 1.2rem;color:#d6e0ee;max-width:520px}
 .hero a.btn{background:#fff;color:var(--brand);font-weight:600;position:relative;z-index:1}
 .section-title{display:flex;align-items:center;justify-content:space-between;margin:1.4rem 0 .7rem}
 .section-title h3{margin:0;color:var(--brand);text-transform:uppercase;letter-spacing:.04em;font-size:1rem}
 .section-title a{font-size:.88rem}
 .cattiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.6rem;background:#fff;padding:1rem;border-radius:10px;box-shadow:0 1px 3px #1f3a5f14}
 .cattile{display:flex;flex-direction:column;align-items:center;gap:.4rem;text-decoration:none;color:var(--text);font-size:.85rem;text-align:center;padding:.6rem .3rem;border-radius:8px}
 .cattile:hover{background:var(--brand-soft)}
 .cattile .ic{width:54px;height:54px;border-radius:50%;background:var(--brand-soft);color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;border:2px solid var(--brand-mid)}
 .cattile small{color:var(--muted)}
 .pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.8rem}
 .pcard{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px #1f3a5f14;transition:transform .15s,box-shadow .15s;display:flex;flex-direction:column;text-decoration:none;color:inherit;border:1px solid transparent}
 .pcard:hover{transform:translateY(-3px);box-shadow:0 8px 18px #1f3a5f26;border-color:var(--brand)}
 .pimg{aspect-ratio:1/1;background:linear-gradient(135deg,var(--brand-soft),var(--brand-mid));display:flex;align-items:center;justify-content:center;font-size:3.4rem;font-weight:800;color:var(--brand);position:relative;overflow:hidden}
 .pimg img{width:100%;height:100%;object-fit:cover}
 .pimg .tag{position:absolute;top:8px;left:0;background:var(--brand);color:#fff;font-size:.68rem;font-weight:600;padding:.15rem .55rem;border-radius:0 4px 4px 0;letter-spacing:.03em}
 .pimg .tag.warn{background:#b7791f}
 .pimg.sold::after{content:"SOLD OUT";position:absolute;inset:0;background:#000a;color:#fff;font-size:1rem;font-weight:700;letter-spacing:.1em;display:flex;align-items:center;justify-content:center}
 .pbody{padding:.6rem .75rem .8rem;display:flex;flex-direction:column;gap:.3rem;flex:1}
 .pname{font-size:.9rem;line-height:1.3;height:2.6em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
 .pbrand{font-size:.75rem;color:var(--muted)}
 .pprice{color:var(--brand);font-weight:700;font-size:1.15rem;margin-top:auto}
 .pprice small{font-size:.75rem;font-weight:600}
 .pmeta{display:flex;justify-content:space-between;font-size:.74rem;color:var(--muted)}
 .shopwrap{display:grid;grid-template-columns:210px 1fr;gap:1rem;align-items:start}
 .filters{background:#fff;border-radius:10px;padding:1rem;box-shadow:0 1px 3px #1f3a5f14;position:sticky;top:76px}
 .filters h4{margin:0 0 .5rem;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--brand)}
 .filters a.flink{display:block;padding:.35rem .5rem;text-decoration:none;color:var(--text);font-size:.9rem;border-radius:5px;border-left:3px solid transparent}
 .filters a.flink:hover{background:var(--brand-soft)}.filters a.flink.on{color:var(--brand);font-weight:600;border-left-color:var(--brand);background:var(--brand-soft)}
 .sortbar{background:#fff;border-radius:10px;padding:.6rem 1rem;margin-bottom:.8rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;box-shadow:0 1px 3px #1f3a5f14}
 .sortbar span{font-size:.85rem;color:var(--muted);margin-right:.3rem}
 .sortbar a{text-decoration:none;font-size:.85rem;padding:.35rem .9rem;border-radius:5px;background:var(--bg);color:var(--text)}
 .sortbar a.on{background:var(--brand);color:#fff}
 .crumbs{font-size:.85rem;color:var(--muted);margin-bottom:.8rem}.crumbs a{text-decoration:none}
 .detail{display:grid;grid-template-columns:420px 1fr;gap:1.6rem}
 .detail .pimg{border-radius:8px;font-size:7rem}
 .detail h1{margin:0 0 .3rem;font-size:1.5rem;color:var(--text)}
 .pricebar{background:var(--brand-soft);padding:.9rem 1.1rem;border-radius:8px;margin:.8rem 0;font-size:2rem;font-weight:700;color:var(--brand)}
 .kv{display:grid;grid-template-columns:130px 1fr;gap:.5rem 1rem;font-size:.92rem;margin:.8rem 0}.kv dt{color:var(--muted)}.kv dd{margin:0}
 .qty{display:inline-flex;align-items:center;border:1px solid #cdd5e0;border-radius:6px;overflow:hidden;background:#fff}
 .qty button{background:#fff;color:var(--brand);border-radius:0;padding:.4rem .8rem;font-size:1.1rem;line-height:1}
 .qty button:hover{background:var(--brand-soft)}
 .qty input{width:56px;text-align:center;border:0;border-left:1px solid #cdd5e0;border-right:1px solid #cdd5e0;border-radius:0;margin:0;padding:.4rem 0;-moz-appearance:textfield}
 .qty input::-webkit-outer-spin-button,.qty input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
 .crow{display:grid;grid-template-columns:90px 1fr 110px 140px 110px 70px;gap:1rem;align-items:center;padding:.9rem 0;border-bottom:1px solid #eef0f3}
 .crow.head{font-size:.74rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding-top:0}
 .crow .pimg{width:90px;height:90px;aspect-ratio:auto;font-size:2rem;border-radius:6px}
 .sumbar{position:sticky;bottom:0;background:#fff;border-top:2px solid var(--brand);box-shadow:0 -4px 14px #1f3a5f1f;padding:.9rem 1.25rem;display:flex;align-items:center;gap:1.2rem;justify-content:flex-end;border-radius:10px 10px 0 0}
 .sumbar .tot{font-size:1.5rem;font-weight:700;color:var(--brand)}
 .auth{max-width:440px;margin:1.5rem auto}.auth.wide{max-width:600px}
 .auth .logo{background:var(--brand);color:#fff;margin:0 auto .6rem;width:52px;height:52px;font-size:1.2rem}

 .auth label>input,.cols2 label>input{display:block;width:100%}
 .auth h2{text-align:center;margin:.2rem 0 1rem}
 .cols2{display:grid;grid-template-columns:1fr 1fr;gap:0 1rem}
 .auth button.full{width:100%;margin-top:1rem;padding:.7rem}
 footer{text-align:center;color:var(--muted);font-size:.8rem;padding:1.5rem 1rem 2rem}

 @media (max-width:900px){
  .shopwrap{grid-template-columns:1fr}.filters{position:static}
  .detail{grid-template-columns:1fr}
  .crow{grid-template-columns:70px 1fr;gap:.6rem}.crow.head{display:none}
  .crow .pimg{width:70px;height:70px}
 }
 @media (max-width:760px){
  .layout.staff{display:block}
  aside{width:100%;min-height:0;position:static;border-right:0;border-bottom:1px solid var(--line)}
  .header .wrap{flex-wrap:wrap}.search{order:3;flex-basis:100%;max-width:none}
  .cols2{grid-template-columns:1fr}
 }
 @media print{.topbar,.header,.catstrip,aside,footer,.noprint{display:none!important} body{background:#fff} .card{box-shadow:none}}
</style>
</head>

<!-- First Layer of Header
Contains:
Info Management Sys · Hardware Enterprises     Sign up | Login 
-->
<body>
@php($u = auth()->user())
@php($isStaff = $u && $u->isStaff())
<div class="topbar"><div class="wrap">
 <span>Info Management Sys &middot; Hardware Enterprises</span>
 <span class="sp"></span>
 @auth
  <span>{{ $u->name }} ({{ str_replace('_',' ',$u->role) }})</span><span class="sep"></span>
  <form method="POST" action="{{ route('logout') }}" style="margin:0">@csrf<button class="link">Logout</button></form>
 @else
  <a href="{{ route('register') }}">Sign up</a><span class="sep"></span><a href="{{ route('login') }}">Login</a>
 @endauth
</div></div>
<header class="header"><div class="wrap">
<!-- Second Layer of Header
contains:
IMS-HE      Search bar      Cart icon 

- The logo was temporarily commented out.
-->
 <a class="brand" href="{{ $isStaff ? route('dashboard') : route('home') }}"><!--<span class="logo">IH</span>--> IMS-HE</a>
 @if($isStaff)
  <span class="sp"></span>
  <a class="hlink" href="{{ route('home') }}">View store</a>
 @else
  <form class="search" action="{{ route('catalog') }}" method="GET">
   <input name="q" value="{{ request('q') }}" placeholder="Search for tools, paint, plumbing, electrical...">
   <button aria-label="Search"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></button>
  </form>
  <a class="cart" href="{{ route('cart.index') }}" aria-label="Cart" title="My cart">
   <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.4a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 7H6"/></svg>
   @if($cartCount > 0)<span class="count">{{ $cartCount }}</span>@endif
  </a>
 @endif
</div></header>
@unless($isStaff)
<div class="catstrip"><div class="wrap">
 <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}">Home</a>
 <a href="{{ route('catalog') }}" class="{{ request()->routeIs('catalog') && ! request('category') ? 'on' : '' }}">All products</a>
 @foreach($navCategories as $nc)
  <a href="{{ route('catalog',['category'=>$nc->id]) }}" class="{{ request('category') == $nc->id ? 'on' : '' }}">{{ $nc->name }}</a>
 @endforeach
</div></div>
@endunless
<div class="layout {{ $isStaff ? 'staff' : 'shop wrap' }}">
@if($isStaff)
<aside>
 <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'on' : '' }}">Dashboard</a>
 @if($u->hasRole('president','inventory_clerk','store_supervisor'))
  <h4>Inventory</h4>
  <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'on' : '' }}">Products</a>
  <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'on' : '' }}">Categories</a>
  <a href="{{ route('stock.index') }}" class="{{ request()->routeIs('stock.*') ? 'on' : '' }}">Stock monitoring</a>
  <h4>Procurement</h4>
  <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'on' : '' }}">Suppliers</a>
  <a href="{{ route('purchase-orders.index') }}" class="{{ request()->routeIs('purchase-orders.*') ? 'on' : '' }}">Purchase orders</a>
  <a href="{{ route('deliveries.index') }}" class="{{ request()->routeIs('deliveries.*') ? 'on' : '' }}">Deliveries</a>
 @endif
 @if($u->hasRole('president','store_supervisor','employee'))
  <h4>Sales</h4>
  <a href="{{ route('pos.index') }}" class="{{ request()->routeIs('pos.*') ? 'on' : '' }}">Point of sale</a>
 @endif
 @if($u->hasRole('president'))
  <h4>Administration</h4>
  <a href="{{ route('accounts.index') }}" class="{{ request()->routeIs('accounts.*') ? 'on' : '' }}">Accounts</a>
  <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'on' : '' }}">Audit log</a>
 @endif
</aside>
@endif
<main>
 @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
 @if($errors->any())<div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
 @yield('content')
</main>
</div>
<footer>&copy; {{ date('Y') }} Ibay Enterprises &middot; Inventory Management System</footer>
<script>
 // Quantity steppers (+ / -). A stepper inside a form with data-autosubmit saves on change.
 document.addEventListener('click', e => {
  const b = e.target.closest('.qty button[data-d]'); if (!b) return;
  const input = b.parentElement.querySelector('input');
  const min = parseInt(input.min) || 1, max = parseInt(input.max) || Infinity;
  const v = Math.min(max, Math.max(min, (parseInt(input.value) || min) + parseInt(b.dataset.d)));
  if (v !== parseInt(input.value)) { input.value = v; input.dispatchEvent(new Event('change', {bubbles: true})); }
 });
 document.addEventListener('change', e => {
  const f = e.target.closest('form[data-autosubmit]');
  if (f && e.target.matches('.qty input')) f.submit();
 });
</script>
</body>
</html>
