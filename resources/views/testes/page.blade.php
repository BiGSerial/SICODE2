<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate PDF</title>

    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Template Main CSS File -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">


</head>

<style>
    body {
        font-family: DejaVu Sans;
    }
</style>

<body>
    {{-- <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Adidas_logo.png/1200px-Adidas_logo.png" /> --}}
    {{-- <img src="{{ asset('img\edp_documento.png') }}" /> --}}
    <div class="container-fluid border border-1">
        <div class="row">
            <div class="col-4">
                <h3>TESTE DE FILA</h3>
            </div>
            <div class="col-8">
                <h3>Segunda etapa</h3>
            </div>
        </div>
        @foreach ($users as $user)
            <h4>{{ $user->name }}</h4>
        @endforeach
    </div>


</body>

<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- Template Main JS File -->
<script src="{{ asset('assets/js/main.js') }}"></script>


</html>
