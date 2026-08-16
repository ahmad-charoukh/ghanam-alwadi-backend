<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson(
            '/api/notifications'
        )->assertUnauthorized();

        $this->getJson(
            '/api/notifications/unread-count'
        )->assertUnauthorized();

        $this->postJson(
            '/api/notifications/read-all'
        )->assertUnauthorized();

        $this->deleteJson(
            '/api/notifications/clear'
        )->assertUnauthorized();
    }

    public function test_customer_sees_only_own_notifications(): void
    {
        $user = User::factory()->create();

        $otherUser =
            User::factory()->create();

        $this->createNotification(
            $user,
            'الإشعار الأول'
        );

        $this->createNotification(
            $user,
            'الإشعار الثاني',
            true
        );

        $this->createNotification(
            $otherUser,
            'إشعار مستخدم آخر'
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            )
            ->assertJsonCount(
                2,
                'data.notifications'
            )
            ->assertJsonPath(
                'data.unread_count',
                1
            )
            ->assertJsonPath(
                'data.pagination.total',
                2
            )
            ->assertJsonFragment([
                'title' => 'الإشعار الأول',
            ])
            ->assertJsonFragment([
                'title' => 'الإشعار الثاني',
            ])
            ->assertJsonMissing([
                'title' =>
                    'إشعار مستخدم آخر',
            ]);
    }

    public function test_customer_can_get_unread_count(): void
    {
        $user = User::factory()->create();

        $this->createNotification(
            $user,
            'غير مقروء 1'
        );

        $this->createNotification(
            $user,
            'غير مقروء 2'
        );

        $this->createNotification(
            $user,
            'مقروء',
            true
        );

        Sanctum::actingAs($user, ['mobile']);

        $this->getJson(
            '/api/notifications/unread-count'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.unread_count',
                2
            );
    }

    public function test_customer_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification =
            $this->createNotification(
                $user,
                'إشعار جديد'
            );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/notifications/'
            . $notification->id
            . '/read'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.is_read',
                true
            );

        $this->assertNotNull(
            $notification->fresh()->read_at
        );
    }

    public function test_customer_cannot_manage_another_customers_notification(): void
    {
        $owner = User::factory()->create();

        $otherUser =
            User::factory()->create();

        $notification =
            $this->createNotification(
                $owner,
                'إشعار خاص'
            );

        Sanctum::actingAs(
            $otherUser,
            ['mobile']
        );

        $this->postJson(
            '/api/notifications/'
            . $notification->id
            . '/read'
        )->assertNotFound();

        $this->deleteJson(
            '/api/notifications/'
            . $notification->id
        )->assertNotFound();

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' => $notification->id,
                'notifiable_id' => $owner->id,
            ]
        );
    }

    public function test_customer_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $otherUser =
            User::factory()->create();

        $this->createNotification(
            $user,
            'الإشعار الأول'
        );

        $this->createNotification(
            $user,
            'الإشعار الثاني'
        );

        $otherNotification =
            $this->createNotification(
                $otherUser,
                'إشعار مستخدم آخر'
            );

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/notifications/read-all'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.updated_count',
                2
            )
            ->assertJsonPath(
                'data.unread_count',
                0
            );

        $this->assertSame(
            0,
            $user->unreadNotifications()
                ->count()
        );

        $this->assertNull(
            $otherNotification
                ->fresh()
                ->read_at
        );
    }

    public function test_customer_can_delete_own_notification(): void
    {
        $user = User::factory()->create();

        $notification =
            $this->createNotification(
                $user,
                'إشعار للحذف'
            );

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            '/api/notifications/'
            . $notification->id
        )
            ->assertOk()
            ->assertJsonPath(
                'success',
                true
            );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'id' => $notification->id,
            ]
        );
    }

    public function test_clear_removes_only_current_customers_notifications(): void
    {
        $user = User::factory()->create();

        $otherUser =
            User::factory()->create();

        $this->createNotification(
            $user,
            'الإشعار الأول'
        );

        $this->createNotification(
            $user,
            'الإشعار الثاني'
        );

        $otherNotification =
            $this->createNotification(
                $otherUser,
                'إشعار مستخدم آخر'
            );

        Sanctum::actingAs($user, ['mobile']);

        $this->deleteJson(
            '/api/notifications/clear'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.deleted_count',
                2
            );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'notifiable_type' =>
                    User::class,

                'notifiable_id' =>
                    $user->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' =>
                    $otherNotification->id,

                'notifiable_id' =>
                    $otherUser->id,
            ]
        );
    }

    private function createNotification(
        User $user,
        string $title,
        bool $isRead = false
    ): DatabaseNotification {
        /** @var DatabaseNotification $notification */
        $notification =
            $user->notifications()->create([
                'id' =>
                    (string) Str::uuid(),

                'type' =>
                    'Tests\\TestNotification',

                'data' => [
                    'type' =>
                        'test_notification',

                    'title' =>
                        $title,

                    'message' =>
                        'Test notification message',

                    'icon' =>
                        'bell',

                    'action' =>
                        null,
                ],

                'read_at' =>
                    $isRead
                        ? now()
                        : null,
            ]);

        return $notification;
    }
}