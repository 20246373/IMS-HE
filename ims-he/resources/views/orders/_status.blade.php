{{-- Order status badge: Pending (amber), Completed (green), Cancelled (red). Used by customer and staff order pages. --}}
<span class="status status-{{ strtolower($status) }}">{{ $status }}</span>
