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
</style>
@stack('head')
</head>
<body>
<nav>
 <strong>IMS-HE</strong>
 @auth('web')
  <a href="{{ route('dashboard') }}">Dashboard</a>
  {{-- User Account Management: Admin only --}}
  @if(auth()->user()->hasRole('Admin'))
   <a href="{{ route('users.index') }}">Accounts</a>
  @endif
  {{-- Financial Recording: Admin and Treasurer --}}
  @if(auth()->user()->hasRole('Admin', 'Treasurer'))
   <a href="{{ route('financial.index') }}">Financial</a>
  @endif
  {{-- Financial Reports: Admin only --}}
  @if(auth()->user()->hasRole('Admin'))
   <a href="{{ route('financial.statements') }}">Statements</a>
   <a href="{{ route('financial.cash-flow') }}">Cash Flow</a>
  @endif
  {{-- Payroll Processing: Treasurer only --}}
  @if(auth()->user()->hasRole('Treasurer'))
   <a href="{{ route('employees.index') }}">Employees</a>
   <a href="{{ route('payrolls.index') }}">Payroll</a>
  @endif
  {{-- Attendance Management: Treasurer and Store Supervisor --}}
  @if(auth()->user()->hasRole('Treasurer', 'Store Supervisor'))
   <a href="{{ route('attendance.index') }}">Attendance</a>
  @endif
  {{-- Inventory Management: Inventory Clerk and Store Supervisor --}}
  @if(auth()->user()->hasRole('Inventory Clerk', 'Store Supervisor'))
   <a href="{{ route('stock.index') }}">Stock</a>
  @endif
  @if(auth()->user()->hasRole('Inventory Clerk'))
   <a href="{{ route('products.index') }}">Products</a>
   <a href="{{ route('categories.index') }}">Categories</a>
  @endif
  {{-- Procurement Management: Inventory Clerk and Store Supervisor --}}
  @if(auth()->user()->hasRole('Inventory Clerk', 'Store Supervisor'))
   <a href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
   <a href="{{ route('deliveries.create') }}">Receive Delivery</a>
  @endif
  @if(auth()->user()->hasRole('Inventory Clerk'))
   <a href="{{ route('suppliers.index') }}">Suppliers</a>
  @endif
  {{-- Sales Management: Store Supervisor and Employee --}}
  @if(auth()->user()->hasRole('Store Supervisor', 'Employee'))
   <a href="{{ route('pos.index') }}">POS</a>
   <a href="{{ route('sales.index') }}">Sales</a>
  @endif
  {{-- Online Order Processing: Admin and Store Supervisor --}}
  @if(auth()->user()->hasRole('Admin', 'Store Supervisor'))
   <a href="{{ route('sales.online') }}">Online Orders</a>
  @endif
  {{-- Report Generation --}}
  @if(auth()->user()->hasRole('Admin'))
   <a href="{{ route('reports.company') }}">Reports</a>
  @elseif(auth()->user()->hasRole('Treasurer'))
   <a href="{{ route('reports.financial') }}">Reports</a>
  @elseif(auth()->user()->hasRole('Store Supervisor'))
   <a href="{{ route('reports.sales') }}">Reports</a>
  @endif
  <span class="sp"></span>
  <span>{{ auth()->user()->username }} ({{ auth()->user()->role }})</span>
  <form method="POST" action="{{ route('logout') }}" class="inline">@csrf<button>Logout</button></form>
 @elseauth('customer')
  @php($cartCount = auth('customer')->user()->cart ? auth('customer')->user()->cart->items()->count() : 0)
  <a href="{{ route('home') }}">Home</a>
  <a href="{{ route('catalog.index') }}">Products</a>
  <a href="{{ route('customer.cart.index') }}">Cart @if($cartCount > 0)<span style="background:#e6f4ea;color:#1e6b34;padding:.1rem .4rem;border-radius:999px;font-size:.8rem">{{ $cartCount }}</span>@endif</a>
  <a href="{{ route('customer.orders.index') }}">My Orders</a>
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
</body>
</html>
