<?php

use Illuminate\View\View;

Route::get('/test-500', function (): never {
    throw new Exception('Test 500 error');
});

Route::get('/i18n/missing', function (): View {
    return view('ig-common::i18n.missing');
});

Route::get('/i18n/missing-cs', function (): View {
    return view('ig-common::i18n.missing-cs');
});

Route::get('/i18n/missing-en', function (): View {
    return view('ig-common::i18n.missing-en');
});
