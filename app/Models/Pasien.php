<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected static function booted(): void
    {
        // Form pendaftaran bersifat publik dan mengenali pasien lewat NIK. Orang yang hanya tahu NIK
        // seseorang tidak boleh mengganti nomor HP yang sudah tersimpan (notifikasi WhatsApp
        // "giliran Anda" akan bocor ke nomor orang lain). Nomor yang sudah ada hanya bisa diubah
        // oleh pengguna yang sedang login (petugas atau admin).
        static::updating(function (Pasien $pasien) {
            if ($pasien->isDirty('no_hp') && filled($pasien->getOriginal('no_hp')) && ! auth()->check()) {
                $pasien->no_hp = $pasien->getOriginal('no_hp');
            }
        });
    }

    /** Nama untuk halaman publik: "Budi Santoso" menjadi "B*** S******". */
    protected function namaSamar(): Attribute
    {
        return Attribute::get(function () {
            return collect(preg_split('/\s+/u', trim((string) $this->nama), -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($kata) => mb_substr($kata, 0, 1).str_repeat('*', min(mb_strlen($kata) - 1, 6)))
                ->implode(' ');
        });
    }

    public function antrians(): HasMany
    {
        return $this->hasMany(Antrian::class);
    }
}
