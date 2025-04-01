<x-app>
    <x-slot:title>
        @yield('code') @yield('title')
    </x-slot>

    <x-slot:description>
        {{-- @lang('layouts.error-page.description') --}}
        @yield('message')
    </x-slot>

    <x-slot:headTitle>
        @yield('code') @yield('title')
    </x-slot>

    <section class="section section-error">
        <div>
            <p class="text-danger"></p>
        </div>
    </section>
</x-app>
