<?php

namespace App\Http\Resources;

use App\Models\PromoClaim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PromoClaim
 */
class PromoClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // The code as submitted, which is also the only code available
            // when the attempt matched no promo code.
            'code' => $this->submitted_code,
            // Minor units. Null for an attempt that moved no money.
            'bonus_amount' => $this->bonus_amount,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason?->value,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
