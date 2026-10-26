@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Data belum bisa disimpan. Periksa isian berikut:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
