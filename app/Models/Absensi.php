<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'pertemuan_id', 'user_id', 'status', 'latitude', 'longitude',
        'jarak', 'keterangan', 'waktu_absen',
    ];

    protected $casts = ['waktu_absen' => 'datetime'];

    public function pertemuan() { return $this->belongsTo(Pertemuan::class); }
    public function user() { return $this->belongsTo(User::class); }
}