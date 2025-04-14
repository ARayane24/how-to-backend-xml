<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\SolutionStepController;
use App\Http\Controllers\Api\V1\TopicController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\VoteController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'prefix' => 'v1',
    ],
    function () {
        Route::apiResource('user-profiles', UserProfileController::class);
        Route::apiResource('user-account', AccountController::class);
        Route::apiResource('topics', TopicController::class);
        Route::apiResource('solution-steps', SolutionStepController::class);
        Route::apiResource('vote', VoteController::class);
    }
);
