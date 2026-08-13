<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * The player is always resolved from the authentication token, never from
     * anything supplied by the client.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'player' => new PlayerResource($request->user()),
        ]);
    }
}
