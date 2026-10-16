@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$stats = [
    ['icon' => 'bi-book', 'value' => $todayClassesCount ?? 0, 'label' => "Today's Classes", 'color' => 'text-warning'],
    ['icon' => 'bi-clock', 'value' => $nextClassTime ?? 'N/A', 'label' => 'Next Class', 'color' => 'text-secondary'],
    ['icon' => 'bi-building', 'value' => $availableRoomsCount ?? 0, 'label' => 'Available Rooms', 'color' => 'text-success', 'id' => 'available-rooms-count'],
];
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => true, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => false, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => false, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => false, 'route' => 'student.profile'],
];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartDoor - Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
/* Sidebar */
    #sidebar { width: 230px; min-height: 100vh; background: #fff; border-right: 1px solid #e8eaf0; }
    .brand-icon { background: var(--gold); border-radius: 10px; width: 42px; height: 42px; display:grid; place-items:center; }
    .nav-link { color: #555; border-radius: 10px; padding: .55rem 1rem; font-weight: 500; }
    .nav-link:hover, .nav-link.active { background: #F0F4FF; color: var(--navy); }
    .nav-link.active::after { content:''; display:inline-block; width:7px; height:7px; background:var(--navy); border-radius:50%; margin-left:auto; }
    .avatar { width:38px; height:38px; background:var(--navy); border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:700; font-size:.85rem; }

    /* ═══════════════════════════════════════════════
       MAIN CONTENT - scoped so the sidebar is untouched
    ═══════════════════════════════════════════════ */
    main {
      --panel-border: #e7e9f0;
      --panel-shadow: 0 1px 3px rgba(15,23,41,.05);
      --panel-shadow-hover: 0 10px 24px rgba(15,23,41,.08);
      --muted: #8891a3;
      --muted-2: #b0b8c7;
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
    }
    main h1, main h2, main h3, main h4, main h5, main h6 {
      font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
      font-weight: 700; letter-spacing: -0.015em; color: #0f1729;
    }
    main .text-muted { color: var(--muted) !important; }
    main .badge { border-radius: 999px; font-weight: 600; letter-spacing: .01em; }
    main .btn { border-radius: 10px; transition: all .2s cubic-bezier(.4,0,.2,1); border: 1px solid transparent; font-weight: 600; }
    main .bg-white { background-color: #fff !important; }
    main .rounded-4 { border-radius: 16px !important; }
    main .border { border: 1px solid var(--panel-border) !important; }
    main .shadow-sm { box-shadow: var(--panel-shadow) !important; }

    /* Topbar */
    .home-topbar-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.05rem; color:#0f1729; }
    .home-topbar-pill {
      display:inline-flex; align-items:center; gap:6px;
      font-size:.76rem; font-weight:600; color: var(--navy);
      background: #f0f2fb; border: 1px solid #e2e6f7;
      padding: 6px 14px; border-radius: 999px;
    }
    .home-topbar-pill i { color: var(--gold-dark, #b7860b); }

    /* ── Hero ─────────────────────────────────────── */
    .student-home-hero {
      position:relative; overflow:hidden;
      margin: 0 -24px 24px; padding: 30px 24px 26px;
      color:#111827;
      background:
        radial-gradient(circle at 88% -10%, rgba(245,197,24,0.14) 0%, transparent 42%),
        radial-gradient(circle at 4% 120%, rgba(11,22,64,0.05) 0%, transparent 45%),
        #fff;
      border-bottom: 1px solid var(--panel-border);
      box-shadow: 0 1px 0 rgba(15,23,41,.02);
    }
    .student-home-hero-copy { position:relative; z-index:1; margin-bottom:24px; display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .student-home-hero h1 { color:#0f1729; font-size:1.6rem; margin-bottom:4px; }
    .student-home-hero h1 span { color: var(--navy); }
    .student-home-hero .hero-subtitle { color: var(--muted); font-size:.9rem; }
    .hero-id-chip {
      display:inline-flex; align-items:center; gap:8px;
      font-size:.78rem; font-weight:600; color:#374151;
      background:#fff; border:1px solid var(--panel-border);
      padding:8px 14px; border-radius:999px; box-shadow: var(--panel-shadow);
    }
    .hero-id-chip i { color: var(--navy); }

    .student-home-stats { position:relative; z-index:1; padding:0; background:transparent; border:none; box-shadow:none; }

    /* ── Stat cards ───────────────────────────────── */
    .stat-card {
      border-radius: 16px; border: 1px solid var(--panel-border); background: #fff;
      box-shadow: var(--panel-shadow); padding: 18px 20px; position:relative; overflow:hidden;
      transition: all .22s cubic-bezier(.4,0,.2,1);
    }
    .stat-card:hover { border-color: #d7dcea; box-shadow: var(--panel-shadow-hover); transform: translateY(-2px); }
    .stat-card::after {
      content:''; position:absolute; top:-26px; right:-22px; width:96px; height:96px; border-radius:50%;
      background: var(--tint, rgba(11,22,64,.05)); pointer-events:none;
    }
    .stat-card[data-tone="amber"]  { --tint: rgba(245,197,24,.18); }
    .stat-card[data-tone="navy"]   { --tint: rgba(11,22,64,.07); }
    .stat-card[data-tone="green"]  { --tint: rgba(15,157,88,.14); }

    .stat-icon-chip {
      width: 40px; height: 40px; border-radius: 11px;
      display:flex; align-items:center; justify-content:center;
      font-size: 1.05rem; position:relative; z-index:1; margin-bottom: 14px;
    }
    .stat-card[data-tone="amber"] .stat-icon-chip { background:#fdf3d6; color:#a9760c; }
    .stat-card[data-tone="navy"]  .stat-icon-chip { background:#e9ecfa; color: var(--navy); }
    .stat-card[data-tone="green"] .stat-icon-chip { background:#e2f7ec; color:#0f9d58; }

    .stat-card .stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.7rem; font-weight:800; color:#0f1729; line-height:1; position:relative; z-index:1; }
    .stat-card .stat-label { color: var(--muted); font-size:.8rem; font-weight:500; margin-top:6px; position:relative; z-index:1; }

    /* ── Next Class Banner ────────────────────────── */
    .next-banner {
      position:relative; overflow:hidden;
      background: linear-gradient(120deg, var(--gold) 0%, #ffd94d 100%);
      border-radius: 16px; border: 1px solid #ecd27a;
      box-shadow: 0 10px 26px rgba(245,197,24,.28);
    }
    .next-banner::after {
      content:''; position:absolute; top:-40px; right:-30px; width:190px; height:190px; border-radius:50%;
      background: rgba(255,255,255,.22); pointer-events:none;
    }
    .next-banner > * { position:relative; z-index:1; }
    .next-banner .eyebrow { font-size:.72rem; font-weight:800; letter-spacing:.12em; color: var(--navy); opacity:.8; }
    .next-banner .btn-view { background: var(--navy); color: #fff; border-radius: 10px; flex:1; }
    .next-banner .btn-view:hover { background:#141f52; }
    .next-banner .btn-nav  { background: rgba(255,255,255,.55); color: var(--navy); border-radius: 10px; font-weight:600; border: 1px solid rgba(11,22,64,.14); backdrop-filter: blur(4px); }
    .next-banner .btn-nav:hover { background: rgba(255,255,255,.8); }

    /* ── Panels ───────────────────────────────────── */
    .panel-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:.95rem; color:#0f1729; }

    /* ── Schedule rows ────────────────────────────── */
    .schedule-row {
      border-radius: 14px; transition: all .2s cubic-bezier(.4,0,.2,1);
      border: 1px solid transparent; position:relative;
    }
    .schedule-row:hover { background: #f8f9fd; border-color: var(--panel-border); transform: translateX(2px); }
    .time-badge {
      background: #f5f6fa; border-radius: 10px; font-size: .7rem; font-weight: 800; color: var(--navy);
      line-height:1.15; width: 54px; text-align:center; padding: 7px 0; border: 1px solid var(--panel-border);
    }
    .time-badge.pm { color: var(--text-3, #7c8a9e); background: var(--bg, #f0f2f8); }
    .schedule-row .course-badge { background:#f0f2fb; color:#374151; font-weight:600; }
    .schedule-row .soon-badge { background:#fdf3d6; color:#a9760c; }

    /* ── Quick actions ────────────────────────────── */
    .qa-card {
      border-radius: 14px; border: 1px solid var(--panel-border); background: #fff; cursor:pointer;
      transition: all .2s cubic-bezier(.4,0,.2,1); box-shadow: var(--panel-shadow); padding: 16px;
    }
    .qa-card:hover { box-shadow: var(--panel-shadow-hover); border-color: #d7dcea; transform: translateY(-2px); }
    .qa-icon { width:44px; height:44px; border-radius:11px; display:grid; place-items:center; font-size:1.2rem; background: #e9ecfa; color: var(--navy); flex-shrink:0; }
    .qa-arrow { margin-left:auto; color: var(--muted-2); transition: transform .2s; }
    .qa-card:hover .qa-arrow { transform: translateX(3px); color: var(--navy); }

    .notice-card { background: #fff; border-radius: 14px; border: 1px solid var(--panel-border); box-shadow: var(--panel-shadow); padding: 16px; }
    .notice-card .qa-icon { background:#e6f0fe; color:#1d4ed8; }

    .student-notification-panel {
      background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
      border: 1px solid var(--panel-border);
      border-radius: 18px;
      box-shadow: var(--panel-shadow);
      overflow: hidden;
      margin-bottom: 24px;
    }
    .student-notification-header {
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      padding: 18px 20px 14px; border-bottom: 1px solid var(--panel-border);
    }
    .student-notification-header h2 {
      margin: 0; font-size: 1rem; color: #0f1729; font-family:'Plus Jakarta Sans',sans-serif;
    }
    .student-notification-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: #eef6ff; color: #1d4ed8; border: 1px solid #dbeafe; border-radius: 999px;
      padding: 6px 10px; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    }
    .student-notification-grid {
      display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 18px 20px 20px;
    }
    .student-notification-item {
      display: flex; gap: 12px; align-items: flex-start; padding: 14px 12px; border-radius: 14px;
      background: #fff; border: 1px solid var(--panel-border); transition: all .2s ease;
    }
    .student-notification-item:hover { transform: translateY(-2px); border-color: #d9e1f4; box-shadow: 0 8px 20px rgba(15,23,41,.05); }
    .student-notification-icon {
      width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; background: #eef2ff; color: var(--navy); font-size: 1rem; flex-shrink: 0;
    }
    .student-notification-item[data-type="schedule"] .student-notification-icon { background: #fff1d8; color: #b7791f; }
    .student-notification-item[data-type="room"] .student-notification-icon { background: #e0f2fe; color: #0f766e; }
    .student-notification-item[data-type="attendance"] .student-notification-icon { background: #ecfdf5; color: #15803d; }
    .student-notification-content { min-width: 0; }
    .student-notification-title { font-size: .87rem; font-weight: 700; color: #111827; margin-bottom: 4px; }
    .student-notification-body { font-size: .78rem; color: var(--muted); line-height: 1.6; }
    .student-notification-meta {
      display: inline-flex; align-items: center; margin-top: 8px; font-size: .68rem; color: var(--navy); font-weight: 700; letter-spacing: .02em;
      background: #f3f7ff; border: 1px solid #dde7ff; border-radius: 999px; padding: 4px 8px;
    }
    .student-notification-item a { text-decoration: none; color: inherit; }
    .student-notification-item[data-type="announcement"] .student-notification-icon { background: #eef6ff; color: #1d4ed8; }
    .student-notification-empty { grid-column: 1 / -1; padding: 26px 20px; text-align: center; color: var(--muted); }
    .student-notification-empty i { display: block; margin-bottom: 8px; font-size: 1.35rem; color: var(--muted-2); }

    .home-empty { padding: 2.4rem 1rem; text-align: center; color: var(--muted); }
    .home-empty-icon { width: 48px; height: 48px; margin: 0 auto 12px; display: grid; place-items: center; border-radius: 14px; background: #f5f6fa; color: var(--muted-2); font-size: 1.35rem; border: 1px solid var(--panel-border); }

    @media (max-width:768px) {
      .student-home-hero { margin:0 -16px 20px; padding:24px 16px; }
      .student-home-hero-copy { align-items:flex-start; margin-bottom:16px; }
      .student-home-stats > .row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-inline: 0;
      }
      .student-home-stats > .row > .col-md-4 {
        width: auto;
        max-width: none;
        min-width: 0;
        flex: none;
        padding: 0;
      }
      .student-home-stats .stat-card {
        height: 100%;
        min-height: 104px;
        padding: 11px 9px;
        border-radius: 11px;
      }
      .student-home-stats .stat-card::after {
        top: -18px;
        right: -17px;
        width: 58px;
        height: 58px;
        opacity: .75;
      }
      .student-home-stats .stat-icon-chip {
        width: 28px;
        height: 28px;
        margin-bottom: 8px;
        border-radius: 8px;
        font-size: .85rem;
      }
      .student-home-stats .stat-card .stat-value {
        font-size: 1.3rem;
      }
      .student-home-stats .stat-card .stat-label {
        min-height: 2.3em;
        margin-top: 5px;
        font-size: .75rem;
        line-height: 1.15;
      }
      .student-notification-grid { grid-template-columns: 1fr; padding: 14px; }
      .student-notification-header { padding: 16px; }
    }
    @media (max-width: 768px) { #sidebar { width: 100%; min-height: auto; } body > .d-flex { display: block !important; } main { padding: 1.25rem !important; } }
  </style>
  <style>
    .is-summary-loading { color: transparent !important; min-width: 2.5rem; min-height: 2rem; border-radius: 6px; background: linear-gradient(90deg,#f3f4f6 25%,#fff 50%,#f3f4f6 75%); background-size: 200% 100%; animation: summarySkeleton 1.35s ease-in-out infinite; }
    @keyframes summarySkeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    @media (prefers-reduced-motion: reduce) { .is-summary-loading { animation: none; background: #f3f4f6; } }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!-- Main -->
  <main class="flex-grow-1 p-4">

    <!-- Topbar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <div class="home-topbar-title">Home</div>
        <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
      </div>
      <span class="home-topbar-pill"><i class="bi bi-person-check"></i>Student dashboard</span>
    </div>

    <section class="student-home-hero">
      <div class="student-home-hero-copy">
        <div>
          <h1 class="fw-bold mb-0">Good morning, <span>{{ $firstName }}</span>!</h1>
          <p class="hero-subtitle mb-0">Here's your schedule for today</p>
        </div>
        <span class="hero-id-chip"><i class="bi bi-person-vcard"></i>{{ $studentId }}</span>
      </div>
      <div class="student-home-stats">
        <div class="row g-3 mb-0">
          <div class="col-md-4">
            <div class="stat-card" data-tone="amber">
              <div class="stat-icon-chip"><i class="bi bi-book"></i></div>
              <div class="stat-value" id="today-classes-count">{{ $todayClassesCount ?? 0 }}</div>
              <div class="stat-label">Today's Classes</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card" data-tone="navy">
              <div class="stat-icon-chip"><i class="bi bi-clock"></i></div>
              <div class="stat-value" id="next-class-time">{{ $nextClassTime ?? 'N/A' }}</div>
              <div class="stat-label">Next Class</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card" data-tone="green">
              <div class="stat-icon-chip"><i class="bi bi-building"></i></div>
              <div class="stat-value" id="available-rooms-count">{{ $availableRoomsCount ?? 0 }}</div>
              <div class="stat-label">Available Rooms</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="student-notification-panel" aria-label="Student notifications">
      <div class="student-notification-header">
        <h2>Campus updates</h2>
        <span class="student-notification-badge"><i class="bi bi-bell-fill"></i> {{ $studentNotifications->count() }} updates</span>
      </div>
      <div class="student-notification-grid">
        @forelse ($studentNotifications as $notification)
          @php
            $notificationType = strtolower((string) $notification->type);
            $notificationCategory = match (true) {
              str_contains($notificationType, 'schedule'), str_contains($notificationType, 'class_cancel') => 'schedule',
              str_contains($notificationType, 'room'), str_contains($notificationType, 'reservation') => 'room',
              str_contains($notificationType, 'attendance') => 'attendance',
              str_contains($notificationType, 'announcement') => 'announcement',
              default => 'info',
            };
            $notificationIcon = match ($notificationCategory) {
              'schedule' => str_contains($notificationType, 'cancel') ? 'bi-calendar-x' : 'bi-calendar3',
              'room' => 'bi-door-open',
              'attendance' => 'bi-clipboard-check',
              'announcement' => 'bi-megaphone',
              default => 'bi-info-circle',
            };
            $notificationLabel = ucfirst($notificationCategory === 'info' ? 'update' : $notificationCategory);
          @endphp
          <article class="student-notification-item" data-type="{{ $notificationCategory }}">
            <div class="student-notification-icon"><i class="bi {{ $notificationIcon }}"></i></div>
            <div class="student-notification-content">
              <div class="student-notification-title">{{ $notification->title }}</div>
              @if ($notification->body)
                <div class="student-notification-body">{{ $notification->body }}</div>
              @endif
              <span class="student-notification-meta">{{ $notificationLabel }} · {{ $notification->created_at?->diffForHumans() }}</span>
            </div>
          </article>
        @empty
          <div class="student-notification-empty">
            <i class="bi bi-bell-slash" aria-hidden="true"></i>
            <div>No campus updates yet.</div>
          </div>
        @endforelse
      </div>
    </section>

    <!-- Next Class Banner -->
    @if (!empty($todaySchedules) && $todaySchedules->count() > 0)
      @php
        $nextClass = $todaySchedules->first(fn($s) => \Carbon\Carbon::parse($s->start_at)->greaterThan(now()));
      @endphp
      @if ($nextClass)
        <div class="next-banner p-4 mb-4">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="eyebrow">NEXT CLASS</span>
            <span class="badge rounded-pill" style="background:var(--navy);padding:.5rem .9rem">Starts in {{ now()->diffInMinutes(\Carbon\Carbon::parse($nextClass->start_at)) }} min</span>
          </div>
          <h3 class="fw-bold mb-1" style="color:var(--navy)">{{ $nextClass->course_code ?? 'Course' }}</h3>
          <p class="mb-3" style="color:var(--navy);opacity:.75">{{ $nextClass->block_section ?? 'N/A' }} • {{ $nextClass->faculty_name ?? 'Faculty' }}</p>
          <div class="d-flex gap-3 mb-3 small" style="color:var(--navy)">
            <span><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($nextClass->start_at)->format('g:i A') }} - {{ \Carbon\Carbon::parse($nextClass->end_at ?? $nextClass->start_at)->format('g:i A') }}</span>
            <span><i class="bi bi-geo-alt me-1"></i>{{ $nextClass->classroom_name ?? 'Room TBA' }}</span>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('student.checkingRoom') }}" class="btn btn-view py-2 px-4">View Room Details</a>
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(($nextClass->classroom_name ?? 'Classroom') . ' ' . ($nextClass->building ?? '')) }}" class="btn btn-nav py-2 px-4" target="_blank" rel="noopener">Navigate</a>
          </div>
        </div>
      @endif
    @endif

    <!-- Schedule + Quick Actions -->
    <div class="row g-4">

      <!-- Today's Schedule -->
      <div class="col-lg-7">
        <div class="bg-white rounded-4 p-4 border">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="panel-title mb-0">Today's Schedule</h2>
          </div>
          @php($todayRows = collect($todaySchedules ?? []))
          @forelse ($todayRows as $row)
            <a href="{{ route('student.schedule') }}" class="schedule-row d-flex align-items-center gap-3 p-2 mb-2 text-decoration-none text-reset">
              <div class="time-badge {{ \Carbon\Carbon::parse($row->start_at)->greaterThan(now()) ? 'am' : 'pm' }}">
                {{ \Carbon\Carbon::parse($row->start_at)->format('H:i') }}<br>{{ \Carbon\Carbon::parse($row->start_at)->format('A') }}
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold small">
                  {{ $row->course?->code ?? $row->course_code ?? 'Course' }}
                  <span class="badge course-badge ms-1">{{ $row->block_section ?? 'N/A' }}</span>
                  @if (\Carbon\Carbon::parse($row->start_at)->greaterThan(now()))
                    <span class="badge soon-badge ms-1">SOON</span>
                  @endif
                </div>
                <div class="text-muted" style="font-size:.75rem">
                  <i class="bi bi-person me-1"></i>{{ $row->course?->instructor?->name ?? $row->faculty_name ?? 'Faculty' }}
                  <i class="bi bi-geo-alt ms-2 me-1"></i>{{ $row->classroom?->name ?? $row->classroom_name ?? 'Room TBA' }}
                </div>
              </div>
              <i class="bi bi-chevron-right text-muted"></i>
            </a>
          @empty
            <div class="home-empty">
              <div class="home-empty-icon"><i class="bi bi-calendar-check"></i></div>
              <div class="fw-semibold text-dark">No enrolled classes today</div>
              <p class="small mb-3">Your schedule is clear for today.</p>
            </div>
          @endforelse
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="col-lg-5">
        <h2 class="panel-title mb-3">Quick Actions</h2>

        <a href="{{ route('student.checkingRoom') }}" class="qa-card mb-3 d-flex align-items-center gap-3" style="text-decoration: none; color: inherit;">
          <div class="qa-icon"><i class="bi bi-building"></i></div>
          <div><div class="fw-semibold small">Find Available Rooms</div><div class="text-muted" style="font-size:.75rem">Search for vacant classrooms</div></div>
          <i class="bi bi-arrow-right qa-arrow"></i>
        </a>

        <a href="{{ route('student.courses') }}" class="qa-card mb-3 d-flex align-items-center gap-3" style="text-decoration: none; color: inherit;">
          <div class="qa-icon"><i class="bi bi-journal-bookmark"></i></div>
          <div><div class="fw-semibold small">Browse Courses</div><div class="text-muted" style="font-size:.75rem">Review available subjects</div></div>
          <i class="bi bi-arrow-right qa-arrow"></i>
        </a>

        <a href="{{ route('student.attendance') }}" class="qa-card mb-3 d-flex align-items-center gap-3" style="text-decoration: none; color: inherit;">
          <div class="qa-icon"><i class="bi bi-clipboard-check"></i></div>
          <div><div class="fw-semibold small">Check Attendance</div><div class="text-muted" style="font-size:.75rem">View records or check in</div></div>
          <i class="bi bi-arrow-right qa-arrow"></i>
        </a>

        <div class="notice-card">
          <div class="d-flex gap-2 align-items-start">
            <div class="qa-icon flex-shrink-0"><i class="bi bi-info-circle"></i></div>
            <div>
              <div class="fw-semibold small">Stay up to date</div>
              <p class="text-muted mb-0" style="font-size:.78rem">Room changes and attendance updates will appear here when they are available.</p>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /row -->
  </main>
</div>
<script>
  (function () {
    var todayClassesCount = document.getElementById('today-classes-count');
    var nextClassTime = document.getElementById('next-class-time');
    var availableRoomsCount = document.getElementById('available-rooms-count');
    var homeSummaryLoaded = false;

    async function refreshHomeSummary() {
      if (!todayClassesCount && !nextClassTime && !availableRoomsCount) {
        return;
      }

      if (!homeSummaryLoaded) {
        [todayClassesCount, nextClassTime, availableRoomsCount].forEach(function (element) {
          element?.classList.add('is-summary-loading');
        });
      }

      try {
        var response = await fetch('{{ route('student.home.summary') }}', {
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          return;
        }

        var payload = await response.json();
        if (!payload.success) return;
        if (todayClassesCount) todayClassesCount.textContent = String(payload.today_classes);
        if (nextClassTime) nextClassTime.textContent = payload.next_class;
        if (availableRoomsCount) availableRoomsCount.textContent = String(payload.available_rooms);
        [todayClassesCount, nextClassTime, availableRoomsCount].forEach(function (element) {
          element?.classList.remove('is-summary-loading');
        });
        homeSummaryLoaded = true;
      } catch (error) {
      } finally {
        [todayClassesCount, nextClassTime, availableRoomsCount].forEach(function (element) {
          element?.classList.remove('is-summary-loading');
        });
        homeSummaryLoaded = true;
      }
    }

    refreshHomeSummary();
    setInterval(refreshHomeSummary, 10000);
  }());
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>