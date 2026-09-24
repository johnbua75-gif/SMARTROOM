<?php
$facultyName     = $facultyName     ?? request()->user()?->name ?? 'Faculty';
$facultyDept     = $facultyDept     ?? request()->user()?->department ?? 'Faculty';
$facultyEmail    = $facultyEmail    ?? request()->user()?->email ?? '';
$facultyInitials = $facultyInitials ?? strtoupper(substr((string) $facultyName, 0, 1));

// Normalize $session to an array so this partial can accept Eloquent models, arrays, or plain objects.
$session = $session ?? [];
if (!is_array($session)) {
  if (is_object($session) && method_exists($session, 'toArray')) {
    $session = $session->toArray();
  } elseif (is_object($session)) {
    $session = (array) $session;
  } else {
    $session = (array) $session;
  }
}

// Normalize records to a Collection of arrays (supports collections, arrays, models)
$records = $records ?? collect([]);
try {
  $records = collect($records)->map(function($r) {
    if (is_array($r)) return $r;
    if (is_object($r) && method_exists($r, 'toArray')) return $r->toArray();
    if (is_object($r)) return (array) $r;
    return (array) $r;
  });
} catch (\Exception $e) {
  $records = collect([]);
}

$stats   = $stats   ?? ['total'=>0,'present'=>0,'absent'=>0,'excused'=>0,'rate'=>0];

$isOpen = ($session['status'] ?? '') === 'open';
$checkinUrl = !empty($session['token']) ? route('attendance.checkin.show', $session['token']) : null;
$qrUrl = $checkinUrl ? 'https://quickchart.io/qr?text='.urlencode($checkinUrl).'&size=220' : null;

// Pass flash messages to JS safely — avoids Blade conditionals inside <script>
$flashSuccess = session('success') ?? '';
$flashError   = session('error')   ?? '';
$lateCount    = max(0, ($stats['total'] - $stats['present'] - $stats['absent'] - $stats['excused']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $session['course_code'] ?? 'Session' }} – Attendance · SmartDoor</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --yellow: #f5c518;  --navy: #0b1640;    --navy-mid: #1a2f80;   --navy-light: #e8ecfb;
  --bg: #f0f2f8;      --bg-card: #ffffff; --border: #e4e8f0;     --border-strong: #cdd3e0;
  --text: #0f1729;    --text-2: #3d4a5c;  --text-3: #7c8a9e;     --text-4: #b0bac8;
  --green-bg: #e6f9f0;  --green-border: #a7e9c8; --green-text: #0a7a43;
  --blue-bg: #eaf0fd;   --blue-border: #93b8f8;  --blue-text: #1740b0;
  --amber-bg: #fef3e2;  --amber-border: #fcd38a; --amber-text: #b45309;
  --purple-bg: #f4f0fe; --purple-border: #c4b5fd; --purple-text: #5b21b6;
  --red: #dc2626;       --red-bg: #fef2f2;  --red-border: #fca5a5; --red-text: #b91c1c;
  --shadow-xs: 0 1px 2px rgba(15,23,41,.05);
  --shadow-sm: 0 2px 6px rgba(15,23,41,.06), 0 1px 2px rgba(15,23,41,.04);
  --shadow-md: 0 4px 16px rgba(15,23,41,.08), 0 1px 4px rgba(15,23,41,.04);
  --shadow-lg: 0 8px 32px rgba(15,23,41,.10), 0 2px 8px rgba(15,23,41,.06);
  --radius-xs: 6px; --radius-sm: 10px; --radius: 14px; --radius-lg: 18px;
  --sidebar-w: 230px;
  --font-head: 'Plus Jakarta Sans', sans-serif;
  --font-body: 'DM Sans', sans-serif;
}
body { font-family: var(--font-body); background: var(--bg); color: var(--text); min-height: 100vh; display: flex; -webkit-font-smoothing: antialiased; }

/* ══ SIDEBAR ══ */
.sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-w); height: 100vh; background: var(--navy); display: flex; flex-direction: column; overflow: hidden; z-index: 100; }
.sidebar::before { content: ''; position: absolute; inset: 0; background: linear-gradient(160deg, rgba(245,197,24,.06) 0%, transparent 55%); pointer-events: none; }
.sidebar::after { content: ''; position: absolute; bottom: -60px; right: -60px; width: 180px; height: 180px; border-radius: 50%; border: 1px solid rgba(245,197,24,.08); pointer-events: none; }
.sidebar-logo { display: flex; align-items: center; gap: 12px; padding: 28px 20px 24px 24px; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,.06); margin-bottom: 8px; }
.logo-mark { width: 40px; height: 40px; background: var(--yellow); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--navy); flex-shrink: 0; box-shadow: 0 4px 12px rgba(245,197,24,.4); }
.logo-text .brand-psu { font-size: .6rem; font-weight: 600; letter-spacing: .18em; color: rgba(255,255,255,.45); text-transform: uppercase; display: block; margin-bottom: 3px; }
.logo-text .brand-main { font-size: 1.05rem; font-weight: 700; color: #fff; letter-spacing: -.01em; }
.logo-text .brand-main span { color: var(--yellow); }
.nav-section-label { font-size: .68rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.25); padding: 16px 24px 6px; }
.sidebar-nav { list-style: none; overflow-y: auto; padding: 0 12px; }
.sidebar-nav::-webkit-scrollbar { width: 0; }
.sidebar-nav li { margin-bottom: 2px; }
.sidebar-nav a { display: flex; align-items: center; gap: 11px; padding: 11px 12px; text-decoration: none; color: rgba(255,255,255,.6); font-size: .88rem; font-weight: 500; border-radius: var(--radius-sm); transition: all .22s cubic-bezier(.4,0,.2,1); position: relative; overflow: hidden; }
.sidebar-nav a .nav-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .85rem; background: rgba(255,255,255,.05); flex-shrink: 0; transition: all .22s; }
.sidebar-nav a:hover { color: rgba(255,255,255,.9); background: rgba(255,255,255,.06); }
.sidebar-nav a:hover .nav-icon { background: rgba(255,255,255,.1); }
.sidebar-nav a.active { background: rgba(245,197,24,.14); color: var(--yellow); }
.sidebar-nav a.active .nav-icon { background: rgba(245,197,24,.2); color: var(--yellow); }
.sidebar-nav a.active::before { content: ''; position: absolute; left: 0; top: 20%; bottom: 20%; width: 3px; background: var(--yellow); border-radius: 0 2px 2px 0; }
.sidebar-footer { margin-top: auto; padding: 16px 12px 24px; border-top: 1px solid rgba(255,255,255,.06); }
.user-widget { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--radius-sm); background: rgba(255,255,255,.05); margin-bottom: 8px; }
.user-avatar { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; background: var(--navy-mid); border: 2px solid rgba(245,197,24,.4); display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 700; color: var(--yellow); }
.user-widget-name { font-size: .83rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-widget-role { font-size: .73rem; color: rgba(255,255,255,.4); }
.sidebar-logout-btn { display: flex; align-items: center; gap: 10px; padding: 9px 12px; color: rgba(255,255,255,.4); font-size: .84rem; font-weight: 500; border-radius: var(--radius-sm); transition: all .22s; width: 100%; background: none; border: none; cursor: pointer; font-family: inherit; }
.sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,.08); }
.sidebar-logout-btn .nav-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .85rem; background: rgba(255,255,255,.05); }

/* ══ LAYOUT ══ */
.main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

/* ══ TOPBAR ══ */
.topbar { background: rgba(255,255,255,.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-bottom: 1px solid rgba(229,231,235,.8); padding: 0 28px; height: 68px; display: flex; align-items: center; gap: 14px; position: sticky; top: 0; z-index: 60; box-shadow: 0 1px 12px rgba(26,43,109,.06); }
.back-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: var(--radius-sm); border: 1.5px solid var(--border); background: none; font-size: .83rem; font-weight: 600; color: var(--text-2); text-decoration: none; transition: all .16s; white-space: nowrap; }
.back-btn:hover { background: #f4f6fb; border-color: var(--border-strong); color: var(--text); }
.topbar-divider { width: 1px; height: 22px; background: var(--border); flex-shrink: 0; }
.topbar-info .topbar-title { font-family: var(--font-head); font-size: 1rem; font-weight: 800; color: var(--text); line-height: 1.2; }
.topbar-info .topbar-sub { font-size: .78rem; color: var(--text-3); margin-top: 1px; }
.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.topbar-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #4a6cf7, #1a2b6d); border: 2px solid rgba(74,108,247,.3); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .78rem; color: #fff; flex-shrink: 0; }

/* ══ BUTTONS ══ */
.btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: var(--radius-sm); font-size: .85rem; font-weight: 600; font-family: var(--font-body); cursor: pointer; border: none; text-decoration: none; transition: all .18s; white-space: nowrap; line-height: 1; }
.btn-sm { padding: 7px 14px; font-size: .8rem; }
.btn-primary { background: var(--yellow); color: var(--navy); box-shadow: 0 2px 8px rgba(245,197,24,.3); }
.btn-primary:hover { background: #f0bc10; transform: translateY(-1px); }
.btn-outline { background: none; border: 1.5px solid var(--border); color: var(--text-2); }
.btn-outline:hover { background: #f4f6fb; border-color: var(--border-strong); }
.btn-danger { background: var(--red-bg); color: var(--red-text); border: 1.5px solid var(--red-border); }
.btn-danger:hover { background: #fecaca; }
.btn:disabled { opacity: .5; cursor: not-allowed; pointer-events: none; }

/* ══ CONTENT ══ */
.content { padding: 28px 32px 120px; display: flex; flex-direction: column; gap: 20px; }

/* ══ SESSION INFO CARD ══ */
.session-info-card { background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 22px 26px; display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; box-shadow: var(--shadow-sm); }
.sic-left { display: flex; align-items: flex-start; gap: 16px; }
.sic-icon { width: 50px; height: 50px; border-radius: 14px; background: var(--blue-bg); color: var(--blue-text); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; border: 1px solid var(--blue-border); }
.sic-course-code { font-family: var(--font-head); font-size: 1.15rem; font-weight: 800; color: var(--text); }
.sic-course-title { font-size: .85rem; color: var(--text-3); margin-top: 3px; }
.sic-meta { display: flex; align-items: center; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
.sic-meta-item { display: inline-flex; align-items: center; gap: 5px; font-size: .79rem; color: var(--text-3); background: var(--bg); border: 1px solid var(--border); padding: 4px 10px; border-radius: 20px; }
.sic-meta-item i { font-size: .72rem; color: var(--text-4); }
.sic-right { flex-shrink: 0; }
.session-qr { display:flex; align-items:center; gap:12px; padding:10px 12px; border:1px solid var(--blue-border); border-radius:var(--radius); background:var(--blue-bg); }
.session-qr img { width:88px; height:88px; padding:5px; border-radius:9px; background:#fff; }
.session-qr-copy { max-width:150px; }
.session-qr-title { color:var(--blue-text); font-size:.78rem; font-weight:800; }
.session-qr-copy p { margin-top:3px; color:var(--text-2); font-size:.7rem; line-height:1.35; }
.session-qr-link { display:inline-block; margin-top:6px; color:var(--blue-text); font-size:.68rem; font-weight:700; text-decoration:none; }
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: .78rem; font-weight: 700; }
.status-badge .status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.status-badge.open     { background: #dcfce7; color: #15803d; border: 1.5px solid #bbf7d0; }
.status-badge.open .status-dot { background: #16a34a; }
.status-badge.closed   { background: var(--navy-light); color: var(--navy-mid); border: 1.5px solid #c7d2fe; }
.status-badge.closed .status-dot { background: var(--navy-mid); }
.status-badge.cancelled { background: var(--red-bg); color: var(--red-text); border: 1.5px solid var(--red-border); }
.status-badge.cancelled .status-dot { background: var(--red); }

/* ══ STAT PILLS ══ */
.stat-pills { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
.stat-pill { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 14px 14px; display: flex; flex-direction: column; align-items: center; gap: 3px; box-shadow: var(--shadow-xs); transition: box-shadow .18s, transform .18s; }
.stat-pill:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
.sp-val { font-family: var(--font-head); font-size: 1.6rem; font-weight: 800; color: var(--text); line-height: 1; }
.sp-val.green  { color: var(--green-text); }
.sp-val.red    { color: var(--red-text); }
.sp-val.amber  { color: var(--amber-text); }
.sp-val.purple { color: var(--purple-text); }
.sp-lbl { font-size: .71rem; font-weight: 600; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; text-align: center; }
.sp-bar { height: 4px; border-radius: 2px; width: 100%; margin-top: 8px; background: var(--border); overflow: hidden; }
.sp-bar-fill { height: 100%; border-radius: 2px; }

/* ══ CLOSED BANNER ══ */
.closed-banner { background: var(--navy-light); border: 1px solid #c7d2fe; border-radius: var(--radius); padding: 14px 20px; display: flex; align-items: center; gap: 12px; font-size: .86rem; color: var(--navy-mid); font-weight: 500; }
.closed-banner i { font-size: 1rem; flex-shrink: 0; }

/* ══ ROSTER CARD ══ */
.roster-card { background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; }
.roster-header { padding: 18px 22px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
.roster-title { font-family: var(--font-head); font-size: 1rem; font-weight: 700; color: var(--text); }
.roster-subtitle { font-size: .77rem; color: var(--text-3); margin-top: 2px; }
.roster-toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.search-input-wrap { display: flex; align-items: center; gap: 8px; background: var(--bg); border: 1.5px solid var(--border); border-radius: 24px; padding: 7px 14px; transition: border-color .18s; }
.search-input-wrap:focus-within { border-color: var(--blue-border); box-shadow: 0 0 0 3px rgba(59,130,246,.08); background: #fff; }
.search-input-wrap i { color: var(--text-4); font-size: .8rem; }
.search-input-wrap input { border: none; outline: none; background: transparent; font-size: .84rem; font-family: var(--font-body); color: var(--text); width: 160px; }
.search-input-wrap input::placeholder { color: var(--text-4); }
.mark-all-bar { padding: 11px 22px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; background: #fafbfc; flex-wrap: wrap; }
.mark-all-label { font-size: .78rem; font-weight: 700; color: var(--text-3); margin-right: 4px; }
.mark-all-btn { display: inline-flex; align-items: center; gap: 5px; padding: 5px 13px; border-radius: 20px; font-size: .77rem; font-weight: 700; cursor: pointer; border: 1.5px solid transparent; font-family: var(--font-body); transition: all .16s; }
.mark-all-btn.present { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.mark-all-btn.present:hover { background: #bbf7d0; }
.mark-all-btn.absent  { background: var(--red-bg); color: var(--red-text); border-color: var(--red-border); }
.mark-all-btn.absent:hover  { background: #fecaca; }
.mark-all-btn.late    { background: var(--amber-bg); color: var(--amber-text); border-color: var(--amber-border); }
.mark-all-btn.late:hover    { background: #fde68a; }
.roster-table { width: 100%; border-collapse: collapse; }
.roster-table th { padding: 10px 16px; text-align: left; font-size: .72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--text-3); border-bottom: 1px solid var(--border); background: #fafbfc; white-space: nowrap; }
.roster-table td { padding: 11px 16px; font-size: .85rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.roster-table tbody tr:last-child td { border-bottom: none; }
.roster-table tbody tr { transition: background .14s; }
.roster-table tbody tr:hover { background: #f7f8fc; }
.student-cell { display: flex; align-items: center; gap: 10px; }
.student-avatar { width: 34px; height: 34px; border-radius: 10px; background: var(--navy-light); display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 800; color: var(--navy-mid); flex-shrink: 0; }
.student-name-input { border: none; outline: none; background: transparent; font-size: .87rem; font-weight: 600; color: var(--text); font-family: var(--font-body); width: 100%; }
.student-name-input:focus { background: #f4f6fd; border-radius: 4px; padding: 2px 6px; margin: -2px -6px; }
.student-name-input:disabled { color: var(--text-2); }
.student-id-input { border: none; outline: none; background: transparent; font-size: .75rem; color: var(--text-3); font-family: var(--font-body); width: 100%; margin-top: 2px; display: block; }
.student-id-input:disabled { color: var(--text-4); }
.status-selector { display: flex; gap: 4px; flex-wrap: wrap; }
.status-btn { padding: 5px 11px; border-radius: 20px; font-size: .74rem; font-weight: 700; border: 1.5px solid var(--border); cursor: pointer; transition: all .14s; background: #fff; color: var(--text-3); font-family: var(--font-body); }
.status-btn:hover { border-color: var(--border-strong); color: var(--text-2); }
.status-btn.active[data-status="present"] { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.status-btn.active[data-status="absent"]  { background: var(--red-bg); color: var(--red-text); border-color: var(--red-border); }
.status-btn.active[data-status="late"]    { background: var(--amber-bg); color: var(--amber-text); border-color: var(--amber-border); }
.status-btn.active[data-status="excused"] { background: var(--purple-bg); color: var(--purple-text); border-color: var(--purple-border); }
.time-in-input { padding: 6px 10px; border: 1.5px solid var(--border); border-radius: var(--radius-xs); font-size: .8rem; font-family: var(--font-body); outline: none; width: 100px; color: var(--text-2); background: #fff; transition: border-color .18s; }
.time-in-input:focus { border-color: var(--blue-border); box-shadow: 0 0 0 3px rgba(59,130,246,.08); }
.time-in-input:disabled { background: var(--bg); color: var(--text-4); }
.remarks-input { padding: 6px 10px; border: 1.5px solid var(--border); border-radius: var(--radius-xs); font-size: .8rem; font-family: var(--font-body); outline: none; width: 100%; color: var(--text-2); background: #fff; transition: border-color .18s; }
.remarks-input:focus { border-color: var(--blue-border); box-shadow: 0 0 0 3px rgba(59,130,246,.08); }
.remarks-input:disabled { background: var(--bg); color: var(--text-4); }
.row-del-btn { width: 30px; height: 30px; border-radius: var(--radius-xs); border: 1.5px solid var(--border); background: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .78rem; color: var(--text-4); transition: all .14s; }
.row-del-btn:hover { background: var(--red-bg); border-color: var(--red-border); color: var(--red-text); }
.add-row-row td { padding: 10px 16px; border-top: 1px solid var(--border); }
.add-row-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: var(--radius-sm); border: 1.5px dashed var(--border); background: none; font-size: .82rem; font-weight: 600; color: var(--text-3); cursor: pointer; transition: all .16s; font-family: var(--font-body); }
.add-row-btn:hover { border-color: var(--blue-border); color: var(--blue-text); background: var(--blue-bg); }
.empty-state { padding: 48px 24px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; }
.empty-icon { width: 64px; height: 64px; border-radius: 50%; background: var(--navy-light); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--navy-mid); margin-bottom: 4px; }
.empty-text { font-family: var(--font-head); font-size: .98rem; font-weight: 700; color: var(--text); }
.empty-sub  { font-size: .84rem; color: var(--text-3); }

/* ══ SAVE BAR ══ */
.save-bar { position: fixed; left: var(--sidebar-w); right: 0; bottom: 0; background: rgba(255,255,255,.97); backdrop-filter: blur(12px); border-top: 1px solid var(--border); padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; box-shadow: 0 -4px 24px rgba(11,22,64,.07); z-index: 50; }
.save-bar-left { font-size: .83rem; color: var(--text-3); }
.save-bar-left strong { color: var(--text-2); font-weight: 700; }
.save-bar-right { display: flex; gap: 10px; align-items: center; }

/* ══ MODAL ══ */
.modal-overlay { position: fixed; inset: 0; background: rgba(11,22,64,.45); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 4000; opacity: 0; pointer-events: none; transition: opacity .22s; }
.modal-overlay.is-open { opacity: 1; pointer-events: auto; }
.modal { background: #fff; border-radius: var(--radius-lg); width: 100%; max-width: 460px; box-shadow: 0 24px 64px rgba(11,22,64,.18); transform: translateY(12px) scale(.98); transition: transform .22s cubic-bezier(.4,0,.2,1); overflow: hidden; }
.modal-overlay.is-open .modal { transform: translateY(0) scale(1); }
.modal-header { padding: 24px 26px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.modal-title { font-family: var(--font-head); font-size: 1.05rem; font-weight: 800; color: var(--text); }
.modal-sub { font-size: .8rem; color: var(--text-3); margin-top: 3px; }
.modal-close-btn { width: 32px; height: 32px; border-radius: 50%; border: none; background: var(--bg); cursor: pointer; font-size: .85rem; color: var(--text-3); display: flex; align-items: center; justify-content: center; transition: background .15s; flex-shrink: 0; }
.modal-close-btn:hover { background: #fee2e2; color: var(--red); }
.modal-body { padding: 22px 26px; font-size: .86rem; color: var(--text-2); line-height: 1.65; }
.modal-footer { padding: 14px 26px 22px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); }

/* ══ TOAST ══ */
.toast-container { position: fixed; bottom: 90px; right: 28px; display: flex; flex-direction: column; gap: 10px; z-index: 5000; }
.toast { display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-radius: var(--radius-sm); background: #fff; border: 1px solid var(--border); box-shadow: var(--shadow-lg); font-size: .85rem; color: var(--text-2); min-width: 280px; transform: translateX(110%); transition: transform .28s cubic-bezier(.4,0,.2,1); font-weight: 500; }
.toast.is-visible { transform: translateX(0); }
.toast-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
.toast.success .toast-icon { background: var(--green-bg); color: var(--green-text); }
.toast.error   .toast-icon { background: var(--red-bg);   color: var(--red-text); }

@media (max-width: 900px) {
  .content { padding: 20px 16px 120px; }
  .save-bar { left: 0; padding: 12px 16px; }
  .stat-pills { grid-template-columns: repeat(3, 1fr); }
}
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <a href="{{ url('/faculty_dashboard') }}" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Door</span></span>
    </div>
  </a>
  <span class="nav-section-label">Main</span>
  <ul class="sidebar-nav">
    <li><a href="{{ url('/faculty_dashboard') }}" class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-line"></i></span>Dashboard</a></li>
    <li><a href="{{ url('/rooms') }}" class="{{ Request::is('rooms*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-door-open"></i></span>Rooms</a></li>
    <li><a href="{{ url('/faculty-schedule') }}" class="{{ Request::is('faculty-schedule') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clock"></i></span>Schedule</a></li>
    <li><a href="{{ url('/attendance') }}" class="active"><span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance</a></li>
  </ul>
  <span class="nav-section-label">Tools</span>
  <ul class="sidebar-nav">
    <li><a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID Verification</a></li>
    <li><a href="{{ url('/ai-recommendations') }}" class="{{ Request::is('ai-recommendations') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-robot"></i></span>AI Recommendations</a></li>
    <li><a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports</a></li>
  </ul>
  <div class="sidebar-footer">
    <div class="user-widget">
      <div class="user-avatar">{{ $facultyInitials }}</div>
      <div>
        <div class="user-widget-name">{{ $facultyName }}</div>
        <div class="user-widget-role">{{ $facultyDept }}</div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout-btn">
        <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>Sign Out
      </button>
    </form>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <a href="{{ route('faculty.attendance') }}" class="back-btn">
      <i class="fas fa-arrow-left"></i> Attendance
    </a>
    <div class="topbar-divider"></div>
    <div class="topbar-info">
      <div class="topbar-title">{{ $session['course_code'] ?? 'Session' }} — {{ $session['date_short'] ?? '' }}</div>
      <div class="topbar-sub">{{ $session['course_title'] ?? '' }}{{ ($session['room'] ?? '') ? ' · ' . $session['room'] : '' }}</div>
    </div>
    <div class="topbar-right">
      <a href="{{ route('faculty.attendance.export', $session['id']) }}" class="btn btn-outline btn-sm">
        <i class="fas fa-file-csv"></i> Export CSV
      </a>
      @if($isOpen)
      <button class="btn btn-danger btn-sm" id="closeSessionBtn">
        <i class="fas fa-lock"></i> Close Session
      </button>
      @endif
      <div class="topbar-avatar">{{ $facultyInitials }}</div>
    </div>
  </header>

  <div class="content">

    <!-- SESSION INFO CARD -->
    <div class="session-info-card">
      <div class="sic-left">
        <div class="sic-icon"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="sic-course-code">{{ $session['course_code'] ?? 'N/A' }}</div>
          <div class="sic-course-title">{{ $session['course_title'] ?? '' }}</div>
          <div class="sic-meta">
            <span class="sic-meta-item"><i class="fas fa-calendar-day"></i>{{ $session['date'] ?? '' }}</span>
            <span class="sic-meta-item"><i class="fas fa-door-open"></i>{{ $session['room'] ?? 'N/A' }}{{ ($session['building'] ?? '') ? ', '.$session['building'] : '' }}</span>
            <span class="sic-meta-item"><i class="fas fa-clock"></i>Started {{ $session['started_at'] ?? '--:--' }}</span>
            @if(!empty($session['remarks']))
            <span class="sic-meta-item"><i class="fas fa-note-sticky"></i>{{ $session['remarks'] }}</span>
            @endif
          </div>
        </div>
      </div>
      <div class="sic-right">
        @if($isOpen && $qrUrl)
        <div class="session-qr">
          <img src="{{ $qrUrl }}" alt="Student attendance QR code">
          <div class="session-qr-copy">
            <div class="session-qr-title">Scan to attend</div>
            <p>Students scan this code to record attendance.</p>
            <a class="session-qr-link" href="{{ route('faculty.attendance.qr', $session['id']) }}" target="_blank">Open large QR</a>
          </div>
        </div>
        @endif
        <span class="status-badge {{ $session['status'] ?? 'open' }}">
          <span class="status-dot"></span>{{ ucfirst($session['status'] ?? 'open') }}
        </span>
      </div>
    </div>

    <!-- STAT PILLS -->
    <div class="stat-pills">
      <div class="stat-pill">
        <span class="sp-val">{{ $stats['total'] }}</span>
        <span class="sp-lbl">Total</span>
        <div class="sp-bar"><div class="sp-bar-fill" style="width:100%;background:var(--border-strong)"></div></div>
      </div>
      <div class="stat-pill">
        <span class="sp-val green" id="pill-present">{{ $stats['present'] }}</span>
        <span class="sp-lbl">Present</span>
        <div class="sp-bar"><div class="sp-bar-fill" id="bar-present" style="width:{{ $stats['total'] > 0 ? round(($stats['present']/$stats['total'])*100) : 0 }}%;background:linear-gradient(90deg,#16a34a,#22c55e)"></div></div>
      </div>
      <div class="stat-pill">
        <span class="sp-val red" id="pill-absent">{{ $stats['absent'] }}</span>
        <span class="sp-lbl">Absent</span>
        <div class="sp-bar"><div class="sp-bar-fill" id="bar-absent" style="width:{{ $stats['total'] > 0 ? round(($stats['absent']/$stats['total'])*100) : 0 }}%;background:linear-gradient(90deg,#dc2626,#f87171)"></div></div>
      </div>
      <div class="stat-pill">
        <span class="sp-val amber" id="pill-late">{{ $lateCount }}</span>
        <span class="sp-lbl">Late</span>
        <div class="sp-bar"><div class="sp-bar-fill" id="bar-late" style="width:{{ $stats['total'] > 0 ? round(($lateCount/$stats['total'])*100) : 0 }}%;background:linear-gradient(90deg,#d97706,#fbbf24)"></div></div>
      </div>
      <div class="stat-pill">
        <span class="sp-val purple" id="pill-excused">{{ $stats['excused'] }}</span>
        <span class="sp-lbl">Excused</span>
        <div class="sp-bar"><div class="sp-bar-fill" id="bar-excused" style="width:{{ $stats['total'] > 0 ? round(($stats['excused']/$stats['total'])*100) : 0 }}%;background:linear-gradient(90deg,#7c3aed,#a78bfa)"></div></div>
      </div>
      <div class="stat-pill">
        <span class="sp-val green" id="pill-rate">{{ $stats['rate'] }}%</span>
        <span class="sp-lbl">Rate</span>
        <div class="sp-bar"><div class="sp-bar-fill" id="bar-rate" style="width:{{ $stats['rate'] }}%;background:linear-gradient(90deg,#16a34a,#22c55e)"></div></div>
      </div>
    </div>

    <!-- CLOSED BANNER -->
    @if(!$isOpen)
    <div class="closed-banner">
      <i class="fas fa-lock"></i>
      This session is <strong>{{ ucfirst($session['status']) }}</strong>. Records are read-only. You can still export or review attendance below.
    </div>
    @endif

    <!-- ROSTER CARD -->
    <div class="roster-card">
      <div class="roster-header">
        <div>
          <div class="roster-title">Student Roster</div>
          <div class="roster-subtitle">
            @if($isOpen)
              Mark attendance for each student. Auto-saves every 90 seconds.
            @else
              Showing recorded attendance for this session.
            @endif
          </div>
        </div>
        <div class="roster-toolbar">
          <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="rosterSearch" placeholder="Search students…" autocomplete="off">
          </div>
          @if($isOpen)
          <button class="btn btn-primary btn-sm" onclick="showAddStudentModal()">
            <i class="fas fa-user-plus"></i> Add Student
          </button>
          @endif
        </div>
      </div>

      @if($isOpen)
      <div class="mark-all-bar">
        <span class="mark-all-label">Mark all as:</span>
        <button class="mark-all-btn present" onclick="markAll('present')"><i class="fas fa-check"></i> Present</button>
        <button class="mark-all-btn absent"  onclick="markAll('absent')"><i class="fas fa-xmark"></i> Absent</button>
        <button class="mark-all-btn late"    onclick="markAll('late')"><i class="fas fa-clock"></i> Late</button>
      </div>
      @endif

      <div style="overflow-x:auto">
        <table class="roster-table">
          <thead>
            <tr>
              <th style="width:40px">#</th>
              <th>Student</th>
              <th>Status</th>
              <th style="width:108px">Time In</th>
              <th>Remarks</th>
              @if($isOpen)<th style="width:38px"></th>@endif
            </tr>
          </thead>
          <tbody id="rosterBody">
            @forelse($records as $i => $rec)
            <tr class="roster-row" data-index="{{ $i }}">
              <td style="color:var(--text-4);font-size:.78rem;font-weight:600">{{ $i + 1 }}</td>
              <td>
                <div class="student-cell">
                  <div class="student-avatar" id="av-{{ $i }}">{{ strtoupper(substr($rec['student_name'] ?? '?', 0, 1)) }}</div>
                  <div style="flex:1;min-width:0">
                    <input class="student-name-input" id="name-{{ $i }}" type="text"
                           value="{{ $rec['student_name'] }}" placeholder="Student name…"
                           {{ !$isOpen ? 'disabled' : '' }} autocomplete="off"
                           oninput="updateAvatar({{ $i }})">
                    <input class="student-id-input" id="sid-{{ $i }}" type="text"
                           value="{{ $rec['student_id_number'] }}" placeholder="Student ID…"
                           {{ !$isOpen ? 'disabled' : '' }}>
                  </div>
                </div>
              </td>
              <td>
                <div class="status-selector" id="status-sel-{{ $i }}">
                  <button type="button" class="status-btn {{ $rec['status'] === 'present' ? 'active' : '' }}" data-status="present" onclick="setStatus({{ $i }},'present')">Present</button>
                  <button type="button" class="status-btn {{ $rec['status'] === 'absent'  ? 'active' : '' }}" data-status="absent"  onclick="setStatus({{ $i }},'absent')">Absent</button>
                  <button type="button" class="status-btn {{ $rec['status'] === 'late'    ? 'active' : '' }}" data-status="late"    onclick="setStatus({{ $i }},'late')">Late</button>
                  <button type="button" class="status-btn {{ $rec['status'] === 'excused' ? 'active' : '' }}" data-status="excused" onclick="setStatus({{ $i }},'excused')">Excused</button>
                </div>
              </td>
              <td><input type="time" class="time-in-input" id="timein-{{ $i }}" value="{{ $rec['time_in'] }}" {{ !$isOpen ? 'disabled' : '' }}></td>
              <td><input type="text" class="remarks-input" id="rmk-{{ $i }}" value="{{ $rec['remarks'] }}" placeholder="Optional…" {{ !$isOpen ? 'disabled' : '' }}></td>
              @if($isOpen)
              <td><button class="row-del-btn" onclick="deleteRow({{ $i }})" title="Remove"><i class="fas fa-trash-can"></i></button></td>
              @endif
            </tr>
            @empty
            <tr id="emptyRow">
              <td colspan="{{ $isOpen ? 6 : 5 }}">
                <div class="empty-state">
                  <div class="empty-icon"><i class="fas fa-user-graduate"></i></div>
                  <div class="empty-text">No students yet</div>
                  <div class="empty-sub">
                    @if($isOpen)
                      Click "Add Student" to start taking attendance.
                    @else
                      No records were saved for this session.
                    @endif
                  </div>
                  @if($isOpen)
                  <button class="btn btn-primary btn-sm" style="margin-top:8px" onclick="addRow()">
                    <i class="fas fa-plus"></i> Add Student
                  </button>
                  @endif
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
          @if($isOpen)
          <tfoot>
            <tr class="add-row-row">
              <td colspan="6">
                <button class="add-row-btn" onclick="addRow()">
                  <i class="fas fa-plus"></i> Add Another Student
                </button>
              </td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>

  </div><!-- /content -->

  <!-- SAVE BAR -->
  @if($isOpen)
  <div class="save-bar">
    <div class="save-bar-left">
      <span id="saveStatus">Auto-saves every 90 seconds · <strong>Don't forget to save before closing.</strong></span>
    </div>
    <div class="save-bar-right">
      <a href="{{ route('faculty.attendance') }}" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back
      </a>
      <button class="btn btn-primary" id="saveBtn" onclick="saveRecords()">
        <i class="fas fa-floppy-disk"></i> Save Attendance
      </button>
    </div>
  </div>
  @endif

</main>

<!-- ══ CLOSE SESSION MODAL ══ -->
<div class="modal-overlay" id="closeSessionOverlay">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <div>
        <div class="modal-title">Close Attendance Session?</div>
        <div class="modal-sub">Once closed, no further edits can be made.</div>
      </div>
      <button class="modal-close-btn" id="closeModalDismiss"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p>Make sure all student attendance is recorded before closing. The session will be locked and marked as <strong>Closed</strong>.</p>
      <p style="margin-top:10px;color:var(--text-3);font-size:.81rem">You can still view and export records after closing.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" id="cancelCloseBtn">Cancel</button>
      <button class="btn btn-danger btn-sm" id="confirmCloseBtn">
        <i class="fas fa-lock"></i> Close Session
      </button>
    </div>
  </div>
</div>

<!-- ══ ADD STUDENT MODAL ══ -->
<div class="modal-overlay" id="addStudentOverlay">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <div>
        <div class="modal-title">Add Student</div>
        <div class="modal-sub">Search by email or add manually</div>
      </div>
      <button class="modal-close-btn" id="addStudentModalClose"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-tabs">
        <button class="modal-tab-btn active" onclick="switchAddTab('email')">
          <i class="fas fa-envelope"></i> Search by Email
        </button>
        <button class="modal-tab-btn" onclick="switchAddTab('manual')">
          <i class="fas fa-user"></i> Add Manually
        </button>
      </div>

      <!-- Email Search Tab -->
      <div id="emailTab" class="modal-tab-content" style="display:block">
        <div style="margin-top:16px">
          <label style="font-size:.78rem;font-weight:700;color:var(--text-2);display:block;margin-bottom:8px">Student Email</label>
          <input type="email" id="studentEmail" placeholder="student@psu.edu" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:var(--font-body);outline:none" onkeyup="if(event.key==='Enter')searchStudentByEmail()">
          <div id="emailSearchSpinner" style="display:none;margin-top:10px;font-size:.81rem;color:var(--text-3);flex:1;display:flex;align-items:center;gap:8px">
            <i class="fas fa-spinner fa-spin"></i>Searching...
          </div>
          <div id="emailSearchResults" style="margin-top:12px;max-height:240px;overflow-y:auto"></div>
        </div>
      </div>

      <!-- Manual Add Tab -->
      <div id="manualTab" class="modal-tab-content" style="display:none">
        <div style="margin-top:16px">
          <label style="font-size:.78rem;font-weight:700;color:var(--text-2);display:block;margin-bottom:6px">Student Name</label>
          <input type="text" id="manualName" placeholder="John Doe" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:var(--font-body);outline:none;margin-bottom:12px">
          <label style="font-size:.78rem;font-weight:700;color:var(--text-2);display:block;margin-bottom:6px">Student ID</label>
          <input type="text" id="manualId" placeholder="2024-001" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-size:.85rem;font-family:var(--font-body);outline:none">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline btn-sm" id="addStudentCancel">Cancel</button>
      <button class="btn btn-primary btn-sm" id="addStudentConfirm" onclick="confirmAddStudent()">
        <i class="fas fa-plus"></i> Add
      </button>
    </div>
  </div>
</div>

<style>
.modal-tabs { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--border); }
.modal-tab-btn { padding: 10px 14px; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-3); font-size: .82rem; font-weight: 600; cursor: pointer; font-family: var(--font-body); transition: all .16s; }
.modal-tab-btn.active { color: var(--text); border-bottom-color: var(--blue-border); }
.modal-tab-content { margin-top: 16px; }
.search-result-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius-sm); background: var(--bg); border: 1px solid var(--border); cursor: pointer; margin-bottom: 8px; transition: all .16s; }
.search-result-item:hover { background: #f4f6fb; border-color: var(--border-strong); }
.search-result-avatar { width: 32px; height: 32px; border-radius: 8px; background: var(--blue-bg); color: var(--blue-text); display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }
.search-result-info { flex: 1; min-width: 0; }
.search-result-name { font-size: .84rem; font-weight: 600; color: var(--text); }
.search-result-email { font-size: .75rem; color: var(--text-3); margin-top: 1px; }
</style>

<div class="toast-container" id="toastContainer"></div>

<script>
const SESSION_ID = {{ json_encode($session['id'] ?? '') }};
const IS_OPEN    = {{ $isOpen ? 'true' : 'false' }};
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
const SAVE_URL   = '/attendance/sessions/' + SESSION_ID + '/records';
const CLOSE_URL  = '/attendance/sessions/' + SESSION_ID + '/close';

// Flash messages passed from PHP — no Blade conditionals needed inside script
const FLASH_SUCCESS = '{{ addslashes($flashSuccess) }}';
const FLASH_ERROR   = '{{ addslashes($flashError) }}';

const statuses = {};
let rowCount = 0;

document.querySelectorAll('.roster-row').forEach(function(row) {
  const i = parseInt(row.dataset.index);
  rowCount = Math.max(rowCount, i + 1);
  const activeBtn = row.querySelector('.status-btn.active');
  statuses[i] = activeBtn ? activeBtn.dataset.status : 'present';
});

function setStatus(i, val) {
  if (!IS_OPEN) return;
  statuses[i] = val;
  const sel = document.getElementById('status-sel-' + i);
  if (!sel) return;
  sel.querySelectorAll('.status-btn').forEach(function(b) { b.classList.remove('active'); });
  const btn = sel.querySelector('[data-status="' + val + '"]');
  if (btn) btn.classList.add('active');
  updatePills();
}

function markAll(val) {
  if (!IS_OPEN) return;
  document.querySelectorAll('.roster-row').forEach(function(row) {
    setStatus(parseInt(row.dataset.index), val);
  });
}

function updateAvatar(i) {
  const name = ((document.getElementById('name-' + i) || {}).value || '').trim();
  const av = document.getElementById('av-' + i);
  if (av) av.textContent = name ? name[0].toUpperCase() : '?';
}

function addRow() {
  const emptyRow = document.getElementById('emptyRow');
  if (emptyRow) emptyRow.remove();

  const i = rowCount++;
  statuses[i] = 'present';
  const now = new Date();
  const hh  = String(now.getHours()).padStart(2, '0');
  const mm  = String(now.getMinutes()).padStart(2, '0');

  const tr = document.createElement('tr');
  tr.className = 'roster-row';
  tr.dataset.index = i;
  tr.innerHTML =
    '<td style="color:var(--text-4);font-size:.78rem;font-weight:600" class="row-num"></td>' +
    '<td><div class="student-cell">' +
      '<div class="student-avatar" id="av-' + i + '">?</div>' +
      '<div style="flex:1;min-width:0">' +
        '<input class="student-name-input" id="name-' + i + '" type="text" placeholder="Student name…" autocomplete="off" oninput="updateAvatar(' + i + ')">' +
        '<input class="student-id-input"   id="sid-'  + i + '" type="text" placeholder="Student ID…">' +
      '</div></div></td>' +
    '<td><div class="status-selector" id="status-sel-' + i + '">' +
      '<button type="button" class="status-btn active" data-status="present" onclick="setStatus(' + i + ',\'present\')">Present</button>' +
      '<button type="button" class="status-btn"        data-status="absent"  onclick="setStatus(' + i + ',\'absent\')">Absent</button>' +
      '<button type="button" class="status-btn"        data-status="late"    onclick="setStatus(' + i + ',\'late\')">Late</button>' +
      '<button type="button" class="status-btn"        data-status="excused" onclick="setStatus(' + i + ',\'excused\')">Excused</button>' +
    '</div></td>' +
    '<td><input type="time" class="time-in-input" id="timein-' + i + '" value="' + hh + ':' + mm + '"></td>' +
    '<td><input type="text" class="remarks-input" id="rmk-' + i + '" placeholder="Optional…"></td>' +
    '<td><button class="row-del-btn" onclick="deleteRow(' + i + ')" title="Remove"><i class="fas fa-trash-can"></i></button></td>';

  document.getElementById('rosterBody').appendChild(tr);
  document.getElementById('name-' + i).focus();
  renumberRows();
  updatePills();
}

function deleteRow(i) {
  const row = document.querySelector('.roster-row[data-index="' + i + '"]');
  if (row) row.remove();
  delete statuses[i];
  renumberRows();
  updatePills();
  if (!document.querySelector('.roster-row')) {
    const tr = document.createElement('tr');
    tr.id = 'emptyRow';
    tr.innerHTML = '<td colspan="6"><div class="empty-state">' +
      '<div class="empty-icon"><i class="fas fa-user-graduate"></i></div>' +
      '<div class="empty-text">No students yet</div>' +
      '<div class="empty-sub">Click "Add Student" to start taking attendance.</div>' +
      '<button class="btn btn-primary btn-sm" style="margin-top:8px" onclick="addRow()"><i class="fas fa-plus"></i> Add Student</button>' +
      '</div></td>';
    document.getElementById('rosterBody').appendChild(tr);
  }
}

function renumberRows() {
  document.querySelectorAll('.roster-row').forEach(function(r, idx) {
    const cell = r.querySelector('.row-num') || r.cells[0];
    if (cell) cell.textContent = idx + 1;
  });
}

document.getElementById('rosterSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.roster-row').forEach(function(row) {
    const i = row.dataset.index;
    const name = ((document.getElementById('name-' + i) || {}).value || '').toLowerCase();
    row.style.display = name.includes(q) ? '' : 'none';
  });
});

function updatePills() {
  const rows = document.querySelectorAll('.roster-row');
  const total = rows.length;
  var present = 0, absent = 0, late = 0, excused = 0;
  rows.forEach(function(row) {
    const s = statuses[parseInt(row.dataset.index)] || 'present';
    if (s === 'present') present++;
    else if (s === 'absent') absent++;
    else if (s === 'late') late++;
    else if (s === 'excused') excused++;
  });
  const rate = total > 0 ? Math.round(((present + late) / total) * 100) : 0;

  function set(id, val)  { var el = document.getElementById(id); if (el) el.textContent = val; }
  function setW(id, pct) { var el = document.getElementById(id); if (el) el.style.width = pct + '%'; }

  set('pill-present', present); set('pill-absent', absent);
  set('pill-late', late);       set('pill-excused', excused);
  set('pill-rate', rate + '%');
  if (total > 0) {
    setW('bar-present', Math.round((present / total) * 100));
    setW('bar-absent',  Math.round((absent  / total) * 100));
    setW('bar-late',    Math.round((late    / total) * 100));
    setW('bar-excused', Math.round((excused / total) * 100));
    setW('bar-rate', rate);
  }
}

async function saveRecords() {
  const rows = document.querySelectorAll('.roster-row');
  const records = [];
  var hasError = false;

  rows.forEach(function(row) {
    const i    = parseInt(row.dataset.index);
    const name = ((document.getElementById('name-' + i) || {}).value || '').trim();
    if (!name) { hasError = true; return; }
    records.push({
      student_name:      name,
      student_id_number: ((document.getElementById('sid-'    + i) || {}).value || '').trim(),
      status:             statuses[i] || 'present',
      time_in:           ((document.getElementById('timein-' + i) || {}).value || ''),
      remarks:           ((document.getElementById('rmk-'    + i) || {}).value || '').trim(),
    });
  });

  if (hasError) { showToast('Please fill in all student names before saving.', 'error'); return; }

  const saveBtn    = document.getElementById('saveBtn');
  const saveStatus = document.getElementById('saveStatus');
  if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…'; }

  try {
    const res  = await fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ records: records }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      showToast('Attendance saved successfully!', 'success');
      if (saveStatus) saveStatus.innerHTML = 'Last saved at <strong>' + new Date().toLocaleTimeString() + '</strong>';
    } else {
      showToast(data.message || 'Failed to save. Please try again.', 'error');
    }
  } catch(e) {
    showToast('Network error. Please check your connection.', 'error');
  } finally {
    if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save Attendance'; }
  }
}

// Modal controls (guarded to avoid duplicate initialization)
if (!window.__attendance_close_modal_initialized) {
  window.__attendance_close_modal_initialized = true;
  (function(){
    const closeOverlay      = document.getElementById('closeSessionOverlay');
    const closeSessionBtn   = document.getElementById('closeSessionBtn');
    const closeModalDismiss = document.getElementById('closeModalDismiss');
    const cancelCloseBtn    = document.getElementById('cancelCloseBtn');
    const confirmCloseBtn   = document.getElementById('confirmCloseBtn');

    function openCloseModal()  { if (closeOverlay) { closeOverlay.classList.add('is-open');    document.body.style.overflow = 'hidden'; } }
    function closeCloseModal() { if (closeOverlay) { closeOverlay.classList.remove('is-open'); document.body.style.overflow = ''; } }

    if (closeSessionBtn)    closeSessionBtn.addEventListener('click', openCloseModal);
    if (closeModalDismiss)  closeModalDismiss.addEventListener('click', closeCloseModal);
    if (cancelCloseBtn)     cancelCloseBtn.addEventListener('click', closeCloseModal);
    if (closeOverlay)       closeOverlay.addEventListener('click', function(e) { if (e.target === closeOverlay) closeCloseModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCloseModal(); });
  })();
}

const confirmCloseBtn = document.getElementById('confirmCloseBtn');
if (confirmCloseBtn) {
  confirmCloseBtn.addEventListener('click', async function() {
    if (!SESSION_ID) return showToast('Missing session ID.', 'error');
    confirmCloseBtn.disabled = true;
    confirmCloseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Closing…';
    await saveRecords();
    try {
      const res  = await fetch(CLOSE_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF } });
      const data = await res.json();
      if (res.ok && data.success) {
        showToast('Session closed. Redirecting…', 'success');
        setTimeout(function() { window.location.href = '/attendance'; }, 1400);
      } else {
        showToast(data.message || 'Could not close session.', 'error');
        confirmCloseBtn.disabled = false;
        confirmCloseBtn.innerHTML = '<i class="fas fa-lock"></i> Close Session';
      }
    } catch(e) {
      showToast('Network error.', 'error');
      confirmCloseBtn.disabled = false;
      confirmCloseBtn.innerHTML = '<i class="fas fa-lock"></i> Close Session';
    }
  });
}

function showToast(message, type) {
  type = type || 'success';
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<div class="toast-icon"><i class="fas fa-' + (type === 'success' ? 'circle-check' : 'circle-exclamation') + '"></i></div><span>' + message + '</span>';
  c.appendChild(t);
  requestAnimationFrame(function() { t.classList.add('is-visible'); });
  setTimeout(function() { t.classList.remove('is-visible'); setTimeout(function() { t.remove(); }, 300); }, 3500);
}

// Flash messages — no Blade conditionals inside script
if (FLASH_SUCCESS) showToast(FLASH_SUCCESS, 'success');
if (FLASH_ERROR)   showToast(FLASH_ERROR, 'error');

// ── Add Student Modal ──────────────────────────────────
let currentAddTab = 'email';
let selectedStudentData = null;

function showAddStudentModal() {
  const overlay = document.getElementById('addStudentOverlay');
  if (overlay) {
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    document.getElementById('studentEmail').focus();
  }
}

function closeAddStudentModal() {
  const overlay = document.getElementById('addStudentOverlay');
  if (overlay) {
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  document.getElementById('studentEmail').value = '';
  document.getElementById('emailSearchResults').innerHTML = '';
  document.getElementById('manualName').value = '';
  document.getElementById('manualId').value = '';
  selectedStudentData = null;
}

function switchAddTab(tab) {
  currentAddTab = tab;
  document.querySelectorAll('.modal-tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.modal-tab-content').forEach(el => el.style.display = 'none');
  event.target.closest('.modal-tab-btn').classList.add('active');
  document.getElementById(tab + 'Tab').style.display = 'block';
}

async function searchStudentByEmail() {
  const email = document.getElementById('studentEmail').value.trim();
  if (!email) return;

  const spinner = document.getElementById('emailSearchSpinner');
  const results = document.getElementById('emailSearchResults');
  
  spinner.style.display = 'flex';
  results.innerHTML = '';
  selectedStudentData = null;

  try {
    const res = await fetch('/api/students/search?email=' + encodeURIComponent(email), {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
    });
    const data = await res.json();
    spinner.style.display = 'none';

    if (data.success && data.students && data.students.length > 0) {
      results.innerHTML = data.students.map((student, idx) => `
        <div class="search-result-item" data-student-idx="${idx}" onclick="selectStudentByIdx(${idx})">
          <div class="search-result-avatar">${student.name.charAt(0).toUpperCase()}</div>
          <div class="search-result-info">
            <div class="search-result-name">${student.name}</div>
            <div class="search-result-email">${student.email}</div>
          </div>
        </div>
      `).join('');
      // Store student data in a map
      window.__studentSearchResults = data.students;
    } else {
      results.innerHTML = '<div style="padding:12px;text-align:center;color:var(--text-3);font-size:.82rem">No students found with this email.</div>';
    }
  } catch (e) {
    spinner.style.display = 'none';
    results.innerHTML = '<div style="padding:12px;color:var(--red-text);font-size:.82rem">Error searching: ' + e.message + '</div>';
  }
}

function selectStudentByIdx(idx) {
  if (!window.__studentSearchResults || !window.__studentSearchResults[idx]) return;
  const student = window.__studentSearchResults[idx];
  selectStudent(student);
}

function selectStudent(student) {
  selectedStudentData = student;
  document.getElementById('studentEmail').value = student.email;
  document.getElementById('emailSearchResults').innerHTML = `
    <div style="padding:12px;border-radius:var(--radius-sm);background:var(--green-bg);border:1px solid var(--green-border);color:var(--green-text);font-size:.82rem">
      <i class="fas fa-check" style="margin-right:6px"></i><strong>${student.name}</strong> selected
    </div>
  `;
}

function confirmAddStudent() {
  if (currentAddTab === 'email') {
    if (!selectedStudentData) {
      showToast('Please select a student from the search results.', 'error');
      return;
    }
    addRowWithStudent(selectedStudentData.name, selectedStudentData.id || '');
  } else {
    const name = document.getElementById('manualName').value.trim();
    if (!name) {
      showToast('Please enter a student name.', 'error');
      return;
    }
    const id = document.getElementById('manualId').value.trim();
    addRowWithStudent(name, id);
  }
  closeAddStudentModal();
}

function addRowWithStudent(name, id) {
  const emptyRow = document.getElementById('emptyRow');
  if (emptyRow) emptyRow.remove();

  const i = rowCount++;
  statuses[i] = 'present';
  const now = new Date();
  const hh  = String(now.getHours()).padStart(2, '0');
  const mm  = String(now.getMinutes()).padStart(2, '0');

  const tr = document.createElement('tr');
  tr.className = 'roster-row';
  tr.dataset.index = i;
  tr.innerHTML =
    '<td style="color:var(--text-4);font-size:.78rem;font-weight:600" class="row-num"></td>' +
    '<td><div class="student-cell">' +
      '<div class="student-avatar" id="av-' + i + '">' + name.charAt(0).toUpperCase() + '</div>' +
      '<div style="flex:1;min-width:0">' +
        '<input class="student-name-input" id="name-' + i + '" type="text" value="' + name + '" placeholder="Student name…" autocomplete="off" oninput="updateAvatar(' + i + ')">' +
        '<input class="student-id-input"   id="sid-'  + i + '" type="text" value="' + id + '" placeholder="Student ID…">' +
      '</div></div></td>' +
    '<td><div class="status-selector" id="status-sel-' + i + '">' +
      '<button type="button" class="status-btn active" data-status="present" onclick="setStatus(' + i + ',\'present\')">Present</button>' +
      '<button type="button" class="status-btn"        data-status="absent"  onclick="setStatus(' + i + ',\'absent\')">Absent</button>' +
      '<button type="button" class="status-btn"        data-status="late"    onclick="setStatus(' + i + ',\'late\')">Late</button>' +
      '<button type="button" class="status-btn"        data-status="excused" onclick="setStatus(' + i + ',\'excused\')">Excused</button>' +
    '</div></td>' +
    '<td><input type="time" class="time-in-input" id="timein-' + i + '" value="' + hh + ':' + mm + '"></td>' +
    '<td><input type="text" class="remarks-input" id="rmk-' + i + '" placeholder="Optional…"></td>' +
    '<td><button class="row-del-btn" onclick="deleteRow(' + i + ')" title="Remove"><i class="fas fa-trash-can"></i></button></td>';

  document.getElementById('rosterBody').appendChild(tr);
  renumberRows();
  updatePills();
  showToast('Student added: ' + name, 'success');
}

// Add Student Modal Event Listeners
if (!window.__attendance_add_modal_initialized) {
  window.__attendance_add_modal_initialized = true;
  const addStudentOverlay = document.getElementById('addStudentOverlay');
  const addStudentModalClose = document.getElementById('addStudentModalClose');
  const addStudentCancel = document.getElementById('addStudentCancel');
  
  if (addStudentModalClose) addStudentModalClose.addEventListener('click', closeAddStudentModal);
  if (addStudentCancel) addStudentCancel.addEventListener('click', closeAddStudentModal);
  if (addStudentOverlay) {
    addStudentOverlay.addEventListener('click', function(e) {
      if (e.target === addStudentOverlay) closeAddStudentModal();
    });
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('addStudentOverlay').classList.contains('is-open')) {
      closeAddStudentModal();
    }
  });
}

// Auto-save every 90s for open sessions
if (IS_OPEN) { setInterval(saveRecords, 90000); }
</script>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>