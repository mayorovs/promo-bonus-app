<?php

namespace App\Http\Controllers;

use App\Actions\ClaimPromoCode;
use App\Enums\ApiErrorCode;
use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Http\Requests\ClaimPromoCodeRequest;
use App\Models\PromoClaim;
use Illuminate\Http\JsonResponse;

class ClaimPromoCodeController extends Controller
{
    public function store(ClaimPromoCodeRequest $request, ClaimPromoCode $claimPromoCode): JsonResponse
    {
        $player = $request->user();

        $claim = $claimPromoCode->handle($player, $request->validated('code'));

        if ($claim->status === PromoClaimStatus::Rejected) {
            return $this->rejection($claim);
        }

        return response()->json([
            'bonus_amount' => $claim->bonus_amount,
            'balance' => $player->refresh()->balance,
        ], 201);
    }

    /**
     * A refused attempt is already recorded in history; this only translates
     * the reason into the HTTP error contract.
     */
    private function rejection(PromoClaim $claim): JsonResponse
    {
        [$code, $message] = match ($claim->rejection_reason) {
            PromoClaimRejectionReason::PromoCodeNotFound => [
                ApiErrorCode::PromoCodeNotFound,
                'This promo code does not exist.',
            ],
            PromoClaimRejectionReason::PromoCodeExpired => [
                ApiErrorCode::PromoCodeExpired,
                'This promo code has expired.',
            ],
            PromoClaimRejectionReason::AlreadyClaimed => [
                ApiErrorCode::PromoCodeAlreadyClaimed,
                'You have already claimed this promo code.',
            ],
        };

        return response()->json([
            'code' => $code->value,
            'message' => $message,
        ], 409);
    }
}
