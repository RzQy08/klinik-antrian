<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Antrian extends Model
{
    use HasFactory;
    protected static function booted(): void
    {
        // Setiap antrian baru otomatis mendapat kode acak untuk alamat tiketnya
        static::creating(function (Antrian $antrian) {
            $antrian->kode_tiket ??= Str::random(32);
        });
    }

    // ==== Relasi ====
    protected $fillable = [
        'pasien_id',
        'poli_id',
        'dokter_id',
        'nomor_antrian',
        'status',
        'waktu_daftar',
        'waktu_dipanggil',
        'waktu_mulai_dilayani',
        'waktu_selesai',
        'estimasi_tunggu_detik',
        'jumlah_panggil',
        'tanggal',
        'tanggal_asal',
        'nomor_lama',
        'dialihkan_at',
    ];

    protected $casts = [
        'waktu_daftar' => 'datetime',
        'waktu_dipanggil' => 'datetime',
        'waktu_mulai_dilayani' => 'datetime',
        'waktu_selesai' => 'datetime',
        'dialihkan_at' => 'datetime',
        'tanggal' => 'date',
        'tanggal_asal' => 'date',
    ];

    // ==== Relasi ====

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }

    // ==== Scope ====

    public function scopeHariIni(Builder $query): Builder
    {
        return $query->padaTanggal(now());
    }

    /**
     * Filter satu hari memakai rentang (>= hari ini dan < besok), bukan whereDate().
     * whereDate() membungkus kolom dengan fungsi DATE() sehingga MySQL tidak bisa
     * memakai index; rentang ini bisa, jadi tetap cepat saat data antrian menumpuk.
     */
    public function scopePadaTanggal(Builder $query, $tanggal): Builder
    {
        $hari = Carbon::parse($tanggal);

        return $query
            ->where('tanggal', '>=', $hari->toDateString())
            ->where('tanggal', '<', $hari->copy()->addDay()->toDateString());
    }

        /**
     * Rentang beberapa hari (inklusif) dengan pola yang sama seperti padaTanggal(): >= awal dan < sehari
     * setelah akhir. whereBetween('tanggal', [awal, akhir]) bisa melewatkan baris di tanggal akhir kalau
     * kolomnya tersimpan beserta jam (terjadi di SQLite), sedangkan rentang ini aman di semua database.
     */
    public function scopeAntaraTanggal(Builder $query, $dari, $sampai): Builder
    {
        return $query
            ->where('tanggal', '>=', Carbon::parse($dari)->toDateString())
            ->where('tanggal', '<', Carbon::parse($sampai)->addDay()->toDateString());
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', 'selesai');
    }

    /**
     * Dinamai untukPoli() (bukan poli()) agar tidak bentrok dengan relasi poli().
     */
    public function scopeUntukPoli(Builder $query, int $poliId): Builder
    {
        return $query->where('poli_id', $poliId);
    }

    /**
     * Data untuk analitik Queue Theory: antrian selesai, TANPA limpahan dari hari lain.
     * Limpahan dikeluarkan karena waktu daftarnya dibuat tengah malam, sehingga
     * waktu tunggunya (semalaman) akan merusak perhitungan lambda dan Wq.
     */
    public function scopeUntukAnalitik(Builder $query): Builder
    {
        return $query->where('status', 'selesai')->whereNull('tanggal_asal');
    }

        /**
     * Ekspresi SQL selisih dua kolom waktu dalam detik (akhir dikurangi awal), supaya rata-rata
     * dihitung langsung di database. Nama kolom hanya dari kode, bukan dari input pengguna.
     */
    public static function sqlSelisihDetik(string $awal, string $akhir): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "(strftime('%s', {$akhir}) - strftime('%s', {$awal}))",
            'pgsql' => "EXTRACT(EPOCH FROM ({$akhir} - {$awal}))",
            default => "TIMESTAMPDIFF(SECOND, {$awal}, {$akhir})",
        };
    }

    // ==== Accessor bantu ====

    public function getLamaTungguDetikAttribute(): ?int
    {
        if (! $this->waktu_daftar || ! $this->waktu_mulai_dilayani) {
            return null;
        }

        return (int) $this->waktu_daftar->diffInSeconds($this->waktu_mulai_dilayani);
    }

    public function getLamaLayananDetikAttribute(): ?int
    {
        if (! $this->waktu_mulai_dilayani || ! $this->waktu_selesai) {
            return null;
        }

        return (int) $this->waktu_mulai_dilayani->diffInSeconds($this->waktu_selesai);
    }

    public function getLimpahanAttribute(): bool
    {
        return $this->tanggal_asal !== null;
    }
}
