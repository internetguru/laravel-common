@if(config('app.demo'))
    <div class="container-fluid alert alert-warning mb-0 rounded-0">
        <p class="my-0">
            {!! Str::inlineMarkdown(__('ig-common::messages.demo.warning')) !!}
        </div>
    </div>
@endif
