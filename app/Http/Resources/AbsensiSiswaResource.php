<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiSiswaResource extends JsonResource
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
            'keterangan'  => $this->keterangan,
            'waktu_absen' => $this->waktu_absen?->toIso8601String(),
            'pertemuan'   => $this->whenLoaded('pertemuan', fn () => [
                'id'    => $this->pertemuan->id,
                'nama'  => $this->pertemuan->nama,
                'mode'  => $this->pertemuan->mode,
                'mulai' => $this->pertemuan->mulai?->toIso8601String(),
            ]),
        ];
    }
}
