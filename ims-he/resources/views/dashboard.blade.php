@extends('layouts.app')
@section('title','Dashboard')
@section('heading')Welcome back, {{ auth()->user()->displayName() }}@endsection

@push('head')
<style>
  .dash-sub {
    margin: -0.4rem 0 1.5rem;
    color: #64748b;
    font-size: 0.95rem;
  }
  .dash-sub strong {
    color: #1f3a5f;
    background: #e2e8f0;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
  }

  /* Metric Stat Cards */
  .dash-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
  }
  
  .stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 8px rgba(15, 31, 51, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(15, 31, 51, 0.08);
    border-color: #cbd5e1;
  }

  .stat-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #0f1f33;
    line-height: 1.1;
  }
  .stat-value.alert-val {
    color: #b42318;
  }
  .stat-value.ok-val {
    color: #1e6b34;
  }

  .dash-section {
    margin-bottom: 1.5rem;
  }
</style>
@endpush

@section('content')
<p class="dash-sub">Signed in as <strong>{{ auth()->user()->role }}</strong></p>

<div style="display: flex; flex-direction: column; gap: 0.5rem;">
 @forelse($widgets as $widget)
  <div class="dash-section">
   @include('dashboard._'.$widget)
  </div>
 @empty
  <div class="card"><p>No dashboard modules are assigned to your role yet.</p></div>
 @endforelse
</div>
@endsection