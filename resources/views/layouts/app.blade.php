<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'SmartRoom') - SmartRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            font-family: 'Instrument Sans', sans-serif;
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

        /* ── MAIN CONTENT ── */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-w, 252px);
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
<body class="@yield('body-class')">
    <div class="container">
        @include('frontend.admin.partials.sidebar')

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
