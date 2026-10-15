<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pertemuan', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('divisi_id')->nullable()->constrained('divisi')->restrictOnDelete();
            $table->enum('role', ['siswa', 'pengurus'])->nullable();
            $table->foreignUlid('pengurus_id')->constrained('users');
            $table->string('nama');
            $table->string('kode', 5)->unique();
            $table->enum('mode', ['online', 'offline']);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance')->nullable(); // meter
            $table->dateTime('mulai');
            $table->dateTime('selesai');
            $table->timestamps();

            $table->index(['divisi_id', 'mulai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pertemuan');
    }
};
