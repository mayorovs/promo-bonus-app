<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Models\PromoClaim;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RevokePromoClaimTest extends TestCase
{
    use RefreshDatabase;

    private function actingToken(User $player): string
    {
        // The auth guard memoises the resolved user for the lifetime of the
        // application instance; production serves each request with a fresh one.
        $this->app['auth']->forgetGuards();

        return $player->createToken('api')->plainTextToken;
    }

    private function revokeAs(User $player, int|string $claimId): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->actingToken($player))
            ->patchJson("/api/promo/{$claimId}/revoke");
    }

    /**
     * A player holding one applied claim worth the given bonus.
     *
     * @return array{0: User, 1: PromoClaim}
     */
    private function playerWithAppliedClaim(int $bonusAmount = 5000, ?int $balance = null): array
    {
        $player = User::factory()->create(['balance' => $balance ?? $bonusAmount]);
        $promoCode = PromoCode::factory()->create(['bonus_amount' => $bonusAmount]);

        $claim = PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        return [$player, $claim];
    }

    public function test_a_player_can_revoke_their_own_applied_claim(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000, balance: 8000);

        $this->revokeAs($player, $claim->id)
            ->assertOk()
            ->assertExactJson([
                'status' => PromoClaimStatus::Revoked->value,
                'balance' => 3000,
            ]);

        $this->assertSame(3000, $player->fresh()->balance);
        $this->assertSame(PromoClaimStatus::Revoked, $claim->fresh()->status);
    }

    public function test_the_original_bonus_amount_is_subtracted(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 1234, balance: 10000);

        $this->revokeAs($player, $claim->id)->assertOk()->assertJsonPath('balance', 8766);

        $this->assertSame(8766, $player->fresh()->balance);
    }

    public function test_the_bonus_amount_is_kept_on_the_revoked_record(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000);

        $this->revokeAs($player, $claim->id)->assertOk();

        $revoked = $claim->fresh();

        $this->assertSame(5000, $revoked->bonus_amount);
        $this->assertNull($revoked->rejection_reason);
    }

    public function test_it_requires_authentication(): void
    {
        [, $claim] = $this->playerWithAppliedClaim();

        $this->patchJson("/api/promo/{$claim->id}/revoke")
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);

        $this->assertSame(PromoClaimStatus::Applied, $claim->fresh()->status);
    }

    public function test_an_unknown_claim_is_refused(): void
    {
        $player = User::factory()->create(['balance' => 5000]);

        $this->revokeAs($player, 999999)
            ->assertNotFound()
            ->assertJsonPath('code', ApiErrorCode::PromoClaimNotFound->value)
            ->assertJsonPath('message', 'This promo claim does not exist.');

        $this->assertSame(5000, $player->fresh()->balance);
    }

    public function test_a_non_numeric_claim_id_does_not_match_the_route(): void
    {
        $player = User::factory()->create();

        $this->revokeAs($player, 'abc')->assertNotFound();
    }

    public function test_a_player_cannot_revoke_another_players_claim(): void
    {
        [$owner, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000);
        $attacker = User::factory()->create(['balance' => 100]);

        $this->revokeAs($attacker, $claim->id)
            ->assertNotFound()
            // Identical to an unknown claim, so ownership cannot be probed.
            ->assertJsonPath('code', ApiErrorCode::PromoClaimNotFound->value)
            ->assertJsonPath('message', 'This promo claim does not exist.');

        $this->assertSame(PromoClaimStatus::Applied, $claim->fresh()->status);
        $this->assertSame(5000, $owner->fresh()->balance);
        $this->assertSame(100, $attacker->fresh()->balance);
    }

    public function test_an_already_revoked_claim_is_refused_and_debits_nothing(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000, balance: 8000);

        $this->revokeAs($player, $claim->id)->assertOk();

        $this->revokeAs($player, $claim->id)
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoClaimAlreadyRevoked->value)
            ->assertJsonPath('message', 'This promo bonus has already been revoked.');

        // Debited exactly once.
        $this->assertSame(3000, $player->fresh()->balance);
    }

    public function test_many_repeated_revocations_debit_the_bonus_only_once(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 1000, balance: 10000);

        $this->revokeAs($player, $claim->id)->assertOk();

        foreach (range(1, 4) as $ignored) {
            $this->revokeAs($player, $claim->id)->assertStatus(409);
        }

        $this->assertSame(9000, $player->fresh()->balance);
    }

    public function test_a_rejected_attempt_cannot_be_revoked(): void
    {
        $player = User::factory()->create(['balance' => 5000]);

        $claim = PromoClaim::factory()
            ->rejected(PromoClaimRejectionReason::PromoCodeExpired)
            ->create(['user_id' => $player->id]);

        $this->revokeAs($player, $claim->id)
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoClaimNotRevocable->value)
            ->assertJsonPath('message', 'Only an applied promo bonus can be revoked.');

        $this->assertSame(5000, $player->fresh()->balance);
        $this->assertSame(PromoClaimStatus::Rejected, $claim->fresh()->status);
    }

    public function test_a_revocation_is_refused_when_the_balance_is_too_low(): void
    {
        // The player has spent part of the bonus since it was credited.
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000, balance: 1000);

        $this->revokeAs($player, $claim->id)
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::InsufficientBalance->value)
            ->assertJsonPath('message', 'The balance is too low to revoke this bonus.');

        // Nothing moved and the claim stays revocable later.
        $this->assertSame(1000, $player->fresh()->balance);
        $this->assertSame(PromoClaimStatus::Applied, $claim->fresh()->status);
    }

    public function test_a_revocation_leaving_exactly_zero_is_allowed(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000, balance: 5000);

        $this->revokeAs($player, $claim->id)
            ->assertOk()
            ->assertJsonPath('balance', 0);

        $this->assertSame(0, $player->fresh()->balance);
    }

    public function test_revoking_one_claim_leaves_the_players_other_claims_alone(): void
    {
        $player = User::factory()->create(['balance' => 8000]);
        $first = PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => PromoCode::factory()->create(['bonus_amount' => 3000])->id,
        ]);
        $second = PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => PromoCode::factory()->create(['bonus_amount' => 5000])->id,
        ]);

        $this->revokeAs($player, $first->id)->assertOk()->assertJsonPath('balance', 5000);

        $this->assertSame(PromoClaimStatus::Revoked, $first->fresh()->status);
        $this->assertSame(PromoClaimStatus::Applied, $second->fresh()->status);
    }

    public function test_the_player_row_is_locked_while_the_bonus_is_debited(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000);

        DB::enableQueryLog();

        $this->revokeAs($player, $claim->id)->assertOk();

        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' | ');

        DB::disableQueryLog();

        $this->assertStringContainsStringIgnoringCase('for update', $queries);
    }

    public function test_a_revoked_bonus_cannot_be_claimed_again(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->withHeader('Authorization', 'Bearer '.$this->actingToken($player))
            ->postJson('/api/promo/claim', ['code' => 'BONUS10'])
            ->assertCreated()
            ->assertJsonPath('balance', 5000);

        $claimId = PromoClaim::where('user_id', $player->id)->sole()->id;

        $this->revokeAs($player, $claimId)->assertOk()->assertJsonPath('balance', 0);

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$this->actingToken($player))
            ->postJson('/api/promo/claim', ['code' => 'BONUS10'])
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoCodeAlreadyClaimed->value);

        $this->assertSame(0, $player->fresh()->balance);
    }

    public function test_a_revoked_claim_is_listed_in_history_with_its_new_status(): void
    {
        [$player, $claim] = $this->playerWithAppliedClaim(bonusAmount: 5000);

        $this->revokeAs($player, $claim->id)->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$this->actingToken($player))
            ->getJson('/api/promo/history?status=revoked')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $claim->id)
            ->assertJsonPath('data.0.status', PromoClaimStatus::Revoked->value)
            ->assertJsonPath('data.0.bonus_amount', 5000);
    }
}
