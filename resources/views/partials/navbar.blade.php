<header>
    <nav class="container">
        <div class="logo">
            <h1>My<span>BillApp</span></h1>
        </div>
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <ul class="nav-menu">
            @if (Route::currentRouteName() == 'home')
                <li><a href="#home">Home</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
            @else
                <li><a href="{{ route('home') }}">Home</a></li>
            @endif

            @auth
                <li>
                    <li><a href="{{ route('dashboard') }}" class="btn btn-outline">Dashboard</a></li>
                    <a href="{{ route('logout') }}" class="btn btn-danger">Logout</a>
                </li>
            @endauth

            @guest
                <li>
                    @if (Route::currentRouteName() == 'login')
                        <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
                    @elseif (Route::currentRouteName() == 'register')
                        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                    @endif
                </li>
            @endguest
        </ul>
    </nav>
</header>