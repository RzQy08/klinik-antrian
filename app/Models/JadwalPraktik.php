<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPraktik extends Model
{
    use HasFactory;

    protected $fillable = [
        'dokter_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'kuota',
    ];

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }
}
