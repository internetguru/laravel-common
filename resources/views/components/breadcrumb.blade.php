{{-- The mark is handed to the stylesheet as a property: it is drawn by pseudo-elements, where an <img> cannot go --}}
<div @class(['ig-breadcrumb', 'ig-breadcrumb-marked' => $mark]) @if ($mark) style="--breadcrumb-home-mark: url('{{ $mark }}');" @endif data-testid="breadcrumb">
    {{-- On a narrow screen the levels fold into a menu behind the first one --}}
    <div class="dropdown ig-breadcrumb-menu d-sm-none">
        <a class="dropdown-toggle" href="{{ $items[0]['uri'] ?? url('/') }}" role="button" id="breadcrumbMenu" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('ig-common::layouts.breadcrumb.toggle') }}">
            @if ($mark)
                <span class="ig-breadcrumb-mark"></span>
            @else
                {!! $items[0]['translation'] ?? '' !!}
            @endif
            <i class="fa-solid fa-fw fa-angle-down" aria-hidden="true"></i>
        </a>
        <ul class="dropdown-menu" aria-labelledby="breadcrumbMenu">
            @foreach ($items as $item)
                @if ($loop->last || ! $item['uri'])
                    <li><span class="dropdown-item-text" @if ($loop->last) aria-current="page" @endif>{!! $item['translation'] !!}</span></li>
                @else
                    <li><a class="dropdown-item" href="{{ $item['uri'] }}">{!! $item['translation'] !!}</a></li>
                @endif
            @endforeach
        </ul>
    </div>
    <nav class="d-none d-sm-block" style="--bs-breadcrumb-divider: '{{ $divider }}';" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            @foreach ($items as $item)
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
</div>
