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
            $componentName = basename($component);
            $branch = '[detached]';
            $relativePath = str_replace('/var/www/html', '', $component);
            $commit = trim(Storage::disk('root')->get($relativePath . '/.git/HEAD'));
            if (substr($commit, 0, 10) == 'ref: refs/') {
                $branch = substr($commit, 5);
                $commit = trim(Storage::disk('root')->get($relativePath . '/.git/' . $branch));
            }
        @endphp
        {{ $componentName }} {{ $version }} {{ basename($branch) }} {{ substr($commit, 0, 7) }}
    @endforeach
    -->
@endif