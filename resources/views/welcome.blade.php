@extends('layouts.auth')

@section('main-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h2 text-gray-900 mb-2">{{ config('app.name', 'Laravel') }}</h1>
                                    <div class="mb-4">
                                        <i class="fas fa-laugh-wink fa-3x text-primary rotate-n-15 mb-3"></i>
                                        <p>Selamat datang di aplikasi kami yang dibangun dengan Laravel dan SB Admin 2</p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="text-center mb-4">
                                    <a href="{{ route('login') }}" class="btn btn-primary btn-user btn-block">
                                        <i class="fas fa-sign-in-alt fa-fw"></i> Login
                                    </a>
                                </div>
                                
                                @if (Route::has('register'))
                                <div class="text-center mb-4">
                                    <a href="{{ route('register') }}" class="btn btn-success btn-user btn-block">
                                        <i class="fas fa-user-plus fa-fw"></i> Register
                                    </a>
                                </div>
                                @endif
                                
                                <div class="text-center mt-3">
                                    <div class="card border-left-warning shadow py-2 mb-4">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Fitur Unggulan</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">SB Admin 2 + Laravel</div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-star fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-500">Dibuat dengan <i class="fas fa-heart text-danger"></i> oleh Developer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.zoom-on-hover {
    transition: transform .3s;
}
.zoom-on-hover:hover {
    transform: scale(1.05);
}
.rotate-n-15 {
    transform: rotate(-15deg);
}
</style>
@endsection