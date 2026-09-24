<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SmartRoom</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --primary: #0f172a;
            --accent: #f5c518;
            --error: #b91c1c;
            --success: #166534;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: var(--bg);
            font-family: Arial, sans-serif;
            color: var(--text);
            padding: 16px;
        }

        .card {
            width: 100%;
            max-width: 440px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
        }

        h1 { margin: 0 0 8px; font-size: 1.35rem; }
        p { margin: 0 0 16px; color: var(--muted); font-size: 0.92rem; line-height: 1.5; }

        label { display: block; margin-bottom: 6px; font-size: 0.82rem; font-weight: 700; }
        input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 12px;
        }

        button {
            width: 100%;
            padding: 11px 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            background: var(--accent);
            color: var(--primary);
        }

        .back {
            display: inline-block;
            margin-top: 12px;
            color: var(--primary);
            font-size: 0.82rem;
            text-decoration: none;
        }

        .msg {
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.82rem;
        }

        .msg-error { background: #fff5f5; border: 1px solid #fecaca; color: var(--error); }
        .msg-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: var(--success); }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reset your password</h1>
        <p>Enter your SmartRoom account email and we will send you a reset link.</p>

        @if (session('status'))
            <div class="msg msg-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="msg msg-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            <button type="submit">Send Reset Link</button>
        </form>

        <a href="{{ route('auth.login') }}" class="back">Back to Sign In</a>
    </div>
</body>
</html>
