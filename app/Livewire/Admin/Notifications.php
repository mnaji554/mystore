<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function open(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url && str_starts_with($url, url('/')) ? $this->redirect($url) : null;
    }

    public function render()
    {
        return view('livewire.admin.notifications', [
            'notifications' => auth()->user()->notifications()->paginate(15),
        ])->layout('components.layouts.admin', ['title' => 'الإشعارات']);
    }
}
