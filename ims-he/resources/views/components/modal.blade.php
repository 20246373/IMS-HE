@props(['id', 'title'])

{{--
 A pop-up for small forms (1-3 fields). Open it with: onclick="document.getElementById('{id}').showModal()".
 Put <input type="hidden" name="_modal" value="{id}"> in its form: if the server rejects the form,
 the page comes back with this pop-up open again (old('_modal')), showing the errors.
--}}
<dialog id="{{ $id }}" class="modal-dialog" onclick="if (event.target === this) this.close()" @if(old('_modal') === $id) data-open-on-load @endif>
 <h3 tabindex="-1" autofocus>{{ $title }}</h3>
 {{ $slot }}
</dialog>
