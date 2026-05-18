<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>
<div>
    <div class="container h-100">
        <div class="row h-100 justify-content-center align-items-center">
            <div class=" col-md-5 col-lg-5">
                <div class="card shadow rounded" style="margin-top: 45%; background-color: #43767D">
                    <div class="card-header" style="background-color: #225E66">
                        <div class="row text-center">
                            <img src="{{ asset('img/EDP-Logo-white.svg') }}" class="align-middle" alt=""
                                height="80">
                            <span class="text-white fs-1">sicode <span
                                    class="text-white fs-6">v{{ $version->appver }}</span></span>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="card-body">
                            <h3 class="card-title text-white text-center mb-3 fw-bold">ALTERAR SENHA</h3>
                            <div class="mb-3">
                                <label class="text-white">Nova Senha</label>
                                <input wire:model.defer="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="text-white">Confirmar Senha</label>
                                <input wire:model.defer="re_password" type="password" class="form-control"
                                    name="re_password" required>
                            </div>

                            <div class="text-center">

                                <button wire:click="change_password"
                                    class="btn btn-primary w-100 mt-4 shadow">Alterar</button>

                            </div>

                        </div>
                    </div>
                </div </div </div </div </div
