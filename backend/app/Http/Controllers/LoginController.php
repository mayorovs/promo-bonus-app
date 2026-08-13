<?php

namespace App\Http\Controllers;

use App\Enums\ApiErrorCode;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\PlayerResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $player = User::where('email', $request->validated('email'))->first();

        // The same response is returned for an unknown email and a wrong
        // password, so the endpoint cannot be used to discover which addresses
        // are registered.
        if ($player === null || ! Hash::check($request->validated('password'), $player->password)) {
            return response()->json([
                'code' => ApiErrorCode::InvalidCredentials->value,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        return response()->json([
            'token' => $player->createToken('api')->plainTextToken,
            'player' => new PlayerResource($player),
        ]);
    }
}
