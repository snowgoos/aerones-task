<?php

declare(strict_types=1);

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::get('downloads/progress', [FileController::class, 'getProgress']);
});
