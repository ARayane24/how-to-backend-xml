<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\SolutionStepController;
use App\Http\Controllers\Api\V1\TopicController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\VoteController;
use App\Http\Controllers\Api\V1\AuthController;

use Illuminate\Support\Facades\Route;

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});


Route::group(
    [
        'prefix' => 'v1',
    ],
    function () {
        // user-profiles
        // Route::apiResource('user-profiles', UserProfileController::class);
        Route::middleware('ourMiddleGuy')->group(function () {
            Route::get('user-profiles', [UserProfileController::class, 'index']);
            Route::get('user-profiles/{id}', [UserProfileController::class, 'show'])->whereNumber('id');
            Route::post('user-profiles/{idAccount}', [UserProfileController::class, 'store'])->whereNumber('idAccount');
        });
        // Route::post('user-profiles/{idAccount}', [UserProfileController::class, 'store'])->whereNumber('idAccount');
        Route::put('user-profiles/{id}', [UserProfileController::class, 'update'])->whereNumber('id');
        Route::delete('user-profiles/{id}', [UserProfileController::class, 'destroy'])->whereNumber('id');
        // user-accounts
        // Route::apiResource('user-accounts', AccountController::class);
        Route::get('user-accounts', [AccountController::class, 'index']);
        Route::get('user-accounts/{id}', [AccountController::class, 'show'])->whereNumber('id');
        Route::post('user-accounts', [AccountController::class, 'store']);
        Route::put('user-accounts/{id}', [AccountController::class, 'update'])->whereNumber('id');
        Route::delete('user-accounts/{id}', [AccountController::class, 'destroy'])->whereNumber('id');
        // topics
        // Route::apiResource('topics', TopicController::class);

        Route::get('topics', [TopicController::class, 'index']);
        Route::get('topics/{id}', [TopicController::class, 'show'])->whereNumber('id');
        Route::post('topics', [TopicController::class, 'store']);
        Route::put('topics/{id}', [TopicController::class, 'update'])->whereNumber('id');
        Route::delete('topics/{id}', [TopicController::class, 'destroy'])->whereNumber('id');
        // // solution-steps
        // Route::apiResource('solution-steps', SolutionStepController::class);
        // votes
        Route::apiResource('votes', VoteController::class);
        Route::get('votes', [VoteController::class, 'index']);
        Route::get('votes/{id}/{idAccount?}', [VoteController::class, 'show'])->whereNumber('id', 'idAccount');
        Route::post('votes', [VoteController::class, 'store']);
        Route::put('votes/{id}/{idAccount}', [VoteController::class, 'update'])->whereNumber('id', 'idAccount');
        Route::delete('votes/{id}/{idAccount}', [VoteController::class, 'destroy'])->whereNumber('id', 'idAccount');
    }
);
