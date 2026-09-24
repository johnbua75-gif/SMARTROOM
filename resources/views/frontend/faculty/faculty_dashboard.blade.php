<?php
$dateFormatted = $dateFormatted ?? now()->format('h:i A • l, F j, Y');
$facultyName = $facultyName ?? request()->user()?->name ?? 'Faculty';
$facultyDept = $facultyDept ?? request()->user()?->department ?? 'Faculty';
$facultyEmail = $facultyEmail ?? request()->user()?->email ?? '';
$facultyInitials = $facultyInitials ?? strtoupper(substr((string) $facultyName, 0, 1));
$stats = $stats ?? [
  'available_rooms' => 0,
  'my_reservations' => 0,
  'active_classes' => 0,
  'total_students' => 0,
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – SmartDoor</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ── RESET ─────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  /* Brand */
  --yellow:        #f5c518;
  --yellow-light:  #fef9e7;
  --navy:          #0b1640;
  --navy-mid:      #1a2f80;
  --navy-light:    #e8ecfb;

  /* Surface */
  --white:         #ffffff;
  --bg:            #f0f2f8;
  --bg-card:       #ffffff;
  --border:        #e4e8f0;
  --border-strong: #cdd3e0;

  /* Text */
  --text:          #0f1729;
  --text-2:        #3d4a5c;
  --text-3:        #7c8a9e;
  --text-4:        #b0bac8;

  /* Semantic */
  --green:         #0f9d58;
  --green-mid:     #12b564;
  --green-bg:      #e6f9f0;
  --green-border:  #a7e9c8;
  --green-text:    #0a7a43;

  --blue:          #1a56db;
  --blue-mid:      #2563eb;
  --blue-bg:       #eaf0fd;
  --blue-border:   #93b8f8;
  --blue-text:     #1740b0;

  --amber:         #d97706;
  --amber-bg:      #fef3e2;
  --amber-border:  #fcd38a;
  --amber-text:    #b45309;

  --purple:        #7c3aed;
  --purple-bg:     #f4f0fe;
  --purple-border: #c4b5fd;
  --purple-text:   #5b21b6;

  --red:           #dc2626;
  --red-bg:        #fef2f2;

  /* Shadows */
  --shadow-xs:  0 1px 2px rgba(15,23,41,0.05);
  --shadow-sm:  0 2px 6px rgba(15,23,41,0.06), 0 1px 2px rgba(15,23,41,0.04);
  --shadow-md:  0 4px 16px rgba(15,23,41,0.08), 0 1px 4px rgba(15,23,41,0.04);
  --shadow-lg:  0 8px 32px rgba(15,23,41,0.10), 0 2px 8px rgba(15,23,41,0.06);

  /* Layout */
  --radius-xs:  6px;
  --radius-sm:  10px;
  --radius:     14px;
  --radius-lg:  18px;
  --sidebar-w:  230px;

  /* Fonts */
  --font-head: 'Plus Jakarta Sans', sans-serif;
  --font-body: 'DM Sans', sans-serif;
}

body {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  -webkit-font-smoothing: antialiased;
}

/* ══════════════════════════════════════════════
   SIDEBAR — DO NOT CHANGE
══════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════
   MAIN LAYOUT
══════════════════════════════════════════════ */
.main {
  margin-left: var(--sidebar-w); flex: 1;
  display: flex; flex-direction: column; min-height: 100vh;
}

/* ── TOPBAR ─────────────────────────────────────── */
.topbar {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 22px 0 24px; height: 72px;
  display: flex; align-items: center;
  position: sticky; top: 0; z-index: 50;
  box-shadow: 0 1px 0 var(--border);
}

.topbar-right { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.topbar-profile { position: relative; display: flex; align-items: center; gap: 10px; cursor: pointer; padding-right: 0; }
.topbar-profile-info { text-align: right; margin-right: 0; }
.topbar-profile-name { font-size: 0.88rem; font-weight: 700; color: var(--text); line-height: 1.2; }
.topbar-profile-role { font-size: 0.78rem; color: var(--text-3); }
.topbar-profile img {
  width: 40px; height: 40px; border-radius: 50%; object-fit: cover;
  border: 2px solid var(--border); box-shadow: var(--shadow-xs);
}
.topbar-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid rgba(245,197,24,0.5);
  box-shadow: var(--shadow-xs);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--navy);
  background: var(--yellow);
}

/* Profile dropdown */
.profile-dropdown {
  position: absolute;
  top: 115%;
  right: 0;
  min-width: 230px;
  background: var(--white);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
  padding: 10px 12px 8px;
  display: none;
  z-index: 2000;
}
.profile-dropdown.is-open { display: block; }
.profile-dropdown-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 8px;
}
.profile-dropdown-icon {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  color: var(--text-3);
  font-size: 0.82rem;
  flex-shrink: 0;
}
.profile-dropdown-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.profile-dropdown-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-3);
  font-weight: 700;
  padding: 2px 0 3px;
}
.profile-dropdown-value {
  font-size: 0.82rem;
  color: var(--text-2);
}
.profile-signout-btn {
  width: 100%;
  margin-top: 4px;
  border: none;
  outline: none;
  border-radius: 999px;
  padding: 7px 10px;
  font-size: 0.82rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: var(--red-bg);
  color: var(--red);
  cursor: pointer;
  transition: background 0.16s ease, color 0.16s ease, transform 0.08s ease;
}
.profile-signout-btn i { font-size: 0.86rem; }
.profile-signout-btn:hover {
  background: #fee2e2;
  transform: translateY(-1px);
}

/* ── CONTENT AREA ────────────────────────────────── */
.content {
  padding: 28px 36px 52px;
  display: flex; flex-direction: column; gap: 22px;
}

/* ── WELCOME BANNER ──────────────────────────────── */
.welcome-banner {
  background: linear-gradient(118deg, #0b1640 0%, #112060 48%, #1d4ed8 100%);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(37,99,235,0.35);
  padding: 28px 30px;
  display: flex; flex-direction: column; gap: 24px;
  position: relative; overflow: hidden;
  box-shadow: 0 12px 28px rgba(11,22,64,0.18);
}
.welcome-banner::before {
  content: '';
  position: absolute; top: -40px; right: 120px;
  width: 200px; height: 200px; border-radius: 50%;
  background: rgba(96,165,250,0.16);
  pointer-events: none;
}
.welcome-banner::after {
  content: '';
  position: absolute; bottom: -50px; right: -20px;
  width: 160px; height: 160px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.1);
  pointer-events: none;
}
.welcome-top {
  width: 100%;
  display: flex; align-items: center; justify-content: space-between;
  gap: 20px;
  position: relative; z-index: 1;
}
.welcome-text { position: relative; z-index: 1; }
.welcome-greeting {
  font-family: var(--font-head);
  font-size: 1.45rem; font-weight: 800;
  color: #fff; letter-spacing: -0.025em; line-height: 1.2;
  margin-bottom: 6px;
}
.welcome-greeting em { color: var(--yellow); font-style: normal; }
.welcome-sub {
  font-size: 0.86rem; color: rgba(255,255,255,0.55); font-weight: 400;
}
.welcome-meta {
  display: inline-flex; align-items: center; gap: 7px;
  margin-top: 13px; color: rgba(255,255,255,0.5);
  font-size: 0.74rem; font-weight: 500;
}
.welcome-meta i { color: #93c5fd; }
.welcome-actions { display: flex; gap: 10px; position: relative; z-index: 1; }
.btn-banner {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 24px;
  font-family: var(--font-body); font-size: 0.82rem; font-weight: 600;
  text-decoration: none; cursor: pointer; transition: all 0.18s;
}
.btn-banner-solid {
  background: var(--yellow); color: var(--navy); border: none;
  box-shadow: 0 2px 8px rgba(245,197,24,0.4);
}
.btn-banner-solid:hover { background: #ffd740; transform: translateY(-1px); }
.btn-banner-outline {
  background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.85);
  border: 1.5px solid rgba(255,255,255,0.15);
}
.btn-banner-outline:hover { background: rgba(255,255,255,0.14); }

/* ── STAT CARDS ──────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin: 0;
  width: 100%;
}

.stat-card {
  background: rgba(255,255,255,0.11);
  border-radius: 18px;
  border: 1px solid rgba(255,255,255,0.18);
  padding: 16px 18px 18px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(4,12,45,0.16), inset 0 1px 0 rgba(255,255,255,0.08);
  transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
  cursor: default;
  min-height: 150px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: 100%;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 26px rgba(4,12,45,0.22), inset 0 1px 0 rgba(255,255,255,0.12);
  border-color: rgba(255,255,255,0.32);
}

.stat-card::before {
  display: none;
}

.stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 26px;
  margin-bottom: 12px;
  position: relative;
  z-index: 1;
}
.stat-title {
  color: rgba(255,255,255,0.76);
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1.25;
  letter-spacing: 0;
}

.stat-tag {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 5px 10px;
  border-radius: 999px;
  letter-spacing: 0.01em;
  white-space: nowrap;
  box-shadow: 0 1px 3px rgba(15, 23, 41, 0.06);
}
.tag-green  { background: rgba(19, 180, 95, 0.12); color: #0c8f53; border: 1px solid rgba(19, 180, 95, 0.25); }
.tag-blue   { background: rgba(59, 130, 246, 0.10); color: #2b6fe8; border: 1px solid rgba(59, 130, 246, 0.22); }
.tag-amber  { background: rgba(245, 158, 11, 0.12); color: #b26a00; border: 1px solid rgba(245, 158, 11, 0.22); }
.tag-purple { background: rgba(124, 58, 237, 0.09); color: #5d2ec7; border: 1px solid rgba(124, 58, 237, 0.18); }

.stat-card .tag-green  { background: rgba(74,222,128,0.16); color: #bbf7d0; border-color: rgba(134,239,172,0.34); }
.stat-card .tag-blue   { background: rgba(96,165,250,0.18); color: #dbeafe; border-color: rgba(147,197,253,0.36); }
.stat-card .tag-amber  { background: rgba(251,191,36,0.18); color: #fef3c7; border-color: rgba(253,230,138,0.36); }
.stat-card .tag-purple { background: rgba(167,139,250,0.18); color: #ede9fe; border-color: rgba(196,181,253,0.36); }

.stat-value {
  font-family: var(--font-head);
  font-size: 2.05rem;
  font-weight: 800;
  letter-spacing: -0.06em;
  line-height: 1.05;
  color: #fff;
  margin-bottom: 8px;
}
/* ── BOTTOM GRID ─────────────────────────────────── */
.bottom-grid {
  display: grid;
  grid-template-columns: 1.7fr 1fr;
  gap: 10px;
}

/* ── SHARED PANEL ────────────────────────────────── */
.panel {
  background: rgba(255,255,255,0.97);
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 1px 3px rgba(15,23,41,0.03);
  overflow: hidden;
  display: flex; flex-direction: column;
}
.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  min-height: 52px;
  padding: 14px 16px 10px;
  border-bottom: 1px solid var(--border);
}
.panel-title {
  display: flex; align-items: center; gap: 8px;
  font-family: var(--font-head);
  font-size: 0.88rem; font-weight: 700; color: var(--text);
}
.panel-title-icon {
  width: 26px; height: 26px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center; font-size: 0.72rem;
}
.pti-blue   { background: var(--blue-bg);   color: var(--blue-text); }
.pti-green  { background: var(--green-bg);  color: var(--green-text); }

.link-all {
  font-size: 0.7rem; font-weight: 700; color: var(--blue-text);
  text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 8px; border-radius: 999px;
  border: 1px solid var(--blue-border); background: var(--blue-bg);
  transition: all 0.18s;
}
.link-all:hover { background: var(--blue-text); color: #fff; border-color: var(--blue-text); }

/* ── RESERVATION ROWS ────────────────────────────── */
.res-row {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid var(--border);
  transition: background 0.15s; cursor: pointer;
}
.res-row:last-child { border-bottom: none; }
.res-row:hover { background: #f8fafc; }

.res-date-col {
  flex-shrink: 0; width: 44px; text-align: center;
  background: #f8fafc; border-radius: 9px;
  padding: 7px 4px; border: 1px solid var(--border);
}
.res-date-day { font-size: 1.1rem; font-weight: 800; color: var(--text); line-height: 1; font-family: var(--font-head); }
.res-date-label { font-size: 0.6rem; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
.res-date-col.today { background: var(--navy); border-color: var(--navy); }
.res-date-col.today .res-date-day { color: var(--yellow); }
.res-date-col.today .res-date-label { color: rgba(255,255,255,0.6); }

.res-body { flex: 1; min-width: 0; }
.res-room-name {
  font-family: var(--font-head);
  font-size: 0.85rem; font-weight: 700; color: var(--text);
  margin-bottom: 2px; display: flex; align-items: center; gap: 7px;
}
.res-subject-name { font-size: 0.78rem; color: var(--text-2); font-weight: 500; margin-bottom: 6px; }
.res-chips { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.res-chip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 0.7rem; color: var(--text-3); font-weight: 500;
}
.res-chip i { font-size: 0.62rem; color: var(--text-4); }

.badge {
  font-size: 0.66rem; font-weight: 700; padding: 4px 8px;
  border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;
  white-space: nowrap;
}
.badge-confirmed { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
.badge-pending   { background: var(--amber-bg);  color: var(--amber-text); border: 1px solid var(--amber-border); }
.badge-cancelled { background: var(--red-bg);    color: var(--red);        border: 1px solid #fca5a5; }

.res-caret {
  flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-4); font-size: 0.72rem;
  background: var(--bg); border: 1px solid var(--border);
  transition: all 0.18s;
}
.res-row:hover .res-caret { background: var(--navy); border-color: var(--navy); color: #fff; }

/* ── AVAILABLE ROOMS ─────────────────────────────── */
.room-row {
  padding: 12px 16px; border-bottom: 1px solid var(--border);
  transition: background 0.15s; cursor: pointer;
}
.room-row:last-child { border-bottom: none; }
.room-row:hover { background: #f8fafc; }

.room-header-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.room-id-text {
  font-family: var(--font-head);
  font-size: 0.88rem; font-weight: 800; color: var(--text); letter-spacing: -0.01em;
}
.room-cap-badge {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 0.68rem; font-weight: 700;
  background: var(--green-bg); color: var(--green-text);
  padding: 4px 8px; border-radius: 999px;
  border: 1px solid var(--green-border);
}
.room-location-text {
  font-size: 0.74rem; color: var(--text-3); margin-bottom: 8px;
  display: flex; align-items: center; gap: 4px;
}
.room-location-text i { font-size: 0.65rem; color: var(--text-4); }
.room-tags { display: flex; gap: 5px; flex-wrap: wrap; }
.rtag {
  font-size: 0.67rem; font-weight: 600; padding: 3px 7px;
  border-radius: 999px;
  background: #f8fafc; color: var(--text-3); border: 1px solid var(--border);
  transition: all 0.15s;
}
.room-row:hover .rtag { background: var(--navy-light); color: var(--navy-mid); border-color: #c7d0f0; }

/* ── EMPTY STATE ─────────────────────────────────── */
.empty-state {
  padding: 36px 22px; text-align: center;
  color: var(--text-3); font-size: 0.84rem;
}
.empty-state i { font-size: 1.8rem; color: var(--text-4); margin-bottom: 10px; display: block; }

/* ── ANIMATIONS ──────────────────────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.welcome-banner { animation: fadeUp 0.4s both 0.04s; }
.stats-grid     { animation: fadeUp 0.4s both 0.12s; }
.bottom-grid    { animation: fadeUp 0.4s both 0.2s; }

/* ── RESPONSIVE ──────────────────────────────────── */
@media (max-width: 1280px) {
  .stats-grid  { grid-template-columns: repeat(2,1fr); }
  .bottom-grid { grid-template-columns: 1fr; }
  .content     { padding: 22px 20px 40px; }
  .topbar      { padding: 0 20px; }
}
@media (max-width: 768px) {
  :root { --sidebar-w: 0px; }
  .sidebar { display: none; }
  .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
  .welcome-banner { gap: 18px; }
  .welcome-top { flex-direction: column; align-items: flex-start; gap: 16px; }
  .welcome-actions { width: 100%; flex-wrap: wrap; }
  .btn-banner { flex: 1 1 150px; justify-content: center; }
  .topbar { padding: 0 14px; gap: 8px; }
}

/* ── AI Recommendations (local page styles) ───────────────────── */
.rec-list { display:flex;flex-direction:column;gap:10px;padding:12px 16px; }
.rec-card { display:flex;align-items:center;gap:16px;padding:14px;border-radius:12px;border:1px solid var(--border);background:var(--white);text-decoration:none;color:inherit;transition:transform .12s,box-shadow .12s; }
.rec-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md)}
.rec-card.top-pick{background:linear-gradient(90deg,rgba(29,78,216,0.04),transparent);border-color:rgba(37,99,235,0.08)}
.rec-rank{width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;flex-shrink:0;background:linear-gradient(135deg,#1d4ed8,#3b82f6)}
.rec-info{flex:1;min-width:0}
.rec-name{font-size:0.98rem;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px}
.rec-best-tag{font-size:0.64rem;font-weight:800;padding:4px 8px;border-radius:8px;background:var(--yellow-light);color:#b45309;border:1px solid rgba(245,197,24,0.25)}
.rec-meta{display:flex;gap:10px;color:var(--text-3);font-size:0.85rem;margin:6px 0}
.rec-reason{font-size:0.85rem;color:var(--blue-text);font-weight:600;margin-top:4px;display:flex;align-items:center;gap:8px}
.rec-features{display:flex;gap:6px;margin-top:6px}
.rec-feat-tag{font-size:0.72rem;padding:4px 8px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-2)}
.rec-score{display:flex;flex-direction:column;align-items:center;gap:6px;margin-left:8px}
.rec-score-ring{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid var(--border);}
.rec-score-ring.high{border-color:#60a5fa;background:var(--blue-bg)}
.rec-score-ring.med{border-color:#a78bfa;background:var(--purple-bg)}
.rec-score-val{font-size:1rem;font-weight:800;color:var(--blue-text)}
.rec-score-label{font-size:0.64rem;color:var(--text-3);font-weight:800}
.rec-arrow{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:var(--bg);color:var(--text-3)}

/* ── Schedule Assistant Chat ─────────────────────────────────── */
.assistant-panel {
  background: var(--white);
  border-radius: var(--radius);
  border: 1.5px solid var(--border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.assistant-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.assistant-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-head);
  font-size: 0.95rem;
  font-weight: 700;
}
.assistant-title-badge {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: var(--blue-bg);
  color: var(--blue-text);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.assistant-sub {
  font-size: 0.78rem;
  color: var(--text-3);
}
.assistant-chat {
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 360px;
  overflow: auto;
  background: linear-gradient(180deg, #fbfcff 0%, #f8faff 100%);
}
.assistant-msg {
  max-width: 78%;
  border-radius: 12px;
  padding: 10px 12px;
  font-size: 0.84rem;
  line-height: 1.45;
  box-shadow: var(--shadow-xs);
}
.assistant-msg.user {
  margin-left: auto;
  background: var(--navy);
  color: #fff;
  border-bottom-right-radius: 4px;
}
.assistant-msg.bot {
  margin-right: auto;
  background: #fff;
  border: 1px solid var(--border);
  color: var(--text);
  border-bottom-left-radius: 4px;
}
.assistant-items {
  margin-top: 8px;
  display: grid;
  gap: 7px;
}
.assistant-item {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 8px 9px;
  background: var(--bg);
}
.assistant-item-top {
  font-size: 0.78rem;
  color: var(--text);
  font-weight: 700;
}
.assistant-item-meta {
  font-size: 0.73rem;
  color: var(--text-2);
  margin-top: 3px;
}
.assistant-quick {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 10px 16px;
  border-top: 1px solid var(--border);
  background: #fff;
}
.assistant-chip {
  border: 1px solid var(--blue-border);
  background: var(--blue-bg);
  color: var(--blue-text);
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
}
.assistant-form {
  display: flex;
  gap: 8px;
  padding: 12px 16px 16px;
  border-top: 1px solid var(--border);
  background: #fff;
}
.assistant-input {
  flex: 1;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 0.84rem;
  outline: none;
}
.assistant-input:focus {
  border-color: #93c5fd;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.assistant-send {
  border: 0;
  border-radius: 10px;
  padding: 0 14px;
  font-size: 0.82rem;
  font-weight: 700;
  background: var(--navy);
  color: #fff;
  cursor: pointer;
}
.assistant-send[disabled] {
  opacity: 0.65;
  cursor: not-allowed;
}
.assistant-loading {
  display: inline-flex;
  gap: 4px;
  align-items: center;
}
.assistant-loading span {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: var(--text-4);
  animation: dotPulse 1s infinite ease-in-out;
}
.assistant-loading span:nth-child(2) { animation-delay: 0.15s; }
.assistant-loading span:nth-child(3) { animation-delay: 0.3s; }

@keyframes dotPulse {
  0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
  40% { transform: translateY(-2px); opacity: 1; }
}

</style>
@include('partials.pro-motion')
</head>
<body>

<!-- ═══════════════════════════════════════════
     SIDEBAR — DO NOT CHANGE
═══════════════════════════════════════════ -->
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
    <li>
      <a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-id-card"></i></span>
        RFID Verification
      </a>
    </li>
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

<!-- ═══════════════════════════════════════════
     MAIN
═══════════════════════════════════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-right">
      <div class="topbar-profile">
        <div class="topbar-profile-info">
          <div class="topbar-profile-name"><?= htmlspecialchars($facultyName) ?></div>
          <div class="topbar-profile-role"><?= htmlspecialchars($facultyDept) ?></div>
        </div>
        <div class="topbar-avatar"><?= htmlspecialchars($facultyInitials) ?></div>
        <div class="profile-dropdown">
          <div class="profile-dropdown-item">
            <span class="profile-dropdown-icon"><i class="fas fa-envelope"></i></span>
            <div class="profile-dropdown-text">
              <span class="profile-dropdown-label">Email</span>
              <span class="profile-dropdown-value"><?= htmlspecialchars($facultyEmail) ?></span>
            </div>
          </div>
          <div class="profile-dropdown-item">
            <span class="profile-dropdown-icon"><i class="fas fa-briefcase"></i></span>
            <div class="profile-dropdown-text">
              <span class="profile-dropdown-label">University Position</span>
              <span class="profile-dropdown-value"><?= htmlspecialchars($facultyDept) ?></span>
            </div>
          </div>
          <form method="POST" action="<?= htmlspecialchars(url('/logout')) ?>">
            <?= csrf_field(); ?>
            <button type="submit" class="profile-signout-btn">
              <i class="fas fa-arrow-right-from-bracket"></i>
              Sign Out
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- ── Welcome Header ── -->
    <section class="welcome-banner">
      <div class="welcome-top">
        <div class="welcome-text">
          <h1 class="welcome-greeting">Good morning, <em><?= htmlspecialchars($facultyName) ?></em></h1>
          <p class="welcome-sub">Here’s what’s happening with your faculty workspace today.</p>
          <div class="welcome-meta">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <span><?= htmlspecialchars($dateFormatted) ?></span>
          </div>
        </div>
        <div class="welcome-actions">
          <a href="<?= htmlspecialchars(url('/faculty-schedule')) ?>" class="btn-banner btn-banner-solid">
            <i class="fas fa-calendar-days" aria-hidden="true"></i>
            View Schedule
          </a>
          <a href="<?= htmlspecialchars(url('/rooms')) ?>" class="btn-banner btn-banner-outline">
            <i class="fas fa-door-open" aria-hidden="true"></i>
            Find a Room
          </a>
        </div>
      </div>

      <!-- ── Stat Cards ── -->
      <div class="stats-grid">

        <div class="stat-card green">
          <div class="stat-top">
            <span class="stat-title">Available Rooms</span>
            <span class="stat-tag tag-green">+3 new</span>
          </div>
          <div class="stat-value"><?= (int) ($stats['available_rooms'] ?? 0) ?></div>
        </div>

        <div class="stat-card blue">
          <div class="stat-top">
            <span class="stat-title">My Reservations</span>
            <span class="stat-tag tag-blue">This Week</span>
          </div>
          <div class="stat-value"><?= (int) ($stats['my_reservations'] ?? 0) ?></div>
        </div>

        <div class="stat-card amber">
          <div class="stat-top">
            <span class="stat-title">Active Classes</span>
            <span class="stat-tag tag-amber">This Semester</span>
          </div>
          <div class="stat-value"><?= (int) ($stats['active_classes'] ?? 0) ?></div>
        </div>

      </div>
    </section>

    <!-- ── Bottom Grid ── -->
    <div class="bottom-grid">

      <!-- AI Recommendations (replaces Upcoming Schedules) -->
      <div class="panel" id="ai-recommendations-panel">
        <div class="panel-header">
          <div class="panel-title">
            <span class="panel-title-icon pti-blue"><i class="fas fa-robot"></i></span>
            AI Recommendations
          </div>
          <a href="<?= htmlspecialchars(url('/ai-recommendations')) ?>" class="link-all">
            View all <i class="fas fa-arrow-right" style="font-size:0.65rem;"></i>
          </a>
        </div>

        <div id="ai-recommendations-content">
          <div class="empty-state">
            <i class="fas fa-robot"></i>
            Loading recommendations...
          </div>
        </div>
      </div>

      <!-- Available Now -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">
            <span class="panel-title-icon pti-green"><i class="fas fa-door-open"></i></span>
            Available Now
          </div>
        </div>

        <?php if (empty($availableNowRooms)): ?>
          <div class="empty-state">
            <i class="fas fa-door-closed"></i>
            No rooms available right now.
          </div>
        <?php else:
          foreach ($availableNowRooms as $room): ?>
        <div class="room-row">
          <div class="room-header-row">
            <div class="room-id-text"><?= htmlspecialchars($room['id']) ?></div>
            <div class="room-cap-badge">
              <i class="fas fa-users"></i> <?= htmlspecialchars($room['capacity']) ?>
            </div>
          </div>
          <div class="room-location-text">
            <i class="fas fa-location-dot"></i>
            <?= htmlspecialchars($room['location']) ?>
          </div>
          <div class="room-tags">
            <?php foreach ($room['tags'] as $tag): ?>
            <span class="rtag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

    </div><!-- /bottom-grid -->

    <div class="assistant-panel">
      <div class="assistant-head">
        <div>
          <div class="assistant-title">
            <span class="assistant-title-badge"><i class="fas fa-comment-dots"></i></span>
            Smart Schedule Assistant
          </div>
          <div class="assistant-sub">Ask in plain English: today, tomorrow, next class, weekday schedule, first class.</div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <label style="font-size:.82rem;color:var(--text-3);display:flex;align-items:center;gap:8px;">
            <input type="checkbox" id="assistantUseLlm" style="width:16px;height:16px;" />
            <span style="font-weight:600">Use Groq AI</span>
          </label>
        </div>
      </div>
      <div class="assistant-chat" id="scheduleAssistantChat">
        <div class="assistant-msg bot">Hi <?= htmlspecialchars($facultyInitials) ?>, I can answer schedule questions instantly.</div>
      </div>
      <div class="assistant-quick" id="scheduleAssistantQuick">
        <button class="assistant-chip" type="button">What is my schedule today?</button>
        <button class="assistant-chip" type="button">Where is my next class?</button>
        <button class="assistant-chip" type="button">Do I have classes tomorrow?</button>
        <button class="assistant-chip" type="button">Show my Wednesday schedule.</button>
        <button class="assistant-chip" type="button">When is my first class?</button>
      </div>
      <form class="assistant-form" id="scheduleAssistantForm">
        <input
          id="scheduleAssistantInput"
          class="assistant-input"
          type="text"
          maxlength="500"
          placeholder="Type your question about schedule..."
          autocomplete="off"
        >
        <button class="assistant-send" id="scheduleAssistantSend" type="submit">Ask</button>
      </form>
    </div>
  </div><!-- /content -->
</div><!-- /main -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  function closeAllProfileDropdowns() {
    document.querySelectorAll('.profile-dropdown').forEach(function (el) {
      el.classList.remove('is-open');
    });
  }

  document.querySelectorAll('.topbar-profile').forEach(function (profile) {
    var dropdown = profile.querySelector('.profile-dropdown');
    if (!dropdown) return;

    profile.addEventListener('click', function (event) {
      event.stopPropagation();
      var isOpen = dropdown.classList.contains('is-open');
      closeAllProfileDropdowns();
      if (!isOpen) {
        dropdown.classList.add('is-open');
      }
    });
  });

  document.addEventListener('click', closeAllProfileDropdowns);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const chat = document.getElementById('scheduleAssistantChat');
  const form = document.getElementById('scheduleAssistantForm');
  const input = document.getElementById('scheduleAssistantInput');
  const sendBtn = document.getElementById('scheduleAssistantSend');
  const quick = document.getElementById('scheduleAssistantQuick');
  const endpoint = "{{ route('faculty.schedule.assistant.ask') }}";
  const csrf = "{{ csrf_token() }}";
  const llmAvailable = @json((bool) env('GROQ_API_KEY'));
  const llmCheckbox = document.getElementById('assistantUseLlm');

  if (llmCheckbox) {
    llmCheckbox.checked = false;
    if (!llmAvailable) {
      llmCheckbox.disabled = true;
      llmCheckbox.parentElement.insertAdjacentHTML('beforeend', '<span style="font-size:.72rem;color:var(--text-4);margin-left:6px">Groq not configured</span>');
    }
  }

  if (!chat || !form || !input || !sendBtn || !quick) return;

  function appendUser(text) {
    const msg = document.createElement('div');
    msg.className = 'assistant-msg user';
    msg.textContent = text;
    chat.appendChild(msg);
    chat.scrollTop = chat.scrollHeight;
  }

  function appendBot(text, items) {
    const msg = document.createElement('div');
    msg.className = 'assistant-msg bot';

    const textNode = document.createElement('div');
    textNode.textContent = text;
    msg.appendChild(textNode);

    if (Array.isArray(items) && items.length) {
      const list = document.createElement('div');
      list.className = 'assistant-items';
      items.forEach(function (item) {
        const row = document.createElement('div');
        row.className = 'assistant-item';

        const top = document.createElement('div');
        top.className = 'assistant-item-top';
        top.textContent = `${item.subject || 'Class'} (${item.code || 'N/A'})`;

        const meta = document.createElement('div');
        meta.className = 'assistant-item-meta';
        meta.textContent = `${item.date || '-'} · ${item.time || '-'} · ${item.location || 'Room N/A'}`;

        row.appendChild(top);
        row.appendChild(meta);
        list.appendChild(row);
      });
      msg.appendChild(list);
    }

    chat.appendChild(msg);
    chat.scrollTop = chat.scrollHeight;
  }

  function appendError() {
    appendBot('I could not process that right now. Please try again in a moment.', []);
  }

  function appendLoading() {
    const msg = document.createElement('div');
    msg.className = 'assistant-msg bot';
    msg.id = 'assistantLoading';
    msg.innerHTML = '<span class="assistant-loading"><span></span><span></span><span></span></span>';
    chat.appendChild(msg);
    chat.scrollTop = chat.scrollHeight;
  }

  function removeLoading() {
    const loading = document.getElementById('assistantLoading');
    if (loading) loading.remove();
  }

  async function askAssistant(message) {
    appendUser(message);
    appendLoading();
    sendBtn.disabled = true;

    const useLlm = llmCheckbox && llmCheckbox.checked;

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ message: message, use_llm: useLlm }),
      });

      const payload = await response.json();
      removeLoading();

      if (!response.ok || !payload || payload.success !== true) {
        appendError();
        return;
      }

      appendBot(payload.answer || 'No response available.', payload.items || []);
    } catch (e) {
      removeLoading();
      appendError();
    } finally {
      sendBtn.disabled = false;
      input.focus();
    }
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    const message = (input.value || '').trim();
    if (!message) return;
    input.value = '';
    askAssistant(message);
  });

  quick.addEventListener('click', function (event) {
    const btn = event.target.closest('.assistant-chip');
    if (!btn) return;
    const question = (btn.textContent || '').trim();
    if (!question) return;
    askAssistant(question);
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('ai-recommendations-content');
  if (!root) return;
  fetch('/api/ai/recommendations')
    .then(r => r.json())
    .then(j => {
      if (!j.success || !Array.isArray(j.recommendations) || j.recommendations.length === 0) {
        root.innerHTML = '<div class="empty-state"><i class="fas fa-robot"></i>No recommendations available.</div>';
        return;
      }

      const list = document.createElement('div');
      list.className = 'rec-list';

      j.recommendations.forEach((r, idx) => {
        const card = document.createElement('a');
        card.className = 'rec-card' + (idx === 0 ? ' top-pick' : '');
        card.href = '#';
        const location = [r.building, r.floor].filter(Boolean).join(' · ') || 'Location unavailable';
        const freeHours = Number(r.free_for || 0);
        const freeLabel = freeHours === 1 ? '1 hour' : `${freeHours} hours`;
        const freeUntil = r.free_until ? new Date(r.free_until).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : 'closing';
        card.innerHTML = `
          <div class="rec-rank rank-${idx+1}">${idx + 1}</div>
          <div class="rec-info">
            <div class="rec-name">${r.name} ${idx===0? '<span class="rec-best-tag">BEST MATCH</span>':''}</div>
            <div class="rec-meta">
              <span class="rec-meta-item"><i class="fas fa-location-dot"></i> ${location}</span>
              <span class="rec-meta-item"><i class="fas fa-users"></i> ${r.capacity} seats</span>
              <span class="rec-meta-item"><i class="fas fa-clock"></i> Free ${freeLabel}</span>
            </div>
            <div class="rec-reason"><i class="fas fa-lightbulb"></i> Available until ${freeUntil}</div>
          </div>
          <div class="rec-arrow"><i class="fas fa-arrow-right"></i></div>
        `;
        list.appendChild(card);
      });

      root.innerHTML = '';
      root.appendChild(list);
    })
    .catch(() => {
      root.innerHTML = '<div class="empty-state"><i class="fas fa-robot"></i>Failed loading recommendations.</div>';
    });
});
</script>

@include('frontend.faculty.partials.notifications-widget')
</body>
</html>