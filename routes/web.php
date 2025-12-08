<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->away('https://app.saputragroupindo.com/admin'));
