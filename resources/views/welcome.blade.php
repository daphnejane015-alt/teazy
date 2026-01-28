<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Teazy - Discover Your Perfect Tea</title>
    
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
        
        .hero {
            padding-top: 120px;
            padding-bottom: 60px;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .hero-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-title span {
            color: var(--primary-green);
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-medium);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1rem;
        }
        
        .features {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 1rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-green);
        }
        
        .feature-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: var(--light-green);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .feature-desc {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
            flex-grow: 1;
        }
        
        .feature-arrow {
            margin-top: 1rem;
            color: var(--primary-green);
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .stats {
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 40px 1rem;
            margin-top: 40px;
        }
        
        .stats-grid {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-green);
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .footer {
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 2rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-large {
                width: 100%;
                max-width: 280px;
            }
        }
    </style>
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
                @auth
                    <a href="{{ route('user.dashboard') }}" class="btn btn-secondary">Dashboard</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-icon">🍵</div>
        <h1 class="hero-title">
            Discover Your <span>Perfect Tea</span>
        </h1>
        <p class="hero-subtitle">
            Teazy helps you find the ideal tea based on your mood, preferences, and health goals. 
            Explore our curated collection of teas with smart recommendations.
        </p>
        @guest
        <div class="hero-cta">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="btn btn-secondary btn-large">Log in</a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-primary btn-large">Register</a>
            @endif
        </div>
        @endguest
        @auth
        <div class="hero-cta">
            <a href="{{ route('user.dashboard') }}" class="btn btn-primary btn-large">Go to Dashboard</a>
        </div>
        @endauth
    </section>

    <!-- Features Grid -->
    <section class="features">
        <div class="features-grid">
            <a href="{{ route('find.tea') }}" class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3 class="feature-title">Find Tea</h3>
                <p class="feature-desc">
                    Search through our extensive collection by flavor, caffeine level, benefits, or name to find your perfect match.
                </p>
                <div class="feature-arrow">
                    Explore teas 
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <a href="{{ route('top.tea') }}" class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3 class="feature-title">Top Rated</h3>
                <p class="feature-desc">
                    Discover the highest-rated teas by our community. See what's trending and highly recommended.
                </p>
                <div class="feature-arrow">
                    View top teas
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <a href="{{ route('recommendations') }}" class="feature-card">
                <div class="feature-icon">💡</div>
                <h3 class="feature-title">Smart Recommendations</h3>
                <p class="feature-desc">
                    Get personalized tea suggestions based on your preferences, ratings, and favorite flavors.
                </p>
                <div class="feature-arrow">
                    Get recommendations
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>

            <a href="{{ route('rated.tea') }}" class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3 class="feature-title">Rate & Review</h3>
                <p class="feature-desc">
                    Rate teas you've tried and build your personal taste profile for better recommendations.
                </p>
                <div class="feature-arrow">
                    Your ratings
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">{{ \App\Models\Tea::count() }}+</div>
                <div class="stat-label">Teas Available</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ \App\Models\Rating::count() }}+</div>
                <div class="stat-label">Ratings Given</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ \App\Models\User::count() }}+</div>
                <div class="stat-label">Happy Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50+</div>
                <div class="stat-label">Tea Varieties</div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>🍃 Teazy - Discover Your Perfect Tea Match</p>
        <p style="margin-top: 0.5rem; font-size: 0.75rem;">
            Laravel v{{ Illuminate\Foundation\Application::VERSION }} | PHP v{{ PHP_VERSION }}
        </p>
    </footer>
</body>
</html>
