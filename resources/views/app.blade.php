<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'GED Pharma') }} - Système de Gestion Documentaire</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>📄</text></svg>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Meta for PWA -->
    <meta name="theme-color" content="#1e40af">
    <meta name="description" content="Système de Gestion Électronique des Documents - Conforme GMP, 21 CFR Part 11, ISO 13485">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Vue App Mount Point -->
    <div id="app"></div>
    
    <!-- Noscript Fallback -->
    <noscript>
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f3f4f6; padding: 2rem;">
            <div style="text-align: center; max-width: 400px;">
                <svg style="width: 64px; height: 64px; margin: 0 auto 1rem; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">
                    JavaScript Requis
                </h1>
                <p style="color: #6b7280; margin-bottom: 1rem;">
                    Cette application nécessite JavaScript pour fonctionner. Veuillez activer JavaScript dans votre navigateur.
                </p>
                <p style="font-size: 0.875rem; color: #9ca3af;">
                    GED Pharma - Système de Gestion Documentaire<br>
                    Conforme GMP Annexe 11 | 21 CFR Part 11
                </p>
            </div>
        </div>
    </noscript>
    
    <!-- Loading State (shown before Vue mounts) -->
    <script>
        // Show loading state immediately
        (function() {
            var app = document.getElementById('app');
            if (app && !app.innerHTML.trim()) {
                app.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f9fafb;"><div style="text-align: center;"><svg style="width: 48px; height: 48px; margin: 0 auto 1rem; animation: spin 1s linear infinite; color: #3b82f6;" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p style="color: #6b7280; font-size: 0.875rem;">Chargement de l\'application...</p></div></div><style>@keyframes spin { to { transform: rotate(360deg); } }</style>';
            }
        })();
    </script>
</body>
</html>
