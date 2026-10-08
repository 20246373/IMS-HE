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
</style>
</head>
<body>
<nav>
 <strong>IMS-HE</strong>
 @auth
  <a href="{{ route('dashboard') }}">Dashboard</a>
  {{-- Teammates: add your module links here, wrapped in the matching role check --}}
  @if(auth()->user()->hasRole('President'))
   <a href="{{ route('users.index') }}">Accounts</a>
   <a href="{{ route('audit.index') }}">Audit log</a>
  @endif
  <span class="sp"></span>
  <span>{{ auth()->user()->username }} ({{ auth()->user()->role }})</span>
  <form method="POST" action="{{ route('logout') }}" class="inline">@csrf<button>Logout</button></form>
 @else
  <span class="sp"></span>
  <a href="{{ route('login') }}">Login</a>
 @endauth
</nav>
<main>
 @if(session('status'))<div class="ok">{{ session('status') }}</div>@endif
 @if($errors->any())<div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
 @yield('content')
</main>
</body>
</html>
