<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'LMS API is running. Visit the platform at https://mrhifnimuhammad.tech']);
});
