<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'OJT Sync') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            :root {
                --green: #2e7d32;
                --green-dark: #256a29;
                --green-deep: #163a1d;
                --gold: #d4af37;
                --cream: #fcfbf7;
                --cream-dark: #f7f4ed;
                --ink: #163a1d;
            }

            * { margin: 0; padding: 0; box-sizing: border-box; }

            body {
                font-family: "Figtree", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                background: var(--cream);
                color: var(--ink);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem 1.5rem;
            }

            .landing { width: 100%; max-width: 26rem; text-align: center; }

            .logo { width: 6.5rem; height: 6.5rem; margin: 0 auto 1.5rem; }

            h1 {
                font-size: 2rem;
                font-weight: 700;
                letter-spacing: -0.02em;
                color: var(--green-deep);
            }

            .rule {
                width: 3rem;
                height: 3px;
                background: var(--gold);
                border: 0;
                margin: 1.25rem auto;
            }

            p.tagline {
                color: #5e503b;
                font-size: 0.95rem;
                line-height: 1.6;
                margin-bottom: 2rem;
            }

            .actions { display: flex; flex-direction: column; gap: 0.75rem; }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.75rem 1.25rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 0.15s ease, border-color 0.15s ease;
            }

            .btn-primary {
                background: var(--green);
                color: #fff;
                border: 1px solid var(--green);
            }
            .btn-primary:hover { background: var(--green-dark); }

            .btn-outline {
                background: #fff;
                color: var(--green);
                border: 1px solid #a9dbb8;
            }
            .btn-outline:hover { background: var(--cream-dark); }

            footer {
                margin-top: 2.5rem;
                font-size: 0.75rem;
                color: #a89066;
            }
        </style>
    </head>
    <body>
        <div class="landing">
            <x-brand.sync-mark class="logo" />

            <h1>OJT Sync</h1>
            <hr class="rule">
            <p class="tagline">Connect students with companies for a smoother on-the-job training experience.</p>

            <div class="actions">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-outline">Register</a>
                        @endif
                        @if (Route::has('company.register'))
                            <a href="{{ route('company.register') }}" class="btn btn-outline">Partner with CvSU — Register your company</a>
                        @endif
                    @endauth
                @endif
            </div>

            <footer>OJT Sync — On-the-Job Training Management</footer>
        </div>
    </body>
</html>
