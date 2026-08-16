<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_addresses(): void
    {
        $this->getJson('/api/addresses')
            ->assertUnauthorized();

        $this->postJson(
            '/api/addresses',
            []
        )->assertUnauthorized();
    }

    public function test_first_address_becomes_default_automatically(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['mobile']);

        $response = $this->postJson(
            '/api/addresses',
            $this->addressPayload([
                'is_default' => false,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.recipient_name',
                'Test Customer'
            )
            ->assertJsonPath(
                'data.is_default',
                true
            );

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'city' => 'Riyadh',
            'is_default' => true,
        ]);
    }

    public function test_new_default_address_replaces_old_default(): void
    {
        $user = User::factory()->create();

        $oldAddress =
            $this->createAddress(
                $user,
                [
                    'is_default' => true,
                ]
            );

        Sanctum::actingAs($user, ['mobile']);

        $response = $this->postJson(
            '/api/addresses',
            $this->addressPayload([
                'label' => 'العمل',
                'city' => 'Jeddah',
                'is_default' => true,
            ])
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.is_default',
                true
            );

        $this->assertDatabaseHas('addresses', [
            'id' => $oldAddress->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'city' => 'Jeddah',
            'is_default' => true,
        ]);
    }

    public function test_customer_sees_only_own_addresses(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $secondAddress =
            $this->createAddress(
                $user,
                [
                    'label' => 'العمل',
                    'is_default' => false,
                ]
            );

        $defaultAddress =
            $this->createAddress(
                $user,
                [
                    'label' => 'المنزل',
                    'is_default' => true,
                ]
            );

        $this->createAddress($otherUser);

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson('/api/addresses')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.id',
                $defaultAddress->id
            )
            ->assertJsonPath(
                'data.1.id',
                $secondAddress->id
            );
    }

    public function test_customer_can_update_own_address(): void
    {
        $user = User::factory()->create();
        $address =
            $this->createAddress($user);

        Sanctum::actingAs($user, ['mobile']);

        $this->putJson(
            "/api/addresses/{$address->id}",
            [
                'city' => 'Jeddah',
                'district' => 'Al Rawdah',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.city',
                'Jeddah'
            )
            ->assertJsonPath(
                'data.district',
                'Al Rawdah'
            );

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'city' => 'Jeddah',
            'district' => 'Al Rawdah',
        ]);
    }

    public function test_customer_cannot_access_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $address =
            $this->createAddress($owner);

        Sanctum::actingAs(
            $otherUser,
            ['mobile']
        );

        $this->getJson(
            "/api/addresses/{$address->id}"
        )->assertNotFound();

        $this->putJson(
            "/api/addresses/{$address->id}",
            [
                'city' => 'Jeddah',
            ]
        )->assertNotFound();

        $this->postJson(
            "/api/addresses/{$address->id}/default"
        )->assertNotFound();

        $this->deleteJson(
            "/api/addresses/{$address->id}"
        )->assertNotFound();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_customer_can_set_default_address(): void
    {
        $user = User::factory()->create();

        $oldDefault =
            $this->createAddress(
                $user,
                [
                    'is_default' => true,
                ]
            );

        $newDefault =
            $this->createAddress(
                $user,
                [
                    'is_default' => false,
                ]
            );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            "/api/addresses/{$newDefault->id}/default"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.is_default',
                true
            );

        $this->assertDatabaseHas('addresses', [
            'id' => $oldDefault->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $newDefault->id,
            'is_default' => true,
        ]);
    }

    public function test_deleting_default_address_promotes_another_address(): void
    {
        $user = User::factory()->create();

        $defaultAddress =
            $this->createAddress(
                $user,
                [
                    'is_default' => true,
                ]
            );

        $remainingAddress =
            $this->createAddress(
                $user,
                [
                    'is_default' => false,
                ]
            );

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            "/api/addresses/{$defaultAddress->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing(
            'addresses',
            [
                'id' => $defaultAddress->id,
            ]
        );

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $remainingAddress->id,
                'is_default' => true,
            ]
        );
    }

    public function test_address_validation_rejects_invalid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/addresses',
            [
                'recipient_name' => '',
                'phone' => '',
                'city' => '',
                'latitude' => 100,
                'longitude' => 200,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'recipient_name',
                'phone',
                'city',
                'latitude',
                'longitude',
            ]);
    }

    private function addressPayload(
        array $attributes = []
    ): array {
        return array_merge([
            'label' => 'المنزل',
            'recipient_name' =>
                'Test Customer',
            'phone' => '0500000000',
            'country' => 'السعودية',
            'city' => 'Riyadh',
            'district' => 'Test District',
            'street' => 'Test Street',
            'building_number' => '10',
            'apartment_number' => '2',
            'postal_code' => '12345',
            'additional_details' => null,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'is_default' => false,
        ], $attributes);
    }

    private function createAddress(
        User $user,
        array $attributes = []
    ): Address {
        return $user
            ->addresses()
            ->create(
                $this->addressPayload(
                    $attributes
                )
            );
    }
}