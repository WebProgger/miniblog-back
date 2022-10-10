<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot_password', [AuthController::class, 'forgot_password'])
    ->middleware('guest')
    ->name('password.email');
Route::patch('reset_password', [AuthController::class, 'reset_password']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('user/me', [UserController::class, 'me']);
    Route::patch('user/me', [UserController::class, 'edit']);

    Route::get('user/{id}/isfollowed', [FollowController::class, 'isFollowed']);
    Route::get('user/{id}/follow', [FollowController::class, 'follow']);
    Route::get('user/{id}/unfollow', [FollowController::class, 'unfollow']);

    Route::get('post/{id}/like', [LikeController::class, 'like']);
    Route::get('post/{id}/unlike', [LikeController::class, 'unlike']);


    Route::post('post', [PostController::class, 'create']);
    Route::patch('post/{id}', [PostController::class, 'edit']);
    Route::delete('post/{id}', [PostController::class, 'delete']);
});

Route::middleware('guest')->group(function () {
    Route::get('user/{id}', [UserController::class, 'get_one']);

    Route::get('user/{id}/followers', [FollowController::class, 'followers']);
    Route::get('user/{id}/follows', [FollowController::class, 'follows']);

    Route::get('posts', [PostController::class, 'get']);

    Route::get('post/{id}', [PostController::class, 'get_one']);
});




