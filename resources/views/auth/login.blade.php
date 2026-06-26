<x-guest-layout>
    <section class="login-content min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #0B1D3A 0%, #152d4e 50%, #1a3555 100%);">
       <div class="container">
          <div class="row align-items-center justify-content-center">
             <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-lg" style="border-radius: 16px;">
                   <div class="card-body p-4 p-lg-5">
                      <div class="auth-logo text-center mb-4">
                         <a href="{{ route('frontend.index') }}">
                            <img src="{{ asset('landing-images/greylogo.png') }}" class="img-fluid" alt="logo" style="max-height: 48px;">
                         </a>
                      </div>

                      @if (session('success'))
                         <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                         </div>
                      @endif

                      <x-auth-session-status class="mb-4" :status="session('status')" />
                      <x-auth-validation-errors class="mb-4" :errors="$errors" />

                      <h3 class="mb-1 fw-bold text-center" style="color: #0B1D3A;">{{ __('auth.sign_in') }}</h3>
                      <p class="text-center mb-4" style="color: #7A7A7A; font-size: 0.875rem;">{{ __('auth.login_continue') }}</p>

                      <form method="POST" action="{{ route('login') }}" data-bs-toggle="validator">
                         {{ csrf_field() }}
                         <div class="mb-3">
                            <label class="form-label">
                               {{ __('auth.email') }} <span class="text-danger">*</span>
                            </label>
                            <input id="email" name="email" value="{{ request('email') }}"
                                   class="form-control" type="email"
                                   placeholder="{{ __('auth.enter_name', ['name' => __('auth.email')]) }}"
                                   required autofocus>
                            <small class="help-block with-errors text-danger"></small>
                         </div>

                         <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                               <label class="form-label mb-0">
                                  {{ __('auth.login_password') }} <span class="text-danger">*</span>
                               </label>
                            </div>
                            <input class="form-control" type="password" value="{{ request('password') }}"
                                   placeholder="{{ __('auth.enter_name', ['name' => __('auth.login_password')]) }}"
                                   name="password" required autocomplete="current-password">
                            <small class="help-block with-errors text-danger"></small>
                         </div>

                         <div class="mb-4 text-end">
                            <a href="{{ route('auth.recover-password') }}"
                               class="text-decoration-none" style="color: #D4AF37; font-size: 0.875rem; font-weight: 500;">
                               {{ __('auth.forgot_password') }}
                            </a>
                         </div>

                            @if(getSettingValue('demo_login') == 1)
                            <div class="text-center mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm mx-1 demo-login" data-email="demo@admin.com" data-password="12345678">Demo Admin</button>
                                <button type="button" class="btn btn-outline-primary btn-sm mx-1 demo-login" data-email="demo@provider.com" data-password="12345678"> Provider</button>
                                <button type="button" class="btn btn-outline-primary btn-sm mx-1 demo-login" data-email="demo@handyman.com" data-password="12345678"> Handyman</button>
                            </div>
                            @endif
                         <button type="submit" class="btn btn-primary btn-lg w-100 mt-2" style="border-radius: 10px; font-weight: 600;">
                            {{ __('auth.login') }}
                         </button>

                         <div class="text-center mt-4">
                            <label class="m-0 text-capitalize" style="color: #7A7A7A; font-size: 0.875rem;">
                               {{ __('auth.dont_have_account') }}
                            </label>
                            <a href="{{ route('auth.register') }}"
                               class="ms-1 text-decoration-none fw-medium" style="color: #D4AF37; font-size: 0.875rem;">
                               {{ __('auth.signup') }}
                            </a>
                         </div>
                      </form>
                   </div>
                </div>
             </div>
          </div>
       </div>
    </section>

    <script>
    document.querySelectorAll('.demo-login').forEach(button => {
       button.addEventListener('click', function () {
          document.getElementById('email').value = this.getAttribute('data-email');
          document.querySelector('input[name="password"]').value = this.getAttribute('data-password');
       });
    });
 </script>
 </x-guest-layout>
