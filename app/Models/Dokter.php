<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dokter extends Model
{
    use HasFactory;

    protected $fillable = [
        'poli_id',
        'user_id',
        'nama',
        'no_sip',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwalPraktiks(): HasMany
    {
        return $this->hasMany(JadwalPraktik::class);
    }

    public function cutis(): HasMany
    {
        return $this->hasMany(CutiDokter::class);
    }

    /** Dokter yang tidak sedang cuti pada tanggal tertentu (default hari ini). */
    public function scopeTidakCuti($query, $tanggal = null)
    {
        $tanggal = ($tanggal ? \Illuminate\Support\Carbon::parse($tanggal) : now())->toDateString();

        return $query->whereDoesntHave('cutis', fn ($q) => $q->whereDate('tanggal_mulai', '<=', $tanggal)->whereDate('tanggal_selesai', '>=', $tanggal));
    }

    public function sedangCuti($tanggal = null): bool
    {
        $tanggal = ($tanggal ? \Illuminate\Support\Carbon::parse($tanggal) : now())->toDateString();

        return $this->cutis()->whereDate('tanggal_mulai', '<=', $tanggal)->whereDate('tanggal_selesai', '>=', $tanggal)->exists();
    }

    public function antrians(): HasMany
    {
        return $this->hasMany(Antrian::class);
    }

    /**
     * Cek apakah dokter sedang praktik pada hari & jam saat ini.
     */
    public function sedangPraktik(): bool
    {
        $hari = strtolower(now()->translatedFormat('l')); // sesuaikan locale Indonesia
        $jamSekarang = now()->format('H:i:s');

        return $this->jadwalPraktiks()
            ->where('hari', $hari)
            ->where('jam_mulai', '<=', $jamSekarang)
            ->where('jam_selesai', '>=', $jamSekarang)
            ->exists();
    }
}
