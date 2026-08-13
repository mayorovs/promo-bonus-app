<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $player): string
    {
        return $player->createToken('api')->plainTextToken;
    }

    public function test_it_returns_the_authenticated_player_with_their_balance(): void
    {
        $player = User::factory()->create(['balance' => 4200]);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($player))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonStructure(['player' => ['id', 'name', 'email', 'balance']])
            ->assertJsonPath('player.id', $player->id)
            ->assertJsonPath('player.email', $player->email)
            ->assertJsonPath('player.balance', 4200);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);
    }

    public function test_it_rejects_an_unknown_token(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::Unauthenticated->value);
    }

    public function test_it_returns_the_token_owner_and_never_another_player(): void
    {
        $owner = User::factory()->create(['balance' => 100]);
        $other = User::factory()->create(['balance' => 999999]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($owner))
            ->getJson('/api/me')
            ->assertOk();

        $response->assertJsonPath('player.id', $owner->id)
            ->assertJsonPath('player.balance', 100);

        $this->assertNotSame($other->id, $response->json('player.id'));
        $this->assertNotSame($other->balance, $response->json('player.balance'));
    }

    public function test_it_never_exposes_the_password_or_remember_token(): void
    {
        $player = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($player))
            ->getJson('/api/me')
            ->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('player'));
        $this->assertArrayNotHasKey('remember_token', $response->json('player'));
    }

    public function test_it_reflects_the_current_balance(): void
    {
        $player = User::factory()->create(['balance' => 0]);
        $token = $this->tokenFor($player);

        // forceFill, because balance is intentionally not mass assignable.
        $player->forceFill(['balance' => 7500])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('player.balance', 7500);
    }
}
