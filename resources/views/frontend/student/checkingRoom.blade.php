@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => false, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => true, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => false, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => false, 'route' => 'student.profile'],
];
$availableRoomsCount = $classrooms->where('status', 'available')->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SmartDoor - Rooms</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --gold: #f5c518; --navy: #0b1640; }
    body { background: #f8f9fb; font-family: 'Segoe UI', sans-serif; color: #111827; }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.01em; color: #111827; }

    /* Sidebar */
    #sidebar{width:220px;min-height:100vh;background:#fff;border-right:1px solid #e8eaf0;}
    .brand-icon{background:var(--gold);border-radius:10px;width:40px;height:40px;display:grid;place-items:center;}
    .nav-link{color:#555;border-radius:8px;padding:.5rem 1rem;font-weight:500;}
    .nav-link:hover,.nav-link.active{background:#F0F4FF;color:var(--navy);}
    .nav-link.active::after{content:'';display:inline-block;width:7px;height:7px;background:var(--navy);border-radius:50%;margin-left:auto;}
    .avatar{width:36px;height:36px;background:var(--navy);border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:.8rem;}

    /* ═══════════════════════════════════════════════
       MAIN CONTENT - scoped so the sidebar is untouched
    ═══════════════════════════════════════════════ */
    main {
      --panel-border: #e7e9f0;
      --panel-shadow: 0 1px 3px rgba(15,23,41,.05);
      --panel-shadow-hover: 0 10px 24px rgba(15,23,41,.08);
      --muted: #8891a3;
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
    }
    main h1, main h2, main h3, main h4, main h5, main h6 {
      font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
      font-weight: 700; letter-spacing: -0.015em; color: #0f1729;
    }
    main .text-muted { color: var(--muted) !important; }

    .home-topbar-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.05rem; color:#0f1729; }

    /* ── Campus status banner ─────────────────────── */
    .campus-banner {
      position:relative; overflow:hidden;
      background:
        radial-gradient(circle at 92% 0%, rgba(245,197,24,0.16) 0%, transparent 48%),
        #fff;
      border-radius: 16px; border: 1px solid var(--panel-border);
      box-shadow: var(--panel-shadow); color: #111827;
    }
    .campus-banner > * { position:relative; z-index:1; }
    .campus-banner .eyebrow { font-size:.72rem; font-weight:800; letter-spacing:.1em; color: var(--navy); opacity:.7; }
    .campus-banner h2 { color: #0f1729; font-size:1.85rem; }
    .campus-banner .sub-line { color: var(--muted); font-size:.85rem; }
    .campus-icon {
      background: #fdf3d6; border-radius: 14px; width: 60px; height: 60px;
      display: grid; place-items: center; font-size: 1.7rem; color: #a9760c;
      border: 1px solid #f3e2ab;
    }

    /* ── Search & filters ─────────────────────────── */
    #room-search {
      border-radius: 0 12px 12px 0 !important; padding-top:.7rem; padding-bottom:.7rem;
      border-color: var(--panel-border) !important;
    }
    .input-group .input-group-text {
      border-radius: 12px 0 0 12px !important; border-color: var(--panel-border) !important;
    }
    #room-search:focus { box-shadow: 0 0 0 3px rgba(11,22,64,.08); border-color:#c7cde0 !important; }
    .filters-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .room-filter {
      border-radius: 999px !important; font-size:.8rem; padding:.4rem 1rem !important;
      border-color: var(--panel-border) !important; transition: all .18s;
    }
    .room-filter:not([style*="background"]) { background:#fff; color:#4b5566; }
    .room-filter:hover { border-color:#c7cde0 !important; }

    /* ── Campus building availability ───────────────── */
    .campus-building-panel { padding:0; margin-bottom:20px; overflow:hidden; background:#fff; border:1px solid var(--panel-border); border-radius:14px; box-shadow:var(--panel-shadow); }
    .campus-building-header { display:flex; align-items:center; justify-content:space-between; gap:16px; min-height:104px; padding:18px 20px; background:linear-gradient(100deg,rgba(11,22,64,.88),rgba(11,22,64,.42)),url('/images/map.png') center 46%/cover no-repeat; }
    .campus-building-title-group { display:flex; align-items:center; gap:12px; min-width:0; }
    .campus-building-icon { display:grid; width:40px; height:40px; flex:0 0 40px; place-items:center; border:1px solid rgba(255,255,255,.28); border-radius:10px; background:rgba(255,255,255,.14); color:#fff; font-size:1rem; }
    main.flex-grow-1 .campus-building-title { margin:0; color:#fff; font-family:'Plus Jakarta Sans',sans-serif; font-size:1rem; font-weight:800; }
    .campus-building-subtitle { margin:3px 0 0; color:rgba(255,255,255,.8); font-size:.78rem; }
    .campus-building-body { padding:16px 20px 20px; }
    .campus-building-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
    .building-availability-card { min-width:0; padding:14px 16px; border:1px solid var(--panel-border); border-radius:10px; background:#fbfcfe; scroll-margin-top:24px; transition:border-color .2s ease,background-color .2s ease,box-shadow .2s ease; }
    .building-availability-card.is-selected,
    .building-availability-card:focus { border-color:#d8b234; background:#fffdf5; box-shadow:0 0 0 3px rgba(245,197,24,.18); outline:none; }
    .building-availability-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .building-availability-name { display:flex; align-items:center; gap:8px; min-width:0; color:#24314a; font-size:.88rem; font-weight:700; }
    .building-availability-name i { color:#7585a0; }
    .building-availability-state-dot { width:9px; height:9px; flex:0 0 9px; border-radius:50%; background:#16a34a; }
    .building-availability-card.is-unavailable .building-availability-state-dot { background:#dc2626; }
    .building-availability-count { display:flex; align-items:baseline; gap:5px; margin-top:12px; color:#667085; font-size:.78rem; }
    .building-availability-count strong { color:#0f1729; font-family:'Plus Jakarta Sans',sans-serif; font-size:1.45rem; line-height:1; }
    .building-availability-total { margin-left:auto; color:#7b8799; font-size:.72rem; }
    .building-availability-meter { height:5px; margin-top:10px; overflow:hidden; border-radius:999px; background:#e9edf3; }
    .building-availability-meter span { display:block; width:100%; height:100%; border-radius:inherit; background:#16a34a; transform:scaleX(0); transform-origin:left; transition:transform .3s ease; }
    .building-availability-card.is-unavailable .building-availability-meter span { background:#dc2626; }
    .building-availability-empty { grid-column:1/-1; margin:0; padding:16px; color:var(--muted); font-size:.85rem; }
    .live-badge { display: flex; align-items: center; gap: 6px; font-size: .74rem; font-weight: 700; color: #0a7a43; background: #e6f9f0; padding: 5px 12px; border-radius: 999px; border: 1px solid #b7e5c8; }
    .live-dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; animation: pulse 1.6s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }

    /* ── Room cards ────────────────────────────────── */
    .room-card { border-radius: 16px; border: 1px solid var(--panel-border); background: #fff; box-shadow: var(--panel-shadow); transition: transform 0.2s ease, box-shadow 0.2s ease; padding: 18px 22px !important; position:relative; }
    .room-card:hover { transform: translateY(-2px); box-shadow: var(--panel-shadow-hover); border-color:#d7dcea; }
    .room-card { cursor: pointer; }
    .room-card.has-room-image { display:flex; flex-direction:column; overflow:hidden; padding:0 !important; }
    .room-card-image { width:100%; height:160px; flex:0 0 160px; overflow:hidden; background:#e8edf4; }
    .room-card-image img { display:block; width:100%; height:100%; object-fit:cover; }
    .room-card-content { display:flex; flex:1; flex-direction:column; min-width:0; padding:16px 22px 14px; }
    .room-card-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
    .room-card-title { min-width:0; margin:0; color:#0f1729; font-family:'Plus Jakarta Sans',sans-serif; font-size:1.05rem; font-weight:800; line-height:1.25; }
    .room-card-location { display:flex; align-items:center; gap:6px; margin-top:4px; color:#65718a; font-size:.82rem; line-height:1.35; }
    .room-card-location i { color:#8894a8; }
    .room-card-details { display:grid; grid-template-columns:minmax(128px,.4fr) minmax(0,1fr); gap:12px 20px; margin:10px 0 12px; }
    .room-card-detail { min-width:0; }
    .room-card-detail-label { display:block; margin-bottom:5px; color:#5d6879; font-size:.75rem; font-weight:700; line-height:1.2; }
    .room-card-amenities { display:flex; align-items:center; flex-wrap:wrap; gap:6px; }
    .room-card-actions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; margin-top:auto; padding-top:10px; border-top:1px solid #edf0f5; }
    .room-card-actions button { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-width:0; min-height:44px; padding:8px 10px; border-radius:9px; font-size:.78rem; font-weight:700; line-height:1.1; white-space:nowrap; transition:background-color .18s ease,border-color .18s ease,color .18s ease,box-shadow .18s ease; }
    main.flex-grow-1 .room-card-actions button { min-height:44px; }
    .room-card-actions button:focus-visible { outline:2px solid var(--gold); outline-offset:2px; }
    .room-card-actions .room-building-button { flex:none; background:#fff; color:var(--navy); border:1px solid #cbd4e4; }
    .room-card-actions .room-building-button:hover { background:#fff9e6; border-color:#d8b234; color:var(--navy); }
    .room-schedule-button { flex:0 0 auto; background:#e9ecfa; color:var(--navy); border:1px solid #cbd4f4; border-radius:10px; font-weight:700; padding:9px 13px; }
    .room-card-actions .room-schedule-button { flex:none; background:var(--navy); color:#fff; border:1px solid var(--navy); }
    .room-card-actions .room-schedule-button:hover { background:#1a2f80; border-color:#1a2f80; color:#fff; }
    .room-status-badge { font-weight:700; letter-spacing:.02em; border-radius:999px !important; padding:5px 10px !important; }
    .amenity-tag { background: #f5f6fa; color: #5b6577; border: 1px solid var(--panel-border); border-radius: 999px; font-size: .74rem; padding: 4px 10px; }
    .room-seats-chip { display:inline-flex; align-items:center; gap:6px; background:#f5f6fa; border:1px solid var(--panel-border); border-radius:999px; padding:4px 10px; font-size:.78rem; color:#374151; }
    .btn-map { background: #fff; color: var(--navy); border: 1px solid var(--panel-border); border-radius: 10px; font-weight: 600; transition: all 0.2s ease; padding: 9px 16px; }
    .btn-map:hover { background: #f0f2fb; border-color:#c7cde0; }
    .btn-map-gray { background: #f5f6fa; color: #a3abbb; border: 1px solid var(--panel-border); border-radius: 10px; font-weight: 500; pointer-events: none; padding: 9px 16px; }

    .room-results-panel { min-width:0; }
    @media (min-width: 992px) {
      .room-results-panel #roomResultsGrid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px; margin:0; }
      .room-results-panel .room-result { width:auto; max-width:none; padding:0; }
    }

    @media (max-width: 768px) {
      .campus-banner { gap:12px; padding:14px !important; }
      .campus-banner h2 { font-size:1.28rem; line-height:1.15; }
      .campus-banner .sub-line { font-size:.74rem; }
      .campus-icon { width:44px; height:44px; flex:0 0 44px; border-radius:11px; font-size:1.2rem; }
      .campus-building-header { min-height:78px; padding:12px 14px; }
      .campus-building-title-group { gap:9px; }
      .campus-building-icon { width:34px; height:34px; flex-basis:34px; border-radius:8px; font-size:.88rem; }
      main.flex-grow-1 .campus-building-title { font-size:.9rem; line-height:1.2; }
      .campus-building-subtitle { font-size:.7rem; }
      .campus-building-header .live-badge { gap:5px; padding:4px 8px; font-size:.66rem; }
      .campus-building-body { padding:10px; }
      .campus-building-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
      .building-availability-card { padding:10px; border-radius:8px; }
      .building-availability-name { gap:6px; font-size:.76rem; }
      .building-availability-count { display:grid; grid-template-columns:auto minmax(0,1fr); gap:3px 4px; margin-top:9px; font-size:.72rem; }
      .building-availability-count strong { font-size:1.25rem; }
      .building-availability-total { grid-column:1/-1; margin-left:0; font-size:.68rem; }
      .building-availability-meter { margin-top:8px; }
    }

    .is-room-syncing .room-card { opacity: .7; }
    .is-room-syncing .room-card::after { content: ''; display: block; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(90deg,transparent 25%,rgba(255,255,255,.5) 50%,transparent 75%); background-size: 200% 100%; animation: roomSyncSkeleton 1.35s ease-in-out infinite; pointer-events: none; }
    @keyframes roomSyncSkeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    @media (prefers-reduced-motion: reduce) { .is-room-syncing .room-card::after { animation: none; background: rgba(255,255,255,.35); } }

    .room-schedule-overlay { position:fixed; inset:0; z-index:1800; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(11,19,48,.48); backdrop-filter:blur(5px); }
    .room-schedule-overlay.is-open { display:flex; }
    .room-schedule-modal { width:min(1100px,100%); max-height:min(860px,calc(100vh - 40px)); display:flex; flex-direction:column; overflow:hidden; background:#fff; border:1px solid rgba(15,26,60,.1); border-radius:18px; box-shadow:0 24px 70px rgba(11,19,48,.25); }
    .room-schedule-cover { position:relative; min-height:160px; display:flex; align-items:flex-end; padding:20px 24px; overflow:hidden; background:linear-gradient(100deg,rgba(11,22,64,.9),rgba(11,22,64,.38)),url('/images/map.png') center/cover no-repeat; color:#fff; }
    .room-schedule-cover::after { content:''; position:absolute; inset:0; background:linear-gradient(180deg,transparent 25%,rgba(11,22,64,.55)); pointer-events:none; }
    .room-schedule-cover::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:var(--gold); z-index:2; }
    .room-schedule-cover-content { position:relative; z-index:1; }
    .room-schedule-cover-kicker { font-size:.7rem; font-weight:800; letter-spacing:.16em; opacity:.8; }
    .room-schedule-room-number { margin-top:5px; font-family:'Plus Jakarta Sans',sans-serif; font-size:2rem; font-weight:800; }
    .room-schedule-close { position:absolute; top:20px; right:24px; z-index:10; width:36px; height:36px; border:1px solid rgba(255,255,255,.2); border-radius:10px; background:rgba(11,22,64,.5); backdrop-filter:blur(4px); color:#fff; cursor:pointer; display:grid; place-items:center; transition:all 0.2s ease; }
    .room-schedule-close:hover { background:#fff; color:var(--navy); transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.15); }
    .room-schedule-body { flex:1; overflow-y:auto; padding:24px; background:#f8f9fb; }
    .timetable-wrapper { background:#fff; border-radius:12px; border:1px solid var(--panel-border); box-shadow:var(--panel-shadow); overflow:hidden; margin-bottom:24px; }
    .timetable-legend { display:flex; gap:16px; padding:12px 20px; background:#fff; border-bottom:1px solid var(--panel-border); font-size:0.75rem; font-weight:700; color:#4b5566; }
    .timetable-legend-item { display:flex; align-items:center; gap:6px; }
    .timetable-legend-dot { width:12px; height:12px; border-radius:4px; }
    .timetable-legend-dot.class { background:linear-gradient(135deg, #1e3a8a, #3b82f6); }
    .timetable-legend-dot.reservation { background:linear-gradient(135deg, #a16207, #eab308); }
    .timetable-scroll { overflow-x:auto; }
    .timetable { width:100%; min-width:900px; border-collapse:collapse; text-align:center; font-size:0.75rem; }
    .timetable th, .timetable td { border:1px solid var(--panel-border); padding:8px 4px; }
    .timetable th { background:var(--navy); color:#fff; font-weight:700; border-color:rgba(255,255,255,0.1); white-space:nowrap; }
    .timetable .day-col { background:#e0e7ff; color:var(--navy); font-weight:800; width:60px; border-color:#c7d2fe; position:sticky; left:0; z-index:2; }
    .timetable td { height:60px; background:#fff; position:relative; }
    .timetable-entry { position:absolute; inset:2px; border-radius:6px; padding:4px; display:flex; flex-direction:column; justify-content:center; align-items:center; color:#fff; font-weight:700; font-size:0.7rem; line-height:1.2; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,0.1); }
    .timetable-entry.is-class { background:linear-gradient(135deg, #1e3a8a, #3b82f6); border:1px solid #1e40af; }
    .timetable-entry.is-reservation { background:linear-gradient(135deg, #a16207, #eab308); border:1px solid #854d0e; color:#fff; }
    .timetable-entry-time { font-size:0.6rem; opacity:0.9; margin-top:2px; font-weight:500; }
    .room-schedule-empty, .room-schedule-loading { padding:60px 20px; color:#7585a0; text-align:center; background:#fff; }
    .room-schedule-check { padding:20px; border:1px solid var(--panel-border); border-radius:12px; background:#fff; box-shadow:var(--panel-shadow); }
    .room-schedule-check-title { margin-bottom:12px; color:#0f1729; font-size:.85rem; font-weight:800; display:flex; align-items:center; gap:8px; }
    .room-schedule-check-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:end; }
    .room-schedule-check label { display:block; margin-bottom:6px; color:#4b5566; font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; }
    .room-schedule-check input { width:100%; min-height:42px; padding:8px 12px; border:1px solid var(--panel-border); border-radius:8px; background:#f8f9fb; color:#0f1729; font-weight:500; transition:all 0.2s; }
    .room-schedule-check input:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(11,22,64,.1); background:#fff; }
    .room-schedule-check-actions { display:flex; gap:12px; margin-top:16px; }
    .room-schedule-check-actions button { flex:1; min-height:42px; border-radius:8px; padding:0 16px; font-weight:700; cursor:pointer; transition:all 0.2s; }
    .room-schedule-check-close { border:1px solid var(--panel-border); background:#fff; color:#4b5566; }
    .room-schedule-check-close:hover { background:#f5f6fa; }
    .room-schedule-check-run { border:0; background:var(--navy); color:#fff; }
    .room-schedule-check-run:hover { background:#1e3a8a; }
    .room-schedule-check-result { margin-top:12px; font-size:.8rem; font-weight:700; padding:10px; border-radius:8px; display:none; }
    .room-schedule-check-result:not(:empty) { display:block; }
    .room-schedule-check-result.is-available { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
    .room-schedule-check-result.is-unavailable { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    @media (max-width:768px) { .room-schedule-overlay { padding:10px; } .room-schedule-body { padding:16px; } .room-schedule-check-grid { grid-template-columns:1fr; } }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!- Main ->
  <main class="flex-grow-1 p-4">

    <!- Topbar ->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <div class="home-topbar-title">Rooms</div>
        <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
      </div>
      <a href="{{ route('student.home') }}" class="btn btn-warning fw-semibold"><i class="bi bi-house me-1"></i>Back to Home</a>
    </div>

    <!- Campus Status Banner ->
    <div class="campus-banner p-4 d-flex justify-content-between align-items-center mb-4">
      <div>
        <div class="eyebrow mb-1">CAMPUS STATUS</div>
        <h2 class="fw-bold mb-1"><span id="available-rooms-count">{{ $availableRoomsCount }}</span> Rooms Available</h2>
        <div class="sub-line">Out of <span id="total-rooms-count">{{ $classrooms->count() }}</span> total classrooms</div>
      </div>
      <div class="campus-icon"><i class="bi bi-building"></i></div>
    </div>

    <!- Search & Filters ->
    <div class="input-group mb-3">
      <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
      <input id="room-search" type="text" class="form-control border-start-0" placeholder="Search by room name or building...">
    </div>
    <div class="filters-row mb-4">
      <span class="text-muted small"><i class="bi bi-funnel me-1"></i>Filters:</span>
      <button type="button" class="room-filter btn btn-sm rounded-pill fw-semibold" data-room-filter="all" style="background:var(--navy);color:#fff">All</button>
      <button type="button" class="room-filter btn btn-sm btn-outline-secondary rounded-pill" data-room-filter="available">Available</button>
      <button type="button" class="room-filter btn btn-sm btn-outline-secondary rounded-pill" data-room-filter="occupied">Occupied</button>
    </div>

    @php
      $buildings_list = $classrooms->groupBy('building')->map(function ($rooms, $building) {
        return [
          'name' => $building,
          'total' => $rooms->count(),
          'available' => $rooms->where('status', 'available')->count(),
        ];
      })->values();
    @endphp

    <section class="campus-building-panel" aria-labelledby="campusBuildingTitle">
      <div class="campus-building-header">
        <div class="campus-building-title-group">
          <div class="campus-building-icon"><i class="bi bi-buildings" aria-hidden="true"></i></div>
          <div>
            <h2 class="campus-building-title" id="campusBuildingTitle">Campus Building Availability</h2>
            <p class="campus-building-subtitle">Available rooms by building</p>
          </div>
        </div>
        <span class="live-badge"><span class="live-dot"></span> Live</span>
      </div>
      <div class="campus-building-body">
      <div class="campus-building-grid" id="campusBuildingGrid">
        @forelse ($buildings_list as $building)
          @php
            $availabilityPercent = $building['total'] > 0
              ? round(($building['available'] / $building['total']) * 100)
              : 0;
          @endphp
          <article class="building-availability-card {{ $building['available'] === 0 ? 'is-unavailable' : '' }}" data-building-card data-building="{{ $building['name'] }}" tabindex="-1">
            <div class="building-availability-top">
              <div class="building-availability-name"><i class="bi bi-building" aria-hidden="true"></i>{{ $building['name'] }}</div>
              <span class="building-availability-state-dot" aria-hidden="true"></span>
            </div>
            <div class="building-availability-count">
              <strong data-building-count>{{ $building['available'] }}</strong>
              <span>available</span>
              <span class="building-availability-total">of <span data-building-total>{{ $building['total'] }}</span> <span data-building-room-word>{{ $building['total'] === 1 ? 'room' : 'rooms' }}</span></span>
            </div>
            <div class="building-availability-meter" role="progressbar" aria-label="{{ $building['name'] }} available rooms" aria-valuemin="0" aria-valuemax="{{ $building['total'] }}" aria-valuenow="{{ $building['available'] }}">
              <span data-building-meter style="transform:scaleX({{ $availabilityPercent / 100 }})"></span>
            </div>
          </article>
        @empty
          <p class="building-availability-empty">No building availability to show.</p>
        @endforelse
      </div>
      </div>
    </section>

    <section class="room-results-panel" aria-label="Room results">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span id="rooms-found-count" class="fw-semibold small text-muted">{{ $classrooms->count() ?? 0 }} rooms found</span>
      <span id="room-sync-status" class="small text-muted"><i class="bi bi-arrow-repeat me-1"></i>Syncing live status...</span>
    </div>
    <div class="row g-3" id="roomResultsGrid">
      @forelse ($classrooms ?? [] as $room)
        @php
          $roomSearchText = strtolower((string) ($room->name ?? '').' '.(string) ($room->building ?? ''));
          $roomImages = ['room-1.png', 'room-2.png', 'computer-lab.png'];
          $roomImage = str_contains($roomSearchText, 'lab') || str_contains($roomSearchText, 'computer')
              ? 'computer-lab.png'
              : $roomImages[(int) ($room->id ?? 0) % count($roomImages)];
        @endphp
        <div class="col-md-6 room-result" data-room-card data-room-id="{{ $room->id }}" data-room-building="{{ $room->building }}" data-room-name="{{ $room->name }}" data-room-status="{{ $room->status === 'available' ? 'available' : 'occupied' }}">
          <div class="room-card has-room-image h-100">
            <div class="room-card-image">
              <img src="{{ asset('images/'.$roomImage) }}" alt="{{ $room->name }} classroom" loading="lazy">
            </div>
            <div class="room-card-content">
            <div class="room-card-heading">
              <div class="room-card-title-wrap">
                <h3 class="room-card-title">{{ $room->name ?? 'Room' }}</h3>
                <div class="room-card-location"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>{{ $room->building ?? 'Building' }}</span><span aria-hidden="true">·</span><span>{{ $room->floor ?? 'Floor' }}</span></div>
              </div>
              <span class="badge room-status-badge {{ $room->status === 'available' ? 'text-success' : 'text-danger' }}" style="background:{{ $room->status === 'available' ? '#e2f7ec' : '#fdecea' }};font-size:.72rem"><i class="room-status-icon bi {{ $room->status === 'available' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i><span class="room-status-label">{{ $room->status === 'available' ? 'AVAILABLE' : 'OCCUPIED' }}</span></span>
            </div>

            <div class="room-card-details">
              <div class="room-card-detail">
                <span class="room-card-detail-label">Capacity</span>
                <span class="room-seats-chip"><i class="bi bi-people"></i>{{ $room->capacity ?? 0 }} seats</span>
              </div>
              <div class="room-card-detail">
                <span class="room-card-detail-label">Amenities</span>
                <div class="room-card-amenities" role="group" aria-label="Amenities">
                  <span class="amenity-tag">Standard</span>
                  <span class="amenity-tag">WiFi</span>
                </div>
              </div>
            </div>

            <div class="room-card-actions">
              <button type="button" class="btn room-building-button" aria-label="View {{ $room->building ?? 'building' }} availability">
                <i class="bi bi-building me-1" aria-hidden="true"></i>View Building
              </button>
              <button type="button" class="btn room-schedule-button" aria-label="Check room availability"><i class="bi bi-calendar3" aria-hidden="true"></i><span>Check</span></button>
            </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-info">No classrooms available</div>
        </div>
      @endforelse
    </div>
    </section>
    </div>

    <div class="room-schedule-overlay" id="roomScheduleOverlay" aria-hidden="true">
      <section class="room-schedule-modal" role="dialog" aria-modal="true" aria-labelledby="roomScheduleRoomNumber">
        <h2 id="roomScheduleTitle" class="d-none"></h2>
        <div class="room-schedule-cover">
          <button class="room-schedule-close" id="roomScheduleClose" type="button" aria-label="Close room schedule"><i class="bi bi-x-lg"></i></button>
          <div class="room-schedule-cover-content">
            <div class="room-schedule-cover-kicker">WEEKLY SCHEDULE</div>
            <div class="room-schedule-room-number" id="roomScheduleRoomNumber">Room</div>
          </div>
        </div>
        <div class="room-schedule-body" id="roomScheduleBody">
          <div class="timetable-wrapper">
            <div class="timetable-legend">
              <div class="timetable-legend-item"><span class="timetable-legend-dot class"></span> Class</div>
              <div class="timetable-legend-item"><span class="timetable-legend-dot reservation"></span> Reservation</div>
            </div>
            <div class="timetable-scroll" id="roomScheduleList">
              <div class="room-schedule-loading">Select a room to load its schedule.</div>
            </div>
          </div>
          <div class="room-schedule-check">
            <div class="room-schedule-check-title"><i class="bi bi-calendar-check"></i> Check Availability</div>
            <div class="room-schedule-check-grid">
              <div><label for="roomCheckStart">Start</label><input id="roomCheckStart" type="datetime-local"></div>
              <div><label for="roomCheckEnd">End</label><input id="roomCheckEnd" type="datetime-local"></div>
            </div>
            <div class="room-schedule-check-result" id="roomCheckResult" aria-live="polite"></div>
            <div class="room-schedule-check-actions"><button type="button" class="room-schedule-check-close" id="roomCheckClose">Cancel</button><button type="button" class="room-schedule-check-run" id="roomCheckButton">Run Check</button></div>
          </div>
        </div>
      </section>
    </div>

  </main>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var roomCards = Array.from(document.querySelectorAll('[data-room-card]'));
    var activeFilter = 'all';
    var roomStatusesLoaded = false;
    var searchInput = document.getElementById('room-search');
    var syncStatus = document.getElementById('room-sync-status');
    var scheduleOverlay = document.getElementById('roomScheduleOverlay');
    var scheduleClose = document.getElementById('roomScheduleClose');
    var scheduleTitle = document.getElementById('roomScheduleTitle');
    var scheduleRoomNumber = document.getElementById('roomScheduleRoomNumber');
    var scheduleBody = document.getElementById('roomScheduleBody');
    var scheduleList = document.getElementById('roomScheduleList');
    var roomCheckStart = document.getElementById('roomCheckStart');
    var roomCheckEnd = document.getElementById('roomCheckEnd');
    var roomCheckButton = document.getElementById('roomCheckButton');
    var roomCheckClose = document.getElementById('roomCheckClose');
    var roomCheckResult = document.getElementById('roomCheckResult');
    var activeScheduleRoomId = null;

    // Building pins are positioned by CSS grid layout

    function escapeText(value) {
      return String(value ?? '').toLowerCase();
    }

    function applyRoomFilters() {
      var search = escapeText(searchInput?.value);
      var visibleCount = 0;

      roomCards.forEach(function(card) {
        var matchesSearch = [card.dataset.roomName, card.dataset.roomBuilding]
          .some(function(value) { return escapeText(value).includes(search); });
        var matchesFilter = activeFilter === 'all' || card.dataset.roomStatus === activeFilter;
        var visible = matchesSearch && matchesFilter;
        card.classList.toggle('d-none', !visible);
        if (visible) visibleCount += 1;
      });

      document.getElementById('rooms-found-count').textContent = visibleCount + (visibleCount === 1 ? ' room found' : ' rooms found');
    }

    function startOfWeek(date) {
      var value = new Date(date);
      var day = value.getDay();
      value.setDate(value.getDate() - (day === 0 ? 6 : day - 1));
      return value.toISOString().slice(0, 10);
    }

    function formatTime(value) {
      return new Date(value).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function renderTimetable(payload) {
      var entries = [];
      (payload.schedules || []).forEach(function (item) { entries.push({ ...item, kind: 'Class' }); });
      (payload.reservations || []).forEach(function (item) { entries.push({ ...item, kind: 'Reservation', course: 'Room reservation' }); });
      
      if (!entries.length) {
        scheduleList.innerHTML = '<div class="room-schedule-empty"><i class="bi bi-calendar2-check fs-2 d-block mb-2"></i>No classes or reservations this week.</div>';
        return;
      }

      var days = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
      var dayMap = { 1: 'MON', 2: 'TUE', 3: 'WED', 4: 'THU', 5: 'FRI', 6: 'SAT', 0: 'SUN' };
      var timeSlots = [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
      var timeLabels = [
        '7:00-8:00', '8:00-9:00', '9:00-10:00', '10:00-11:00', 
        '11:00-12:00', '12:00-1:00', '1:00-2:00', '2:00-3:00', 
        '3:00-4:00', '4:00-5:00', '5:00-6:00', '6:00-7:00', '7:00-8:00'
      ];

      var html = '<table class="timetable"><thead><tr><th class="day-col">Day</th>';
      timeLabels.forEach(function(label) { html += '<th>' + label + '</th>'; });
      html += '</tr></thead><tbody>';

      var grid = {};
      days.forEach(function(day) { 
        grid[day] = {};
        timeSlots.forEach(function(slot) { grid[day][slot] = null; });
      });

      entries.forEach(function(entry) {
        var start = new Date(entry.start_at);
        var end = new Date(entry.end_at);
        var dayStr = dayMap[start.getDay()];
        
        var startHour = start.getHours();
        var endHour = end.getHours();
        if (end.getMinutes() > 0) endHour++;
        
        startHour = Math.max(7, Math.min(19, startHour));
        endHour = Math.max(8, Math.min(20, endHour));
        
        var span = endHour - startHour;
        if (span < 1) span = 1;

        if (dayStr && grid[dayStr] && grid[dayStr][startHour] !== undefined) {
          grid[dayStr][startHour] = {
            span: span,
            course: entry.course_code || entry.course || entry.kind,
            time: formatTime(entry.start_at) + ' - ' + formatTime(entry.end_at),
            isClass: entry.kind === 'Class'
          };
          for (var i = 1; i < span; i++) {
            if (grid[dayStr][startHour + i] !== undefined) {
              grid[dayStr][startHour + i] = 'covered';
            }
          }
        }
      });

      days.forEach(function(day) {
        html += '<tr><td class="day-col">' + day + '</td>';
        timeSlots.forEach(function(slot) {
          var cell = grid[day][slot];
          if (cell === 'covered') {
          } else if (cell) {
             html += '<td colspan="' + cell.span + '"><div class="timetable-entry ' + (cell.isClass ? 'is-class' : 'is-reservation') + '">'
                  + '<div>' + cell.course + '</div><div class="timetable-entry-time">' + cell.time + '</div></div></td>';
          } else {
             html += '<td></td>';
          }
        });
        html += '</tr>';
      });

      html += '</tbody></table>';
      scheduleList.innerHTML = html;
    }

    function openRoomSchedule(card) {
      var roomId = card.dataset.roomId;
      var roomName = card.dataset.roomName || 'Room ' + roomId;
      activeScheduleRoomId = roomId;
      scheduleRoomNumber.textContent = roomName;
      if (scheduleTitle) scheduleTitle.textContent = roomName;
      scheduleList.hidden = false;
      
      var start = new Date();
      start.setMinutes(0, 0, 0);
      var end = new Date(start.getTime() + 60 * 60 * 1000);
      roomCheckStart.value = toDateTimeLocal(start);
      roomCheckEnd.value = toDateTimeLocal(end);
      roomCheckResult.textContent = '';
      roomCheckResult.className = 'room-schedule-check-result';

      scheduleList.innerHTML = '<div class="room-schedule-loading"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading schedule...</div>';
      scheduleOverlay.classList.add('is-open');
      scheduleOverlay.setAttribute('aria-hidden', 'false');

      fetch('{{ request()->getBaseUrl() }}/api/v1/map/rooms/' + encodeURIComponent(roomId) + '/fixed-schedules?week_start=' + startOfWeek(new Date()), { headers: { Accept: 'application/json' } })
        .then(function (response) { if (!response.ok) throw new Error('Schedule request failed'); return response.json(); })
        .then(function (payload) { renderTimetable(payload.data || {}); })
        .catch(function () { scheduleList.innerHTML = '<div class="room-schedule-empty"><i class="bi bi-exclamation-circle fs-2 d-block mb-2"></i>Unable to load this room schedule.</div>'; });
    }

    function toDateTimeLocal(date) {
      var offset = date.getTimezoneOffset() * 60000;
      return new Date(date.getTime() - offset).toISOString().slice(0, 16);
    }

    function openRoomCheck(card) {
      openRoomSchedule(card);
    }

    function checkRoomTime() {
      if (!activeScheduleRoomId || !roomCheckStart?.value || !roomCheckEnd?.value) {
        roomCheckResult.textContent = 'Choose both a start and end time.';
        roomCheckResult.className = 'room-schedule-check-result is-unavailable';
        return;
      }

      roomCheckButton.disabled = true;
      roomCheckButton.textContent = 'Checking...';
      roomCheckResult.textContent = '';
      var query = new URLSearchParams({
        classroom_id: activeScheduleRoomId,
        start_at: new Date(roomCheckStart.value).toISOString(),
        end_at: new Date(roomCheckEnd.value).toISOString(),
      });

      fetch('{{ request()->getBaseUrl() }}/api/v1/room-availability/check?' + query.toString(), { headers: { Accept: 'application/json' } })
        .then(function (response) { if (!response.ok) throw new Error('Availability check failed'); return response.json(); })
        .then(function (payload) {
          var data = payload.data || {};
          var available = data.status === 'available' || data.available === true;
          roomCheckResult.textContent = available ? 'Available for the selected time.' : (data.reason || 'Room is not available for the selected time.');
          roomCheckResult.className = 'room-schedule-check-result ' + (available ? 'is-available' : 'is-unavailable');
        })
        .catch(function () {
          roomCheckResult.textContent = 'Unable to check this time right now.';
          roomCheckResult.className = 'room-schedule-check-result is-unavailable';
        })
        .finally(function () { roomCheckButton.disabled = false; roomCheckButton.textContent = 'Run Check'; });
    }

    function closeRoomSchedule() { scheduleOverlay.classList.remove('is-open'); scheduleOverlay.setAttribute('aria-hidden', 'true'); }

    function focusBuildingAvailability(roomCard) {
      var buildingName = roomCard.dataset.roomBuilding || 'Unknown';
      var buildingCard = Array.from(document.querySelectorAll('[data-building-card]'))
        .find(function(card) { return card.dataset.building === buildingName; });

      if (!buildingCard) return;

      document.querySelectorAll('.building-availability-card.is-selected').forEach(function(card) {
        card.classList.remove('is-selected');
      });

      buildingCard.classList.add('is-selected');
      buildingCard.focus({ preventScroll: true });
      var topOffset = window.innerWidth <= 768 ? 84 : 24;
      var targetTop = window.scrollY + buildingCard.getBoundingClientRect().top - topOffset;
      window.scrollTo({
        top: Math.max(0, targetTop),
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
      });

      window.setTimeout(function() {
        buildingCard.classList.remove('is-selected');
      }, 1800);
    }

    roomCards.forEach(function (card) {
      card.querySelector('.room-building-button')?.addEventListener('click', function (event) {
        event.stopPropagation();
        focusBuildingAvailability(card);
      });
      card.querySelector('.room-schedule-button')?.addEventListener('click', function (event) {
        event.stopPropagation();
        openRoomCheck(card);
      });
      card.addEventListener('click', function (event) {
        if (event.target.closest('button, a, input, select')) return;
        openRoomSchedule(card);
      });
    });
    roomCheckButton?.addEventListener('click', checkRoomTime);
    roomCheckClose?.addEventListener('click', closeRoomSchedule);
    scheduleClose?.addEventListener('click', closeRoomSchedule);
    scheduleOverlay?.addEventListener('click', function (event) { if (event.target === scheduleOverlay) closeRoomSchedule(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeRoomSchedule(); });

    function setRoomStatus(card, room) {
      var status = room.status || 'available';
      var isAvailable = status === 'available';
      var label = room.status_label || (isAvailable ? 'Available' : 'Occupied');
      var badge = card.querySelector('.room-status-badge');
      var icon = card.querySelector('.room-status-icon');
      var statusLabel = card.querySelector('.room-status-label');

      card.dataset.roomStatus = isAvailable ? 'available' : 'occupied';
      badge.className = 'badge room-status-badge ' + (isAvailable ? 'text-success' : 'text-danger');
      badge.style.background = isAvailable ? '#e2f7ec' : '#fdecea';
      icon.className = 'room-status-icon bi ' + (isAvailable ? 'bi-check-circle' : 'bi-x-circle') + ' me-1';
      statusLabel.textContent = label.toUpperCase();
    }

    function updateBuildingAvailability(rooms) {
      var buildingCounts = {};
      rooms.forEach(function(room) {
        var building = room.building || 'Unknown';
        if (!buildingCounts[building]) buildingCounts[building] = { total: 0, available: 0 };
        buildingCounts[building].total += 1;
        if (room.status === 'available') buildingCounts[building].available += 1;
      });

      document.querySelectorAll('[data-building-card]').forEach(function(card) {
        var counts = buildingCounts[card.dataset.building] || { total: 0, available: 0 };
        var ratio = counts.total > 0 ? Math.round((counts.available / counts.total) * 100) : 0;
        card.classList.toggle('is-unavailable', counts.available === 0);
        card.querySelector('[data-building-count]').textContent = counts.available;
        card.querySelector('[data-building-total]').textContent = counts.total;
        card.querySelector('[data-building-room-word]').textContent = counts.total === 1 ? 'room' : 'rooms';
        card.querySelector('[data-building-meter]').style.transform = 'scaleX(' + (ratio / 100) + ')';
        card.querySelector('[role="progressbar"]').setAttribute('aria-valuemax', counts.total);
        card.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', counts.available);
      });
    }

    function syncRoomStatuses() {
      // Only show skeleton on very first load (no flash on subsequent polls)
      syncStatus.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Updating live status...';
      fetch('{{ url('/api/v1/room-statuses') }}', { headers: { Accept: 'application/json' } })
        .then(function(response) {
          if (!response.ok) throw new Error('Room status request failed');
          return response.json();
        })
        .then(function(payload) {
          var rooms = payload.data || [];
          var roomById = new Map(rooms.map(function(room) { return [String(room.classroom_id), room]; }));
          var availableCount = 0;

          roomCards.forEach(function(card) {
            var room = roomById.get(String(card.dataset.roomId));
            if (!room) return;
            setRoomStatus(card, room);
            if (room.status === 'available') availableCount += 1;
          });

          updateBuildingAvailability(rooms);
          document.getElementById('available-rooms-count').textContent = availableCount;
          document.getElementById('total-rooms-count').textContent = rooms.length;
          syncStatus.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Live status updated ' + new Date().toLocaleTimeString();
          document.getElementById('roomResultsGrid')?.classList.remove('is-room-syncing');
          roomStatusesLoaded = true;
          applyRoomFilters();
        })
        .catch(function() {
          document.getElementById('roomResultsGrid')?.classList.remove('is-room-syncing');
          syncStatus.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Live status unavailable';
        });
    }

    searchInput?.addEventListener('input', applyRoomFilters);
    document.querySelectorAll('.room-filter').forEach(function(button) {
      button.addEventListener('click', function() {
        activeFilter = button.dataset.roomFilter || 'all';
        document.querySelectorAll('.room-filter').forEach(function(filterButton) {
          filterButton.classList.toggle('fw-semibold', filterButton === button);
          filterButton.classList.toggle('btn-outline-secondary', filterButton !== button);
          filterButton.style.background = filterButton === button ? 'var(--navy)' : '';
          filterButton.style.color = filterButton === button ? '#fff' : '';
        });
        applyRoomFilters();
      });
    });

    syncRoomStatuses();
    window.setInterval(syncRoomStatuses, 10000);
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>