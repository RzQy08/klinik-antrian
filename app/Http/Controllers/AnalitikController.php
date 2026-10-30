<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Poli;
use App\Services\QueueTheoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalitikController extends Controller
{
    /**
     * Dashboard analitik: bandingkan Wq TEORI (hasil rumus Queue Theory)
     * dengan Wq AKTUAL (rata-rata riil dari data historis antrian).
     *
     * Ini yang jadi bahan validasi model di bab Hasil & Pembahasan skripsi.
     */
    public function index(Request $request): View
    {
                // Rentang dibatasi 1-90 hari supaya input aneh tidak memaksa database membaca data bertahun-tahun
        $hari = $request->integer('hari', 14);
        $jumlahHari = $hari < 1 ? 14 : min($hari, 90);
        // Target rata-rata waktu tunggu (menit) untuk rekomendasi jumlah dokter, dibatasi 1-60
        $target = min(60, max(1, $request->integer('target', 10)));
        // Tanggal akhir bisa ditentukan lewat ?sampai=YYYY-MM-DD (bawaan: kemarin), supaya angka pada
        // laporan dapat dibuka kembali untuk periode yang sama. Rentang selalu berakhir pada tanggal itu.
        $request->validate(['sampai' => ['nullable', 'date']]);
        $sampai = $request->filled('sampai') ? Carbon::parse($request->input('sampai'))->startOfDay() : Carbon::yesterday();
        $dari = $sampai->copy()->subDays($jumlahHari - 1);

        $polis = Poli::where('aktif', true)->get();

        // Satu query untuk semua poli (dihitung di database), bukan dua query per poli
        $layanan = Antrian::sqlSelisihDetik('waktu_mulai_dilayani', 'waktu_selesai');
        $tunggu = Antrian::sqlSelisihDetik('waktu_daftar', 'waktu_mulai_dilayani');

        $statistik = Antrian::query()
            ->untukAnalitik()
            ->antaraTanggal($dari, $sampai)
            ->selectRaw("poli_id, COUNT(*) as jumlah, COUNT(DISTINCT dokter_id) as dokter, COUNT(DISTINCT tanggal) as hari_aktif, AVG({$layanan}) as rata_layanan, AVG({$tunggu}) as rata_tunggu")
            ->groupBy('poli_id')
            ->get()
            ->keyBy('poli_id');

        $hasil = $polis->map(function (Poli $poli) use ($statistik, $dari, $sampai, $target) {
            return $this->analisisSatuPoli($poli, $statistik->get($poli->id), $dari, $sampai, $target);
        });

        return view('antrian.analitik', [
            'hasil' => $hasil,
            'jumlahHari' => $jumlahHari,
            'target' => $target,
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    /**
     * Hitung metrik teori vs aktual untuk satu poli.
     */
        private function analisisSatuPoli(Poli $poli, ?Antrian $statistik, Carbon $dari, Carbon $sampai, int $target = 10): array
    {
        $jumlah = $statistik ? (int) $statistik->jumlah : 0;

        if ($jumlah === 0) {
            return [
                'poli' => $poli,
                'ada_data' => false,
                'jumlah_sampel' => 0,
            ];
        }

        try {
            $service = QueueTheoryService::dariStatistik(
                jumlah: $jumlah,
                rataLamaLayananDetik: $statistik->rata_layanan !== null ? (float) $statistik->rata_layanan : null,
                jumlahDokter: (int) $statistik->dokter,
                dari: $dari,
                sampai: $sampai,
                hariAktif: (int) $statistik->hari_aktif,
            );
        } catch (\InvalidArgumentException $e) {
            return [
                'poli' => $poli,
                'ada_data' => false,
                'jumlah_sampel' => $jumlah,
                'pesan_error' => $e->getMessage(),
            ];
        }

        // Wq AKTUAL: rata-rata riil dari selisih waktu_daftar -> waktu_mulai_dilayani
        $wqAktualDetik = $statistik->rata_tunggu !== null ? (float) $statistik->rata_tunggu : null;
        $wqAktualMenit = $wqAktualDetik !== null ? $wqAktualDetik / 60 : null;

        $stabil = $service->stabil();

        $ringkasan = $stabil ? $service->ringkasan() : null;

        // selisih & persentase error, buat kolom "validasi model" di tabel
        $selisihMenit = null;
        $persentaseError = null;

        if ($stabil && $wqAktualMenit !== null) {
            $selisihMenit = abs($ringkasan['wq_menit'] - $wqAktualMenit);
            $persentaseError = $wqAktualMenit > 0
                ? ($selisihMenit / $wqAktualMenit) * 100
                : null;
        }

        // Rekomendasi jumlah dokter + simulasi skenario di sekitar jumlah dokter saat ini
        $rekomendasi = $service->rekomendasiDokter($target);
        $batasAtas = min(10, max($service->c + 3, $rekomendasi ?? 0));

        return [
            'poli' => $poli,
            'ada_data' => true,
            'rekomendasi_dokter' => $rekomendasi,
            'simulasi' => $service->simulasiDokter(max(1, $service->c - 1), $batasAtas),
            'jumlah_sampel' => $jumlah,
            'stabil' => $stabil,
            'lambda' => $service->lambda,
            'mu' => $service->mu,
            'jumlah_server' => $service->c,
            'rho' => round($service->rho(), 4),
            'wq_teori_menit' => $stabil ? round($ringkasan['wq_menit'], 2) : null,
            'wq_aktual_menit' => $wqAktualMenit !== null ? round($wqAktualMenit, 2) : null,
            'selisih_menit' => $selisihMenit !== null ? round($selisihMenit, 2) : null,
            'persentase_error' => $persentaseError !== null ? round($persentaseError, 1) : null,
        ];
    }
}
