<?php

namespace Database\Factories;

use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Models\PromoClaim;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoClaim>
 */
class PromoClaimFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'promo_code_id' => PromoCode::factory(),
            // These two are resolved after promo_code_id above, so a claim
            // always agrees with the promo code it points at.
            'submitted_code' => fn (array $attributes) => PromoCode::findOrFail($attributes['promo_code_id'])->code,
            'bonus_amount' => fn (array $attributes) => PromoCode::findOrFail($attributes['promo_code_id'])->bonus_amount,
            'status' => PromoClaimStatus::Applied,
            'rejection_reason' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PromoClaimStatus::Revoked,
        ]);
    }

    /**
     * A refused attempt against a promo code that does exist.
     */
    public function rejected(
        PromoClaimRejectionReason $reason = PromoClaimRejectionReason::AlreadyClaimed,
    ): static {
        return $this->state(fn (array $attributes) => [
            'status' => PromoClaimStatus::Rejected,
            'bonus_amount' => null,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * A refused attempt against a code that matched nothing.
     */
    public function forUnknownCode(string $submittedCode = 'NOSUCH'): static
    {
        return $this->state(fn (array $attributes) => [
            'promo_code_id' => null,
            'submitted_code' => $submittedCode,
            'bonus_amount' => null,
            'status' => PromoClaimStatus::Rejected,
            'rejection_reason' => PromoClaimRejectionReason::PromoCodeNotFound,
        ]);
    }
}
