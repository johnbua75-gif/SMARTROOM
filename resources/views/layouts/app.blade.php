<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'SmartRoom') – SmartRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3H59N9lWKQCaYMx50uDF08MkCsBPSP3E2McJ6+8WcGXflyZ2Zy1H56ZWDr8ZGhnPJ+9WMwQvvN4g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="{{ asset('css/classroom-detail.css') }}" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #f5c518;
            --primary-light: #fde97a;
            --primary-dark: #d4a10a;
            --blue: #112060;
            --blue-light: #1a2f80;
            --green: #16a34a;
            --gray: #5a6785;
            --gray-light: #eef2ff;
            --gray-lighter: #f4f7ff;
            --white: #ffffff;
            --border: #dbe3f5;
            --text: #0b1640;
            --text-secondary: #5a6785;
            --red: #ef4444;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-lighter);
            color: var(--text);
            overflow-x: hidden;
            line-height: 1.6;
        }

        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--text) 0%, var(--blue) 100%);
            border-right: 1px solid rgba(245, 197, 24, 0.18);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            overflow-y: auto;
            z-index: 10;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            box-shadow: 1px 0 8px rgba(0, 0, 0, 0.25), 2px 0 20px rgba(0, 0, 0, 0.2);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 3px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            margin-bottom: 40px;
            text-decoration: none;
            color: var(--white);
            cursor: pointer;
            transition: all 0.3s;
        }

        .sidebar-logo-img {
            width: 36px;
            height: 36px;
            display: block;
            object-fit: contain;
            object-position: center;
            flex-shrink: 0;
        }

        .sidebar-logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1;
            color: var(--white);
        }

        .sidebar-logo-text .brand-psu {
            font-size: 0.56rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            color: rgba(255, 255, 255, 0.72);
            margin-left: 2px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .sidebar-logo-text .brand-main {
            font-weight: 700;
            font-size: 0.98rem;
            letter-spacing: -0.01em;
            color: var(--white);
        }

        .sidebar-logo-text .door-accent {
            color: var(--primary);
        }

        .sidebar-nav {
            flex: 1;
            list-style: none;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.78);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
            position: relative;
        }

        .sidebar-nav a:hover {
            color: var(--primary-light);
            background: rgba(255, 255, 255, 0.06);
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary);
            opacity: 0;
        }

        .sidebar-nav a.active {
            color: var(--primary);
            background: rgba(245, 197, 24, 0.16);
        }

        .sidebar-nav a.active::before {
            opacity: 1;
        }

        .sidebar-logout {
            border-top: 1px solid rgba(245, 197, 24, 0.2);
            padding-top: 16px;
            padding-bottom: 16px;
            margin: 16px 0 0 0;
        }

        .sidebar-logout a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.78);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .sidebar-logout a:hover {
            color: var(--primary-light);
            background: rgba(255, 255, 255, 0.06);
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            flex: 1;
            margin-left: 260px;
            overflow-y: auto;
            background: var(--gray-lighter);
            padding: 32px;
            z-index: 1;
        }

        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .main-content::-webkit-scrollbar-thumb {
            background: #d0d0d0;
            border-radius: 4px;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            background: var(--white);
            padding: 24px 28px;
            border-radius: 12px;
            border: 1px solid rgba(229, 231, 235, 0.6);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* ── FILTER BAR ── */
        .filter-bar {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
        }

        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            background: transparent;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        .filter-icon-btn {
            background: var(--white);
            border: 1px solid rgba(229, 231, 235, 0.6);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* ── ROOM GRID ── */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .room-card {
            background: var(--white);
            border: 1px solid rgba(229, 231, 235, 0.6);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 0 rgba(255, 255, 255, 0.5);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .room-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .room-location {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .room-status {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-available {
            background: rgba(39, 174, 96, 0.1);
            color: var(--green);
        }

        .status-occupied {
            background: rgba(26, 47, 128, 0.12);
            color: var(--blue-light);
        }

        .status-reserved {
            background: rgba(245, 197, 24, 0.14);
            color: var(--primary);
        }

        .card-body {
            padding: 20px;
            flex: 1;
        }

        .room-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }

        .info-label {
            color: var(--text-secondary);
        }

        .info-value {
            font-weight: 600;
            color: var(--text);
        }

        .room-class {
            padding: 12px;
            border-radius: 8px;
            background: var(--gray-light);
            font-size: 0.85rem;
        }

        .class-title {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .class-time {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1200px) {
            .room-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
                left: -260px;
                transition: left 0.3s;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .filter-bar {
                flex-direction: column;
            }

            .room-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="#" class="sidebar-logo">
                <img src="{{ asset('images/logo.png') }}" alt="PSU SmartDoor Logo" class="sidebar-logo-img">
                <div class="sidebar-logo-text">
                    <span class="brand-psu">PSU</span>
                    <span class="brand-main">Smart<span class="door-accent">Door</span></span>
                </div>
            </a>

            <ul class="sidebar-nav">
                <li><a href="/dashboard" class="{{ Request::is('dashboard') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="/classrooms" class="{{ Request::is('classrooms*') ? 'active' : '' }}"><i class="fas fa-school"></i> Classrooms</a></li>
                <li><a href="/schedule" class="{{ Request::is('schedule*') ? 'active' : '' }}"><i class="fas fa-calendar"></i> Schedule</a></li>
                <li><a href="/smartlocking" class="{{ Request::is('smartlocking*') ? 'active' : '' }}"><i class="fas fa-lock"></i> SmartLocking</a></li>
                <!-- AI Recommendations removed from sidebar -->
                <li><a href="/attendance" class="{{ Request::is('attendance*') ? 'active' : '' }}"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                <!-- Reports link removed from sidebar -->
            </ul>

            <div class="sidebar-logout">
                <form method="POST" action="/logout" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:none;border:none;padding:0;color:inherit;font:inherit;cursor:pointer;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-sign-out-alt"></i> Sign Out
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            @yield('content')
        </div>
    </div>

    <script>
        // Active nav link
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>
