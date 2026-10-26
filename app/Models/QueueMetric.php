<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueMetric extends Model
{
    use HasFactory;

    protected $table = 'queue_metrics';

    protected $fillable = [
        'poli_id',
        'tanggal',
        'lambda',
        'mu',
        'rho',
        'wq_teori_menit',
        'wq_aktual_menit',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lambda' => 'decimal:4',
        'mu' => 'decimal:4',
        'rho' => 'decimal:4',
        'wq_teori_menit' => 'decimal:2',
        'wq_aktual_menit' => 'decimal:2',
    ];

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }
}
