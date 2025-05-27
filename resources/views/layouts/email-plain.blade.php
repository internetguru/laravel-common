@yield('content')

@hasSection('footer')
@yield('footer')
@else
<x-ig-common::emails.salutation-plain />

<x-ig-common::emails.footer-plain />
@endif