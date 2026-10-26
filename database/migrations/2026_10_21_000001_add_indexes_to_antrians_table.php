<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            // dashboard dokter: antrian milik satu dokter pada satu hari menurut status
            $table->index(['dokter_id', 'tanggal', 'status'], 'antrians_dokter_tanggal_status_index');

            // layar TV dan panggilan FIFO: antrian satu poli pada satu hari menurut urutan daftar
            $table->index(['poli_id', 'tanggal', 'waktu_daftar'], 'antrians_poli_tanggal_daftar_index');

            // limpahan dan laporan: semua antrian pada satu hari menurut status
            $table->index(['tanggal', 'status'], 'antrians_tanggal_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            $table->dropIndex('antrians_dokter_tanggal_status_index');
            $table->dropIndex('antrians_poli_tanggal_daftar_index');
            $table->dropIndex('antrians_tanggal_status_index');
        });
    }
};
