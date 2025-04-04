<?php

use GuzzleHttp\Exception\ConnectException;
use Illuminate\View\View;

Route::get('/error', function (): View {
    return view('ig-common::layouts.base', [
        'view' => 'errors.index',
    ]);
})->name('errors.index');

Route::get('/error/401', function (): never {
    abort(401);
})->name('error.401');

Route::get('/error/402', function (): never {
    abort(402);
})->name('error.402');

Route::get('/error/403', function (): never {
    abort(403);
})->name('error.403');

Route::get('/error/404', function (): never {
    abort(404);
})->name('error.404');

Route::get('/error/419', function (): never {
    abort(419);
})->name('error.419');

Route::get('/error/429', function (): never {
    abort(429);
})->name('error.429');

Route::get('/error/500', function (): never {
    throw new Exception('Test 500 error');
})->name('error.500');

Route::get('/error/503', function (): never {
    abort(503);
})->name('error.503');

Route::get('/error/connect', function (): never {
    throw new ConnectException('Test connect error', new \GuzzleHttp\Psr7\Request('GET', 'test'));
})->name('error.connect');

Route::get('/error/{code}', function (): never {
    abort(404);
})->name('error.unknown');

Route::get('/i18n', function (): View {
    return view('ig-common::layouts.base', [
        'view' => 'i18n.index',
    ]);
})->name('i18n.index');

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
