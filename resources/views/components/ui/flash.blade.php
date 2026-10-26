@if (session('success'))
    <div role="status" class="alert alert-ok mb-5">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div role="alert" class="alert alert-bad mb-5">{{ session('error') }}</div>
@endif
