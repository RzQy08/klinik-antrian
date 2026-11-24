<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status baru "telat": pasien yang sudah dipanggil tetapi belum datang setelah batas waktu.
 * Kolom status semula ENUM, jadi diganti string supaya nilai baru bisa dipakai tanpa
 * mengubah definisi ENUM setiap kali ada status tambahan. Nilai yang sah dijaga aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            $table->string('status', 20)->default('menunggu')->change();
            $table->timestamp('waktu_telat')->nullable()->after('waktu_dipanggil');
        });
    }

    public function down(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            $table->dropColumn('waktu_telat');
        });
    }
};
