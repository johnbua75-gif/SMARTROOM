@php
    $facultyName = request()->user()?->name ?? 'Faculty';
    $facultyDept = request()->user()?->department ?? 'Faculty';
    $facultyEmail = request()->user()?->email ?? '';
    $facultyInitials = strtoupper(substr((string) $facultyName, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID - SmartDoor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --navy:#0b1640; --yellow:#f5c518; --bg:#f0f2f8; --text:#0f1729; --muted:#64748b; --border:#e2e8f0; --green:#15803d; --green-bg:#dcfce7; --red:#b91c1c; --red-bg:#fee2e2; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:'Instrument Sans',sans-serif; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:230px; height:100vh; padding:0; background:var(--navy); color:#fff; display:flex; flex-direction:column; overflow:hidden; z-index:100; }
        .brand { display:flex; align-items:center; gap:10px; padding:0 12px 24px; border-bottom:1px solid rgba(255,255,255,.1); font-weight:800; font-size:1rem; }
        .brand-mark { display:grid; place-items:center; width:38px; height:38px; border-radius:11px; background:var(--yellow); color:var(--navy); }
        .nav-label { margin:24px 12px 8px; color:rgba(255,255,255,.35); font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .nav-link { display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:10px; color:rgba(255,255,255,.65); text-decoration:none; font-weight:600; font-size:.84rem; }
        .nav-link:hover,.nav-link.active { background:rgba(245,197,24,.14); color:var(--yellow); }
        .nav-link i { width:20px; text-align:center; }
        .main { margin-left:230px; padding:34px 40px 60px; }
        .header { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:24px; }
        h1 { margin:0; font:800 1.65rem 'Sora',sans-serif; }
        .subtitle { margin-top:6px; color:var(--muted); }
        .search { display:flex; gap:8px; }
        .search input { min-width:280px; padding:11px 14px; border:1px solid var(--border); border-radius:10px; font:inherit; }
        .search button { border:0; border-radius:10px; padding:0 16px; background:var(--navy); color:#fff; font-weight:700; cursor:pointer; }
        .grid { display:grid; grid-template-columns:1.15fr .85fr; gap:20px; }
        .panel { overflow:hidden; border:1px solid var(--border); border-radius:16px; background:#fff; box-shadow:0 4px 18px rgba(15,23,42,.05); }
        .panel-head { padding:18px 20px; border-bottom:1px solid var(--border); }
        .panel-head h2 { margin:0; font-size:1rem; }
        .panel-head p { margin:4px 0 0; color:var(--muted); font-size:.8rem; }
        .card-list { display:grid; gap:10px; padding:16px; }
        .card-row { display:grid; grid-template-columns:1fr auto; gap:14px; padding:15px; border:1px solid var(--border); border-radius:12px; }
        .card-name { font-weight:800; }
        .card-meta { margin-top:4px; color:var(--muted); font-size:.8rem; }
        .card-code { margin-top:8px; color:#334155; font:500 .78rem 'JetBrains Mono',monospace; }
        .status { align-self:start; padding:5px 9px; border-radius:999px; font-size:.72rem; font-weight:800; text-transform:capitalize; }
        .status.active { color:var(--green); background:var(--green-bg); }
        .status.inactive,.status.suspended { color:var(--red); background:var(--red-bg); }
        .status.pending { color:#92400e; background:#fef3c7; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:12px 16px; border-bottom:1px solid var(--border); text-align:left; font-size:.78rem; }
        th { color:var(--muted); font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; background:#f8fafc; }
        td small { display:block; margin-top:3px; color:var(--muted); }
        .result { font-weight:800; text-transform:capitalize; }
        .result.granted { color:var(--green); } .result.denied { color:var(--red); }
        .empty { padding:30px 20px; color:var(--muted); text-align:center; }
        .sidebar::before { content:''; position:absolute; inset:0; background:linear-gradient(160deg,rgba(245,197,24,.06) 0%,transparent 55%); pointer-events:none; }
        .sidebar::after { content:''; position:absolute; bottom:-60px; right:-60px; width:180px; height:180px; border-radius:50%; border:1px solid rgba(245,197,24,.08); pointer-events:none; }
        .sidebar-logo { position:relative; z-index:1; display:flex; align-items:center; gap:12px; padding:28px 20px 24px 24px; color:#fff; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.06); margin-bottom:8px; }
        .logo-mark { width:40px; height:40px; background:var(--yellow); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:var(--navy); flex-shrink:0; box-shadow:0 4px 12px rgba(245,197,24,.4); }
        .logo-text { line-height:1; }.brand-psu { display:block; margin-bottom:3px; color:rgba(255,255,255,.45); font-size:.6rem; font-weight:600; letter-spacing:.18em; text-transform:uppercase; }.brand-main { font-size:1.05rem; font-weight:700; }.brand-main span { color:var(--yellow); }
        .nav-section-label { display:block; position:relative; z-index:1; padding:16px 24px 6px; color:rgba(255,255,255,.25); font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .sidebar-nav { position:relative; z-index:1; list-style:none; overflow-y:auto; padding:0 12px; scrollbar-width:none; -ms-overflow-style:none; }.sidebar-nav::-webkit-scrollbar { width:0; height:0; display:none; }.sidebar-nav li { margin-bottom:2px; }.sidebar-nav a { display:flex; align-items:center; gap:11px; padding:11px 12px; color:rgba(255,255,255,.6); text-decoration:none; font-size:.88rem; font-weight:500; border-radius:10px; transition:all .22s; position:relative; overflow:hidden; }.sidebar-nav a .nav-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem; background:rgba(255,255,255,.05); flex-shrink:0; }.sidebar-nav a:hover { color:rgba(255,255,255,.9); background:rgba(255,255,255,.06); }.sidebar-nav a.active { background:rgba(245,197,24,.14); color:var(--yellow); }.sidebar-nav a.active .nav-icon { background:rgba(245,197,24,.2); color:var(--yellow); }.sidebar-nav a.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; background:var(--yellow); border-radius:0 2px 2px 0; }
        .sidebar-footer { position:relative; z-index:1; margin-top:auto; padding:16px 12px 24px; border-top:1px solid rgba(255,255,255,.06); }.user-widget { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; background:rgba(255,255,255,.05); margin-bottom:8px; }.user-avatar { width:34px; height:34px; border-radius:50%; flex-shrink:0; background:#1a2f80; border:2px solid rgba(245,197,24,.4); display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; color:var(--yellow); }.user-widget-info { flex:1; min-width:0; }.user-widget-name { font-size:.83rem; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }.user-widget-role { font-size:.73rem; color:rgba(255,255,255,.4); }.sidebar-logout-btn { display:flex; align-items:center; gap:10px; width:100%; padding:9px 12px; color:rgba(255,255,255,.4); font-size:.84rem; font-weight:500; border:0; border-radius:10px; background:none; cursor:pointer; font-family:inherit; text-align:left; }.sidebar-logout-btn:hover { color:#f87171; background:rgba(244,63,94,.08); }
        @media (max-width:900px) { .sidebar { display:none; } .main { margin-left:0; padding:24px 16px; } .header { align-items:stretch; flex-direction:column; } .grid { grid-template-columns:1fr; } .search input { min-width:0; flex:1; } }
        .main { padding:36px clamp(24px,3.2vw,56px) 60px; }
        h1 { font-size:1.9rem; letter-spacing:-.02em; }
        .header { padding-bottom:22px; border-bottom:1px solid var(--border); }
        .panel { border-radius:16px; box-shadow:0 8px 30px rgba(15,23,42,.07); }
        .panel-head { padding:20px 26px; background:linear-gradient(180deg,#fff,#fbfcfe); }
        .panel-head h2 { display:flex; align-items:center; gap:10px; color:var(--navy); font:700 1.02rem 'Sora',sans-serif; }
        .panel-head h2 i { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; color:var(--navy); background:#fff3c4; font-size:.88rem; }
        .card-list { gap:18px; padding:22px 26px; }
        .rfid-row { display:grid; grid-template-columns:minmax(300px,420px) minmax(0,1fr); gap:28px; align-items:center; padding:22px; border:1px solid var(--border); border-radius:16px; background:#fcfdff; }
        .rfid-row.inactive { background:#fffafa; border-color:#f3d6d6; }
        .rfid-visual { position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; gap:26px; min-height:210px; padding:22px 24px; border-radius:18px; color:#fff; background:linear-gradient(135deg,#0b1640 0%,#1a2f80 62%,#2b49b3 100%); box-shadow:0 14px 30px rgba(11,22,64,.28); }
        .rfid-visual::before { content:''; position:absolute; top:-70px; right:-50px; width:210px; height:210px; border-radius:50%; background:radial-gradient(circle,rgba(245,197,24,.28),transparent 68%); }
        .rfid-visual::after { content:''; position:absolute; bottom:-90px; left:-60px; width:220px; height:220px; border-radius:50%; border:1px solid rgba(255,255,255,.1); }
        .rfid-row.inactive .rfid-visual { background:linear-gradient(135deg,#3b2230 0%,#6b2a3a 62%,#8c3347 100%); box-shadow:0 14px 30px rgba(107,42,58,.28); }
        .rfid-visual > * { position:relative; z-index:1; }
        .rfid-visual-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .rfid-chip { width:44px; height:34px; border-radius:7px; background:linear-gradient(135deg,#f9d84a,#d9a80f); box-shadow:inset 0 0 0 1px rgba(0,0,0,.18),inset 0 8px 0 -4px rgba(255,255,255,.35); }
        .rfid-visual .status { display:inline-flex; align-items:center; gap:6px; padding:6px 11px; backdrop-filter:blur(2px); }
        .rfid-visual .status i { font-size:.72rem; }
        .rfid-visual-number { font:600 1.25rem 'JetBrains Mono',ui-monospace,monospace; letter-spacing:.14em; overflow-wrap:anywhere; }
        .rfid-visual-bottom { display:flex; align-items:flex-end; justify-content:space-between; gap:12px; }
        .rfid-visual-bottom small { display:block; margin-bottom:3px; color:rgba(255,255,255,.6); font-size:.64rem; letter-spacing:.12em; text-transform:uppercase; }
        .rfid-visual-bottom strong { font-size:.95rem; }
        .rfid-wave { color:rgba(255,255,255,.55); font-size:1.3rem; transform:rotate(90deg); }
        .rfid-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin:0; }
        .rfid-detail { display:flex; align-items:flex-start; gap:13px; padding:16px; border:1px solid var(--border); border-radius:14px; background:#fff; }
        .rfid-detail-icon { width:38px; height:38px; flex:0 0 38px; display:flex; align-items:center; justify-content:center; border-radius:11px; color:#273b70; background:#e6ebf8; }
        .rfid-detail dt { margin-bottom:4px; color:var(--muted); font-size:.68rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
        .rfid-detail dd { margin:0; color:var(--text); font-size:.92rem; font-weight:700; overflow-wrap:anywhere; }
        .rfid-detail dd.mono { font:500 .84rem 'JetBrains Mono',ui-monospace,monospace; }
        .rfid-detail dd small { display:block; margin-top:3px; color:var(--muted); font-size:.74rem; font-weight:500; }
        .rfid-detail dd small.bad { color:var(--red); font-weight:700; }
        .empty { padding:48px 20px; }
        .empty i { display:block; margin-bottom:12px; color:#9da8b8; font-size:1.8rem; }
        .empty strong { display:block; margin-bottom:5px; color:var(--text); }
        @media (max-width:1180px) { .rfid-row { grid-template-columns:1fr; } .rfid-visual { max-width:460px; } }
        @media (max-width:900px) { .main { padding:24px 16px 40px; } .card-list { padding:16px; } .panel-head { padding:16px; } }
        @media (max-width:560px) { .rfid-details { grid-template-columns:1fr; } .rfid-row { padding:14px; } }
    </style>
</head>
<body>
<aside class="sidebar">
    <a href="{{ url('/dashboard') }}" class="sidebar-logo">
        <div class="logo-mark"><i class="fas fa-door-open"></i></div>
        <div class="logo-text"><span class="brand-psu">PSU</span><span class="brand-main">Smart<span>Door</span></span></div>
    </a>
    <span class="nav-section-label">Main Menu</span>
    <ul class="sidebar-nav">
        <li><a href="{{ url('/faculty_dashboard') }}" class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-line"></i></span>Dashboard</a></li>
        <li><a href="{{ url('/rooms') }}" class="{{ Request::is('rooms*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-door-open"></i></span>Rooms</a></li>
        <li><a href="{{ url('/faculty-schedule') }}" class="{{ Request::is('faculty-schedule') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clock"></i></span>Schedule</a></li>
        <li><a href="{{ url('/attendance') }}" class="{{ Request::is('attendance*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance</a></li>
    </ul>
    <span class="nav-section-label">Tools</span>
    <ul class="sidebar-nav">
        <li><a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID</a></li>
        <li><a href="{{ route('faculty.notifications') }}" class="{{ Request::routeIs('faculty.notifications') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-bell"></i></span>Notifications</a></li>
        <li><a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports</a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="user-widget"><div class="user-avatar">{{ htmlspecialchars($facultyInitials) }}</div><div class="user-widget-info"><div class="user-widget-name">{{ htmlspecialchars($facultyName) }}</div><div class="user-widget-role">{{ htmlspecialchars($facultyDept) }}</div></div></div>
        <form method="POST" action="{{ url('/logout') }}">@csrf<button type="submit" class="sidebar-logout-btn"><i class="fas fa-arrow-right-from-bracket"></i>Sign Out</button></form>
    </div>
</aside>
<main class="main">
    <header class="header">
        <div><h1>RFID</h1><div class="subtitle">View the condition of your registered RFID card.</div></div>
    </header>
    <div class="grid" style="grid-template-columns:1fr;">
        <section class="panel">
            <div class="panel-head"><h2><i class="fas fa-id-card" aria-hidden="true"></i>My Card Condition</h2><p>Your registered RFID card status and access details.</p></div>
            <div class="card-list">
                @forelse($cards as $card)
                    @php
                        $isExpired = $card->expires_at && $card->expires_at->isPast();
                        $condition = $isExpired ? 'Expired' : ucfirst((string) $card->status);
                        $conditionClass = $isExpired || strtolower((string) $card->status) !== 'active' ? 'inactive' : 'active';
                    @endphp
                    <article class="rfid-row {{ $conditionClass }}">
                        <div class="rfid-visual" aria-label="RFID card">
                            <div class="rfid-visual-top">
                                <span class="rfid-chip" aria-hidden="true"></span>
                                <span class="status {{ $conditionClass }}"><i class="fas {{ $conditionClass === 'active' ? 'fa-circle-check' : 'fa-circle-exclamation' }}" aria-hidden="true"></i>{{ $condition }}</span>
                            </div>
                            <div class="rfid-visual-number">{{ $card->card_number ?? 'No card number' }}</div>
                            <div class="rfid-visual-bottom">
                                <div><small>Card holder</small><strong>{{ $card->user?->name ?? 'Unassigned card' }}</strong></div>
                                <i class="fas fa-wifi rfid-wave" aria-hidden="true"></i>
                            </div>
                        </div>
                        <dl class="rfid-details">
                            <div class="rfid-detail"><span class="rfid-detail-icon"><i class="fas fa-door-open" aria-hidden="true"></i></span><div><dt>Assigned room</dt><dd>{{ $card->classroom?->name ?? 'No room assigned' }}</dd></div></div>
                            <div class="rfid-detail"><span class="rfid-detail-icon"><i class="fas fa-fingerprint" aria-hidden="true"></i></span><div><dt>RFID UID</dt><dd class="mono">{{ $card->rfid_uid ?? 'No RFID UID' }}</dd></div></div>
                            <div class="rfid-detail"><span class="rfid-detail-icon"><i class="far fa-calendar-check" aria-hidden="true"></i></span><div><dt>Expires</dt><dd>{{ $card->expires_at?->format('M j, Y') ?? 'No expiry date' }}@if ($card->expires_at)<small class="{{ $isExpired ? 'bad' : '' }}">{{ $card->expires_at->diffForHumans() }}</small>@endif</dd></div></div>
                            <div class="rfid-detail"><span class="rfid-detail-icon"><i class="far fa-clock" aria-hidden="true"></i></span><div><dt>Last seen</dt><dd>{{ $card->last_accessed_at?->format('M j, Y g:i A') ?? 'Never' }}@if ($card->last_accessed_at)<small>{{ $card->last_accessed_at->diffForHumans() }}</small>@endif</dd></div></div>
                        </dl>
                    </article>
                @empty
                    <div class="empty"><i class="fas fa-id-card" aria-hidden="true"></i><strong>No RFID card assigned</strong>No RFID card is currently assigned to your account.</div>
                @endforelse
            </div>
        </section>
    </div>
</main>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>
