<?php

use Illuminate\Support\Facades\Route;
use JkOster\CronMonitor\Http\Controllers\CronMonitorController;

Route::get('/ping/{hash}/{status?}', [CronMonitorController::class, 'ping']);
Route::post('/ping/{hash}/{status?}', [CronMonitorController::class, 'ping']);

Route::get('/status/{hash}', [CronMonitorController::class, 'status']);
