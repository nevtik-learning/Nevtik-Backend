<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_pertemuan_isi_absensi() RETURNS trigger AS $$
            BEGIN
                INSERT INTO absensi (pertemuan_id, user_id, status, created_at, updated_at)
                SELECT NEW.id, u.id, 'alpha', NOW(), NOW()
                FROM users u
                WHERE u.role IN ('siswa', 'pengurus')
                  AND (NEW.divisi_id IS NULL OR u.divisi_id = NEW.divisi_id)
                  AND (NEW.role IS NULL OR u.role = NEW.role);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_pertemuan_isi_absensi
            AFTER INSERT ON pertemuan
            FOR EACH ROW EXECUTE FUNCTION fn_pertemuan_isi_absensi();
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pertemuan_isi_absensi ON pertemuan');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_pertemuan_isi_absensi()');
    }
};