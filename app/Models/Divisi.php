<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    protected $table = 'divisi';
    protected $fillable = ['nama'];
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pertemuan()
    {
        return $this->hasMany(Pertemuan::class);
    }
}