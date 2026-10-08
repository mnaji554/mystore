<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use Livewire\Component;
use Livewire\WithPagination;

class Messages extends Component
{
    use WithPagination;

    public ?int $openId = null;

    public function mount(): void
    {
        $this->authorize('manage-settings');
    }

    public function open(int $id): void
    {
        $this->authorize('manage-settings');
        $message = ContactMessage::findOrFail($id);
        $message->update(['read_at' => $message->read_at ?? now()]);
        $this->openId = $this->openId === $id ? null : $id;
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-settings');
        $this->authorize('delete-records');
        ContactMessage::findOrFail($id)->delete();
        $this->openId = null;
    }

    public function render()
    {
        return view('livewire.admin.messages', [
            'messages' => ContactMessage::query()->latest('id')->paginate(15),
        ])->layout('components.layouts.admin', ['title' => 'رسائل التواصل']);
    }
}
