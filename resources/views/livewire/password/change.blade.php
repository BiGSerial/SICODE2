<div>
    <section>
        <div class="page-header min-vh-75">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-6 d-flex flex-column mx-auto">
                        <div class="card text-bg-secondary mt-8 border border-1">
                            <div class="card-header bg-transparent pb-0 text-left">
                                <h3 class="font-weight-bolder text-info text-gradient text-center"> <img
                                        src="{{ asset('img/EDP-Logo-white.svg') }}" style="max-height: 45px;"> SICODE</h3>
                                {{-- <h3 class="font-weight-bolder text-info text-gradient">SICODE</h3> --}}

                                <h4 class="edp-text-verde-dark my-3 text-center fw-bold">TROCAR SENHA</h4>

                            </div>
                            <div class="card-body">


                                <label class="text-white">Nova Senha</label>
                                <div class="mb-3">
                                    <input wire:model.defer="password" type="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <label class="text-white">Confirmar Senha</label>
                                <div class="mb-3">
                                    <input wire:model.defer="re_password" type="password" class="form-control"
                                        name="re_password" required>
                                </div>

                                <div class="text-center">

                                    <button wire:click="change_password"
                                        class="btn bg-gradient-info w-100 mt-4">Alterar</button>

                                </div>

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
</div>
