<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanJadwal extends Model
{
    protected $fillable = [
        'dokter_id', 'hari', 'jam_mulai_baru', 'jam_selesai_baru', 'kuota_baru',
        'alasan', 'status', 'ditinjau_oleh', 'catatan_admin', 'ditinjau_pada',
    ];

    protected $casts = ['ditinjau_pada' => 'datetime'];

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }
}
