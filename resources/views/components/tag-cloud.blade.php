@if ($items !== [])
    <ul
        {{ $attributes->class(['tag-cloud', 'tag-cloud-typography' => $isTypography]) }}
        @if ($isTypography) x-data="tagCloud" @endif
    >
        @foreach ($items as $item)
            <li class="tag" style="{{ $item['style'] }}">{{ $item['label'] }}</li>
        @endforeach
    </ul>
@endif
