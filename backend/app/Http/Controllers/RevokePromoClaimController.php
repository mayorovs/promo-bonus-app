<?php

namespace App\Http\Controllers;

use App\Actions\RevokePromoClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevokePromoClaimController extends Controller
{
    public function update(Request $request, int $claimId, RevokePromoClaim $revokePromoClaim): JsonResponse
    {
        $player = $request->user();

        $claim = $revokePromoClaim->handle($player, $claimId);

        return response()->json([
            'status' => $claim->status->value,
            'balance' => $player->refresh()->balance,
        ]);
    }
}
