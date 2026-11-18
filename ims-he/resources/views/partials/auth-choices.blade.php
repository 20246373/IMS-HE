{{-- Customer / Employee choice tiles for the log in and sign up pages. Expects $customerUrl and $employeeUrl. --}}
<div class="choice-list">
 <a href="{{ $customerUrl }}">
  <span class="choice-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg></span>
  <span class="choice-text"><strong>Customer</strong><small>Shop products and track your orders</small></span>
  <span class="choice-arrow"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></span>
 </a>
 <a href="{{ $employeeUrl }}">
  <span class="choice-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/></svg></span>
  <span class="choice-text"><strong>Employee</strong><small>Access the staff workspace</small></span>
  <span class="choice-arrow"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></span>
 </a>
</div>
