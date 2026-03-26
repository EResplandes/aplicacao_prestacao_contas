<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_and_mark_notifications_as_read(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $requester->notify(new OperationalAlertNotification(
            title: 'Caixa liberado para uso',
            message: 'Seu caixa foi liberado e o valor já está disponível.',
            type: 'cash_request.released',
            context: ['cash_request_public_id' => 'cash-request-demo'],
            occurredAt: now(),
        ));

        Sanctum::actingAs($requester);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Caixa liberado para uso')
            ->assertJsonPath('data.0.type', 'cash_request.released')
            ->assertJsonPath('data.0.is_read', false);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertSame(0, $requester->fresh()->unreadNotifications()->count());
    }
}
