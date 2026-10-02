<?php
$facultyName = $facultyName ?? request()->user()?->name ?? 'Faculty';
$facultyDept = $facultyDept ?? request()->user()?->department ?? 'Faculty';
$facultyInitials = $facultyInitials ?? strtoupper(substr((string) $facultyName, 0, 1));

$user = $user ?? $facultyName;

$stats = $stats ?? [];
$departments = $departments ?? [];
$reports = $reports ?? [];
$bar_data = $bar_data ?? [0, 0, 0, 0, 0, 0];
$bar_labels = $bar_labels ?? ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
$line_data = $line_data ?? [0, 0, 0, 0, 0, 0, 0];
$line_labels = $line_labels ?? ["7 AM", "9 AM", "11 AM", "1 PM", "3 PM", "5 PM", "7 PM"];

// SVG line chart coords
$pts = max(1, count($line_data));
$lineMax = 60;
$svgW = 420; $svgH = 180; $padX = 10; $padY = 16;
$xs = []; $ys = [];
for ($i = 0; $i < $pts; $i++) {
    $xs[] = $padX + ($i / max(1, ($pts - 1))) * ($svgW - $padX*2);
  $ys[] = $svgH - $padY - ($line_data[$i]/$lineMax)*($svgH - $padY*2);
}

// Build a smooth quadratic curve path from points
$curvePath = "M {$xs[0]} {$ys[0]}";
for ($i = 1; $i < $pts; $i++) {
  $midX = ($xs[$i - 1] + $xs[$i]) / 2;
  $midY = ($ys[$i - 1] + $ys[$i]) / 2;
  $curvePath .= " Q {$xs[$i - 1]} {$ys[$i - 1]} {$midX} {$midY}";
}
$curvePath .= " T {$xs[$pts - 1]} {$ys[$pts - 1]}";
$areaPath = $curvePath . " L {$xs[$pts-1]} {$svgH} L {$xs[0]} {$svgH} Z";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>SmartDoor – Analytics & Reports</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root {
  /* palette */
  --yellow:     #f5c518;
  --navy:       #0b1640;
  --navy-mid:   #1a2f80;
  --navy-light: #e8ecfb;
  --white:      #ffffff;
  --bg:         #f0f2f6;
  --border:     #e5e7eb;
  --text:       #111827;
  --text-2:     #374151;
  --text-3:     #6b7280;
  --text-4:     #9ca3af;
  /* accents */
  --blue:       #3b82f6;
  --blue-mid:   #1d4ed8;
  --blue-bg:    #eff6ff;
  --blue-bd:    #bfdbfe;
  --green:      #16a34a;
  --green-bg:   #dcfce7;
  --green-bd:   #bbf7d0;
  --purple:     #7c3aed;
  --purple-bg:  #ede9fe;
  --red:        #dc2626;
  --red-bg:     #fee2e2;
  --red-bd:     #fca5a5;
  --orange:     #f59e0b;
  /* shadows */
  --sh-sm:  0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
  --sh-md:  0 4px 16px rgba(0,0,0,.08),0 1px 4px rgba(0,0,0,.04);
  --sh-lg:  0 8px 32px rgba(0,0,0,.10),0 2px 8px rgba(0,0,0,.06);
  /* radii */
  --r-xs:6px; --r-sm:10px; --r:14px; --r-lg:18px;
  /* layout */
  --sidebar-w:240px;
  /* fonts */
  --fh:'Plus Jakarta Sans',sans-serif;
  --fb:'DM Sans',sans-serif;
}

body{font-family:var(--fb);background:var(--bg);color:var(--text);display:flex;min-height:100vh;-webkit-font-smoothing:antialiased}

/* ════════════════════════════════
   SIDEBAR — UNCHANGED
════════════════════════════════ */
.sidebar {
  position: fixed; left: 0; top: 0;
  width: var(--sidebar-w); height: 100vh;
  background: var(--navy);
  display: flex; flex-direction: column;
  overflow: hidden; z-index: 100;
}
.sidebar::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(245,197,24,0.06) 0%, transparent 55%);
  pointer-events: none;
}
.sidebar::after {
  content: ''; position: absolute;
  bottom: -60px; right: -60px;
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
  color: rgba(255,255,255,0.45); text-transform: uppercase;
  display: block; margin-bottom: 3px;
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
  display: flex; align-items: center; gap: 11px; padding: 11px 12px;
  text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.88rem; font-weight: 500;
  border-radius: var(--radius-sm);
  transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
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
.sidebar-footer {
  margin-top: auto; padding: 16px 12px 24px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.user-widget {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px;
  border-radius: var(--radius-sm); background: rgba(255,255,255,0.05); margin-bottom: 8px;
}
.user-avatar {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  background: var(--navy-mid); border: 2px solid rgba(245,197,24,0.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem; font-weight: 700; color: var(--yellow);
}
.user-widget-info { flex: 1; min-width: 0; }
.user-widget-name {
  font-size: 0.83rem; font-weight: 600; color: #fff;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-widget-role { font-size: 0.73rem; color: rgba(255,255,255,0.4); }
.sidebar-logout-btn {
  display: flex; align-items: center; gap: 10px; padding: 9px 12px;
  color: rgba(255,255,255,0.4); font-size: 0.84rem; font-weight: 500;
  border-radius: var(--radius-sm); transition: all 0.22s; width: 100%;
  background: none; border: none; cursor: pointer; font-family: inherit;
}
.sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,0.08); }


/* ════════════════════════════════
   MAIN LAYOUT
════════════════════════════════ */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-width:0}

/* ── TOPBAR ── */
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50;box-shadow:0 1px 0 var(--border)}
.topbar-search{flex:1;max-width:400px;position:relative}
.topbar-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-4);font-size:.85rem;pointer-events:none}
.topbar-search input{width:100%;padding:9px 16px 9px 38px;border:1.5px solid var(--border);border-radius:24px;font-size:.88rem;color:var(--text);background:var(--bg);outline:none;font-family:var(--fb);transition:border-color .2s,box-shadow .2s}
.topbar-search input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09)}
.topbar-search input::placeholder{color:var(--text-4)}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:14px}
.topbar-profile{display:flex;align-items:center;gap:10px}
.tp-name{font-size:.88rem;font-weight:700;color:var(--text);line-height:1.25}
.tp-role{font-size:.74rem;color:var(--text-3)}
.tp-avatar{width:38px;height:38px;border-radius:50%;overflow:hidden;border:2px solid var(--border)}
.tp-avatar img{width:100%;height:100%;object-fit:cover}
.tp-avatar-initials{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:var(--blue-mid);background:#e0e7ff}

/* ════════════════════════════════
   CONTENT
════════════════════════════════ */
.content{padding:30px 32px 52px;display:flex;flex-direction:column;gap:22px}

/* ── PAGE HEADER ── */
.page-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px}
.page-title{font-family:var(--fh);font-size:1.4rem;font-weight:800;color:var(--text);letter-spacing:-.02em}
.page-sub{font-size:.82rem;color:var(--text-3);margin-top:3px}
.header-actions{display:flex;gap:10px}
.btn-outline{display:flex;align-items:center;gap:7px;padding:9px 16px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:var(--white);font-family:var(--fb);font-size:.82rem;font-weight:600;color:var(--text-2);cursor:pointer;transition:all .15s}
.btn-outline:hover{border-color:#93c5fd;background:#f8fbff}
.btn-primary{display:flex;align-items:center;gap:7px;padding:9px 18px;border:none;border-radius:var(--r-sm);background:var(--navy);color:#fff;font-family:var(--fb);font-size:.82rem;font-weight:700;cursor:pointer;transition:all .18s}
.btn-primary:hover{background:var(--navy-mid);transform:translateY(-1px);box-shadow:0 4px 14px rgba(11,22,64,.28)}

/* ── STAT CARDS ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.stat-card{background:var(--white);border-radius:var(--r);border:1.5px solid var(--border);padding:20px 22px 18px;box-shadow:var(--sh-sm);transition:transform .18s,box-shadow .18s}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--sh-md)}
.stat-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.stat-icon-box{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:.9rem}
.sib-blue  {background:var(--blue-bg);color:var(--blue-mid)}
.sib-green {background:var(--green-bg);color:var(--green)}
.sib-purple{background:var(--purple-bg);color:var(--purple)}
.sib-red   {background:var(--red-bg);color:var(--red)}
.stat-badge{font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:20px}
.sb-up  {background:var(--green-bg);color:#15803d}
.sb-down{background:var(--red-bg);color:var(--red)}
.stat-label{font-size:.76rem;color:var(--text-3);font-weight:500;margin-bottom:4px}
.stat-value{font-family:var(--fh);font-size:1.8rem;font-weight:800;color:var(--text);letter-spacing:-.04em;line-height:1}

/* ════════════════════════════════
   CHART SECTION
════════════════════════════════ */
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.chart-card{background:var(--white);border-radius:var(--r);border:1.5px solid var(--border);padding:22px 24px 18px;box-shadow:var(--sh-sm)}

.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.card-title{font-family:var(--fh);font-size:.95rem;font-weight:700;color:var(--text)}
.card-subtitle{font-size:.73rem;color:var(--text-3);margin-top:2px}
.rt-badge{display:flex;align-items:center;gap:5px;font-size:.71rem;font-weight:700;color:var(--blue);background:var(--blue-bg);padding:4px 10px;border-radius:20px;border:1px solid var(--blue-bd)}
.rt-dot{width:7px;height:7px;border-radius:50%;background:var(--blue);animation:rtpulse 1.5s infinite}
@keyframes rtpulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.85)}}

/* ── BAR CHART ── */
.bar-chart-wrap{display:flex;gap:0;align-items:flex-end}
.bar-y-axis{display:flex;flex-direction:column;justify-content:space-between;height:160px;padding-right:10px;flex-shrink:0}
.bar-y-axis span{font-size:.68rem;color:var(--text-4);line-height:1;text-align:right;width:24px}
.bar-canvas{flex:1;display:flex;flex-direction:column;gap:0}
.bar-grid-lines{flex:1;position:relative;height:160px}
.bar-grid-lines::before,.bar-grid-lines::after{content:'';position:absolute;left:0;right:0;height:1px;background:var(--border)}
.bar-grid-lines::before{top:0}
.bar-grid-lines::after{bottom:0}
.bar-grid-mid{position:absolute;left:0;right:0;height:1px;background:var(--border);top:50%}
.bar-grid-q1{position:absolute;left:0;right:0;height:1px;background:var(--border);top:25%}
.bar-grid-q3{position:absolute;left:0;right:0;height:1px;background:var(--border);top:75%}
.bars-row{position:absolute;bottom:0;left:0;right:0;top:0;display:flex;align-items:flex-end;gap:6px;padding:0 4px}
.bar-item{flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;gap:0;position:relative;cursor:pointer}
.bar-item:hover .bar-fill{opacity:.82;transform:scaleY(1.015);transform-origin:bottom}
.bar-item:hover .bar-tooltip{opacity:1;transform:translateX(-50%) translateY(-2px)}
.bar-fill{border-radius:5px 5px 0 0;transition:opacity .2s,transform .18s;width:100%;background:linear-gradient(180deg,#3b82f6 0%,#1d4ed8 100%)}
.bar-tooltip{position:absolute;top:-34px;left:50%;transform:translateX(-50%) translateY(0);background:var(--text);color:#fff;font-size:.7rem;font-weight:700;padding:4px 8px;border-radius:6px;white-space:nowrap;opacity:0;transition:all .18s;pointer-events:none;z-index:10}
.bar-tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:var(--text)}
.bar-x-axis{display:flex;padding:8px 4px 0;gap:6px}
.bar-x-label{flex:1;font-size:.72rem;color:var(--text-3);text-align:center;font-weight:500}

/* ── LINE CHART ── */
.line-chart-wrap{display:flex;gap:10px;align-items:stretch}
.lc-y-axis{height:180px;display:flex;flex-direction:column;justify-content:space-between;align-items:flex-end;padding:0 8px 0 0;min-width:26px}
.lc-y-axis span{font-size:.74rem;color:#94a3b8;line-height:1}
.lc-plot{flex:1;min-width:0}
svg.lc{width:100%;overflow:visible;display:block}
.lc-x-axis{display:flex;justify-content:space-between;padding:8px 0 0;margin-top:2px}
.lc-x-label{font-size:.68rem;color:var(--text-3);font-weight:500;text-align:center;flex:1}

/* ════════════════════════════════
   BOTTOM ROW
════════════════════════════════ */
.bottom-row{display:grid;grid-template-columns:1fr 1.65fr;gap:16px}

/* ── DONUT CARD ── */
.donut-card{background:var(--white);border-radius:var(--r);border:1.5px solid var(--border);padding:22px;box-shadow:var(--sh-sm)}
.donut-wrap{display:flex;justify-content:center;margin:18px 0 16px;position:relative}
.donut-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
.donut-center-val{font-family:var(--fh);font-size:1.4rem;font-weight:800;color:var(--text);line-height:1}
.donut-center-lbl{font-size:.65rem;color:var(--text-3);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:2px}
.donut-legend{display:flex;flex-direction:column;gap:8px}
.legend-row{display:flex;align-items:center;gap:9px}
.legend-swatch{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.legend-name{font-size:.8rem;color:var(--text-2);flex:1}
.legend-bar-wrap{flex:2;height:5px;background:var(--border);border-radius:3px;overflow:hidden}
.legend-bar-fill{height:100%;border-radius:3px}
.legend-val{font-size:.8rem;font-weight:700;color:var(--text);min-width:28px;text-align:right}

/* ── REPORTS CARD ── */
.reports-card{background:var(--white);border-radius:var(--r);border:1.5px solid var(--border);padding:22px;box-shadow:var(--sh-sm)}
.reports-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.view-all-btn{display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--blue-mid);font-weight:700;text-decoration:none;padding:5px 12px;border-radius:20px;border:1.5px solid var(--blue-bd);background:var(--blue-bg);transition:all .15s}
.view-all-btn:hover{background:#dbeafe}
.report-item{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--border)}
.report-item:last-child{border-bottom:none;padding-bottom:0}
.report-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:var(--bg);flex-shrink:0}
.report-icon i{font-size:.9rem;color:var(--text-3)}
.report-info{flex:1;min-width:0}
.report-name{font-size:.88rem;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.report-meta{display:flex;align-items:center;gap:8px;margin-top:3px}
.report-date{font-size:.73rem;color:var(--text-3)}
.report-size{font-size:.73rem;color:var(--text-4)}
.report-type-badge{font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:4px;letter-spacing:.04em}
.dl-btn{width:34px;height:34px;border-radius:9px;border:1.5px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-3);font-size:.82rem;transition:all .15s;flex-shrink:0}
.dl-btn:hover{background:var(--blue-bg);border-color:var(--blue-bd);color:var(--blue-mid)}

/* ════════════════════════════════
   ANIMATIONS
════════════════════════════════ */
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.page-header  {animation:fadeUp .35s both .03s}
.stats-grid   {animation:fadeUp .35s both .08s}
.charts-row   {animation:fadeUp .35s both .14s}
.bottom-row   {animation:fadeUp .35s both .20s}

/* ── RESPONSIVE ── */

</style>
@include('partials.pro-motion')
</head>
<body>

<div class="sidebar">
  <a href="<?= htmlspecialchars(url('/dashboard')) ?>" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Door</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main Menu</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ url('/faculty_dashboard') }}"
         class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
        Dashboard
      </a>
    </li>
    <li>
      <a href="{{ url('/rooms') }}"
         class="{{ Request::is('rooms*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-door-open"></i></span>
        Rooms
      </a>
    </li>
    <li>
      <a href="{{ url('/faculty-schedule') }}"
         class="{{ Request::is('faculty-schedule') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clock"></i></span>
        Schedule
      </a>
    </li>
    <li>
      <a href="{{ url('/attendance') }}" class="{{ Request::is('attendance*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>
        Attendance
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
        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
        Reports
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
    <form method="POST" action="<?= htmlspecialchars(url('/logout')) ?>">
      <?= csrf_field(); ?>
      <button type="submit" class="sidebar-logout-btn">
        <i class="fas fa-arrow-right-from-bracket"></i>
        Sign Out
      </button>
    </form>
  </div>
</div>


<!-- ════ MAIN ════ -->
<div class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-search">
      <i class="fas fa-magnifying-glass"></i>
      <input type="text" placeholder="Search for classrooms, faculty, or subjects…"/>
    </div>
    <div class="topbar-right">
      <div class="topbar-profile">
        <div>
          <div class="tp-name"><?= htmlspecialchars($user) ?></div>
          <div class="tp-role"><?= htmlspecialchars($facultyDept) ?></div>
        </div>
        <div class="tp-avatar">
          <div class="tp-avatar-initials"><?= htmlspecialchars($facultyInitials) ?></div>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENT -->
  <div class="content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <div class="page-title">System Analytics &amp; Reports</div>
        <div class="page-sub">Comprehensive overview of PSU SmartRoom utilization and efficiency.</div>
      </div>
      <div class="header-actions">
        <button class="btn-outline"><i class="fas fa-sliders"></i> Filter</button>
        <button class="btn-primary" onclick="window.location.href='<?= htmlspecialchars(route('faculty.reports.export.csv')) ?>'"><i class="fas fa-file-arrow-down"></i> Export CSV</button>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <?php
      $sibMap = ['blue'=>'sib-blue','green'=>'sib-green','purple'=>'sib-purple','red'=>'sib-red'];
      foreach ($stats as $s):
        $sib = $sibMap[$s['color']] ?? 'sib-blue';
        $sbcls = $s['positive'] ? 'sb-up' : 'sb-down';
      ?>
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon-box <?= $sib ?>"><i class="<?= $s['icon'] ?>"></i></div>
          <span class="stat-badge <?= $sbcls ?>"><?= $s['change'] ?></span>
        </div>
        <div class="stat-label"><?= htmlspecialchars($s['label']) ?></div>
        <div class="stat-value"><?= htmlspecialchars($s['value']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">

      <!-- BAR CHART -->
      <div class="chart-card">
        <div class="card-header">
          <div>
            <div class="card-title">Classroom Usage Trends</div>
            <div class="card-subtitle">Weekly booking activity per day</div>
          </div>
          <span style="font-size:.72rem;color:var(--text-3);font-weight:600;background:var(--bg);padding:4px 10px;border-radius:20px;border:1px solid var(--border)">This Week</span>
        </div>
        <div class="bar-chart-wrap">
          <!-- Y axis -->
          <div class="bar-y-axis">
            <span>100</span>
            <span>75</span>
            <span>50</span>
            <span>25</span>
            <span>0</span>
          </div>
          <!-- Canvas -->
          <div class="bar-canvas" style="flex:1">
            <div class="bar-grid-lines" style="position:relative;height:160px">
              <div class="bar-grid-mid"></div>
              <div class="bar-grid-q1"></div>
              <div class="bar-grid-q3"></div>
              <div class="bars-row">
                <?php foreach ($bar_data as $i => $val): ?>
                <div class="bar-item">
                  <div class="bar-tooltip"><?= $val ?>%</div>
                  <div class="bar-fill" style="height:<?= ($val/100)*100 ?>%"></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="bar-x-axis">
              <?php foreach ($bar_labels as $lbl): ?>
              <div class="bar-x-label"><?= $lbl ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- LINE CHART -->
      <div class="chart-card">
        <div class="card-header">
          <div>
            <div class="card-title">Peak Hours Capacity Load</div>
            <div class="card-subtitle">Real-time room utilization by hour</div>
          </div>
          <span style="font-size:.72rem;color:var(--text-3);font-weight:600;background:var(--bg);padding:4px 10px;border-radius:20px;border:1px solid var(--border)">Today</span>
        </div>
        <div class="line-chart-wrap">
          <div class="lc-y-axis">
            <span>60</span>
            <span>45</span>
            <span>30</span>
            <span>15</span>
            <span>0</span>
          </div>
          <div class="lc-plot">
            <svg class="lc" viewBox="0 0 <?=$svgW?> <?=$svgH?>" preserveAspectRatio="none" style="height:180px">
              <defs>
                <linearGradient id="lcGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#1e3a8a" stop-opacity="0.16"/>
                  <stop offset="100%" stop-color="#1e3a8a" stop-opacity="0.02"/>
                </linearGradient>
              </defs>

              <!-- dashed horizontal guides -->
              <?php foreach ([0, 15, 30, 45, 60] as $gVal):
                $gy = round($svgH - $padY - ($gVal / $lineMax) * ($svgH - $padY * 2), 2);
              ?>
              <line x1="<?=$padX?>" y1="<?=$gy?>" x2="<?=$svgW - $padX?>" y2="<?=$gy?>" stroke="#dbe4f0" stroke-width="1" stroke-dasharray="3 4"/>
              <?php endforeach; ?>

              <!-- area fill + smooth line -->
              <path d="<?=$areaPath?>" fill="url(#lcGrad)"/>
              <path d="<?=$curvePath?>" fill="none" stroke="#1f3f94" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="lc-x-axis">
              <?php foreach($line_labels as $lbl): ?><span class="lc-x-label"><?=$lbl?></span><?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /charts-row -->

    <!-- Bottom Row -->
    <div class="bottom-row">

      <!-- DONUT CARD -->
      <div class="donut-card">
        <div class="card-header">
          <div>
            <div class="card-title">Usage by Department</div>
            <div class="card-subtitle">Current semester distribution</div>
          </div>
        </div>
        <?php
          $cx=90;$cy=90;$r=72;$sw=20;
          $circ=2*M_PI*$r;
          $offset=0;
        ?>
        <div class="donut-wrap">
          <svg width="180" height="180" viewBox="0 0 180 180">
            <!-- bg ring -->
            <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="var(--border)" stroke-width="<?=$sw?>"/>
            <?php foreach($departments as $d):
              $dash=($d['pct']/100)*$circ;
              $gap=$circ-$dash;
            ?>
            <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none"
              stroke="<?=htmlspecialchars($d['color'])?>"
              stroke-width="<?=$sw?>"
              stroke-dasharray="<?=round($dash,2)?> <?=round($gap,2)?>"
              stroke-dashoffset="<?=round(-($offset/100)*$circ,2)?>"
              transform="rotate(-90 <?=$cx?> <?=$cy?>)"
              stroke-linecap="butt"/>
            <?php $offset+=$d['pct']; endforeach; ?>
          </svg>
          <div class="donut-center">
            <div class="donut-center-val"><?= array_sum(array_column($departments,'pct')) ?>%</div>
            <div class="donut-center-lbl">Total</div>
          </div>
        </div>
        <div class="donut-legend">
          <?php foreach($departments as $d): ?>
          <div class="legend-row">
            <div class="legend-swatch" style="background:<?=$d['color']?>"></div>
            <div class="legend-name"><?=htmlspecialchars($d['name'])?></div>
            <div class="legend-bar-wrap">
              <div class="legend-bar-fill" style="width:<?=$d['pct']?>%;background:<?=$d['color']?>"></div>
            </div>
            <div class="legend-val"><?=$d['pct']?>%</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- REPORTS CARD -->
      <div class="reports-card">
        <div class="reports-top">
          <div>
            <div class="card-title">Recent Generated Reports</div>
            <div class="card-subtitle">Last 7 days</div>
          </div>
          <a href="#" class="view-all-btn">View All <i class="fas fa-arrow-right" style="font-size:.65rem"></i></a>
        </div>
        <?php foreach($reports as $r): ?>
        <div class="report-item">
          <div class="report-icon"><i class="fas fa-file-lines"></i></div>
          <div class="report-info">
            <div class="report-name"><?=htmlspecialchars($r['title'])?></div>
            <div class="report-meta">
              <span class="report-date"><?=htmlspecialchars($r['date'])?></span>
              <span class="report-size"><?=htmlspecialchars($r['size'])?></span>
              <span class="report-type-badge" style="background:<?=$r['type']==='PDF'?'#fee2e2':'#dcfce7'?>;color:<?=$r['type_color']?>"><?=$r['type']?></span>
            </div>
          </div>
          <button class="dl-btn" title="Download" onclick="window.location.href='<?= htmlspecialchars(route('faculty.reports.export.csv')) ?>'"><i class="fas fa-arrow-down"></i></button>
        </div>
        <?php endforeach; ?>
      </div>

    </div><!-- /bottom-row -->

  </div><!-- /content -->
</div><!-- /main -->

@include('frontend.faculty.partials.notifications-widget')
</body>
</html>