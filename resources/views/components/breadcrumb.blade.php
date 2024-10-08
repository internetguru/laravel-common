<nav style="--bs-breadcrumb-divider: '{{ $divider }}';" aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach ($items as $index => $item)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }} {{ $item['class'] }}">
                @if ($loop->last)
                    {{ $item['translation'] }}
                @else
                    <a href="{{ $item['route'] }}">{{ $item['translation'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
