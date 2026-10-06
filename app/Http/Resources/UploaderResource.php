<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versi ringkas pengguna untuk ditempel di resep (UploaderModel di Flutter).
 *
 * @mixin \App\Models\User
 */
class UploaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'profileImage' => $this->photo ? asset($this->photo) : null,
            'avatarIndex' => $this->avatar_index,
        ];
    }
}
