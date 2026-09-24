<?php
$facultyName = request()->user()?->name ?? 'Faculty';
$facultyDept = request()->user()?->department ?? 'Faculty';
$facultyEmail = request()->user()?->email ?? '';
$facultyInitials = collect(explode(' ', $facultyName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
$facultyInitials = $facultyInitials !== '' ? $facultyInitials : 'U';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Recommendations – SmartDoor</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --yellow:        #f5c518;
  --yellow-bg:     #fff8e1;
  --navy:          #0b1640;
  --navy-mid:      #1a2f80;
  --white:         #ffffff;
  --bg:            #f4f6f9;
  --border:        #e8ecf3;
  --text:          #111827;
  --text-secondary:#6b7280;
  --text-light:    #9ca3af;
  --green:         #16a34a;
  --green-bg:      #dcfce7;
  --green-border:  #86efac;
  --blue-bg:       #dbeafe;
  --blue-border:   #93c5fd;
  --blue-text:     #1d4ed8;
  --orange-bg:     #fff7ed;
  --orange-border: #fed7aa;
  --orange-text:   #c2410c;
  --red:           #dc2626;
  --red-bg:        #fee2e2;
  --red-border:    #fca5a5;
  --purple-bg:     #ede9fe;
  --purple-text:   #7c3aed;
  --shadow:        0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-card:   0 2px 8px rgba(0,0,0,0.06);
  --radius:        14px;
  --radius-sm:     10px;
  --sidebar-w:     240px;
}

body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

/* SIDEBAR + MAIN LAYOUT (ensure consistent app layout) */
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  width: var(--sidebar-w);
  height: 100vh;
  background: var(--navy);
  color: var(--white);
  display: flex;
  flex-direction: column;
  padding: 0 14px;
  z-index: 100;
  overflow-y: auto;
}
.main {
  flex: 1 1 auto;
  margin-left: var(--sidebar-w);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  width: calc(100% - var(--sidebar-w));
}
.sidebar-logo {
  display: flex; align-items: center; gap: 12px; padding: 28px 6px 24px;
  color: var(--white); text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 8px;
}
.sidebar-logo-img {
  display: none;
}
.logo-mark { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--yellow); color: var(--navy); font-size: 1.1rem; box-shadow: 0 4px 12px rgba(245,197,24,0.35); }
.logo-text { line-height: 1; }
.sidebar-logo-text { line-height: 1.05; }
.brand-psu { display: block; color: rgba(255,255,255,0.55); font-size: 0.58rem; font-weight: 700; letter-spacing: 0.12em; }
.brand-main { color: #fff; font-size: 0.98rem; font-weight: 700; }
.door-accent { color: var(--yellow); }
.sidebar-nav { list-style: none; padding: 0 0 4px; margin: 0; }
.sidebar-nav li { margin: 2px 0; }
.nav-section-label { display: block; padding: 16px 10px 6px; color: rgba(255,255,255,0.3); font-size: 0.64rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; }
.sidebar-nav a {
  display: flex; align-items: center; gap: 11px; padding: 8px 10px;
  color: rgba(255,255,255,0.6); text-decoration: none; border-radius: 10px;
  font-size: 0.84rem; font-weight: 600; transition: background 0.18s, color 0.18s;
}
.sidebar-nav a .nav-icon { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.55); }
.sidebar-nav a > i { width: 18px; text-align: center; color: rgba(255,255,255,0.5); }
.sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(245,197,24,0.14); color: var(--yellow); }
.sidebar-nav a:hover > i, .sidebar-nav a.active > i, .sidebar-nav a:hover .nav-icon, .sidebar-nav a.active .nav-icon { color: var(--yellow); }
.sidebar-footer { margin-top: auto; padding: 16px 0 24px; border-top: 1px solid rgba(255,255,255,0.08); }
.user-widget { display: flex; align-items: center; gap: 9px; padding: 9px 10px; margin-bottom: 8px; border-radius: 9px; background: rgba(255,255,255,0.05); }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--navy-mid); border: 2px solid rgba(245,197,24,0.4); color: var(--yellow); font-size: 0.72rem; font-weight: 700; }
.user-widget-info { min-width: 0; }
.user-widget-name { overflow: hidden; color: #fff; font-size: 0.76rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.user-widget-role { color: rgba(255,255,255,0.45); font-size: 0.68rem; }
.sidebar-logout { margin-top: auto; padding: 12px 0 2px; border-top: 1px solid rgba(255,255,255,0.08); }
.sidebar-logout-btn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 10px; color: rgba(255,255,255,0.55); background: none; border: 0; border-radius: 9px; font: 600 0.84rem 'Inter', sans-serif; cursor: pointer; }
.sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,0.08); }
.topbar {
  min-height: 64px; display: flex; align-items: center; gap: 16px;
  padding: 0 24px; background: var(--white); border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 50;
}
.topbar-search {
  display: flex; align-items: center; gap: 9px; width: min(100%, 560px);
  padding: 8px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: 24px;
}
.topbar-search i { color: var(--text-light); font-size: 0.86rem; }
.topbar-search input { width: 100%; border: 0; outline: 0; background: transparent; color: var(--text); font: 0.84rem 'Inter', sans-serif; }
.topbar-right { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.topbar-profile { position: relative; display: flex; align-items: center; gap: 10px; cursor: pointer; }
.topbar-profile-info { text-align: right; }
.topbar-profile-name { color: var(--text); font-size: 0.84rem; font-weight: 700; line-height: 1.2; }
.topbar-profile-role { color: var(--text-secondary); font-size: 0.72rem; }
.topbar-avatar {
  width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  background: var(--blue-bg); border: 1px solid var(--blue-border); color: var(--blue-text); font-size: 0.76rem; font-weight: 700;
}
.profile-dropdown {
  position: absolute; top: calc(100% + 10px); right: 0; min-width: 230px; display: none;
  padding: 10px 12px 8px; background: var(--white); border: 1px solid var(--border);
  border-radius: var(--radius-sm); box-shadow: 0 12px 30px rgba(15,23,42,0.14); z-index: 200;
}
.profile-dropdown.is-open { display: block; }

.profile-dropdown-text { display: flex; flex-direction: column; gap: 2px; }
.profile-dropdown-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-light);
  font-weight: 700;
  padding: 2px 0 3px;
}
.profile-dropdown-value {
  font-size: 0.82rem;
  color: var(--text-secondary);
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
  background: #fecaca;
  transform: translateY(-1px);
}

/* CONTENT */
.content { padding: 32px 36px 48px; display: flex; flex-direction: column; gap: 24px; }

/* ════════════════════════════
   HERO HEADER
════════════════════════════ */
.ai-hero {
  background: linear-gradient(135deg, var(--navy) 0%, #1a2f80 100%);
  border-radius: 18px; padding: 28px 32px;
  display: flex; align-items: center; justify-content: space-between; gap: 24px;
  position: relative; overflow: hidden;
  box-shadow: 0 8px 32px rgba(11,22,64,0.18);
  animation: fadeIn 0.4s both;
}
.ai-hero::before {
  content: ''; position: absolute; top: -50px; right: -50px;
  width: 220px; height: 220px; border-radius: 50%;
  border: 1px solid rgba(245,197,24,0.1); pointer-events: none;
}
.ai-hero::after {
  content: ''; position: absolute; bottom: -70px; right: 120px;
  width: 180px; height: 180px; border-radius: 50%;
  border: 1px solid rgba(245,197,24,0.06); pointer-events: none;
}
.ai-hero-left { display: flex; align-items: center; gap: 20px; position: relative; z-index: 1; }
.ai-hero-icon {
  width: 60px; height: 60px; border-radius: 16px;
  background: rgba(245,197,24,0.15); border: 1.5px solid rgba(245,197,24,0.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: var(--yellow); flex-shrink: 0;
}
.ai-hero-title { font-size: 1.45rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; margin-bottom: 4px; }
.ai-hero-sub { font-size: 0.86rem; color: rgba(255,255,255,0.55); }
.ai-hero-right { display: flex; align-items: center; gap: 10px; position: relative; z-index: 1; }
.ai-live-badge {
  display: flex; align-items: center; gap: 8px;
  background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3);
  border-radius: 100px; padding: 7px 16px;
  font-size: 0.78rem; font-weight: 700; color: #4ade80; letter-spacing: 0.05em;
}
.ai-live-dot { width: 7px; height: 7px; background: #4ade80; border-radius: 50%; box-shadow: 0 0 8px rgba(74,222,128,0.6); animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.4;} }
.ai-refresh-btn {
  display: flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 10px;
  background: rgba(255,255,255,0.08); border: 1.5px solid rgba(255,255,255,0.15);
  color: rgba(255,255,255,0.8); font-size: 0.84rem; font-weight: 600;
  font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s;
}
.ai-refresh-btn:hover { background: rgba(255,255,255,0.14); color: #fff; }

/* ════════════════════════════
   QUICK STATS
════════════════════════════ */
.quick-stats {
  display: grid; grid-template-columns: repeat(4,1fr); gap: 14px;
  animation: fadeIn 0.4s both 0.08s;
}
.qs-card {
  background: var(--white); border-radius: var(--radius); border: 1.5px solid var(--border);
  padding: 18px 20px; box-shadow: var(--shadow-card);
  display: flex; align-items: center; gap: 14px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.qs-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.qs-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
.qi-blue   { background: var(--blue-bg);   color: var(--blue-text); }
.qi-green  { background: var(--green-bg);  color: var(--green); }
.qi-yellow { background: var(--yellow-bg); color: #b45309; }
.qi-purple { background: var(--purple-bg); color: var(--purple-text); }
.qs-val { font-size: 1.55rem; font-weight: 800; color: var(--text); letter-spacing: -0.03em; line-height: 1; }
.qs-label { font-size: 0.76rem; color: var(--text-secondary); font-weight: 500; margin-top: 3px; }

/* ════════════════════════════
   MAIN GRID
════════════════════════════ */
.main-grid {
  display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 20px;
  animation: fadeIn 0.4s both 0.14s;
}

/* ─ CARD BASE ─ */
.card {
  background: var(--white); border-radius: var(--radius);
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card); overflow: hidden;
}
.card-head {
  padding: 18px 22px 14px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid var(--border);
}
.card-head-title { display: flex; align-items: center; gap: 10px; font-size: 0.95rem; font-weight: 700; color: var(--text); }
.card-head-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
.card-head-meta { font-size: 0.78rem; color: var(--text-secondary); font-weight: 500; }

/* ════════════════════════════
   RECOMMENDATION CARDS
════════════════════════════ */
.rec-list { display: flex; flex-direction: column; gap: 0; }

.rec-card {
  display: flex; align-items: center; gap: 16px; padding: 16px 22px;
  border-bottom: 1px solid var(--border); cursor: pointer;
  transition: background 0.18s;
  text-decoration: none; color: inherit;
  position: relative;
}
.rec-card:last-child { border-bottom: none; }
.rec-card:hover { background: #f8faff; }
.rec-card.top-pick { background: linear-gradient(90deg, rgba(29,78,216,0.03) 0%, transparent 100%); }
.rec-card.top-pick::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0;
  width: 3px; background: linear-gradient(180deg, var(--blue-text), #60a5fa);
  border-radius: 0 2px 2px 0;
}

/* rank badge */
.rec-rank {
  width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.15rem; font-weight: 800;
}
.rank-1 { background: linear-gradient(135deg, #1d4ed8, #3b82f6); color: #fff; }
.rank-2 { background: linear-gradient(135deg, #7c3aed, #a78bfa); color: #fff; }
.rank-3 { background: linear-gradient(135deg, #0f766e, #2dd4bf); color: #fff; }

.rec-info { flex: 1; }
.rec-name { font-size: 0.94rem; font-weight: 700; color: var(--text); margin-bottom: 3px; display: flex; align-items: center; gap: 8px; }
.rec-best-tag { font-size: 0.62rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; background: var(--yellow-bg); color: #b45309; border: 1px solid rgba(245,197,24,0.3); letter-spacing: 0.04em; }
.rec-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 5px; }
.rec-meta-item { font-size: 0.76rem; color: var(--text-secondary); display: flex; align-items: center; gap: 4px; }
.rec-meta-item i { font-size: 0.68rem; color: var(--text-light); }
.rec-reason { font-size: 0.76rem; color: var(--blue-text); font-weight: 500; display: flex; align-items: center; gap: 5px; }
.rec-reason i { font-size: 0.68rem; }
.rec-features { display: flex; gap: 5px; margin-top: 6px; flex-wrap: wrap; }
.rec-feat-tag { font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 5px; background: var(--bg); border: 1px solid var(--border); color: var(--text-secondary); }

.rec-score { text-align: center; flex-shrink: 0; margin-right: 4px; }
.rec-score-val { font-size: 1.05rem; font-weight: 800; color: var(--blue-text); line-height: 1; }
.rec-score-ring {
  width: 52px; height: 52px; border-radius: 50%;
  border: 3px solid var(--border); display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  position: relative; margin: 0 auto 3px;
}
.rec-score-ring.high  { border-color: #60a5fa; background: var(--blue-bg); }
.rec-score-ring.med   { border-color: #a78bfa; background: var(--purple-bg); }
.rec-score-ring.low   { border-color: #5eead4; background: #f0fdfa; }
.rec-score-label { font-size: 0.58rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.06em; }
.rec-arrow { width: 32px; height: 32px; border-radius: 8px; background: var(--bg); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 0.78rem; flex-shrink: 0; transition: all 0.18s; }
.rec-card:hover .rec-arrow { background: var(--navy); color: #fff; border-color: var(--navy); }

/* ════════════════════════════
   RIGHT COLUMN
════════════════════════════ */
.right-col { display: flex; flex-direction: column; gap: 18px; }

/* PREDICTION CARD */
.prediction-card {
  background: linear-gradient(135deg, var(--navy) 0%, #1a2f80 100%);
  border-radius: var(--radius); padding: 22px 24px;
  box-shadow: 0 8px 28px rgba(11,22,64,0.2);
  position: relative; overflow: hidden;
}
.prediction-card::before {
  content: ''; position: absolute; top: -40px; right: -40px;
  width: 140px; height: 140px; border-radius: 50%;
  border: 1px solid rgba(245,197,24,0.1); pointer-events: none;
}
.pred-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.pred-title { font-size: 0.92rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px; }
.pred-title i { color: var(--yellow); }
.pred-time-badge { font-size: 0.72rem; font-weight: 600; padding: 4px 10px; border-radius: 7px; background: rgba(245,197,24,0.15); color: var(--yellow); border: 1px solid rgba(245,197,24,0.25); }
.pred-label { font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-bottom: 10px; }
.pred-bar-wrap { margin-bottom: 16px; }
.pred-bar-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.pred-bar-name { font-size: 0.76rem; color: rgba(255,255,255,0.6); }
.pred-bar-pct { font-size: 0.76rem; font-weight: 700; color: var(--yellow); }
.pred-bar { height: 7px; background: rgba(255,255,255,0.12); border-radius: 99px; overflow: hidden; margin-bottom: 10px; }
.pred-bar-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #f5c518, #fde97a); transition: width 0.8s ease; }
.pred-note { font-size: 0.8rem; color: rgba(255,255,255,0.6); line-height: 1.65; background: rgba(255,255,255,0.06); border-radius: 10px; padding: 12px 14px; border: 1px solid rgba(255,255,255,0.08); }
.pred-note strong { color: rgba(255,255,255,0.9); }

/* CONFLICTS CARD */
.conflict-list { display: flex; flex-direction: column; gap: 10px; padding: 16px 22px 18px; }

.alert-item {
  display: flex; align-items: flex-start; gap: 13px; padding: 14px 16px;
  border-radius: 11px; border: 1.5px solid;
}
.alert-danger { background: #fff8f8; border-color: #fecaca; }
.alert-info   { background: #f0f9ff; border-color: var(--blue-border); }
.alert-warn   { background: var(--orange-bg); border-color: var(--orange-border); }

.alert-icon-box { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.ai-danger { background: var(--red-bg); color: var(--red); }
.ai-info   { background: var(--blue-bg); color: var(--blue-text); }
.ai-warn   { background: var(--orange-bg); color: var(--orange-text); }

.alert-body { flex: 1; }
.alert-title { font-size: 0.86rem; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.alert-desc  { font-size: 0.78rem; color: var(--text-secondary); line-height: 1.5; }
.alert-btns  { display: flex; gap: 6px; margin-top: 9px; flex-wrap: wrap; }
.btn-resolve {
  padding: 5px 14px; border-radius: 7px; border: none; cursor: pointer;
  font-size: 0.76rem; font-weight: 700; font-family: 'Inter', sans-serif;
  background: var(--red); color: #fff; transition: all 0.18s;
}
.btn-resolve:hover { background: #b91c1c; }
.btn-ignore {
  padding: 5px 14px; border-radius: 7px; cursor: pointer;
  font-size: 0.76rem; font-weight: 600; font-family: 'Inter', sans-serif;
  background: none; color: var(--text-secondary); border: 1.5px solid var(--border); transition: all 0.18s;
}
.btn-ignore:hover { border-color: #93c5fd; color: var(--text); }
.btn-view {
  padding: 5px 14px; border-radius: 7px; cursor: pointer;
  font-size: 0.76rem; font-weight: 600; font-family: 'Inter', sans-serif;
  background: var(--blue-bg); color: var(--blue-text); border: 1.5px solid var(--blue-border); transition: all 0.18s;
}
.btn-view:hover { background: #bfdbfe; }

/* AI INSIGHT CARD */
.insight-card {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-radius: var(--radius); border: 1.5px solid #86efac;
  padding: 18px 22px;
  display: flex; align-items: flex-start; gap: 14px;
}
.insight-icon { width: 40px; height: 40px; border-radius: 11px; background: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #fff; flex-shrink: 0; }
.insight-title { font-size: 0.88rem; font-weight: 700; color: #14532d; margin-bottom: 4px; }
.insight-text  { font-size: 0.8rem; color: #15803d; line-height: 1.65; }
.insight-action { display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; font-size: 0.78rem; font-weight: 700; color: #16a34a; background: rgba(22,163,74,0.1); border: 1px solid rgba(22,163,74,0.25); padding: 5px 12px; border-radius: 7px; cursor: pointer; transition: all 0.18s; border: none; font-family: 'Inter', sans-serif; }
.insight-action:hover { background: rgba(22,163,74,0.18); }

/* ANIMATIONS */
@keyframes fadeIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

/* RESPONSIVE */
@media (max-width:1200px) { .main-grid { grid-template-columns: 1fr; } .quick-stats { grid-template-columns: repeat(2,1fr); } .content { padding: 24px 20px 40px; } .topbar { padding: 0 20px; } }
@media (max-width:768px)  { :root { --sidebar-w: 0px; } .sidebar { display: none; } .quick-stats { grid-template-columns: 1fr 1fr; gap: 10px; } .topbar-search { width: 200px; } }
</style>
@include('partials.pro-motion')
</head>
<body>

<!-- Faculty dashboard sidebar -->
<div class="sidebar">
  <a href="{{ url('/faculty_dashboard') }}" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Door</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main Menu</span>
  <ul class="sidebar-nav">
    <li><a href="{{ url('/faculty_dashboard') }}" class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-line"></i></span>Dashboard</a></li>
    <li><a href="{{ url('/rooms') }}" class="{{ Request::is('rooms*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-door-open"></i></span>Rooms</a></li>
    <li><a href="{{ url('/faculty-schedule') }}" class="{{ Request::is('faculty-schedule') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clock"></i></span>Schedule</a></li>
    <li><a href="{{ url('/attendance') }}" class="{{ Request::is('attendance*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance</a></li>
  </ul>

  <span class="nav-section-label">Tools</span>
  <ul class="sidebar-nav">
    <li><a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID Verification</a></li>
    <li><a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports</a></li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <div class="user-avatar"><?= htmlspecialchars($facultyInitials) ?></div>
      <div class="user-widget-info">
        <div class="user-widget-name"><?= htmlspecialchars($facultyName) ?></div>
        <div class="user-widget-role"><?= htmlspecialchars($facultyDept) ?></div>
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

<!-- ════════════════════════════════════════════
     MAIN
════════════════════════════════════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search for classrooms, faculty, or subjects...">
    </div>
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
          <form method="POST" action="{{ url('/logout') }}">
            @csrf
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

    <!-- ── HERO ── -->
    <div class="ai-hero">
      <div class="ai-hero-left">
        <div class="ai-hero-icon"><i class="fas fa-robot"></i></div>
        <div>
          <h1 class="ai-hero-title">AI Classroom Recommendations</h1>
          <p class="ai-hero-sub">Find the most suitable room in seconds using real-time occupancy and schedule analysis.</p>
        </div>
      </div>
      <div class="ai-hero-right">
        <div class="ai-live-badge"><span class="ai-live-dot"></span> LIVE ANALYSIS</div>
        <button class="ai-refresh-btn" id="refreshRecommendations" type="button"><i class="fas fa-rotate"></i> Refresh</button>
      </div>
    </div>

    <!-- ── QUICK STATS ── -->
    <div class="quick-stats">
      <div class="qs-card">
        <div class="qs-icon qi-blue"><i class="fas fa-door-open"></i></div>
        <div>
          <div class="qs-val" id="roomsAvailableNow">--</div>
          <div class="qs-label">Rooms Available Now</div>
        </div>
      </div>
      <div class="qs-card">
        <div class="qs-icon qi-green"><i class="fas fa-circle-check"></i></div>
        <div>
          <div class="qs-val" id="recommendationCount">--</div>
          <div class="qs-label">Top Recommendations</div>
        </div>
      </div>
      <div class="qs-card">
        <div class="qs-icon qi-yellow"><i class="fas fa-triangle-exclamation"></i></div>
        <div>
          <div class="qs-val" id="conflictCount">--</div>
          <div class="qs-label">Active Conflicts</div>
        </div>
      </div>
      <div class="qs-card">
        <div class="qs-icon qi-purple"><i class="fas fa-chart-line"></i></div>
        <div>
          <div class="qs-val" id="campusLoad">--</div>
          <div class="qs-label">Campus Load (2–4 PM)</div>
        </div>
      </div>
    </div>

    <!-- ── MAIN GRID ── -->
    <div class="main-grid">

      <!-- LEFT: RECOMMENDATIONS -->
      <div class="card">
        <div class="card-head">
          <div class="card-head-title">
            <div class="card-head-icon qi-blue"><i class="fas fa-star" style="font-size:0.78rem;color:var(--blue-text);"></i></div>
            Top Recommended for Next Class
          </div>
          <span class="card-head-meta" id="recommendationUpdatedAt">Syncing…</span>
        </div>
        <div class="rec-list" id="recommendationList">
          <div class="rec-empty">Loading live recommendations...</div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="right-col">

        <!-- Usage Prediction -->
        <div class="prediction-card">
          <div class="pred-head">
            <div class="pred-title"><i class="fas fa-chart-line"></i> Usage Predictions</div>
            <span class="pred-time-badge">2PM – 4PM</span>
          </div>
          <div class="pred-label">Expected Campus Load by Building</div>
          <div class="pred-bar-wrap">
            <div class="pred-bar-row"><span class="pred-bar-name">IT Building</span><span class="pred-bar-pct">82%</span></div>
            <div class="pred-bar"><div class="pred-bar-fill" style="width:82%"></div></div>
            <div class="pred-bar-row"><span class="pred-bar-name">Main Building</span><span class="pred-bar-pct">64%</span></div>
            <div class="pred-bar"><div class="pred-bar-fill" style="width:64%"></div></div>
            <div class="pred-bar-row"><span class="pred-bar-name">Tech Building</span><span class="pred-bar-pct">47%</span></div>
            <div class="pred-bar" style="margin-bottom:0;"><div class="pred-bar-fill" style="width:47%"></div></div>
          </div>
          <div class="pred-note">
            <strong>⚠ Room shortage predicted</strong> in the IT Building between 1:00 PM and 3:00 PM. AI recommends booking early or switching to the Main Building.
          </div>
        </div>

        <!-- Potential Conflicts -->
        <div class="card">
          <div class="card-head">
            <div class="card-head-title">
              <div class="card-head-icon" style="background:var(--red-bg);"><i class="fas fa-triangle-exclamation" style="font-size:0.78rem;color:var(--red);"></i></div>
              Potential Conflicts Detected
            </div>
            <span style="font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:6px;background:var(--red-bg);color:var(--red);">2 Issues</span>
          </div>
          <div class="conflict-list">

            <div class="alert-item alert-danger">
              <div class="alert-icon-box ai-danger"><i class="fas fa-calendar-times"></i></div>
              <div class="alert-body">
                <div class="alert-title">Double Booking Alert</div>
                <div class="alert-desc">Room 15 has two overlapping schedules on Wednesday at 10:00 AM.</div>
                <div class="alert-btns">
                  <button type="button" class="btn-resolve js-ai-resolve" data-target="schedule"><i class="fas fa-wrench" style="font-size:0.7rem;"></i> Review Schedule</button>
                  <button type="button" class="btn-ignore js-ai-dismiss">Ignore</button>
                </div>
              </div>
            </div>

            <div class="alert-item alert-info">
              <div class="alert-icon-box ai-info"><i class="fas fa-clock"></i></div>
              <div class="alert-body">
                <div class="alert-title">Unused Reservation</div>
                <div class="alert-desc">Room 17 was reserved but remains empty for 30+ minutes.</div>
                <div class="alert-btns">
                  <button type="button" class="btn-view js-ai-view-room" data-room="Room 17"><i class="fas fa-eye" style="font-size:0.7rem;"></i> Review Room</button>
                  <button type="button" class="btn-ignore js-ai-dismiss">Dismiss</button>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- AI Insight -->
        <div class="insight-card">
          <div class="insight-icon"><i class="fas fa-lightbulb"></i></div>
          <div class="insight-body">
            <div class="insight-title">AI Optimization Tip</div>
            <div class="insight-text">Based on this week's patterns, shifting 3 afternoon classes from IT Building to Tech Building would reduce conflicts by 40% and improve overall campus efficiency.</div>
            <button type="button" class="insight-action js-ai-apply"><i class="fas fa-bolt"></i> Review Optimization</button>
          </div>
        </div>

      </div><!-- /right-col -->
    </div><!-- /main-grid -->

  </div><!-- /content -->
</div><!-- /main -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  const recommendationList = document.getElementById('recommendationList');
  const refreshButton = document.getElementById('refreshRecommendations');
  const recommendationCount = document.getElementById('recommendationCount');
  const roomsAvailableNow = document.getElementById('roomsAvailableNow');
  const conflictCount = document.getElementById('conflictCount');
  const campusLoad = document.getElementById('campusLoad');

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, function (character) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character];
    });
  }

  function recommendationCard(room, index) {
    const rankClass = ['rank-1', 'rank-2', 'rank-3'][index] || 'rank-3';
    const cardClass = index === 0 ? 'rec-card top-pick' : 'rec-card';
    const bestTag = index === 0 ? '<span class="rec-best-tag">BEST MATCH</span>' : '';
    const location = [room.building, room.floor].filter(Boolean).join(' · ') || 'Location unavailable';
    const freeHours = Number(room.free_for || 0);
    const freeLabel = freeHours === 1 ? '1 hour' : `${freeHours} hours`;

    return `<div class="${cardClass}">
      <div class="rec-rank ${rankClass}">${index + 1}</div>
      <div class="rec-info">
        <div class="rec-name">${escapeHtml(room.name)} ${bestTag}</div>
        <div class="rec-meta">
          <span class="rec-meta-item"><i class="fas fa-location-dot"></i> ${escapeHtml(location)}</span>
          <span class="rec-meta-item"><i class="fas fa-users"></i> ${escapeHtml(room.capacity)} seats</span>
          <span class="rec-meta-item"><i class="fas fa-clock"></i> Free ${escapeHtml(freeLabel)}</span>
        </div>
        <div class="rec-reason"><i class="fas fa-lightbulb"></i> Available until ${room.free_until ? escapeHtml(new Date(room.free_until).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })) : 'closing'}</div>
      </div>
      <div class="rec-arrow"><i class="fas fa-arrow-right"></i></div>
    </div>`;
  }

  function formatUpdatedAt(value) {
    if (!value) return 'Updated just now';

    try {
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return 'Updated just now';
      }

      return 'Updated ' + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    } catch (error) {
      return 'Updated just now';
    }
  }

  async function loadRecommendations() {
    if (!recommendationList) return;
    refreshButton?.classList.add('is-loading');
    recommendationList.innerHTML = '<div class="rec-empty">Loading live recommendations...</div>';

    try {
      const response = await fetch('/api/ai/recommendations?hours=2', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store',
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error('Recommendation request failed');

      const recommendations = Array.isArray(payload.recommendations) ? payload.recommendations : [];
      recommendationList.innerHTML = recommendations.length
        ? recommendations.map(recommendationCard).join('')
        : '<div class="rec-empty">No available classrooms found for the next two hours.</div>';

      if (document.getElementById('recommendationUpdatedAt')) {
        document.getElementById('recommendationUpdatedAt').textContent = formatUpdatedAt(payload.updated_at);
      }

      if (recommendationCount) recommendationCount.textContent = String(recommendations.length);
      if (roomsAvailableNow) roomsAvailableNow.textContent = String(payload.available_count ?? 0);
      if (conflictCount) conflictCount.textContent = String(payload.conflict_count ?? 0);
      if (campusLoad) campusLoad.textContent = payload.total_rooms ? `${Math.round(((payload.total_rooms - (payload.available_count ?? 0)) / payload.total_rooms) * 100)}%` : '0%';
    } catch (error) {
      recommendationList.innerHTML = '<div class="rec-empty">Unable to load recommendations. Please try again.</div>';
    } finally {
      refreshButton?.classList.remove('is-loading');
    }
  }

  refreshButton?.addEventListener('click', loadRecommendations);
  loadRecommendations();
  setInterval(loadRecommendations, 30000);

  document.querySelectorAll('.js-ai-dismiss').forEach(button => button.addEventListener('click', () => {
    button.closest('.alert-item')?.remove();
  }));
  document.querySelectorAll('.js-ai-resolve').forEach(button => button.addEventListener('click', () => {
    window.location.href = '{{ url('/faculty-schedule') }}?review=conflict';
  }));
  document.querySelectorAll('.js-ai-view-room').forEach(button => button.addEventListener('click', () => {
    window.location.href = '{{ url('/rooms') }}?search=' + encodeURIComponent(button.dataset.room || '');
  }));
  document.querySelector('.js-ai-apply')?.addEventListener('click', event => {
    window.location.href = '{{ url('/faculty-schedule') }}?review=optimization';
  });

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

@include('frontend.faculty.partials.notifications-widget')
</body>
</html>