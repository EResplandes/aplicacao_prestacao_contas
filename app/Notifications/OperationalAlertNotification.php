<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
        private readonly array $context = [],
        private readonly ?CarbonInterface $occurredAt = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'context' => $this->context,
            'occurred_at' => ($this->occurredAt ?? now())->toIso8601String(),
        ];
    }
}
