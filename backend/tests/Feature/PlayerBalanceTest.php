<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlayerBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_player_starts_with_a_zero_balance(): void
    {
        $player = User::factory()->create();

        $this->assertSame(0, $player->balance);
    }

    public function test_the_balance_column_defaults_to_zero_in_the_database(): void
    {
        // Inserted without a balance, so the column default is what is under test.
        DB::table('users')->insert([
            'name' => 'Default Balance Player',
            'email' => 'default-balance@example.test',
            'password' => Hash::make('password'),
        ]);

        $balance = DB::table('users')
            ->where('email', 'default-balance@example.test')
            ->value('balance');

        $this->assertSame(0, (int) $balance);
    }

    public function test_the_balance_is_stored_and_read_back_as_an_integer(): void
    {
        $player = User::factory()->create(['balance' => 12345]);

        $this->assertSame(12345, $player->fresh()->balance);
    }

    public function test_a_balance_of_zero_is_allowed(): void
    {
        $player = User::factory()->create(['balance' => 500]);

        $player->balance = 0;
        $player->save();

        $this->assertSame(0, $player->fresh()->balance);
    }

    public function test_the_database_rejects_a_negative_balance_on_insert(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['balance' => -1]);
    }

    public function test_the_database_rejects_a_negative_balance_on_update(): void
    {
        $player = User::factory()->create(['balance' => 100]);

        $this->expectException(QueryException::class);

        // Eloquent is bypassed on purpose: the guarantee must come from the
        // database, not from application-level checks.
        DB::table('users')->where('id', $player->id)->update(['balance' => -1]);
    }

    public function test_the_balance_is_left_untouched_by_the_database_when_a_debit_would_overdraw_it(): void
    {
        $player = User::factory()->create(['balance' => 100]);

        try {
            // Wrapped in a nested transaction so the rejection rolls back to a
            // savepoint; in PostgreSQL a failed statement otherwise poisons the
            // surrounding transaction and the assertion below could not run.
            DB::transaction(function () use ($player) {
                DB::table('users')->where('id', $player->id)->update(['balance' => 100 - 150]);
            });
            $this->fail('The database accepted a balance below zero.');
        } catch (QueryException) {
            // Expected.
        }

        $this->assertSame(100, $player->fresh()->balance);
    }

    public function test_the_balance_cannot_be_mass_assigned(): void
    {
        $player = User::factory()->create(['balance' => 250]);

        $player->update(['balance' => 999999]);

        $this->assertSame(250, $player->fresh()->balance);
    }
}
