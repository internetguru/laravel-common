@if (config('app.debug'))
    @php
        $components = glob(base_path('vendor/internetguru/*'));
    @endphp

    <!-- Component Signatures -->
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
@endif