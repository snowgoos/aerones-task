<?php

declare(strict_types=1);

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FileController::class, 'index']);
Route::resource('files', FileController::class)->only(['index', 'create', 'store']);
Route::post('files/force-download', [FileController::class, 'forceDownload'])
    ->name('files.force-download');
