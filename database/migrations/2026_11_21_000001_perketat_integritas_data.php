<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memperketat integritas data di tingkat database (bukan hanya di kode aplikasi):
 * - satu dokter hanya boleh punya satu jadwal per hari;
 * - satu akun login hanya boleh terhubung ke satu dokter.
 *
 * Aman dijalankan pada data yang sudah ada: kalau ditemukan duplikat, jadwal ganda dirapikan
 * (yang terbaru dipertahankan) dan keterhubungan akun ganda dilepas dari yang lebih baru,
 * sebelum indeks unik dipasang.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Jadwal ganda: simpan baris dengan id terbesar untuk tiap (dokter, hari)
        $ganda = DB::table('jadwal_praktiks')
            ->select('dokter_id', 'hari', DB::raw('MAX(id) as simpan'))
            ->groupBy('dokter_id', 'hari')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($ganda as $g) {
            DB::table('jadwal_praktiks')
                ->where('dokter_id', $g->dokter_id)
                ->where('hari', $g->hari)
                ->where('id', '<>', $g->simpan)
                ->delete();
        }

        Schema::table('jadwal_praktiks', function (Blueprint $table) {
            $table->unique(['dokter_id', 'hari'], 'jadwal_praktiks_dokter_hari_unique');
        });

        // Akun yang dipakai lebih dari satu dokter: biarkan dokter pertama, lepaskan sisanya
        $akunGanda = DB::table('dokters')
            ->select('user_id', DB::raw('MIN(id) as simpan'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($akunGanda as $a) {
            DB::table('dokters')
                ->where('user_id', $a->user_id)
                ->where('id', '<>', $a->simpan)
                ->update(['user_id' => null]);
        }

        Schema::table('dokters', function (Blueprint $table) {
            $table->unique('user_id', 'dokters_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('dokters', function (Blueprint $table) {
            $table->dropUnique('dokters_user_id_unique');
        });

        Schema::table('jadwal_praktiks', function (Blueprint $table) {
            $table->dropUnique('jadwal_praktiks_dokter_hari_unique');
        });
    }
};
