<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'IMS-HE') - Ibay Enterprises</title>
<style>
 body{font-family:system-ui,sans-serif;margin:0;background:#f4f5f7;color:#222}
 nav{background:#1f3a5f;color:#fff;padding:.7rem 1.2rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
 nav a{color:#fff;text-decoration:none} nav .sp{flex:1}
 nav.staff-nav{position:fixed;z-index:20;inset:0 auto 0 0;width:248px;box-sizing:border-box;flex-direction:column;flex-wrap:nowrap;align-items:stretch;gap:.35rem;padding:1.5rem 1rem 1rem;background:linear-gradient(165deg,#172d49 0%,#1f3a5f 58%,#25466d 100%);box-shadow:8px 0 28px #14253a20;overflow-y:auto;overflow-x:hidden}
 nav.staff-nav > strong{display:flex;align-items:center;gap:.7rem;font-size:1.05rem;letter-spacing:.06em;padding:.3rem .65rem 1.45rem}
 nav.staff-nav > strong:before{content:"I";display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#d8e8fb;color:#1f3a5f;font-size:1.05rem;font-weight:800;letter-spacing:0;box-shadow:0 3px 10px #071b3333}
 nav.staff-nav > a{display:flex;align-items:center;min-height:42px;padding:0 .8rem;border-radius:9px;color:#d9e4f1;font-size:.94rem;font-weight:500;transition:background .16s ease,color .16s ease,transform .16s ease}
 nav.staff-nav > a:hover,nav.staff-nav .nav-dropdown > div a:hover{background:#ffffff14;color:#fff;transform:translateX(2px)}
 nav.staff-nav > a.nav-link-active,nav.staff-nav .nav-dropdown > div a.nav-link-active{background:#dceafe;color:#19395e;font-weight:650;box-shadow:0 3px 10px #0c1e342b}
 nav.staff-nav .nav-section{padding:1rem .8rem .35rem;color:#9cb2cb;font-size:.68rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase}
 nav.staff-nav .nav-sp{flex:1;min-height:1rem}
 nav.staff-nav .nav-dropdown{border-radius:9px}
 nav.staff-nav .nav-dropdown summary{position:relative;display:flex;align-items:center;min-height:42px;padding:0 2rem 0 .8rem;border-radius:9px;color:#d9e4f1;font-size:.94rem;font-weight:500;transition:background .16s ease,color .16s ease}
 nav.staff-nav .nav-dropdown summary:hover{background:#ffffff14;color:#fff}
 nav.staff-nav .nav-dropdown summary:after{content:"";position:absolute;right:.95rem;width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translateY(-2px);transition:transform .16s ease}
 nav.staff-nav .nav-dropdown[open] summary:after{transform:rotate(225deg) translate(-1px,-1px)}
 nav.staff-nav .nav-dropdown > div{position:static;min-width:0;margin:.2rem 0 .35rem .8rem;padding:.25rem 0 .25rem .65rem;border-left:1px solid #ffffff30;box-shadow:none;background:transparent;gap:.15rem}
 nav.staff-nav .nav-dropdown > div a{display:flex;align-items:center;min-height:36px;padding:0 .7rem;border-radius:7px;color:#c2d1e3;font-size:.88rem;transition:background .16s ease,color .16s ease,transform .16s ease}
 nav.staff-nav .nav-user{display:grid;gap:.18rem;margin:.25rem 0 .55rem;padding:.8rem .85rem;border:1px solid #ffffff18;border-radius:10px;background:#ffffff0b}
 nav.staff-nav .nav-user strong{color:#fff;font-size:.88rem;font-weight:600}
 nav.staff-nav .nav-user span{color:#aebfd3;font-size:.76rem}
 nav.staff-nav form{margin:0}
 nav.staff-nav form button{width:100%;min-height:40px;background:#ffffff12;color:#e8eef6;text-align:left;padding:0 .85rem;border:1px solid #ffffff14;border-radius:9px;font-size:.9rem}
 nav.staff-nav form button:hover{background:#ffffff20;color:#fff}
 body.staff-layout main{width:calc(100% - 294px);max-width:1120px;margin:1.6rem 1.5rem 1.5rem 272px;padding:0}
 @media (max-width:700px){
  nav.staff-nav{width:210px;padding:1rem .8rem .8rem}
  body.staff-layout main{width:calc(100% - 236px);margin:1rem .8rem 1rem 222px}
 }
 @media (max-width:520px){
  nav.staff-nav{position:sticky;inset:auto;width:100%;height:auto;max-height:48vh;border-radius:0 0 16px 16px;padding:.7rem;gap:.2rem}
  nav.staff-nav > strong{padding:.1rem .55rem .55rem}
  nav.staff-nav .nav-section,nav.staff-nav .nav-sp,nav.staff-nav .nav-user{display:none}
  nav.staff-nav > a,nav.staff-nav .nav-dropdown summary{min-height:36px;font-size:.88rem}
  nav.staff-nav .nav-dropdown > div{margin-left:.55rem}
  nav.staff-nav form button{min-height:36px}
  body.staff-layout main{width:auto;max-width:none;margin:1rem .8rem;padding:0}
 }
 .nav-dropdown{position:relative}
 .nav-dropdown summary{cursor:pointer;list-style:none}
 .nav-dropdown summary::-webkit-details-marker{display:none}
 .nav-dropdown > div{position:absolute;z-index:10;top:calc(100% + .7rem);left:0;min-width:10rem;background:#1f3a5f;padding:.7rem;display:grid;gap:.7rem;border-radius:0 0 5px 5px;box-shadow:0 2px 6px #0003}
 main{max-width:1000px;margin:1.2rem auto;padding:0 1rem}
 .card{background:#fff;border-radius:8px;padding:1rem 1.2rem;margin-bottom:1rem;box-shadow:0 1px 3px #0001}
 table{width:100%;border-collapse:collapse} th,td{padding:.45rem .5rem;border-bottom:1px solid #eee;text-align:left}
 input,select,textarea{padding:.4rem;margin:.2rem 0;max-width:100%} label{display:block;margin-top:.5rem;font-size:.9rem}
 button,.btn{background:#1f3a5f;color:#fff;border:0;border-radius:5px;padding:.45rem .9rem;cursor:pointer;text-decoration:none;display:inline-block}
 .danger{background:#a33}.ok{background:#e6f4ea;padding:.6rem;border-radius:5px;margin-bottom:1rem}
 .err{background:#fdecea;padding:.6rem;border-radius:5px;margin-bottom:1rem;color:#8a1f17}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}.low{color:#a33;font-weight:600}
 .inline{display:inline;margin:0}
 /* Login: access chooser + forms, built on .card */
 .auth{max-width:400px;margin:auto} .auth h2{margin-top:0}
 .auth-top{display:flex;justify-content:space-between;font-size:.9rem;color:#666;margin-bottom:.4rem}
 .auth-top a{color:#1f3a5f;text-decoration:none}
 .auth input{display:block;width:100%;box-sizing:border-box}
 .auth .btn{display:block;text-align:center;margin-bottom:.5rem}
 .btn-alt{background:#fff;color:#1f3a5f;border:1px solid #1f3a5f}
 /* Storefront (Product Catalog Module) */
 .search{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center} .search input{flex:1;min-width:180px}
 .cats a{display:inline-block;margin:.2rem .4rem .2rem 0;padding:.2rem .6rem;border:1px solid #1f3a5f;border-radius:999px;color:#1f3a5f;text-decoration:none;font-size:.85rem}
 .product{display:flex;flex-direction:column;gap:.25rem;color:inherit;text-decoration:none;margin:0} .product:hover{box-shadow:0 2px 8px #0002}
 .product img,.product .noimg{width:100%;height:150px;object-fit:cover;border-radius:5px}
 .noimg{background:#eef0f3;color:#888;display:flex;align-items:center;justify-content:center;font-size:.85rem}
 .product small,.detail small{color:#666}
 .stock{font-size:.8rem;font-weight:600;padding:.1rem .5rem;border-radius:999px;align-self:flex-start}
 .stock.in{background:#e6f4ea;color:#1e6b34} .stock.out{background:#fdecea;color:#8a1f17}
 .detail{display:grid;grid-template-columns:minmax(0,320px) 1fr;gap:1.2rem} .detail img,.detail .noimg{width:100%;height:280px;object-fit:cover;border-radius:5px}
 .detail h2{margin:.2rem 0} .price{font-size:1.3rem;font-weight:600;margin:.3rem 0}
 @media (max-width:600px){.detail{grid-template-columns:1fr}}
 .pager{display:flex;gap:1rem;justify-content:center;align-items:center} .pager span{color:#888}
 /* Cart */
 .qty{display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;margin:0} .qty label{margin:0}
 .cart-table th,.cart-table td{text-align:center;vertical-align:middle} .cart-table .qty{justify-content:center}
 .cart-table tr > :first-child{text-align:left}   /* Product column stays left-aligned */
 .cart-table .cart-total-row th:first-child{text-align:right}   /* "Total" label sits next to the amount */
 .report-heading{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
 .report-heading h2{margin:.2rem 0}
 .report-filter{display:flex;align-items:end;gap:.8rem;flex-wrap:wrap;margin:1rem 0}
 .report-filter label{margin:0}
 .report-summary{display:flex;gap:1rem;flex-wrap:wrap;margin:1rem 0}
 .report-summary > div{background:#f4f5f7;border-radius:5px;padding:.7rem 1rem;min-width:11rem;display:grid;gap:.3rem}
 .stepper{display:inline-flex;align-items:stretch;border:1px solid #ccc;border-radius:5px;overflow:hidden;background:#fff}
 .stepper button{background:#eef0f3;color:#1f3a5f;border-radius:0;padding:.3rem .7rem;font-size:1rem;font-weight:600}
 .stepper button:hover{background:#dde2e8}
 .stepper input{width:3.5rem;margin:0;border:0;border-left:1px solid #ccc;border-right:1px solid #ccc;text-align:center;-moz-appearance:textfield}
 .stepper input::-webkit-outer-spin-button,.stepper input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
</style>
@stack('head')
</head>
<body class="@auth('web') staff-layout @endauth">
<nav @auth('web') class="staff-nav" @endauth>
 <strong>IMS-HE</strong>
 @auth('web')
  <span class="nav-section">Workspace</span>
  <a class="{{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
  <details class="nav-dropdown">
   <summary>Inventory</summary>
   <div>
    <a class="{{ request()->routeIs('products.*') ? 'nav-link-active' : '' }}" href="{{ route('products.index') }}">Products</a>
    <a class="{{ request()->routeIs('categories.*') ? 'nav-link-active' : '' }}" href="{{ route('categories.index') }}">Categories</a>
    <a class="{{ request()->routeIs('stock.*') ? 'nav-link-active' : '' }}" href="{{ route('stock.index') }}">Stock</a>
   </div>
  </details>
  <span class="nav-section">Operations</span>
  <a class="{{ request()->routeIs('suppliers.*') ? 'nav-link-active' : '' }}" href="{{ route('suppliers.index') }}">Suppliers</a>
  <a class="{{ request()->routeIs('purchase-orders.*') ? 'nav-link-active' : '' }}" href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
  <a class="{{ request()->routeIs('deliveries.*') ? 'nav-link-active' : '' }}" href="{{ route('deliveries.create') }}">Receive Delivery</a>
  <details class="nav-dropdown">
   <summary>Reports</summary>
   <div><a class="{{ request()->routeIs('reports.sales*') ? 'nav-link-active' : '' }}" href="{{ route('reports.sales') }}">Sales report</a><a class="{{ request()->routeIs('reports.inventory*') ? 'nav-link-active' : '' }}" href="{{ route('reports.inventory') }}">Inventory report</a></div>
  </details>
  {{-- Teammates: add your module links here, wrapped in the matching role check --}}
  @if(auth()->user()->hasRole('President'))
   <span class="nav-section">Administration</span>
   <a class="{{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}" href="{{ route('users.index') }}">Accounts</a>
   <a class="{{ request()->routeIs('audit.*') ? 'nav-link-active' : '' }}" href="{{ route('audit.index') }}">Audit log</a>
  @endif
  <span class="nav-sp"></span>
  <div class="nav-user"><strong>{{ auth()->user()->username }}</strong><span>{{ auth()->user()->role }}</span></div>
  <form method="POST" action="{{ route('logout') }}">@csrf<button>Logout</button></form>
 @elseauth('customer')
  <a href="{{ route('home') }}">Home</a>
  <a href="{{ route('catalog.index') }}">Products</a>
  <a href="{{ route('customer.cart.index') }}">Cart (<span id="cart-count">{{ auth('customer')->user()->cart?->items()->sum('quantity') ?? 0 }}</span>)</a>
  <a href="{{ route('customer.account') }}">My account</a>
  <span class="sp"></span>
  <span>{{ auth('customer')->user()->username }}</span>
  <form method="POST" action="{{ route('customer.logout') }}" class="inline">@csrf<button>Logout</button></form>
 @else
  <a href="{{ route('home') }}">Home</a>
  <a href="{{ route('catalog.index') }}">Products</a>
  <span class="sp"></span>
  <a href="{{ route('login') }}">Log in</a>
 @endauth
</nav>
@yield('hero')
<main>
 @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
 @if($errors->any())<div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
 @yield('content')
</main>
<script>
 // Quantity steppers (partials/qty): - / + stay within the input's min and max (available stock).
 document.addEventListener('click', function (e) {
  var btn = e.target.closest('.stepper [data-step]');
  if (!btn) return;
  var input = btn.parentElement.querySelector('input');
  var min = Number(input.min) || 1, max = Number(input.max) || Infinity;
  var next = (Number(input.value) || min) + Number(btn.dataset.step);
  input.value = Math.min(max, Math.max(min, next));
  input.dispatchEvent(new Event('input', { bubbles: true }));   // lets pages react, e.g. the cart's auto-update
 });
</script>
@stack('scripts')
</body>
</html>
