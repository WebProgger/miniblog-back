<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/*
|--------------------------------------------------------------------------
| Api Responser Trait
|--------------------------------------------------------------------------
|
| This trait will be used for any response we sent to clients.
|
*/

trait ApiResponser
{
    /**
     * Return a success JSON response.
     *
     * @param int $code
     * @param string|null $message
     * @param array|object|string|null $data
     * @return JsonResponse
     */
    protected function success(int $code = 200, string $message = null, array|object|string $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param int $code
     * @param string|null $message
     * @param array|object|string|null $data
     * @return JsonResponse
     */
    protected function error(int $code = 400, string $message = null, array|object|string $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => $data
        ], $code);
    }

}
