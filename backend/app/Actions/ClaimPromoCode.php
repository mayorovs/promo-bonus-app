<?php

namespace App\Actions;

use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Models\PromoClaim;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClaimPromoCode
{
    /**
     * Credits a promo bonus to the player, or records why it was refused.
     *
     * Returns the resulting history record, which is either applied or
     * rejected. A refusal is a normal outcome rather than an exception,
     * because a refused attempt belongs to the player's history and must be
     * committed rather than rolled back.
     */
    public function handle(User $player, string $submittedCode): PromoClaim
    {
        $submittedCode = PromoCode::normaliseCode($submittedCode);

        return DB::transaction(function () use ($player, $submittedCode) {
            // Locking the player row serialises concurrent claims by the same
            // player, so two requests cannot both pass the checks below and
            // credit the bonus twice. Claims by different players do not
            // contend for this lock.
            $lockedPlayer = User::query()
                ->whereKey($player->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $promoCode = PromoCode::findByCode($submittedCode);

            if ($promoCode === null) {
                return $this->reject(
                    $lockedPlayer,
                    $submittedCode,
                    null,
                    PromoClaimRejectionReason::PromoCodeNotFound
                );
            }

            if ($promoCode->expires_at->isPast()) {
                return $this->reject(
                    $lockedPlayer,
                    $submittedCode,
                    $promoCode,
                    PromoClaimRejectionReason::PromoCodeExpired
                );
            }

            if ($this->hasSettledClaim($lockedPlayer, $promoCode)) {
                return $this->reject(
                    $lockedPlayer,
                    $submittedCode,
                    $promoCode,
                    PromoClaimRejectionReason::AlreadyClaimed
                );
            }

            // The partial unique index over applied and revoked rows is the
            // hard guarantee behind this insert; the check above only exists
            // to turn the common case into a clean business rejection.
            $claim = PromoClaim::create([
                'user_id' => $lockedPlayer->getKey(),
                'promo_code_id' => $promoCode->getKey(),
                'submitted_code' => $submittedCode,
                'bonus_amount' => $promoCode->bonus_amount,
                'status' => PromoClaimStatus::Applied,
                'rejection_reason' => null,
            ]);

            $lockedPlayer->increment('balance', $promoCode->bonus_amount);

            return $claim;
        });
    }

    /**
     * A revoked claim counts as settled, so a reversed bonus never becomes
     * claimable again.
     */
    private function hasSettledClaim(User $player, PromoCode $promoCode): bool
    {
        return PromoClaim::query()
            ->where('user_id', $player->getKey())
            ->where('promo_code_id', $promoCode->getKey())
            ->whereIn('status', [PromoClaimStatus::Applied, PromoClaimStatus::Revoked])
            ->exists();
    }

    private function reject(
        User $player,
        string $submittedCode,
        ?PromoCode $promoCode,
        PromoClaimRejectionReason $reason,
    ): PromoClaim {
        return PromoClaim::create([
            'user_id' => $player->getKey(),
            'promo_code_id' => $promoCode?->getKey(),
            'submitted_code' => $submittedCode,
            'bonus_amount' => null,
            'status' => PromoClaimStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }
}
