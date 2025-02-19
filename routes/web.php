<?php

Route::get('/test-500', function (): never {
    throw new Exception('Test 500 error');
});
