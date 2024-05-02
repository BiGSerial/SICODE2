@extends('layouts.login_new')

@section('content')
    <section>
        <div class="page-header min-vh-75">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-6 d-flex flex-column mx-auto">
                        <div class="card card-plain mt-8 border border-1">
                            <div class="card-header bg-transparent pb-0 text-left">
                                <h3 class="font-weight-bolder text-info text-gradient text-center"> <img
                                        src="{{ asset('img/EDP-Logo-white.svg') }}" style="max-height: 45px;"> SICODE</h3>
                                {{-- <h3 class="font-weight-bolder text-info text-gradient">SICODE</h3> --}}
                                <p class="mb-0 edp-text-verde-dark text-center">Entre com seu Email e Senha para Acessar</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('login.login') }}">
                                    @csrf


                                    <div class="mb-3">
                                        <label>Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            name="email" value="{{ old('email') }}" required autocomplete="email"
                                            autofocus>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-white">Password</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                            name="password" required autocomplete="current-password">
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label edp-text-verde-dark" for="remember">
                                            {{ __('Lembrar-me') }}
                                        </label>
                                    </div>

                                    <div class="text-center">
                                        {{-- <button type="button" class="btn bg-edp-verde text-secondary w-100 mt-4 mb-0">Acessar</button> --}}
                                        <button type="submit" class="btn bg-gradient-info w-100 mt-4">Acessar</button>

                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="oblique position-absolute h-100 d-md-block d-none me-n8 top-0">
                            {{-- <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6" style="background-image:url('{{ asset('img/curved-images/curved6.jpg') }}')"></div> --}}
                            <div class="oblique-image position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6 bg-cover"
                                style="background-image:url('{{ asset('img/edp-img/Changing-Tomorrow-Now-EDP-foto.jpeg') }}')">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
