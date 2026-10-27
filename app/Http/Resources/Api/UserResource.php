<?php

namespace App\Http\Resources\Api;

use App\Support\OfficeLocationTranslator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->role?->name;

        return [
            'id' => $this->id,
            'username' => $this->username,
            'fullname' => $this->fullname,
            'role' => $role,
            'is_admin' => $role === 'Admin' || (bool) $this->is_root,
            'is_root' => (bool) $this->is_root,
            'office_location' => $this->office_location ? OfficeLocationTranslator::toArabic($this->office_location) : null,
            'telegram_linked' => (bool) $this->telegram_linked_at,
        ];
    }
}
