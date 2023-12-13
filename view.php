@extends('layouts.dashboard', [
    'overrideApp' => true,
])

@section('title', __('Tembak Paket TRI') . ' - ')

@section('content')
    <x-content.header :title="__('Tembak Paket XL')">
        <x-slot name="breadcrumb">
            <x-breadcrumb.item :active="true">{{ __('Tembak Paket XL') }}</x-breadcrumb.item>
        </x-slot>
    </x-content.header>

    <x-content.main>
        <x-verify-email-notice />
        <x-auto.alert />
        <x-auto.error />
        <x-banner />

        <x-card type="card-primary" :title="__('Tembak Paket XL')">



            <p><b>PETUNJUK:</b><br/>
                1. Masukkan nomor XL kamu dengan format awalan 628xx lalu klik minta kode OTP
                <br/>2. masukkan kode OTP ke inputan yang ada,
                <br/>3. Terakhir klik Login dan pilih paket yang ingin kamu beli.</p>



            <div class="row">
                <div id="Detail" name="Detail" style="display: none;">

                </div>

                <br/>
                <div class="col-sm-6" id="noTujuan">
                    <div class="form-group">
                        <label>{{ __('Nomer Phone') }}</label>
                        <input class="form-control" name="nomor" type="number" maxlength="20"
                               value="" placeholder="{{ __('No Tujuan ex: 089869696969') }}">
                    </div>
                </div>

                <div class="col-sm-6" id="otpGroup">
                    <div class="form-group">
                        <label>{{ __('OTP') }}</label>
                        <input class="form-control" id="otp" name="otp" type="text" maxlength="20"
                               value="" placeholder="{{ __('No OTP ex: 325SDM') }}">
                        <small style="text-decoration: underline; cursor: pointer; color: blue;" id="mintaOTP"><b>Minta Kode OTP</b></small>
                    </div>
                </div>


                <div class="col-sm-6">
                    <label for="produk">Pilih Produk:</label>

                    <select name="produk" id="produk" class="form-control">
                        <option value="">- Pilih Produk -</option>
                        <option value="1937582">Chatting Unlimited (WA,Line,Wechat)/30 hari Rp6000</option>
                        <option value="806547">2.5GB+25 Menit/30 hari Rp3000</option>
                        <option value="28892">Promo 5GB/7 hari Rp5000</option>
                        <option value="28894">Promo 7GB (1GB/Hari)/7 hari Rp10000</option>
                        <option value="28897">Promo 30GB (1GB/Hari)/30 hari Rp25000</option>
                        <option value="28899">Promo 60GB (2GB/Hari)/30 hari Rp35000</option>
                        <option value="30474">Promo 65GB /30 hari Rp100000</option>
                        <option value="30475">Promo 90GB /30 hari Rp130000</option>
                    </select>
                </div>

                <input type="hidden" class="form-control" name="auth_id" id="auth_id" value="">
                <input type="hidden" class="form-control" name="token" id="token" value="">
                <input type="hidden" class="form-control" name="access_token" id="access_token" value="">


            </div>
            <br/>


            <button id="submit" class="btn btn-primary">{{ __('Login') }}</button>

            <button type="button" name="Profil" id="Profil" class="btn btn-primary" style="display: none;">
                Cek Profil
            </button>

            <button type="button" name="cekPaket" id="cekPaket" class="btn btn-primary" style="display: none;">
                Cek Paket Tersedia
            </button>
            <button type="button" name="submitOrder" id="submitOrder" class="btn btn-primary" style="display: none;">
                <i class="fa fa-shopping-cart" aria-hidden="true"></i> Beli Paket
            </button>



        </x-card>



        <x-banner />

    </x-content.main>
@endsection
@push('header-scripts')
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" rel="stylesheet">

@endpush


@push('footer-parent-scripts')
    <script>


        $(document).ready(function(){
            var time = 60;
            var timer = true;
            var tombolAnda = document.getElementById("mintaOTP");

            $("#mintaOTP").click(function(){
                var nomor = $("input[name='nomor']").val();

                if(nomor == ''){
                    Swal.fire({
                        title: "Phone Phone Kosong!",
                        text: "silahkan masukkan nomer phone anda",
                        icon: "warning"
                    });
                }else{
                    $.ajax(
                        {
                            url: "/produk/xl/otp",
                            type: "POST",
                            dataType: 'json',
                            data: {
                                _token: "{{ csrf_token() }}",
                                nomor: nomor
                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Tunggu sebentar',
                                    allowOutsideClick: false
                                })
                                Swal.showLoading();
                            },
                            success: function(data){
                                console.log(data);
                                if(timer){
                                    timer = false;
                                    var interval = setInterval(function(){
                                        time--;
                                        tombolAnda.hidden = true;
                                        tombolAnda.innerHTML = "Kirim Ulang OTP ("+time+")";
                                        if(time == 0){
                                            clearInterval(interval);
                                            tombolAnda.hidden = false;
                                            tombolAnda.innerHTML = "Kirim Ulang OTP";
                                            timer = true;
                                            time = 60;
                                        }
                                    }, 1000);
                                }
                                // jika status true maka tampilkan pesan sukses

                                if (data.status == true) {
                                    Swal.fire({
                                        title: data.message,
                                        text: "Kode OTP berhasil dikirim ke nomor " + nomor,
                                        icon: "success"
                                    });

                                    $("#auth_id").val(data.auth_id);

                                }else{
                                    Swal.fire({
                                        title: data.message,
                                        text: "Kode OTP GAGAL dikirim ke nomor " +nomor,
                                        icon: "error"
                                    });
                                }

                            },
                            error: function(data){
                                console.log(data);
                                Swal.fire({
                                    title: "Gagal!",
                                    text: "Kode OTP gagal dikirim ke nomor " +nomor,
                                    icon: "error"
                                });
                            }
                        });

                }
            });

            // fungsi login
            $("#submit").click(function(){
                let nomor = $("input[name='nomor']").val();
                let otp = $("input[name='otp']").val();
                let auth_id = $("input[name='auth_id']").val();
                // console.log(nomor, otp, auth_id);

                if(nomor == '' || otp == '') {
                    Swal.fire({
                        title: "Gagal!",
                        text: "silahkan masukan nomor dan otp",
                        icon: "warning"
                    });

                }
                if(auth_id == '')
                {
                    Swal.fire({
                        title: "Gagal!",
                        text: "silahkan minta otp terlebih dahulu",
                        icon: "warning"
                    });
                }else{
                    $.ajax(
                        {
                            url: "/produk/xl/login",
                            type: "POST",
                            dataType: 'json',
                            data: {
                                _token: "{{ csrf_token() }}",
                                otp: otp,
                                nomor: nomor,
                                auth_id: auth_id

                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Tunggu sebentar',
                                    allowOutsideClick: false
                                })
                                Swal.showLoading();
                            },
                            success: function(data){
                                    console.log(data);

                                    // let token = data.
                                    //
                                    // if(data.status == false) {
                                    //     Swal.fire({
                                    //         title: "Gagal Login ke Nomer " + nomor,
                                    //         text: data.message,
                                    //         icon: "error"
                                    //     });
                                    //
                                    // }else{
                                    //     Swal.fire({
                                    //         title: data.message,
                                    //         text: "berhasil login ke nomor " + nomor,
                                    //         icon: "success"
                                    //     });
                                    //
                                    //     $("#token").val(data.data.token);
                                    //     console.log(data.token);
                                    //
                                    //     $('#noTujuan').hide();
                                    //     $('#otpGroup').hide();
                                    //     $("#submit").hide();
                                    //
                                    //     $('#Profil').show();
                                    //     $('#cekPaket').show();
                                    //     $('#submitOrder').show();
                                    // }

                                //     $('#Detail').show();
                                //
                                //     $('#Detail').html('Detail Akun:<br/><b>Phone : </b>' + data.msisdn +
                                //         '<br/><b>Pulsa : </b>' + data.pulsa +
                                //         '<br/><b>kouta : </b>' + data.kouta +
                                //         '<br/><b>masa aktif : </b>' + data.masaaktif +
                                //         '<br/><b>Point : </b>' + data.point +
                                //         '<br/><b>Tipe : </b>' + data.subscriberType
                                //     );
                                //
                                // }else{
                                //     Swal.fire({
                                //         title: "Gagal Login ke Nomer " + nomor,
                                //         text: data.message,
                                //         icon: "error"
                                //     });
                                // }

                            },
                            error: function(data){
                                console.log(data);
                                Swal.fire({
                                    title: "Gagal!",
                                    text: "error",
                                    icon: "error"
                                });
                            }
                        });

                }
            });


            // cek paket internet tersedia
            $("#cekPaket").click(function(){
                var nomor = $("input[name='nomor']").val();
                var produk = $("#produk").val();
                var secretKey = $("#secretKey").val();
                // console.log(nomor, produk, secretKey);

                if(nomor == '' || produk == '') {
                    Swal.fire({
                        title: "Gagal!",
                        text: "silahkan masukan nomor dan produk",
                        icon: "warning"
                    });

                    if(secretKey == ''){
                        Swal.fire({
                            title: "Gagal!",
                            text: "silahkan login terlebih dahulu",
                            icon: "warning"
                        });
                    }

                }else{
                    $.ajax(
                        {
                            url: "/produk/tri/paket",
                            type: "POST",
                            dataType: 'json',
                            data: {
                                _token: "{{ csrf_token() }}",
                                secretKey: secretKey,
                                produk: produk,
                                nomor: nomor

                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Tunggu sebentar',
                                    allowOutsideClick: false
                                })
                                Swal.showLoading();
                            },
                            success: function(data){

                                // console.log(data);

                                if(data.status == false){
                                    Swal.fire({
                                        title: "Paket EROR!",
                                        text: "Paket tidak tersedia untuk nomer " + nomor,
                                        icon: "error"
                                    });
                                }else {
                                    var productName = data.product.productName;
                                    var productDescription = data.product.productDescription;

                                    Swal.fire({
                                        title: "Paket " + productName + " Tersedia!",
                                        text: productDescription,
                                        icon: "success"
                                    });
                                }


                            },
                            error: function(data){
                                console.log(data);
                                Swal.fire({
                                    title: "Gagal!",
                                    text: "error",
                                    icon: "error"
                                });
                            }
                        });

                }
            });

            // beli paket internet
            // cek paket internet tersedia
            $("#submitOrder").click(function(){
                var nomor = $("input[name='nomor']").val();
                var produk = $("#produk").val();
                var secretKey = $("#secretKey").val();
                // console.log(nomor, produk, secretKey);

                if(nomor == '' || produk == '') {
                    Swal.fire({
                        title: "Gagal!",
                        text: "silahkan masukan nomor dan produk",
                        icon: "warning"
                    });

                    if(secretKey == ''){
                        Swal.fire({
                            title: "Gagal!",
                            text: "silahkan login terlebih dahulu",
                            icon: "warning"
                        });
                    }

                }else{
                    $.ajax(
                        {
                            url: "/produk/tri/buy",
                            type: "POST",
                            dataType: 'json',
                            data: {
                                _token: "{{ csrf_token() }}",
                                secretKey: secretKey,
                                produk: produk,
                                nomor: nomor

                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Tunggu sebentar',
                                    allowOutsideClick: false
                                })
                                Swal.showLoading();
                            },
                            success: function(data){

                                // console.log(data);
                                if(data.isDdcUrl == true) {
                                    var ddcUrl = data.ddcUrl;
                                    var trxId = data.trxId;

                                    Swal.fire({
                                        title: "Berhasil!",
                                        text: "Paket berhasil dibeli dengan No Transaksi " + trxId,
                                        icon: "success"
                                    }).then(function () {
                                        window.location.reload()
                                    });

                                    // swal.fire("Berhasil!", data.message, "success").then(function () {
                                    //     window.location.reload()
                                    // });
                                }else{
                                    Swal.fire({
                                        title: "Gagal!",
                                        text: "Paket gagal dibeli",
                                        icon: "error"
                                    });
                                }

                            },
                            error: function(data){
                                console.log(data);
                                Swal.fire({
                                    title: "Gagal!",
                                    text: "error",
                                    icon: "error"
                                });
                            }
                        });

                }
            });


        });



    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
@endpush
