<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - SmartRoom</title>
    <style>
        :root {
            --navy: #0b1640;
            --gold: #f5c518;
            --border: #d1d5db;
            --danger: #b91c1c;
            --ok: #166534;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0b1640 0%, #1a2f80 100%);
            display: grid;
            place-items: center;
            padding: 18px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .head {
            background: var(--navy);
            color: #fff;
            padding: 18px 20px;
        }
        .head h1 {
            margin: 0;
            font-size: 20px;
        }
        .head p {
            margin: 8px 0 0;
            color: #dbe3ff;
            font-size: 14px;
        }
        .body {
            padding: 20px;
        }
        .alert {
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
        }
        .alert-error {
            color: var(--danger);
            background: #fef2f2;
            border: 1px solid #fecaca;
        }
        .alert-ok {
            color: var(--ok);
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }
        input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 14px;
            font-size: 14px;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 14px;
            font-weight: 700;
            background: var(--gold);
            color: #111827;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1>Change Your Password</h1>
            <p>Your temporary password must be replaced before you can continue.</p>
        </div>
        <div class="body">
            @if (session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.change.submit') }}">
                @csrf
                <label for="current_password">Current Password (temporary)</label>
                <input id="current_password" name="current_password" type="password" required>

                <label for="password">New Password</label>
                <input id="password" name="password" type="password" minlength="8" required>

                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>

                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
