<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Teazy - {{ __('Authentication') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        
        <!-- Custom Styles -->
        <style>
            :root {
                --primary-green: #10b981;
                --accent-green: #059669;
                --light-green: #d1fae5;
                --cream-green: #ecfdf5;
                --text-dark: #1f2937;
                --text-medium: #6b7280;
                --text-light: #9ca3af;
            }
            
            body {
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                min-height: 100vh;
                font-family: 'Figtree', sans-serif;
            }
            
            .auth-container {
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            }
            
            .btn-primary {
                background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
                transition: all 0.3s ease;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
            }
            
            .input-group {
                position: relative;
                margin-bottom: 1.5rem;
            }
            
            .input-group input {
                width: 100%;
                padding: 20px 12px 8px 12px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                font-size: 16px;
                transition: all 0.3s ease;
                background: white;
            }
            
            .input-group input:focus {
                outline: none;
                border-color: var(--primary-green);
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            }
            
            .input-group label {
                position: absolute;
                left: 12px;
                top: 16px;
                font-size: 16px;
                color: var(--text-medium);
                transition: all 0.3s ease;
                pointer-events: none;
                background: white;
                padding: 0 4px;
            }
            
            .input-group input:focus + label,
            .input-group input:not(:placeholder-shown) + label {
                top: -10px;
                left: 8px;
                font-size: 12px;
                color: var(--primary-green);
                font-weight: 600;
            }
            
            .input-group input.pr-12 {
                padding-right: 3rem;
            }
            
            .toggle-password {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--text-light);
                cursor: pointer;
                z-index: 10;
            }
            
            .toggle-password:hover {
                color: var(--primary-green);
            }
            
            .tea-logo {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, var(--primary-green), var(--accent-green));
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
                color: white;
                margin-bottom: 2rem;
                box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('layouts.partials.pwa')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <!-- Logo Section -->
            <div class="text-center mb-8">
                <div class="tea-logo mx-auto mb-4">
                    🍵
                </div>
                <h1 class="text-3xl font-bold text-green-800 mb-2">Teazy</h1>
                <p class="text-green-700">Discover your perfect tea match</p>
            </div>

            <!-- Auth Card -->
            <div class="w-full sm:max-w-md auth-container rounded-2xl p-8">
                {{ $slot }}
            </div>
            
            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-green-800/60 text-sm">
                    © 2026 Tea Recommendation. All rights reserved.
                </p>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.toggle-password').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const inputId = this.getAttribute('data-target');
                        const input = document.getElementById(inputId);
                        if (!input) return;

                        const isHidden = input.type === 'password';
                        input.type = isHidden ? 'text' : 'password';
                        this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');

                        const openIcon = this.querySelector('.eye-open');
                        const closedIcon = this.querySelector('.eye-closed');
                        if (openIcon && closedIcon) {
                            openIcon.classList.toggle('hidden', !isHidden);
                            closedIcon.classList.toggle('hidden', isHidden);
                        }
                    });
                });
            });
        </script>
    </body>
</html>
