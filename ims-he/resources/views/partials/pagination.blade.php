{{-- Page links for every paginated list: Previous / Page X of Y / Next (set as the default in AppServiceProvider). --}}
@if ($paginator->hasPages())
 <div class="pager" role="navigation" aria-label="Pages">
  @if ($paginator->onFirstPage())
   <span class="pager-off">Previous</span>
  @else
   <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
  @endif

  <span>Page {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) of {{ $paginator->lastPage() }}@endif</span>

  @if ($paginator->hasMorePages())
   <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
  @else
   <span class="pager-off">Next</span>
  @endif
 </div>
@endif
