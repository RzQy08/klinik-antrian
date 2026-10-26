@if (session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2" role="status">
        <i class="bi bi-check-circle-fill mt-1"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif
