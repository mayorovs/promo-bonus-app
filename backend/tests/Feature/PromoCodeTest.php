<?php

namespace Tests\Feature;

use App\Models\PromoCode;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lowercase_code_is_stored_in_uppercase(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'bonus10']);

        $this->assertSame('BONUS10', $promoCode->code);
        $this->assertDatabaseHas('promo_codes', ['code' => 'BONUS10']);
    }

    public function test_a_mixed_case_code_is_stored_in_uppercase(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'BoNuS10']);

        $this->assertSame('BONUS10', $promoCode->fresh()->code);
    }

    public function test_a_code_is_found_regardless_of_the_case_used_to_look_it_up(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'BONUS10']);

        foreach (['BONUS10', 'bonus10', 'BoNuS10', ' bonus10 '] as $lookup) {
            $this->assertSame(
                $promoCode->id,
                PromoCode::findByCode($lookup)?->id,
                "Lookup failed for: {$lookup}"
            );
        }
    }

    public function test_an_unknown_code_is_not_found(): void
    {
        PromoCode::factory()->create(['code' => 'BONUS10']);

        $this->assertNull(PromoCode::findByCode('NOSUCH'));
    }

    public function test_the_code_must_be_unique(): void
    {
        PromoCode::factory()->create(['code' => 'BONUS10']);

        $this->expectException(QueryException::class);

        PromoCode::factory()->create(['code' => 'BONUS10']);
    }

    public function test_uniqueness_is_case_insensitive(): void
    {
        PromoCode::factory()->create(['code' => 'BONUS10']);

        $this->expectException(QueryException::class);

        // Differs only in case, so it is the same promo code.
        PromoCode::factory()->create(['code' => 'bonus10']);
    }

    public function test_the_database_rejects_a_code_that_is_not_uppercase(): void
    {
        $this->expectException(QueryException::class);

        // Eloquent is bypassed on purpose: the canonical form must be
        // guaranteed by the database, not only by the model mutator.
        DB::table('promo_codes')->insert([
            'code' => 'bonus10',
            'bonus_amount' => 1000,
            'expires_at' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_bonus_amount_cannot_be_zero(): void
    {
        $this->expectException(QueryException::class);

        PromoCode::factory()->create(['bonus_amount' => 0]);
    }

    public function test_the_bonus_amount_cannot_be_negative(): void
    {
        $this->expectException(QueryException::class);

        PromoCode::factory()->create(['bonus_amount' => -100]);
    }

    public function test_a_positive_bonus_amount_is_stored_as_an_integer(): void
    {
        $promoCode = PromoCode::factory()->create(['bonus_amount' => 12345]);

        $this->assertSame(12345, $promoCode->fresh()->bonus_amount);
    }

    public function test_the_smallest_positive_bonus_amount_is_allowed(): void
    {
        $promoCode = PromoCode::factory()->create(['bonus_amount' => 1]);

        $this->assertSame(1, $promoCode->fresh()->bonus_amount);
    }

    public function test_the_expiration_date_is_cast_to_a_date_time(): void
    {
        $promoCode = PromoCode::factory()
            ->create(['expires_at' => '2026-12-31 23:59:59'])
            ->fresh();

        $this->assertInstanceOf(Carbon::class, $promoCode->expires_at);
        $this->assertSame('2026-12-31 23:59:59', $promoCode->expires_at->toDateTimeString());
    }

    public function test_the_expiration_date_is_required(): void
    {
        $this->expectException(QueryException::class);

        PromoCode::factory()->create(['expires_at' => null]);
    }

    public function test_the_factory_builds_an_unexpired_code_by_default(): void
    {
        $promoCode = PromoCode::factory()->create();

        $this->assertTrue($promoCode->expires_at->isFuture());
        $this->assertGreaterThan(0, $promoCode->bonus_amount);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $promoCode->code);
    }

    public function test_the_factory_can_build_an_expired_code(): void
    {
        $promoCode = PromoCode::factory()->expired()->create();

        $this->assertTrue($promoCode->expires_at->isPast());
    }
}
