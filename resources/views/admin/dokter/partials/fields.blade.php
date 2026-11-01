{{-- Isian data dokter yang sama untuk tambah dan ubah. Variabel: $polis, $dokter (boleh null) --}}
<div class="df-grid">
    <div class="df-full">
        <label for="poli_id" class="form-label">Poli tempat bertugas</label>
        <select id="poli_id" name="poli_id" required class="form-select @error('poli_id') is-invalid @enderror">
            <option value="">Pilih poli</option>
            @foreach ($polis as $poli)
                <option value="{{ $poli->id }}" @selected(old('poli_id', $dokter?->poli_id) == $poli->id)>{{ $poli->kode_poli }} - {{ $poli->nama_poli }}</option>
            @endforeach
        </select>
        @error('poli_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="df-full">
        <label for="nama" class="form-label">Nama dokter</label>
        <input id="nama" name="nama" type="text" required maxlength="255" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $dokter?->nama) }}" placeholder="Contoh: dr. Andi Wijaya">
        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div>
        <label for="no_sip" class="form-label">No. SIP <span class="fw-normal text-body-secondary">(opsional)</span></label>
        <input id="no_sip" name="no_sip" type="text" maxlength="255" class="form-control @error('no_sip') is-invalid @enderror" value="{{ old('no_sip', $dokter?->no_sip) }}">
        @error('no_sip')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="df-status">
        <span class="form-label d-block">Status</span>
        <label class="df-toggle">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $dokter?->aktif ?? true))>
            <span class="df-toggle-track"><span></span></span>
            <span>Aktif bertugas</span>
        </label>
    </div>
</div>
