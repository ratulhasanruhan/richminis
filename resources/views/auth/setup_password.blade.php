@extends('auth.layouts.authentication')

@section('content')
    <div class="aiz-main-wrapper d-flex flex-column justify-content-center bg-white">
        <section class="bg-white overflow-hidden" style="min-height:100vh;">
            <div class="row no-gutters" style="min-height: 100vh;">
                <div class="col-xxl-9 col-lg-8">
                    <div class="h-100" style="max-height: 100vh">
                        <img src="{{ uploaded_asset(get_setting('password_reset_page_image')) }}" alt="{{ translate('Password Reset Page Image') }}" class="img-fit h-100">
                    </div>
                </div>

                <div class="col-xxl-3 col-lg-4">
                    <div class="d-flex align-items-center right-content">
                        <div class="py-3 py-lg-4 px-3 px-xl-5 flex-grow-1">
                            <div class="size-48px mb-3 mx-auto mx-lg-0">
                                <img src="{{ uploaded_asset(get_setting('site_icon')) }}" alt="{{ translate('Site Icon')}}" class="img-fit h-100">
                            </div>

                            <div class="text-center text-lg-left">
                                <h1 class="fs-20 fs-md-20 fw-700 text-primary" style="text-transform: uppercase;">{{ translate('Set Your Password') }}</h1>
                                <h5 class="fs-14 fw-400 text-dark">
                                    {{ translate('Choose a password for your new account.') }}
                                </h5>
                            </div>

                            <div class="pt-3 pt-lg-4">
                                <form class="form-default" role="form" action="{{ route('account.setup-password.update') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $token }}">

                                    <div class="form-group">
                                        <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" readonly placeholder="{{ translate('Email') }}" required>
                                        @if ($errors->has('email'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('email') }}</strong>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{ translate('New Password') }}" required autofocus>
                                        @if ($errors->has('password'))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first('password') }}</strong>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="{{ translate('Confirm Password') }}" required>
                                    </div>

                                    <div class="mb-4 mt-4">
                                        <button type="submit" class="btn btn-primary btn-block fw-700 fs-14 rounded-0">{{ translate('Set Password') }}</button>
                                    </div>
                                </form>

                                <a href="{{ route('user.login') }}" class="mt-3 fs-14 fw-700 d-flex align-items-center text-primary" style="max-width: fit-content;">
                                    <i class="las la-arrow-left fs-20 mr-1"></i>
                                    {{ translate('Back to login') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
