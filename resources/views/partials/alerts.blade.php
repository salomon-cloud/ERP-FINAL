@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger no-print">
        <i class="bi bi-exclamation-triangle me-2"></i>Revisa los campos marcados.
    </div>
@endif
