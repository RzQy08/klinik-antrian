<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturans';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    public static function ambil(string $kunci, ?string $bawaan = null): ?string
    {
        return static::query()->whereKey($kunci)->value('nilai') ?? $bawaan;
    }

    public static function simpan(string $kunci, ?string $nilai): void
    {
        static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }
}
