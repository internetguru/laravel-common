<div {{ $attributes->class([
    'card',
    'card-linked' => $link,
    'card-gray' => $gray,
]) }}>
    @if ($badges !== [])
        <div class="badge-wrapper">
            @foreach ($badges as $badgeItem)
                <span @class(['badge', "badge-$badgeType" => $badgeType])>{{ $badgeItem }}</span>
            @endforeach
        </div>
    @endif

    @if ($title)
        <h{{ $level }}>{{ $title }}</h{{ $level }}>
    @endif

    @if ($subtitle)
        <p class="lead">{{ $subtitle }}</p>
    @endif

    {{ $slot }}

    @if ($link)
        {{-- Covers the whole card, so a click anywhere but on a nested link follows it. --}}
        <a class="card-action" href="{{ $link }}" aria-label="{{ $linkLabel }}">
            <i class="{{ $icon }}" aria-hidden="true"></i>
        </a>
    @endif
</div>
