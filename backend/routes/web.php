<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    return response()->file(public_path('spa.html'));
})->where('any', '^(?!api(?:/|$)).*');
