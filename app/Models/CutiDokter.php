<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CutiDokter extends Model
{
    protected $table = 'cuti_dokters';

    protected $fillable = ['dokter_id', 'tanggal_mulai', 'tanggal_selesai', 'alasan'];

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }
}
