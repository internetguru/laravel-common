@if (config('app.readonly', false))
    <div class="alert alert-warning">
        <p>
            <i class="fa fa-exclamation-triangle"></i>
            {!! \Illuminate\Support\Str::inlineMarkdown(__('ig-common::layouts.readonly_mode')) !!}
        </p>
    </div>
@endif