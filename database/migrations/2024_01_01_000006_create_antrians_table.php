<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antrians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasiens')->cascadeOnDelete();
            $table->foreignId('poli_id')->constrained('polis')->cascadeOnDelete();
            $table->foreignId('dokter_id')->nullable()->constrained('dokters')->nullOnDelete();

            $table->string('nomor_antrian', 10); // contoh: A001, A002

            $table->enum('status', [
                'menunggu',
                'dipanggil',
                'dilayani',
                'selesai',
                'batal',
                'tidak_hadir',
            ])->default('menunggu');

            // Kolom kunci untuk perhitungan Queue Theory
            $table->timestamp('waktu_daftar');          // dipakai hitung lambda (kedatangan)
            $table->timestamp('waktu_dipanggil')->nullable();
            $table->timestamp('waktu_mulai_dilayani')->nullable();
            $table->timestamp('waktu_selesai')->nullable(); // dipakai hitung mu (waktu layanan)

            $table->unsignedInteger('estimasi_tunggu_detik')->nullable(); // prediksi sistem saat pasien daftar
            $table->date('tanggal'); // buat query per hari, index cepat

            $table->timestamps();

            // index buat query realtime (ambil antrian aktif per poli per tanggal)
            $table->index(['poli_id', 'tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrians');
    }
};
