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
