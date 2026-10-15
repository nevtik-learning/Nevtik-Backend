<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiResource extends JsonResource
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
          'status'      => $this->status,
          'jarak'       => $this->jarak,
          'latitude'    => $this->latitude !== null ? (float) $this->latitude : null,
          'longitude'   => $this->longitude !== null ? (float) $this->longitude : null,
          'keterangan'  => $this->keterangan,
          'waktu_absen' => $this->waktu_absen?->toIso8601String(),
          'user'        => new UserResource($this->whenLoaded('user')),
          'pertemuan'   => new PertemuanResource($this->whenLoaded('pertemuan')),
      ];
    }
}
