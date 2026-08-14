<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use App\Models\PromoClaim;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PromoClaimHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $query
     */
    private function historyAs(User $player, array $query = []): TestResponse
    {
        // The auth guard memoises the resolved user for the lifetime of the
        // application instance; production serves each request with a fresh one.
        $this->app['auth']->forgetGuards();

        $url = '/api/promo/history'.($query === [] ? '' : '?'.http_build_query($query));

        return $this->withHeader('Authorization', 'Bearer '.$player->createToken('api')->plainTextToken)
            ->getJson($url);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/promo/history')
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);
    }

    public function test_it_returns_the_players_claims_from_newest_to_oldest(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $oldest = PromoClaim::factory()->rejected()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'created_at' => now()->subDays(3),
        ]);
        $middle = PromoClaim::factory()->rejected()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'created_at' => now()->subDays(2),
        ]);
        $newest = PromoClaim::factory()->rejected()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->historyAs($player)->assertOk();

        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            $response->json('data.*.id')
        );
    }

    public function test_it_returns_exactly_the_documented_fields(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10', 'bonus_amount' => 5000]);

        $claim = PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $response = $this->historyAs($player)->assertOk();

        $this->assertSame(
            ['id', 'code', 'bonus_amount', 'status', 'rejection_reason', 'created_at'],
            array_keys($response->json('data.0'))
        );

        $response->assertJsonPath('data.0.id', $claim->id)
            ->assertJsonPath('data.0.code', 'BONUS10')
            ->assertJsonPath('data.0.bonus_amount', 5000)
            ->assertJsonPath('data.0.status', PromoClaimStatus::Applied->value)
            ->assertJsonPath('data.0.rejection_reason', null)
            ->assertJsonPath('data.0.created_at', $claim->created_at->toIso8601String());
    }

    public function test_a_rejected_attempt_reports_its_reason_and_no_bonus_amount(): void
    {
        $player = User::factory()->create();

        PromoClaim::factory()->forUnknownCode('GHOST1')->create(['user_id' => $player->id]);

        $this->historyAs($player)
            ->assertOk()
            ->assertJsonPath('data.0.code', 'GHOST1')
            ->assertJsonPath('data.0.bonus_amount', null)
            ->assertJsonPath('data.0.status', PromoClaimStatus::Rejected->value)
            ->assertJsonPath('data.0.rejection_reason', PromoClaimRejectionReason::PromoCodeNotFound->value);
    }

    public function test_it_uses_the_standard_pagination_format(): void
    {
        $player = User::factory()->create();

        PromoClaim::factory()->rejected()->count(3)->create(['user_id' => $player->id]);

        $this->historyAs($player)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'code', 'bonus_amount', 'status', 'rejection_reason', 'created_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
            ]);
    }

    public function test_it_returns_ten_records_per_page(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()->rejected()->count(25)->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->historyAs($player)
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_the_last_page_returns_the_remaining_records(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()->rejected()->count(25)->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $this->historyAs($player, ['page' => 3])
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 3);
    }

    public function test_pages_do_not_overlap(): void
    {
        $player = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        PromoClaim::factory()->rejected()->count(25)->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $first = $this->historyAs($player, ['page' => 1])->json('data.*.id');
        $second = $this->historyAs($player, ['page' => 2])->json('data.*.id');
        $third = $this->historyAs($player, ['page' => 3])->json('data.*.id');

        $all = array_merge($first, $second, $third);

        $this->assertCount(25, $all);
        $this->assertSame($all, array_unique($all));
    }

    public function test_an_empty_history_returns_an_empty_page(): void
    {
        $player = User::factory()->create();

        $this->historyAs($player)
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * One player holding one claim of each status.
     *
     * @return array{0: User, 1: PromoClaim, 2: PromoClaim, 3: PromoClaim}
     */
    private function playerWithEveryStatus(): array
    {
        $player = User::factory()->create();

        $applied = PromoClaim::factory()->create([
            'user_id' => $player->id,
            'promo_code_id' => PromoCode::factory()->create()->id,
        ]);

        $revoked = PromoClaim::factory()->revoked()->create([
            'user_id' => $player->id,
            'promo_code_id' => PromoCode::factory()->create()->id,
        ]);

        $rejected = PromoClaim::factory()->rejected()->create([
            'user_id' => $player->id,
            'promo_code_id' => PromoCode::factory()->create()->id,
        ]);

        return [$player, $applied, $rejected, $revoked];
    }

    public function test_it_filters_by_applied(): void
    {
        [$player, $applied] = $this->playerWithEveryStatus();

        $this->historyAs($player, ['status' => 'applied'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $applied->id)
            ->assertJsonPath('data.0.status', 'applied');
    }

    public function test_it_filters_by_rejected(): void
    {
        [$player, , $rejected] = $this->playerWithEveryStatus();

        $this->historyAs($player, ['status' => 'rejected'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $rejected->id)
            ->assertJsonPath('data.0.status', 'rejected');
    }

    public function test_it_filters_by_revoked(): void
    {
        [$player, , , $revoked] = $this->playerWithEveryStatus();

        $this->historyAs($player, ['status' => 'revoked'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $revoked->id)
            ->assertJsonPath('data.0.status', 'revoked');
    }

    public function test_without_a_filter_every_status_is_returned(): void
    {
        [$player] = $this->playerWithEveryStatus();

        $this->historyAs($player)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_an_empty_status_is_treated_as_no_filter(): void
    {
        [$player] = $this->playerWithEveryStatus();

        $this->historyAs($player, ['status' => ''])
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $player = User::factory()->create();

        $this->historyAs($player, ['status' => 'pending'])
            ->assertUnprocessable()
            ->assertJsonPath('code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonValidationErrors('status');
    }

    public function test_a_player_only_sees_their_own_history(): void
    {
        $player = User::factory()->create();
        $other = User::factory()->create();
        $promoCode = PromoCode::factory()->create();

        $mine = PromoClaim::factory()->rejected()->count(2)->create([
            'user_id' => $player->id,
            'promo_code_id' => $promoCode->id,
        ]);

        PromoClaim::factory()->rejected()->count(5)->create([
            'user_id' => $other->id,
            'promo_code_id' => $promoCode->id,
        ]);

        $response = $this->historyAs($player)->assertOk();

        $this->assertSame(2, $response->json('meta.total'));
        $this->assertEqualsCanonicalizing(
            $mine->pluck('id')->all(),
            $response->json('data.*.id')
        );
    }

    public function test_the_filter_cannot_expose_another_players_history(): void
    {
        $player = User::factory()->create();
        $other = User::factory()->create();

        PromoClaim::factory()->create([
            'user_id' => $other->id,
            'promo_code_id' => PromoCode::factory()->create()->id,
        ]);

        $this->historyAs($player, ['status' => 'applied'])
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_a_user_id_in_the_query_string_is_ignored(): void
    {
        $player = User::factory()->create();
        $other = User::factory()->create();

        PromoClaim::factory()->rejected()->count(3)->create(['user_id' => $other->id]);

        $this->historyAs($player, ['user_id' => $other->id])
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
