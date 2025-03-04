<?php

use Illuminate\View\View;

Route::get('/test-500', function (): never {
    throw new Exception('Test 500 error');
});

Route::get('/test-trans', function (): View {
    return view('ig-common::test-trans');
});
