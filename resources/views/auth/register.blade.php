@extends('layouts.app')

@section('title', 'register')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/register-styles.css') }}">
@endsection

@section('content')
<div class="register-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <div class="register-form-container">
                        <div class="text-center mb-4">
                            <p class="mt-2 text-muted">Create your account</p>
                        </div>
                        
                        <!-- Google Register Button -->
                        <div class="social-login mb-4">
                            <button class="btn btn-google w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                Sign up with Google
                            </button>
                        </div>
                        
                        <div class="divider">
                            <span>or register with email</span>
                        </div>
                        
                        <!-- Register Form -->
                        <form id="registerForm" method="POST">
                            @csrf
                        
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Your full name" required>
                                </div>
                            </div>
                        
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                                </div>
                            </div>
                        
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                                </div>
                            </div>
                        
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" placeholder="Confirm your password" required>
                                </div>
                            </div>
                        
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-warning">Create Account</button>
                            </div>
                        </form>                                 
                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? <a href="{{ route("login") }}" class="login-link">Sign In</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const routes = {
        register: "{{ route('register.submit') }}",
    };
</script>
<script src="{{ mix('js/register-script.js') }}" defer></script>
@endsection