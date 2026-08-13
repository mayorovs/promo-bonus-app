<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Models\PromoClaim;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClaimPromoCodeTest extends TestCase
{
    use RefreshDatabase;

    private function claimAs(User $player, mixed $code): TestResponse
    {
        // The auth guard memoises the resolved user for the lifetime of the
        // application instance. Production serves every request with a fresh
        // instance, so it must be cleared between requests here for a second
        // request to authenticate as whoever its own token belongs to.
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$player->createToken('api')->plainTextToken)
            ->postJson('/api/promo/claim', ['code' => $code]);
    }

    public function test_a_player_can_claim_a_valid_promo_code(): void
    {
        $player = User::factory()->create(['balance' => 1000]);
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'BONUS10')
            ->assertCreated()
            ->assertExactJson([
                'bonus_amount' => 5000,
                'balance' => 6000,
            ]);

        $this->assertSame(6000, $player->fresh()->balance);
        $this->assertDatabaseHas('promo_claims', [
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'submitted_code' => 'BONUS10',
            'bonus_amount' => 5000,
            'status' => PromoClaimStatus::Applied->value,
            'rejection_reason' => null,
        ]);
    }

    public function test_a_code_can_be_claimed_in_any_case(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 2500]);

        $this->claimAs($player, 'bOnUs10')->assertCreated();

        $this->assertSame(2500, $player->fresh()->balance);
        $this->assertDatabaseHas('promo_claims', ['submitted_code' => 'BONUS10']);
    }

    public function test_the_player_comes_from_the_token_and_not_from_the_request_body(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        $victim = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->withHeader('Authorization', 'Bearer '.$player->createToken('api')->plainTextToken)
            ->postJson('/api/promo/claim', [
                'code' => 'BONUS10',
                'user_id' => $victim->id,
                'player_id' => $victim->id,
                'balance' => 999999,
                'bonus_amount' => 999999,
            ])
            ->assertCreated()
            ->assertJsonPath('balance', 5000);

        $this->assertSame(5000, $player->fresh()->balance);
        $this->assertSame(0, $victim->fresh()->balance);
        $this->assertDatabaseHas('promo_claims', ['user_id' => $player->id]);
        $this->assertDatabaseMissing('promo_claims', ['user_id' => $victim->id]);
    }

    public function test_it_requires_authentication(): void
    {
        PromoCode::factory()->create(['code' => 'BONUS10']);

        $this->postJson('/api/promo/claim', ['code' => 'BONUS10'])
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);

        $this->assertDatabaseCount('promo_claims', 0);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidCodes(): array
    {
        return [
            'too short' => ['ABC12'],
            'too long' => ['ABCDEFGHIJKLM'],
            'contains a hyphen' => ['BONUS-10'],
            'contains a space' => ['BONUS 10'],
            'contains an underscore' => ['BONUS_10'],
            'non latin letters' => ['БОНУС10'],
            'empty string' => [''],
        ];
    }

    #[DataProvider('invalidCodes')]
    public function test_an_invalid_code_is_rejected_and_not_recorded(string $code): void
    {
        $player = User::factory()->create(['balance' => 1000]);

        $this->claimAs($player, $code)
            ->assertUnprocessable()
            ->assertJsonPath('code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonValidationErrors('code');

        // A malformed code is not a business attempt, so nothing is recorded.
        $this->assertDatabaseCount('promo_claims', 0);
        $this->assertSame(1000, $player->fresh()->balance);
    }

    public function test_a_missing_code_is_rejected_and_not_recorded(): void
    {
        $player = User::factory()->create(['balance' => 1000]);

        $this->withHeader('Authorization', 'Bearer '.$player->createToken('api')->plainTextToken)
            ->postJson('/api/promo/claim', [])
            ->assertUnprocessable()
            ->assertJsonPath('code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('promo_claims', 0);
        $this->assertSame(1000, $player->fresh()->balance);
    }

    public function test_an_unknown_code_is_refused_and_recorded_as_a_rejected_attempt(): void
    {
        $player = User::factory()->create(['balance' => 1000]);

        $this->claimAs($player, 'NOSUCH')
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoCodeNotFound->value)
            ->assertJsonPath('message', 'This promo code does not exist.');

        $this->assertSame(1000, $player->fresh()->balance);
        $this->assertDatabaseHas('promo_claims', [
            'user_id' => $player->id,
            'promo_code_id' => null,
            'submitted_code' => 'NOSUCH',
            'bonus_amount' => null,
            'status' => PromoClaimStatus::Rejected->value,
            'rejection_reason' => PromoClaimRejectionReason::PromoCodeNotFound->value,
        ]);
    }

    public function test_an_expired_code_is_refused_and_recorded_as_a_rejected_attempt(): void
    {
        $player = User::factory()->create(['balance' => 1000]);
        $promoCode = PromoCode::factory()->expired()->create(['code' => 'OLD123', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'OLD123')
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoCodeExpired->value)
            ->assertJsonPath('message', 'This promo code has expired.');

        $this->assertSame(1000, $player->fresh()->balance);
        $this->assertDatabaseHas('promo_claims', [
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'bonus_amount' => null,
            'status' => PromoClaimStatus::Rejected->value,
            'rejection_reason' => PromoClaimRejectionReason::PromoCodeExpired->value,
        ]);
    }

    public function test_a_second_claim_of_the_same_code_is_refused_and_credits_nothing(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'BONUS10')->assertCreated();

        $this->claimAs($player, 'BONUS10')
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoCodeAlreadyClaimed->value)
            ->assertJsonPath('message', 'You have already claimed this promo code.');

        // Credited exactly once.
        $this->assertSame(5000, $player->fresh()->balance);
        $this->assertSame(1, PromoClaim::where('status', PromoClaimStatus::Applied)->count());
        $this->assertDatabaseHas('promo_claims', [
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'status' => PromoClaimStatus::Rejected->value,
            'rejection_reason' => PromoClaimRejectionReason::AlreadyClaimed->value,
        ]);
    }

    public function test_many_repeated_claims_credit_the_bonus_only_once(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'BONUS10')->assertCreated();

        foreach (range(1, 4) as $ignored) {
            $this->claimAs($player, 'BONUS10')->assertStatus(409);
        }

        $this->assertSame(5000, $player->fresh()->balance);
        $this->assertSame(1, PromoClaim::where('status', PromoClaimStatus::Applied)->count());
        $this->assertSame(4, PromoClaim::where('status', PromoClaimStatus::Rejected)->count());
    }

    public function test_a_revoked_claim_cannot_be_claimed_again(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        PromoClaim::factory()->revoked()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->claimAs($player, 'BONUS10')
            ->assertStatus(409)
            ->assertJsonPath('code', ApiErrorCode::PromoCodeAlreadyClaimed->value);

        $this->assertSame(0, $player->fresh()->balance);
        $this->assertSame(0, PromoClaim::where('status', PromoClaimStatus::Applied)->count());
    }

    public function test_two_players_may_each_claim_the_same_code(): void
    {
        $first = User::factory()->create(['balance' => 0]);
        $second = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->claimAs($first, 'BONUS10')->assertCreated();
        $this->claimAs($second, 'BONUS10')->assertCreated();

        $this->assertSame(5000, $first->fresh()->balance);
        $this->assertSame(5000, $second->fresh()->balance);
    }

    public function test_one_player_may_claim_several_different_codes(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'FIRST1', 'bonus_amount' => 1000]);
        PromoCode::factory()->create(['code' => 'SECND2', 'bonus_amount' => 2500]);

        $this->claimAs($player, 'FIRST1')->assertCreated();
        $this->claimAs($player, 'SECND2')->assertCreated()->assertJsonPath('balance', 3500);

        $this->assertSame(3500, $player->fresh()->balance);
    }

    public function test_the_player_row_is_locked_while_the_bonus_is_credited(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        DB::enableQueryLog();

        $this->claimAs($player, 'BONUS10')->assertCreated();

        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' | ');

        DB::disableQueryLog();

        // Serialises concurrent claims by the same player.
        $this->assertStringContainsStringIgnoringCase('for update', $queries);
    }

    public function test_the_database_refuses_a_duplicate_credit_even_without_the_application_check(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'BONUS10')->assertCreated();

        $this->expectException(QueryException::class);

        // What a second concurrent request would attempt if it had passed the
        // application check: the database itself must still refuse it.
        DB::table('promo_claims')->insert([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'submitted_code' => 'BONUS10',
            'bonus_amount' => 5000,
            'status' => PromoClaimStatus::Applied->value,
            'rejection_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_a_refused_attempt_leaves_the_balance_untouched_and_creates_no_applied_claim(): void
    {
        $player = User::factory()->create(['balance' => 7500]);
        PromoCode::factory()->expired()->create(['code' => 'OLD123', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'OLD123')->assertStatus(409);
        $this->claimAs($player, 'NOSUCH')->assertStatus(409);

        $this->assertSame(7500, $player->fresh()->balance);
        $this->assertSame(0, PromoClaim::where('status', PromoClaimStatus::Applied)->count());
        $this->assertSame(2, PromoClaim::where('status', PromoClaimStatus::Rejected)->count());
    }

    public function test_a_claim_never_touches_another_players_balance(): void
    {
        $player = User::factory()->create(['balance' => 100]);
        $other = User::factory()->create(['balance' => 200]);
        PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $this->claimAs($player, 'BONUS10')->assertCreated();

        $this->assertSame(5100, $player->fresh()->balance);
        $this->assertSame(200, $other->fresh()->balance);
    }
}
