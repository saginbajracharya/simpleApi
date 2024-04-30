<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/route-cache', function() {
    Artisan::call('route:cache');
    return 'Routes cache has been cleared';
});
Route::get('/view-cache', function() {
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    return 'View cache has been cleared';
});

Route::post('/login', 'App\Http\Controllers\UserController@login');
Route::post('/signup', 'App\Http\Controllers\UserController@signup');
Route::post('forgotPassword', 'App\Http\Controllers\UserController@forgotPassword');
Route::post('/resetPassword', 'App\Http\Controllers\UserController@resetPassword');
Route::middleware('auth:sanctum')->group(function () {
    Route::resource('users', UserController::class);
    Route::post('/logout', 'App\Http\Controllers\UserController@logout');
    Route::put('/updateProfile', 'App\Http\Controllers\UserController@updateProfile');
    Route::put('/updateResources', [UserController::class, 'updateUserResources']);
});
