<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    use ApiResponser;

    /**
     * Is followed user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function isFollowed(Request $request): JsonResponse
    {
        $user = User::where('id', $request->route('id'))->first();
        if(!$user) { return $this->error(404, 'Followed user not found', []); }

        $follow_count = Follow::where(['user' => $user->id, 'follower' => auth()->id()])->get()->count();

        return $this->success(200, 'OK', ['followed' => $follow_count > 0]);
    }

    /**
     * Follow user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function follow(Request $request): JsonResponse
    {
        $user = User::where('id', $request->route('id'))->first();
        if(!$user) { return $this->error(404, 'Followed user not found', []); }
        if($user->id == auth()->id()) { return $this->error(409, 'You cant follow to yourself', []); }

        $follow_count = Follow::where(['user' => $user->id, 'follower' => auth()->id()])->get()->count();
        if($follow_count > 0) { return $this->error(409, 'You already followed on this user', []); }

        $follow = Follow::create([
            'user' => $user->id,
            'follower' => auth()->id()
        ]);

        $follow->save();

        return $this->success(200, 'Follow is successfully', [$follow]);
    }

    /**
     * Unfollow user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unfollow(Request $request): JsonResponse
    {
        $user = User::where('id', $request->route('id'))->first();
        if(!$user) { return $this->error(404, 'Followed user not found', []); }
        if($user->id == auth()->id()) { return $this->error(409, 'You cant unfollow to yourself', []); }

        $follow = Follow::where(['user' => $user->id, 'follower' => auth()->id()])->first();
        if(!$follow) { return $this->error(409, 'You not followed on this user', []); }

        $follow->delete();

        return $this->success(200, 'Unfollow is successfully', []);
    }

    /**
     * Followers list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function followers(Request $request): JsonResponse
    {
        $user = User::where('id', $request->route('id'))->first();
        if(!$user) { return $this->error(404, 'User not found', []); }

        $count = $request->get('count') ? $request->get('count') : '*';
        $follows = Follow::where(['user' => $user->id])->take($count)->get();
        $followers = [];
        if($follows->count() > 0) {
            $followers = User::whereIn('id', $follows->pluck('follower'))->get();
        }

        return $this->success(200, 'Get followers is received',
            [
                'count' => $follows->count(),
                'followers' => $followers
            ]
        );
    }

    /**
     * Follows list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function follows(Request $request): JsonResponse
    {
        $user = User::where('id', $request->route('id'))->first();
        if(!$user) { return $this->error(404, 'User not found', []); }

        $count = $request->get('count') ? $request->get('count') : '*';
        $follows = Follow::where(['follower' => $user->id])->take($count)->get();
        $followers = [];
        if($follows->count() > 0) {
            $followers = User::whereIn('id', $follows->pluck('user'))->get();
        }

        return $this->success(200, 'Get follows is received',
            [
                'count' => $follows->count(),
                'follows' => $followers
            ]
        );
    }
}
