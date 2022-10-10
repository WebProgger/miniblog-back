<?php

namespace App\Http\Controllers\Api;

use App\Events\NewPostEvent;
use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    use ApiResponser;

    /**
     * Get posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $conditions = [];

        if(!empty($request->get('author'))) {
            $conditions[] = ['author', '=', $request->get('author')];
        }
        if(!empty($request->get('no_author'))) {
            $conditions[] = ['author', '<>', $request->get('no_author')];
        }
        $posts = Post::where($conditions);
        if(!empty($request->get('followed'))) {
            $authors = Follow::where([['follower', '=', $request->get('followed')]])->get('user')->toArray();
            $posts = $posts->whereIn('author', $authors);
        }
        if(!empty($request->get('liked'))) {
            $posts = $posts->whereHas('likes', function($query) use ($request) {
                return $query->where('likes.user', '=', $request->get('liked'));
            });
        }
        //$posts = Post::where([['author', '<>', auth()->id()]])->with('author')->withCount('likes')->with('likes')->orderBy('updated_at')->get();
        $posts = $posts
            ->with('author')
            ->withCount('likes')
            ->with('likes')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);
        if($posts->count() < 1) { return $this->error(404, 'Posts not found', []); }

        foreach($posts as $post) {
            $post->text = Str::limit($post->text, 250, ' ...');
        }

        return $this->success(200, 'OK', $posts);
    }

    /**
     * Get one post.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function get_one(Request $request): JsonResponse
    {
        $post = Post::where('id', $request->route('id'))->with('author')->withCount('likes')->with('likes')->first();
        if(!$post) { return $this->error(404, 'Post not found', []); }

        $post->views += 1;
        $post->save();

        return $this->success(200, 'OK', $post);
    }

    /**
     * Create post.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string']
        ]);

        if($validator->fails()) {
            return $this->error(422, 'Validation error', [$validator->errors()]);
        }

        $post = Post::create([
            'title' => $request->title,
            'text' => $request->text,
            'author' => auth()->id(),
            'views' => 0
        ]);

        $post->save();

        $post = Post::where('id', $post->id)->with('author')->withCount('likes')->with('likes')->first();

        broadcast(new NewPostEvent($post))->toOthers();

        return $this->success(201, 'Post was created successfully', ['id' => $post->id]);
    }

    /**
     * Edit post.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function edit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required_without:text', 'string', 'min:3', 'max:255'],
            'text' => ['required_without:title', 'string', 'min:3']
        ]);

        if($validator->fails()) {
            return $this->error(422, 'Validation error', [$validator->errors()]);
        }

        $post = Post::where('id', $request->route('id'))->first();
        if(!$post) { return $this->error(404, 'Post not found', []); }

        if(!empty($request->title)) { $post->title = $request->title; }
        if(!empty($request->text)) { $post->text = $request->text; }

        $post->save();

        return $this->success(200, 'Post was edited successfully', $post->getChanges());
    }

    /**
     * Delete post.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function delete(Request $request): JsonResponse
    {
        $post = Post::where('id', $request->route('id'))->first();
        if(!$post) { return $this->error(404, 'Post not found', []); }

        Like::where('post', $post->id)->delete();

        $post->delete();

        return $this->success(200, 'Post was deleted successfully', []);
    }
}
