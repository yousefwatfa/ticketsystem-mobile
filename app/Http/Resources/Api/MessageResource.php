<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'author' => [
                'id' => $this->user?->id,
                'fullname' => $this->user?->fullname,
                'is_admin' => $this->user?->role?->name === 'Admin',
            ],
            'is_mine' => $this->created_by === $request->user()?->id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
