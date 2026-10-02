<?php
$facultyName     = $facultyName     ?? request()->user()?->name       ?? 'Faculty';
$facultyDept     = $facultyDept     ?? request()->user()?->department  ?? 'Faculty';
$facultyEmail    = $facultyEmail    ?? request()->user()?->email       ?? '';
$facultyInitials = $facultyInitials ?? strtoupper(substr((string) $facultyName, 0, 1));

$attendanceCards = collect($courses ?? [])->map(function ($course) {
  $schedules = collect($course->schedules ?? [])->sortBy('start_at')->values();
  if ($schedules->isEmpty()) {
    return null;
  }

  $now = now();
  $ongoing = $schedules->first(function ($schedule) use ($now) {
    return $schedule->start_at
      && $schedule->start_at->lte($now)
      && ($schedule->end_at === null || $schedule->end_at->gte($now));
  });
  $upcoming = $schedules->first(function ($schedule) use ($now) {
    return $schedule->start_at && $schedule->start_at->gte($now);
  });
  $primary = $ongoing ?? $upcoming ?? $schedules->last();

  if (! $primary) {
    return null;
  }

  $status = $ongoing ? 'ongoing' : ($upcoming ? 'upcoming' : 'finished');
  $courseCode = (string) ($course->code ?? 'N/A');
  $courseTitle = (string) ($course->title ?? 'Untitled Subject');
  $roomName = (string) ($primary->classroom?->name ?? 'Room N/A');
  $building = (string) ($primary->classroom?->building ?? '');
  $section = (string) ($primary->block_section ?? '—');

  return [
    'schedule_id' => (int) $primary->id,
    'course_code' => $courseCode,
    'subject' => $courseTitle,
    'section' => $section,
    'room' => trim($roomName . ($building !== '' ? ', ' . $building : '')),
    'time' => $primary->start_at
      ? $primary->start_at->format('g:i A') . ($primary->end_at ? ' - ' . $primary->end_at->format('g:i A') : '')
      : 'TBA',
    'status' => $status,
    'status_label' => ucfirst($status),
    'search' => strtolower(trim($courseCode . ' ' . $courseTitle . ' ' . $section . ' ' . $roomName . ' ' . $building)),
  ];
})->filter()->values();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Attendance — SmartDoor</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --yellow:#f5c518; --yellow-light:#fef9e7;
      --navy:#0b1640; --navy-mid:#1a2f80; --navy-light:#e8ecfb;
      --white:#ffffff; --bg:#f0f2f8;
      --border:#e4e8f0; --border-strong:#cdd3e0;
      --text:#0f1729; --text-2:#3d4a5c; --text-3:#7c8a9e; --text-4:#b0bac8;
      --green:#0f9d58; --green-mid:#12b564; --green-bg:#e6f9f0; --green-border:#a7e9c8; --green-text:#0a7a43;
      --blue:#1a56db; --blue-mid:#2563eb; --blue-bg:#eaf0fd; --blue-border:#93b8f8; --blue-text:#1740b0;
      --amber:#d97706; --amber-bg:#fef3e2; --amber-border:#fcd38a; --amber-text:#b45309;
      --red:#dc2626; --red-bg:#fef2f2; --red-border:#fca5a5;
      --shadow-xs:0 1px 2px rgba(15,23,41,.05);
      --shadow-sm:0 2px 8px rgba(15,23,41,.07),0 1px 2px rgba(15,23,41,.04);
      --shadow-md:0 4px 20px rgba(15,23,41,.09),0 1px 4px rgba(15,23,41,.05);
      --shadow-lg:0 12px 40px rgba(15,23,41,.13),0 3px 10px rgba(15,23,41,.07);
      --radius-xs:6px; --radius-sm:10px; --radius:14px; --radius-lg:18px; --radius-xl:24px;
      --sidebar-w:230px;
      --font-head:'Plus Jakarta Sans',sans-serif;
      --font-body:'DM Sans',sans-serif;
      --font-mono:'DM Mono',monospace;
    }

    body { font-family:var(--font-body); background:var(--bg); color:var(--text); min-height:100vh; display:flex; -webkit-font-smoothing:antialiased; }

    /* ══════════════════════════════════════════
       SIDEBAR — DO NOT CHANGE
    ══════════════════════════════════════════ */
    .sidebar { position:fixed; left:0; top:0; width:var(--sidebar-w); height:100vh; background:var(--navy); display:flex; flex-direction:column; overflow:hidden; z-index:100; }
    .sidebar::before { content:''; position:absolute; inset:0; background:linear-gradient(160deg,rgba(245,197,24,.06) 0%,transparent 55%); pointer-events:none; }
    .sidebar::after { content:''; position:absolute; bottom:-60px; right:-60px; width:180px; height:180px; border-radius:50%; border:1px solid rgba(245,197,24,.08); pointer-events:none; }
    .sidebar-logo { display:flex; align-items:center; gap:12px; padding:28px 20px 24px 24px; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.06); margin-bottom:8px; }
    .logo-mark { width:40px; height:40px; background:var(--yellow); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:var(--navy); flex-shrink:0; box-shadow:0 4px 12px rgba(245,197,24,.4); }
    .logo-text { line-height:1; }
    .logo-text .brand-psu { font-size:.6rem; font-weight:600; letter-spacing:.18em; color:rgba(255,255,255,.45); text-transform:uppercase; display:block; margin-bottom:3px; }
    .logo-text .brand-main { font-size:1.05rem; font-weight:700; color:#fff; letter-spacing:-.01em; }
    .logo-text .brand-main span { color:var(--yellow); }
    .nav-section-label { font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.25); padding:16px 24px 6px; }
    .sidebar-nav { list-style:none; overflow-y:auto; padding:0 12px; }
    .sidebar-nav::-webkit-scrollbar { width:0; }
    .sidebar-nav li { margin-bottom:2px; }
    .sidebar-nav a { display:flex; align-items:center; gap:11px; padding:11px 12px; text-decoration:none; color:rgba(255,255,255,.6); font-size:.88rem; font-weight:500; border-radius:var(--radius-sm); transition:all .22s cubic-bezier(.4,0,.2,1); position:relative; overflow:hidden; }
    .sidebar-nav a .nav-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem; background:rgba(255,255,255,.05); flex-shrink:0; transition:all .22s; }
    .sidebar-nav a:hover { color:rgba(255,255,255,.9); background:rgba(255,255,255,.06); }
    .sidebar-nav a:hover .nav-icon { background:rgba(255,255,255,.1); }
    .sidebar-nav a.active { background:rgba(245,197,24,.14); color:var(--yellow); }
    .sidebar-nav a.active .nav-icon { background:rgba(245,197,24,.2); color:var(--yellow); }
    .sidebar-nav a.active::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; background:var(--yellow); border-radius:0 2px 2px 0; }
    .sidebar-footer { margin-top:auto; padding:16px 12px 24px; border-top:1px solid rgba(255,255,255,.06); }
    .user-widget { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:var(--radius-sm); background:rgba(255,255,255,.05); margin-bottom:8px; }
    .user-avatar { width:34px; height:34px; border-radius:50%; flex-shrink:0; background:var(--navy-mid); border:2px solid rgba(245,197,24,.4); display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; color:var(--yellow); }
    .user-widget-info { flex:1; min-width:0; }
    .user-widget-name { font-size:.83rem; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .user-widget-role { font-size:.73rem; color:rgba(255,255,255,.4); }
    .sidebar-logout-btn { display:flex; align-items:center; gap:10px; padding:9px 12px; color:rgba(255,255,255,.4); font-size:.84rem; font-weight:500; border-radius:var(--radius-sm); transition:all .22s; width:100%; background:none; border:none; cursor:pointer; font-family:inherit; }
    .sidebar-logout-btn:hover { color:#f87171; background:rgba(244,63,94,.08); }

    /* ══════════════════════════════════════════
       MAIN
    ══════════════════════════════════════════ */
    .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-height:100vh; }

    /* TOPBAR */
    .topbar {
      background:rgba(255,255,255,.94);
      backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
      border-bottom:1px solid var(--border);
      padding:0 40px; height:68px;
      display:flex; align-items:center; justify-content:space-between;
      position:sticky; top:0; z-index:50;
      box-shadow:0 1px 0 var(--border),0 4px 24px rgba(15,23,41,.04);
    }
    .topbar-breadcrumb { display:flex; align-items:center; gap:8px; }
    .bc-home { font-size:.82rem; color:var(--text-3); font-weight:500; text-decoration:none; transition:color .15s; }
    .bc-home:hover { color:var(--text-2); }
    .bc-sep { color:var(--text-4); font-size:.7rem; }
    .bc-current { font-family:var(--font-head); font-size:.98rem; font-weight:800; color:var(--text); letter-spacing:-.015em; }
    .topbar-right { display:flex; align-items:center; gap:12px; }
    .topbar-date { font-size:.79rem; color:var(--text-3); font-weight:500; padding:6px 13px; background:var(--bg); border:1px solid var(--border); border-radius:20px; display:flex; align-items:center; gap:6px; }
    .topbar-date i { color:var(--text-4); font-size:.7rem; }
    .new-btn {
      display:inline-flex; align-items:center; gap:8px;
      padding:9px 20px; background:var(--navy); color:#fff;
      border:none; border-radius:10px; font-family:var(--font-body); font-weight:700; font-size:.83rem;
      cursor:pointer; transition:all .2s cubic-bezier(.4,0,.2,1);
      box-shadow:0 2px 8px rgba(11,22,64,.25); position:relative; overflow:hidden;
    }
    .new-btn::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(245,197,24,.1) 0%,transparent 60%); pointer-events:none; }
    .new-btn:hover { background:#0d1f55; transform:translateY(-1px); box-shadow:0 6px 20px rgba(11,22,64,.3); }
    .new-btn-icon { width:19px; height:19px; background:var(--yellow); border-radius:5px; display:flex; align-items:center; justify-content:center; color:var(--navy); font-size:.6rem; font-weight:900; flex-shrink:0; }

    /* CONTENT */
    .content { padding:32px 40px 60px; display:flex; flex-direction:column; gap:26px; }

    /* PAGE HEADER */
    .page-header { display:flex; align-items:flex-end; justify-content:space-between; }
    .page-eyebrow { font-size:.71rem; font-weight:700; letter-spacing:.13em; text-transform:uppercase; color:var(--blue-text); margin-bottom:7px; display:flex; align-items:center; gap:7px; }
    .page-eyebrow::before { content:''; width:18px; height:2px; background:linear-gradient(90deg,var(--blue-mid),var(--navy-mid)); border-radius:2px; }
    .page-title { font-family:var(--font-head); font-size:1.7rem; font-weight:800; color:var(--text); letter-spacing:-.03em; line-height:1.1; }
    .page-subtitle { font-size:.84rem; color:var(--text-3); margin-top:6px; }

    /* STAT CARDS */
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
    .stat-card {
      background:var(--white); border-radius:var(--radius-lg); border:1px solid var(--border);
      padding:22px 22px 20px; position:relative; overflow:hidden;
      box-shadow:var(--shadow-sm); transition:transform .22s cubic-bezier(.4,0,.2,1),box-shadow .22s; cursor:default;
    }
    .stat-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }
    .stat-card.c-blue  { background:linear-gradient(145deg,#fff 55%,#edf2ff); }
    .stat-card.c-green { background:linear-gradient(145deg,#fff 55%,#ecfdf5); }
    .stat-card.c-slate { background:linear-gradient(145deg,#fff 55%,#f8fafc); }
    .stat-card.c-amber { background:linear-gradient(145deg,#fff 55%,#fffbeb); }
    /* decorative circle */
    .stat-card::after { content:''; position:absolute; top:-28px; right:-28px; width:100px; height:100px; border-radius:50%; opacity:.06; }
    .stat-card.c-blue::after  { background:var(--blue-mid); }
    .stat-card.c-green::after { background:var(--green-mid); }
    .stat-card.c-slate::after { background:#475569; }
    .stat-card.c-amber::after { background:var(--amber); }

    .stat-card-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:18px; position:relative; z-index:1; }
    .stat-icon-wrap { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
    .ic-blue  { background:linear-gradient(135deg,#60a5fa,#1d4ed8); color:#fff; box-shadow:0 4px 12px rgba(37,99,235,.28); }
    .ic-green { background:linear-gradient(135deg,#34d399,#059669); color:#fff; box-shadow:0 4px 12px rgba(16,185,129,.28); }
    .ic-slate { background:linear-gradient(135deg,#94a3b8,#475569); color:#fff; box-shadow:0 4px 12px rgba(71,85,105,.22); }
    .ic-amber { background:linear-gradient(135deg,#fbbf24,#b45309); color:#fff; box-shadow:0 4px 12px rgba(245,158,11,.28); }
    .stat-pill { font-size:.68rem; font-weight:700; padding:4px 10px; border-radius:20px; letter-spacing:.01em; white-space:nowrap; }
    .sp-blue  { background:var(--blue-bg);  color:var(--blue-text);  border:1px solid var(--blue-border); }
    .sp-green { background:var(--green-bg); color:var(--green-text); border:1px solid var(--green-border); }
    .sp-gray  { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
    .sp-amber { background:var(--amber-bg); color:var(--amber-text); border:1px solid var(--amber-border); }
    .stat-value { font-family:var(--font-head); font-size:2.5rem; font-weight:800; letter-spacing:-.055em; line-height:1; color:var(--text); margin-bottom:5px; position:relative; z-index:1; }
    .stat-label { font-size:.8rem; color:var(--text-3); font-weight:500; position:relative; z-index:1; }
    .stat-divider { height:1px; background:var(--border); margin:14px 0 12px; }
    .stat-footer { font-size:.74rem; color:var(--text-3); display:flex; align-items:center; gap:5px; }
    .stat-footer i { font-size:.65rem; color:var(--text-4); }

    /* TABLE CARD */
    .table-card { background:var(--white); border-radius:var(--radius-lg); border:1px solid var(--border); box-shadow:var(--shadow-sm); overflow:hidden; }
    .table-toolbar { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 18px; border-bottom:1px solid var(--border); gap:16px; flex-wrap:wrap; }
    .toolbar-left { display:flex; align-items:center; gap:12px; }
    .toolbar-icon { width:38px; height:38px; border-radius:10px; background:var(--blue-bg); color:var(--blue-text); display:flex; align-items:center; justify-content:center; font-size:.88rem; }
    .toolbar-title { font-family:var(--font-head); font-size:.98rem; font-weight:800; color:var(--text); }
    .toolbar-sub { font-size:.77rem; color:var(--text-3); margin-top:1px; }
    .toolbar-right { display:flex; align-items:center; gap:10px; }
    .search-box { display:flex; align-items:center; gap:8px; background:var(--bg); border:1.5px solid var(--border); border-radius:9px; padding:7px 13px; transition:border-color .2s,box-shadow .2s; }
    .search-box:focus-within { border-color:var(--blue-border); box-shadow:0 0 0 3px rgba(37,99,235,.08); }
    .search-box i { color:var(--text-4); font-size:.8rem; }
    .search-box input { border:none; outline:none; background:transparent; font-size:.83rem; font-family:var(--font-body); color:var(--text); width:170px; }
    .search-box input::placeholder { color:var(--text-4); }
    .filter-tabs { display:flex; gap:3px; background:var(--bg); padding:3px; border-radius:9px; border:1px solid var(--border); }
    .ftab { padding:6px 13px; border-radius:6px; background:transparent; border:none; color:var(--text-3); font-size:.78rem; cursor:pointer; font-weight:600; font-family:var(--font-body); transition:all .16s; white-space:nowrap; display:flex; align-items:center; gap:5px; }
    .ftab.active { background:var(--white); color:var(--text); box-shadow:0 1px 4px rgba(15,23,41,.1); }
    .ftab:not(.active):hover { color:var(--text-2); }

    /* Table */
    table { width:100%; border-collapse:collapse; }
    thead tr { background:#f8fafc; }
    thead th { padding:11px 18px; text-align:left; font-size:.7rem; font-weight:700; color:var(--text-3); text-transform:uppercase; letter-spacing:.07em; border-bottom:1px solid var(--border); white-space:nowrap; }
    thead th:first-child { padding-left:24px; }
    thead th:last-child  { padding-right:24px; }
    tbody tr { border-bottom:1px solid var(--border); transition:background .13s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafbff; }
    td { padding:15px 18px; font-size:.875rem; color:var(--text); vertical-align:middle; }
    td:first-child { padding-left:24px; }
    td:last-child  { padding-right:24px; }

    /* Date cell */
    .date-block { display:flex; align-items:center; gap:11px; }
    .date-cal { flex-shrink:0; width:44px; background:var(--bg); border:1px solid var(--border); border-radius:9px; text-align:center; overflow:hidden; }
    .date-cal-top { background:var(--navy); padding:3px 0; font-size:.56rem; font-weight:700; letter-spacing:.08em; color:var(--yellow); text-transform:uppercase; }
    .date-cal-num { font-family:var(--font-head); font-size:1.2rem; font-weight:800; color:var(--text); padding:4px 0 2px; line-height:1; }
    .date-cal-yr { font-size:.58rem; color:var(--text-4); padding-bottom:4px; }
    .date-main { font-weight:700; font-size:.87rem; }
    .date-day  { font-size:.74rem; color:var(--text-3); margin-top:2px; }

    /* Course cell */
    .course-wrap { display:flex; align-items:center; gap:10px; }
    .course-dot { width:8px; height:8px; border-radius:50%; background:linear-gradient(135deg,var(--blue-mid),var(--navy-mid)); flex-shrink:0; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .course-code { font-weight:700; font-size:.87rem; font-family:var(--font-mono); letter-spacing:.03em; color:var(--text); }
    .course-sub  { font-size:.75rem; color:var(--text-3); margin-top:2px; }

    /* Room */
    .room-tag { display:inline-flex; align-items:center; gap:5px; font-size:.77rem; font-weight:600; color:var(--text-2); background:var(--bg); border:1px solid var(--border); padding:4px 10px; border-radius:6px; }
    .room-tag i { font-size:.63rem; color:var(--text-4); }

    /* Status */
    .status-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:.74rem; font-weight:700; letter-spacing:.01em; }
    .status-open   { background:var(--green-bg); color:var(--green-text); border:1px solid var(--green-border); }
    .status-closed { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
    .status-dot { width:6px; height:6px; border-radius:50%; background:currentColor; display:inline-block; }
    .status-open .status-dot { animation:pulse-dot 1.8s ease-in-out infinite; }
    @keyframes pulse-dot { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4)} 50%{box-shadow:0 0 0 5px rgba(16,185,129,0)} }

    /* Actions */
    .actions { display:flex; gap:5px; align-items:center; }
    .action-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 11px; border-radius:7px; font-size:.75rem; font-weight:600; cursor:pointer; border:1.5px solid var(--border); background:var(--white); color:var(--text-2); text-decoration:none; transition:all .15s; font-family:var(--font-body); white-space:nowrap; }
    .action-btn i { font-size:.67rem; }
    .action-btn:hover { transform:translateY(-1px); box-shadow:var(--shadow-xs); }
    .action-btn.view:hover   { background:var(--blue-bg);  border-color:var(--blue-border);  color:var(--blue-text); }
    .action-btn.export:hover { background:var(--green-bg); border-color:var(--green-border); color:var(--green-text); }
    .action-btn.danger { border-color:var(--red-border); color:var(--red); background:var(--red-bg); }
    .action-btn.danger:hover { background:#fee2e2; border-color:#f87171; }
    .action-divider { width:1px; height:18px; background:var(--border); margin:0 1px; }
    .row-num { font-family:var(--font-mono); font-size:.72rem; color:var(--text-4); font-weight:500; }

    /* Table footer */
    .table-footer { padding:14px 24px; border-top:1px solid var(--border); background:#fafbfc; display:flex; align-items:center; justify-content:space-between; }
    .table-count { font-size:.77rem; color:var(--text-3); font-weight:500; }
    .table-count strong { color:var(--text-2); }

    .attendance-feedback { margin: 14px 0 0; padding: 11px 14px; border-radius: 12px; border: 1px solid var(--border); background: linear-gradient(90deg, rgba(37,99,235,.06), #fff); color: var(--text-2); font-size: .82rem; }
    .attendance-feedback i { color: var(--blue-text); }

    /* Empty state */
    .empty-state { padding:64px 24px; text-align:center; }
    .empty-icon-wrap { width:72px; height:72px; border-radius:20px; background:var(--bg); border:1.5px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; }
    .empty-icon-wrap i { font-size:1.8rem; color:var(--text-4); }
    .empty-title { font-family:var(--font-head); font-size:1rem; font-weight:700; color:var(--text-2); margin-bottom:6px; }
    .empty-sub { font-size:.83rem; color:var(--text-3); }

    /* MODAL */
    .modal-bg { display:none; position:fixed; inset:0; background:rgba(11,22,64,.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center; padding:24px; z-index:200; }
    .modal-bg.show { display:flex; }
    .modal { background:var(--white); max-width:560px; width:100%; border-radius:var(--radius-xl); overflow:hidden; box-shadow:var(--shadow-lg); animation:modal-in .22s cubic-bezier(.34,1.4,.64,1); }
    @keyframes modal-in { from{opacity:0;transform:scale(.95) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
    .modal-head { padding:24px 28px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#fafbff,var(--white)); }
    .modal-head-left { display:flex; align-items:center; gap:13px; }
    .modal-head-icon { width:42px; height:42px; border-radius:12px; background:var(--navy); color:var(--yellow); display:flex; align-items:center; justify-content:center; font-size:.95rem; box-shadow:0 3px 10px rgba(11,22,64,.25); }
    .modal-title { font-family:var(--font-head); font-size:1rem; font-weight:800; color:var(--text); }
    .modal-subtitle { font-size:.77rem; color:var(--text-3); margin-top:2px; }
    .modal-close { width:32px; height:32px; background:var(--bg); border:1.5px solid var(--border); border-radius:8px; cursor:pointer; color:var(--text-3); display:flex; align-items:center; justify-content:center; font-size:.85rem; transition:all .15s; }
    .modal-close:hover { background:var(--red-bg); border-color:var(--red-border); color:var(--red); }
    .modal-body { padding:24px 28px; display:flex; flex-direction:column; gap:18px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-label { font-size:.78rem; font-weight:700; color:var(--text-2); display:flex; align-items:center; gap:5px; }
    .form-label i { font-size:.7rem; color:var(--text-4); }
    .form-muted { font-weight:400; color:var(--text-3); }
    .form-input { padding:10px 14px; border:1.5px solid var(--border); border-radius:var(--radius-sm); background:var(--white); font-size:.875rem; color:var(--text); width:100%; outline:none; transition:border-color .18s,box-shadow .18s; font-family:var(--font-body); appearance:none; }
    .form-input:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(11,22,64,.08); }
    .form-input::placeholder { color:var(--text-4); }
    .form-select-wrap { position:relative; }
    .form-select-wrap::after { content:'\f107'; font-family:'Font Awesome 6 Free'; font-weight:900; position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--text-3); font-size:.75rem; pointer-events:none; }
    .form-select-wrap .form-input { padding-right:36px; cursor:pointer; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-hint { font-size:.73rem; color:var(--text-3); margin-top:2px; display:flex; align-items:center; gap:4px; }
    .form-hint i { font-size:.63rem; }
    .modal-sep { height:1px; background:linear-gradient(90deg,transparent,var(--border),transparent); }
    .modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 28px; border-top:1px solid var(--border); background:#fafbfc; }
    .btn-cancel { padding:10px 18px; border:1.5px solid var(--border); border-radius:9px; background:var(--white); color:var(--text-2); cursor:pointer; font-weight:600; font-size:.84rem; font-family:var(--font-body); transition:all .15s; }
    .btn-cancel:hover { background:var(--bg); }
    .btn-primary { padding:10px 22px; border:none; border-radius:9px; background:var(--navy); color:#fff; cursor:pointer; font-weight:700; font-size:.84rem; font-family:var(--font-body); transition:all .18s; display:flex; align-items:center; gap:7px; box-shadow:0 2px 8px rgba(11,22,64,.25); }
    .btn-primary:hover { background:#0d1f55; box-shadow:0 4px 14px rgba(11,22,64,.3); transform:translateY(-1px); }
    .btn-primary i { font-size:.7rem; }

    /* Animations */
    @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
    .page-header { animation:fadeUp .35s both .04s; }
    .stats-row   { animation:fadeUp .35s both .10s; }
    .table-card  { animation:fadeUp .35s both .18s; }

    /* Responsive */
    @media(max-width:1280px){ .stats-row{grid-template-columns:repeat(2,1fr)} .content{padding:24px 24px 48px} .topbar{padding:0 24px} }
    @media(max-width:960px){ :root{--sidebar-w:0px} .sidebar{display:none} .stats-row{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:600px){ .stats-row{grid-template-columns:1fr} .form-row{grid-template-columns:1fr} .search-box{display:none} }
  </style>
</head>
<body>

<!-- ═══════════════════════════════════════════
     SIDEBAR — DO NOT CHANGE
═══════════════════════════════════════════ -->
<div class="sidebar">
  <a href="{{ url('/dashboard') }}" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Door</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main Menu</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ url('/faculty_dashboard') }}" class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-line"></i></span>Dashboard
      </a>
    </li>
    <li>
      <a href="{{ url('/rooms') }}" class="{{ Request::is('rooms*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-door-open"></i></span>Rooms
      </a>
    </li>
    <li>
      <a href="{{ url('/faculty-schedule') }}" class="{{ Request::is('faculty-schedule') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clock"></i></span>Schedule
      </a>
    </li>
    <li>
      <a href="{{ url('/attendance') }}" class="{{ Request::is('attendance*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance
      </a>
    </li>
  </ul>

  <span class="nav-section-label">Tools</span>
  <ul class="sidebar-nav">
    <!-- AI Recommendations removed from sidebar -->
    <li>
      <a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID
      </a>
    </li>
    <li><a href="{{ route('faculty.notifications') }}" class="{{ Request::routeIs('faculty.notifications') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-bell"></i></span>Notifications</a></li>
    <li>
      <a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <div class="user-avatar"><?= htmlspecialchars($facultyInitials) ?></div>
      <div class="user-widget-info">
        <div class="user-widget-name"><?= htmlspecialchars($facultyName) ?></div>
        <div class="user-widget-role"><?= htmlspecialchars($facultyDept) ?></div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout-btn">
        <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
      </button>
    </form>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     MAIN
═══════════════════════════════════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <nav class="topbar-breadcrumb">
      <a href="{{ url('/faculty_dashboard') }}" class="bc-home">Dashboard</a>
      <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
      <span class="bc-current">Attendance</span>
    </nav>
    <div class="topbar-right">
      <div class="topbar-date">
        <i class="fas fa-calendar-days"></i>
        <span id="live-date">{{ now()->format('M j, Y') }}</span>
      </div>
      <a href="{{ url('/faculty_dashboard') }}" class="new-btn" style="text-decoration:none">
        <span class="new-btn-icon"><i class="fas fa-layer-group"></i></span>
        My Classes
      </a>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content" data-quick-start-url="{{ route('faculty.attendance.quick.start') }}" data-live-attendance-url="{{ route('faculty.attendance.live') }}" data-csrf-token="{{ csrf_token() }}">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <div class="page-eyebrow">Faculty Portal</div>
        <div class="page-title">Attendance Management</div>
        <div class="page-subtitle">Launch class attendance quickly, monitor participation, and export records.</div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-row">

      <div class="stat-card c-blue">
        <div class="stat-card-top">
          <div class="stat-icon-wrap ic-blue"><i class="fas fa-calendar-check"></i></div>
          <span class="stat-pill sp-blue">This semester</span>
        </div>
        <div class="stat-value">{{ $sessions ? $sessions->count() : 0 }}</div>
        <div class="stat-label">Total Sessions</div>
        <div class="stat-divider"></div>
        <div class="stat-footer"><i class="fas fa-clock"></i> All recorded sessions</div>
      </div>

      <div class="stat-card c-green">
        <div class="stat-card-top">
          <div class="stat-icon-wrap ic-green"><i class="fas fa-circle-dot"></i></div>
          <span class="stat-pill sp-green"><i class="fas fa-circle" style="font-size:.42rem"></i>&nbsp;Live</span>
        </div>
        <div class="stat-value" id="open-sessions-count">{{ $sessions ? $sessions->where('status','open')->count() : 0 }}</div>
        <div class="stat-label">Open Sessions</div>
        <div class="stat-divider"></div>
        <div class="stat-footer"><i class="fas fa-door-open"></i> Currently accepting entries</div>
      </div>

      <div class="stat-card c-slate">
        <div class="stat-card-top">
          <div class="stat-icon-wrap ic-slate"><i class="fas fa-box-archive"></i></div>
          <span class="stat-pill sp-gray">Archived</span>
        </div>
        <div class="stat-value" id="closed-sessions-count">{{ $sessions ? $sessions->where('status','closed')->count() : 0 }}</div>
        <div class="stat-label">Closed Sessions</div>
        <div class="stat-divider"></div>
        <div class="stat-footer"><i class="fas fa-lock"></i> No longer accepting entries</div>
      </div>

      <div class="stat-card c-amber">
        <div class="stat-card-top">
          <div class="stat-icon-wrap ic-amber"><i class="fas fa-book-open"></i></div>
          <span class="stat-pill sp-amber">Active</span>
        </div>
        <div class="stat-value">{{ $courses ? $courses->count() : 0 }}</div>
        <div class="stat-label">Courses Tracked</div>
        <div class="stat-divider"></div>
        <div class="stat-footer"><i class="fas fa-graduation-cap"></i> Assigned this semester</div>
      </div>

    </div>

    <!-- Assigned Classes Table -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="toolbar-left">
          <div class="toolbar-icon"><i class="fas fa-layer-group"></i></div>
          <div>
            <div class="toolbar-title">Assigned Classes</div>
            <div class="toolbar-sub">Click any class to open or resume today’s attendance session.</div>
          </div>
        </div>
        <div class="toolbar-right">
          <div class="search-box">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" placeholder="Search subjects…" id="class-search">
          </div>
          <div class="filter-tabs" id="classes-filter-tabs">
            <button class="ftab active" type="button" onclick="filterClasses('all',this)">All</button>
            <button class="ftab" type="button" onclick="filterClasses('ongoing',this)">
              <i class="fas fa-circle" style="font-size:.42rem;color:var(--green-text)"></i> Ongoing
            </button>
            <button class="ftab" type="button" onclick="filterClasses('upcoming',this)">Upcoming</button>
            <button class="ftab" type="button" onclick="filterClasses('finished',this)">Finished</button>
          </div>
        </div>
      </div>

      @if($attendanceCards && $attendanceCards->count() > 0)
        <table>
          <thead>
            <tr>
              <th>Subject</th>
              <th>Section</th>
              <th>Room</th>
              <th>Schedule Time</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="classes-tbody">
            @foreach($attendanceCards as $class)
              <tr data-status="{{ $class['status'] }}" data-search="{{ $class['search'] }}">
                <td>
                  <div class="course-wrap">
                    <div class="course-dot"></div>
                    <div>
                      <div class="course-code">{{ $class['course_code'] }}</div>
                      <div class="course-sub">{{ $class['subject'] }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ $class['section'] }}</td>
                <td>
                  <span class="room-tag"><i class="fas fa-location-dot"></i>{{ $class['room'] }}</span>
                </td>
                <td>{{ $class['time'] }}</td>
                <td>
                  @if($class['status'] === 'ongoing')
                    <span class="status-pill status-open"><span class="status-dot"></span>Ongoing</span>
                  @elseif($class['status'] === 'upcoming')
                    <span class="status-pill" style="background:var(--amber-bg);color:var(--amber-text);border:1px solid var(--amber-border)"><span class="status-dot"></span>Upcoming</span>
                  @else
                    <span class="status-pill status-closed"><span class="status-dot"></span>Finished</span>
                  @endif
                </td>
                <td>
                  <div class="actions">
                    <button type="button" class="action-btn view js-open-attendance" data-schedule-id="{{ $class['schedule_id'] }}">
                      <i class="fas fa-bolt"></i> Check Attendance
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="table-footer">
          <div class="table-count">Showing <strong id="classes-visible-count">{{ $attendanceCards->count() }}</strong> of <strong>{{ $attendanceCards->count() }}</strong> classes</div>
        </div>
      @else
        <div class="empty-state">
          <div class="empty-icon-wrap"><i class="fas fa-layer-group"></i></div>
          <div class="empty-title">No assigned classes found</div>
          <div class="empty-sub">Only subjects assigned to your account will appear here.</div>
        </div>
      @endif
    </div>

    <!-- Sessions Table -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="toolbar-left">
          <div class="toolbar-icon"><i class="fas fa-clipboard-list"></i></div>
          <div>
            <div class="toolbar-title">Attendance Sessions</div>
            <div class="toolbar-sub">All sessions across your assigned courses</div>
          </div>
        </div>
        <div class="toolbar-right">
          <div class="search-box">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" placeholder="Search sessions…" id="session-search">
          </div>
          <div class="filter-tabs" id="sessions-filter-tabs">
            <button class="ftab active" onclick="filterSessions('all',this)">All</button>
            <button class="ftab" onclick="filterSessions('open',this)">
              <i class="fas fa-circle" style="font-size:.42rem;color:var(--green-text)"></i> Open
            </button>
            <button class="ftab" onclick="filterSessions('closed',this)">Closed</button>
          </div>
        </div>
      </div>

      @if($sessions && $sessions->count() > 0)
        <table>
          <thead>
            <tr>
              <th style="width:40px">#</th>
              <th>Date</th>
              <th>Course / Schedule</th>
              <th>Room</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="sessions-tbody">
            @foreach($sessions as $i => $s)
              <tr data-session-id="{{ $s->id }}" data-status="{{ $s->status ?? 'closed' }}"
                  data-search="{{ strtolower(optional($s->schedule)->course->code ?? optional($s->course)->code ?? '') }} {{ strtolower($s->room ?? '') }}">
                <td><span class="row-num">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span></td>
                <td>
                  <div class="date-block">
                    <div class="date-cal">
                      <div class="date-cal-top">{{ \Carbon\Carbon::parse($s->date ?? $s['date'])->format('M') }}</div>
                      <div class="date-cal-num">{{ \Carbon\Carbon::parse($s->date ?? $s['date'])->format('j') }}</div>
                      <div class="date-cal-yr">{{ \Carbon\Carbon::parse($s->date ?? $s['date'])->format('Y') }}</div>
                    </div>
                    <div>
                      <div class="date-main">{{ \Carbon\Carbon::parse($s->date ?? $s['date'])->format('M j, Y') }}</div>
                      <div class="date-day">{{ \Carbon\Carbon::parse($s->date ?? $s['date'])->format('l') }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="course-wrap">
                    <div class="course-dot"></div>
                    <div>
                      <div class="course-code">{{ optional($s->schedule)->course->code ?? optional($s->course)->code ?? ($s->course_id ?? '—') }}</div>
                      <div class="course-sub">Schedule #{{ optional($s->schedule)->id ?? ($s->schedule_id ?? '—') }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="room-tag">
                    <i class="fas fa-location-dot"></i>
                    {{ $s->room ?? (optional($s->schedule)->classroom->name ?? '—') }}
                  </span>
                </td>
                <td>
                  @if(($s->status ?? '') === 'open')
                    <span class="status-pill status-open" data-session-status-badge><span class="status-dot"></span>Open</span>
                  @else
                    <span class="status-pill status-closed" data-session-status-badge><span class="status-dot"></span>{{ ucfirst($s->status ?? 'Closed') }}</span>
                  @endif
                </td>
                <td>
                  <div class="actions">
                    <a href="{{ route('faculty.attendance.students', $s->id) }}" class="action-btn view js-view-students" data-students-url="{{ route('faculty.attendance.students', $s->id) }}">
                      <i class="fas fa-users"></i> View Students
                    </a>
                    <a href="{{ route('faculty.attendance.export', $s->id) }}" class="action-btn export">
                      <i class="fas fa-download"></i> Export
                    </a>
                    @if(($s->status ?? '') === 'open')
                      <div class="action-divider"></div>
                      <button type="button" class="action-btn danger js-close-session" data-session-id="{{ $s->id }}">
                        <i class="fas fa-xmark"></i> Close
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="table-footer">
          <div class="table-count">Showing <strong id="visible-count">{{ $sessions->count() }}</strong> of <strong>{{ $sessions->count() }}</strong> sessions</div>
        </div>

      @else
        <div class="empty-state">
          <div class="empty-icon-wrap"><i class="fas fa-clipboard-check"></i></div>
          <div class="empty-title">No attendance sessions yet</div>
          <div class="empty-sub">Click <strong>New Session</strong> in the top-right to get started.</div>
        </div>
      @endif
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<div id="student-roster-modal" class="student-roster-modal" aria-hidden="true">
  <section class="student-roster-dialog" role="dialog" aria-modal="true" aria-labelledby="student-roster-heading">
    <header class="student-roster-header">
      <div>
        <div class="student-roster-eyebrow"><i class="fas fa-users"></i> STUDENT ROSTER</div>
        <h2 id="student-roster-heading">Students handled</h2>
        <p id="student-roster-title">Loading attendance session...</p>
      </div>
      <button id="student-roster-close" class="attendance-modal-close" type="button" aria-label="Close student roster"><i class="fas fa-xmark"></i></button>
    </header>
    <div class="student-roster-toolbar">
      <label for="student-roster-filter">Block</label>
      <select id="student-roster-filter">
        <option value="all">All blocks</option>
        <option value="Block A">Block A</option>
        <option value="Block B">Block B</option>
      </select>
    </div>
    <div class="student-roster-table-wrap">
      <table class="student-roster-table">
        <thead><tr><th>Student</th><th>Block</th><th>Status</th><th>Check-in time</th></tr></thead>
        <tbody id="student-roster-rows"><tr class="roster-skeleton" aria-hidden="true"><td></td><td></td><td></td><td></td></tr><tr class="roster-skeleton" aria-hidden="true"><td></td><td></td><td></td><td></td></tr><tr class="roster-skeleton" aria-hidden="true"><td></td><td></td><td></td><td></td></tr></tbody>
      </table>
    </div>
  </section>
</div>

<script>
  const classSearch = document.getElementById('class-search');
  const classRows = Array.from(document.querySelectorAll('#classes-tbody tr'));
  const classTabs = Array.from(document.querySelectorAll('#classes-filter-tabs .ftab'));
  const classVisibleCount = document.getElementById('classes-visible-count');
  const contentRoot = document.querySelector('.content');
  const quickStartUrl = contentRoot?.dataset.quickStartUrl || '/attendance/quick/start';
  const liveAttendanceUrl = contentRoot?.dataset.liveAttendanceUrl || '/attendance/live';
  const csrf = contentRoot?.dataset.csrfToken || '';
  let classFilter = 'all';

  async function refreshAttendanceOverview() {
    if (document.hidden) return;

    try {
      const response = await fetch(liveAttendanceUrl, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store',
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) return;

      const openCount = document.getElementById('open-sessions-count');
      const closedCount = document.getElementById('closed-sessions-count');
      if (openCount) openCount.textContent = payload.open_sessions;
      if (closedCount) closedCount.textContent = payload.closed_sessions;

      (payload.sessions || []).forEach(session => {
        const row = document.querySelector(`#sessions-tbody tr[data-session-id="${session.id}"]`);
        if (!row) return;

        row.dataset.status = session.status;
        const badge = row.querySelector('[data-session-status-badge]');
        if (badge) {
          badge.className = `status-pill ${session.status === 'open' ? 'status-open' : 'status-closed'}`;
          badge.innerHTML = `<span class="status-dot"></span>${session.status === 'open' ? 'Open' : 'Closed'}`;
        }

        const actions = row.querySelector('.actions');
        const closeButton = actions?.querySelector('.js-close-session');
        if (session.status === 'open' && actions && !closeButton) {
          const divider = document.createElement('div');
          divider.className = 'action-divider';
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'action-btn danger js-close-session';
          button.dataset.sessionId = session.id;
          button.innerHTML = '<i class="fas fa-xmark"></i> Close';
          button.addEventListener('click', function () {
            closeSession(this.dataset.sessionId, this);
          });
          actions.append(divider, button);
        } else if (session.status !== 'open' && closeButton) {
          closeButton.previousElementSibling?.remove();
          closeButton.remove();
        }
      });
    } catch (error) {
      // Keep the current page usable when a live refresh is temporarily unavailable.
    }
  }

  refreshAttendanceOverview();
  window.setInterval(refreshAttendanceOverview, 3000);

  function updateClassVisibleCount() {
    if (!classVisibleCount) return;
    const visible = classRows.filter(row => row.style.display !== 'none').length;
    classVisibleCount.textContent = visible;
  }

  function filterClasses(status, btn) {
    classFilter = status || 'all';
    classTabs.forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const query = (classSearch?.value || '').trim().toLowerCase();

    classRows.forEach(row => {
      const statusMatch = classFilter === 'all' || row.dataset.status === classFilter;
      const searchMatch = !query || (row.dataset.search || '').includes(query);
      row.style.display = statusMatch && searchMatch ? '' : 'none';
    });

    updateClassVisibleCount();
  }

  function setAttendanceFeedback(message, loading = true) {
    const feedback = document.getElementById('attendance-feedback');
    const feedbackText = document.getElementById('attendance-feedback-text');
    if (!feedback || !feedbackText) return;

    feedback.classList.add('is-visible');
    feedback.innerHTML = loading
      ? '<i class="fas fa-spinner fa-spin"></i><span>' + message + '</span>'
      : '<i class="fas fa-circle-check"></i><span>' + message + '</span>';

    if (!loading) {
      window.clearTimeout(setAttendanceFeedback._timer);
      setAttendanceFeedback._timer = window.setTimeout(() => {
        feedback.classList.remove('is-visible');
      }, 2200);
    }
  }

  function openAttendanceModal() {
    const modal = document.getElementById('attendance-modal');
    const loader = document.getElementById('attendance-modal-loader');
    const error = document.getElementById('attendance-modal-error');
    const qrPanel = document.getElementById('attendance-qr-panel');
    const frame = document.getElementById('attendance-modal-frame');
    if (!modal) return;
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    if (loader) loader.style.display = 'flex';
    if (error) error.style.display = 'none';
    if (qrPanel) qrPanel.style.display = 'none';
    if (frame) frame.style.display = 'none';
  }

  function closeAttendanceModal() {
    const modal = document.getElementById('attendance-modal');
    const frame = document.getElementById('attendance-modal-frame');
    if (!modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
    if (frame) frame.src = 'about:blank';
    if (frame) frame.style.display = 'none';
    const qrPanel = document.getElementById('attendance-qr-panel');
    if (qrPanel) qrPanel.style.display = 'none';
    window.clearInterval(attendanceStatusTimer);
  }

  function showAttendanceModalError(message) {
    const loader = document.getElementById('attendance-modal-loader');
    const error = document.getElementById('attendance-modal-error');
    const errorText = document.getElementById('attendance-modal-error-text');
    if (loader) loader.style.display = 'none';
    if (error) error.style.display = 'flex';
    if (errorText) errorText.textContent = message || 'Unable to load attendance session.';
  }

  function loadAttendanceSessionInModal(url) {
    const frame = document.getElementById('attendance-modal-frame');
    const loader = document.getElementById('attendance-modal-loader');
    if (!frame || !url) return;

    frame.onload = function () {
      if (loader) loader.style.display = 'none';
    };

    frame.src = url;
  }

  function showAttendanceQr(payload) {
    const loader = document.getElementById('attendance-modal-loader');
    const qrPanel = document.getElementById('attendance-qr-panel');
    const qrImage = document.getElementById('attendance-qr-image');
    const checkinLink = document.getElementById('attendance-checkin-link');
    const sessionLink = document.getElementById('attendance-session-link');
    const closeButton = document.getElementById('attendance-qr-close-session');
    if (!qrPanel || !qrImage || !payload?.checkin_url) {
      throw new Error('The attendance QR could not be generated.');
    }

    qrImage.src = payload.qr_url || `https://quickchart.io/qr?text=${encodeURIComponent(payload.checkin_url)}&size=320`;
    qrImage.onerror = () => {
      qrImage.alt = 'QR image unavailable. Use the student check-in link below.';
    };
    checkinLink.href = payload.checkin_url;
    sessionLink.href = payload.redirect_url || '#';
    closeButton.dataset.sessionId = payload.session_id || '';
    startAttendanceStatusPolling(payload.session_id);
    if (loader) loader.style.display = 'none';
    qrPanel.style.display = 'block';
  }

  let attendanceStatusTimer = null;
  function startAttendanceStatusPolling(sessionId) {
    window.clearInterval(attendanceStatusTimer);
    if (!sessionId) return;

    const update = async () => {
      try {
        const response = await fetch(`/attendance/${sessionId}/status`, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store',
        });
        const payload = await response.json();
        if (!response.ok || !payload.success) return;
        const count = document.getElementById('attendance-qr-count');
        const checkedIn = Number(payload.checked_in ?? payload.present ?? 0);
        if (count) count.textContent = `${checkedIn} student${checkedIn === 1 ? '' : 's'} checked in`;
        if (payload.status !== 'open') window.clearInterval(attendanceStatusTimer);
      } catch (error) {
        // Keep the QR usable if a status refresh is temporarily unavailable.
      }
    };

    update();
    attendanceStatusTimer = window.setInterval(update, 1500);
  }

  function openQuickAttendance(scheduleId, trigger) {
    if (!scheduleId || !trigger) {
      console.warn('Missing scheduleId or trigger', { scheduleId, trigger });
      return;
    }

    console.log('Opening attendance for schedule:', scheduleId);
    console.log('Quick start URL:', quickStartUrl);
    console.log('CSRF token present:', !!csrf);

    const buttons = document.querySelectorAll('.action-btn, .attendance-check-btn');
    buttons.forEach(btn => btn.disabled = true);
  openAttendanceModal();
  setAttendanceFeedback('Opening attendance...', true);

    fetch(quickStartUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({ schedule_id: scheduleId }),
    })
      .then(async function (response) {
        console.log('Response status:', response.status);
        const text = await response.text();
        console.log('Response text:', text);
        
        let payload;
        try {
          payload = JSON.parse(text);
        } catch (e) {
          console.error('Failed to parse JSON response:', e);
          throw new Error('Invalid server response: ' + text.substring(0, 200));
        }

        console.log('Response payload:', payload);

        if (!response.ok || payload.success === false) {
          throw new Error(payload.message || ('Server error: ' + response.status));
        }

        showAttendanceQr(payload);
        setAttendanceFeedback(payload.message || 'Attendance session started successfully.', false);
      })
      .catch(function (error) {
        console.error('Error opening attendance:', error);
        setAttendanceFeedback(error.message || 'Unable to open attendance.', false);
        showAttendanceModalError(error.message || 'Unable to open attendance session.');
      })
      .finally(function () {
        buttons.forEach(btn => btn.disabled = false);
      });
  }

  document.querySelectorAll('.js-open-attendance').forEach(button => {
    button.addEventListener('click', function () {
      openQuickAttendance(this.dataset.scheduleId, this);
    });
  });

  // Filter tabs
  function filterSessions(status, btn) {
    document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    let n = 0;
    document.querySelectorAll('#sessions-tbody tr').forEach(tr => {
      const show = status === 'all' || tr.dataset.status === status;
      tr.style.display = show ? '' : 'none';
      if (show) n++;
    });
    const vc = document.getElementById('visible-count');
    if (vc) vc.textContent = n;
  }

  if (classSearch) {
    classSearch.addEventListener('input', function () {
      filterClasses(classFilter);
    });
  }

  filterClasses('all');

  // Search
  document.getElementById('session-search')?.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    let n = 0;
    document.querySelectorAll('#sessions-tbody tr').forEach(tr => {
      const match = !q || (tr.dataset.search || '').includes(q);
      tr.style.display = match ? '' : 'none';
      if (match) n++;
    });
    const vc = document.getElementById('visible-count');
    if (vc) vc.textContent = n;
  });

  // Close session AJAX
  function closeSession(id, btn) {
    if (!confirm('Close this session? Students will no longer be able to log in.')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Closing…';
    fetch(`/attendance/${id}/close`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(j => {
      if (j.success) {
        const row = btn.closest('tr');
        if (!row) {
          closeAttendanceModal();
          window.location.reload();
          return;
        }
        row.querySelector('.status-pill').className = 'status-pill status-closed';
        row.querySelector('.status-pill').innerHTML = '<span class="status-dot"></span>Closed';
        row.dataset.status = 'closed';
        const div = btn.previousElementSibling;
        if (div?.classList.contains('action-divider')) div.remove();
        btn.remove();
      } else {
        alert('Failed to close session.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-xmark"></i> Close';
      }
    })
    .catch(() => {
      alert('Network error. Please try again.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-xmark"></i> Close';
    });
  }

  document.querySelectorAll('.js-close-session').forEach(button => {
    button.addEventListener('click', function () {
      closeSession(this.dataset.sessionId, this);
    });
  });

  const rosterModal = document.getElementById('student-roster-modal');
  const rosterRows = document.getElementById('student-roster-rows');
  const rosterFilter = document.getElementById('student-roster-filter');
  let rosterStudents = [];

  function renderStudentRoster() {
    const selectedBlock = rosterFilter?.value || 'all';
    const students = rosterStudents.filter(student => selectedBlock === 'all' || student.block === selectedBlock);
    if (!rosterRows) return;
    rosterRows.innerHTML = students.length
      ? students.map(student => `
        <tr>
          <td><strong>${escapeRosterHtml(student.name)}</strong><small>${escapeRosterHtml(student.student_id)}</small></td>
          <td><span class="roster-block-badge">${escapeRosterHtml(student.block)}</span></td>
          <td><span class="roster-status ${student.checked_in ? 'is-checked' : 'is-absent'}">${student.checked_in ? (student.status === 'late' ? 'Late' : 'Checked in') : 'Absent'}</span></td>
          <td>${escapeRosterHtml(student.time_in || '—')}</td>
        </tr>`).join('')
      : '<tr><td colspan="4" class="roster-empty">No students found for this block.</td></tr>';
  }

  function escapeRosterHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
  }

  function closeStudentRoster() {
    rosterModal?.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.js-view-students').forEach(link => {
    link.addEventListener('click', async event => {
      event.preventDefault();
      if (!rosterModal) return;
      rosterModal.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      rosterRows.setAttribute('aria-busy', 'true');
      rosterRows.innerHTML = '<tr class="roster-skeleton" aria-hidden="true"><td></td><td></td><td></td><td></td></tr><tr class="roster-skeleton" aria-hidden="true"><td></td><td></td><td></td><td></td></tr><tr class="roster-skeleton" aria-hidden="true"><td></td><td></td><td></td><td></td></tr>';
      try {
        const response = await fetch(link.dataset.studentsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
        const payload = await response.json();
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Unable to load students.');
        document.getElementById('student-roster-title').textContent = `${payload.session.course} · ${payload.session.date}`;
        rosterStudents = payload.students || [];
        rosterFilter.value = 'all';
        renderStudentRoster();
        rosterRows.setAttribute('aria-busy', 'false');
      } catch (error) {
        rosterRows.innerHTML = `<tr><td colspan="4" class="roster-empty roster-error">${escapeRosterHtml(error.message)}</td></tr>`;
        rosterRows.setAttribute('aria-busy', 'false');
      }
    });
  });

  rosterFilter?.addEventListener('change', renderStudentRoster);
  document.getElementById('student-roster-close')?.addEventListener('click', closeStudentRoster);
  rosterModal?.addEventListener('click', event => { if (event.target === rosterModal) closeStudentRoster(); });

  // Live date
  const el = document.getElementById('live-date');
  if (el) el.textContent = new Date().toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });

  document.addEventListener('click', function (e) {
    const closeQrButton = e.target.closest('#attendance-qr-close-session');
    if (closeQrButton) {
      e.preventDefault();
      if (closeQrButton.dataset.sessionId) closeSession(closeQrButton.dataset.sessionId, closeQrButton);
      return;
    }

    if (e.target.closest('#attendance-modal-close')) {
      closeAttendanceModal();
      return;
    }

    if (e.target.id === 'attendance-modal') {
      closeAttendanceModal();
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAttendanceModal();
  });
</script>

<div id="attendance-modal" class="attendance-modal">
  <div class="attendance-modal-card">
    <div class="attendance-modal-head">
      <div>
        <div class="attendance-modal-title">Attendance Session</div>
        <div class="attendance-modal-sub">Mark students as Present, Absent, or Excused, and add students as needed.</div>
      </div>
      <button id="attendance-modal-close" class="attendance-modal-close" type="button" aria-label="Close">
        <i class="fas fa-xmark"></i>
      </button>
    </div>

    <div class="attendance-modal-body">
      <div id="attendance-modal-loader" class="attendance-modal-loader">
        <i class="fas fa-spinner fa-spin"></i>
        <span>Loading attendance session...</span>
      </div>

      <div id="attendance-modal-error" class="attendance-modal-error" style="display:none;">
        <i class="fas fa-triangle-exclamation"></i>
        <span id="attendance-modal-error-text">Unable to load attendance session.</span>
      </div>

      <div id="attendance-qr-panel" class="attendance-qr-panel" style="display:none;">
        <div class="attendance-qr-badge"><i class="fas fa-qrcode"></i> LIVE CHECK-IN</div>
        <h2>Scan to record attendance</h2>
        <p class="attendance-qr-subtitle">Students can scan this QR code with their phone to check in.</p>
        <div class="attendance-qr-card">
          <img id="attendance-qr-image" src="" alt="Live attendance QR code">
        </div>
        <div class="attendance-qr-status"><span class="attendance-live-dot"></span> Attendance session is open</div>
        <div id="attendance-qr-count" class="attendance-qr-count">0 students checked in</div>
        <a id="attendance-checkin-link" class="attendance-qr-link" href="#" target="_blank" rel="noopener">Open student check-in link</a>
        <a id="attendance-session-link" class="attendance-session-link" href="#" target="_blank" rel="noopener">Open full attendance session</a>
        <button id="attendance-qr-close-session" class="attendance-qr-close" type="button">
          <i class="fas fa-lock"></i> Close Attendance Session
        </button>
      </div>

      <iframe id="attendance-modal-frame" class="attendance-modal-frame" src="about:blank" title="Attendance Session"></iframe>
    </div>
  </div>
</div>

<style>
.attendance-modal {
  position: fixed;
  inset: 0;
  z-index: 2400;
  background: rgba(11,22,64,.52);
  backdrop-filter: blur(5px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 22px;
}

.attendance-modal.is-open { display: flex; }

.attendance-qr-panel { width: min(100%, 520px); margin: 20px auto; padding: 26px 24px; border: 1px solid #dbe5f5; border-radius: 20px; background: linear-gradient(145deg, #f8fbff 0%, #ffffff 65%); box-shadow: 0 16px 42px rgba(15,23,42,.1); text-align: center; }
.attendance-qr-badge { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px; border: 1px solid #bbf7d0; border-radius: 999px; background: #f0fdf4; color: #15803d; font-size: .7rem; font-weight: 800; letter-spacing: .08em; }
.attendance-qr-panel h2 { margin-top: 18px; color: #0f1729; font-size: 1.35rem; font-weight: 800; }
.attendance-qr-subtitle { margin: 7px auto 20px; max-width: 330px; color: #64748b; font-size: .86rem; line-height: 1.5; }
.attendance-qr-card { display: flex; align-items: center; justify-content: center; width: 250px; height: 250px; margin: 0 auto 16px; padding: 12px; border: 1px solid #dbe5f5; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(37,99,235,.1); }
.attendance-qr-card img { display: block; width: 100%; height: 100%; object-fit: contain; }
.attendance-qr-status { display: inline-flex; align-items: center; gap: 7px; color: #15803d; font-size: .78rem; font-weight: 700; }
.attendance-qr-count { margin-top: 8px; color: #1d4ed8; font-size: .82rem; font-weight: 800; }
.attendance-live-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,.12); }
.attendance-qr-link, .attendance-session-link { display: block; margin-top: 13px; color: #1d4ed8; font-size: .78rem; font-weight: 700; text-decoration: none; }
.attendance-session-link { margin-top: 9px; color: #64748b; font-weight: 600; }
.attendance-qr-close { display: inline-flex; align-items: center; gap: 7px; margin-top: 22px; padding: 10px 16px; border: 1px solid #fecaca; border-radius: 9px; background: #fff1f2; color: #be123c; font: 700 .78rem 'DM Sans', sans-serif; cursor: pointer; }
.attendance-qr-close:hover { background: #ffe4e6; }

.student-roster-modal { position: fixed; inset: 0; z-index: 2600; display: none; align-items: center; justify-content: center; padding: 24px; background: rgba(11,22,64,.5); backdrop-filter: blur(5px); }
.student-roster-modal.is-open { display: flex; }
.student-roster-dialog { width: min(900px, 100%); max-height: min(760px, 90vh); overflow: hidden; border: 1px solid #dbe5f5; border-radius: 18px; background: #fff; box-shadow: 0 22px 70px rgba(15,23,42,.25); }
.student-roster-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding: 22px 24px 18px; border-bottom: 1px solid var(--border); background: linear-gradient(180deg,#fff,#f8fbff); }
.student-roster-eyebrow { color: #1d4ed8; font-size: .7rem; font-weight: 800; letter-spacing: .1em; }
.student-roster-header h2 { margin-top: 7px; color: #0f1729; font-size: 1.2rem; font-weight: 800; }
.student-roster-header p { margin-top: 4px; color: #64748b; font-size: .82rem; }
.student-roster-toolbar { display: flex; align-items: center; gap: 10px; padding: 14px 24px; border-bottom: 1px solid var(--border); background: #fafbfc; }
.student-roster-toolbar label { color: #475569; font-size: .78rem; font-weight: 700; }
.student-roster-toolbar select { min-width: 150px; padding: 8px 11px; border: 1px solid #cdd8eb; border-radius: 8px; background: #fff; color: #1e293b; font: 600 .8rem 'DM Sans',sans-serif; }
.student-roster-table-wrap { max-height: 570px; overflow: auto; }
.student-roster-table { width: 100%; border-collapse: collapse; }
.student-roster-table th { position: sticky; top: 0; padding: 11px 24px; background: #f8fafc; color: #64748b; font-size: .7rem; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.student-roster-table td { padding: 13px 24px; border-top: 1px solid #edf1f7; color: #334155; font-size: .82rem; vertical-align: middle; }
.student-roster-table td strong { display: block; color: #0f1729; font-size: .86rem; }
.student-roster-table td small { display: block; margin-top: 2px; color: #94a3b8; }
.roster-block-badge { display: inline-flex; padding: 5px 9px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: .72rem; font-weight: 700; }
.roster-status { display: inline-flex; padding: 5px 9px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
.roster-status.is-checked { background: #dcfce7; color: #15803d; }
.roster-status.is-absent { background: #fee2e2; color: #b91c1c; }
.roster-empty { padding: 38px 24px !important; color: #64748b !important; text-align: center; }
.roster-skeleton td { height: 48px; border-top: 1px solid #edf1f7; }
.roster-skeleton td::after { content: ''; display: block; height: 12px; border-radius: 5px; background: linear-gradient(90deg,#eef1f5 25%,#fff 50%,#eef1f5 75%); background-size: 200% 100%; animation: rosterSkeleton 1.35s ease-in-out infinite; }
@keyframes rosterSkeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) { .roster-skeleton td::after { animation: none; background: #eef1f5; } }
.roster-error { color: #b91c1c !important; }

@media (max-width: 640px) {
  .student-roster-modal { padding: 12px; }
  .student-roster-table th, .student-roster-table td { padding: 11px 13px; }
  .student-roster-table th:nth-child(2), .student-roster-table td:nth-child(2) { display: none; }
}

.attendance-modal-card {
  width: min(1400px, 100%);
  height: min(92vh, 980px);
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 18px;
  box-shadow: 0 18px 56px rgba(15,23,41,.22);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.attendance-modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(180deg, #fff, #f9fbff);
}

.attendance-modal-title {
  font-family: var(--font-head);
  font-size: .98rem;
  font-weight: 800;
  color: var(--text);
}

.attendance-modal-sub {
  margin-top: 2px;
  font-size: .78rem;
  color: var(--text-3);
}

.attendance-modal-close {
  width: 36px;
  height: 36px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: #fff;
  color: var(--text-3);
  cursor: pointer;
}

.attendance-modal-close:hover {
  background: var(--bg);
  color: var(--text);
}

.attendance-modal-body {
  position: relative;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  background: #fff;
}

.attendance-modal-frame {
  width: 100%;
  height: 100%;
  border: 0;
  background: #fff;
}

.attendance-modal-loader,
.attendance-modal-error {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--text-3);
  font-size: .9rem;
  background: #fff;
  z-index: 2;
}

.attendance-modal-error {
  color: var(--red-text);
  flex-direction: column;
  text-align: center;
}

@media (max-height: 760px) {
  .attendance-qr-panel { margin: 12px auto; padding: 18px 20px; }
  .attendance-qr-panel h2 { margin-top: 12px; font-size: 1.15rem; }
  .attendance-qr-subtitle { margin-bottom: 12px; }
  .attendance-qr-card { width: 200px; height: 200px; margin-bottom: 12px; }
  .attendance-qr-close { margin-top: 14px; }
}
</style>

@include('frontend.faculty.partials.notifications-widget')
</body>
</html>