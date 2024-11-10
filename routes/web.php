<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MusicAnalyticsController;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/*', function () {
    return view('error');
});

Route::get('/analytics/popularity/{isrc}', [MusicAnalyticsController::class, 'showPopularityByISRC']);