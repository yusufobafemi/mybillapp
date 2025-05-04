<header>
    <nav class="container">
        <div class="logo">
            <h1>My<span>BillApp</span></h1>
        </div>
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <ul class="nav-menu">
            <li><a href="#home">Home</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#testimonials">Testimonials</a></li>
            <li><a href="#contact" class="btn btn-outline">Contact Us</a></li>
            @auth
                <li>
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