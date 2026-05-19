@extends('layouts.login_new')

<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>

@section('content')
    <div class="container h-100">
        <div class="row h-100 justify-content-center align-items-center">
            <div class=" col-md-5 col-lg-5">
                <div class="card shadow rounded" style="margin-top: 45%">
                    <div class="card-header" style="background-color: #225E66">
                        <div class="row text-center">
                            <img src="{{ asset('img/EDP-Logo-white.svg') }}" class="align-middle" alt=""
                                height="80">
                            <span class="text-white fs-1">sicode <span
                                    class="text-white fs-6">v{{ $version->appver }}</span></span>
                        </div>
                    </div>

                    <div class="card-body">

                        <div wire:loading.remove wire:target="login">
                            <h3 class="card-title text-center mb-3 fw-bold">LOGIN</h3>
                            <form method="POST" action="{{ route('login') }}">
                                @csrf


                                <div class="mb-3">
                                    <label class="form-label">Email:</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password:</label>
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
                                    <button type="submit" class="btn btn-primary w-100 mt-4">Acessar</button>

                                </div>
                            </form>
                        </div>


                    </div>



                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
