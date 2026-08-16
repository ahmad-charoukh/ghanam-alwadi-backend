<?php

namespace Tests\Feature;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_support_ticket(): void
    {
        $response = $this->postJson(
            '/api/support-tickets',
            $this->ticketPayload()
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.subject',
                'Test Support Ticket'
            )
            ->assertJsonPath(
                'data.status',
                SupportTicket::STATUS_NEW
            )
            ->assertJsonCount(
                1,
                'data.messages'
            )
            ->assertJsonPath(
                'data.messages.0.sender_type',
                SupportMessage::SENDER_CUSTOMER
            );

        $ticket = SupportTicket::query()
            ->firstOrFail();

        $this->assertNull($ticket->user_id);

        $this->assertDatabaseHas(
            'support_messages',
            [
                'support_ticket_id' =>
                    $ticket->id,

                'sender_type' =>
                    SupportMessage::SENDER_CUSTOMER,

                'is_read' => false,
            ]
        );
    }

    public function test_support_ticket_validation_rejects_invalid_data(): void
    {
        $this->postJson(
            '/api/support-tickets',
            [
                'name' => '',
                'subject' => '',
                'category' => 'invalid',
                'message' => 'short',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'phone',
                'subject',
                'category',
                'message',
            ]);
    }

    public function test_guest_can_track_ticket_with_matching_email(): void
    {
        $ticket = $this->createTicket([
            'email' =>
                'customer@example.com',

            'phone' => null,
        ]);

        $this->getJson(
            '/api/support-tickets/'
            . $ticket->ticket_number
            . '/track?email=customer%40example.com'
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.ticket_number',
                $ticket->ticket_number
            )
            ->assertJsonCount(
                1,
                'data.messages'
            );

        $this->getJson(
            '/api/support-tickets/'
            . $ticket->ticket_number
            . '/track?email=wrong%40example.com'
        )->assertNotFound();
    }

    public function test_guest_can_reply_with_matching_contact_data(): void
    {
        $ticket = $this->createTicket([
            'email' =>
                'customer@example.com',

            'phone' => null,
        ]);

        $this->postJson(
            '/api/support-tickets/'
            . $ticket->ticket_number
            . '/reply',
            [
                'email' =>
                    'customer@example.com',

                'message' =>
                    'A second customer message',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonCount(
                2,
                'data.messages'
            )
            ->assertJsonPath(
                'data.messages.1.message',
                'A second customer message'
            );

        $this->assertDatabaseCount(
            'support_messages',
            2
        );

        $this->postJson(
            '/api/support-tickets/'
            . $ticket->ticket_number
            . '/reply',
            [
                'email' =>
                    'wrong@example.com',

                'message' =>
                    'Unauthorized reply',
            ]
        )->assertNotFound();
    }

    public function test_authenticated_ticket_is_linked_to_customer_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Account Customer',
            'email' =>
                'account@example.com',
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/account/support-tickets',
            $this->ticketPayload([
                'name' =>
                    'Account Customer',

                'email' =>
                    'account@example.com',
            ])
        )->assertCreated();

        $ticket = SupportTicket::query()
            ->firstOrFail();

        $this->assertSame(
            $user->id,
            $ticket->user_id
        );

        $this->getJson(
            '/api/account/support-tickets'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1
            )
            ->assertJsonPath(
                'data.tickets.0.ticket_number',
                $ticket->ticket_number
            );

        $this->getJson(
            '/api/account/support-tickets/'
            . $ticket->ticket_number
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $ticket->id
            );
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $owner = User::factory()->create();
        $otherUser =
            User::factory()->create();

        $ticket = $this->createTicket([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs(
            $otherUser,
            ['mobile']
        );

        $this->getJson(
            '/api/account/support-tickets/'
            . $ticket->ticket_number
        )->assertNotFound();

        $this->postJson(
            '/api/account/support-tickets/'
            . $ticket->ticket_number
            . '/reply',
            [
                'message' =>
                    'Unauthorized reply',
            ]
        )->assertNotFound();

        $this->assertDatabaseCount(
            'support_messages',
            1
        );
    }

    public function test_replying_to_resolved_ticket_reopens_it(): void
    {
        $user = User::factory()->create();

        $ticket = $this->createTicket([
            'user_id' => $user->id,

            'status' =>
                SupportTicket::STATUS_RESOLVED,

            'closed_at' => now(),
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/account/support-tickets/'
            . $ticket->ticket_number
            . '/reply',
            [
                'message' =>
                    'The problem still exists',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                SupportTicket::STATUS_IN_PROGRESS
            )
            ->assertJsonCount(
                2,
                'data.messages'
            );

        $ticket->refresh();

        $this->assertSame(
            SupportTicket::STATUS_IN_PROGRESS,
            $ticket->status
        );

        $this->assertNull(
            $ticket->closed_at
        );
    }

    public function test_closed_ticket_cannot_receive_replies(): void
    {
        $user = User::factory()->create();

        $ticket = $this->createTicket([
            'user_id' => $user->id,

            'status' =>
                SupportTicket::STATUS_CLOSED,

            'closed_at' => now(),
        ]);

        Sanctum::actingAs($user, ['mobile']);

        $this->postJson(
            '/api/account/support-tickets/'
            . $ticket->ticket_number
            . '/reply',
            [
                'message' =>
                    'Trying to reply',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'success',
                false
            );

        $this->assertDatabaseCount(
            'support_messages',
            1
        );
    }

    private function ticketPayload(
        array $attributes = []
    ): array {
        return array_merge([
            'name' => 'Test Customer',
            'email' =>
                'customer@example.com',

            'phone' => null,

            'subject' =>
                'Test Support Ticket',

            'category' => 'general',

            'message' =>
                'This is a valid support ticket message.',
        ], $attributes);
    }

    private function createTicket(
        array $attributes = []
    ): SupportTicket {
        $ticket = SupportTicket::query()
            ->create(
                array_merge([
                    'user_id' => null,
                    'name' => 'Test Customer',

                    'email' =>
                        'customer@example.com',

                    'phone' => null,

                    'subject' =>
                        'Test Support Ticket',

                    'category' => 'general',

                    'priority' =>
                        SupportTicket::PRIORITY_NORMAL,

                    'message' =>
                        'This is the initial support message.',

                    'attachment' => null,

                    'status' =>
                        SupportTicket::STATUS_NEW,

                    'admin_reply' => null,
                    'assigned_to' => null,
                    'replied_at' => null,
                    'closed_at' => null,
                ], $attributes)
            );

        $ticket->messages()->create([
            'sender_type' =>
                SupportMessage::SENDER_CUSTOMER,

            'sender_id' =>
                $ticket->user_id,

            'message' =>
                $ticket->message,

            'attachment' => null,
            'is_read' => false,
            'read_at' => null,
        ]);

        return $ticket;
    }
}