@php
    $description = trim($slot);
    // A bag passed whole is printed as it is, so its values are escaped here
    $tooltip = $description === '' ? [] : [
        'data-bs-toggle' => 'tooltip',
        'data-bs-title' => e(trim(html_entity_decode(strip_tags($description), ENT_QUOTES))),
        'tabindex' => '0',
    ];
@endphp
@if ($badge instanceof Illuminate\View\ComponentSlot)
    <p class="ig-page-heading-badge" data-testid="page-heading-badge">{{ $badge }}</p>
@elseif ($badge !== null)
    <p class="ig-page-heading-badge">
        <x-ig::label :text="$badge" :variant="$variant" :icon="$badgeIcon" data-testid="page-heading-badge" :attributes="new Illuminate\View\ComponentAttributeBag($tooltip)" />
    </p>
@endif
<h1{{ $attributes->isEmpty() ? '' : ' ' }}{{ $attributes }}>{{ $heading }}</h1>
@if ($badge === null && $description !== '')
    <p class="ig-page-heading-desc ig-page-heading-desc-{{ $variant }}" data-testid="page-heading-desc">{{ $slot }}</p>
@endif
