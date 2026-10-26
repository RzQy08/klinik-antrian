<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_poli');
            $table->string('kode_poli', 5)->unique(); // contoh: A, B, C -> prefix nomor antrian
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polis');
    }
};
