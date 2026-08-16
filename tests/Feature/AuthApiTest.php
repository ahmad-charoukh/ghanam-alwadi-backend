<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * يستطيع العميل إنشاء حساب جديد.
     */
    public function test_customer_can_register(): void
    {
        $response = $this->postJson(
            '/api/auth/register',
            [
                'name' => 'Test Customer',
                'email' => 'Customer@Example.com',
                'password' => 'Password123',
                'password_confirmation' =>
                    'Password123',
                'device_name' => 'test-device',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.user.name',
                'Test Customer'
            )
            ->assertJsonPath(
                'data.user.email',
                'customer@example.com'
            )
            ->assertJsonPath(
                'data.token_type',
                'Bearer'
            )
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                    ],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas(
            'users',
            [
                'name' => 'Test Customer',
                'email' =>
                    'customer@example.com',
                'is_admin' => false,
            ]
        );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );
    }

    /**
     * لا يمكن التسجيل ببريد مستخدم سابقًا.
     */
    public function test_customer_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $response = $this->postJson(
            '/api/auth/register',
            [
                'name' => 'Another Customer',
                'email' => 'customer@example.com',
                'password' => 'Password123',
                'password_confirmation' =>
                    'Password123',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertDatabaseCount(
            'users',
            1
        );
    }

    /**
     * يستطيع العميل تسجيل الدخول.
     */
    public function test_customer_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make(
                'Password123'
            ),
        ]);

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => 'CUSTOMER@example.com',
                'password' => 'Password123',
                'device_name' => 'test-device',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.user.id',
                $user->id
            )
            ->assertJsonPath(
                'data.user.email',
                'customer@example.com'
            )
            ->assertJsonPath(
                'data.token_type',
                'Bearer'
            )
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );
    }

    /**
     * يتم رفض بيانات الدخول غير الصحيحة.
     */
    public function test_customer_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make(
                'Password123'
            ),
        ]);

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => 'customer@example.com',
                'password' => 'WrongPassword123',
            ]
        );

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'success',
                false
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }

    /**
     * مسار بيانات الحساب يتطلب تسجيل الدخول.
     */
    public function test_guest_cannot_access_authenticated_user_data(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    /**
     * يستطيع المستخدم المسجل جلب بيانات حسابه.
     */
    public function test_authenticated_customer_can_get_account_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        Sanctum::actingAs(
            $user,
            ['mobile']
        );

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonPath(
                'data.user.id',
                $user->id
            )
            ->assertJsonPath(
                'data.user.name',
                'Test Customer'
            )
            ->assertJsonPath(
                'data.user.email',
                'customer@example.com'
            );
    }

    /**
     * يستطيع المستخدم تسجيل الخروج وحذف رمزه الحالي.
     */
    public function test_authenticated_customer_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user
            ->createToken(
                'test-device',
                ['mobile']
            )
            ->plainTextToken;

        $this->assertDatabaseCount(
            'personal_access_tokens',
            1
        );

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }
}