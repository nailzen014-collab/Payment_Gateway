<?php

use App\Http\Controllers\BooksController;
use App\Http\Controllers\SiswaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'Test route works!']);
});

Route::resource('/siswa', SiswaController::class);
Route::resource('/Books', BooksController::class);