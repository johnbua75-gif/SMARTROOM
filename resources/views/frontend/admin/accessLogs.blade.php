<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartLocking – Access Logs</title>
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
  --green-text:    #15803d;
  --blue-bg:       #dbeafe;
  --blue-border:   #93c5fd;
  --blue-text:     #1d4ed8;
  --red:           #dc2626;
  --red-bg:        #fee2e2;
  --red-text:      #b91c1c;
  --orange:        #ea580c;
  --orange-bg:     #ffedd5;
  --shadow:        0 1px 3px rgba(0,0,0,0.06);
  --shadow-card:   0 2px 8px rgba(0,0,0,0.07);
  --radius:        14px;
  --radius-sm:     10px;
  --sidebar-w:     240px;
}

body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

/* ══ SIDEBAR ══ */
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
.logo-text .brand-psu {
  font-size: 0.6rem; font-weight: 700; letter-spacing: 0.18em;
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
  border-radius: var(--radius-sm); transition: all 0.22s; position: relative; overflow: hidden;
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

/* TOPBAR */
.topbar {
  background: var(--white); border-bottom: 1px solid var(--border);
  padding: 0 36px; height: 64px;
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  position: sticky; top: 0; z-index: 50;
}
.topbar-search {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg); border: 1.5px solid var(--border);
  border-radius: 24px; padding: 8px 18px; width: 340px; transition: border-color 0.2s;
}
.topbar-search:focus-within { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
.topbar-search i { color: var(--text-light); font-size: 0.88rem; }
.topbar-search input { border: none; outline: none; background: transparent; font-size: 0.9rem; font-family: 'Inter', sans-serif; color: var(--text); width: 100%; }
.topbar-search input::placeholder { color: var(--text-light); }
.topbar-right { display: flex; align-items: center; gap: 18px; }
.notif-btn { position: relative; background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 1.15rem; padding: 7px; border-radius: 9px; transition: background 0.2s; }
.notif-btn:hover { background: var(--bg); }
.notif-badge { position: absolute; top: 5px; right: 5px; width: 7px; height: 7px; background: var(--red); border-radius: 50%; border: 1.5px solid var(--white); }
.topbar-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.topbar-profile-info { text-align: right; }
.topbar-profile-name { font-size: 0.88rem; font-weight: 700; color: var(--text); line-height: 1.2; }
.topbar-profile-role { font-size: 0.76rem; color: var(--text-secondary); }
.topbar-profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); }

/* CONTENT */
.content { padding: 32px 36px 48px; display: flex; flex-direction: column; gap: 24px; }

/* ══ PAGE HEADER ══ */
.page-header { display: flex; align-items: center; gap: 16px; animation: fadeIn 0.35s both; }
.page-header-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: linear-gradient(135deg, #3b5bdb, #4c6ef5);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem; color: #fff; flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(59,91,219,0.3);
}
.page-header h1 { font-size: 1.55rem; font-weight: 800; color: var(--text); letter-spacing: -0.02em; margin-bottom: 2px; }
.page-header p { font-size: 0.86rem; color: var(--text-secondary); }

/* TABS */
.tab-bar {
  display: flex; gap: 2px; background: var(--white);
  border: 1.5px solid var(--border); border-radius: 12px; padding: 5px;
  box-shadow: var(--shadow); width: fit-content;
  animation: fadeIn 0.35s both 0.06s;
}
.tab-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 20px; border-radius: 9px; border: none;
  font-size: 0.86rem; font-weight: 600; font-family: 'Inter', sans-serif;
  cursor: pointer; transition: all 0.2s; color: var(--text-secondary); background: none;
}
.tab-btn i { font-size: 0.82rem; }
.tab-btn:hover { color: var(--text); background: var(--bg); }
.tab-btn.active { background: #3b5bdb; color: #fff; box-shadow: 0 2px 8px rgba(59,91,219,0.25); }

/* ══ STAT ROW ══ */
.stat-row {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
  animation: fadeIn 0.35s both 0.1s;
}
.stat-tile {
  border-radius: 14px; padding: 22px 24px;
  position: relative; overflow: hidden;
  display: flex; flex-direction: column; justify-content: space-between;
  min-height: 110px;
}
.stat-tile-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.stat-tile-icon-wrap { width: 42px; height: 42px; border-radius: 11px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; flex-shrink: 0; }
.stat-tile-label { font-size: 0.76rem; font-weight: 600; color: rgba(255,255,255,0.8); margin-bottom: 6px; letter-spacing: 0.02em; }
.stat-tile-val { font-size: 2.4rem; font-weight: 800; line-height: 1; color: #fff; letter-spacing: -0.03em; }
.tile-blue   { background: linear-gradient(135deg, #3b5bdb 0%, #4c6ef5 100%); }
.tile-green  { background: linear-gradient(135deg, #2f9e44 0%, #40c057 100%); }
.tile-red    { background: linear-gradient(135deg, #c92a2a 0%, #e03131 100%); }
.tile-orange { background: linear-gradient(135deg, #e67700 0%, #fd7e14 100%); }

/* ══ CONTROLS BAR ══ */
.controls-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
  animation: fadeIn 0.35s both 0.14s;
}
.controls-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.filter-select {
  display: flex; align-items: center; gap: 8px;
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 10px; padding: 9px 14px; font-size: 0.84rem; font-weight: 500;
  font-family: 'Inter', sans-serif; color: var(--text); cursor: pointer;
  transition: border-color 0.2s; box-shadow: var(--shadow); outline: none;
  appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
}
.filter-select:focus { border-color: #93c5fd; }
.date-input {
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 10px; padding: 9px 14px; font-size: 0.84rem; font-weight: 500;
  font-family: 'Inter', sans-serif; color: var(--text); outline: none;
  box-shadow: var(--shadow); transition: border-color 0.2s;
}
.date-input:focus { border-color: #93c5fd; }
.search-logs {
  display: flex; align-items: center; gap: 8px;
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 24px; padding: 9px 18px; width: 260px; box-shadow: var(--shadow);
  transition: border-color 0.2s;
}
.search-logs:focus-within { border-color: #93c5fd; }
.search-logs i { color: var(--text-light); font-size: 0.82rem; }
.search-logs input { border: none; outline: none; background: transparent; font-size: 0.86rem; font-family: 'Inter', sans-serif; color: var(--text); width: 100%; }
.search-logs input::placeholder { color: var(--text-light); }
.btn-export {
  display: flex; align-items: center; gap: 8px; padding: 10px 18px;
  background: var(--navy); color: #fff; border: none; border-radius: 10px;
  font-size: 0.84rem; font-weight: 700; font-family: 'Inter', sans-serif;
  cursor: pointer; transition: all 0.18s; box-shadow: var(--shadow);
}
.btn-export:hover { background: #0a1235; transform: translateY(-1px); }

/* ══ TABLE CARD ══ */
.table-card {
  background: var(--white); border-radius: 16px;
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
  overflow: hidden; animation: fadeIn 0.35s both 0.18s;
}
.table-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 24px; border-bottom: 1px solid var(--border);
}
.table-card-header-left h2 { font-size: 1rem; font-weight: 800; color: var(--text); margin-bottom: 2px; }
.table-card-header-left p { font-size: 0.78rem; color: var(--text-secondary); }
.live-badge {
  display: flex; align-items: center; gap: 7px;
  background: #dcfce7; border: 1px solid #86efac;
  border-radius: 100px; padding: 5px 12px;
  font-size: 0.72rem; font-weight: 700; color: #15803d; letter-spacing: 0.04em;
}
.live-dot {
  width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
  animation: pulse 1.4s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}

/* TABLE */
.log-table { width: 100%; border-collapse: collapse; }
.log-table thead tr { background: #f8fafc; border-bottom: 1.5px solid var(--border); }
.log-table thead th {
  padding: 11px 16px; text-align: left;
  font-size: 0.72rem; font-weight: 700; color: var(--text-secondary);
  text-transform: uppercase; letter-spacing: 0.08em; white-space: nowrap;
}
.log-table tbody tr {
  border-bottom: 1px solid var(--border); transition: background 0.15s;
}
.log-table tbody tr:last-child { border-bottom: none; }
.log-table tbody tr:hover { background: #f9fafb; }
.log-table td { padding: 13px 16px; font-size: 0.84rem; vertical-align: middle; }

/* instructor cell */
.instr-cell { display: flex; align-items: center; gap: 10px; }
.instr-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); flex-shrink: 0; }
.instr-name { font-weight: 700; color: var(--text); font-size: 0.85rem; white-space: nowrap; }
.instr-dept { font-size: 0.72rem; color: var(--text-secondary); margin-top: 1px; }

/* room cell */
.room-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--blue-bg); border: 1px solid var(--blue-border);
  border-radius: 8px; padding: 4px 10px;
  font-size: 0.78rem; font-weight: 600; color: var(--blue-text);
}
.room-chip i { font-size: 0.7rem; }

/* rfid cell */
.rfid-mono { font-family: 'Courier New', monospace; font-size: 0.78rem; color: var(--text-secondary); }

/* time cell */
.time-val { font-weight: 600; color: var(--text); font-size: 0.84rem; }
.date-val { font-size: 0.72rem; color: var(--text-secondary); margin-top: 1px; }

/* status badge */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 100px;
  font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; white-space: nowrap;
}
.status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.status-granted { background: var(--green-bg); color: var(--green-text); }
.status-granted::before { background: #22c55e; }
.status-denied  { background: var(--red-bg); color: var(--red-text); }
.status-denied::before  { background: var(--red); }
.status-timeout { background: var(--orange-bg); color: var(--orange); }
.status-timeout::before { background: var(--orange); }

/* method badge */
.method-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 6px;
  font-size: 0.72rem; font-weight: 600;
}
.method-rfid { background: #ede9fe; color: #6d28d9; }
.method-pin  { background: #fef3c7; color: #92400e; }

/* duration */
.duration-val { font-size: 0.82rem; font-weight: 600; color: var(--text); }
.duration-bar-wrap { width: 72px; height: 4px; background: #f3f4f6; border-radius: 4px; margin-top: 4px; overflow: hidden; }
.duration-bar { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #3b5bdb, #4c6ef5); }

/* action btn */
.btn-view-detail {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border-radius: 8px; border: 1.5px solid var(--border);
  background: #fff; color: var(--text-secondary); cursor: pointer; transition: all 0.18s;
  font-size: 0.78rem;
}
.btn-view-detail:hover { border-color: #93c5fd; color: #3b5bdb; background: var(--blue-bg); }

/* PAGINATION */
.table-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 24px; border-top: 1px solid var(--border);
  background: #f8fafc;
}
.table-footer-info { font-size: 0.8rem; color: var(--text-secondary); }
.table-footer-info span { font-weight: 700; color: var(--text); }
.pagination { display: flex; align-items: center; gap: 4px; }
.page-btn {
  width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid var(--border);
  background: #fff; color: var(--text-secondary); cursor: pointer; transition: all 0.18s;
  font-size: 0.82rem; font-weight: 600; font-family: 'Inter', sans-serif;
  display: flex; align-items: center; justify-content: center;
}
.page-btn:hover { border-color: #93c5fd; color: #3b5bdb; }
.page-btn.active { background: #3b5bdb; border-color: #3b5bdb; color: #fff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* DETAIL MODAL */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 200;
  display: flex; align-items: center; justify-content: center; padding: 24px;
  opacity: 0; pointer-events: none; transition: opacity 0.22s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal {
  background: #fff; border-radius: 20px; width: 100%; max-width: 520px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.15); overflow: hidden;
  transform: translateY(14px) scale(0.98); transition: transform 0.22s;
}
.modal-overlay.open .modal { transform: translateY(0) scale(1); }
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; border-bottom: 1px solid var(--border);
}
.modal-header h3 { font-size: 1rem; font-weight: 800; color: var(--text); }
.modal-close {
  width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border);
  background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: var(--text-secondary); font-size: 0.9rem; transition: all 0.18s;
}
.modal-close:hover { background: var(--red-bg); color: var(--red); border-color: #fecaca; }
.modal-body { padding: 24px; }
.modal-section-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); margin-bottom: 12px; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
.detail-item { background: var(--bg); border-radius: 10px; padding: 12px 14px; }
.detail-item-label { font-size: 0.7rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.08em; }
.detail-item-val { font-size: 0.88rem; font-weight: 700; color: var(--text); }
.modal-map {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #60a5fa 100%);
  border-radius: 12px; padding: 18px; position: relative; overflow: hidden; margin-bottom: 0;
}
.modal-map::before { content:''; position:absolute; top:-30px; right:-30px; width:120px; height:120px; border-radius:50%; background:rgba(255,255,255,0.07); }
.modal-map-label { font-size: 0.68rem; font-weight: 600; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; }
.modal-map-room { font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: 2px; }
.modal-map-floor { font-size: 0.78rem; color: rgba(255,255,255,0.6); }
.modal-map-icon { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); font-size: 2rem; color: rgba(255,255,255,0.18); }

/* CHART MINI */
.mini-chart-card {
  background: var(--white); border-radius: 16px;
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
  padding: 20px 24px; animation: fadeIn 0.35s both 0.22s;
}
.mini-chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.mini-chart-header h3 { font-size: 0.95rem; font-weight: 800; color: var(--text); }
.mini-chart-header span { font-size: 0.78rem; color: var(--text-secondary); }
.bar-chart { display: flex; align-items: flex-end; gap: 6px; height: 80px; }
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.bar-val { height: 100%; display: flex; align-items: flex-end; width: 100%; }
.bar-fill { width: 100%; border-radius: 5px 5px 0 0; transition: height 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
.bar-label { font-size: 0.64rem; color: var(--text-light); font-weight: 500; text-align: center; }
.bar-num { font-size: 0.7rem; font-weight: 700; color: var(--text); }

/* two-col layout at bottom */
.bottom-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

/* LEGEND */
.legend { display: flex; gap: 16px; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; color: var(--text-secondary); }
.legend-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

/* room activity */
.room-activity-card {
  background: var(--white); border-radius: 16px;
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
  padding: 20px 24px; animation: fadeIn 0.35s both 0.24s;
}
.room-activity-card h3 { font-size: 0.95rem; font-weight: 800; color: var(--text); margin-bottom: 14px; }
.room-act-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 0; border-bottom: 1px solid #f3f4f6;
}
.room-act-item:last-child { border-bottom: none; }
.room-act-name { font-size: 0.84rem; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.room-act-bar-wrap { height: 5px; background: #f3f4f6; border-radius: 5px; overflow: hidden; margin-top: 4px; width: 120px; }
.room-act-bar { height: 100%; border-radius: 5px; }
.room-act-count { font-size: 0.82rem; font-weight: 700; color: var(--text); white-space: nowrap; }
.room-act-label { font-size: 0.68rem; color: var(--text-secondary); }

@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

@media (max-width: 1100px) { .bottom-row { grid-template-columns: 1fr; } .stat-row { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 768px) { :root { --sidebar-w: 0px; } .sidebar { display:none; } .content { padding: 20px 16px 40px; } .topbar { padding: 0 16px; } }
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>

<!-- SIDEBAR -->
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
    <li><a href="{{ route('admin.classrooms') }}"><span class="nav-icon"><i class="fas fa-school"></i></span>Room Management</a></li>
    <li><a href="{{ url('/admin/schedule') }}"><span class="nav-icon"><i class="fas fa-calendar-days"></i></span>Schedule</a></li>
    <li><a href="{{ url('/admin/users') }}"><span class="nav-icon"><i class="fas fa-users-cog"></i></span>User Management</a></li>
    <li><a href="{{ url('/smartlocking') }}"><span class="nav-icon"><i class="fas fa-lock"></i></span>SmartLocking</a></li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'Admin User') }}&background=0ea5e9&color=fff" alt="{{ auth()->user()?->name ?? 'Admin User' }}">
      <div class="user-widget-info">
        <div class="user-widget-name">{{ auth()->user()?->name ?? 'Admin User' }}</div>
        <div class="user-widget-role">{{ ucfirst(auth()->user()?->role ?? 'admin') }}</div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout-btn"><i class="fas fa-arrow-right-from-bracket"></i> Sign Out</button>
    </form>
  </div>
</div>

<!-- MAIN -->
<div class="main">

  <div class="content">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-icon"><i class="fas fa-shield-halved"></i></div>
      <div>
        <h1>SmartDoor System</h1>
        <p>RFID-based access control with time-validated entry</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
      <button type="button" class="tab-btn" onclick="window.location.href='/admin/smartlocking';">
        <i class="fas fa-credit-card"></i> RFID Access Cards
      </button>
      <button type="button" class="tab-btn active">
        <i class="fas fa-wave-square"></i> Access Logs
      </button>
      <button type="button" class="tab-btn" onclick="window.location.href='/admin/smartlocking?tab=settings';">
        <i class="fas fa-gear"></i> Settings
      </button>
    </div>

    <!-- Stat Row -->
    <div class="stat-row">
      <div class="stat-tile tile-blue">
        <div class="stat-tile-top">
          <div><div class="stat-tile-label">Total Access Today</div></div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-right-to-bracket"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['total_today'] ?? 0 }}</div>
      </div>
      <div class="stat-tile tile-green">
        <div class="stat-tile-top">
          <div><div class="stat-tile-label">Access Granted</div></div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-circle-check"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['granted_today'] ?? 0 }}</div>
      </div>
      <div class="stat-tile tile-red">
        <div class="stat-tile-top">
          <div><div class="stat-tile-label">Access Denied</div></div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-circle-xmark"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['denied_today'] ?? 0 }}</div>
      </div>
      <div class="stat-tile tile-orange">
        <div class="stat-tile-top">
          <div><div class="stat-tile-label">Avg. Duration (min)</div></div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['avg_duration'] ?? 0 }}</div>
      </div>
    </div>

    <!-- Controls Bar -->
    <div class="controls-bar">
      <div class="controls-left">
        <select id="classroomFilter" class="filter-select" aria-label="Filter by door" onchange="filterLogs()">
          <option value="">All Doors</option>
          @foreach ($classrooms as $classroom)
            <option value="{{ $classroom->id }}">Door {{ $classroom->id }} — {{ $classroom->name }}</option>
          @endforeach
        </select>
        <select class="filter-select">
          <option>All Instructors</option>
          <option>Prof. Maria Santos</option>
          <option>Dr. Roberto Cruz</option>
          <option>Prof. Ana Reyes</option>
          <option>Dr. Carlos Mendoza</option>
        </select>
        <select class="filter-select">
          <option>All Statuses</option>
          <option>Granted</option>
          <option>Denied</option>
          <option>Timeout</option>
        </select>
        <input type="date" class="date-input" value="2025-03-29">
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="search-logs">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search logs..." id="logSearch" oninput="filterLogs()">
        </div>
        <select id="exportFormat" class="filter-select" title="Choose file type" aria-label="Choose file type" onchange="syncExportButtonLabel()">
          <option value="csv" selected>CSV (.csv)</option>
          <option value="pdf">PDF (.pdf)</option>
          <option value="pptx" <?= empty($can_export_pptx) ? 'disabled' : '' ?>>PPTX (.pptx)<?= empty($can_export_pptx) ? ' - Unavailable' : '' ?></option>
        </select>
        <button class="btn-export" id="exportBtn" onclick="exportLogs()"><i class="fas fa-file-arrow-down"></i> Export CSV</button>
      </div>
    </div>

    <!-- Log Table -->
    <div class="table-card">
      <div class="table-card-header">
        <div class="table-card-header-left">
          <h2>Access Event Log</h2>
          <p>Showing {{ count($logs ?? []) }} of {{ $stats['total_today'] ?? count($logs ?? []) }} events today</p>
        </div>
        <div class="live-badge"><div class="live-dot"></div> LIVE <span id="accessLogsLastUpdated">Connecting</span></div>
      </div>
      <table class="log-table">
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Room</th>
            <th>RFID Tag</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Duration</th>
            <th>Method</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="logTableBody">
          <!-- Rows inserted by JS -->
        </tbody>
      </table>
      <div class="table-footer">
        <div class="table-footer-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationControls">
          <button class="page-btn" data-page-action="previous" aria-label="Previous page"><i class="fas fa-chevron-left"></i></button>
          <div id="paginationPages"></div>
          <button class="page-btn" data-page-action="next" aria-label="Next page"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="bottom-row">
      <!-- Bar Chart -->
      <div class="mini-chart-card">
        <div class="mini-chart-header">
          <div>
            <h3>Hourly Access Activity</h3>
            <div class="legend" style="margin-top:6px;">
              <span class="legend-item"><span class="legend-dot" style="background:#3b5bdb;"></span>Granted</span>
              <span class="legend-item"><span class="legend-dot" style="background:#e03131;"></span>Denied</span>
            </div>
          </div>
          <span>Today, Mar 29</span>
        </div>
        <div class="bar-chart" id="barChart"></div>
        <div style="display:flex;gap:6px;margin-top:6px;" id="barLabels"></div>
      </div>

      <!-- Room Activity -->
      <div class="room-activity-card">
        <h3>Room Activity</h3>
        @forelse(($roomActivity ?? []) as $room)
          <div class="room-act-item">
            <div>
              <div class="room-act-name">{{ $room['room'] }}</div>
              <div class="room-act-bar-wrap"><div class="room-act-bar" style="background:#3b5bdb;" data-width="{{ $room['width'] }}"></div></div>
            </div>
            <div style="text-align:right;">
              <div class="room-act-count">{{ $room['events'] }}</div>
              <div class="room-act-label">events</div>
            </div>
          </div>
        @empty
          <div class="room-act-item">
            <div>
              <div class="room-act-name">No room activity yet</div>
              <div class="room-act-bar-wrap"><div class="room-act-bar" style="width:0%;background:#3b5bdb;"></div></div>
            </div>
            <div style="text-align:right;">
              <div class="room-act-count">0</div>
              <div class="room-act-label">events</div>
            </div>
          </div>
        @endforelse
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- Detail Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal">
    <div class="modal-header">
      <h3>Access Event Detail</h3>
      <button class="modal-close" onclick="closeModalBtn()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- injected by JS -->
    </div>
  </div>
</div>

<script type="application/json" id="accessLogsJson">{!! json_encode($logs ?? []) !!}</script>
<script type="application/json" id="hoursJson">{!! json_encode($hours ?? []) !!}</script>
<script type="application/json" id="grantedJson">{!! json_encode($grantedSeries ?? []) !!}</script>
<script type="application/json" id="deniedJson">{!! json_encode($deniedSeries ?? []) !!}</script>
<script>
const toastWrap = (() => {
  let wrap = document.getElementById('toastWrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.id = 'toastWrap';
    wrap.style.cssText = 'position:fixed;right:18px;bottom:18px;display:flex;flex-direction:column;gap:8px;z-index:2200;pointer-events:none;';
    document.body.appendChild(wrap);
  }
  return wrap;
})();

function showToast(message, type = 'info') {
  if (!message) return;

  let background = '#eff6ff';
  let border = '#bfdbfe';
  let color = '#1d4ed8';

  if (type === 'error') {
    background = '#fef2f2';
    border = '#fecaca';
    color = '#991b1b';
  }

  if (type === 'success') {
    background = '#ecfdf5';
    border = '#86efac';
    color = '#166534';
  }

  const toast = document.createElement('div');
  toast.textContent = message;
  toast.style.cssText = `min-width:240px;max-width:360px;padding:10px 12px;border-radius:10px;border:1px solid ${border};box-shadow:0 10px 28px rgba(11,22,64,.2);font-size:.8rem;font-weight:600;opacity:0;transform:translateY(10px);transition:opacity .2s,transform .2s;background:${background};color:${color};`;
  toastWrap.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  });

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 220);
  }, 2600);
}

const logs = JSON.parse(document.getElementById('accessLogsJson')?.textContent || '[]');
const canExportPptx = <?= !empty($can_export_pptx) ? 'true' : 'false' ?>;
const exportBaseUrl = '<?= htmlspecialchars(url('/admin/accessLogs/export')) ?>';

let filteredLogs = [...logs];
let currentPage = 1;
const logsPerPage = 10;
const recentLiveLogIds = new Set();

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);
}

function statusBadge(s) {
  const map = { granted:'status-granted', denied:'status-denied', timeout:'status-timeout' };
  const label = { granted:'Granted', denied:'Denied', timeout:'Timeout' };
  return `<span class="status-badge ${map[s]}">${label[s]}</span>`;
}
function methodBadge(m) {
  const cls = m === 'RFID' ? 'method-rfid' : 'method-pin';
  const icon = m === 'RFID' ? 'fa-credit-card' : 'fa-keyboard';
  return `<span class="method-badge ${cls}"><i class="fas ${icon}" style="font-size:0.65rem;"></i> ${m}</span>`;
}
function durBar(d) {
  if (!d) return '<span style="color:var(--text-light);font-size:0.8rem;">—</span>';
  const pct = Math.min(100, Math.round(d / 150 * 100));
  return `<div class="duration-val">${d} min</div><div class="duration-bar-wrap"><div class="duration-bar" style="width:${pct}%;"></div></div>`;
}

function renderTable(data) {
  const tbody = document.getElementById('logTableBody');
  const totalPages = Math.max(1, Math.ceil(data.length / logsPerPage));
  currentPage = Math.min(currentPage, totalPages);
  const pageRows = data.slice((currentPage - 1) * logsPerPage, currentPage * logsPerPage);
  tbody.innerHTML = pageRows.map(l => `
    <tr class="${recentLiveLogIds.has(Number(l.id)) ? 'access-log-live-highlight' : ''}">
      <td style="color:var(--text-light);font-size:0.78rem;font-weight:600;">#${String(l.id).padStart(3,'0')}</td>
      <td>
        <div class="instr-cell">
          ${l.avatar
            ? `<img class="instr-avatar" src="${escapeHtml(l.avatar)}" alt="${escapeHtml(l.name)}">`
            : `<div class="instr-avatar" style="display:flex;align-items:center;justify-content:center;background:#e2e8f0;color:#475569;font-size:0.7rem;font-weight:700;">${escapeHtml((l.name || 'U').split(' ').filter(Boolean).slice(0,2).map((p)=>p[0]).join('').toUpperCase())}</div>`}
          <div>
            <div class="instr-name">${escapeHtml(l.name)}</div>
            <div class="instr-dept">${escapeHtml(l.dept)}</div>
          </div>
        </div>
      </td>
      <td><span class="room-chip"><i class="fas fa-door-open"></i> Door ${escapeHtml(l.classroom_id)} — ${escapeHtml(l.room)}</span></td>
      <td><span class="rfid-mono">${escapeHtml(l.rfid)}</span></td>
      <td><div class="time-val">${escapeHtml(l.timeIn)}</div><div class="date-val">${escapeHtml(l.date || '')}</div></td>
      <td><div class="time-val">${escapeHtml(l.timeOut)}</div>${l.timeOut !== '—' ? `<div class="date-val">${escapeHtml(l.date || '')}</div>` : ''}</td>
      <td>${durBar(l.dur)}</td>
      <td>${methodBadge(l.method)}</td>
      <td>${statusBadge(l.status)}${l.reason ? `<div class="live-log-reason">${escapeHtml(l.reason)}</div>` : ''}</td>
      <td><button class="btn-view-detail" onclick="openModal(${l.id})"><i class="fas fa-eye"></i></button></td>
    </tr>
  `).join('');
  document.getElementById('paginationInfo').innerHTML = data.length ? `Showing <span>${((currentPage - 1) * logsPerPage) + 1}-${Math.min(currentPage * logsPerPage, data.length)}</span> of <span>${data.length}</span> events` : 'No events found';
  document.getElementById('paginationPages').innerHTML = Array.from({ length: totalPages }, (_, index) => `<button class="page-btn ${index + 1 === currentPage ? 'active' : ''}" data-page="${index + 1}">${index + 1}</button>`).join('');
  document.querySelector('[data-page-action="previous"]').disabled = currentPage === 1;
  document.querySelector('[data-page-action="next"]').disabled = currentPage === totalPages;
  document.querySelectorAll('[data-page]').forEach(button => button.addEventListener('click', () => { currentPage = Number(button.dataset.page); renderTable(filteredLogs); }));
}

function filterLogs() {
  const q = document.getElementById('logSearch').value.toLowerCase();
  const classroomId = document.getElementById('classroomFilter').value;
  filteredLogs = logs.filter(l =>
    (!classroomId || String(l.classroom_id) === classroomId) &&
    (
      l.name.toLowerCase().includes(q) ||
      l.room.toLowerCase().includes(q) ||
      String(l.classroom_id).includes(q) ||
      l.rfid.toLowerCase().includes(q) ||
      l.dept.toLowerCase().includes(q)
    )
  );
  currentPage = 1;
  renderTable(filteredLogs);
}

window.handleNewAdminAccessLogs = function (newLogs) {
  newLogs.forEach((entry) => {
    if (logs.some((log) => Number(log.id) === Number(entry.id))) return;
    const accessedAt = entry.accessed_at ? new Date(entry.accessed_at) : new Date();
    const log = {
      id: Number(entry.id),
      classroom_id: Number(entry.classroom_id),
      name: entry.user_name || 'Unknown User',
      dept: 'N/A',
      avatar: '',
      room: entry.classroom_name || 'Unknown Room',
      rfid: entry.rfid_uid || 'N/A',
      timeIn: entry.direction === 'entry' ? accessedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '--',
      timeOut: entry.direction === 'exit' ? accessedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—',
      dur: null,
      method: ['RFID', 'PIN'].includes(entry.method) ? entry.method : 'RFID',
      status: ['granted', 'denied', 'timeout'].includes(entry.result) ? entry.result : 'denied',
      reason: entry.reason || '',
      date: accessedAt.toLocaleDateString([], { month: 'short', day: '2-digit', year: 'numeric' }),
    };
    logs.unshift(log);
    recentLiveLogIds.add(log.id);
    window.setTimeout(() => recentLiveLogIds.delete(log.id), 2200);
  });
  filterLogs();
};

document.querySelector('[data-page-action="previous"]').addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderTable(filteredLogs); } });
document.querySelector('[data-page-action="next"]').addEventListener('click', () => { if (currentPage < Math.ceil(filteredLogs.length / logsPerPage)) { currentPage++; renderTable(filteredLogs); } });

function exportLogs() {
  const formatSelect = document.getElementById('exportFormat');
  const format = (formatSelect?.value || 'csv').toLowerCase();

  if (format === 'pptx' && !canExportPptx) {
    showToast('PPTX export is unavailable on this server. Please enable PHP zip extension.', 'error');
    return;
  }

  window.location.href = `${exportBaseUrl}/${encodeURIComponent(format)}`;
}

function syncExportButtonLabel() {
  const formatSelect = document.getElementById('exportFormat');
  const exportBtn = document.getElementById('exportBtn');
  if (!formatSelect || !exportBtn) return;

  const selected = (formatSelect.value || 'csv').toUpperCase();
  exportBtn.innerHTML = `<i class="fas fa-file-arrow-down"></i> Export ${selected}`;
}

function openModal(id) {
  const l = logs.find(x => x.id === id);
  if (!l) return;
  document.getElementById('modalBody').innerHTML = `
    <div class="modal-map" style="margin-bottom:16px;">
      <div class="modal-map-label">Access Location</div>
      <div class="modal-map-room">${l.room}</div>
      <div class="modal-map-floor">PSU Assingan Campus</div>
      <i class="fas fa-building modal-map-icon"></i>
    </div>
    <p class="modal-section-title">Event Details</p>
    <div class="detail-grid">
      <div class="detail-item"><div class="detail-item-label">Instructor</div><div class="detail-item-val">${l.name}</div></div>
      <div class="detail-item"><div class="detail-item-label">Department</div><div class="detail-item-val">${l.dept}</div></div>
      <div class="detail-item"><div class="detail-item-label">RFID Tag</div><div class="detail-item-val" style="font-family:monospace;font-size:0.78rem;">${l.rfid}</div></div>
      <div class="detail-item"><div class="detail-item-label">Method</div><div class="detail-item-val">${l.method}</div></div>
      <div class="detail-item"><div class="detail-item-label">Time In</div><div class="detail-item-val">${l.timeIn} — ${l.date || ''}</div></div>
      <div class="detail-item"><div class="detail-item-label">Time Out</div><div class="detail-item-val">${l.timeOut === '—' ? 'Still Inside' : l.timeOut + ' — ' + (l.date || '')}</div></div>
      <div class="detail-item"><div class="detail-item-label">Duration</div><div class="detail-item-val">${l.dur ? l.dur + ' minutes' : '—'}</div></div>
      <div class="detail-item"><div class="detail-item-label">Status</div><div class="detail-item-val">${l.status.charAt(0).toUpperCase()+l.status.slice(1)}</div></div>
    </div>
  `;
  document.getElementById('modalOverlay').classList.add('open');
}
function closeModal(e) { if (e.target === document.getElementById('modalOverlay')) closeModalBtn(); }
function closeModalBtn() { document.getElementById('modalOverlay').classList.remove('open'); }

// Bar chart
const hours = JSON.parse(document.getElementById('hoursJson')?.textContent || '[]');
const granted = JSON.parse(document.getElementById('grantedJson')?.textContent || '[]');
const denied = JSON.parse(document.getElementById('deniedJson')?.textContent || '[]');

document.querySelectorAll('.room-act-bar[data-width]').forEach((bar) => {
  const width = Number(bar.getAttribute('data-width') || 0);
  bar.style.width = `${Math.max(0, Math.min(100, width))}%`;
});
const maxVal = Math.max(...granted.map((g,i) => g + denied[i]));

const chart = document.getElementById('barChart');
const labelsEl = document.getElementById('barLabels');
hours.forEach((h, i) => {
  const gPct = Math.round(granted[i] / maxVal * 100);
  const dPct = Math.round(denied[i] / maxVal * 100);
  chart.innerHTML += `
    <div class="bar-col">
      <div class="bar-num" style="font-size:0.62rem;color:var(--text-light);">${granted[i]+denied[i]}</div>
      <div class="bar-val">
        <div style="width:100%;display:flex;flex-direction:column;justify-content:flex-end;height:100%;gap:2px;">
          ${denied[i] ? `<div class="bar-fill" style="height:${dPct}%;background:#e03131;opacity:0.85;"></div>` : ''}
          <div class="bar-fill" style="height:${gPct}%;background:linear-gradient(180deg,#4c6ef5,#3b5bdb);"></div>
        </div>
      </div>
    </div>`;
  labelsEl.innerHTML += `<div style="flex:1;text-align:center;font-size:0.62rem;color:var(--text-light);font-weight:500;">${h}h</div>`;
});

function goToRFID() {
  // In the full Laravel app this would be: window.location.href = '/smartlocking';
  showToast('Navigate to RFID Access Cards tab', 'info');
}

renderTable(logs);
syncExportButtonLabel();
</script>
@include('frontend.admin.partials.live-access-feed', ['sinceId' => $latestAccessLogId ?? 0])
</body>
</html>