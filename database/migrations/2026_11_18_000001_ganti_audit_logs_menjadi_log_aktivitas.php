<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti nama tabel audit_logs menjadi log_aktivitas. Isi catatan lama ikut terbawa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs') && ! Schema::hasTable('log_aktivitas')) {
            Schema::rename('audit_logs', 'log_aktivitas');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('log_aktivitas') && ! Schema::hasTable('audit_logs')) {
            Schema::rename('log_aktivitas', 'audit_logs');
        }
    }
};
