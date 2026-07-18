<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>About Us - Teazy</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary-green: #22c55e;
            --accent-green: #16a34a;
            --light-green: #dcfce7;
            --dark-green: #15803d;
            --text-dark: #1f2937;
            --text-medium: #4b5563;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --card-bg: #ffffff;
            --bg-color: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, var(--bg-color) 0%, #f0fdf4 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            padding: 1rem 2rem;
        }

        .navbar-content {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-green);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--text-medium);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--light-green);
            color: var(--dark-green);
            border-color: var(--primary-green);
        }

        .page-hero {
            padding-top: 140px;
            padding-bottom: 60px;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .page-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .page-hero h1 span {
            color: var(--primary-green);
        }

        .page-hero p {
            font-size: 1.125rem;
            color: var(--text-medium);
        }

        .section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 1rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .section-subtitle {
            text-align: center;
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto 3rem auto;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            align-items: start;
        }

        .about-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        .about-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-green);
            margin-bottom: 0.75rem;
        }

        .about-card p {
            color: var(--text-medium);
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .value-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }

        .value-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-green);
        }

        .value-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: var(--light-green);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1rem;
        }

        .value-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .value-desc {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
            border-radius: 1.5rem;
            padding: 4rem 2rem;
            text-align: center;
            color: white;
            margin-bottom: 60px;
        }

        .cta-section h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            opacity: 0.95;
        }

        .cta-section .btn-light {
            background: white;
            color: var(--accent-green);
            padding: 0.875rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .cta-section .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .footer {
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 2rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .footer a {
            color: var(--primary-green);
            text-decoration: none;
            margin: 0 0.5rem;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .page-hero h1 {
                font-size: 2.25rem;
            }

            .navbar {
                padding: 0.75rem 1rem;
            }

            .cta-section h2 {
                font-size: 1.5rem;
            }
        }
    </style>
    @include('layouts.partials.pwa')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="{{ url('/') }}" class="logo">
                <span>🍃</span>
                <span>Teazy</span>
            </a>

            <div class="nav-links">
                <a href="{{ url('/') }}" class="btn btn-secondary">Home</a>
                <a href="{{ route('about.us') }}" class="btn btn-secondary">About Us</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="page-hero">
        <h1>About <span>Teazy</span></h1>
        <p>
            We believe finding the right tea should be effortless, personal, and delightful.
            Teazy was built to help everyone discover teas that match their taste, mood, and wellness goals.
        </p>
    </section>

    <!-- Who We Are -->
    <section class="section">
        <h2 class="section-title">Who We Are</h2>
        <p class="section-subtitle">
            Teazy is a smart tea recommendation platform that blends data-driven insights with a love for tea.
        </p>
        <div class="about-grid">
            <div class="about-card">
                <h3>🌱 Our Story</h3>
                <p>
                    Teazy began with a simple idea: the world of tea is vast, and finding the perfect cup for the right moment can be overwhelming. We set out to create a friendly guide that learns what you like and recommends teas that fit your lifestyle.
                </p>
            </div>
            <div class="about-card">
                <h3>🎯 Our Mission</h3>
                <p>
                    To make tea discovery simple, enjoyable, and meaningful. Whether you want a morning energy boost, a calming evening brew, or a wellness ritual, Teazy helps you find your ideal match in seconds.
                </p>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="section" style="padding-top: 20px;">
        <h2 class="section-title">What We Value</h2>
        <p class="section-subtitle">
            The principles that guide every cup we recommend.
        </p>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🍵</div>
                <div class="value-title">Personalization</div>
                <div class="value-desc">Recommendations tailored to your flavor, caffeine preference, and health goals.</div>
            </div>
            <div class="value-card">
                <div class="value-icon">✨</div>
                <div class="value-title">Quality</div>
                <div class="value-desc">A curated collection of teas with clear flavor, benefit, and caffeine information.</div>
            </div>
            <div class="value-card">
                <div class="value-icon">🤝</div>
                <div class="value-title">Community</div>
                <div class="value-desc">Ratings and reviews from real tea lovers help everyone discover better brews.</div>
            </div>
            <div class="value-card">
                <div class="value-icon">🌿</div>
                <div class="value-title">Wellness</div>
                <div class="value-desc">We highlight teas known for relaxation, energy, digestion, immunity, and more.</div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="section">
        <div class="cta-section">
            <h2>Ready to find your perfect tea?</h2>
            <p>Join the Teazy community and explore teas made for you.</p>
            @auth
                <a href="{{ route('user.dashboard') }}" class="btn-light">Go to Dashboard</a>
            @endauth
            @guest
                <a href="{{ route('register') }}" class="btn-light">Get Started</a>
            @endguest
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>
            <a href="{{ url('/') }}">Home</a>
            <a href="{{ route('about.us') }}">About Us</a>
            @auth
                <a href="{{ route('user.dashboard') }}">Dashboard</a>
            @endauth
            @guest
                @if (Route::has('login'))
                    <a href="{{ route('login') }}">Log in</a>
                @endif
            @endguest
        </p>
        <p style="margin-top: 0.75rem;">🍃 Teazy - Discover Your Perfect Tea Match</p>
    </footer>
</body>
</html>
