<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalRumahSakit extends Model
{
    protected $table = 'jadwal_rumah_sakit';

    public const HARI = [
        'senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis',
        'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu',
    ];

    protected $fillable = ['hari', 'buka', 'pendaftaran_buka', 'pendaftaran_tutup', 'layanan_selesai'];

    protected $casts = ['buka' => 'boolean'];
}
