<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_absensi AS
            SELECT a.id, a.pertemuan_id, a.user_id, a.status, a.jarak, a.waktu_absen,
                   p.nama AS pertemuan_nama, p.mulai, p.divisi_id AS pertemuan_divisi_id,
                   u.name AS user_name, u.email AS user_email, u.divisi_id AS user_divisi_id
            FROM absensi a
            JOIN pertemuan p ON p.id = a.pertemuan_id
            JOIN users u ON u.id = a.user_id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_absensi');
    }
};