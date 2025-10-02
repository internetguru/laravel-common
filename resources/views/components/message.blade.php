@props([
    'type',
    'message',
    'class' => '',
])

@php
    $type = $type ?? 'info';
@endphp

<div class="toast text-white bg-{{ $type == 'error' ? 'danger' : $type }} m-3 border-0 {{ $class ?? '' }}"
    data-testid="system-message-{{ $type }}"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
    data-bs-autohide="{{ $type == 'success' ? 'true' : 'false' }}"
    data-bs-delay="8000"
    style="
        padding: 0.3em 0.8em;
        border-radius: 0.2em;
        max-width: 30em;
    ">
    <div class="d-flex">
        <div class="toast-body flex-grow-1">
            {!! $message !!}
        </div>
        <button type="button" class="btn-close btn-close-white m-2" style="margin-top: 0.7em; flex-shrink: 0;" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>
