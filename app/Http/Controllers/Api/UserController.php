<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    use ApiResponser;

    /**
     * Get user info on auth.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = auth()->user();

        return $this->success(200, 'User found', $user);
    }

    /**
     * Get user info on auth.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get_one(Request $request): JsonResponse
    {
        $user = User::where('id', $request->route('id'))->first();
        if(!$user) { return $this->error(404, 'User not found', []); }

        return $this->success(200, 'User found', $user);
    }

    /**
     * Edit user info.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function edit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required_without_all:login,email,password', 'string', 'max:255'],
            'login' => ['required_without_all:full_name,email,password', 'max:255', 'unique:users'],
            'email' => ['required_without_all:full_name,login,password', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required_without_all:full_name,login,email', 'confirmed', Rules\Password::defaults()],
        ]);

        if($validator->fails()) {
            return $this->error(422, 'Validation error', [$validator->errors()]);
        }

        $user = auth()->user();
        if(!empty($request->full_name)) { $user->full_name = $request->full_name; }
        if(!empty($request->login)) { $user->login = $request->login; }
        if(!empty($request->email)) { $user->email = $request->email; }
        if(!empty($request->password)) { $user->password = bcrypt($request->password); }

        $user->save();

        return $this->success(200, 'User has been successfully updated', $user->getChanges());
    }
}
