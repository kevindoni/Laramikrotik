@extends('layouts.admin')

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
                                    <h1 class="h3 mb-4 text-gray-800">{{ __('About') }}</h1>
                                </div>

                                <div class="card-profile-image mt-4 text-center">
                                    <img src="{{ asset('img/favicon.png') }}" class="rounded-circle" alt="user-image" style="width: 100px; height: 100px;">
                                </div>

                                <div class="mt-4">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h5 class="font-weight-bold">Laravel SB Admin 2</h5>
                                            <p>SB Admin 2 for Laravel.</p>
                                            <p>Recommend to install this preset on a project that you are starting from scratch, otherwise your project's design might break.</p>
                                            <p>If you found this project useful, then please consider giving it a ⭐</p>
                                            <a href="https://github.com/aleckrh/laravel-sb-admin-2" target="_blank" class="btn btn-github btn-block">
                                                <i class="fab fa-github fa-fw"></i> Go to repository
                                            </a>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <h5 class="font-weight-bold">Credits</h5>
                                            <p>Laravel SB Admin 2 uses some open-source third-party libraries/packages, many thanks to the web community.</p>
                                            <ul>
                                                <li><a href="https://laravel.com" target="_blank">Laravel</a> - Open source framework.</li>
                                                <li><a href="https://github.com/DevMarketer/LaravelEasyNav" target="_blank">LaravelEasyNav</a> - Making managing navigation in Laravel easy.</li>
                                                <li><a href="https://startbootstrap.com/themes/sb-admin-2" target="_blank">SB Admin 2</a> - Thanks to Start Bootstrap.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                
                                <div class="text-center">
                                    <a href="{{ url('/') }}" class="btn btn-primary">
                                        <i class="fas fa-arrow-left fa-fw"></i> Back to Home
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection