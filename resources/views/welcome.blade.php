@extends('layouts.app')

@section('content')
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content fade-in">
                <h1>Pay Bills & Recharge <span>Instantly</span></h1>
                <p>The fastest and most secure way to pay bills, buy airtime, and make online payments from anywhere, anytime.</p>
                <div class="hero-buttons">
                    <a href="{{ route('register') }}" class="btn btn-primary">Get Started <i class="fas fa-arrow-right"></i></a>
                    <a href="#how-it-works" class="btn btn-secondary">How It Works</a>
                </div>
                {{-- <div class="trusted-by">
                    <p>Trusted by:</p>
                    <div class="trusted-logos">
                        <img src="/placeholder.svg?height=30&width=100" alt="Company 1">
                        <img src="/placeholder.svg?height=30&width=100" alt="Company 2">
                        <img src="/placeholder.svg?height=30&width=100" alt="Company 3">
                    </div>
                </div> --}}
            </div>
            <div class="hero-image fade-in">
                <img src="{{ asset('images/homebanner-image.png') }}" alt="{{ config('app.name') }} Mobile App">
                <div class="floating-card card-1">
                    <i class="fas fa-check-circle"></i>
                    <p>Instant Recharge</p>
                </div>
                <div class="floating-card card-2">
                    <i class="fas fa-shield-alt"></i>
                    <p>Secure Payments</p>
                </div>
                <div class="floating-card card-3">
                    <i class="fas fa-bolt"></i>
                    <p>Lightning Fast</p>
                </div>
            </div>
        </div>
        <div class="wave-divider">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#fff8e1" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </section>

    <section id="services" class="services">
        <div class="container">
            <div class="section-header slide-up">
                <h2>Our Services</h2>
                <p>Everything you need in one place</p>
            </div>
            <div class="services-grid">
                <div class="service-card slide-up">
                    <div class="service-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Airtime Recharge</h3>
                    <p>Instantly recharge airtime for all networks at the best rates with zero hidden charges.</p>
                    <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card slide-up" data-delay="200">
                    <div class="service-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Data Bundles</h3>
                    <p>Purchase data bundles for all networks at discounted rates and stay connected always.</p>
                    <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card slide-up" data-delay="400">
                    <div class="service-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Utility Bills</h3>
                    <p>Pay electricity, water, and other utility bills conveniently without leaving your home.</p>
                    <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="service-card slide-up" data-delay="600">
                    <div class="service-icon">
                        <i class="fas fa-tv"></i>
                    </div>
                    <h3>TV Subscriptions</h3>
                    <p>Renew your cable TV subscriptions in seconds and never miss your favorite shows.</p>
                    <a href="#" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-header slide-up">
                <h2>How It Works</h2>
                <p>Simple, fast, and secure in just 3 steps</p>
            </div>
            <div class="steps">
                <div class="step slide-up">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Create an Account</h3>
                        <p>Sign up in seconds with just your email and phone number. No lengthy forms.</p>
                    </div>
                    <div class="step-image">
                        <img src="{{ asset('images/register.jpg') }}" alt="Create Account" style="max-width: 400px; width: 100%; display: block; margin: 0 auto;" />
                    </div>
                </div>
                <div class="step slide-up" data-delay="200">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Select a Service</h3>
                        <p>Choose from our wide range of services including airtime, data, bills, and more.</p>
                    </div>
                    <div class="step-image">
                        <img src="{{ asset('images/select-service.jpg') }}" alt="Select Service" style="max-width: 400px; width: 100%; display: block; margin: 0 auto;" >
                    </div>
                </div>
                <div class="step slide-up" data-delay="400">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Make Payment</h3>
                        <p>Complete your transaction securely using any payment method of your choice.</p>
                    </div>
                    <div class="step-image">
                        <img src="{{ asset('images/make-payment.jpg') }}" alt="Make Payment" style="max-width: 400px; width: 100%; display: block; margin: 0 auto;" >
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="stats" class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card fade-in">
                    <h3><span class="counter" data-target="120">0</span>+</h3>
                    <p>Happy Customers</p>
                </div>
                <div class="stat-card fade-in" data-delay="200">
                    <h3><span class="counter" data-target="350">0</span>+</h3>
                    <p>Transactions</p>
                </div>
                <div class="stat-card fade-in" data-delay="400">
                    <h3><span class="counter" data-target="99">0</span>%</h3>
                    <p>Success Rate</p>
                </div>
                <div class="stat-card fade-in" data-delay="600">
                    <h3><span class="counter" data-target="24">0</span>/7</h3>
                    <p>Customer Support</p>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonials" class="testimonials">
        <div class="wave-divider top">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#fff8e1" fill-opacity="1" d="M0,160L48,170.7C96,181,192,203,288,202.7C384,203,480,181,576,165.3C672,149,768,139,864,154.7C960,171,1056,213,1152,218.7C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        <div class="container">
            <div class="section-header slide-up">
                <h2>What Our Customers Say</h2>
                <p>Don't just take our word for it</p>
            </div>
            <div class="testimonial-slider">
                <div class="testimonial-card slide-up">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left"></i>
                        <p>{{ config('app.name') }} has made my life so much easier. I no longer have to queue at the bank to pay my bills. Everything is done in seconds!</p>
                    </div>
                    <div class="testimonial-author">
                        <div>
                            <h4>Sarah Johnson</h4>
                            <p>Regular Customer</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card slide-up" data-delay="200">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left"></i>
                        <p>The discounts on data bundles are amazing! I save a lot of money every month using {{ config('app.name') }} for all my internet needs.</p>
                    </div>
                    <div class="testimonial-author">
                        <div>
                            <h4>Michael Chen</h4>
                            <p>Business Owner</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card slide-up" data-delay="400">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left"></i>
                        <p>Customer service is top-notch! Had an issue with a transaction and it was resolved within minutes. Highly recommend!</p>
                    </div>
                    <div class="testimonial-author">
                        <div>
                            <h4>Aisha Mohammed</h4>
                            <p>Student</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
        <div class="wave-divider bottom">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#fff8e1" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path>
            </svg>
        </div>
    </section>

    {{-- <section id="app-download" class="app-download">
        <div class="container">
            <div class="app-content slide-up">
                <h2>Download Our Mobile App</h2>
                <p>Experience even faster payments and exclusive mobile-only discounts with our app.</p>
                <div class="app-buttons">
                    <a href="#" class="app-button">
                        <i class="fab fa-google-play"></i>
                        <div>
                            <span>GET IT ON</span>
                            <strong>Google Play</strong>
                        </div>
                    </a>
                    <a href="#" class="app-button">
                        <i class="fab fa-apple"></i>
                        <div>
                            <span>Download on the</span>
                            <strong>App Store</strong>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image slide-up" data-delay="200">
                <img src="/placeholder.svg?height=400&width=300" alt="{{ config('app.name') }} Mobile App">
            </div>
        </div>
    </section> --}}

    <section id="cta" class="cta">
        <div class="container">
            <div class="cta-content fade-in">
                <h2>Ready to Experience Hassle-Free Payments?</h2>
                <p>Join over 500,000 satisfied customers who trust {{ config('app.name') }} for their daily payment needs.</p>
                <a href="{{ route('register') }}" class="btn btn-primary">Get Started Now <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info slide-up">
                    <h2>Contact Us</h2>
                    <p>We're here to help with any questions or concerns</p>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h3>Phone</h3>
                            {{-- config('services.flutterwave.live_secret_key') --}}
                            <p>{{ config('services.info.CONTACT_PHONE') }}</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>support@mybillapp.com</p>
                        </div>
                    </div>
                    {{-- <div class="social-links">
                        <a href="{{ env('FACEBOOK_URL') }}"><i class="fab fa-facebook-f"></i></a>
                        <a href="{{ env('TWITTER_URL') }}"><i class="fab fa-twitter"></i></a>
                        <a href="{{ env('INSTAGRAM_URL') }}"><i class="fab fa-instagram"></i></a>
                        <a href="{{ env('LINKED_URL') }}"><i class="fab fa-linkedin-in"></i></a>
                    </div> --}}
                </div>
            </div>
        </div>
    </section>
@endsection