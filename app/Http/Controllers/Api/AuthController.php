<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Create new user.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if($validator->fails()) {
            return $this->error(422, 'Validation error', [$validator->errors()]);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'login' => $request->login,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('authToken', ['server:update'])->plainTextToken;
        $id = $user->id;

        return $this->success(201, 'Registration successfully',
            [
                'id' => $id,
                'token' => $token,
            ]
        );
    }

    /**
     * Authorize user.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', Rules\Password::defaults()]
        ]);

        if($validator->fails()) {
            return $this->error(422, 'Validation error', [$validator->errors()]);
        }

        if(!Auth::attempt($request->all())) {
            return $this->error(401, 'Unauthorized', []);
        }
        $token = auth()->user()->createToken('authToken', ['server:update'])->plainTextToken;
        $id = auth()->id();

        return $this->success(200, 'Authorized',
            [
                'id' => $id,
                'token' => $token,
            ]
        );
    }

    /**
     * Logout user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        if(!$request->hasHeader('Authorization') || !Auth::hasUser()) {
            return $this->error(401, 'Unauthenticated', []);
        }
        auth()->user()->tokens()->delete();
        return $this->success(200, 'Logout successfully', []);
    }

    /**
     * Forgot password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgot_password(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if(!$user) { return $this->error(404, 'User not found', []); }

        $token = Password::createToken($user);
        return $this->success(201, 'Reset password token created', [
            'token' => $token
        ]);
    }

    /**
     * Reset password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reset_password(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'token' => ['required', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if($validator->fails()) {
            return $this->error(422, 'Validation error', [$validator->errors()]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ]);
                $user->save();
                Password::deleteToken($user);
            }
        );

        return match ($status) {
            Password::PASSWORD_RESET => $this->success(200, 'Password changed successfully', []),
            default => $this->error(404, 'Reset password failed', ['error' => $status]),
        };

    }
}
