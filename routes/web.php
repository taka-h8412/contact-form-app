<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| お問い合わせフォーム
|--------------------------------------------------------------------------
*/

Route::get('/', [ContactController::class, 'index']);

Route::post('/contacts/confirm', [ContactController::class, 'confirm']);
Route::post('/contacts', [ContactController::class, 'store']);
Route::get('/thanks', [ContactController::class, 'thanks']);

/*
|--------------------------------------------------------------------------
| 管理画面
|--------------------------------------------------------------------------
| 管理画面系はログイン後のみアクセス可能
*/

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/contacts/export', [AdminController::class, 'export']);

    Route::get('/admin/contacts/{contact}', [AdminController::class, 'show']);
    Route::delete('/admin/contacts/{contact}', [AdminController::class, 'destroy']);

    Route::post('/admin/tags', [TagController::class, 'store']);
    Route::get('/admin/tags/{tag}/edit', [TagController::class, 'edit']);
    Route::put('/admin/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/admin/tags/{tag}', [TagController::class, 'destroy']);
});