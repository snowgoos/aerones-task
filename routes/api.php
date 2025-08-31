<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FileController;

Route::group(['prefix' => 'v1'], function () {
    Route::get('downloads/progress', [FileController::class, 'getProgress']);
});
