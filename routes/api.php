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
    Route::post('register', [AuthController::class, 'register']);
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

        Route::prefix('user-profiles')->group(function () {
            Route::get('/', [UserProfileController::class, 'index']);
            Route::get('/{id}', [UserProfileController::class, 'show']);
            Route::post('/', [UserProfileController::class, 'store']);
            Route::put('/{id}', [UserProfileController::class, 'update']);
            Route::delete('/{id}', [UserProfileController::class, 'destroy']);
            Route::get('/account/{accountId}', [UserProfileController::class, 'getByAccountId']);
            Route::get('/search', [UserProfileController::class, 'search']);
        });
        // user-accounts
        // Route::apiResource('user-accounts', AccountController::class);
        Route::get('user-accounts', [AccountController::class, 'index']);
        Route::get('user-accounts/{id}', [AccountController::class, 'show'])->whereNumber('id');
        Route::post('user-accounts', [AccountController::class, 'store']);
        Route::put('user-accounts/{id}', [AccountController::class, 'update'])->whereNumber('id');
        Route::delete('user-accounts/{id}', [AccountController::class, 'destroy'])->whereNumber('id');
        // topics
        // Route::apiResource('topics', TopicController::class);

        // Route::get('topics', [TopicController::class, 'index']);
        // Route::get('topics/{id}', [TopicController::class, 'show'])->whereNumber('id');
        // Route::post('topics', [TopicController::class, 'store']);
        // Route::put('topics/{id}', [TopicController::class, 'update'])->whereNumber('id');
        // Route::delete('topics/{id}', [TopicController::class, 'destroy'])->whereNumber('id');
        Route::prefix('topics')->group(function () {
            // ... other routes ...

            // Topics
            Route::get('/', [TopicController::class, 'index']);
            Route::post('/', [TopicController::class, 'store']);
            Route::get('/{id}', [TopicController::class, 'show']);
            Route::put('/{id}', [TopicController::class, 'update']);
            Route::delete('/{id}', [TopicController::class, 'destroy']);

            // Topic steps
            Route::get('/{topicId}/steps', [TopicController::class, 'getSteps']);
            Route::post('/{topicId}/steps', [TopicController::class, 'addStep']);

            // Topic search and filtering
            Route::get('/search', [TopicController::class, 'search']);
            Route::get('/account/{accountId}', [TopicController::class, 'getByAccount']);
        });
        // // solution-steps
        // Route::apiResource('solution-steps', SolutionStepController::class);
        // votes
        // Route::apiResource('votes', VoteController::class);
        // Route::get('votes', [VoteController::class, 'index']);
        // Route::get('votes/{id}/{idAccount?}', [VoteController::class, 'show'])->whereNumber('id', 'idAccount');
        // Route::post('votes', [VoteController::class, 'store']);
        // Route::put('votes/{id}/{idAccount}', [VoteController::class, 'update'])->whereNumber('id', 'idAccount');
        // Route::delete('votes/{id}/{idAccount}', [VoteController::class, 'destroy'])->whereNumber('id', 'idAccount');

        Route::post('/topics/{topicId}/vote', [VoteController::class, 'vote']);
        Route::prefix('votes')->group(function () {
            Route::get('/topic/{topicId}', [VoteController::class, 'getVotesForTopic']);
            Route::get('/account/{accountId}', [VoteController::class, 'getVotesByAccount']);
            Route::get('/stats/{topicId}', [VoteController::class, 'getVoteStats']);
            Route::delete('/{id}', [VoteController::class, 'destroy']);
            Route::get('/check/{topicId}/{accountId}', [VoteController::class, 'checkVote']);
        });
    }
);
