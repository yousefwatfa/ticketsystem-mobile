<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Attachment */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = strtolower((string) $this->file_type);

        return [
            'id' => $this->id,
            'name' => $this->file_name,
            'type' => $type,
            'is_image' => in_array($type, ['jpg', 'jpeg', 'png']),
            'url' => asset('storage/' . $this->file_path),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
