<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Jejak siapa melakukan apa pada data. Hanya bisa ditambah, tidak pernah diubah dari aplikasi.
 * Dibersihkan otomatis setelah 1 tahun lewat: php artisan model:prune
 */
class AuditLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    /** aksi => [label, warna badge] */
    public const AKSI = [
        'login' => ['Masuk', 'ok'],
        'logout' => ['Keluar', 'muted'],
        'login_gagal' => ['Gagal masuk', 'warn'],
        'login_terkunci' => ['Dikunci sementara', 'bad'],
        'login_demo' => ['Masuk cepat (DEV)', 'warn'],
        'password_reset' => ['Reset password', 'warn'],
        'password_diubah' => ['Password diubah', 'warn'],
        'akun_dibuat' => ['Akun dibuat', 'ok'],
        'peran_diubah' => ['Peran diubah', 'bad'],
        'akun_dihapus' => ['Akun dihapus', 'bad'],
        'lihat_riwayat' => ['Lihat riwayat pasien', 'muted'],
        'ekspor_csv' => ['Unduh CSV pasien', 'bad'],
    ];

    public function label(): string
    {
        return self::AKSI[$this->aksi][0] ?? $this->aksi;
    }

    public function warna(): string
    {
        return self::AKSI[$this->aksi][1] ?? 'muted';
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subYear());
    }
}
