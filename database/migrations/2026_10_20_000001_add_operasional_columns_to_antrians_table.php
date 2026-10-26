<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            // berapa kali pasien sudah dipanggil (1 = panggilan pertama)
            $table->unsignedTinyInteger('jumlah_panggil')->default(0)->after('estimasi_tunggu_detik');

            // diisi kalau antrian ini limpahan dari hari sebelumnya
            $table->date('tanggal_asal')->nullable()->after('tanggal');
            $table->string('nomor_lama', 10)->nullable()->after('tanggal_asal');

            // diisi pada antrian LAMA yang sudah dipindah ke hari berikutnya
            $table->timestamp('dialihkan_at')->nullable()->after('nomor_lama');
        });
    }

    public function down(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            $table->dropColumn(['jumlah_panggil', 'tanggal_asal', 'nomor_lama', 'dialihkan_at']);
        });
    }
};
