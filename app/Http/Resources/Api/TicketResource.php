<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'office_location' => $this->office_location,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'fullname' => $this->creator->fullname,
            ] : null),
            'assignees' => $this->whenLoaded('assignedUsers', fn () => $this->assignedUsers->map(fn ($u) => [
                'id' => $u->id,
                'fullname' => $u->fullname,
            ])->values()),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'messages_count' => $this->whenCounted('messages'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // what the current user may do, so the app never re-implements the policy
            'can' => $this->when($user !== null, fn () => $this->abilitiesFor($user)),
        ];
    }

    private function abilitiesFor($user): array
    {
        $isCreator = $this->created_by === $user->id;
        $isAdmin = $user->role?->name === 'Admin' || $user->is_root;
        $canUpdate = $isAdmin && $user->can('update', $this->resource);
        $open = !in_array($this->status, ['finished', 'closed']);

        return [
            'reply' => $this->status !== 'closed' && ($isCreator || $isAdmin),
            'attach' => $this->status !== 'closed' && ($isCreator || $isAdmin),
            'confirm' => $isCreator && $this->status === 'finished',
            'not_fixed' => $isCreator && $this->status === 'finished',
            'take' => $canUpdate && $open && !$this->assignedUsers->contains($user->id),
            'finish' => $canUpdate && $open,
        ];
    }
}
