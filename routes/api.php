<?php

use App\Http\Controllers\Api\GateController;
use Illuminate\Support\Facades\Route;

Route::post('/gate/check', [GateController::class, 'check'])
    ->middleware(['device.key', 'throttle:60,1']);
