<!-- Component Signatures -->
@if (config('app.debug'))
    @php
        $components = glob(base_path('vendor/internetguru/*'));
    @endphp

    <!--
    @foreach ($components as $component)
        @php
            try {
                $version = trim(file_get_contents("$component/VERSION"));
            } catch (Exception $e) {
                $version = 'unknown';
            }
        @endphp
        {{ basename($component) }} {{ $version }}
    @endforeach
    -->
@else
    <!-- Available in DEBUG mode. -->
@endif