<div>
    @push('css')
        <style>
            .my-bread-box {
                position: fixed;
                bottom: 10px;
                right: 60px;
                width: 220px;
                padding: 10px;
                background-color: #143f47;
                border-radius: 5px;
                z-index: 1;
            }

            .my-bread-box h5,
            .my-bread-box h4,
            .my-bread-box h3,
            .my-bread-box h2 {
                font-size: 20px;
                font-weight: bold;
                color: #28FF52;
            }

            .my-bread-box span {
                font-size: 30px;
                font-weight: bold;
                color: #28FF52;
            }

            .my-bread-box span i {
                font-size: 40px;
                font-weight: bold;
                color: #ffffff;
            }
        </style>
    @endpush

    @if ($count)
        <div class="my-bread-box shadow">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-4 align-middle">
                        <span class="mx-auto my-auto"><i class="ri-checkbox-line align-middle"></i></span>
                    </div>
                    <div class="col-8 text-center">
                        <span class="text-center">{{ $count }}</span>
                        <h5 class="text-center">{{ $count == 1 ? 'Selecionado' : 'Selecionados' }}</h5>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
