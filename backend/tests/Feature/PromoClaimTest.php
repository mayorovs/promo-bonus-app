<?php

namespace Tests\Feature;

use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Models\PromoClaim;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromoClaimTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid applied row, for inserts that deliberately bypass the model.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rawRow(User $player, ?PromoCode $promoCode, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode?->id,
            'submitted_code' => $promoCode?->code ?? 'NOSUCH',
            'bonus_amount' => 1000,
            'status' => 'applied',
            'rejection_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }

    public function test_an_applied_claim_records_the_player_the_code_and_the_amount(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $claim = PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->assertTrue($claim->player->is($player));
        $this->assertTrue($claim->promoCode->is($promoCode));
        $this->assertSame('BONUS10', $claim->submitted_code);
        $this->assertSame(5000, $claim->bonus_amount);
        $this->assertSame(PromoClaimStatus::Applied, $claim->status);
        $this->assertNull($claim->rejection_reason);
    }

    public function test_the_status_and_rejection_reason_are_cast_to_enums(): void
    {
        $claim = PromoClaim::factory()
            ->rejected(PromoClaimRejectionReason::PromoCodeExpired)
            ->create()
            ->fresh();

        $this->assertSame(PromoClaimStatus::Rejected, $claim->status);
        $this->assertSame(PromoClaimRejectionReason::PromoCodeExpired, $claim->rejection_reason);
    }

    public function test_the_bonus_amount_is_cast_to_an_integer(): void
    {
        $promoCode = PromoCode::factory()->create(['bonus_amount' => 12345]);

        $claim = PromoClaim::factory()->create(['promo_code_id' => $promoCode->id])->fresh();

        $this->assertSame(12345, $claim->bonus_amount);
    }

    public function test_the_submitted_code_is_stored_in_uppercase(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10']);

        $claim = PromoClaim::factory()->create([
            'promo_code_id' => $promoCode->id,
            'submitted_code' => 'bonus10',
        ]);

        $this->assertSame('BONUS10', $claim->fresh()->submitted_code);
    }

    public function test_a_rejected_attempt_for_an_unknown_code_keeps_the_submitted_code(): void
    {
        $player = User::factory()->create();

        $claim = PromoClaim::factory()
            ->forUnknownCode('GHOST1')
            ->create(['user_id' => $player->id])
            ->fresh();

        $this->assertNull($claim->promo_code_id);
        $this->assertNull($claim->promoCode);
        $this->assertSame('GHOST1', $claim->submitted_code);
        $this->assertNull($claim->bonus_amount);
        $this->assertSame(PromoClaimStatus::Rejected, $claim->status);
        $this->assertSame(PromoClaimRejectionReason::PromoCodeNotFound, $claim->rejection_reason);
    }

    // The rule: at most one applied or revoked claim per player per promo code.

    public function test_a_player_cannot_apply_the_same_promo_code_twice(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->expectException(QueryException::class);

        PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);
    }

    public function test_a_revoked_claim_still_blocks_a_new_claim_for_the_same_promo_code(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()->revoked()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->expectException(QueryException::class);

        // A reversed bonus must never become claimable again.
        PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);
    }

    public function test_an_applied_claim_cannot_be_duplicated_by_a_revoked_one(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->expectException(QueryException::class);

        PromoClaim::factory()->revoked()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);
    }

    public function test_rejected_attempts_for_the_same_promo_code_may_repeat(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()
            ->rejected(PromoClaimRejectionReason::AlreadyClaimed)
            ->count(3)
            ->create([
                'user_id' => $player->id,
                'promo_code_id' => $promoCode->id,
            ]);

        $this->assertDatabaseCount('promo_claims', 3);
    }

    public function test_repeated_rejected_attempts_for_an_unknown_code_are_allowed(): void
    {
        $player = User::factory()->create();

        PromoClaim::factory()
            ->forUnknownCode('GHOST1')
            ->count(2)
            ->create(['user_id' => $player->id]);

        $this->assertDatabaseCount('promo_claims', 2);
    }

    public function test_a_rejected_attempt_does_not_block_a_later_successful_claim(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()
            ->rejected(PromoClaimRejectionReason::PromoCodeExpired)
            ->create([
                'user_id' => $player->id,
                'promo_code_id' => $promoCode->id,
            ]);

        PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->assertDatabaseCount('promo_claims', 2);
    }

    public function test_two_players_may_each_claim_the_same_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create();

        foreach (User::factory()->count(2)->create() as $player) {
            PromoClaim::factory()->create([
                'user_id' => $player->id,
                'promo_code_id' => $promoCode->id,
            ]);
        }

        $this->assertDatabaseCount('promo_claims', 2);
    }

    public function test_one_player_may_claim_different_promo_codes(): void
    {
        $player = User::factory()->create();

        foreach (PromoCode::factory()->count(2)->create() as $promoCode) {
            PromoClaim::factory()->create([
                'user_id' => $player->id,
                'promo_code_id' => $promoCode->id,
            ]);
        }

        $this->assertDatabaseCount('promo_claims', 2);
    }

    // Row shape, enforced by the database rather than by the model.

    public function test_the_database_rejects_an_unknown_status(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert(
            $this->rawRow($player, $promoCode, ['status' => 'pending'])
        );
    }

    public function test_the_database_rejects_an_unknown_rejection_reason(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert($this->rawRow($player, $promoCode, [
            'status' => 'rejected',
            'bonus_amount' => null,
            'rejection_reason' => 'because_i_said_so',
        ]));
    }

    public function test_the_database_rejects_a_submitted_code_that_is_not_uppercase(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert(
            $this->rawRow($player, $promoCode, ['submitted_code' => 'bonus10'])
        );
    }

    public function test_an_applied_claim_must_refer_to_a_promo_code(): void
    {
        $player = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert(
            $this->rawRow($player, null, ['status' => 'applied'])
        );
    }

    public function test_an_applied_claim_must_have_a_positive_bonus_amount(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert(
            $this->rawRow($player, $promoCode, ['bonus_amount' => 0])
        );
    }

    public function test_an_applied_claim_must_not_carry_a_rejection_reason(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert(
            $this->rawRow($player, $promoCode, ['rejection_reason' => 'already_claimed'])
        );
    }

    public function test_a_rejected_attempt_must_state_a_reason(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert($this->rawRow($player, $promoCode, [
            'status' => 'rejected',
            'bonus_amount' => null,
            'rejection_reason' => null,
        ]));
    }

    public function test_a_rejected_attempt_must_not_carry_a_bonus_amount(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('promo_claims')->insert($this->rawRow($player, $promoCode, [
            'status' => 'rejected',
            'bonus_amount' => 1000,
            'rejection_reason' => 'already_claimed',
        ]));
    }
}
