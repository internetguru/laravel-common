@section('content')
<x-ig-common::emails.salutation-plain />
@show

@section('footer')
<x-ig-common::emails.footer-plain :$ip :$timezone />
@show
