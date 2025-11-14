@section('content')
{{ $content ?? '' }}
@show

@section('footer')
<x-ig-common::emails.footer-plain :$ip :$timezone :$url />
@show
