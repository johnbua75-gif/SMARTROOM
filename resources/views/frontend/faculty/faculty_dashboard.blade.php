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
/* ── RESET ─────────────────────────────────────────── */
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
  --shadow-xl:  0 20px 50px rgba(11,22,64,0.12), 0 4px 12px rgba(11,22,64,0.05);

  /* Layout */
  --radius-xs:  6px;
  --radius-sm:  10px;
  --radius:     14px;
  --radius-lg:  18px;
  --radius-xl:  22px;
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

/* ═══════════════════════════════════════════════════
   SIDEBAR — DO NOT CHANGE
═══════════════════════════════════════════════════ */
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

/* ═══════════════════════════════════════════════════
   MAIN LAYOUT
═══════════════════════════════════════════════════ */
.main {
  margin-left: var(--sidebar-w); flex: 1;
  display: flex; flex-direction: column; min-height: 100vh;
}

/* ── TOPBAR ────────────────────────────────────────── */
.topbar {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 28px;
  height: 64px;
  display: flex; align-items: center;
  position: sticky; top: 0; z-index: 50;
  backdrop-filter: blur(12px);
  background: rgba(255,255,255,0.88);
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
  width: 38px; height: 38px; border-radius: 50%;
  border: 2px solid rgba(245,197,24,0.5);
  box-shadow: var(--shadow-xs);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem; font-weight: 700;
  color: var(--navy); background: var(--yellow);
  transition: box-shadow 0.2s;
}
.topbar-profile:hover .topbar-avatar {
  box-shadow: 0 0 0 3px rgba(245,197,24,0.2);
}

/* Profile dropdown */
.profile-dropdown {
  position: absolute; top: 115%; right: 0;
  min-width: 240px; background: var(--white);
  border-radius: var(--radius); border: 1px solid var(--border);
  box-shadow: var(--shadow-lg); padding: 14px 16px 12px;
  display: none; z-index: 2000;
}
.profile-dropdown.is-open { display: block; animation: dropIn 0.18s ease-out; }
.profile-dropdown-item { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.profile-dropdown-icon {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--bg); color: var(--text-3); font-size: 0.78rem; flex-shrink: 0;
}
.profile-dropdown-text { display: flex; flex-direction: column; gap: 1px; }
.profile-dropdown-label {
  font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.1em;
  color: var(--text-4); font-weight: 700;
}
.profile-dropdown-value { font-size: 0.82rem; color: var(--text-2); font-weight: 500; }
.profile-signout-btn {
  width: 100%; margin-top: 6px; border: none; outline: none;
  border-radius: var(--radius-sm); padding: 9px 10px;
  font-size: 0.82rem; font-weight: 600;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  background: var(--red-bg); color: var(--red); cursor: pointer;
  transition: all 0.18s;
}
.profile-signout-btn i { font-size: 0.82rem; }
.profile-signout-btn:hover { background: #fecaca; transform: translateY(-1px); }

@keyframes dropIn {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── CONTENT AREA ──────────────────────────────────── */
.content {
  padding: 28px 32px 56px;
  display: flex; flex-direction: column; gap: 24px;
  max-width: 1400px;
}

/* ═══════════════════════════════════════════════════
   CAMPUS HERO — new decorative header (image + gradient)
═══════════════════════════════════════════════════ */
.campus-hero {
  display:none;
}
.campus-hero-hidden {
  position: relative;
  border-radius: 0;
  overflow: hidden;
  min-height: 250px;
  display: flex;
  align-items: flex-end;
  margin: -28px -32px 0;
  background: linear-gradient(135deg, #0b1640 0%, rgba(26,47,128,.78) 40%, rgba(11,22,64,.12) 100%);
}
.campus-hero-bg {
  position: absolute; inset: 0;
  background-image: url('/images/map.png');
  background-size: cover;
  background-position: center;
  opacity: .72;
}
.campus-hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(90deg, rgba(11,22,64,.92) 0%, rgba(11,22,64,.7) 45%, rgba(11,22,64,.2) 75%, transparent 100%);
}
.campus-hero::before {
  content: '';
  position: absolute; top: -60px; right: 60px;
  width: 220px; height: 220px; border-radius: 50%;
  background: radial-gradient(circle, rgba(96,165,250,0.16) 0%, transparent 70%);
  pointer-events: none;
}
.campus-hero-content { position:relative; z-index:1; padding:28px 32px 24px; }
.campus-hero-brand { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
.campus-hero-logo { width:48px; height:48px; display:flex; align-items:center; justify-content:center; border:2px solid rgba(255,255,255,.2); border-radius:50%; color:var(--yellow); background:rgba(255,255,255,.15); }
.campus-hero-brand-text { display:flex; flex-direction:column; }.campus-hero-brand-name { font-family:var(--font-head); color:#fff; font-size:.88rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }.campus-hero-brand-campus { margin-top:1px; color:rgba(255,255,255,.5); font-size:.68rem; letter-spacing:.04em; }
.campus-hero-label { margin-bottom:6px; color:var(--yellow); font-size:.62rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }.campus-hero-title { margin:0 0 4px; color:#fff; font-family:var(--font-head); font-size:1.5rem; font-weight:800; letter-spacing:-.02em; }.campus-hero-sub { max-width:440px; color:rgba(255,255,255,.6); font-size:.82rem; }
.campus-feature { display: flex; align-items: center; gap: 12px; }
.campus-feature-icon {
  width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
}
.campus-feature-icon.blue   { background: rgba(96,165,250,0.15); color: #93c5fd; border: 1px solid rgba(147,197,253,0.3); }
.campus-feature-icon.green  { background: rgba(74,222,128,0.15); color: #86efac; border: 1px solid rgba(134,239,172,0.3); }
.campus-feature-icon.purple { background: rgba(167,139,250,0.15); color: #c4b5fd; border: 1px solid rgba(196,181,253,0.3); }
.campus-feature-text strong {
  display: block; font-family: var(--font-head);
  font-size: 0.86rem; font-weight: 700; color: #fff;
}
.campus-feature-text span { font-size: 0.76rem; color: rgba(255,255,255,0.55); }

@media (max-width: 768px) {
  .campus-hero { min-height: 220px; margin: -16px -14px 0; }
  .campus-hero-content { padding:24px 20px; }
}

/* ═══════════════════════════════════════════════════
   WELCOME BANNER — REDESIGNED
═══════════════════════════════════════════════════ */
.welcome-banner {
  background: linear-gradient(90deg, rgba(7,14,43,.94) 0%, rgba(11,22,64,.78) 46%, rgba(29,78,216,.3) 100%), url('/images/map.png') center/cover no-repeat;
  border: 0;
  border-radius: 0;
  margin: -28px -32px 0;
  padding: 34px 32px 30px;
  display: flex; flex-direction: column; gap: 28px;
  position: relative; overflow: hidden;
  box-shadow: none;
}
/* Decorative mesh orbs */
.welcome-banner::before {
  content: '';
  position: absolute; top: -70px; right: 80px;
  width: 280px; height: 280px; border-radius: 50%;
  background: radial-gradient(circle, rgba(37,99,235,.08) 0%, transparent 70%);
  pointer-events: none;
}
.welcome-banner::after {
  content: '';
  position: absolute; bottom: -80px; right: -40px;
  width: 220px; height: 220px; border-radius: 50%;
  background: radial-gradient(circle, rgba(245,197,24,.1) 0%, transparent 70%);
  pointer-events: none;
}
.welcome-banner .orb-accent {
  position: absolute; top: 50%; left: -60px;
  width: 180px; height: 180px; border-radius: 50%;
  background: radial-gradient(circle, rgba(37,99,235,.06) 0%, transparent 70%);
  pointer-events: none;
}

.welcome-top {
  width: 100%;
  display: flex; align-items: center; justify-content: space-between;
  gap: 24px; position: relative; z-index: 1;
}
.welcome-text { position: relative; z-index: 1; }
.welcome-greeting {
  font-family: var(--font-head);
  font-size: 1.55rem; font-weight: 800;
  color: #fff; letter-spacing: -0.03em; line-height: 1.2;
  margin-bottom: 8px;
}
.welcome-greeting em { color: var(--yellow); font-style: normal; }
.welcome-sub {
  font-size: 0.88rem; color: rgba(255,255,255,.64); font-weight: 400;
  line-height: 1.5;
}
.welcome-meta {
  display: inline-flex; align-items: center; gap: 8px;
  margin-top: 14px; color: rgba(255,255,255,.72);
  font-size: 0.76rem; font-weight: 500;
  background: rgba(255,255,255,.1);
  padding: 6px 14px; border-radius: 999px;
  border: 1px solid rgba(255,255,255,.14);
}
.welcome-meta i { color: #93c5fd; font-size: 0.72rem; }
.welcome-actions { display: flex; gap: 10px; position: relative; z-index: 1; }
.btn-banner {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 22px; border-radius: 12px;
  font-family: var(--font-body); font-size: 0.84rem; font-weight: 600;
  text-decoration: none; cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
}
.btn-banner-solid {
  background: var(--yellow); color: var(--navy); border: none;
  box-shadow: 0 4px 14px rgba(245,197,24,0.35);
}
.btn-banner-solid:hover { background: #ffd740; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245,197,24,.35); }
.btn-banner-outline {
  background: rgba(255,255,255,.1); color: rgba(255,255,255,.9);
  border: 1.5px solid rgba(255,255,255,.2);
}
.btn-banner-outline:hover { background: rgba(255,255,255,.16); border-color: rgba(255,255,255,.35); }

/* ── STAT CARDS — GLASSMORPHISM ────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  width: 100%;
  padding: 16px;
  border: 1px solid #dce6f5;
  border-radius: var(--radius-xl);
  background: rgba(255,255,255,.96);
  box-shadow: 0 10px 28px rgba(7,22,64,.14);
  position: relative;
  z-index: 1;
}

.stat-card {
  background: #f8faff;
  border-radius: 16px;
  border: 1px solid #dce6f5;
  padding: 18px 20px 20px;
  position: relative; overflow: hidden;
  transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
  cursor: default;
  display: flex; flex-direction: column;
  gap: 14px;
}
.stat-card:hover {
  background: #fff;
  border-color: #b9cdef;
  transform: translateY(-3px);
  box-shadow: 0 12px 30px rgba(4,12,45,0.18);
}
.stat-card::before { display: none; }

/* Subtle glow per card */
.stat-card.green::after  { content:''; position:absolute; top:-30px; right:-30px; width:80px; height:80px; border-radius:50%; background: rgba(74,222,128,0.12); pointer-events:none; }
.stat-card.blue::after   { content:''; position:absolute; top:-30px; right:-30px; width:80px; height:80px; border-radius:50%; background: rgba(96,165,250,0.12); pointer-events:none; }
.stat-card.amber::after  { content:''; position:absolute; top:-30px; right:-30px; width:80px; height:80px; border-radius:50%; background: rgba(251,191,36,0.12); pointer-events:none; }

.stat-top {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px;
}
.stat-label-group {
  display: flex; align-items: center; gap: 10px;
}
.stat-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.82rem;
  flex-shrink: 0;
}
.stat-card.green .stat-icon  { background: #e8f8ef; color: #15803d; }
.stat-card.blue .stat-icon   { background: #eaf2ff; color: #2563eb; }
.stat-card.amber .stat-icon  { background: #fff7df; color: #b45309; }

.stat-title {
  color: var(--text-2);
  font-size: 0.78rem; font-weight: 600;
  letter-spacing: 0.02em;
  line-height: 1.3;
}

.stat-tag {
  font-size: 0.62rem; font-weight: 700;
  padding: 4px 9px; border-radius: 999px;
  letter-spacing: 0.02em; white-space: nowrap;
}
.stat-card .tag-green  { background: #e8f8ef; color: #15803d; border: 1px solid #b7e5c8; }
.stat-card .tag-blue   { background: #eaf2ff; color: #1d4ed8; border: 1px solid #bfd1fd; }
.stat-card .tag-amber  { background: #fff7df; color: #b45309; border: 1px solid #f5d98b; }
.stat-card .tag-purple { background: #f3efff; color: #6d28d9; border: 1px solid #d8cafa; }

.stat-bottom {
  display: flex; align-items: baseline; gap: 6px;
}
.stat-value {
  font-family: var(--font-head);
  font-size: 2rem; font-weight: 800;
  letter-spacing: -0.06em; line-height: 1;
  color: var(--navy);
}
.stat-unit {
  font-size: 0.76rem; color: var(--text-3);
  font-weight: 500;
}

/* ═══════════════════════════════════════════════════
   BOTTOM GRID
═══════════════════════════════════════════════════ */
.bottom-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 22px;
  align-items: start;
}

/* ── SHARED PANEL ──────────────────────────────────── */
.panel {
  background: var(--white);
  border-radius: var(--radius-xl);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  display: flex; flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
}
.panel:hover {
  box-shadow: var(--shadow-lg);
  border-color: rgba(200,210,230,0.6);
}
.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--border);
}
.panel-title {
  display: flex; align-items: center; gap: 12px;
  font-family: var(--font-head);
  font-size: 0.95rem; font-weight: 700; color: var(--text);
}
.panel-title-icon {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; font-size: 0.82rem;
}
.pti-blue {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  color: var(--blue-text);
  box-shadow: 0 2px 6px rgba(59,130,246,0.12);
}
.pti-green {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
  color: var(--green-text);
  box-shadow: 0 2px 6px rgba(16,185,129,0.12);
}

.link-all {
  font-size: 0.74rem; font-weight: 600; color: var(--blue-text);
  text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 16px; border-radius: 10px;
  background: var(--blue-bg); border: 1px solid var(--blue-border);
  transition: all 0.2s;
}
.link-all:hover { background: var(--blue-text); color: #fff; border-color: var(--blue-text); transform: translateY(-1px); box-shadow: 0 3px 10px rgba(29,78,216,0.2); }

/* ── RESERVATION ROWS ──────────────────────────────── */
.res-row {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 22px; border-bottom: 1px solid rgba(228,232,240,0.6);
  transition: all 0.18s; cursor: pointer;
}
.res-row:last-child { border-bottom: none; }
.res-row:hover { background: linear-gradient(90deg, #f8fafc, transparent); }

.res-date-col {
  flex-shrink: 0; width: 46px; text-align: center;
  background: var(--bg); border-radius: 10px;
  padding: 8px 4px; border: 1px solid var(--border);
  transition: all 0.18s;
}
.res-date-day { font-size: 1.1rem; font-weight: 800; color: var(--text); line-height: 1; font-family: var(--font-head); }
.res-date-label { font-size: 0.58rem; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
.res-date-col.today { background: var(--navy); border-color: var(--navy); }
.res-date-col.today .res-date-day { color: var(--yellow); }
.res-date-col.today .res-date-label { color: rgba(255,255,255,0.6); }

.res-body { flex: 1; min-width: 0; }
.res-room-name {
  font-family: var(--font-head);
  font-size: 0.86rem; font-weight: 700; color: var(--text);
  margin-bottom: 3px; display: flex; align-items: center; gap: 8px;
}
.res-subject-name { font-size: 0.78rem; color: var(--text-2); font-weight: 500; margin-bottom: 6px; }
.res-chips { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.res-chip {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 0.72rem; color: var(--text-3); font-weight: 500;
}
.res-chip i { font-size: 0.62rem; color: var(--text-4); }

.badge {
  font-size: 0.65rem; font-weight: 700; padding: 4px 10px;
  border-radius: 8px; display: inline-flex; align-items: center; gap: 4px;
  white-space: nowrap;
}
.badge-confirmed { background: var(--green-bg); color: var(--green-text); border: 1px solid var(--green-border); }
.badge-pending   { background: var(--amber-bg);  color: var(--amber-text); border: 1px solid var(--amber-border); }
.badge-cancelled { background: var(--red-bg);    color: var(--red);        border: 1px solid #fca5a5; }

.res-caret {
  flex-shrink: 0; width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-4); font-size: 0.72rem;
  background: var(--bg); border: 1px solid var(--border);
  transition: all 0.2s;
}
.res-row:hover .res-caret { background: var(--navy); border-color: var(--navy); color: #fff; }

/* ── AVAILABLE ROOMS — CARD STYLE ──────────────────── */
.room-list { display: flex; flex-direction: column; gap: 10px; padding: 16px 20px; }
.room-card {
  display: flex; align-items: center; gap: 14px;
  padding: 16px 18px; border-radius: 14px;
  border: 1px solid var(--border); background: var(--white);
  transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
  cursor: pointer; position: relative; overflow: hidden;
}
.room-card::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
  background: linear-gradient(180deg, var(--green), #34d399);
  border-radius: 4px 0 0 4px; opacity: 0; transition: opacity 0.2s;
}
.room-card:hover {
  border-color: var(--green-border);
  background: linear-gradient(135deg, rgba(16,185,129,0.02), transparent);
  transform: translateY(-2px); box-shadow: var(--shadow-md);
}
.room-card:hover::before { opacity: 1; }

.room-status-dot {
  width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
  background: var(--green-bg);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; color: var(--green-text); position: relative;
}
.room-status-dot::after {
  content: ''; position: absolute; top: 4px; right: 4px;
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--green); border: 2px solid var(--white);
  box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
}

.room-info { flex: 1; min-width: 0; }
.room-id-text {
  font-family: var(--font-head);
  font-size: 0.92rem; font-weight: 800; color: var(--text); margin-bottom: 3px;
}
.room-location-text {
  font-size: 0.76rem; color: var(--text-3); margin-bottom: 8px;
  display: flex; align-items: center; gap: 5px;
}
.room-location-text i { font-size: 0.62rem; color: var(--text-4); }
.room-tags { display: flex; gap: 6px; flex-wrap: wrap; }
.rtag {
  font-size: 0.66rem; font-weight: 600; padding: 4px 10px; border-radius: 8px;
  background: var(--bg); color: var(--text-3); border: 1px solid var(--border);
  transition: all 0.18s;
}
.room-card:hover .rtag { background: var(--green-bg); color: var(--green-text); border-color: var(--green-border); }

.room-cap-badge {
  display: flex; flex-direction: column; align-items: center; gap: 2px; flex-shrink: 0;
}
.room-cap-number {
  font-family: var(--font-head);
  font-size: 1.1rem; font-weight: 800; color: var(--green-text);
  background: var(--green-bg); border: 1px solid var(--green-border);
  width: 42px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
}
.room-cap-label {
  font-size: 0.58rem; font-weight: 700; color: var(--text-4);
  text-transform: uppercase; letter-spacing: 0.06em;
}

/* Old room-row fallback */
.room-row { padding: 14px 22px; border-bottom: 1px solid rgba(228,232,240,0.6); transition: all 0.18s; cursor: pointer; }
.room-row:last-child { border-bottom: none; }
.room-row:hover { background: #fafbfd; }
.room-header-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }

/* ── EMPTY STATE — ENHANCED ─────────────────────────── */
.empty-state {
  padding: 48px 32px; text-align: center;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  color: var(--text-3); font-size: 0.84rem;
}
.empty-state-icon {
  width: 64px; height: 64px; border-radius: 18px;
  background: linear-gradient(135deg, var(--bg), #e2e8f4);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: var(--text-4); margin-bottom: 4px;
  box-shadow: inset 0 2px 4px rgba(15,23,41,0.04);
}
.empty-state-title {
  font-family: var(--font-head);
  font-size: 0.9rem; font-weight: 700; color: var(--text-2);
}
.empty-state-desc {
  font-size: 0.8rem; color: var(--text-3); max-width: 260px; line-height: 1.5;
}
.empty-state i { font-size: 2rem; color: var(--text-4); margin-bottom: 8px; display: block; opacity: 0.5; }

/* ── ANIMATIONS ──────────────────────────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}
.campus-hero      { animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both 0s; }
.welcome-banner   { animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both 0.05s; }
.bottom-grid      { animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both 0.18s; }
.assistant-panel  { animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both 0.28s; }

/* ── AI Recommendations ──────────────────────────────── */
.rec-list { display:flex;flex-direction:column;gap:8px;padding:14px 22px; }
.dashboard-rec-skeleton { display:flex; flex-direction:column; gap:10px; padding:14px 22px; }
.dashboard-rec-skeleton span { display:block; height:58px; border-radius:10px; background:linear-gradient(90deg,#f1f3f8 25%,#fff 50%,#f1f3f8 75%); background-size:200% 100%; animation:dashboardSkeleton 1.35s ease-in-out infinite; }
@keyframes dashboardSkeleton { 0% { background-position:200% 0; } 100% { background-position:-200% 0; } }
@media (prefers-reduced-motion:reduce) { .dashboard-rec-skeleton span { animation:none; background:#f1f3f8; } }
.rec-card {
  display:flex;align-items:center;gap:16px;padding:14px 16px;
  border-radius:14px;border:1px solid var(--border);background:var(--white);
  text-decoration:none;color:inherit;transition:all .2s cubic-bezier(0.4,0,0.2,1);
}
.rec-card:hover { transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:rgba(37,99,235,0.15); }
.rec-card.top-pick { background:linear-gradient(135deg,rgba(29,78,216,0.03),rgba(245,197,24,0.03));border-color:rgba(37,99,235,0.12); }
.rec-rank {
  width:44px;height:44px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-size:0.95rem;color:#fff;flex-shrink:0;
  background:linear-gradient(135deg,#1d4ed8,#3b82f6);
  box-shadow: 0 4px 12px rgba(29,78,216,0.25);
}
.rec-info{flex:1;min-width:0}
.rec-name{font-size:0.92rem;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px;font-family:var(--font-head);}
.rec-best-tag{font-size:0.6rem;font-weight:800;padding:3px 8px;border-radius:6px;background:var(--yellow-light);color:#b45309;border:1px solid rgba(245,197,24,0.25)}
.rec-meta{display:flex;gap:12px;color:var(--text-3);font-size:0.8rem;margin:5px 0}
.rec-reason{font-size:0.8rem;color:var(--blue-text);font-weight:600;margin-top:4px;display:flex;align-items:center;gap:6px}
.rec-features{display:flex;gap:6px;margin-top:6px}
.rec-feat-tag{font-size:0.72rem;padding:4px 8px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-2)}
.rec-score{display:flex;flex-direction:column;align-items:center;gap:6px;margin-left:8px}
.rec-score-ring{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid var(--border);}
.rec-score-ring.high{border-color:#60a5fa;background:var(--blue-bg)}
.rec-score-ring.med{border-color:#a78bfa;background:var(--purple-bg)}
.rec-score-val{font-size:1rem;font-weight:800;color:var(--blue-text)}
.rec-score-label{font-size:0.64rem;color:var(--text-3);font-weight:800}
.rec-arrow{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:var(--bg);color:var(--text-3);transition:all .18s;}
.rec-card:hover .rec-arrow { background:var(--navy);color:#fff;border-color:var(--navy); }

/* ═══════════════════════════════════════════════════
   SCHEDULE ASSISTANT
═══════════════════════════════════════════════════ */
.assistant-launcher { position:fixed;right:26px;bottom:26px;z-index:1600;width:58px;height:58px;border:0;border-radius:50%;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;cursor:pointer;box-shadow:0 12px 28px rgba(29,78,216,.3);font-size:1.2rem;transition:transform .2s,box-shadow .2s; }
.assistant-launcher:hover { transform:translateY(-3px) scale(1.03);box-shadow:0 16px 34px rgba(29,78,216,.38); }
.assistant-modal { position:fixed;inset:0;z-index:1500;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(7,22,64,.32);backdrop-filter:blur(4px); }
.assistant-modal.is-open { display:flex; }
.assistant-panel { width:min(720px,calc(100vw - 32px));max-height:min(760px,calc(100vh - 32px));background:var(--white);border-radius:var(--radius-xl);border:1px solid var(--border);box-shadow:0 24px 70px rgba(15,23,41,.22);overflow:hidden; }
.assistant-modal-close { width:32px;height:32px;border:1px solid var(--border);border-radius:9px;background:var(--white);color:var(--text-3);cursor:pointer;font-size:.85rem; }
.assistant-modal-close:hover { background:var(--bg);color:var(--text); }

.assistant-head {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
  background: linear-gradient(180deg, #f8faff 0%, var(--white) 100%);
}
.assistant-title {
  display: flex; align-items: center; gap: 12px;
  font-family: var(--font-head);
  font-size: 0.95rem; font-weight: 700;
}
.assistant-title-badge {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(135deg, #dbeafe, #93c5fd);
  color: var(--blue-text);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 0.85rem;
  box-shadow: 0 2px 6px rgba(59,130,246,0.15);
}
.assistant-sub {
  font-size: 0.78rem; color: var(--text-3); margin-top: 2px; padding-left: 46px; line-height: 1.4;
}
.assistant-llm-toggle {
  display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.assistant-llm-toggle label {
  font-size: .8rem; color: var(--text-3); display: flex; align-items: center; gap: 8px; cursor: pointer;
  background: var(--bg); padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border);
  transition: all 0.18s;
}
.assistant-llm-toggle label:hover { border-color: var(--blue-border); background: var(--blue-bg); }
.assistant-llm-toggle label span { font-weight: 600; color: var(--text-2); }
.assistant-llm-toggle input[type="checkbox"] {
  width: 16px; height: 16px; accent-color: var(--blue-mid); cursor: pointer;
}

.assistant-chat {
  padding: 20px 24px;
  display: flex; flex-direction: column; gap: 12px;
  min-height: 120px; max-height: 360px; overflow: auto;
  background:
    radial-gradient(ellipse at 20% 50%, rgba(59,130,246,0.03), transparent 60%),
    radial-gradient(ellipse at 80% 20%, rgba(139,92,246,0.03), transparent 50%),
    linear-gradient(180deg, #f8faff 0%, #f2f5fb 100%);
}
.assistant-chat::-webkit-scrollbar { width: 5px; }
.assistant-chat::-webkit-scrollbar-thumb { background: var(--border); border-radius: 5px; }
.assistant-chat::-webkit-scrollbar-track { background: transparent; }

.assistant-msg {
  max-width: 80%; border-radius: 16px;
  padding: 12px 16px; font-size: 0.84rem; line-height: 1.55;
}
.assistant-msg.user {
  margin-left: auto;
  background: linear-gradient(135deg, var(--navy), #1e3a8a);
  color: #fff; border-bottom-right-radius: 6px;
  box-shadow: 0 3px 12px rgba(11,22,64,0.15);
}
.assistant-msg.bot {
  margin-right: auto;
  background: var(--white); border: 1px solid var(--border);
  color: var(--text); border-bottom-left-radius: 6px;
  box-shadow: 0 1px 4px rgba(15,23,41,0.04);
}
.assistant-items { margin-top: 10px; display: grid; gap: 7px; }
.assistant-item {
  border: 1px solid var(--border); border-radius: 10px;
  padding: 10px 12px; background: var(--bg); transition: all 0.18s;
}
.assistant-item:hover { border-color: var(--blue-border); background: var(--blue-bg); }
.assistant-item-top { font-size: 0.8rem; color: var(--text); font-weight: 700; }
.assistant-item-meta { font-size: 0.74rem; color: var(--text-2); margin-top: 3px; }

.assistant-quick {
  display: flex; flex-wrap: wrap; gap: 8px;
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  background: linear-gradient(180deg, #fafbff 0%, var(--white) 100%);
}
.assistant-chip {
  border: 1px solid var(--blue-border);
  background: var(--white); color: var(--blue-text);
  border-radius: 22px; padding: 8px 18px;
  font-size: 0.76rem; font-weight: 600;
  cursor: pointer; font-family: var(--font-body);
  transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
  box-shadow: 0 1px 3px rgba(15,23,41,0.04);
}
.assistant-chip:hover {
  background: var(--blue-text); color: #fff; border-color: var(--blue-text);
  transform: translateY(-2px); box-shadow: 0 4px 12px rgba(29,78,216,0.2);
}
.assistant-form {
  display: flex; gap: 10px;
  padding: 16px 24px 20px;
  border-top: 1px solid var(--border); background: var(--white);
}
.assistant-input {
  flex: 1; border: 2px solid var(--border); border-radius: 14px;
  padding: 13px 18px; font-size: 0.86rem; outline: none;
  font-family: var(--font-body); transition: all 0.25s; background: var(--bg);
}
.assistant-input::placeholder { color: var(--text-4); }
.assistant-input:focus {
  border-color: var(--blue-mid);
  box-shadow: 0 0 0 4px rgba(59,130,246,0.08);
  background: var(--white);
}
.assistant-send {
  border: 0; border-radius: 14px; padding: 0 24px;
  font-size: 0.86rem; font-weight: 700;
  background: linear-gradient(135deg, var(--navy), #1e3a8a);
  color: #fff; cursor: pointer; font-family: var(--font-body);
  transition: all 0.2s;
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 3px 10px rgba(11,22,64,0.15);
}
.assistant-send:hover { transform: translateY(-2px); box-shadow: 0 5px 16px rgba(11,22,64,0.22); }
.assistant-send[disabled] { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.assistant-loading { display: inline-flex; gap: 5px; align-items: center; padding: 4px 0; }
.assistant-loading span {
  width: 7px; height: 7px; border-radius: 999px;
  background: var(--blue-border);
  animation: dotPulse 1.2s infinite ease-in-out;
}
.assistant-loading span:nth-child(2) { animation-delay: 0.15s; }
.assistant-loading span:nth-child(3) { animation-delay: 0.3s; }

@keyframes dotPulse {
  0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
  40% { transform: translateY(-4px); opacity: 1; }
}

/* ── TAG STYLES (for outside banner) ────────────────── */
.tag-green  { background: rgba(19, 180, 95, 0.12); color: #0c8f53; border: 1px solid rgba(19, 180, 95, 0.25); }
.tag-blue   { background: rgba(59, 130, 246, 0.10); color: #2b6fe8; border: 1px solid rgba(59, 130, 246, 0.22); }
.tag-amber  { background: rgba(245, 158, 11, 0.12); color: #b26a00; border: 1px solid rgba(245, 158, 11, 0.22); }
.tag-purple { background: rgba(124, 58, 237, 0.09); color: #5d2ec7; border: 1px solid rgba(124, 58, 237, 0.18); }

/* ── RESPONSIVE ──────────────────────────────────────── */
@media (max-width: 1280px) {
  .stats-grid  { grid-template-columns: repeat(3,1fr); }
  .bottom-grid { grid-template-columns: 1fr; }
  .content     { padding: 22px 20px 44px; }
}
@media (max-width: 900px) {
  .stats-grid  { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 768px) {
  :root { --sidebar-w: 0px; }
  .sidebar { display: none; }
  .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
  .welcome-banner { gap: 20px; margin: -16px -14px 0; padding: 24px 20px 22px; }
  .welcome-top { flex-direction: column; align-items: flex-start; gap: 16px; }
  .welcome-actions { width: 100%; flex-wrap: wrap; }
  .btn-banner { flex: 1 1 150px; justify-content: center; }
  .topbar { padding: 0 14px; gap: 8px; }
  .content { padding: 16px 14px 36px; gap: 18px; }
  .panel-header { padding: 14px 16px 12px; }
  .rec-list { padding: 10px 14px; }
  .res-row, .room-row { padding: 12px 16px; }
  .assistant-quick, .assistant-form, .assistant-chat { padding-left: 16px; padding-right: 16px; }
  .assistant-launcher { right:18px;bottom:18px;width:54px;height:54px; }
  .assistant-modal { padding:10px; }
}
@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr; }
  .welcome-greeting { font-size: 1.25rem; }
}

</style>
@include('partials.pro-motion')
</head>
<body>

<!-- ═══════════════════════════════════════════════════
     SIDEBAR — DO NOT CHANGE
═══════════════════════════════════════════════════ -->
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

<!-- ═══════════════════════════════════════════════════
     MAIN
═══════════════════════════════════════════════════ -->
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

    <!-- ── Campus Hero (new decorative header) ── -->
    <section class="campus-hero">
      <div class="campus-hero-bg"></div>
      <div class="campus-hero-overlay"></div>
      <div class="campus-hero-content">
        <div class="campus-hero-brand">
          <div class="campus-hero-logo"><i class="fas fa-building-columns"></i></div>
          <div class="campus-hero-brand-text">
            <div class="campus-hero-brand-name">Pangasinan State University</div>
            <div class="campus-hero-brand-campus">Asingan Campus</div>
          </div>
        </div>
        <div class="campus-hero-label">PSU SmartRoom</div>
        <h1 class="campus-hero-title">Faculty Dashboard</h1>
        <div class="campus-hero-sub">Manage your rooms, schedules, reservations, and attendance from one workspace.</div>
      </div>
    </section>

    <!-- ── Welcome Header ── -->
    <section class="welcome-banner">
      <div class="orb-accent"></div>
      <div class="welcome-top">
        <div class="welcome-text">
          <h1 class="welcome-greeting">Good morning, <em><?= htmlspecialchars($facultyName) ?></em></h1>
          <p class="welcome-sub">Here's what's happening with your faculty workspace today.</p>
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
            <div class="stat-label-group">
              <div class="stat-icon"><i class="fas fa-door-open"></i></div>
              <span class="stat-title">Available Rooms</span>
            </div>
            <span class="stat-tag tag-green">+3 new</span>
          </div>
          <div class="stat-bottom">
            <div class="stat-value"><?= (int) ($stats['available_rooms'] ?? 0) ?></div>
            <span class="stat-unit">rooms</span>
          </div>
        </div>

        <div class="stat-card blue">
          <div class="stat-top">
            <div class="stat-label-group">
              <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
              <span class="stat-title">My Reservations</span>
            </div>
            <span class="stat-tag tag-blue">This Week</span>
          </div>
          <div class="stat-bottom">
            <div class="stat-value"><?= (int) ($stats['my_reservations'] ?? 0) ?></div>
            <span class="stat-unit">booked</span>
          </div>
        </div>

        <div class="stat-card amber">
          <div class="stat-top">
            <div class="stat-label-group">
              <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
              <span class="stat-title">Active Classes</span>
            </div>
            <span class="stat-tag tag-amber">This Semester</span>
          </div>
          <div class="stat-bottom">
            <div class="stat-value"><?= (int) ($stats['active_classes'] ?? 0) ?></div>
            <span class="stat-unit">classes</span>
          </div>
        </div>

      </div>
    </section>

    <!-- ── Bottom Grid ── -->
    <div class="bottom-grid">

      <!-- AI Recommendations -->
      <div class="panel" id="ai-recommendations-panel">
        <div class="panel-header">
          <div class="panel-title">
            <span class="panel-title-icon pti-blue"><i class="fas fa-robot"></i></span>
            AI Recommendations
          </div>
          <a href="<?= htmlspecialchars(url('/ai-recommendations')) ?>" class="link-all">
            View all <i class="fas fa-arrow-right" style="font-size:0.6rem;"></i>
          </a>
        </div>

        <div id="ai-recommendations-content">
          <div class="dashboard-rec-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>
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
            <div class="empty-state-icon"><i class="fas fa-door-closed"></i></div>
            <div class="empty-state-title">No rooms available</div>
            <div class="empty-state-desc">All rooms are currently occupied. Check back later for availability.</div>
          </div>
        <?php else: ?>
        <div class="room-list">
          <?php foreach ($availableNowRooms as $room): ?>
          <div class="room-card">
            <div class="room-status-dot"><i class="fas fa-door-open"></i></div>
            <div class="room-info">
              <div class="room-id-text"><?= htmlspecialchars($room['id']) ?></div>
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
            <div class="room-cap-badge">
              <div class="room-cap-number"><?= htmlspecialchars($room['capacity']) ?></div>
              <div class="room-cap-label">Seats</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /bottom-grid -->

    <button class="assistant-launcher" id="scheduleAssistantOpen" type="button" aria-label="Open Smart Schedule Assistant" title="Smart Schedule Assistant">
      <i class="fas fa-comment-dots" aria-hidden="true"></i>
    </button>
    <div class="assistant-modal" id="scheduleAssistantModal" aria-hidden="true">
    <div class="assistant-panel" role="dialog" aria-modal="true" aria-labelledby="scheduleAssistantTitle">
      <div class="assistant-head">
        <div>
          <div class="assistant-title">
            <span class="assistant-title-badge"><i class="fas fa-comment-dots"></i></span>
            <span id="scheduleAssistantTitle">Smart Schedule Assistant</span>
          </div>
          <div class="assistant-sub">Ask in plain English: today, tomorrow, next class, weekday schedule, first class.</div>
        </div>
        <button class="assistant-modal-close" id="scheduleAssistantClose" type="button" aria-label="Close assistant"><i class="fas fa-xmark"></i></button>
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
  const assistantOpen = document.getElementById('scheduleAssistantOpen');
  const assistantModal = document.getElementById('scheduleAssistantModal');
  const assistantClose = document.getElementById('scheduleAssistantClose');
  const endpoint = "{{ route('faculty.schedule.assistant.ask') }}";
  const csrf = "{{ csrf_token() }}";

  if (!chat || !form || !input || !sendBtn || !quick) return;

  function closeAssistant() {
    assistantModal?.classList.remove('is-open');
    assistantModal?.setAttribute('aria-hidden', 'true');
  }

  assistantOpen?.addEventListener('click', function () {
    assistantModal?.classList.add('is-open');
    assistantModal?.setAttribute('aria-hidden', 'false');
    input.focus();
  });
  assistantClose?.addEventListener('click', closeAssistant);
  assistantModal?.addEventListener('click', function (event) {
    if (event.target === assistantModal) closeAssistant();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeAssistant();
  });

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

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ message: message }),
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
  root.setAttribute('aria-busy', 'true');
  fetch('/api/ai/recommendations')
    .then(r => r.json())
    .then(j => {
      if (!j.success || !Array.isArray(j.recommendations) || j.recommendations.length === 0) {
        root.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-robot"></i></div><div class="empty-state-title">No recommendations available</div><div class="empty-state-desc">Check back later for personalized room suggestions.</div></div>';
        root.setAttribute('aria-busy', 'false');
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
      root.setAttribute('aria-busy', 'false');
    })
    .catch(() => {
      root.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-robot"></i></div><div class="empty-state-title">Failed to load</div><div class="empty-state-desc">Could not load recommendations. Please try again later.</div></div>';
      root.setAttribute('aria-busy', 'false');
    });
});
</script>

@include('frontend.faculty.partials.notifications-widget')
</body>
</html>