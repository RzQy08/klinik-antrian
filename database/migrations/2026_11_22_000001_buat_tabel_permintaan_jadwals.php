<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained()->cascadeOnDelete();
            $table->string('hari', 10);
            $table->time('jam_mulai_baru');
            $table->time('jam_selesai_baru');
            $table->unsignedSmallInteger('kuota_baru');
            $table->string('alasan', 180)->nullable();
            $table->string('status', 10)->default('menunggu'); // menunggu, disetujui, ditolak
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('catatan_admin', 180)->nullable();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->timestamps();

            $table->index(['dokter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_jadwals');
    }
};
