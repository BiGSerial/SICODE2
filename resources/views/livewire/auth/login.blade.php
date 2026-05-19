<?php
$version = (object) json_decode(file_get_contents(base_path('appver.json')));
?>
<div>
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
                        <!-- Conteúdo do formulário de login -->
                        @if ($this->show == 0)
                            <div wire:loading.remove wire:target="login">
                                <h3 class="card-title text-center mb-3 fw-bold">LOGIN</h3>
                                <form wire:submit.prevent="login" autocomplete="on">

                                    <div class="form-group mb-3">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control  @error('email') is-invalid @enderror"
                                            id="email" name="email" wire:model.defer="email"
                                            value="{{ old('email') }}" required autocomplete="email" autofocus>
                                        @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="password">Senha</label>
                                        <input id="password" type="password" id="password" name="password"
                                            class="form-control  @error('email') is-invalid @enderror"
                                            wire:model.defer="password" required autocomplete="current-password">
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" role="switch" id="remember"
                                            wire:model.defer="remember">
                                        <label class="form-check-label" for="flexSwitchCheckChecked">Lembrar-me</label>
                                    </div>

                                    <div class="form-group mb-0 justify-content-center align-items-center">
                                        <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                                    </div>
                                </form>
                            </div>
                        @elseif ($this->show == 1)
                            <div class="text-center fw-bold fs-4 mt-5">{{ $msg }}</div>
                        @endif
                        <div wire:loading wire:target="login" class="my-5 py-5">
                            <div class="clear-fix text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="spinner-border text-primary align-self-center me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="fw-bold">EFETUANDO LOGIN</div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
