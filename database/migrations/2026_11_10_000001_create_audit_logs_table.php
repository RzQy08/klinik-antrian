<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Nama dan peran disalin supaya jejak tetap terbaca walau akunnya sudah dihapus
            $table->string('nama_pengguna')->nullable();
            $table->string('peran', 20)->nullable();
            $table->string('aksi', 40)->index();
            $table->string('keterangan', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('perangkat', 160)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
