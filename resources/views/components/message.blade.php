@props([
    'type',
    'message',
])

<div class="toast text-white bg-{{ $type }} m-3 border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false" style="--bs-bg-opacity: .9; max-width: 25em;">
    <div class="d-flex">
        <div class="toast-body flex-grow-1">
            {!! $message !!}
        </div>
        <button type="button" class="btn-close btn-close-white m-2" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>