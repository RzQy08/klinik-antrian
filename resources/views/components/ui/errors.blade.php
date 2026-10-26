@if ($errors->any())
    <div role="alert" class="alert alert-bad mb-5">
        <p class="font-semibold">Data belum bisa disimpan. Periksa isian berikut:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
