@php
    $facultyName = request()->user()?->name ?? 'Faculty';
    $facultyDept = request()->user()?->department ?? 'Faculty';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Verification - SmartDoor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --navy:#0b1640; --yellow:#f5c518; --bg:#f0f2f8; --text:#0f1729; --muted:#64748b; --border:#e2e8f0; --green:#15803d; --green-bg:#dcfce7; --red:#b91c1c; --red-bg:#fee2e2; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:'DM Sans',sans-serif; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:230px; padding:24px 12px; background:var(--navy); color:#fff; }
        .brand { display:flex; align-items:center; gap:10px; padding:0 12px 24px; border-bottom:1px solid rgba(255,255,255,.1); font-weight:800; font-size:1rem; }
        .brand-mark { display:grid; place-items:center; width:38px; height:38px; border-radius:11px; background:var(--yellow); color:var(--navy); }
        .nav-label { margin:24px 12px 8px; color:rgba(255,255,255,.35); font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .nav-link { display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:10px; color:rgba(255,255,255,.65); text-decoration:none; font-weight:600; font-size:.84rem; }
        .nav-link:hover,.nav-link.active { background:rgba(245,197,24,.14); color:var(--yellow); }
        .nav-link i { width:20px; text-align:center; }
        .main { margin-left:230px; padding:34px 40px 60px; }
        .header { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:24px; }
        h1 { margin:0; font:800 1.65rem 'Plus Jakarta Sans',sans-serif; }
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
        .card-code { margin-top:8px; color:#334155; font:500 .78rem 'DM Mono',monospace; }
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
        @media (max-width:900px) { .sidebar { position:static; width:100%; min-height:auto; } .main { margin-left:0; padding:24px 16px; } .header { align-items:stretch; flex-direction:column; } .grid { grid-template-columns:1fr; } .search input { min-width:0; flex:1; } }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><span class="brand-mark"><i class="fas fa-door-open"></i></span> Smart<span style="color:var(--yellow);">Door</span></div>
    <div class="nav-label">Main</div>
    <a class="nav-link" href="{{ route('faculty.dashboard') }}"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a class="nav-link" href="{{ route('faculty.rooms') }}"><i class="fas fa-door-open"></i> Rooms</a>
    <a class="nav-link" href="{{ route('faculty.schedule') }}"><i class="fas fa-calendar-days"></i> Schedule</a>
    <a class="nav-link" href="{{ route('faculty.attendance') }}"><i class="fas fa-clipboard-check"></i> Attendance</a>
    <div class="nav-label">Tools</div>
    <a class="nav-link active" href="{{ route('faculty.rfid.verification') }}"><i class="fas fa-id-card"></i> RFID Verification</a>
    <a class="nav-link" href="{{ url('/reports') }}"><i class="fas fa-chart-bar"></i> Reports</a>
</aside>
<main class="main">
    <header class="header">
        <div><h1>RFID Card Condition</h1><div class="subtitle">Read-only view of registered card condition and assignment.</div></div>
        <form class="search" method="GET" action="{{ route('faculty.rfid.verification') }}">
            <input name="search" value="{{ $search }}" placeholder="Search name, card number, or RFID UID" aria-label="Search RFID cards">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </header>
    <div class="grid" style="grid-template-columns:1fr;">
        <section class="panel">
            <div class="panel-head"><h2>Card Condition</h2><p>{{ $cards->count() }} cards shown from the current system data. No card settings can be changed here.</p></div>
            <div class="card-list">
                @forelse($cards as $card)
                    @php
                        $isExpired = $card->expires_at && $card->expires_at->isPast();
                        $condition = $isExpired ? 'Expired' : ucfirst((string) $card->status);
                        $conditionClass = $isExpired || strtolower((string) $card->status) !== 'active' ? 'inactive' : 'active';
                    @endphp
                    <article class="card-row">
                        <div>
                            <div class="card-name">{{ $card->user?->name ?? 'Unassigned card' }}</div>
                            <div class="card-meta">Assigned room: {{ $card->classroom?->name ?? 'No room assigned' }}</div>
                            <div class="card-code">{{ $card->card_number ?? 'No card number' }} · {{ $card->rfid_uid ?? 'No RFID UID' }}</div>
                            <div class="card-meta">Expires: {{ $card->expires_at?->format('M j, Y') ?? 'No expiry date' }} · Last seen: {{ $card->last_accessed_at?->format('M j, Y g:i A') ?? 'Never' }}</div>
                        </div>
                        <span class="status {{ $conditionClass }}">{{ $condition }}</span>
                    </article>
                @empty
                    <div class="empty">No RFID cards matched your search.</div>
                @endforelse
            </div>
        </section>
    </div>
</main>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>
