@if(config('app.demo'))
    <div class="container-fluid alert alert-info mb-0 rounded-0" data-testid="demo-info">
        <p class="my-0">
            {!! Str::inlineMarkdown(__('ig-common::messages.demo.warning')) !!}
        </div>
    </div>
@endif
