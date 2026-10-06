<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'firstName' => strtok($this->name, ' '),
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'birthDate' => $this->birth_date?->toDateString(),
            'profileImage' => $this->photo ? asset($this->photo) : null,
            'avatarIndex' => $this->avatar_index,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
