<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Classrooms – SmartRoom</title>

<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  /* Brand */
  --yellow:         #f5c518;
  --yellow-light:   #fff8e1;
  --navy:           #0b1640;
  --navy-mid:       #1a2f80;

  /* Surface */
  --white:          #ffffff;
  --bg:             #f0f2f8;
  --bg-alt:         #e8ebf4;
  --surface:        #ffffff;
  --surface-raised: #ffffff;

  /* Border */
  --border:         #e2e6f0;
  --border-strong:  #cdd2e4;

  /* Text */
  --text:           #0d1117;
  --text-secondary: #5a6480;
  --text-light:     #9aa0b8;

  /* Status colors */
  --green:          #059669;
  --green-bg:       #d1fae5;
  --green-border:   #6ee7b7;
  --blue:           #2563eb;
  --blue-bg:        #dbeafe;
  --blue-border:    #93c5fd;
  --orange:         #d97706;
  --orange-bg:      #fef3c7;
  --orange-border:  #fcd34d;
  --red:            #dc2626;
  --red-bg:         #fee2e2;
  --red-border:     #fca5a5;

  /* Shadows */
  --shadow-sm:      0 1px 3px rgba(13,17,40,0.06), 0 1px 2px rgba(13,17,40,0.04);
  --shadow-md:      0 4px 16px rgba(13,17,40,0.08), 0 2px 6px rgba(13,17,40,0.05);
  --shadow-lg:      0 12px 40px rgba(13,17,40,0.12), 0 4px 12px rgba(13,17,40,0.07);
  --shadow-card:    0 2px 12px rgba(13,17,40,0.07);

  /* Layout */
  --sidebar-w:      240px;
  --radius:         16px;
  --radius-sm:      10px;
  --radius-xs:      7px;

  /* Font */
  --font:           'Instrument Sans', sans-serif;
  --font-mono:      'JetBrains Mono', monospace;
}

body {
  font-family: var(--font);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
}

/* ══════════════════════════════════════════════
   SIDEBAR — UNTOUCHED FROM SMARTLOCKING
══════════════════════════════════════════════ */
.sidebar {
  position: fixed; left: 0; top: 0;
  width: var(--sidebar-w); height: 100vh;
  background: var(--navy);
  display: flex; flex-direction: column;
  padding: 0; overflow: hidden; z-index: 100;
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
  background: none; border: none; cursor: pointer; font-family: inherit; text-decoration: none;
}
.sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,0.08); }

/* ══════════════════════════════════════════════
   MAIN LAYOUT
══════════════════════════════════════════════ */
.main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

/* ══════════════════════════════════════════════
   TOPBAR — REDESIGNED
══════════════════════════════════════════════ */
.topbar {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 36px;
  height: 66px;
  display: flex; align-items: center; justify-content: space-between; gap: 20px;
  position: sticky; top: 0; z-index: 50;
  box-shadow: 0 1px 0 var(--border), 0 2px 8px rgba(13,17,40,0.04);
}

.topbar-left { display: flex; align-items: center; gap: 10px; }
.topbar-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-light); }
.topbar-breadcrumb .sep { color: var(--border-strong); }
.topbar-breadcrumb .current { color: var(--text); font-weight: 600; }

.topbar-search {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg); border: 1.5px solid var(--border);
  border-radius: 24px; padding: 9px 18px; width: 300px;
  transition: all 0.2s;
}
.topbar-search:focus-within { border-color: var(--blue-border); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.07); }
.topbar-search i { color: var(--text-light); font-size: 0.82rem; }
.topbar-search input { border: none; outline: none; background: transparent; font-size: 0.875rem; font-family: var(--font); color: var(--text); width: 100%; }
.topbar-search input::placeholder { color: var(--text-light); }

.topbar-right { display: flex; align-items: center; gap: 12px; }

.topbar-date {
  display: flex; align-items: center; gap: 7px;
  font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;
  padding: 6px 12px; border-radius: 20px;
  background: var(--bg); border: 1px solid var(--border);
}
.topbar-date i { font-size: 0.75rem; color: var(--text-light); }

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
  border: 2px solid var(--border); cursor: pointer;
  transition: border-color 0.18s;
}
.topbar-avatar:hover { border-color: var(--navy); }

/* ══════════════════════════════════════════════
   CONTENT AREA
══════════════════════════════════════════════ */
.content { padding: 32px 36px 56px; display: flex; flex-direction: column; gap: 28px; }

/* ── PAGE HEADER ── */
.page-header {
  display: flex; align-items: flex-end; justify-content: space-between; gap: 20px;
  animation: fadeUp 0.4s both;
}
.page-header-left { display: flex; flex-direction: column; gap: 4px; }
.page-header-eyebrow {
  display: flex; align-items: center; gap: 8px;
  font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--blue);
}
.page-header-eyebrow::before {
  content: ''; width: 16px; height: 2px; background: var(--blue); border-radius: 2px;
}
.page-header h1 {
  font-size: 1.75rem; font-weight: 800; color: var(--text);
  letter-spacing: -0.03em; line-height: 1.1;
}
.page-header p { font-size: 0.875rem; color: var(--text-secondary); margin-top: 2px; }

.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 11px 22px; background: var(--navy); color: #fff;
  border: none; border-radius: var(--radius-sm); font-size: 0.875rem;
  font-weight: 700; font-family: var(--font); cursor: pointer;
  box-shadow: 0 4px 14px rgba(11,22,64,0.25);
  transition: all 0.2s; letter-spacing: -0.01em;
}
.btn-primary:hover { background: #152060; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(11,22,64,0.32); }
.btn-primary i { font-size: 0.8rem; }

/* ── STAT TILES ── */
.stats-strip {
  display: grid; grid-template-columns: repeat(4,1fr); gap: 16px;
  animation: fadeUp 0.4s both 0.06s;
}

.stat-card {
  background: var(--white); border-radius: var(--radius);
  border: 1.5px solid var(--border);
  padding: 22px 24px;
  display: flex; flex-direction: column; gap: 16px;
  box-shadow: var(--shadow-card);
  position: relative; overflow: hidden;
  transition: transform 0.22s, box-shadow 0.22s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.stat-card::after {
  content: ''; position: absolute; bottom: -20px; right: -20px;
  width: 90px; height: 90px; border-radius: 50%;
  opacity: 0.06; pointer-events: none;
}
.stat-card.sc-total::after   { background: var(--yellow); }
.stat-card.sc-avail::after   { background: var(--green); }
.stat-card.sc-occupied::after{ background: var(--blue); }
.stat-card.sc-issue::after   { background: var(--red); }

.stat-card-top { display: flex; align-items: flex-start; justify-content: space-between; }
.stat-card-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
}
.sc-total .stat-card-icon   { background: #fff8e1; color: #b45309; }
.sc-avail .stat-card-icon   { background: var(--green-bg); color: var(--green); }
.sc-occupied .stat-card-icon{ background: var(--blue-bg); color: var(--blue); }
.sc-issue .stat-card-icon   { background: var(--red-bg); color: var(--red); }

.stat-card-delta {
  font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 20px;
  letter-spacing: 0.02em;
}
.delta-up   { background: var(--green-bg); color: var(--green); }
.delta-down { background: var(--red-bg); color: var(--red); }
.delta-neu  { background: var(--bg); color: var(--text-secondary); }

.stat-card-val {
  font-size: 2.6rem; font-weight: 800; color: var(--text);
  letter-spacing: -0.04em; line-height: 1; font-family: var(--font);
}
.stat-card-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; margin-top: 4px; }
.stat-card-bar { height: 3px; border-radius: 2px; background: var(--bg-alt); overflow: hidden; }
.stat-card-bar-fill { height: 100%; border-radius: 2px; transition: width 0.8s ease; }
.sc-total   .stat-card-bar-fill { background: linear-gradient(90deg,#f59e0b,#fcd34d); }
.sc-avail   .stat-card-bar-fill { background: linear-gradient(90deg,#059669,#34d399); }
.sc-occupied .stat-card-bar-fill { background: linear-gradient(90deg,#2563eb,#60a5fa); }
.sc-issue   .stat-card-bar-fill { background: linear-gradient(90deg,#dc2626,#f87171); }
.occupancy-bar-fill { height: 100%; border-radius: 999px; transition: width 0.6s ease; background: linear-gradient(90deg,#059669,#34d399); }

/* ── MANAGE TABLE ── */
.section-block {
  background: var(--white); border-radius: var(--radius);
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
  overflow: hidden;
  animation: fadeUp 0.4s both 0.12s;
}
.section-block-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 24px; border-bottom: 1px solid var(--border);
  background: linear-gradient(to right, #fafbff, var(--white));
}
.section-block-title { font-size: 0.95rem; font-weight: 800; color: var(--text); letter-spacing: -0.01em; }
.section-block-sub { font-size: 0.78rem; color: var(--text-secondary); margin-top: 2px; }
.section-block-badge {
  font-size: 0.72rem; font-weight: 700; padding: 4px 12px;
  border-radius: 20px; background: var(--bg); border: 1px solid var(--border);
  color: var(--text-secondary); font-family: var(--font-mono);
}

.manage-table { width: 100%; border-collapse: collapse; }
.manage-table thead th {
  padding: 10px 20px; font-size: 0.7rem; font-weight: 700;
  letter-spacing: 0.1em; text-transform: uppercase;
  color: var(--text-light); background: var(--bg);
  border-bottom: 1px solid var(--border); text-align: left;
}
.manage-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
.manage-table tbody tr:last-child { border-bottom: none; }
.manage-table tbody tr:hover { background: #f8f9ff; }
.manage-table td { padding: 13px 20px; font-size: 0.85rem; color: var(--text); vertical-align: middle; }
.manage-table .td-room { font-weight: 700; }
.manage-table .td-loc { color: var(--text-secondary); font-size: 0.8rem; }
.manage-table .td-issue { color: var(--text-secondary); font-size: 0.8rem; font-style: italic; }

.table-status-pill {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 0.7rem; font-weight: 700; padding: 3px 10px;
  border-radius: 20px; letter-spacing: 0.04em;
}
.table-status-pill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.pill-available   { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
.pill-available::before   { background: var(--green); }
.pill-occupied    { background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-border); }
.pill-occupied::before    { background: var(--blue); animation: blink 1.5s infinite; }
.pill-closed      { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
.pill-closed::before      { background: var(--red); }
.pill-reserved    { background: var(--orange-bg); color: var(--orange); border: 1px solid var(--orange-border); }
.pill-reserved::before    { background: var(--orange); }
.pill-maintenance { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
.pill-maintenance::before { background: var(--red); }

.manage-link {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 0.8rem; font-weight: 700; color: var(--blue);
  text-decoration: none; padding: 5px 10px; border-radius: var(--radius-xs);
  border: 1px solid var(--blue-border); background: var(--blue-bg);
  transition: all 0.18s;
}
.manage-link:hover { background: var(--blue); color: #fff; border-color: var(--blue); }

@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.35;} }

/* ══════════════════════════════════════════════
   MODAL — POLISHED
══════════════════════════════════════════════ */
.room-modal-overlay {
  position: fixed; inset: 0; background: rgba(11,22,64,0.45);
  backdrop-filter: blur(4px); display: none; align-items: center;
  justify-content: center; padding: 20px; z-index: 2100;
}
.room-modal-overlay.is-open { display: flex; }
.room-modal {
  width: min(540px,100%); background: var(--white);
  border: 1.5px solid var(--border); border-radius: 20px;
  box-shadow: 0 24px 60px rgba(11,22,64,0.3); overflow: hidden;
  animation: modalIn 0.28s cubic-bezier(0.16,1,0.3,1);
}
@keyframes modalIn { from { opacity:0; transform:scale(0.95) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }

.room-modal-head {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 12px; padding: 20px 22px; border-bottom: 1px solid var(--border);
  background: linear-gradient(to right,#fafbff,var(--white));
}
.room-modal-head-left { display: flex; align-items: center; gap: 12px; }
.room-modal-head-icon {
  width: 42px; height: 42px; border-radius: 12px;
  background: var(--bg); border: 1.5px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; color: var(--navy); flex-shrink: 0;
}
.room-modal-title { font-size: 1rem; font-weight: 800; color: var(--text); letter-spacing: -0.01em; }
.room-modal-sub { font-size: 0.78rem; color: var(--text-secondary); margin-top: 2px; }
.room-modal-close {
  width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border);
  background: var(--white); color: var(--text-secondary); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
  transition: all 0.18s; flex-shrink: 0;
}
.room-modal-close:hover { background: var(--red-bg); color: var(--red); border-color: var(--red-border); }

.room-modal-body { padding: 20px 22px 22px; display: flex; flex-direction: column; gap: 14px; }
.room-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.room-field { display: flex; flex-direction: column; gap: 6px; }
.room-label {
  font-size: 0.7rem; font-weight: 800; letter-spacing: 0.1em;
  text-transform: uppercase; color: var(--text-secondary);
}
.room-input {
  width: 100%; padding: 10px 13px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--border); background: var(--bg);
  font-family: var(--font); font-size: 0.875rem; color: var(--text); outline: none;
  transition: all 0.18s;
}
.room-input:focus { border-color: var(--blue-border); background: var(--white); box-shadow: 0 0 0 3px rgba(37,99,235,0.07); }
.room-textarea { min-height: 76px; resize: vertical; }
.room-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.room-btn {
  padding: 10px 18px; border-radius: var(--radius-sm); font-size: 0.85rem;
  font-weight: 700; font-family: var(--font); cursor: pointer; border: 1.5px solid transparent;
  transition: all 0.18s; letter-spacing: -0.01em;
}
.room-btn-cancel { background: var(--white); color: var(--text-secondary); border-color: var(--border); }
.room-btn-cancel:hover { background: var(--bg); color: var(--text); }
.room-btn-submit { background: var(--navy); color: #fff; box-shadow: 0 3px 10px rgba(11,22,64,0.22); }
.room-btn-submit:hover { background: #152060; }

/* ── TOAST ── */
.toast-wrap { position: fixed; right: 20px; bottom: 20px; display: flex; flex-direction: column; gap: 8px; z-index: 2200; pointer-events: none; }
.toast {
  min-width: 250px; max-width: 360px; padding: 11px 14px; border-radius: 12px;
  box-shadow: var(--shadow-lg); font-size: 0.82rem; font-weight: 600;
  opacity: 0; transform: translateY(12px); transition: opacity 0.22s, transform 0.22s;
  font-family: var(--font); border: 1.5px solid;
}
.toast.is-visible { opacity: 1; transform: translateY(0); }
.toast-success { background: var(--green-bg); border-color: var(--green-border); color: #065f46; }
.toast-error   { background: var(--red-bg); border-color: var(--red-border); color: #991b1b; }
.toast-info    { background: var(--blue-bg); border-color: var(--blue-border); color: #1e40af; }

/* ── ANIMATIONS ── */
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
.room-card { animation: fadeUp 0.4s both; }
.room-card:nth-child(1) { animation-delay: 0.22s; }
.room-card:nth-child(2) { animation-delay: 0.28s; }
.room-card:nth-child(3) { animation-delay: 0.34s; }
.room-card:nth-child(4) { animation-delay: 0.40s; }
.room-card:nth-child(5) { animation-delay: 0.46s; }
.room-card:nth-child(6) { animation-delay: 0.52s; }

/* ── RESPONSIVE ── */
@media (max-width:1200px) { .stats-strip { grid-template-columns:repeat(2,1fr); } .room-grid { grid-template-columns:repeat(2,1fr); } .content { padding:24px 24px 48px; } }
@media (max-width:768px)  { :root { --sidebar-w:0px; } .sidebar { display:none; } .room-grid { grid-template-columns:1fr; } .toolbar { flex-direction:column; align-items:stretch; } }

  /* ===== ROOM MANAGEMENT DESIGN REFRESH ===== */
  .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
  .content { max-width: 1680px; width: 100%; margin-inline: auto; }
  .topbar-avatar-initial { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:var(--navy); color:var(--yellow); font-weight:800; font-size:.85rem; flex-shrink:0; }

  .flash { display:flex; align-items:flex-start; gap:10px; padding:12px 16px; border:1px solid; border-radius:12px; font-size:.86rem; font-weight:600; }
  .flash i { margin-top:2px; }
  .flash ul { margin:0; padding-left:18px; font-weight:500; }
  .flash-success { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
  .flash-warn { background:#fffbeb; border-color:#fde68a; color:#92400e; }
  .flash-error { background:#fef2f2; border-color:#fecaca; color:#991b1b; }

  .section-block { border-radius:20px; box-shadow:0 1px 2px rgba(13,17,40,.05), 0 10px 30px rgba(13,17,40,.06); }
  .section-block-head { padding:20px 28px; }
  .table-scroll { overflow-x:auto; }
  .room-table { min-width:880px; }
  .room-table thead th { padding:12px 18px; background:#f6f8fc; }
  .room-table thead th:first-child, .room-table td:first-child { padding-left:28px; }
  .room-table thead th:last-child, .room-table td:last-child { padding-right:28px; }
  .room-table td { padding:16px 18px; vertical-align:middle; }
  .room-table tbody tr { transition: background-color .15s, box-shadow .15s; }
  .room-table tbody tr:hover { background:#f7f9ff; box-shadow: inset 3px 0 0 var(--navy); }
  .room-table tr[hidden] { display:none; }

  .room-toolbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:14px 28px; border-bottom:1px solid var(--border); background:#fafbfd; }
  .room-chips { display:flex; flex-wrap:wrap; gap:8px; }
  .room-chip { display:inline-flex; align-items:center; gap:7px; min-height:36px; padding:6px 14px; border:1px solid var(--border); border-radius:999px; background:#fff; color:var(--text-secondary); font:700 .78rem var(--font); cursor:pointer; transition: background-color .15s, border-color .15s, color .15s; }
  .room-chip span { min-width:20px; padding:1px 7px; border-radius:999px; background:#eef1f7; color:var(--text); font-size:.7rem; text-align:center; }
  .room-chip:hover { border-color:#c3cde0; }
  .room-chip.is-active { background:var(--navy); border-color:var(--navy); color:#fff; }
  .room-chip.is-active span { background:var(--yellow); color:var(--navy); }
  .dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:currentColor; flex-shrink:0; }
  .dot-green { color:#10b981; } .dot-blue { color:#3b82f6; } .dot-amber { color:#f59e0b; } .dot-red { color:#ef4444; }
  .room-chip.is-active .dot { box-shadow:0 0 0 2px rgba(255,255,255,.35); }
  .room-search { display:flex; align-items:center; gap:9px; min-width:260px; padding:8px 14px; border:1.5px solid var(--border); border-radius:10px; background:#fff; transition: border-color .18s, box-shadow .18s; }
  .room-search:focus-within { border-color: var(--blue-border); box-shadow:0 0 0 3px rgba(37,99,235,.08); }
  .room-search i { color:var(--text-light); font-size:.8rem; }
  .room-search input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:500 .85rem var(--font); color:var(--text); }

  .room-cell { display:flex; align-items:center; gap:12px; }
  .room-avatar { width:40px; height:40px; flex:0 0 40px; display:flex; align-items:center; justify-content:center; border-radius:12px; background:linear-gradient(135deg,#e8ecfb,#dbe4fb); color:#1a2f80; font-size:.95rem; }
  .room-name { font-weight:800; font-size:.92rem; color:var(--text); letter-spacing:-.01em; }
  .room-sub { margin-top:2px; font-size:.76rem; color:var(--text-secondary); }

  .access-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:8px; border:1px solid var(--border); background:var(--bg); color:var(--text-secondary); font-size:.74rem; font-weight:700; }
  .access-chip.is-esp { background:#eef2ff; border-color:#c7d2fe; color:#3730a3; }
  .device-state { display:inline-flex; align-items:center; gap:7px; font-size:.8rem; font-weight:700; color:var(--text-secondary); }
  .device-state .dot { width:8px; height:8px; }
  .device-good { color:#047857; } .device-bad { color:#b91c1c; } .device-warn { color:#b45309; } .device-muted { color:var(--text-secondary); }
  .device-muted .dot { opacity:.45; }

  .td-occ { min-width:170px; }
  .occ { display:flex; flex-direction:column; gap:6px; }
  .occ-meta { display:flex; justify-content:space-between; font-size:.76rem; color:var(--text-secondary); font-weight:700; }
  .occ-meta strong { font-family:var(--font-mono); color:var(--text); font-weight:800; }
  .occ-track { height:8px; border-radius:999px; background:var(--bg-alt); overflow:hidden; }
  .occ-fill { height:100%; border-radius:999px; transition: width .5s ease; }
  .occ-ok { background:linear-gradient(90deg,#059669,#34d399); }
  .occ-mid { background:linear-gradient(90deg,#d97706,#fbbf24); }
  .occ-high { background:linear-gradient(90deg,#dc2626,#f87171); }

  .td-issue { max-width:260px; color:var(--text-secondary); font-size:.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .td-action { text-align:right; white-space:nowrap; }
  .room-manage { display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border:1px solid var(--navy); border-radius:9px; background:var(--navy); color:#fff; font:700 .78rem var(--font); text-decoration:none; transition: background-color .15s, transform .15s; }
  .room-manage:hover { background:#16286b; transform:translateY(-1px); }

  .room-empty { display:flex; flex-direction:column; align-items:center; gap:6px; padding:44px 20px; color:var(--text-secondary); font-size:.85rem; text-align:center; }
  .room-empty i { font-size:1.8rem; color:#9da8b8; margin-bottom:6px; }
  .room-empty strong { color:var(--text); font-size:.95rem; }

  @media (max-width:768px) { .room-toolbar { padding:12px 16px; } .room-search { min-width:0; width:100%; } .section-block-head { padding:16px; } }
  @media (prefers-reduced-motion: reduce) { .occ-fill, .room-chip, .room-manage, .room-table tbody tr { transition:none; } }
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>

@include('frontend.admin.partials.sidebar')

<!-- ════════════ MAIN ════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-breadcrumb">
        <span>Admin</span>
        <span class="sep">/</span>
        <span class="current">Room Management</span>
      </div>
    </div>

    <div class="topbar-search">
      <i class="fas fa-magnifying-glass"></i>
      <input id="roomSearchTop" aria-label="Filter rooms" type="search" placeholder="Search rooms, buildings, instructors…" autocomplete="off">
    </div>

    <div class="topbar-right">
      <div class="topbar-date">
        <i class="fas fa-calendar"></i>
        <span>{{ \Carbon\Carbon::now()->format('M j, Y') }}</span>
      </div>
      <div class="topbar-avatar-initial" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-left">
        <div class="page-header-eyebrow">Room Management</div>
        <h1>Classroom Directory</h1>
        <p>Real-time availability and status for all campus rooms.</p>
      </div>
      <button type="button" id="addRoomBtn" class="btn-primary">
        <i class="fas fa-plus"></i>
        Add Room
      </button>
    </div>

    @if (session('status'))
      <div class="flash flash-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><span>{{ session('status') }}</span></div>
    @endif
    @if (session('warning'))
      <div class="flash flash-warn" role="status"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>{{ session('warning') }}</span></div>
    @endif
    @if ($errors->any())
      <div class="flash flash-error" role="alert">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <div>
          <div>Please fix the following:</div>
          <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      </div>
    @endif

    <!-- STAT CARDS -->
    <div class="stats-strip">
      <div class="stat-card sc-total">
        <div class="stat-card-top">
          <div class="stat-card-icon"><i class="fas fa-door-open"></i></div>
          <span class="stat-card-delta delta-neu">Campus</span>
        </div>
        <div>
          <div class="stat-card-val">{{ $classrooms->count() }}</div>
          <div class="stat-card-label">Total Rooms</div>
        </div>
        <div class="stat-card-bar"><div class="stat-card-bar-fill" data-width="100"></div></div>
      </div>
      <div class="stat-card sc-avail">
        <div class="stat-card-top">
          <div class="stat-card-icon"><i class="fas fa-circle-check"></i></div>
          <span class="stat-card-delta delta-up">↑ Open</span>
        </div>
        <div>
          <div class="stat-card-val">{{ $roomStatuses->where('status','available')->count() }}</div>
          <div class="stat-card-label">Available Now</div>
        </div>
        <div class="stat-card-bar"><div class="stat-card-bar-fill" data-width="{{ $classrooms->count() ? round($roomStatuses->where('status','available')->count()/$classrooms->count()*100) : 0 }}"></div></div>
      </div>
      <div class="stat-card sc-occupied">
        <div class="stat-card-top">
          <div class="stat-card-icon"><i class="fas fa-user-group"></i></div>
          <span class="stat-card-delta delta-neu">Live</span>
        </div>
        <div>
          <div class="stat-card-val">{{ $roomStatuses->where('status','occupied')->count() }}</div>
          <div class="stat-card-label">In Use</div>
        </div>
        <div class="stat-card-bar"><div class="stat-card-bar-fill" data-width="{{ $classrooms->count() ? round($roomStatuses->where('status','occupied')->count()/$classrooms->count()*100) : 0 }}"></div></div>
      </div>
      <div class="stat-card sc-issue">
        <div class="stat-card-top">
          <div class="stat-card-icon"><i class="fas fa-triangle-exclamation"></i></div>
          <span class="stat-card-delta delta-down">Action</span>
        </div>
        <div>
          <div class="stat-card-val">{{ $roomStatuses->whereIn('status',['reserved','maintenance','unavailable','closed'])->count() }}</div>
          <div class="stat-card-label">Reserved / Maintenance</div>
        </div>
        <div class="stat-card-bar"><div class="stat-card-bar-fill" data-width="{{ $classrooms->count() ? round($roomStatuses->whereIn('status',['reserved','maintenance','unavailable','closed'])->count()/$classrooms->count()*100) : 0 }}"></div></div>
      </div>
    </div>

    <!-- ROOM DIRECTORY -->
    @php
      $roomCounts = [
        'all' => $classrooms->count(),
        'available' => $roomStatuses->where('status', 'available')->count(),
        'occupied' => $roomStatuses->where('status', 'occupied')->count(),
        'reserved' => $roomStatuses->where('status', 'reserved')->count(),
        'issues' => $roomStatuses->whereIn('status', ['maintenance', 'unavailable', 'closed'])->count(),
      ];
    @endphp
    <div class="section-block">
      <div class="section-block-head">
        <div>
          <div class="section-block-title">Room Overview</div>
          <div class="section-block-sub">Manage status and issue details for each classroom</div>
        </div>
        <span class="section-block-badge" id="roomCountBadge">{{ $classrooms->count() }} {{ $classrooms->count() === 1 ? 'room' : 'rooms' }}</span>
      </div>

      <div class="room-toolbar">
        <div class="room-chips" role="group" aria-label="Filter rooms by status">
          <button type="button" class="room-chip is-active" data-room-filter="all" aria-pressed="true">All <span>{{ $roomCounts['all'] }}</span></button>
          <button type="button" class="room-chip" data-room-filter="available" aria-pressed="false"><i class="dot dot-green" aria-hidden="true"></i>Available <span>{{ $roomCounts['available'] }}</span></button>
          <button type="button" class="room-chip" data-room-filter="occupied" aria-pressed="false"><i class="dot dot-blue" aria-hidden="true"></i>In use <span>{{ $roomCounts['occupied'] }}</span></button>
          <button type="button" class="room-chip" data-room-filter="reserved" aria-pressed="false"><i class="dot dot-amber" aria-hidden="true"></i>Reserved <span>{{ $roomCounts['reserved'] }}</span></button>
          <button type="button" class="room-chip" data-room-filter="issues" aria-pressed="false"><i class="dot dot-red" aria-hidden="true"></i>Issues <span>{{ $roomCounts['issues'] }}</span></button>
        </div>
        <label class="room-search">
          <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
          <input id="roomSearch" type="search" placeholder="Filter by room, building, instructor…" aria-label="Filter rooms" autocomplete="off">
        </label>
      </div>

      <div class="table-scroll">
      <table class="manage-table room-table" id="roomTable">
        <thead>
          <tr>
            <th>Room</th>
            <th>Access</th>
            <th>Door device</th>
            <th>Occupancy</th>
            <th>Status</th>
            <th>Issue / Note</th>
            <th><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse($classrooms as $classroom)
            @php
              $roomStatus = $roomStatuses->get($classroom->id, ['status' => 'available', 'status_label' => 'Available', 'reason' => null]);
              $activeDevice = $classroom->devices->firstWhere('status', 'active');
              $onlineDevice = $classroom->devices->first(fn ($device) => $device->status === 'active' && $device->last_seen_at?->gt(now()->subMinutes(5)));
              $deviceLabel = $classroom->access_mode !== 'esp32' ? 'Manual' : ($classroom->devices->isEmpty() ? 'Not installed' : ($onlineDevice ? 'Online' : ($activeDevice ? ($activeDevice->last_seen_at ? 'Offline' : 'Awaiting connection') : 'Disabled')));
              $deviceTone = match ($deviceLabel) { 'Online' => 'good', 'Offline' => 'bad', 'Awaiting connection' => 'warn', default => 'muted' };
              $cap = (int) ($classroom->capacity ?? 0);
              $occ = (int) ($classroom->current_occupancy ?? 0);
              $pct = $cap > 0 ? (int) round($occ / $cap * 100) : 0;
              $occTone = $pct >= 90 ? 'high' : ($pct >= 70 ? 'mid' : 'ok');
              $statusGroup = in_array($roomStatus['status'], ['maintenance', 'unavailable', 'closed'], true) ? 'issues' : $roomStatus['status'];
              $instructors = $classroom->schedules->map(fn ($s) => $s->course?->instructor?->name)->filter()->unique()->implode(' ');
              $note = $roomStatus['reason'] ?? $classroom->unavailable_reason;
              $searchText = \Illuminate\Support\Str::lower(implode(' ', array_filter([$classroom->name, $classroom->building, $classroom->floor, $roomStatus['status_label'], $note, $instructors])));
            @endphp
            <tr data-status-group="{{ $statusGroup }}" data-search="{{ $searchText }}">
              <td>
                <div class="room-cell">
                  <span class="room-avatar" aria-hidden="true"><i class="fas fa-door-open"></i></span>
                  <div>
                    <div class="room-name">{{ $classroom->name }}</div>
                    <div class="room-sub">{{ $classroom->building }}{{ $classroom->floor ? ' · '.$classroom->floor : '' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="access-chip {{ $classroom->access_mode === 'esp32' ? 'is-esp' : '' }}">
                  <i class="fas {{ $classroom->access_mode === 'esp32' ? 'fa-microchip' : 'fa-hand' }}" aria-hidden="true"></i>
                  {{ $classroom->access_mode === 'esp32' ? 'ESP32' : 'Manual' }}
                </span>
              </td>
              <td><span class="device-state device-{{ $deviceTone }}"><i class="dot" aria-hidden="true"></i>{{ $deviceLabel }}</span></td>
              <td class="td-occ">
                <div class="occ">
                  <div class="occ-meta"><span>{{ $occ }} / {{ $cap ?: '–' }}</span><strong>{{ $pct }}%</strong></div>
                  <div class="occ-track" role="img" aria-label="{{ $pct }}% occupied"><div class="occ-fill occ-{{ $occTone }}" style="width: {{ min(100, $pct) }}%"></div></div>
                </div>
              </td>
              <td>
                <span class="table-status-pill pill-{{ $roomStatus['status'] }}">{{ $roomStatus['status_label'] }}</span>
              </td>
              <td class="td-issue" title="{{ $note }}">{{ $note ?: '—' }}</td>
              <td class="td-action"><a class="room-manage" href="{{ route('admin.classrooms.show', $classroom->id) }}" aria-label="Manage {{ $classroom->name }}">Manage <i class="fas fa-arrow-right" aria-hidden="true"></i></a></td>
            </tr>
          @empty
            <tr>
              <td colspan="7">
                <div class="room-empty"><i class="fas fa-door-open" aria-hidden="true"></i><strong>No rooms yet</strong><span>Click <b>Add Room</b> to create your first classroom.</span></div>
              </td>
            </tr>
          @endforelse
          <tr id="roomNoMatch" hidden>
            <td colspan="7">
              <div class="room-empty"><i class="fas fa-filter-circle-xmark" aria-hidden="true"></i><strong>No rooms match</strong><span>Try a different status or search term.</span></div>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>


  </div><!-- /content -->
</div><!-- /main -->

<!-- ADD ROOM MODAL -->
<div class="room-modal-overlay" id="addRoomModal" aria-hidden="true">
  <div class="room-modal" role="dialog" aria-modal="true" aria-labelledby="addRoomModalTitle">
    <div class="room-modal-head">
      <div class="room-modal-head-left">
        <div class="room-modal-head-icon"><i class="fas fa-door-open"></i></div>
        <div>
          <div class="room-modal-title" id="addRoomModalTitle">Add New Room</div>
          <div class="room-modal-sub">Create a classroom in the system</div>
        </div>
      </div>
      <button type="button" class="room-modal-close" id="addRoomModalCloseBtn" aria-label="Close">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <form id="addRoomForm" class="room-modal-body">
      <label class="room-field">
        <span class="room-label">Room Name</span>
        <input id="roomName" class="room-input" type="text" placeholder="e.g., Room 401" required>
      </label>
      <label class="room-field">
        <span class="room-label">Building</span>
        <input id="roomBuilding" class="room-input" type="text" placeholder="e.g., Main Building" required>
      </label>
      <div class="room-grid-2">
        <label class="room-field">
          <span class="room-label">Floor</span>
          <input id="roomFloor" class="room-input" type="text" placeholder="e.g., 1st Floor">
        </label>
        <label class="room-field">
          <span class="room-label">Capacity</span>
          <input id="roomCapacity" class="room-input" type="number" min="0" placeholder="40" required>
        </label>
      </div>
      <label class="room-field">
        <span class="room-label">Status</span>
        <select id="roomStatus" class="room-input" required>
          <option value="available" selected>Available</option>
          <option value="occupied">Occupied</option>
          <option value="reserved">Reserved</option>
          <option value="maintenance">Maintenance</option>
          <option value="unavailable">Unavailable</option>
        </select>
      </label>
      <label class="room-field">
        <span class="room-label">Door access</span>
        <select id="roomAccessMode" class="room-input" required>
          <option value="manual" selected>Manual access</option>
          <option value="esp32">ESP32-controlled</option>
        </select>
        <span class="room-hint">You can register a door device from this room after saving.</span>
      </label>
      <label class="room-field" id="issueReasonWrap" style="display:none;">
        <span class="room-label">Issue Note</span>
        <textarea id="roomIssueReason" class="room-input room-textarea" placeholder="e.g., AC repair ongoing"></textarea>
      </label>
      <div class="room-modal-actions">
        <button type="button" class="room-btn room-btn-cancel" id="addRoomModalCancelBtn">Cancel</button>
        <button type="submit" class="room-btn room-btn-submit" id="addRoomSubmitBtn">
          <i class="fas fa-plus" style="font-size:0.75rem;margin-right:4px;"></i>Save Room
        </button>
      </div>
    </form>
  </div>
</div>

<div class="toast-wrap" id="toastWrap" aria-live="polite" aria-atomic="true"></div>

<script>
  document.querySelectorAll('.stat-card-bar-fill[data-width]').forEach((bar) => {
    const width = Math.max(0, Math.min(100, Number(bar.dataset.width || 0)));
    bar.style.width = `${width}%`;
  });

  const addRoomModal   = document.getElementById('addRoomModal');
  const addRoomForm    = document.getElementById('addRoomForm');
  const roomStatus     = document.getElementById('roomStatus');
  const roomAccessMode = document.getElementById('roomAccessMode');
  const issueReasonWrap= document.getElementById('issueReasonWrap');
  const roomIssueReason= document.getElementById('roomIssueReason');
  const addRoomSubmitBtn=document.getElementById('addRoomSubmitBtn');
  const toastWrap      = document.getElementById('toastWrap');

  function showToast(message, type = 'info') {
    if (!toastWrap || !message) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toastWrap.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => { toast.classList.remove('is-visible'); setTimeout(() => toast.remove(), 220); }, 2800);
  }

  function toggleIssueReasonField() {
    const s = (roomStatus?.value || 'available').toLowerCase();
    const needs = s === 'maintenance' || s === 'unavailable';
    issueReasonWrap.style.display = needs ? '' : 'none';
    roomIssueReason.required = needs;
    if (!needs) roomIssueReason.value = '';
  }

  function openModal() {
    if (!addRoomModal || !addRoomForm) return;
    addRoomForm.reset(); toggleIssueReasonField();
    addRoomModal.classList.add('is-open'); addRoomModal.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
    document.getElementById('roomName')?.focus();
  }
  function closeModal() {
    if (!addRoomModal) return;
    addRoomModal.classList.remove('is-open'); addRoomModal.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  document.getElementById('addRoomBtn')?.addEventListener('click', openModal);
  document.getElementById('addRoomModalCloseBtn')?.addEventListener('click', closeModal);
  document.getElementById('addRoomModalCancelBtn')?.addEventListener('click', closeModal);
  roomStatus?.addEventListener('change', toggleIssueReasonField);
  addRoomModal?.addEventListener('click', e => { if (e.target === addRoomModal) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && addRoomModal?.classList.contains('is-open')) closeModal(); });

  /* Directory filtering: status chips + live search (replaces the old non-functional filter code) */
  (function () {
    var rows = Array.prototype.slice.call(document.querySelectorAll('#roomTable tbody tr[data-search]'));
    var chips = Array.prototype.slice.call(document.querySelectorAll('[data-room-filter]'));
    var inputs = [document.getElementById('roomSearch'), document.getElementById('roomSearchTop')].filter(Boolean);
    var noMatch = document.getElementById('roomNoMatch');
    var badge = document.getElementById('roomCountBadge');
    var filter = 'all';
    var query = '';

    function apply() {
      var shown = 0;
      rows.forEach(function (row) {
        var okStatus = filter === 'all' || row.dataset.statusGroup === filter;
        var okText = !query || row.dataset.search.indexOf(query) !== -1;
        row.hidden = !(okStatus && okText);
        if (!row.hidden) shown++;
      });
      if (noMatch) noMatch.hidden = !(rows.length > 0 && shown === 0);
      if (badge) badge.textContent = shown + (shown === 1 ? ' room' : ' rooms');
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        filter = chip.dataset.roomFilter;
        chips.forEach(function (c) {
          var active = c === chip;
          c.classList.toggle('is-active', active);
          c.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        apply();
      });
    });

    inputs.forEach(function (input) {
      input.addEventListener('input', function () {
        query = input.value.trim().toLowerCase();
        inputs.forEach(function (other) { if (other !== input) other.value = input.value; });
        apply();
      });
    });
  })();

  addRoomForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const name     = document.getElementById('roomName')?.value.trim();
    const building = document.getElementById('roomBuilding')?.value.trim();
    const floor    = document.getElementById('roomFloor')?.value.trim();
    const capacity = parseInt(document.getElementById('roomCapacity')?.value || '0', 10);
    const status   = (roomStatus?.value || 'available').toLowerCase();
    const reason   = roomIssueReason?.value.trim();

    if (!name || !building) { showToast('Room name and building are required.', 'error'); return; }
    if (isNaN(capacity) || capacity < 0) { showToast('Capacity must be a valid non-negative number.', 'error'); return; }
    if ((status === 'maintenance' || status === 'unavailable') && !reason) { showToast('Issue note is required.', 'error'); return; }

    addRoomSubmitBtn.disabled = true;
    try {
      let res;
      try {
        res = await fetch("{{ route('admin.classrooms.store') }}", {
          credentials: 'same-origin',
          method: 'POST',
          headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':"{{ csrf_token() }}" },
          body: JSON.stringify({ name, building, floor: floor||null, capacity, status, access_mode: roomAccessMode.value, unavailable_reason: reason||null, current_occupancy: 0 }),
        });
      } catch (networkErr) {
        console.error('Network error while creating room', networkErr);
        showToast('Network error. Please check your connection and try again.', 'error');
        return;
      }

      if (!res.ok) {
        const err = await res.json().catch(()=>({}));
        console.error('Create room failed', err);
        const firstError = err?.errors ? Object.values(err.errors)[0]?.[0] : null; showToast(firstError || err?.message || 'Failed to create room.', 'error');
        return;
      }

      closeModal(); window.location.reload();
    } finally { addRoomSubmitBtn.disabled = false; }
  });

  document.querySelectorAll('.js-view-room').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.getAttribute('data-room-id');
      if (id) window.location.href = `/admin/classrooms/${id}`;
    });
  });
</script>

</body>
</html>