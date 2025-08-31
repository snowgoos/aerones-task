<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::get('/', [FileController::class, 'index']);
Route::resource('files', FileController::class)->only(['index', 'create', 'store']);
Route::post('files/force-download', [FileController::class, 'forceDownload'])
    ->name('files.force-download');
