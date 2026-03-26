<?php

namespace App\Livewire\Admin\Notifications;

use Livewire\Component;

class Bell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.admin.notifications.bell', [
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
            'latestNotifications' => $user?->notifications()->latest()->limit(12)->get() ?? collect(),
        ]);
    }
}
