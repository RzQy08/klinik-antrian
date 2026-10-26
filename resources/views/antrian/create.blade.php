<x-public-layout title="Ambil nomor antrian" :wide="true">
    <div class="row g-4 g-lg-5">

        <aside class="col-lg-5">
            <div class="board board-pattern p-4 p-lg-5 sticky-lg-top" style="top: 6rem">
                <h1 class="h2 text-white lh-sm mb-3">Pendaftaran antrian poliklinik</h1>
                <p class="board-muted mb-4">Ambil nomor dari ponsel atau kios, lalu tunggu sampai nomor Anda tampil di layar ruang tunggu.</p>

                <ol class="list-unstyled d-grid gap-3 mb-0">
                    @foreach (['Pilih poli tujuan.', 'Isi NIK dan data diri singkat.', 'Terima nomor dan perkiraan waktu tunggu.', 'Datang saat nomor Anda hampir dipanggil.'] as $langkah)
                        <li class="d-flex align-items-start gap-3">
                            <span class="numeral d-inline-grid flex-shrink-0 rounded-circle fs-5" style="width: 2.25rem; height: 2.25rem; place-items: center; background: rgba(255,255,255,.14)">{{ $loop->iteration }}</span>
                            <span class="pt-1">{{ $langkah }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </aside>

        <section class="col-lg-7">
            @include('partials.errors')

            @unless ($pendaftaranDibuka)
                <div class="alert alert-danger" role="alert">
                    <div class="fw-semibold"><i class="bi bi-clock-history me-1"></i> Pendaftaran sedang ditutup</div>
                    <div>{{ $pesanTutup }}</div>
                </div>
            @endunless

            <form method="POST" action="{{ route('antrian.store') }}" class="card">
                @csrf
                <fieldset class="card-body p-4 p-md-5 d-grid gap-4" @disabled(! $pendaftaranDibuka)>

                    <div>
                        <div class="form-label mb-2">Poli tujuan</div>
                        <div class="row g-3">
                            @foreach ($polis as $poli)
                                <div class="col-sm-6">
                                    <input type="radio" class="btn-check" name="poli_id" id="poli-{{ $poli->id }}" value="{{ $poli->id }}" autocomplete="off" required @checked(old('poli_id') == $poli->id)>
                                    <label class="choice-card h-100" for="poli-{{ $poli->id }}">
                                        <span class="kode">{{ $poli->kode_poli }}</span>
                                        <span class="fw-semibold">{{ $poli->nama_poli }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="nik" class="form-label">NIK</label>
                        <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" pattern="[0-9]{16}" autocomplete="off" required class="form-control" value="{{ old('nik') }}">
                        <div class="form-text">16 digit. Pasien lama dikenali otomatis dari NIK.</div>
                    </div>

                    <div>
                        <label for="nama" class="form-label">Nama lengkap</label>
                        <input id="nama" name="nama" type="text" autocomplete="name" required class="form-control" value="{{ old('nama') }}">
                    </div>

                    <div>
                        <label for="no_hp" class="form-label">No. WhatsApp <span class="fw-normal text-body-secondary">(opsional)</span></label>
                        <input id="no_hp" name="no_hp" type="tel" inputmode="tel" autocomplete="tel" class="form-control" value="{{ old('no_hp') }}">
                        <div class="form-text">Isi untuk menerima pemberitahuan WhatsApp saat nomor Anda dipanggil. Contoh: 081234567890</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="tanggal_lahir" class="form-label">Tanggal lahir <span class="fw-normal text-body-secondary">(opsional)</span></label>
                            <input id="tanggal_lahir" name="tanggal_lahir" type="date" class="form-control" value="{{ old('tanggal_lahir') }}">
                        </div>
                        <div class="col-sm-6">
                            <div class="form-label">Jenis kelamin <span class="fw-normal text-body-secondary">(opsional)</span></div>
                            <div class="row g-2">
                                @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $nilai => $teks)
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="jenis_kelamin" id="jk-{{ $nilai }}" value="{{ $nilai }}" autocomplete="off" @checked(old('jenis_kelamin') === $nilai)>
                                        <label class="choice-card choice-pill" for="jk-{{ $nilai }}">{{ $teks }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" @disabled(! $pendaftaranDibuka)><i class="bi bi-ticket-perforated me-2"></i>Ambil nomor antrian</button>
                </fieldset>
            </form>
        </section>
    </div>
</x-public-layout>
