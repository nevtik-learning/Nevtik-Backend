<?php

namespace App\Models;


use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class User extends Authenticatable
{
    use HasApiTokens, HasUlids, HasFactory;

    protected $fillable = ['name', 'email', 'password', 'role', 'divisi_id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public static function buatEmail(string $name): string
    {
        // "Muhammad Rasya Raya Agung" jadi "muhammadrasyarayaagung"
        $base = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->toString();
        $base = $base !== '' ? $base : 'user';
    
        // 1 query: ambil semua email yang mirip, lalu cari nomor yang kosong
        $ada = static::where('email', 'like', "{$base}%@nevtik.my.id")->pluck('email')->all();
    
        $email = "{$base}@nevtik.my.id";
        $i = 1;
        while (in_array($email, $ada, true)) {
            $i++;
            $email = "{$base}{$i}@nevtik.my.id";
        }
    
        return $email;
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }
}