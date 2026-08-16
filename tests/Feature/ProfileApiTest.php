<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_profile(): void
    {
        $this->putJson(
            '/api/account/profile',
            []
        )->assertUnauthorized();

        $this->putJson(
            '/api/account/password',
            []
        )->assertUnauthorized();

        $this->deleteJson(
            '/api/account',
            []
        )->assertUnauthorized();
    }

    public function test_customer_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            '/api/account/profile',
            [
                'name' => 'New Name',
                'email' => 'NEW@EXAMPLE.COM',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.user.name',
                'New Name'
            )
            ->assertJsonPath(
                'data.user.email',
                'new@example.com'
            )
            ->assertJsonPath(
                'data.user.email_verified_at',
                null
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
                'name' => 'New Name',
                'email' => 'new@example.com',
                'email_verified_at' => null,
            ]
        );
    }

    public function test_keeping_same_email_preserves_verification(): void
    {
        $verifiedAt = now()->subDay();

        $user = User::factory()->create([
            'email' =>
                'customer@example.com',

            'email_verified_at' =>
                $verifiedAt,
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            '/api/account/profile',
            [
                'name' => 'Updated Name',

                'email' =>
                    'CUSTOMER@EXAMPLE.COM',
            ]
        )->assertOk();

        $this->assertNotNull(
            $user->fresh()
                ->email_verified_at
        );
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'first@example.com',
        ]);

        User::factory()->create([
            'email' => 'used@example.com',
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            '/api/account/profile',
            [
                'name' => 'First User',
                'email' => 'used@example.com',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' =>
                Hash::make('Password123'),
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            '/api/account/password',
            [
                'current_password' =>
                    'WrongPassword123',

                'password' =>
                    'NewPassword123',

                'password_confirmation' =>
                    'NewPassword123',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'current_password',
            ]);

        $this->assertTrue(
            Hash::check(
                'Password123',
                $user->fresh()->password
            )
        );
    }

    public function test_customer_can_change_password_and_other_tokens_are_revoked(): void
    {
        $user = User::factory()->create([
            'password' =>
                Hash::make('Password123'),
        ]);

        $currentToken =
            $user->createToken(
                'current-device',
                ['mobile']
            );

        $user->createToken(
            'other-device',
            ['mobile']
        );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            2
        );

        $this->withToken(
            $currentToken->plainTextToken
        )
            ->putJson(
                '/api/account/password',
                [
                    'current_password' =>
                        'Password123',

                    'password' =>
                        'NewPassword123',

                    'password_confirmation' =>
                        'NewPassword123',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertTrue(
            Hash::check(
                'NewPassword123',
                $user->fresh()->password
            )
        );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'id' =>
                    $currentToken
                        ->accessToken
                        ->id,

                'name' =>
                    'current-device',
            ]
        );
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = User::factory()->create([
            'password' =>
                Hash::make('Password123'),
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            '/api/account/password',
            [
                'current_password' =>
                    'Password123',

                'password' =>
                    'Password123',

                'password_confirmation' =>
                    'Password123',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);
    }

    public function test_wrong_password_cannot_delete_account(): void
    {
        $user = User::factory()->create([
            'password' =>
                Hash::make('Password123'),
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            '/api/account',
            [
                'password' =>
                    'WrongPassword123',

                'confirm_deletion' =>
                    true,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
            ]
        );
    }

    public function test_last_admin_cannot_delete_account(): void
    {
        $admin = User::factory()->create([
            'password' =>
                Hash::make('Password123'),
        ]);

        $admin->forceFill([
            'is_admin' => true,
        ])->save();

        $token = $admin->createToken(
            'admin-device',
            ['mobile']
        );

        $this->withToken(
            $token->plainTextToken
        )
            ->deleteJson(
                '/api/account',
                [
                    'password' =>
                        'Password123',

                    'confirm_deletion' =>
                        true,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'account',
            ]);

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $admin->id,
                'is_admin' => true,
            ]
        );
    }

    public function test_customer_can_delete_account(): void
    {
        $user = User::factory()->create([
            'email' =>
                'delete@example.com',

            'password' =>
                Hash::make('Password123'),
        ]);

        $token = $user->createToken(
            'test-device',
            ['mobile']
        );

        DB::table(
            'password_reset_tokens'
        )->insert([
            'email' => $user->email,

            'token' =>
                Hash::make('123456'),

            'created_at' => now(),
        ]);

        $this->withToken(
            $token->plainTextToken
        )
            ->deleteJson(
                '/api/account',
                [
                    'password' =>
                        'Password123',

                    'confirm_deletion' =>
                        true,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseMissing(
            'users',
            [
                'id' => $user->id,
            ]
        );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );

        $this->assertDatabaseMissing(
            'password_reset_tokens',
            [
                'email' =>
                    'delete@example.com',
            ]
        );
    }
}