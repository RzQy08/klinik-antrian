<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poli extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_poli',
        'kode_poli',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function dokters(): HasMany
    {
        return $this->hasMany(Dokter::class);
    }

    public function antrians(): HasMany
    {
        return $this->hasMany(Antrian::class);
    }

    public function queueMetrics(): HasMany
    {
        return $this->hasMany(QueueMetric::class);
    }

    /**
     * Antrian aktif hari ini untuk poli ini (dipakai buat display realtime).
     */
    public function antrianHariIni(): HasMany
    {
        return $this->hasMany(Antrian::class)
            ->whereDate('tanggal', now()->toDateString());
    }
}
