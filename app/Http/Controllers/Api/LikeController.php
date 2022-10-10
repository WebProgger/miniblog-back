<?php

namespace App\Http\Controllers\Api;

use App\Events\LikeEvent;
use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    use ApiResponser;

    /**
     * Like post.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function like(Request $request): JsonResponse
    {
        $post = Post::where('id', $request->route('id'))->first();
        if(!$post) { return $this->error(404, 'Post not found', []); }

        $like_count = Like::where(['user' => auth()->id(), 'post' => $post->id])->get()->count();
        if($like_count > 0) { return $this->error(409, 'You already liked this post', []); }

        $like = Like::create([
            'post' => $post->id,
            'user' => auth()->id()
        ]);

        $like->save();

        $post = Post::where('id', $like->post)->with('author')->withCount('likes')->with('likes')->first();

        broadcast(new LikeEvent($post))->toOthers();

        return $this->success(200, 'Post liked is successfully', []);
    }

    /**
     * Unlike post.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unlike(Request $request): JsonResponse
    {
        $post = Post::where('id', $request->route('id'))->first();
        if(!$post) { return $this->error(404, 'Post not found', []); }

        $like = Like::where(['user' => auth()->id(), 'post' => $post->id])->first();
        if(!$like) { return $this->error(409, 'You not liked this post', []); }

        $like->delete();

        $post = Post::where('id', $like->post)->with('author')->with('likes')->withCount('likes')->first();

        broadcast(new LikeEvent($post))->toOthers();

        return $this->success(200, 'Post unliked is successfully', []);
    }
}
