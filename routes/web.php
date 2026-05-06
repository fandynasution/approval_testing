<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EnvController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/env', [EnvController::class, 'index']);

use App\Http\Controllers\ApprListControllers;

Route::get('/appr-list', [ApprListControllers::class, 'index']);
Route::get('/apprlist/data', [ApprListControllers::class, 'getData'])->name('apprlist.getData');
Route::post('/apprlist/sendData', [ApprListControllers::class, 'sendData'])->name('apprlist.sendData');
