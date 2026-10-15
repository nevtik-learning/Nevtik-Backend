<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pertemuan extends Model
{
    use HasUlids;

    protected $table = 'pertemuan';

    protected $fillable = [
        'divisi_id', 'role', 'pengurus_id', 'nama', 'kode', 'mode',
        'latitude', 'longitude', 'distance', 'mulai', 'selesai',
    ];

    protected $casts = ['mulai' => 'datetime', 'selesai' => 'datetime'];

    protected static function booted(): void
    {
        // data berubah: naikkan versi, cache daftar otomatis basi
        static::saved(fn () => Cache::increment('pertemuan:ver'));
        static::deleted(fn () => Cache::increment('pertemuan:ver'));
    }

    public function divisi() { return $this->belongsTo(Divisi::class); }
    public function pengurus() { return $this->belongsTo(User::class, 'pengurus_id'); }
    public function absensi() { return $this->hasMany(Absensi::class); }

    // baris absensi diisi otomatis oleh trigger database
    public static function buat(array $data): self
    {
        do {
            $kode = (string) random_int(10000, 99999);
        } while (static::where('kode', $kode)->exists());

        return static::create($data + ['kode' => $kode]);
    }
}