<?php

namespace App\Actions;

use App\Enums\PromoClaimStatus;
use App\Exceptions\ApiException;
use App\Models\PromoClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokePromoClaim
{
    /**
     * Reverses a previously applied promo bonus.
     *
     * Unlike a refused claim, a refused revocation records nothing, so every
     * failure throws and the transaction rolls back untouched.
     *
     * @throws ApiException
     */
    public function handle(User $player, int $claimId): PromoClaim
    {
        return DB::transaction(function () use ($player, $claimId) {
            // Locked in the same order as when crediting, so a claim and a
            // revocation for one player can never deadlock against each other.
            $lockedPlayer = User::query()
                ->whereKey($player->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Scoping the lookup to the player means another player's claim is
            // indistinguishable from one that does not exist.
            $claim = PromoClaim::query()
                ->where('id', $claimId)
                ->where('user_id', $lockedPlayer->getKey())
                ->first();

            if ($claim === null) {
                throw ApiException::promoClaimNotFound();
            }

            if ($claim->status === PromoClaimStatus::Revoked) {
                throw ApiException::promoClaimAlreadyRevoked();
            }

            if ($claim->status !== PromoClaimStatus::Applied) {
                throw ApiException::promoClaimNotRevocable();
            }

            if ($lockedPlayer->balance < $claim->bonus_amount) {
                throw ApiException::insufficientBalance();
            }

            // Compare and swap on the status: only one request can ever move a
            // claim out of the applied state, so the bonus cannot be debited
            // twice even if two revocations were to run at the same time.
            $transitioned = PromoClaim::query()
                ->whereKey($claim->getKey())
                ->where('status', PromoClaimStatus::Applied)
                ->update(['status' => PromoClaimStatus::Revoked]);

            if ($transitioned !== 1) {
                throw ApiException::promoClaimAlreadyRevoked();
            }

            $lockedPlayer->decrement('balance', $claim->bonus_amount);

            return $claim->refresh();
        });
    }
}
