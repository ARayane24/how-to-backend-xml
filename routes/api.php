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
        // user-profiles
        Route::apiResource('user-profiles', UserProfileController::class);
        // user-accounts
        // Route::apiResource('user-accounts', AccountController::class);
        Route::get('user-accounts', [AccountController::class, 'index']);
        Route::get('user-accounts/{id}', [AccountController::class, 'show'])->whereNumber('id');
        Route::post('user-accounts', [AccountController::class, 'store']);
        Route::put('user-accounts/{id}', [AccountController::class, 'update'])->whereNumber('id');
        Route::delete('user-accounts/{id}', [AccountController::class, 'destroy'])->whereNumber('id');
        // topics
        Route::apiResource('topics', TopicController::class);
        // solution-steps
        Route::apiResource('solution-steps', SolutionStepController::class);
        // votes
        Route::apiResource('votes', VoteController::class);
    }
);
