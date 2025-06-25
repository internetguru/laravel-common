@if (config('app.readonly', false))
    <div class="container-fluid alert alert-warning mb-0 rounded-0" data-testid="readonly-mode-info">
        <p class="my-0">
            {!! \Illuminate\Support\Str::inlineMarkdown(__('ig-common::layouts.readonly_mode')) !!}
        </p>
    </div>
@endif