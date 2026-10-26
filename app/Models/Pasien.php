<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pasien extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_rm',
        'nama',
        'nik',
        'tanggal_lahir',
        'jenis_kelamin',
        'no_hp',
        'alamat',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function antrians(): HasMany
    {
        return $this->hasMany(Antrian::class);
    }
}
