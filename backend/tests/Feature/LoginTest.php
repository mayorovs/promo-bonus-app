<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_player_can_log_in_and_receives_a_token_and_their_balance(): void
    {
        $player = User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
            'balance' => 125000,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'player' => ['id', 'name', 'email', 'balance']])
            ->assertJsonPath('player.id', $player->id)
            ->assertJsonPath('player.email', 'player@example.test')
            ->assertJsonPath('player.balance', 125000);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_the_returned_token_authenticates_a_subsequent_request(): void
    {
        $player = User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('player.id', $player->id);
    }

    public function test_the_balance_is_returned_as_an_integer_in_minor_units(): void
    {
        User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
            'balance' => 1,
        ]);

        $balance = $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ])->json('player.balance');

        $this->assertSame(1, $balance);
    }

    public function test_the_response_never_exposes_the_password(): void
    {
        User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $response->assertOk();
        $this->assertArrayNotHasKey('password', $response->json('player'));
        $response->assertDontSee('correct-horse');
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('code', ApiErrorCode::InvalidCredentials->value)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_an_unknown_email_is_rejected_identically_to_a_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $unknownEmail = $this->postJson('/api/login', [
            'email' => 'nobody@example.test',
            'password' => 'correct-horse',
        ])->assertUnauthorized();

        $wrongPassword = $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        // Identical responses, so the endpoint cannot be used to discover which
        // email addresses are registered.
        $this->assertSame($unknownEmail->json(), $wrongPassword->json());
    }

    public function test_no_token_is_issued_when_the_credentials_are_invalid(): void
    {
        User::factory()->create([
            'email' => 'player@example.test',
            'password' => 'correct-horse',
        ]);

        $this->postJson('/api/login', [
            'email' => 'player@example.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_the_email_is_required(): void
    {
        $this->postJson('/api/login', ['password' => 'correct-horse'])
            ->assertUnprocessable()
            ->assertJsonPath('code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonValidationErrors('email');
    }

    public function test_the_password_is_required(): void
    {
        $this->postJson('/api/login', ['email' => 'player@example.test'])
            ->assertUnprocessable()
            ->assertJsonPath('code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonValidationErrors('password');
    }

    public function test_a_malformed_email_is_rejected(): void
    {
        $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'correct-horse',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', ApiErrorCode::ValidationFailed->value)
            ->assertJsonValidationErrors('email');
    }
}
