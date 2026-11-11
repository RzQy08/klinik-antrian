<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jam buka rumah sakit per hari dalam seminggu
        Schema::create('jadwal_rumah_sakit', function (Blueprint $table) {
            $table->id();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])->unique();
            $table->boolean('buka')->default(true);
            $table->string('pendaftaran_buka', 5)->default('07:00');
            $table->string('pendaftaran_tutup', 5)->default('11:00');
            $table->string('layanan_selesai', 5)->default('14:00');
            $table->timestamps();
        });

        // Tanggal libur (hari besar, cuti bersama, penutupan khusus)
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('keterangan', 120);
            $table->timestamps();
        });

        // Dokter yang cuti atau berhalangan pada rentang tanggal tertentu
        Schema::create('cuti_dokters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokter_id')->constrained('dokters')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('alasan', 120)->nullable();
            $table->timestamps();

            $table->index(['dokter_id', 'tanggal_mulai', 'tanggal_selesai']);
        });

        // Pengaturan umum berbentuk kunci dan nilai
        Schema::create('pengaturans', function (Blueprint $table) {
            $table->string('kunci', 60)->primary();
            $table->text('nilai')->nullable();
            $table->timestamps();
        });

        // Tabel dibiarkan kosong: selama admin belum menyimpan jadwal, aplikasi memakai
        // config/operasional.php (dan .env) seperti sebelumnya.
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturans');
        Schema::dropIfExists('cuti_dokters');
        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('jadwal_rumah_sakit');
    }
};
