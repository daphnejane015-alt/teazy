<!-- PWA Manifest & Meta -->
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#22c55e">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Teazy">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

<!-- Service Worker Registration -->
<script defer src="{{ asset('sw-register.js') }}"></script>
