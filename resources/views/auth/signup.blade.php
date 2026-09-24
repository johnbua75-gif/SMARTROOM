<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sign Up – SmartRoom</title>
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
            --off-white: #f0f4ff;
            --muted: rgba(255,255,255,0.55);
            --card-bg: rgba(255,255,255,0.04);
            --card-border: rgba(245,197,24,0.18);
            --error: #ff6b6b;
            --success: #51cf66;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
        }

        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
        }
        .blob-1 { width: 400px; height: 400px; background: var(--gold); top: -100px; left: -100px; }
        .blob-2 { width: 300px; height: 300px; background: var(--navy-light); bottom: -80px; right: -60px; }

        /* ── CARD ── */
        .signup-card {
            position: relative;
            z-index: 10;
            display: flex;
            width: 92%;
            max-width: 920px;
            min-height: 580px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,197,24,0.1);
            animation: slideUp 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            position: relative;
            width: 40%;
            flex-shrink: 0;
            background: linear-gradient(160deg, var(--navy-light) 0%, var(--navy) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 52px 36px;
            overflow: hidden;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            top: 0; right: -1px;
            width: 60px;
            height: 100%;
            background: var(--white);
            clip-path: ellipse(60px 55% at 100% 50%);
            opacity: 0.07;
        }

        .panel-circle {
            position: absolute;
            border-radius: 50%;
            border: 1.5px solid rgba(245,197,24,0.15);
        }
        .panel-circle-1 { width: 280px; height: 280px; top: -80px; left: -80px; }
        .panel-circle-2 { width: 180px; height: 180px; bottom: -40px; right: -60px; border-color: rgba(245,197,24,0.1); }
        .panel-circle-3 { width: 110px; height: 110px; bottom: 80px; left: 30px; border-color: rgba(245,197,24,0.08); }

        .panel-label {
            font-family: 'Sora', sans-serif;
            font-size: 0.8rem;
            font-weight: 300;
            color: var(--muted);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .panel-icon-wrap {
            width: 88px;
            height: 88px;
            background: rgba(245,197,24,0.12);
            border: 1.5px solid rgba(245,197,24,0.3);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
            box-shadow: 0 8px 32px rgba(245,197,24,0.15);
        }

        .panel-icon-wrap svg { width: 44px; height: 44px; }

        .panel-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: -0.02em;
            margin-bottom: 18px;
        }
        .panel-brand .accent { color: var(--gold); }

        .panel-tagline {
            font-size: 0.88rem;
            color: var(--muted);
            text-align: center;
            line-height: 1.65;
            max-width: 220px;
            margin-bottom: 28px;
        }

        /* Step indicators */
        .step-indicators {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
            max-width: 200px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            transition: color 0.3s;
        }

        .step-item.active { color: var(--white); }

        .step-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(245,197,24,0.25);
            flex-shrink: 0;
            transition: background 0.3s;
        }

        .step-item.active .step-dot { background: var(--gold); box-shadow: 0 0 8px rgba(245,197,24,0.5); }

        .panel-dots {
            position: absolute;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
        }
        .panel-dots span {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(245,197,24,0.3);
        }
        .panel-dots span:nth-child(2) { background: var(--gold); }

        /* ── RIGHT PANEL ── */
        .right-panel {
            flex: 1;
            background: rgba(255,255,255,0.055);
            backdrop-filter: blur(40px) saturate(1.4);
            padding: 44px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid rgba(245,197,24,0.08);
            overflow-y: auto;
        }

        .form-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(90deg, var(--white) 0%, var(--gold-pale) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 4px;
        }

        .form-subtitle {
            font-size: 0.88rem;
            color: var(--muted);
            margin-bottom: 28px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        .form-row .form-group { margin-bottom: 0; }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.83rem;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            letter-spacing: 0.2px;
        }

        .input-wrap { position: relative; }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 11px 40px 11px 15px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(245,197,24,0.12);
            border-radius: 12px;
            color: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            outline: none;
        }

        input::placeholder { color: rgba(255,255,255,0.3); }

        input:focus {
            background: rgba(255,255,255,0.1);
            border-color: rgba(245,197,24,0.4);
            box-shadow: 0 0 0 3px rgba(245,197,24,0.08);
        }

        .input-icon {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(245,197,24,0.35);
            font-size: 13px;
            pointer-events: none;
            transition: color 0.25s;
        }

        input:focus ~ .input-icon { color: var(--gold); }

        /* Password requirements */
        .password-requirements {
            margin-top: 8px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(245,197,24,0.1);
            border-radius: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 8px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            color: var(--muted);
            transition: color 0.3s;
        }

        .req-dot {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: rgba(245,197,24,0.3);
            flex-shrink: 0;
            transition: background 0.3s;
        }

        .requirement.met { color: var(--success); }
        .requirement.met .req-dot { background: var(--success); }

        /* Terms */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 20px;
            font-size: 0.82rem;
            color: var(--muted);
        }

        input[type="checkbox"] {
            width: 16px; height: 16px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: var(--gold);
            flex-shrink: 0;
        }

        .terms-row a { color: var(--gold); text-decoration: none; font-weight: 500; }
        .terms-row a:hover { text-decoration: underline; }

        /* Buttons */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-bottom: 0;
        }

        .signup-button {
            flex: 1;
            padding: 13px 20px;
            background: var(--gold);
            border: none;
            border-radius: 12px;
            color: var(--navy);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            box-shadow: none;
            transition: none;
        }

        .signup-button:hover {
            /* No hover effect */
        }

        .signup-button:disabled {
            cursor: not-allowed;
            transform: none;
        }

        .signin-button {
            flex: 1;
            padding: 13px 20px;
            background: var(--navy-light);
            border: none;
            border-radius: 12px;
            color: var(--white);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: none;
        }

        .signin-button:hover {
            /* No hover effect */
        }

        .error-message {
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 4px;
            display: none;
        }

        input.error {
            border-color: var(--error);
            background: rgba(255,107,107,0.05);
        }

        /* Responsive */
        @media (max-width: 680px) {
            .signup-card { flex-direction: column; max-width: 440px; min-height: unset; }
            .left-panel { width: 100%; min-height: 180px; padding: 32px 24px; }
            .left-panel::after { display: none; }
            .step-indicators { display: none; }
            .right-panel { padding: 32px 24px; }
            .form-row { grid-template-columns: 1fr; }
            .password-requirements { grid-template-columns: 1fr; }
            .btn-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="signup-card">
        <!-- LEFT PANEL -->
        <div class="left-panel">
            <div class="panel-circle panel-circle-1"></div>
            <div class="panel-circle panel-circle-2"></div>
            <div class="panel-circle panel-circle-3"></div>

            <p class="panel-label">Join us today</p>

            <div style="width:72px; height:72px; margin:0 auto 18px; display:flex; align-items:center; justify-content:center;">
                <img src="{{ asset('images/logo.png') }}" alt="SmartRoom Logo" style="width:72px; height:72px; border-radius:16px; background:#fff; object-fit:cover; box-shadow:0 2px 8px rgba(0,0,0,0.08);" />
            </div>

            <div class="panel-brand">Smart<span class="accent">Room</span></div>

            <p class="panel-tagline">Set up your account and start managing your space smarter.</p>
            <!-- Step indicators and panel dots removed as requested -->
        </div>

        <!-- RIGHT PANEL -->
        <div class="right-panel">
            <h1 class="form-title">Create your account</h1>
            <p class="form-subtitle">Fill in your details to get started</p>

            @if ($errors->any())
                <div style="margin-bottom:12px;padding:10px 12px;border:1px solid #fecaca;background:#fff5f5;color:#991b1b;border-radius:8px;font-size:0.82rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="signupForm" method="POST" action="{{ route('auth.signup.submit') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <div class="input-wrap">
                            <input type="text" id="firstName" name="firstName" placeholder="John" value="{{ old('firstName') }}" required>
                            <span class="input-icon">✓</span>
                        </div>
                        <div class="error-message" id="firstNameError"></div>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <div class="input-wrap">
                            <input type="text" id="lastName" name="lastName" placeholder="Doe" value="{{ old('lastName') }}" required>
                            <span class="input-icon">✓</span>
                        </div>
                        <div class="error-message" id="lastNameError"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-mail Address</label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email" placeholder="you@example.com" value="{{ old('email') }}" required>
                        <span class="input-icon">✓</span>
                    </div>
                    <div class="error-message" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="•••••••••" required>
                        <span class="input-icon">✓</span>
                    </div>
                    <div class="error-message" id="passwordError"></div>
                    <div class="password-requirements">
                        <div class="requirement" id="reqLength"><div class="req-dot"></div><span>8+ characters</span></div>
                        <div class="requirement" id="reqUppercase"><div class="req-dot"></div><span>Uppercase letter</span></div>
                        <div class="requirement" id="reqNumber"><div class="req-dot"></div><span>One number</span></div>
                        <div class="requirement" id="reqSpecial"><div class="req-dot"></div><span>Special character</span></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-wrap">
                        <input type="password" id="confirmPassword" name="password_confirmation" placeholder="•••••••••" required>
                        <span class="input-icon">✓</span>
                    </div>
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>

                <div class="terms-row">
                    <input type="checkbox" id="terms" name="terms" {{ old('terms') ? 'checked' : '' }} required>
                    <label for="terms" style="margin-bottom:0; font-weight:400; color:var(--muted);">
                        By signing up, I agree with the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>

                <div class="btn-row">
                    <button type="submit" class="signup-button">Sign Up</button>
                    <button type="button" class="signin-button" onclick="window.location.href='/login'">Sign In</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const signupForm = document.getElementById('signupForm');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');

        passwordInput.addEventListener('input', validatePassword);

        function validatePassword() {
            const password = passwordInput.value;
            updateRequirement('reqLength', password.length >= 8);
            updateRequirement('reqUppercase', /[A-Z]/.test(password));
            updateRequirement('reqNumber', /[0-9]/.test(password));
            updateRequirement('reqSpecial', /[!@#$%^&*(),.?":{}|<>]/.test(password));
        }

        function updateRequirement(id, isMet) {
            const el = document.getElementById(id);
            el.classList.toggle('met', isMet);
        }

        signupForm.addEventListener('submit', (e) => {
            e.preventDefault();

            document.querySelectorAll('.error-message').forEach(el => { el.textContent = ''; el.style.display = 'none'; });
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));

            const firstName = document.getElementById('firstName').value.trim();
            const lastName  = document.getElementById('lastName').value.trim();
            const email     = document.getElementById('email').value.trim();
            const password  = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const termsCheckbox   = document.querySelector('input[name="terms"]');

            let hasError = false;

            if (!firstName) { showError('firstNameError', 'First name is required'); document.getElementById('firstName').classList.add('error'); hasError = true; }
            if (!lastName)  { showError('lastNameError',  'Last name is required');  document.getElementById('lastName').classList.add('error');  hasError = true; }

            if (!email) {
                showError('emailError', 'Email is required');
                document.getElementById('email').classList.add('error');
                hasError = true;
            } else if (!isValidEmail(email)) {
                showError('emailError', 'Please enter a valid email');
                document.getElementById('email').classList.add('error');
                hasError = true;
            }

            if (!password) {
                showError('passwordError', 'Password is required');
                passwordInput.classList.add('error');
                hasError = true;
            } else if (!isValidPassword(password)) {
                showError('passwordError', 'Password does not meet requirements');
                passwordInput.classList.add('error');
                hasError = true;
            }

            if (password !== confirmPassword) {
                showError('confirmPasswordError', 'Passwords do not match');
                confirmPasswordInput.classList.add('error');
                hasError = true;
            }

            if (!termsCheckbox.checked) {
                showError('firstNameError', 'You must agree to the terms');
                hasError = true;
            }

            if (hasError) return;

            signupForm.submit();
        });

        function showError(elementId, message) {
            const el = document.getElementById(elementId);
            el.textContent = message;
            el.style.display = 'block';
        }

        function isValidEmail(email) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }

        function isValidPassword(password) {
            return password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[!@#$%^&*(),.?":{}|<>]/.test(password);
        }
    </script>
</body>
</html>