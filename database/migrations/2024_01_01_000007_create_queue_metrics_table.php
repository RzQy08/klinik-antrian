<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poli_id')->constrained('polis')->cascadeOnDelete();
            $table->date('tanggal');

            $table->decimal('lambda', 8, 4)->nullable();   // rata-rata kedatangan/jam
            $table->decimal('mu', 8, 4)->nullable();        // rata-rata pelayanan/jam
            $table->decimal('rho', 8, 4)->nullable();       // tingkat utilisasi
            $table->decimal('wq_teori_menit', 8, 2)->nullable();     // hasil rumus
            $table->decimal('wq_aktual_menit', 8, 2)->nullable();    // rata-rata realisasi

            $table->timestamps();

            $table->unique(['poli_id', 'tanggal']); // satu snapshot per poli per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_metrics');
    }
};
