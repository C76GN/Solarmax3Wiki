<?php
// FileName: /var/www/Solarmax3Wiki/routes/api.php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WikiSearchController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/wiki/search', [WikiSearchController::class, 'search']);
