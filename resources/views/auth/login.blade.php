<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login – SmartRoom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --navy: #0b1640;
            --navy-mid: #112060;
            --navy-light: #1a2f80;
            --gold: #f5c518;
            --gold-deep: #d4a10a;
            --gold-pale: #fde97a;
            --white: #ffffff;
            --muted: rgba(255,255,255,0.55);
            --error: #ff6b6b;
            --success: #51cf66;
            --ease: cubic-bezier(0.16, 1, 0.3, 1);
            --spring: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: var(--white);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        /* Noise texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
        }

        /* Ambient glow blobs: fade in, then drift slowly */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            opacity: 0;
            animation: blobIn 1.6s ease-out forwards, drift 14s ease-in-out 1.6s infinite alternate;
        }
        .blob-1 { width: 400px; height: 400px; background: var(--gold); top: -100px; left: -100px; }
        .blob-2 { width: 300px; height: 300px; background: var(--navy-light); bottom: -80px; right: -60px; animation-delay: 0.3s, 1.9s; }

        @keyframes blobIn { to { opacity: 0.18; } }
        @keyframes drift  { to { transform: translate(40px, 30px) scale(1.12); opacity: 0.22; } }

        /* ── CARD ── */
        .login-card {
            position: relative;
            z-index: 10;
            display: flex;
            width: 100%;
            max-width: 860px;
            min-height: 540px;
            border-radius: 28px;
            overflow: hidden;
            background: var(--navy);
            box-shadow: 0 32px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,197,24,0.1);
            animation: cardIn 0.9s var(--ease) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(60px) scale(0.96); filter: blur(6px); }
            to   { opacity: 1; transform: none; filter: blur(0); }
        }

        .login-transition {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: grid;
            place-items: center;
            background: rgba(11,22,64,0.88);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .login-card.is-submitting .login-transition {
            opacity: 1;
            pointer-events: auto;
        }
        .transition-content {
            display: grid;
            justify-items: center;
            gap: 14px;
            color: var(--white);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1rem;
            font-weight: 600;
        }
        .transition-spinner {
            width: 34px;
            height: 34px;
            border: 3px solid rgba(245,197,24,0.25);
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Gold light sweep across the card edge once on open */
        .login-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            background: linear-gradient(115deg, transparent 35%, rgba(245,197,24,0.16) 50%, transparent 65%);
            transform: translateX(-100%);
            animation: sweep 1.4s 0.5s var(--ease) forwards;
        }
        @keyframes sweep { to { transform: translateX(100%); } }

        /* ── LEFT PANEL ── */
        .left-panel {
            position: relative;
            width: 42%;
            flex-shrink: 0;
            background: linear-gradient(160deg, var(--navy-light) 0%, var(--navy) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            overflow: hidden;
        }

        .panel-circle {
            position: absolute;
            border-radius: 50%;
            border: 1.5px solid rgba(245,197,24,0.15);
            opacity: 0;
            animation: circleIn 1.2s var(--ease) forwards, float 9s ease-in-out 1.4s infinite alternate;
        }
        .panel-circle-1 { width: 280px; height: 280px; top: -80px; left: -80px; animation-delay: 0.2s, 1.4s; }
        .panel-circle-2 { width: 180px; height: 180px; bottom: -40px; right: -60px; border-color: rgba(245,197,24,0.1); animation-delay: 0.4s, 1.6s; }
        .panel-circle-3 { width: 110px; height: 110px; bottom: 80px; left: 30px; border-color: rgba(245,197,24,0.08); animation-delay: 0.6s, 1.8s; }

        @keyframes circleIn { from { opacity: 0; transform: scale(0.4); } to { opacity: 1; transform: scale(1); } }
        @keyframes float    { to { transform: translate(10px, -14px) scale(1.04); } }

        .panel-welcome {
            font-family: 'Sora', sans-serif;
            font-size: 0.8rem;
            font-weight: 300;
            color: var(--muted);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 26px;
            animation: welcomeIn 1s 0.5s var(--ease) both;
        }
        @keyframes welcomeIn {
            from { opacity: 0; letter-spacing: 0.5em; }
            to   { opacity: 1; letter-spacing: 0.15em; }
        }

        .logo-wrap {
            position: relative;
            width: 76px;
            height: 76px;
            margin-bottom: 20px;
            animation: logoPop 0.9s 0.7s var(--spring) both;
        }
        .logo-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 18px;
            background: #fff;
            object-fit: cover;
            box-shadow: 0 8px 32px rgba(245,197,24,0.25);
        }
        /* pulse ring behind the logo */
        .logo-wrap::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 20px;
            border: 2px solid var(--gold);
            opacity: 0;
            animation: ring 2.4s 1.4s ease-out 2;
        }
        @keyframes logoPop { from { opacity: 0; transform: scale(0.4) rotate(-12deg); } to { opacity: 1; transform: none; } }
        @keyframes ring    { 0% { opacity: 0.7; transform: scale(1); } 100% { opacity: 0; transform: scale(1.7); } }

        .panel-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            animation: brandIn 0.9s 0.95s var(--ease) both;
        }
        .panel-brand .accent { color: var(--gold); }
        @keyframes brandIn {
            from { opacity: 0; clip-path: inset(0 100% 0 0); transform: translateX(-12px); }
            to   { opacity: 1; clip-path: inset(0 0 0 0); transform: none; }
        }

        .panel-tagline {
            font-size: 0.9rem;
            color: var(--muted);
            text-align: center;
            line-height: 1.65;
            max-width: 230px;
            animation: fadeUp 0.8s 1.2s var(--ease) both;
        }

        .panel-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
        }
        .panel-dots span {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(245,197,24,0.3);
            animation: dotIn 0.5s var(--spring) both;
        }
        .panel-dots span:nth-child(1) { background: var(--gold); animation-delay: 1.5s; }
        .panel-dots span:nth-child(2) { animation-delay: 1.6s; }
        .panel-dots span:nth-child(3) { animation-delay: 1.7s; }
        @keyframes dotIn { from { opacity: 0; transform: scale(0); } to { opacity: 1; transform: scale(1); } }

        /* ── RIGHT PANEL ── */
        .right-panel {
            flex: 1;
            background: rgba(255,255,255,0.055);
            backdrop-filter: blur(40px) saturate(1.4);
            -webkit-backdrop-filter: blur(40px) saturate(1.4);
            position: relative;
            padding: 76px 48px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid rgba(245,197,24,0.08);
        }

        /* Return link: sits in the panel's top-left corner, out of the form flow */
        .return-link {
            position: absolute;
            top: 24px;
            left: 40px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 5px 14px 5px 5px;
            border-radius: 999px;
            color: rgba(255,255,255,0.7);
            font-size: 0.82rem;
            font-weight: 500;
            letter-spacing: 0.2px;
            text-decoration: none;
            transition: color 0.25s, background 0.25s;
        }
        .return-icon {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid rgba(245,197,24,0.3);
            background: rgba(245,197,24,0.08);
            color: var(--gold);
            transition: background 0.25s, color 0.25s, border-color 0.25s;
        }
        .return-icon svg { width: 14px; height: 14px; transition: transform 0.3s var(--ease); }
        .return-link:hover { color: var(--white); background: rgba(255,255,255,0.06); }
        .return-link:hover .return-icon { background: var(--gold); border-color: var(--gold); color: var(--navy); }
        .return-link:hover .return-icon svg { transform: translateX(-2px); }

        .rise {
            animation: fadeUp 0.7s var(--ease) both;
            animation-delay: calc(0.75s + var(--i, 0) * 0.08s);
        }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

        .form-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(90deg, var(--white) 0%, var(--gold-pale) 50%, var(--white) 100%);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 6px;
            animation: fadeUp 0.7s 0.75s var(--ease) both, shimmer 2.4s 1.2s ease-in-out 1;
        }
        @keyframes shimmer { from { background-position: 100% 0; } to { background-position: -100% 0; } }

        .form-subtitle { font-size: 0.92rem; color: var(--muted); margin-bottom: 30px; }

        .status {
            margin-bottom: 16px;
            padding: 10px 14px;
            border: 1px solid rgba(81,207,102,0.35);
            background: rgba(81,207,102,0.1);
            color: #b7f0c1;
            border-radius: 10px;
            font-size: 0.85rem;
        }

        .form-group { margin-bottom: 18px; }

        .field-label {
            display: block;
            margin-bottom: 7px;
            font-size: 0.85rem;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            letter-spacing: 0.2px;
        }

        .input-wrap { position: relative; }

        input[type="email"], #password {
            width: 100%;
            padding: 13px 44px 13px 16px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(245,197,24,0.12);
            border-radius: 12px;
            color: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: background 0.3s, border-color 0.3s, box-shadow 0.3s;
        }
        input::placeholder { color: rgba(255,255,255,0.3); }
        input:hover { border-color: rgba(245,197,24,0.25); }
        input:focus {
            background: rgba(255,255,255,0.1);
            border-color: rgba(245,197,24,0.5);
            box-shadow: 0 0 0 4px rgba(245,197,24,0.08);
        }
        input.error { border-color: var(--error); animation: shake 0.4s; }
        @keyframes shake { 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }

        /* Checkmark now appears only once the email is valid */
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            color: var(--gold);
            font-size: 14px;
            pointer-events: none;
            opacity: 0;
            transform: translateY(-50%) scale(0.4);
            transition: opacity 0.25s, transform 0.35s var(--spring);
        }
        .input-wrap.valid .input-icon { opacity: 1; transform: translateY(-50%) scale(1); }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            width: 34px;
            height: 34px;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: color 0.2s, background 0.2s;
        }
        .password-toggle:hover { color: var(--gold); background: rgba(255,255,255,0.06); }
        .password-toggle svg { width: 18px; height: 18px; }

        .error-message { display: none; color: var(--error); font-size: 0.82rem; margin-top: 6px; }
        .error-message.is-visible { display: block; }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 0.85rem;
        }
        .remember-me { display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--muted); }
        input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: var(--gold); }

        .forgot-password {
            color: var(--gold);
            text-decoration: none;
            font-weight: 500;
            background: linear-gradient(currentColor, currentColor) 0 100% / 0 1px no-repeat;
            transition: background-size 0.3s var(--ease);
        }
        .forgot-password:hover { background-size: 100% 1px; }

        .btn-row { display: flex; gap: 12px; margin-bottom: 18px; }
        .signup-note { margin: -8px 0 16px; color: rgba(255,255,255,0.72); font-size: 0.78rem; line-height: 1.45; }

        .login-button, .signup-button {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.25s var(--ease), box-shadow 0.3s, background 0.2s;
        }

        .login-button {
            position: relative;
            overflow: hidden;
            background: var(--gold);
            color: var(--navy);
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        /* shine sweeps across on open and on hover */
        .login-button::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0;
            width: 40%;
            left: -60%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,0.55), transparent);
            transform: skewX(-20deg);
            animation: shine 1.1s 1.7s ease-out 1;
        }
        .login-button:hover::before { animation: shine 0.8s ease-out; }
        @keyframes shine { from { left: -60%; } to { left: 130%; } }

        .login-button:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(245,197,24,0.32); }

        .signup-button { padding-right: 8px; padding-left: 8px; background: var(--navy-light); color: var(--white); font-size: 0.85rem; font-weight: 600; }
        .signup-button:hover { background: #2239a0; transform: translateY(-2px); box-shadow: 0 10px 26px rgba(0,0,0,0.3); }

        .login-button:active, .signup-button:active { transform: scale(0.97); }

        .login-button.is-loading { pointer-events: none; color: transparent; }
        .login-button.is-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            margin: auto;
            width: 18px; height: 18px;
            border: 2px solid rgba(11,22,64,0.25);
            border-top-color: var(--navy);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        :focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }

        /* responsive */
        @media (max-width: 640px) {
            .login-card { flex-direction: column; max-width: 420px; }
            .left-panel { width: 100%; min-height: 220px; padding: 36px 28px 44px; }
            .right-panel { padding: 76px 28px 36px; }
            .return-link { top: 22px; left: 20px; }
            .btn-row { flex-direction: column; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-delay: 0ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-card">
        <div class="login-transition" aria-live="polite" aria-hidden="true">
            <div class="transition-content">
                <span class="transition-spinner" aria-hidden="true"></span>
                <span>Opening your dashboard...</span>
            </div>
        </div>

        <!-- LEFT PANEL -->
        <div class="left-panel">
            <div class="panel-circle panel-circle-1"></div>
            <div class="panel-circle panel-circle-2"></div>
            <div class="panel-circle panel-circle-3"></div>

            <p class="panel-welcome">Welcome to</p>

            <div class="logo-wrap">
                <img src="{{ asset('images/logo.png') }}" alt="SmartRoom Logo" />
            </div>

            <div class="panel-brand">Smart<span class="accent">Room</span></div>

            <p class="panel-tagline">Your intelligent space management platform for seamless access and control.</p>

            <div class="panel-dots" aria-hidden="true"><span></span><span></span><span></span></div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="right-panel">
            <a href="{{ url('/') }}" class="return-link rise" style="--i:0">
                <span class="return-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5"></path>
                        <path d="m12 19-7-7 7-7"></path>
                    </svg>
                </span>
                Return to Landing Page
            </a>
            <h1 class="form-title">Sign In</h1>
            <p class="form-subtitle rise" style="--i:1">Access your SmartRoom account</p>

            @if (session('status'))
                <div class="status rise" style="--i:2">{{ session('status') }}</div>
            @endif

            <form id="loginForm" method="POST" action="{{ route('auth.login.submit') }}" novalidate>
                @csrf
                <div class="form-group rise" style="--i:3">
                    <label class="field-label" for="email">Email Address</label>
                    <div class="input-wrap" id="emailWrap">
                        <input type="email" id="email" name="email" placeholder="you@example.com" value="{{ old('email') }}" autocomplete="email" required>
                        <span class="input-icon" aria-hidden="true">✓</span>
                    </div>
                    <div class="error-message {{ $errors->has('email') ? 'is-visible' : '' }}" id="emailError">{{ $errors->first('email') }}</div>
                </div>

                <div class="form-group rise" style="--i:4">
                    <label class="field-label" for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="•••••••••" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password" title="Show password"></button>
                    </div>
                    <div class="error-message {{ $errors->has('password') ? 'is-visible' : '' }}" id="passwordError">{{ $errors->first('password') }}</div>
                </div>

                <div class="remember-forgot rise" style="--i:5">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="forgot-password">Forgot password?</a>
                </div>

                <div class="btn-row rise" style="--i:6">
                    <button type="submit" class="login-button" id="loginButton">Sign In</button>
                    <button type="button" id="signupButton" class="signup-button" aria-describedby="signupNote" data-signup-url="{{ route('auth.signup') }}">Create Account</button>
                </div>
                <p class="signup-note rise" id="signupNote" style="--i:7">Sign up is for Student only. Faculty accounts are created by an administrator.</p>

            </form>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const emailWrap = document.getElementById('emailWrap');
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        const loginButton = document.getElementById('loginButton');
        const loginCard = document.querySelector('.login-card');
        const signupButton = document.getElementById('signupButton');

        const eyeOpen = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        const eyeOff = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 4.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.3 18.3 0 0 1-3.1 3.9"></path><path d="M6.2 6.2C3.5 8.2 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 3.1-.5"></path></svg>';

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function showError(id, message) {
            const el = document.getElementById(id);
            el.textContent = message;
            el.classList.add('is-visible');
        }

        function markInvalid(input) {
            input.classList.remove('error');
            void input.offsetWidth; // restart the shake
            input.classList.add('error');
        }

        function syncEmailCheck() {
            emailWrap.classList.toggle('valid', isValidEmail(emailInput.value.trim()));
        }

        if (passwordToggle && passwordInput) {
            passwordToggle.innerHTML = eyeOpen;
            passwordToggle.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                passwordToggle.innerHTML = isHidden ? eyeOff : eyeOpen;
                const label = isHidden ? 'Hide password' : 'Show password';
                passwordToggle.setAttribute('aria-label', label);
                passwordToggle.setAttribute('title', label);
            });
        }

        if (signupButton) {
            signupButton.addEventListener('click', () => {
                const url = signupButton.getAttribute('data-signup-url');
                if (url) window.location.href = url;
            });
        }

        if (loginForm && emailInput && passwordInput) {
            syncEmailCheck();
            emailInput.addEventListener('input', () => { emailInput.classList.remove('error'); syncEmailCheck(); });
            passwordInput.addEventListener('input', () => passwordInput.classList.remove('error'));

            loginForm.addEventListener('submit', (e) => {
                document.querySelectorAll('.error-message').forEach(el => { el.textContent = ''; el.classList.remove('is-visible'); });
                [emailInput, passwordInput].forEach(el => el.classList.remove('error'));

                const email = emailInput.value.trim();
                let hasError = false;

                if (!email) {
                    showError('emailError', 'Email is required');
                    markInvalid(emailInput);
                    hasError = true;
                } else if (!isValidEmail(email)) {
                    showError('emailError', 'Please enter a valid email');
                    markInvalid(emailInput);
                    hasError = true;
                }

                if (!passwordInput.value) {
                    showError('passwordError', 'Password is required');
                    markInvalid(passwordInput);
                    hasError = true;
                }

                if (hasError) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                loginCard.classList.add('is-submitting');
                loginButton.classList.add('is-loading');
                loginButton.setAttribute('aria-busy', 'true');
                window.setTimeout(() => loginForm.submit(), 450);
            });
        }
    </script>
</body>
</html>