<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/migrate', function () {
    \Artisan::call('migrate');
    return 'Migrations complete.';
});

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/reset-password', function () {
//     return view('emails.reset-password', [
//         'token' => request()->query('token'),
//         'email' => request()->query('email'),
//     ]);
// })->name('password.reset');
