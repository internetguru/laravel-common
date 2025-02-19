<ul class="list-inline">
    @foreach (config('languages') as $lang => $title)
        @php
            if ($lang == app()->getLocale()) {
                $title = "<strong>$title</strong>";
                $url = '#';
            } else {
                // Get lang url adding ?lang=lang to the current url
                $url = request()->fullUrlWithQuery(['lang' => $lang]);
            }
        @endphp
        <li class="list-inline-item"><a href="{{ $url }}">{!! $title !!}</a></li>
    @endforeach
</ul>
