<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <h1 class="logo">My<span>BillApp</span></h1>
                <p>The fastest and most secure way to pay bills, buy airtime, and make online payments.</p>
            </div>
            @php
                $homeUrl = route('home');
            @endphp

            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="{{ $homeUrl }}#home">Home</a></li>
                    <li><a href="{{ $homeUrl }}#services">Services</a></li>
                    <li><a href="{{ $homeUrl }}#how-it-works">How It Works</a></li>
                    <li><a href="{{ $homeUrl }}#testimonials">Testimonials</a></li>
                    {{-- <li><a href="{{ $homeUrl }}#faq">FAQ</a></li> --}}
                </ul>
            </div>
            <div class="footer-services">
                <h3>Our Services</h3>
                <ul>
                    <li><a href="#">Airtime Recharge</a></li>
                    <li><a href="#">Data Bundles</a></li>
                    <li><a href="#">Utility Bills</a></li>
                    <li><a href="#">TV Subscriptions</a></li>
                    <li><a href="#">Education Payments</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>