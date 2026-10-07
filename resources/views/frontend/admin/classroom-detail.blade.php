{{-- resources/views/admin/classrooms/show.blade.php --}}
{{-- IMPORTANT: This view is STANDALONE. Do NOT use layouts.app (it injects the SmartDoor sidebar). --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $classroom->name }} – SmartRoom</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --yellow:         #f5c518;
    --navy:           #0b1640;
    --white:          #ffffff;
    --bg:             #f0f2f8;
    --bg-alt:         #e8ebf4;
    --border:         #e2e6f0;
    --border-strong:  #cdd2e4;
    --text:           #0d1117;
    --text-secondary: #5a6480;
    --text-light:     #9aa0b8;
    --green:          #059669;
    --green-bg:       #d1fae5;
    --green-border:   #6ee7b7;
    --blue:           #2563eb;
    --blue-bg:        #dbeafe;
    --blue-border:    #93c5fd;
    --red:            #dc2626;
    --red-bg:         #fee2e2;
    --red-border:     #fca5a5;
    --shadow-md:      0 4px 16px rgba(13,17,40,0.08), 0 2px 6px rgba(13,17,40,0.05);
    --shadow-card:    0 2px 12px rgba(13,17,40,0.07);
    --sidebar-w:      240px;
    --radius:         16px;
    --radius-sm:      10px;
    --font:           'Plus Jakarta Sans', sans-serif;
    --font-mono:      'DM Mono', monospace;
  }

  body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

  /* ══ SIDEBAR ══ */
  .sidebar {
    position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh;
    background: var(--navy); display: flex; flex-direction: column;
    padding: 0; overflow: hidden; z-index: 100;
  }
  .sidebar::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(160deg, rgba(245,197,24,0.06) 0%, transparent 55%);
    pointer-events: none;
  }
  .sidebar::after {
    content: ''; position: absolute; bottom: -60px; right: -60px;
    width: 180px; height: 180px; border-radius: 50%;
    border: 1px solid rgba(245,197,24,0.08); pointer-events: none;
  }
  .sidebar-logo {
    display: flex; align-items: center; gap: 12px;
    padding: 28px 20px 24px 24px; text-decoration: none;
    border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 8px;
  }
  .logo-mark {
    width: 40px; height: 40px; background: var(--yellow); border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: var(--navy); flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(245,197,24,0.4);
  }
  .logo-text { line-height: 1; }
  .logo-text .brand-psu {
    font-size: 0.6rem; font-weight: 600; letter-spacing: 0.18em;
    color: rgba(255,255,255,0.45); text-transform: uppercase; display: block; margin-bottom: 3px;
  }
  .logo-text .brand-main { font-size: 1.05rem; font-weight: 700; color: #fff; letter-spacing: -0.01em; }
  .logo-text .brand-main span { color: var(--yellow); }
  .nav-section-label {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: rgba(255,255,255,0.25); padding: 16px 24px 6px;
  }
  .sidebar-nav { list-style: none; overflow-y: auto; padding: 0 12px; }
  .sidebar-nav::-webkit-scrollbar { width: 0; }
  .sidebar-nav li { margin-bottom: 2px; }
  .sidebar-nav a {
    display: flex; align-items: center; gap: 11px; padding: 11px 12px; text-decoration: none;
    color: rgba(255,255,255,0.6); font-size: 0.88rem; font-weight: 500;
    border-radius: var(--radius-sm); transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
    position: relative; overflow: hidden;
  }
  .sidebar-nav a .nav-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; background: rgba(255,255,255,0.05); flex-shrink: 0; transition: all 0.22s;
  }
  .sidebar-nav a:hover { color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.06); }
  .sidebar-nav a:hover .nav-icon { background: rgba(255,255,255,0.1); }
  .sidebar-nav a.active { background: rgba(245,197,24,0.14); color: var(--yellow); }
  .sidebar-nav a.active .nav-icon { background: rgba(245,197,24,0.2); color: var(--yellow); }
  .sidebar-nav a.active::before {
    content: ''; position: absolute; left: 0; top: 20%; bottom: 20%;
    width: 3px; background: var(--yellow); border-radius: 0 2px 2px 0;
  }
  .sidebar-footer { margin-top: auto; padding: 16px 12px 24px; border-top: 1px solid rgba(255,255,255,0.06); }
  .user-widget {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px;
    border-radius: var(--radius-sm); background: rgba(255,255,255,0.05); margin-bottom: 8px;
  }
  .user-widget img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(245,197,24,0.4); }
  .user-widget-info { flex: 1; min-width: 0; }
  .user-widget-name { font-size: 0.83rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .user-widget-role { font-size: 0.73rem; color: rgba(255,255,255,0.4); }
  .sidebar-logout-btn {
    display: flex; align-items: center; gap: 10px; padding: 9px 12px;
    color: rgba(255,255,255,0.4); font-size: 0.84rem; font-weight: 500;
    border-radius: var(--radius-sm); transition: all 0.22s; width: 100%;
    background: none; border: none; cursor: pointer; font-family: inherit;
  }
  .sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,0.08); }

  /* ══ MAIN ══ */
  .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

  /* ══ TOPBAR ══ */
  .topbar {
    background: var(--white); border-bottom: 1px solid var(--border);
    padding: 0 36px; height: 66px;
    display: flex; align-items: center; justify-content: space-between; gap: 20px;
    position: sticky; top: 0; z-index: 50;
    box-shadow: 0 1px 0 var(--border), 0 2px 8px rgba(13,17,40,0.04);
  }
  .topbar-left { display: flex; align-items: center; }
  .topbar-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-light); }
  .topbar-breadcrumb .sep { color: var(--border-strong); }
  .topbar-breadcrumb .current { color: var(--text); font-weight: 600; }
  .topbar-right { display: flex; align-items: center; gap: 12px; }
  .topbar-date {
    display: flex; align-items: center; gap: 7px; font-size: 0.8rem;
    color: var(--text-secondary); font-weight: 500; padding: 6px 12px;
    border-radius: 20px; background: var(--bg); border: 1px solid var(--border);
  }
  .topbar-icon-btn {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    background: var(--bg); border: 1.5px solid var(--border);
    color: var(--text-secondary); cursor: pointer; font-size: 0.9rem;
    transition: all 0.18s; position: relative;
  }
  .topbar-icon-btn:hover { background: var(--navy); color: #fff; border-color: var(--navy); }
  .topbar-avatar {
    width: 38px; height: 38px; border-radius: 10px; object-fit: cover;
    border: 2px solid var(--border); cursor: pointer; transition: border-color 0.18s;
  }
  .topbar-avatar:hover { border-color: var(--navy); }

  /* ══ CONTENT ══ */
  .content { padding: 32px 36px 56px; display: flex; flex-direction: column; gap: 24px; }

  .page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; animation: fadeUp 0.4s both; }
  .page-header-left { display: flex; flex-direction: column; gap: 4px; }
  .page-header-eyebrow {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: var(--blue);
  }
  .page-header-eyebrow::before { content: ''; width: 16px; height: 2px; background: var(--blue); border-radius: 2px; }
  .page-header h1 { font-size: 1.75rem; font-weight: 800; color: var(--text); letter-spacing: -0.03em; line-height: 1.1; }

  .btn-back {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px; background: var(--white); color: var(--text-secondary);
    border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.85rem;
    font-weight: 700; font-family: var(--font); cursor: pointer; text-decoration: none; transition: all 0.18s;
  }
  .btn-back:hover { background: var(--navy); color: #fff; border-color: var(--navy); }

  .stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; animation: fadeUp 0.4s both 0.06s; }
  .stat-card {
    background: var(--white); border-radius: var(--radius); border: 1.5px solid var(--border);
    padding: 22px 24px; display: flex; flex-direction: column; gap: 10px;
    box-shadow: var(--shadow-card); position: relative; overflow: hidden;
    transition: transform 0.22s, box-shadow 0.22s;
  }
  .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
  .stat-card-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
  }
  .stat-card-icon.yellow { background: #fff8e1; color: #b45309; }
  .stat-card-icon.green  { background: var(--green-bg); color: var(--green); }
  .stat-card-icon.blue   { background: var(--blue-bg); color: var(--blue); }
  .stat-card-val { font-size: 2rem; font-weight: 800; color: var(--text); letter-spacing: -0.04em; line-height: 1; }
  .stat-card-label { font-size: 0.78rem; color: var(--text-secondary); font-weight: 600; }

  .section-block {
    background: var(--white); border-radius: var(--radius); border: 1.5px solid var(--border);
    box-shadow: var(--shadow-card); overflow: hidden; animation: fadeUp 0.4s both 0.12s;
  }
  .section-block-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; border-bottom: 1px solid var(--border);
    background: linear-gradient(to right, #fafbff, var(--white));
  }
  .section-block-title { font-size: 0.95rem; font-weight: 800; color: var(--text); letter-spacing: -0.01em; }
  .section-block-sub { font-size: 0.78rem; color: var(--text-secondary); margin-top: 2px; }

  .room-form { padding: 22px 24px; display: flex; flex-direction: column; gap: 14px; max-width: 640px; }
  .device-form { display: flex; align-items: end; gap: 12px; padding: 18px 24px; max-width: 700px; }
  .device-form .room-field { flex: 1; }
  .device-actions { display: flex; gap: 8px; }
  .device-action { border: 1px solid var(--border); background: #fff; color: var(--text-secondary); border-radius: 7px; padding: 7px 10px; cursor: pointer; font: 600 0.75rem var(--font); }
  .device-action:hover { border-color: var(--blue-border); color: var(--blue); }
  .device-action:focus-visible, .device-form button:focus-visible { outline: 3px solid var(--blue-border); outline-offset: 2px; }
  .device-feedback { min-height: 22px; padding: 0 24px 12px; color: var(--text-secondary); font-size: 0.8rem; }
  .credential-dialog { width: min(560px, calc(100% - 32px)); margin: auto; border: 1px solid var(--border); border-radius: 12px; padding: 24px; color: var(--text); box-shadow: var(--shadow-md); }
  .credential-dialog::backdrop { background: rgba(11,22,64,0.45); }
  .credential-dialog h2 { font-size: 1.05rem; margin-bottom: 8px; }
  .credential-dialog p { color: var(--text-secondary); font-size: 0.82rem; margin-bottom: 14px; }
  .credential-value { display: block; overflow-wrap: anywhere; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 7px; font: 0.8rem var(--font-mono); }
  .credential-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
  .room-label {
    font-size: 0.7rem; font-weight: 800; letter-spacing: 0.1em;
    text-transform: uppercase; color: var(--text-secondary); margin-bottom: 6px; display: block;
  }
  .room-input {
    width: 100%; padding: 10px 13px; border-radius: var(--radius-sm);
    border: 1.5px solid var(--border); background: var(--bg);
    font-family: var(--font); font-size: 0.875rem; color: var(--text); outline: none; transition: all 0.18s;
  }
  .room-input:focus { border-color: var(--blue-border); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.07); }
  .room-textarea { min-height: 80px; resize: vertical; }
  .room-hint { font-size: 0.74rem; color: var(--text-light); margin-top: 5px; }
  .btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; background: var(--navy); color: #fff;
    border: none; border-radius: var(--radius-sm); font-size: 0.875rem;
    font-weight: 700; font-family: var(--font); cursor: pointer;
    box-shadow: 0 4px 14px rgba(11,22,64,0.25); transition: all 0.2s;
  }
  .btn-primary:hover { background: #152060; transform: translateY(-1px); }

  .manage-table { width: 100%; border-collapse: collapse; }
  .manage-table thead th {
    padding: 10px 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
    text-transform: uppercase; color: var(--text-light); background: var(--bg);
    border-bottom: 1px solid var(--border); text-align: left;
  }
  .manage-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
  .manage-table tbody tr:last-child { border-bottom: none; }
  .manage-table tbody tr:hover { background: #f8f9ff; }
  .manage-table td { padding: 13px 20px; font-size: 0.85rem; color: var(--text); vertical-align: middle; }
  .td-muted { color: var(--text-secondary); font-size: 0.8rem; }

  .chip {
    display: inline-flex; align-items: center; gap: 5px; font-size: 0.7rem; font-weight: 700;
    padding: 3px 10px; border-radius: 20px; letter-spacing: 0.04em;
    background: var(--bg); border: 1px solid var(--border); color: var(--text-secondary);
  }
  .chip.good { background: var(--green-bg); color: var(--green); border-color: var(--green-border); }
  .chip.bad  { background: var(--red-bg);   color: var(--red);   border-color: var(--red-border); }

  @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
  @media (max-width:1200px) { .stats-strip { grid-template-columns: repeat(2,1fr); } .content { padding: 24px 24px 48px; } }
  @media (max-width:768px)  { :root { --sidebar-w: 0px; } .sidebar { display: none; } }
  @media (max-width:600px) { .device-form { align-items: stretch; flex-direction: column; padding-inline: 16px; } }
  </style>
</head>
<body>

<!-- ════════════ SIDEBAR — SmartRoom (from classrooms index) ════════════ -->
<div class="sidebar">
  <a href="#" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Room</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main Menu</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ route('admin.classrooms') }}" class="active">
        <span class="nav-icon"><i class="fas fa-school"></i></span>
        Room Management
      </a>
    </li>
    <li>
      <a href="{{ url('/admin/schedule') }}">
        <span class="nav-icon"><i class="fas fa-calendar-days"></i></span>
        Schedule
      </a>
    </li>
    <li>
      <a href="{{ url('/admin/users') }}">
        <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
        User Management
      </a>
    </li>
    <li>
      <a href="{{ url('/smartlocking') }}">
        <span class="nav-icon"><i class="fas fa-lock"></i></span>
        SmartLocking
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'Admin User') }}&background=0ea5e9&color=fff" alt="{{ auth()->user()?->name ?? 'Admin User' }}">
      <div class="user-widget-info">
        <div class="user-widget-name">{{ auth()->user()?->name ?? 'Admin User' }}</div>
        <div class="user-widget-role">{{ ucfirst(auth()->user()?->role ?? 'admin') }}</div>
      </div>
    </div>
    <form method="POST" action="{{ url('/logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout-btn">
        <i class="fas fa-arrow-right-from-bracket"></i>
        Sign Out
      </button>
    </form>
  </div>
</div>

<!-- ════════════ MAIN ════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-breadcrumb">
        <span>Admin</span>
        <span class="sep">/</span>
        <a href="{{ route('admin.classrooms') }}" style="color:var(--text-light);text-decoration:none;">Room Management</a>
        <span class="sep">/</span>
        <span class="current">{{ $classroom->name }}</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date">
        <i class="fas fa-calendar"></i>
        <span>{{ \Carbon\Carbon::now()->format('M j, Y') }}</span>
      </div>
      <img src="https://randomuser.me/api/portraits/women/44.jpg" class="topbar-avatar" alt="Admin">
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-left">
        <div class="page-header-eyebrow">Room Management</div>
        <h1>{{ $classroom->name }}</h1>
      </div>
      <a class="btn-back" href="{{ route('admin.classrooms') }}">
        <i class="fas fa-arrow-left"></i> Back to Classrooms
      </a>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-strip">
      <div class="stat-card">
        <div class="stat-card-icon yellow"><i class="fas fa-building"></i></div>
        <div class="stat-card-val">{{ $classroom->building }}</div>
        <div class="stat-card-label">Building</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fas fa-layer-group"></i></div>
        <div class="stat-card-val">{{ $classroom->floor ?? '–' }}</div>
        <div class="stat-card-label">Floor</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon green"><i class="fas fa-user-group"></i></div>
        <div class="stat-card-val">{{ $classroom->current_occupancy }}/{{ $classroom->capacity }}</div>
        <div class="stat-card-label">Occupancy</div>
      </div>
    </div>

    <!-- ROOM MANAGEMENT FORM -->
    <div class="section-block">
      <div class="section-block-head">
        <div>
          <div class="section-block-title">Room Management</div>
          <div class="section-block-sub">Update status and issue details for this classroom</div>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.classrooms.update', $classroom->id) }}" class="room-form">
        @csrf
        @method('PATCH')
        <label class="room-field">
          <span class="room-label">Door access</span>
          <select name="access_mode" class="room-input" required>
            <option value="manual" {{ $classroom->access_mode === 'manual' ? 'selected' : '' }}>Manual access</option>
            <option value="esp32" {{ $classroom->access_mode === 'esp32' ? 'selected' : '' }}>ESP32-controlled</option>
          </select>
          <span class="room-hint">Schedules and reservations remain separate from device installation.</span>
        </label>
        <label class="room-field">
          <span class="room-label">Status</span>
          <select name="status" class="room-input" required>
            <option value="available"   {{ $classroom->status === 'available'   ? 'selected' : '' }}>Available</option>
            <option value="occupied"    {{ $classroom->status === 'occupied'    ? 'selected' : '' }}>Occupied</option>
            <option value="reserved"    {{ $classroom->status === 'reserved'    ? 'selected' : '' }}>Reserved</option>
            <option value="maintenance" {{ in_array($classroom->status, ['maintenance','unavailable'], true) ? 'selected' : '' }}>Unavailable (Issue / Maintenance)</option>
          </select>
        </label>
        <label class="room-field">
          <span class="room-label">Issue Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;">(required for unavailable)</span></span>
          <textarea name="unavailable_reason" rows="3" class="room-input room-textarea"
            placeholder="e.g., Electrical issue in panel board, AC repair ongoing, projector replacement pending"
          >{{ old('unavailable_reason', $classroom->unavailable_reason) }}</textarea>
          <div class="room-hint">This note appears in room availability so faculty know why the room is blocked.</div>
        </label>
        <div>
          <button type="submit" class="btn-primary">
            <i class="fas fa-floppy-disk" style="font-size:0.8rem;"></i>
            Save Room Settings
          </button>
        </div>
      </form>
    </div>

    <div class="section-block">
      <div class="section-block-head">
        <div>
          <div class="section-block-title">Door devices</div>
          <div class="section-block-sub">Devices belong to this room; no user account is created for a door.</div>
        </div>
        <span class="chip {{ $classroom->access_mode === 'esp32' ? 'good' : '' }}">{{ $classroom->access_mode === 'esp32' ? 'ESP32-controlled' : 'Manual access' }}</span>
      </div>
      @if($classroom->access_mode === 'esp32')
        <form class="device-form" id="registerDeviceForm" action="{{ route('admin.classrooms.devices.store', $classroom) }}">
          @csrf
          <label class="room-field">
            <span class="room-label">New device name</span>
            <input class="room-input" name="name" maxlength="255" placeholder="e.g. Main Door" required>
          </label>
          <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Register device</button>
        </form>
      @else
        <div class="room-form"><p class="room-hint">This room uses manual access. Change Door access above to register an ESP32.</p></div>
      @endif
      <div class="device-feedback" id="deviceFeedback" role="status" aria-live="polite"></div>
      <table class="manage-table">
        <thead><tr><th>Device</th><th>Status</th><th>Last seen</th><th>Actions</th></tr></thead>
        <tbody>
          @forelse($classroom->devices as $device)
            @php
              $deviceOnline = $device->status === 'active' && $device->last_seen_at?->gt(now()->subMinutes(5));
              $deviceLabel = $device->status !== 'active' ? 'Disabled' : ($deviceOnline ? 'Online' : ($device->last_seen_at ? 'Offline' : 'Awaiting connection'));
            @endphp
            <tr>
              <td>{{ $device->name }}</td>
              <td><span class="chip {{ $deviceOnline ? 'good' : 'bad' }}">{{ $deviceLabel }}</span></td>
              <td class="td-muted">{{ $device->last_seen_at?->diffForHumans() ?? 'Never connected' }}</td>
              <td>
                <div class="device-actions">
                  <button type="button" class="device-action js-rotate-device" data-url="{{ route('admin.classrooms.devices.credential', [$classroom, $device]) }}" title="Rotate device credential"><i class="fas fa-key"></i> Rotate key</button>
                  <button type="button" class="device-action js-toggle-device" data-url="{{ route('admin.classrooms.devices.status', [$classroom, $device]) }}" data-status="{{ $device->status === 'active' ? 'inactive' : 'active' }}">{{ $device->status === 'active' ? 'Disable' : 'Enable' }}</button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="td-muted" style="padding:20px 24px;">{{ $classroom->access_mode === 'esp32' ? 'No door device installed yet.' : 'No ESP32 device is needed for manual access.' }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- SCHEDULES TABLE -->
    <div class="section-block">
      <div class="section-block-head">
        <div>
          <div class="section-block-title">Schedules</div>
          <div class="section-block-sub">All scheduled classes for this room</div>
        </div>
      </div>
      <table class="manage-table">
        <thead>
          <tr><th>Start</th><th>End</th><th>Course</th><th>Instructor</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          @forelse($classroom->schedules as $schedule)
            <tr>
              <td>{{ optional($schedule->start_at)->format('M d, Y H:i') }}</td>
              <td>{{ optional($schedule->end_at)->format('M d, Y H:i') }}</td>
              <td>{{ $schedule->course?->code }} – {{ $schedule->course?->title }}</td>
              <td>{{ $schedule->course?->instructor?->name }}</td>
              <td><span class="chip">{{ $schedule->status }}</span></td>
              <td>
                <button data-id="{{ $schedule->id }}" class="btn-link delete-schedule-btn" title="Delete schedule">
                  <i class="fas fa-trash" style="color:#ef4444"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="td-muted" style="padding:24px 20px;">No schedules assigned.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <dialog class="credential-dialog" id="credentialDialog" aria-labelledby="credentialDialogTitle">
      <h2 id="credentialDialogTitle">Device credential</h2>
      <p>Copy this credential into the device config now. It is shown only once; rotate it if you lose it.</p>
      <code class="credential-value" id="credentialValue"></code>
      <div class="credential-dialog-actions">
        <button type="button" class="device-action" id="copyCredentialBtn"><i class="fas fa-copy"></i> Copy</button>
        <button type="button" class="btn-primary" id="closeCredentialDialog">Done</button>
      </div>
    </dialog>

    <script>
      (function () {
        var csrfToken = '{{ csrf_token() }}';
        var feedback = document.getElementById('deviceFeedback');
        var credentialDialog = document.getElementById('credentialDialog');
        var credentialValue = document.getElementById('credentialValue');

        function showCredential(credential) {
          credentialValue.textContent = credential;
          credentialDialog.showModal();
        }

        async function readResponse(response) {
          var payload = await response.json().catch(function () { return {}; });
          if (!response.ok) throw new Error(payload.message || 'Request failed.');
          return payload;
        }

        document.getElementById('registerDeviceForm')?.addEventListener('submit', async function (event) {
          event.preventDefault();
          var form = event.currentTarget;
          var submit = form.querySelector('button[type="submit"]');
          submit.disabled = true;
          feedback.textContent = 'Registering device…';
          try {
            var response = await fetch(form.action, {
              method: 'POST',
              credentials: 'same-origin',
              headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
              body: JSON.stringify({ name: new FormData(form).get('name') })
            });
            var payload = await readResponse(response);
            showCredential(payload.data.credential);
            form.reset();
            feedback.textContent = 'Device registered. Credential shown once.';
          } catch (error) {
            feedback.textContent = error.message;
          } finally {
            submit.disabled = false;
          }
        });

        document.querySelectorAll('.js-rotate-device').forEach(function (button) {
          button.addEventListener('click', async function () {
            if (!confirm('Rotate this device credential? The old credential will stop working immediately.')) return;
            button.disabled = true;
            try {
              var response = await fetch(button.dataset.url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
              });
              var payload = await readResponse(response);
              showCredential(payload.data.credential);
              feedback.textContent = 'Credential rotated.';
            } catch (error) {
              feedback.textContent = error.message;
            } finally {
              button.disabled = false;
            }
          });
        });

        document.querySelectorAll('.js-toggle-device').forEach(function (button) {
          button.addEventListener('click', async function () {
            button.disabled = true;
            try {
              var response = await fetch(button.dataset.url, {
                method: 'PATCH', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ status: button.dataset.status })
              });
              await readResponse(response);
              window.location.reload();
            } catch (error) {
              feedback.textContent = error.message;
              button.disabled = false;
            }
          });
        });

        document.getElementById('copyCredentialBtn')?.addEventListener('click', async function () {
          try {
            if (navigator.clipboard && window.isSecureContext) {
              await navigator.clipboard.writeText(credentialValue.textContent);
              feedback.textContent = 'Credential copied.';
              return;
            }

            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(credentialValue);
            selection.removeAllRanges();
            selection.addRange(range);

            if (document.execCommand('copy')) {
              selection.removeAllRanges();
              feedback.textContent = 'Credential copied.';
            } else {
              feedback.textContent = 'Credential selected. Copy it with Ctrl+C.';
            }
          } catch (error) {
            feedback.textContent = 'Credential selected. Copy it with Ctrl+C.';
          }
        });
        document.getElementById('closeCredentialDialog')?.addEventListener('click', function () {
          credentialDialog.close();
          window.location.reload();
        });
      })();

      (function () {
        var table = document.querySelector('.manage-table');
        table?.addEventListener('click', function (ev) {
          var el = ev.target;
          // find button
          while (el && !el.classList?.contains('delete-schedule-btn')) el = el.parentElement;
          if (!el) return;
          var id = el.getAttribute('data-id');
          if (!id) return;
          if (!confirm('Delete this schedule? This action cannot be undone.')) return;

          var tr = el.closest('tr');
          fetch('/api/v1/schedules/' + encodeURIComponent(id), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
          }).then(function (res) {
            if (!res.ok) return res.json().then(function (j) { throw j; });
            return res.json();
          }).then(function () {
            if (tr) tr.remove();
          }).catch(function (err) {
            console.error('Failed to delete schedule', err);
            alert('Failed to delete schedule. Check console for details.');
          });
        });
      })();
    </script>

    <!-- ACCESS LOGS TABLE -->
    <div class="section-block">
      <div class="section-block-head">
        <div>
          <div class="section-block-title">Recent Access Logs</div>
          <div class="section-block-sub">Entry and exit events for this classroom</div>
        </div>
      </div>
      <table class="manage-table">
        <thead>
          <tr><th>Time</th><th>User</th><th>Card</th><th>Direction</th><th>Result</th></tr>
        </thead>
        <tbody>
          @forelse($classroom->accessLogs as $log)
            <tr>
              <td>{{ optional($log->accessed_at)->format('M d, Y H:i') }}</td>
              <td>{{ $log->user?->name ?? 'Unknown' }}</td>
              <td>{{ $log->accessCard?->card_number ?? '–' }}</td>
              <td>{{ $log->direction }}</td>
              <td>
                <span class="chip {{ $log->result === 'granted' ? 'good' : 'bad' }}">
                  {{ $log->result }}
                </span>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="td-muted" style="padding:24px 20px;">No access logs for this classroom.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

</body>
</html>