<?php

use Illuminate\View\View;

Route::get('/test-500', function (): never {
    throw new Exception('Test 500 error');
});

Route::get('/i18n/missing-all', function (): View {
    return view('ig-common::layouts.base', [
        'view' => 'i18n.missing-all',
    ]);
})->name('i18n.missing-all');

Route::get('/i18n/missing-cs', function (): View {
    return view('ig-common::layouts.base', [
        'view' => 'i18n.missing-cs',
    ]);
})->name('i18n.missing-cs');

Route::get('/i18n/missing-en', function (): View {
    return view('ig-common::layouts.base', [
        'view' => 'i18n.missing-en',
    ]);
})->name('i18n.missing-en');

Route::get('/i18n/complete', function (): View {
    return view('ig-common::layouts.base', [
        'view' => 'i18n.complete',
    ]);
})->name('i18n.complete');
