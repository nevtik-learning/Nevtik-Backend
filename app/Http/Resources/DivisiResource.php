<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
      return [
          'id'          => $this->id,
          'nama'        => $this->nama,
          'users' => $this->relationLoaded('users') ? $this->users->pluck('name') : [],
          'users_count' => $this->users_count ?? 0,
      ];
    }
}
