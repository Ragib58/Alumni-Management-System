<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This is a headless API backend. The web routes are intentionally minimal;
| the React SPA (frontend/) consumes the API defined in routes/api.php.
|
*/

Route::get('/', function () {
    return response()->json([
        'application' => config('app.name'),
        'status'      => 'ok',
        'api'         => url('/api'),
        'docs'        => url('/api/documentation'),
    ]);
});
