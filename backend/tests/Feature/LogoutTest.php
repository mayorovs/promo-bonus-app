<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The auth guard caches the resolved user for the lifetime of the
     * application instance. Production serves every request with a fresh
     * instance, so the cache must be cleared between two requests in one test
     * for the assertion to reflect real behaviour.
     */
    private function forgetResolvedUser(): void
    {
        $this->app['auth']->forgetGuards();
    }

    public function test_it_deletes_the_token_that_made_the_request(): void
    {
        $player = User::factory()->create();
        $token = $player->createToken('api')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_the_token_no_longer_authenticates_after_logout(): void
    {
        $player = User::factory()->create();
        $token = $player->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_it_revokes_only_the_current_token_and_leaves_other_sessions_signed_in(): void
    {
        $player = User::factory()->create();
        $phone = $player->createToken('phone')->plainTextToken;
        $laptop = $player->createToken('laptop')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$phone}")
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $player->id,
            'name' => 'laptop',
        ]);

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', "Bearer {$laptop}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('player.id', $player->id);
    }

    public function test_it_never_touches_another_players_tokens(): void
    {
        $player = User::factory()->create();
        $otherPlayer = User::factory()->create();

        $token = $player->createToken('api')->plainTextToken;
        $otherPlayer->createToken('api');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $otherPlayer->id,
        ]);
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);
    }

    public function test_it_rejects_an_unknown_token(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->postJson('/api/logout')
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);
    }

    public function test_a_full_login_and_logout_cycle_works(): void
    {
        User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ])->assertOk()->json('token');

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk();

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
