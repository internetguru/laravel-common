@props([
    'type',
    'message',
])

<div class="toast text-white bg-{{ $type }} m-3 border-0 {{ $class ?? '' }}" role="alert" aria-live="assertive" aria-atomic="true"
    data-bs-autohide="{{ $type == 'success' ? 'true' : 'false' }}" style="
        padding: 0.3em 0.8em;
        border-radius: 0.2em;
        max-width: 30em;
    ">
    <div class="d-flex">
        <div class="toast-body flex-grow-1">
            {!! $message !!}
        </div>
        <button type="button" class="btn-close btn-close-white m-2" style="margin-top: 0.7em;" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>
