<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SISEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/sisen.css') }}" rel="stylesheet">
</head>
<body class="auth-page d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="soft-card p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="brand-mark">SN</div>
                    <div>
                        <h1 class="h4 fw-bold mb-0">SISEN</h1>
                        <div class="text-muted">Sistema Empresarial de Nominas</div>
                    </div>
                </div>
                @if ($errors->any())
                    <div class="alert alert-danger">Credenciales invalidas o usuario inactivo.</div>
                @endif
                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contrasena</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Recordar sesion</label>
                    </div>
                    <button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Ingresar</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
