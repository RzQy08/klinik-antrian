<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            // Alamat tiket publik memakai kode acak ini, bukan ID berurutan
            $table->string('kode_tiket', 40)->nullable()->unique()->after('nomor_antrian');
        });

        // Antrian yang sudah ada ikut diberi kode acak
        DB::table('antrians')->whereNull('kode_tiket')->orderBy('id')->chunkById(500, function ($baris) {
            foreach ($baris as $b) {
                DB::table('antrians')->where('id', $b->id)->update(['kode_tiket' => Str::random(32)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('antrians', function (Blueprint $table) {
            $table->dropUnique(['kode_tiket']);
            $table->dropColumn('kode_tiket');
        });
    }
};