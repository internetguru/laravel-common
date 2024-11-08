<nav style="--bs-breadcrumb-divider: '{{ $divider }}';" aria-label="breadcrumb">
    <ol class="breadcrumb" style="margin-bottom: 0;">
        @foreach ($items as $index => $item)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                @if ($loop->last || ! $item['uri'])
                    {!! $item['translation'] !!}
                @else
                    <a href="{{ $item['uri'] }}">{!! $item['translation'] !!}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
