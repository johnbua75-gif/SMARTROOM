<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Schedule – SmartDoor</title>
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
  --shadow:        0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-card:   0 2px 8px rgba(0,0,0,0.06);
  --radius:        14px;
  --radius-sm:     10px;
  --sidebar-w:     240px;
}

body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

/* ══════════════════════════════════════════════
   SIDEBAR
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
  padding: 28px 20px 24px 24px;
  text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  margin-bottom: 8px;
}
.logo-mark {
  width: 40px; height: 40px; background: var(--yellow);
  border-radius: 12px;
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
.logo-text .brand-main {
  font-size: 1.05rem; font-weight: 700; color: #fff; letter-spacing: -0.01em;
}
.logo-text .brand-main span { color: var(--yellow); }

.nav-section-label {
  font-size: 0.68rem; font-weight: 700; letter-spacing: 0.12em;
  text-transform: uppercase; color: rgba(255,255,255,0.25);
  padding: 16px 24px 6px;
}
.sidebar-nav { list-style: none; overflow-y: auto; padding: 0 12px; }
.sidebar-nav::-webkit-scrollbar { width: 0; }
.sidebar-nav li { margin-bottom: 2px; }
.sidebar-nav a {
  display: flex; align-items: center; gap: 11px;
  padding: 11px 12px; text-decoration: none;
  color: rgba(255,255,255,0.6); font-size: 0.88rem; font-weight: 500;
  border-radius: var(--radius-sm);
  transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
  position: relative; overflow: hidden;
}
.sidebar-nav a .nav-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; background: rgba(255,255,255,0.05);
  flex-shrink: 0; transition: all 0.22s;
}
.sidebar-nav a:hover { color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.06); }
.sidebar-nav a:hover .nav-icon { background: rgba(255,255,255,0.1); }
.sidebar-nav a.active { background: rgba(245,197,24,0.14); color: var(--yellow); }
.sidebar-nav a.active .nav-icon { background: rgba(245,197,24,0.2); color: var(--yellow); }
.sidebar-nav a.active::before {
  content: ''; position: absolute;
  left: 0; top: 20%; bottom: 20%;
  width: 3px; background: var(--yellow);
  border-radius: 0 2px 2px 0;
}
.sidebar-footer {
  margin-top: auto; padding: 16px 12px 24px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.user-widget {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: var(--radius-sm);
  background: rgba(255,255,255,0.05); margin-bottom: 8px;
}
.user-widget img {
  width: 34px; height: 34px; border-radius: 50%; object-fit: cover;
  border: 2px solid rgba(245,197,24,0.4);
}
.user-widget-info { flex: 1; min-width: 0; }
.user-widget-name {
  font-size: 0.83rem; font-weight: 600; color: #fff;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-widget-role { font-size: 0.73rem; color: rgba(255,255,255,0.4); }
.sidebar-logout-btn {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; color: rgba(255,255,255,0.4);
  font-size: 0.84rem; font-weight: 500; border-radius: var(--radius-sm);
  transition: all 0.22s; width: 100%;
  background: none; border: none; cursor: pointer; font-family: inherit;
  text-decoration: none;
}
.sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,0.08); }

/* ══════════════════════════════════════
   MAIN
══════════════════════════════════════ */
.main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

/* TOPBAR */
.topbar {
  background: var(--white); border-bottom: 1px solid var(--border);
  padding: 0 36px; height: 68px;
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  position: sticky; top: 0; z-index: 50;
}

/* ══════════════════════════════════════
   CONTENT
══════════════════════════════════════ */
.content { padding: 32px 36px 48px; display: flex; flex-direction: column; gap: 24px; }

/* PAGE HEADER */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; animation: fadeIn 0.35s both; }
.page-header-left { display: flex; align-items: center; gap: 14px; }
.page-header-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--yellow-bg); border: 1.5px solid rgba(245,197,24,0.3); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #b45309; }
.page-header-left h1 { font-size: 1.55rem; font-weight: 800; color: var(--text); letter-spacing: -0.02em; margin-bottom: 3px; }
.page-header-left p { font-size: 0.88rem; color: var(--text-secondary); }
.page-header-right { display: flex; align-items: center; gap: 10px; }
.btn-filter {
  display: flex; align-items: center; gap: 7px;
  padding: 10px 16px; border-radius: 11px;
  font-size: 0.84rem; font-weight: 700; letter-spacing: 0.01em; font-family: 'Inter', sans-serif;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  color: #334155;
  border: 1px solid #dbe3ef; cursor: pointer;
  transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}
.btn-filter i { font-size: 0.8rem; }
.btn-filter:hover {
  border-color: #9fb5d8;
  color: #0f172a;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
}
.btn-danger {
  background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
  color: #fff;
  border-color: #b91c1c;
  box-shadow: 0 6px 16px rgba(220, 38, 38, 0.26);
}
.btn-danger:hover {
  background: linear-gradient(180deg, #f87171 0%, #dc2626 100%);
  border-color: #991b1b;
  color: #fff;
}
.btn-filter[disabled] { opacity: 0.6; cursor: not-allowed; }
.btn-add {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 11px;
  font-size: 0.84rem; font-weight: 700; letter-spacing: 0.01em; font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0b1640 0%, #1a2f80 100%);
  color: #fff; border: 1px solid #1e3a8a; cursor: pointer;
  transition: all 0.2s ease; box-shadow: 0 8px 20px rgba(11,22,64,0.28);
}
.btn-add i { font-size: 0.8rem; }
.btn-add:hover {
  background: linear-gradient(135deg, #13245f 0%, #2642aa 100%);
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(11,22,64,0.34);
}

/* FACULTY LIST */
.faculty-section { animation: fadeIn 0.35s both 0.06s; }
.faculty-list { display: flex; flex-direction: column; gap: 10px; }
.faculty-card {
  background: var(--white);
  border-radius: var(--radius);
  border: 1.5px solid var(--border);
  box-shadow: var(--shadow-card);
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
  text-align: left;
  width: 100%;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.faculty-card:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(0,0,0,0.08); border-color: #bfdbfe; }
.faculty-avatar {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: var(--blue-bg);
  border: 1.5px solid var(--blue-border);
  color: var(--navy-mid);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.92rem;
  font-weight: 800;
  flex-shrink: 0;
}
.faculty-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.faculty-name { font-size: 0.92rem; font-weight: 700; color: var(--text); }
.faculty-meta { margin-top: 4px; font-size: 0.78rem; color: var(--text-secondary); display: flex; gap: 12px; flex-wrap: wrap; }
.faculty-action {
  padding: 7px 14px;
  border-radius: 9px;
  border: 1.5px solid var(--border);
  background: var(--white);
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--text-secondary);
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  margin-left: auto;
}
.faculty-action:hover { border-color: #93c5fd; color: var(--text); }
.faculty-delete {
  padding: 7px 14px;
  border-radius: 9px;
  border: 1.5px solid #fecaca;
  background: #fef2f2;
  font-size: 0.78rem;
  font-weight: 700;
  color: #b91c1c;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  margin-left: 8px;
}
.faculty-delete:hover { border-color: #fca5a5; color: #991b1b; }

/* FACULTY MODAL */
.faculty-overlay {
  position: fixed; inset: 0; z-index: 2800;
  display: none; align-items: center; justify-content: center;
  background: rgba(11, 22, 64, 0.46); backdrop-filter: blur(3px); padding: 18px;
}
.faculty-overlay.is-open { display: flex; }
.faculty-dialog {
  width: min(680px, 100%); max-height: calc(100vh - 36px); overflow: auto;
  border-radius: 14px; border: 1.5px solid var(--border);
  background: var(--white); box-shadow: 0 16px 42px rgba(11, 22, 64, 0.22);
}
.faculty-head {
  padding: 14px 16px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.faculty-title { font-size: 0.96rem; font-weight: 800; color: var(--text); }
.faculty-sub { margin-top: 3px; font-size: 0.78rem; color: var(--text-secondary); }
.faculty-close {
  width: 32px; height: 32px; border-radius: 8px;
  border: 1.5px solid var(--border); background: var(--white); color: var(--text-secondary); cursor: pointer;
}
.faculty-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 12px; }
.faculty-summary {
  font-size: 0.74rem; color: var(--text-secondary); display: flex; gap: 8px; flex-wrap: wrap;
}
.faculty-summary span {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 8px; border: 1px solid var(--border); border-radius: 999px; background: #f8fafc;
}
.faculty-class-list { display: flex; flex-direction: column; gap: 10px; }
.faculty-group {
  border: 1px solid var(--border); border-radius: 12px;
  background: #f8fafc; padding: 10px 12px; display: flex; flex-direction: column; gap: 8px;
}
.faculty-group-head { display: flex; flex-direction: column; gap: 4px; }
.faculty-group-instructor { font-size: 0.72rem; font-weight: 800; color: var(--navy-mid); text-transform: uppercase; letter-spacing: 0.08em; }
.faculty-group-subject { font-size: 0.85rem; font-weight: 700; color: var(--text); }
.faculty-group-head { display: flex; flex-direction: column; gap: 4px; }
.faculty-group-instructor { font-size: 0.72rem; font-weight: 800; color: var(--navy-mid); text-transform: uppercase; letter-spacing: 0.08em; }
.faculty-group-subject { font-size: 0.85rem; font-weight: 700; color: var(--text); }
.js-unassign-course-btn {
  padding: 6px 10px;
  background: #b45309 !important;
  color: white !important;
  border: none !important;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.76rem;
  font-weight: 600;
  transition: background 0.2s ease;
  white-space: nowrap;
}
.js-unassign-course-btn:hover {
  background: #92400e !important;
}
.js-unassign-course-btn:active {
  background: #78350f !important;
}
.faculty-group-items { display: flex; flex-direction: column; gap: 8px; }
.faculty-class-item {
  border: 1px solid var(--border); border-radius: 10px;
  background: #fff; padding: 8px 10px;
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.faculty-class-main { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.faculty-class-title { font-size: 0.84rem; font-weight: 700; color: var(--text); }
.faculty-class-meta { font-size: 0.72rem; color: var(--text-secondary); }
.faculty-class-time { font-size: 0.72rem; font-weight: 700; color: var(--navy-mid); white-space: nowrap; }

/* FACULTY DELETE MODAL */
.faculty-delete-overlay {
  position: fixed; inset: 0; z-index: 2850;
  display: none; align-items: center; justify-content: center;
  background: rgba(11, 22, 64, 0.46); backdrop-filter: blur(3px); padding: 18px;
}
.faculty-delete-overlay.is-open { display: flex; }
.faculty-delete-dialog {
  width: min(520px, 100%); max-height: calc(100vh - 36px); overflow: auto;
  border-radius: 14px; border: 1.5px solid var(--border);
  background: var(--white); box-shadow: 0 16px 42px rgba(11, 22, 64, 0.22);
}
.faculty-delete-head {
  padding: 14px 16px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.faculty-delete-title { font-size: 0.96rem; font-weight: 800; color: var(--text); }
.faculty-delete-sub { margin-top: 3px; font-size: 0.78rem; color: var(--text-secondary); }
.faculty-delete-close {
  width: 32px; height: 32px; border-radius: 8px;
  border: 1.5px solid var(--border); background: var(--white); color: var(--text-secondary); cursor: pointer;
}
.faculty-delete-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 10px; }
.faculty-delete-summary { font-size: 0.8rem; color: var(--text-secondary); display: flex; gap: 12px; flex-wrap: wrap; }
.faculty-delete-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-secondary); }
.faculty-delete-select {
  height: 38px; border-radius: 9px; border: 1.5px solid var(--border);
  padding: 0 10px; font-size: 0.84rem; font-family: 'Inter', sans-serif;
  color: var(--text); background: var(--bg);
}
.faculty-delete-error {
  display: none; padding: 10px 12px; border-radius: 9px;
  border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; font-size: 0.78rem;
}
.faculty-delete-error.is-visible { display: block; }
.faculty-delete-actions { display: flex; gap: 8px; align-items: center; }
.faculty-delete-btn {
  height: 38px; border-radius: 9px; border: 1.5px solid var(--border);
  padding: 0 14px; font-size: 0.82rem; font-weight: 700;
  font-family: 'Inter', sans-serif; cursor: pointer;
  background: var(--white); color: var(--text-secondary);
}
.faculty-delete-btn.primary { border-color: #b91c1c; background: #b91c1c; color: #fff; }

/* SCHEDULE CARD */
.schedule-card {
  background: var(--white); border-radius: var(--radius);
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
  display: flex; align-items: center; gap: 0; overflow: hidden;
  transition: transform 0.22s cubic-bezier(0.16,1,0.3,1), box-shadow 0.22s, border-color 0.22s;
  cursor: pointer; text-decoration: none; color: inherit;
}
.schedule-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); border-color: #bfdbfe; }
.schedule-card:active { transform: translateY(0); }
.sc-select-wrap {
  display: none; align-items: center; gap: 7px;
  padding: 6px 10px; border-radius: 8px; border: 1.5px solid var(--border);
  background: #fff; color: var(--text-secondary); font-size: 0.76rem; font-weight: 600; cursor: pointer;
}
.sc-select-wrap input { width: 14px; height: 14px; accent-color: var(--navy-mid); }
.schedule-list.selection-mode .sc-select-wrap { display: inline-flex; }
.schedule-card.is-selected {
  border-color: #60a5fa;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12), var(--shadow-card);
}

/* SCHEDULE HEADER + SEARCH */
.schedule-day-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.schedule-day-label { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-secondary); }
.schedule-day-label::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--navy-mid); }
.schedule-count { font-size: 0.82rem; color: var(--text-secondary); font-weight: 500; }
.schedule-search {
  display: flex; align-items: center; gap: 8px;
  width: 270px; padding: 9px 14px; border-radius: 10px;
  background: var(--white); border: 1.5px solid var(--border);
  box-shadow: var(--shadow);
  transition: border-color 0.18s, box-shadow 0.18s;
}
.schedule-search:focus-within { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
.schedule-search i { color: var(--text-light); font-size: 0.82rem; }
.schedule-search input {
  border: none; outline: none; background: transparent;
  width: 100%; color: var(--text); font-size: 0.84rem; font-family: 'Inter', sans-serif;
}
.schedule-search input::placeholder { color: var(--text-light); }

/* ROOM SCHEDULE ITEMS */
.room-schedule-empty { font-size: 0.78rem; color: var(--text-secondary); }

/* MODAL — CREATE */
.create-overlay {
  position: fixed; inset: 0; z-index: 2700;
  display: none; align-items: center; justify-content: center;
  background: rgba(11, 22, 64, 0.5); backdrop-filter: blur(3px); padding: 18px;
}
.create-overlay.is-open { display: flex; }
.create-dialog {
  width: min(980px, 100%); max-height: calc(100vh - 36px); overflow: auto;
  border-radius: 14px; border: 1.5px solid var(--border);
  background: var(--white); box-shadow: 0 16px 42px rgba(11, 22, 64, 0.26);
}
.create-head {
  padding: 16px 18px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.create-title { font-size: 1rem; font-weight: 800; color: var(--text); }
.create-sub { margin-top: 3px; font-size: 0.8rem; color: var(--text-secondary); }
.create-close {
  width: 34px; height: 34px; border-radius: 8px;
  border: 1.5px solid var(--border); background: var(--white); color: var(--text-secondary); cursor: pointer;
}
.create-body { padding: 16px 18px 18px; display: flex; flex-direction: column; gap: 12px; }
.create-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.create-field { display: flex; flex-direction: column; gap: 5px; }
.create-field.full { grid-column: 1 / -1; }
.create-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-secondary); }
.create-input, .create-select {
  height: 38px; border-radius: 9px; border: 1.5px solid var(--border);
  padding: 0 10px; font-size: 0.84rem; font-family: 'Inter', sans-serif;
  color: var(--text); background: var(--bg);
}
.create-context-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.create-room-summary { border: 1px solid var(--border); border-radius: 10px; background: #f8fafc; padding: 12px; }
.create-room-summary-title { font-size: 0.78rem; font-weight: 800; color: var(--text); margin-bottom: 8px; letter-spacing: 0.02em; }
.room-schedule-list { display: flex; flex-direction: column; gap: 8px; }
.room-schedule-item {
  border: 1px solid var(--border); border-radius: 9px; background: var(--white);
  padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.room-schedule-main { display: flex; flex-direction: column; gap: 3px; }
.room-schedule-subject { font-size: 0.8rem; font-weight: 700; color: var(--text); }
.room-schedule-meta { font-size: 0.75rem; color: var(--text-secondary); }
.room-schedule-time { font-size: 0.74rem; font-weight: 700; color: var(--navy-mid); white-space: nowrap; }
.create-actions { display: flex; gap: 8px; align-items: center; }
.create-btn {
  height: 38px; border-radius: 9px; border: 1.5px solid var(--border);
  padding: 0 14px; font-size: 0.82rem; font-weight: 700;
  font-family: 'Inter', sans-serif; cursor: pointer;
  background: var(--white); color: var(--text-secondary);
}
.create-btn.primary { border-color: var(--navy); background: var(--navy); color: #fff; }

/* MODAL — EDIT */
.edit-overlay {
  position: fixed; inset: 0; z-index: 2750;
  display: none; align-items: center; justify-content: center;
  background: rgba(11, 22, 64, 0.46); backdrop-filter: blur(3px); padding: 18px;
}
.edit-overlay.is-open { display: flex; }
.edit-dialog {
  width: min(560px, 100%); max-height: calc(100vh - 36px); overflow: auto;
  border-radius: 14px; border: 1.5px solid var(--border);
  background: var(--white); box-shadow: 0 16px 42px rgba(11, 22, 64, 0.22);
}
.edit-head {
  padding: 14px 16px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.edit-title { font-size: 0.96rem; font-weight: 800; color: var(--text); }
.edit-sub { margin-top: 3px; font-size: 0.78rem; color: var(--text-secondary); }
.edit-close {
  width: 32px; height: 32px; border-radius: 8px;
  border: 1.5px solid var(--border); background: var(--white); color: var(--text-secondary); cursor: pointer;
}
.edit-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 10px; }
.edit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.edit-field { display: flex; flex-direction: column; gap: 5px; }
.edit-field.full { grid-column: 1 / -1; }
.edit-label { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-secondary); }
.edit-input, .edit-select {
  height: 38px; border-radius: 9px; border: 1.5px solid var(--border);
  padding: 0 10px; font-size: 0.84rem; font-family: 'Inter', sans-serif;
  color: var(--text); background: var(--bg);
}
.edit-error {
  display: none; padding: 10px 12px; border-radius: 9px;
  border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; font-size: 0.78rem;
}
.edit-error.is-visible { display: block; }
.edit-actions { display: flex; gap: 8px; align-items: center; }
.edit-btn {
  height: 38px; border-radius: 9px; border: 1.5px solid var(--border);
  padding: 0 14px; font-size: 0.82rem; font-weight: 700;
  font-family: 'Inter', sans-serif; cursor: pointer;
  background: var(--white); color: var(--text-secondary);
}
.edit-btn.primary { border-color: var(--navy); background: var(--navy); color: #fff; }

/* ANIMATIONS */
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* RESPONSIVE */
@media (max-width:900px) {
  :root { --sidebar-w: 0px; }
  .sidebar { display: none; }
  .content { padding: 20px 16px 40px; }
  .topbar { padding: 0 16px; }
  .import-grid { grid-template-columns: 1fr; }
  .create-grid { grid-template-columns: 1fr; }
  .edit-grid { grid-template-columns: 1fr; }
  .create-context-grid { grid-template-columns: 1fr; }
}
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>

@include('frontend.admin.partials.sidebar')

<!-- ══════════════════════════ MAIN ══════════════════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div style="font-size:0.84rem;color:var(--text-secondary);display:flex;align-items:center;gap:7px;">
      <i class="fas fa-clock" style="font-size:0.78rem;color:var(--text-light);"></i>
      <span>{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-left">
        <div class="page-header-icon"><i class="fas fa-calendar-days"></i></div>
        <div>
          <h1>Class Schedule Management</h1>
          <p>Manage and coordinate classroom schedules across PSU campus.</p>
        </div>
      </div>
      <div class="page-header-right">
        <button class="btn-filter" id="selectDeleteBtn" type="button">
          <i class="fas fa-check-square"></i> Select to Delete
        </button>
        <button class="btn-filter btn-danger" id="deleteSelectedBtn" type="button" style="display:none;" disabled>
          <i class="fas fa-trash"></i> Delete Selected (0)
        </button>
        <button class="btn-add" id="addScheduleBtn" type="button">
          <i class="fas fa-plus"></i> Add Schedule
        </button>
      </div>
    </div>

    @php
      $scheduledFacultySummaries = collect($schedules ?? [])
        ->groupBy(function ($schedule) {
          $instructorId = (int) ($schedule->courseOffering?->instructor_user_id ?? $schedule->course?->instructor_user_id ?? 0);
          return $instructorId > 0 ? $instructorId : 'unassigned';
        })
        ->map(function ($group, $instructorId) {
          $primary = $group->first();
          $instructor = $primary?->courseOffering?->instructor ?? $primary?->course?->instructor;

          if ($instructorId === 'unassigned') {
            return [
              'id' => 0,
              'name' => 'Unassigned Instructor',
              'department' => 'Not Assigned',
              'email' => '',
              'class_count' => (int) $group->count(),
              'course_count' => (int) $group->pluck('course_id')->unique()->count(),
              'classes' => $group
                ->sortBy('start_at')
                ->map(function ($schedule) {
                  return [
                    'course_id' => (int) ($schedule->course_id ?? 0),
                    'offering_id' => (int) ($schedule->course_offering_id ?? 0),
                    'code' => (string) ($schedule->course?->code ?? 'N/A'),
                    'subject' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
                    'section' => (string) ($schedule->courseOffering?->block_section ?? $schedule->block_section ?? ''),
                    'room' => (string) ($schedule->classroom?->name ?? 'Room N/A'),
                    'building' => (string) ($schedule->classroom?->building ?? ''),
                    'status' => (string) ($schedule->status ?? 'scheduled'),
                    'start_at' => optional($schedule->start_at)->toIso8601String(),
                    'end_at' => optional($schedule->end_at)->toIso8601String(),
                  ];
                })
                ->values(),
            ];
          }

          return [
            'id' => (int) $instructorId,
            'name' => (string) ($instructor?->name ?? 'Faculty'),
            'department' => (string) ($instructor?->department ?? 'Faculty'),
            'email' => (string) ($instructor?->email ?? ''),
            'class_count' => (int) $group->count(),
            'course_count' => (int) $group->pluck('course_id')->unique()->count(),
            'classes' => $group
              ->sortBy('start_at')
              ->map(function ($schedule) {
                return [
                  'course_id' => (int) ($schedule->course_id ?? 0),
                  'offering_id' => (int) ($schedule->course_offering_id ?? 0),
                  'code' => (string) ($schedule->course?->code ?? 'N/A'),
                  'subject' => (string) ($schedule->course?->title ?? 'Untitled Subject'),
                  'section' => (string) ($schedule->courseOffering?->block_section ?? $schedule->block_section ?? ''),
                  'room' => (string) ($schedule->classroom?->name ?? 'Room N/A'),
                  'building' => (string) ($schedule->classroom?->building ?? ''),
                  'status' => (string) ($schedule->status ?? 'scheduled'),
                  'start_at' => optional($schedule->start_at)->toIso8601String(),
                  'end_at' => optional($schedule->end_at)->toIso8601String(),
                ];
              })
              ->values(),
          ];
        })
        ->values();

      $facultySummaries = collect($facultyUsers ?? [])->map(function ($faculty) use ($scheduledFacultySummaries) {
        $summary = $scheduledFacultySummaries->firstWhere('id', (int) $faculty->id);
        $courses = collect($faculty->courses ?? []);
        $offerings = collect($faculty->courseOfferings ?? []);
        $scheduledClasses = collect($summary['classes'] ?? []);
        $scheduledCourseIds = $scheduledClasses->pluck('course_id')->unique();
        $unscheduledClasses = $courses
          ->filter(fn($course) => !$scheduledCourseIds->contains((int) $course->id))
          ->map(function ($course) {
            return [
              'course_id' => (int) $course->id,
              'code' => (string) ($course->code ?? 'N/A'),
              'subject' => (string) ($course->title ?? 'Untitled Subject'),
              'room' => 'Not scheduled',
              'building' => '',
              'status' => 'not_scheduled',
              'start_at' => null,
              'end_at' => null,
            ];
          });
        $unscheduledOfferings = $offerings
          ->filter(fn ($offering) => ! $scheduledClasses->contains(fn ($class) => (int) ($class['offering_id'] ?? 0) === (int) $offering->id))
          ->map(function ($offering) {
            return [
              'course_id' => (int) $offering->course_id,
              'offering_id' => (int) $offering->id,
              'code' => (string) ($offering->course?->code ?? 'N/A'),
              'subject' => (string) ($offering->course?->title ?? 'Untitled Subject'),
              'section' => (string) $offering->block_section,
              'room' => 'Not scheduled',
              'building' => '',
              'status' => 'not_scheduled',
              'start_at' => null,
              'end_at' => null,
            ];
          });

        return [
          'id' => (int) $faculty->id,
          'name' => (string) $faculty->name,
          'department' => (string) ($faculty->department ?? 'Faculty'),
          'email' => (string) ($faculty->email ?? ''),
          'class_count' => (int) $scheduledClasses->count(),
          'course_count' => (int) ($courses->count() + $offerings->count()),
          'classes' => $scheduledClasses->concat($unscheduledClasses)->concat($unscheduledOfferings)->values(),
        ];
      })->filter(function ($faculty) {
        return ((int) ($faculty['course_count'] ?? 0) > 0) || ((int) ($faculty['class_count'] ?? 0) > 0);
      })->values();

      $facultySummaries = $facultySummaries->concat(
        $scheduledFacultySummaries->filter(function ($summary) use ($facultyUsers) {
          $isUnassignedOrUnknown = (int) $summary['id'] === 0 || !$facultyUsers->contains('id', $summary['id']);
          $hasAssignments = ((int) ($summary['course_count'] ?? 0) > 0) || ((int) ($summary['class_count'] ?? 0) > 0);

          return $isUnassignedOrUnknown && $hasAssignments;
        })->values()
      )->sortBy(function ($summary) {
        return $summary['id'] === 0 ? 'zzzzz' : $summary['name'];
      })->values();
    @endphp

    <!-- Faculty / Schedule List -->
    <div class="faculty-section">
      <div class="schedule-day-header">
        <div class="schedule-day-label">Faculty / Schedule List</div>
        <div class="schedule-count" id="facultyCountLabel">{{ $facultySummaries->count() }} Faculty</div>
      </div>

      <div class="schedule-search" style="margin-bottom:12px;max-width:360px;">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" id="facultySearchInput" placeholder="Search faculty...">
      </div>

      <div class="faculty-list" id="facultyListContainer">
        @forelse ($facultySummaries as $faculty)
          @php
            $initials = collect(explode(' ', $faculty['name']))
              ->filter()
              ->map(fn($part) => strtoupper(substr($part, 0, 1)))
              ->take(2)
              ->implode('');
          @endphp
          <div class="faculty-card js-faculty-card" role="button" tabindex="0" data-faculty-id="{{ $faculty['id'] }}">
            <div class="faculty-avatar">{{ $initials !== '' ? $initials : 'F' }}</div>
            <div class="faculty-info">
              <div class="faculty-name">{{ $faculty['name'] }}</div>
              <div class="faculty-meta">
                <span>{{ $faculty['department'] }}</span>
                <span>{{ $faculty['course_count'] }} Subject(s)</span>
                <span>{{ $faculty['class_count'] }} Session(s)</span>
              </div>
            </div>
            <span class="faculty-action">View Classes</span>
            @if ($faculty['id'] > 0)
            <button
              type="button"
              class="faculty-delete js-faculty-delete"
              data-faculty-id="{{ $faculty['id'] }}"
              data-faculty-name="{{ $faculty['name'] }}"
              data-faculty-courses="{{ $faculty['course_count'] }}"
              data-faculty-classes="{{ $faculty['class_count'] }}"
            >Delete</button>
            @endif
          </div>
        @empty
          <div class="room-schedule-empty">No faculty schedules found yet.</div>
        @endforelse
      </div>
    </div>

  </div><!-- /.content -->
</div><!-- /.main -->

<!-- ══════════════════════════ EDIT OVERLAY ══════════════════════════ -->
<div class="edit-overlay" id="editScheduleOverlay" aria-hidden="true">
  <div class="edit-dialog" role="dialog" aria-modal="true" aria-labelledby="editScheduleTitle">
    <div class="edit-head">
      <div>
        <div class="edit-title" id="editScheduleTitle">Edit Schedule</div>
        <div class="edit-sub" id="editScheduleSub">Update schedule and optionally extend weekly sessions until semester end.</div>
      </div>
      <button class="edit-close" id="editScheduleClose" type="button" aria-label="Close edit schedule dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>

    <form class="edit-body" id="editScheduleForm">
      <input type="hidden" id="editScheduleId">
      <input type="hidden" id="editScheduleSeriesId">

      <div class="edit-grid">
        <div class="edit-field">
          <label class="edit-label" for="editScheduleClassroom">Room</label>
          <select class="edit-select" id="editScheduleClassroom" required>
            <option value="">Select room...</option>
            @foreach (($classrooms ?? []) as $classroom)
              <option value="{{ $classroom->id }}">{{ $classroom->name }} ({{ $classroom->building }})</option>
            @endforeach
          </select>
        </div>

        <div class="edit-field full">
          <label class="edit-label" for="editScheduleCourse">Subject</label>
          <select class="edit-select" id="editScheduleCourse" required>
            <option value="">Select subject...</option>
            @foreach (collect($courses ?? [])->filter() as $course)
              <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>
            @endforeach
          </select>
        </div>

        <div class="edit-field">
          <label class="edit-label" for="editScheduleEnrolled">Expected Enrolled</label>
          <input class="edit-input" id="editScheduleEnrolled" type="number" min="0" value="0">
        </div>

        <div class="edit-field">
          <label class="edit-label" for="editScheduleStatus">Status</label>
          <select class="edit-select" id="editScheduleStatus">
            <option value="scheduled">Scheduled</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="edit-field">
          <label class="edit-label" for="editScheduleStart">Start</label>
          <input class="edit-input" id="editScheduleStart" type="datetime-local" required>
        </div>

        <div class="edit-field">
          <label class="edit-label" for="editScheduleEnd">End</label>
          <input class="edit-input" id="editScheduleEnd" type="datetime-local" required>
        </div>

        <div class="edit-field">
          <label class="edit-label" for="editScheduleRepeatUntil">Repeat Until (Semester End)</label>
          <input class="edit-input" id="editScheduleRepeatUntil" type="date">
        </div>

        <div class="edit-field full">
          <label class="edit-label" style="text-transform:none;letter-spacing:0;">
            <input type="checkbox" id="editScheduleApplySeries" style="margin-right:6px;">Apply updates to entire series
          </label>
          <div style="font-size:0.72rem;color:var(--text-light);">Series update applies room, subject, status, enrolled, and block section. Times stay per occurrence.</div>
        </div>
      </div>

      <div class="edit-error" id="editScheduleError"></div>

      <div class="edit-actions">
        <button class="edit-btn primary" id="editScheduleSave" type="submit">
          <i class="fas fa-floppy-disk"></i> Save Changes
        </button>
        <button class="edit-btn" id="editScheduleCancel" type="button">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════ FACULTY MODAL ══════════════════════════ -->
<div class="faculty-overlay" id="facultyOverlay" aria-hidden="true">
  <div class="faculty-dialog" role="dialog" aria-modal="true" aria-labelledby="facultyModalTitle">
    <div class="faculty-head">
      <div>
        <div class="faculty-title" id="facultyModalTitle">Faculty Classes</div>
        <div class="faculty-sub" id="facultyModalSub">Select a faculty member to view details.</div>
      </div>
      <button class="faculty-close" id="facultyModalClose" type="button" aria-label="Close faculty classes dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <div class="faculty-body">
      <div class="faculty-summary" id="facultyModalSummary"></div>
      <div class="faculty-class-list" id="facultyModalClassList"></div>
    </div>
  </div>
</div>

<!-- ══════════════════════════ FACULTY DELETE MODAL ══════════════════════════ -->
<div class="faculty-delete-overlay" id="facultyDeleteOverlay" aria-hidden="true">
  <div class="faculty-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="facultyDeleteTitle">
    <div class="faculty-delete-head">
      <div>
        <div class="faculty-delete-title" id="facultyDeleteTitle">Delete Faculty</div>
        <div class="faculty-delete-sub" id="facultyDeleteSub">Choose a replacement instructor before deleting this faculty member.</div>
      </div>
      <button class="faculty-delete-close" id="facultyDeleteClose" type="button" aria-label="Close delete faculty dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <div class="faculty-delete-body">
      <div class="faculty-delete-summary" id="facultyDeleteSummary"></div>
      <div class="faculty-delete-label">Replacement Instructor</div>
      <select class="faculty-delete-select" id="facultyDeleteReplacement">
        <option value="">Select replacement...</option>
      </select>
      <div class="faculty-delete-error" id="facultyDeleteError"></div>
      <div class="faculty-delete-actions">
        <button class="faculty-delete-btn primary" id="facultyDeleteConfirm" type="button">Delete</button>
        <button class="faculty-delete-btn" id="facultyDeleteCancel" type="button">Cancel</button>
      </div>
    </div>
  </div>
</div>

<div class="warning-confirm-overlay" id="warningConfirmOverlay" aria-hidden="true">
  <div class="warning-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="warningConfirmTitle">
    <div class="warning-confirm-head">
      <div class="warning-confirm-icon"><i class="fas fa-triangle-exclamation"></i></div>
      <div>
        <div class="warning-confirm-title" id="warningConfirmTitle">Confirm action</div>
        <div class="warning-confirm-text" id="warningConfirmText">This action cannot be undone.</div>
      </div>
    </div>
    <div class="warning-confirm-actions">
      <button class="warning-confirm-btn primary" id="warningConfirmButton" type="button">Delete</button>
      <button class="warning-confirm-btn" id="warningConfirmCancel" type="button">Cancel</button>
    </div>
  </div>
</div>

<style>
  .warning-confirm-overlay {
    position: fixed;
    top: 18px;
    right: 18px;
    z-index: 5000;
    display: none;
    pointer-events: none;
  }

  .warning-confirm-overlay.is-open {
    display: block;
  }

  .warning-confirm-dialog {
    width: min(320px, calc(100vw - 24px));
    background: #fffaf3;
    border: 1px solid #f3c981;
    border-radius: 12px;
    box-shadow: 0 18px 40px rgba(17, 24, 39, 0.18);
    padding: 14px 16px;
    pointer-events: auto;
    position: relative;
    z-index: 5001;
  }

  .warning-confirm-head {
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  .warning-confirm-icon {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #fff1d6;
    color: #b45309;
    font-size: 0.9rem;
    flex-shrink: 0;
  }

  .warning-confirm-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 2px;
  }

  .warning-confirm-text {
    font-size: 0.8rem;
    color: #6b7280;
    line-height: 1.35;
  }

  .warning-confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 12px;
  }

  .warning-confirm-btn {
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
  }

  .warning-confirm-btn.primary {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
  }
</style>

@php
  $instructors = collect($facultyUsers ?? [])->filter()->unique('id')->sortBy('name')->values();

  if ($instructors->isEmpty()) {
    $instructors = collect($courses ?? [])->pluck('instructor')->filter()->unique('id')->sortBy('name')->values();
  }

  $bsitCatalog = [
    ['code' => 'A_CC 101', 'title' => 'Introduction to Computing', 'units' => 3.0],
    ['code' => 'A_CC 102', 'title' => 'Fundamental of Programming', 'units' => 3.0],
    ['code' => 'A_GE 5', 'title' => 'The Contemporary World', 'units' => 3.0],
    ['code' => 'A_GE 6', 'title' => 'Science, Technology and Society', 'units' => 3.0],
    ['code' => 'A_GE 7', 'title' => 'Mathematics in the Modern World', 'units' => 3.0],
    ['code' => 'A_NSTP 1', 'title' => 'ROTC/CWTS 1', 'units' => 3.0],
    ['code' => 'A_PE1', 'title' => 'PATH-FIT I (Movement Patterns; Exercise based)', 'units' => 2.0],
    ['code' => 'A_CC 103', 'title' => 'Intermediate Programming', 'units' => 3.0],
    ['code' => 'A_CO 101', 'title' => 'Computer Organization', 'units' => 3.0],
    ['code' => 'A_GE 1', 'title' => 'Understanding the Self', 'units' => 3.0],
    ['code' => 'A_GE 2', 'title' => 'Readings in Philippine History', 'units' => 3.0],
    ['code' => 'A_GE 3', 'title' => 'Art Appreciation', 'units' => 3.0],
    ['code' => 'A_GEE 3', 'title' => 'Reading Visual Art', 'units' => 3.0],
    ['code' => 'A_MS 101', 'title' => 'Discrete Mathematics', 'units' => 3.0],
    ['code' => 'A_NSTP 2', 'title' => 'ROTC / CWTS 2', 'units' => 3.0],
    ['code' => 'A_PE2', 'title' => 'PATH-FIT II (Exercise Program based)', 'units' => 2.0],
    ['code' => 'A_CC 104', 'title' => 'Data Structures and Algorithms', 'units' => 3.0],
    ['code' => 'A_GE 4', 'title' => 'Purposive Communication', 'units' => 3.0],
    ['code' => 'A_GEE 1', 'title' => 'Living in the IT Era', 'units' => 3.0],
    ['code' => 'A_GEE 4', 'title' => 'Global Citizenship', 'units' => 3.0],
    ['code' => 'A_HCI 101', 'title' => 'Human Computer Interaction 1', 'units' => 3.0],
    ['code' => 'A_OOP 101', 'title' => 'Object Oriented Programming', 'units' => 3.0],
    ['code' => 'A_PE3', 'title' => 'PATH-FIT III (Dance)', 'units' => 2.0],
    ['code' => 'A_CC 105', 'title' => 'Information Management 1 (Fund. Of Database)', 'units' => 3.0],
    ['code' => 'A_GE_9', 'title' => 'The Life and Works of Rizal', 'units' => 3.0],
    ['code' => 'A_HCI 102', 'title' => 'Human Computer Interaction 2', 'units' => 3.0],
    ['code' => 'A_MT 101', 'title' => 'Multimedia Technologies', 'units' => 3.0],
    ['code' => 'A_NET 101', 'title' => 'Network 1 (Fundamentals of Networking)', 'units' => 3.0],
    ['code' => 'A_PE4', 'title' => 'PATH-FIT IV (Sports)', 'units' => 2.0],
    ['code' => 'A_SAD 101', 'title' => 'System Analysis and Design', 'units' => 3.0],
    ['code' => 'A_WD 101', 'title' => 'Web Development', 'units' => 3.0],
    ['code' => 'A_CC 106', 'title' => 'Application Development and Emerging Technologies', 'units' => 3.0],
    ['code' => 'A_GEE 2', 'title' => 'The Entrepreneurial Mind', 'units' => 3.0],
    ['code' => 'A_IM 102', 'title' => 'Information Management 2 (Advance Database Systems)', 'units' => 3.0],
    ['code' => 'A_MD 101', 'title' => 'Mobile Application Development 1', 'units' => 3.0],
    ['code' => 'A_MS 102', 'title' => 'Quantitative Methods', 'units' => 3.0],
    ['code' => 'A_NET 102', 'title' => 'Networking 2 (Advance Networking)', 'units' => 3.0],
    ['code' => 'A_SP 101', 'title' => 'Social and Professional Issues', 'units' => 3.0],
    ['code' => 'A_WS 101', 'title' => 'Web Systems and Technologies 1', 'units' => 3.0],
    ['code' => 'A_CAP 101', 'title' => 'Capstone Project 1', 'units' => 3.0],
    ['code' => 'A_ELEC1', 'title' => 'Elective 1 (Web Systems and Technologies 2)', 'units' => 3.0],
    ['code' => 'A_ELEC2', 'title' => 'Elective 2 (Mobile Application Development 2)', 'units' => 3.0],
    ['code' => 'A_GE_8', 'title' => 'Ethics', 'units' => 3.0],
    ['code' => 'A_IAS 101', 'title' => 'Information Assurance and Security 1', 'units' => 3.0],
    ['code' => 'A_IC 1', 'title' => 'Personality Development', 'units' => 3.0],
    ['code' => 'A_IPT 101', 'title' => 'Integrative Programming and Technologies', 'units' => 3.0],
    ['code' => 'A_TECH 101', 'title' => 'Technopreneurship', 'units' => 3.0],
    ['code' => 'A_CAP 102', 'title' => 'Capstone Project 2', 'units' => 3.0],
    ['code' => 'A_ELEC3', 'title' => 'Elective 3 (Special Topics on Web and Mobile 1)', 'units' => 3.0],
    ['code' => 'A_ELEC4', 'title' => 'Elective 4 (Special Topics on Web and Mobile 2)', 'units' => 3.0],
    ['code' => 'A_IAS 102', 'title' => 'Information Assurance and Security 2', 'units' => 3.0],
    ['code' => 'A_OS 101', 'title' => 'Operating System Applications', 'units' => 3.0],
    ['code' => 'A_SA 101', 'title' => 'System Administration and Maintenance', 'units' => 3.0],
    ['code' => 'A_SIA 101', 'title' => 'Systems Integration and Architecture', 'units' => 3.0],
  ];

  $bsitCodes = collect($bsitCatalog)->pluck('code')->all();
  $courseLookup = collect($courses ?? [])->filter()->keyBy(fn($c) => (string) ($c->code ?? ''));
  $bsitOptions = collect($bsitCatalog)->map(function (array $item) use ($courseLookup) {
    $code = (string) $item['code'];
    return [
      'code'   => $code,
      'title'  => (string) $item['title'],
      'units'  => (float) $item['units'],
      'course' => $courseLookup->get($code),
    ];
  });
  $remainingCourses = collect($courses ?? [])
    ->filter()
    ->reject(fn($c) => in_array((string) ($c->code ?? ''), $bsitCodes, true))
    ->values();
@endphp

<!-- ══════════════════════════ CREATE OVERLAY ══════════════════════════ -->
<div class="create-overlay" id="createScheduleOverlay" aria-hidden="true">
  <div class="create-dialog" role="dialog" aria-modal="true" aria-labelledby="createScheduleTitle">
    <div class="create-head">
      <div>
        <div class="create-title" id="createScheduleTitle">Add Schedule Per Room</div>
        <div class="create-sub">Create a twice-a-week schedule with different times for the whole semester.</div>
      </div>
      <button class="create-close" id="createScheduleClose" type="button" aria-label="Close create schedule dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>

    <form method="POST" action="{{ route('admin.schedule.store') }}" class="create-body">
      @csrf
      <div class="create-grid">
        <div class="create-field">
          <label class="create-label" for="createInstructorId">Instructor / Faculty</label>
          <select class="create-select" id="createInstructorId" name="instructor_user_id">
            <option value="">All instructors</option>
            @foreach ($instructors as $instructor)
              <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="create-field">
          <label class="create-label" for="createClassroomId">Room</label>
          <select class="create-select" id="createClassroomId" name="classroom_id" required>
            <option value="">Select room...</option>
            @foreach (($classrooms ?? []) as $classroom)
              <option value="{{ $classroom->id }}">{{ $classroom->name }} ({{ $classroom->building }})</option>
            @endforeach
          </select>
        </div>

        <div class="create-field">
          <label class="create-label" for="createBlockSection">Block Section</label>
          <select class="create-select" id="createBlockSection" name="block_section">
            <option value="">Select block...</option>
            <option value="Block A">Block A</option>
            <option value="Block B">Block B</option>
            <option value="Block C">Block C</option>
            <option value="Block D">Block D</option>
          </select>
        </div>

        <div class="create-field">
          <label class="create-label" for="createCourseId">Subject</label>
          <select class="create-select" id="createCourseId" name="course_id" required>
            <option value="">Select subject...</option>
            @foreach ($bsitOptions as $option)
              @php
                $course = $option['course'];
                $selected = $course && (string) old('course_id') === (string) $course->id ? 'selected' : '';
              @endphp
              @if ($course)
                <option value="{{ $course->id }}" data-instructor-id="{{ $course->instructor_user_id }}" {{ $selected }}>
                  {{ $option['code'] }} - {{ $course->title }}
                </option>
              @else
                <option value="" disabled>{{ $option['code'] }} - {{ $option['title'] }} (Not in system)</option>
              @endif
            @endforeach
            @if ($remainingCourses->isNotEmpty())
              <option value="" disabled>— Other Subjects —</option>
              @foreach ($remainingCourses as $course)
                <option value="{{ $course->id }}" data-instructor-id="{{ $course->instructor_user_id }}" {{ (string) old('course_id') === (string) $course->id ? 'selected' : '' }}>
                  {{ $course->code }} - {{ $course->title }}
                </option>
              @endforeach
            @endif
          </select>
        </div>

        <div class="create-field">
          <label class="create-label" for="createEnrolled">Expected Enrolled</label>
          <input class="create-input" id="createEnrolled" name="enrolled" type="number" min="0" value="0">
        </div>

        <div class="create-field">
          <label class="create-label" for="createSemesterStart">Semester Start</label>
          <input class="create-input" id="createSemesterStart" name="semester_start" type="date" required>
        </div>

        <div class="create-field">
          <label class="create-label" for="createSemesterEnd">Semester End</label>
          <input class="create-input" id="createSemesterEnd" name="semester_end" type="date" required>
        </div>

      </div>

      <div class="create-context-grid">
        <div class="create-room-summary">
          <div class="create-room-summary-title">Room Schedule Context</div>
          <div class="room-schedule-list" id="roomScheduleList">
            <div class="room-schedule-empty">Select a room to view its current faculty subjects and time blocks.</div>
          </div>
        </div>

        <div class="create-room-summary">
          <div class="create-room-summary-title">Instructor Schedule Context</div>
          <div class="room-schedule-list" id="instructorScheduleList">
            <div class="room-schedule-empty">Select an instructor to view assigned subjects and schedule time blocks.</div>
          </div>
        </div>
      </div>

      <div class="create-actions">
        <button class="create-btn primary" type="submit">
          <i class="fas fa-floppy-disk"></i> Save Schedule
        </button>
        <button class="create-btn" id="createScheduleCancel" type="button">Cancel</button>
      </div>
    </form>
  </div>
</div>

@php
  $roomSchedulesData = collect($schedules ?? [])->map(function ($schedule) {
    return [
      'id'                  => (int) $schedule->id,
      'classroom_id'        => $schedule->classroom_id,
      'classroom_name'      => (string) ($schedule->classroom?->name ?? 'Unknown Room'),
      'classroom_building'  => (string) ($schedule->classroom?->building ?? ''),
      'instructor_user_id'  => $schedule->courseOffering?->instructor_user_id ?? $schedule->course?->instructor_user_id,
      'course_offering_id'  => $schedule->course_offering_id,
      'block_section'       => (string) ($schedule->courseOffering?->block_section ?? $schedule->block_section ?? ''),
      'course_code'         => (string) ($schedule->course?->code ?? 'N/A'),
      'subject'             => (string) ($schedule->course?->title ?? 'Untitled Subject'),
      'faculty'             => (string) (($schedule->courseOffering?->instructor ?? $schedule->course?->instructor)?->name ?? 'Unassigned Faculty'),
      'start_at'            => optional($schedule->start_at)->toIso8601String(),
      'end_at'              => optional($schedule->end_at)->toIso8601String(),
      'status'              => (string) ($schedule->status ?? 'scheduled'),
    ];
  })->values();
@endphp

<script type="application/json" id="roomSchedulesData">@json($roomSchedulesData)</script>
<script type="application/json" id="facultyScheduleData">@json($facultySummaries)</script>
<script type="application/json" id="facultyUsersData">@json($facultyUsers ?? [])</script>

<script>
/* ── TOAST ── */
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

const warningConfirmOverlay = document.getElementById('warningConfirmOverlay');
const warningConfirmTitle = document.getElementById('warningConfirmTitle');
const warningConfirmText = document.getElementById('warningConfirmText');
const warningConfirmButton = document.getElementById('warningConfirmButton');
const warningConfirmCancel = document.getElementById('warningConfirmCancel');
let pendingWarningAction = null;

function openWarningConfirm({ title = 'Confirm action', message = 'This action cannot be undone.', confirmText = 'Confirm', onConfirm }) {
  if (!warningConfirmOverlay || !warningConfirmTitle || !warningConfirmText || !warningConfirmButton) return false;
  warningConfirmTitle.textContent = title;
  warningConfirmText.textContent = message;
  warningConfirmButton.textContent = confirmText;
  pendingWarningAction = typeof onConfirm === 'function' ? onConfirm : null;
  warningConfirmOverlay.classList.add('is-open');
  warningConfirmOverlay.setAttribute('aria-hidden', 'false');
  return true;
}

function closeWarningConfirm() {
  if (!warningConfirmOverlay) return;
  warningConfirmOverlay.classList.remove('is-open');
  warningConfirmOverlay.setAttribute('aria-hidden', 'true');
  pendingWarningAction = null;
}

function showToast(message, type = 'info') {
  if (!message) return;
  const colors = {
    error:   { bg: '#fef2f2', border: '#fecaca', color: '#991b1b' },
    success: { bg: '#ecfdf5', border: '#86efac', color: '#166534' },
    info:    { bg: '#eff6ff', border: '#bfdbfe', color: '#1d4ed8' },
  };
  const { bg, border, color } = colors[type] || colors.info;
  const toast = document.createElement('div');
  toast.textContent = message;
  toast.style.cssText = `min-width:240px;max-width:360px;padding:10px 12px;border-radius:10px;border:1px solid ${border};box-shadow:0 10px 28px rgba(11,22,64,.2);font-size:.8rem;font-weight:600;opacity:0;transform:translateY(10px);transition:opacity .2s,transform .2s;background:${bg};color:${color};`;
  toastWrap.appendChild(toast);
  requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
  setTimeout(() => {
    toast.style.opacity = '0'; toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 220);
  }, 2600);
}

/* ── DATA ── */
const roomSchedulesData    = JSON.parse(document.getElementById('roomSchedulesData')?.textContent || '[]');
const facultyScheduleData  = JSON.parse(document.getElementById('facultyScheduleData')?.textContent || '[]');
const facultyUsersData     = JSON.parse(document.getElementById('facultyUsersData')?.textContent || '[]');

/* ── ELEMENT REFS ── */
const createOverlay              = document.getElementById('createScheduleOverlay');
const editOverlay                = document.getElementById('editScheduleOverlay');
const createCloseBtn             = document.getElementById('createScheduleClose');
const createCancelBtn            = document.getElementById('createScheduleCancel');
const editCloseBtn               = document.getElementById('editScheduleClose');
const editCancelBtn              = document.getElementById('editScheduleCancel');
const editForm                   = document.getElementById('editScheduleForm');
const editScheduleIdInput        = document.getElementById('editScheduleId');
const editScheduleSeriesIdInput  = document.getElementById('editScheduleSeriesId');
const editScheduleClassroomInput = document.getElementById('editScheduleClassroom');
const editScheduleCourseInput    = document.getElementById('editScheduleCourse');
const editScheduleEnrolledInput  = document.getElementById('editScheduleEnrolled');
const editScheduleStatusInput    = document.getElementById('editScheduleStatus');
const editScheduleApplySeriesInput = document.getElementById('editScheduleApplySeries');
const editScheduleStartInput     = document.getElementById('editScheduleStart');
const editScheduleEndInput       = document.getElementById('editScheduleEnd');
const editScheduleRepeatUntilInput = document.getElementById('editScheduleRepeatUntil');
const editScheduleErrorBox       = document.getElementById('editScheduleError');
const editScheduleSub            = document.getElementById('editScheduleSub');
const editScheduleSaveBtn        = document.getElementById('editScheduleSave');
const createInstructorSelect     = document.getElementById('createInstructorId');
const createClassroomSelect      = document.getElementById('createClassroomId');
const createCourseSelect         = document.getElementById('createCourseId');
const createSemesterStartInput   = document.getElementById('createSemesterStart');
const createSemesterEndInput     = document.getElementById('createSemesterEnd');
const createScheduleForm         = document.querySelector('form[action="{{ route("admin.schedule.store") }}"]');
const roomScheduleList           = document.getElementById('roomScheduleList');
const instructorScheduleList     = document.getElementById('instructorScheduleList');

function updateCreateCourseOptionsForYear(yearLevel) {
  if (!createCourseSelect) return;
  
  // Simply enable the select and show all options
  const options = Array.from(createCourseSelect.options);
  options.forEach((option, index) => {
    if (index === 0) {
      option.hidden = false;
      option.disabled = false;
      return;
    }
    // Show all options except empty-value disabled ones
    if (option.value === '') {
      option.hidden = true;  // Hide placeholder options with no value
    } else {
      option.hidden = false;
      option.disabled = false;
    }
  });
  
  // Enable the select so it can be submitted
  createCourseSelect.disabled = false;
}

function resetCreateCourseSelect() {
  if (!createCourseSelect) return;
  const options = Array.from(createCourseSelect.options);
  options.forEach((option, index) => {
    if (index === 0) {
      option.textContent = 'Select subject...';
      option.hidden = false;
      option.disabled = false;
      return;
    }
    option.hidden = option.value === '';
    option.disabled = false;
  });
  createCourseSelect.disabled = false;
  createCourseSelect.selectedIndex = 0;
}
const facultyOverlay             = document.getElementById('facultyOverlay');
const facultyModalClose          = document.getElementById('facultyModalClose');
const facultyModalTitle          = document.getElementById('facultyModalTitle');
const facultyModalSub            = document.getElementById('facultyModalSub');
const facultyModalSummary        = document.getElementById('facultyModalSummary');
const facultyModalClassList      = document.getElementById('facultyModalClassList');
const facultyDeleteOverlay       = document.getElementById('facultyDeleteOverlay');
const facultyDeleteClose         = document.getElementById('facultyDeleteClose');
const facultyDeleteCancel        = document.getElementById('facultyDeleteCancel');
const facultyDeleteConfirm       = document.getElementById('facultyDeleteConfirm');
const facultyDeleteSummary       = document.getElementById('facultyDeleteSummary');
const facultyDeleteSub           = document.getElementById('facultyDeleteSub');
const facultyDeleteReplacement   = document.getElementById('facultyDeleteReplacement');
const facultyDeleteError         = document.getElementById('facultyDeleteError');
const facultySearchInput         = document.getElementById('facultySearchInput');
const facultyCountLabel          = document.getElementById('facultyCountLabel');
const selectDeleteBtn            = document.getElementById('selectDeleteBtn');
const deleteSelectedBtn          = document.getElementById('deleteSelectedBtn');

const previewEndpoint    = "{{ route('admin.schedule.import.preview') }}";
const importEndpoint     = "{{ route('admin.schedule.import.store') }}";
const bulkDeleteEndpoint = "{{ route('admin.schedule.bulk-destroy') }}";
const deleteFacultyEndpoint = "{{ route('admin.users.destroy.reassign', ['user' => '__USER__']) }}";

let selectionModeEnabled = false;
let deleteFacultyTarget  = null;

/* ── FACULTY MODAL ── */
function formatFacultyTime(value) {
  if (!value) return '-';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}
function formatFacultyDay(value) {
  if (!value) return '';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString([], { weekday: 'short' });
}

function openFacultyModal(facultyId) {
  const faculty = facultyScheduleData.find((e) => String(e.id) === String(facultyId));
  if (!faculty || !facultyOverlay) return;

  if (facultyModalTitle) facultyModalTitle.textContent = faculty.name || 'Faculty Classes';
  if (facultyModalSub)   facultyModalSub.textContent   = faculty.department || 'Faculty';

  if (facultyModalSummary) {
    const parts = [
      `<span><i class="fas fa-layer-group"></i> ${faculty.class_count || 0} Session(s)</span>`,
      `<span><i class="fas fa-book"></i> ${faculty.course_count || 0} Subject(s)</span>`,
    ];
    if (faculty.department) parts.unshift(`<span><i class="fas fa-building"></i> ${faculty.department}</span>`);
    if (faculty.email)      parts.push(`<span><i class="fas fa-envelope"></i> ${faculty.email}</span>`);
    facultyModalSummary.innerHTML = parts.join('');
  }

  if (facultyModalClassList) {
    const rows = Array.isArray(faculty.classes) ? faculty.classes : [];
    if (rows.length === 0) {
      facultyModalClassList.innerHTML = '<div class="room-schedule-empty">No classes assigned yet.</div>';
    } else {
      const grouped = rows.reduce((acc, row) => {
        const key = `${row.code}||${row.subject}||${row.section || ''}||${row.offering_id || 0}`;
        if (!acc[key]) acc[key] = { code: row.code || 'N/A', subject: row.subject || 'Untitled', section: row.section || '', items: [] };
        acc[key].items.push(row);
        return acc;
      }, {});

      facultyModalClassList.innerHTML = Object.values(grouped).map((group) => {
        const sessionLabel = group.items.length === 1 ? '1 session' : `${group.items.length} sessions`;
        const courseId = group.items[0]?.course_id || 0;
        const offeringId = group.items[0]?.offering_id || 0;
        const items = group.items.map((row) => {
          const day   = formatFacultyDay(row.start_at);
          const start = formatFacultyTime(row.start_at);
          const end   = formatFacultyTime(row.end_at);
          const time  = day ? `${day} ${start}${end !== '-' ? ` - ${end}` : ''}` : `${start}${end !== '-' ? ` - ${end}` : ''}`;
          const loc   = row.building ? `${row.room}, ${row.building}` : row.room;
          return `<div class="faculty-class-item">
            <div class="faculty-class-main">
              <div class="faculty-class-meta">${loc}</div>
            </div>
            <div class="faculty-class-time">${time}</div>
          </div>`;
        }).join('');

        return `<div class="faculty-group">
          <div class="faculty-group-head">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
              <div style="min-width:0;flex:1;">
                <div class="faculty-group-subject">${group.code} - ${group.subject}${group.section ? ` (${group.section})` : ''}</div>
              </div>
              <button class="js-unassign-course-btn" type="button" data-course-id="${courseId}" data-offering-id="${offeringId}" data-course-code="${group.code}" title="Unassign subject offering from instructor">
                <i class="fas fa-unlink"></i> Unassign
              </button>
            </div>
            <div class="faculty-class-meta">${sessionLabel}</div>
          </div>
          <div class="faculty-group-items">${items}</div>
        </div>`;
      }).join('');
    }
  }

  facultyOverlay.classList.add('is-open');
  facultyOverlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeFacultyModal() {
  if (!facultyOverlay) return;
  facultyOverlay.classList.remove('is-open');
  facultyOverlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

/* ── FACULTY DELETE MODAL ── */
function setFacultyDeleteError(msg) {
  if (!facultyDeleteError) return;
  facultyDeleteError.textContent = msg;
  facultyDeleteError.classList.add('is-visible');
}
function clearFacultyDeleteError() {
  if (!facultyDeleteError) return;
  facultyDeleteError.textContent = '';
  facultyDeleteError.classList.remove('is-visible');
}

function openFacultyDeleteModal(payload) {
  if (!facultyDeleteOverlay || !facultyDeleteReplacement) return;
  deleteFacultyTarget = payload;
  clearFacultyDeleteError();
  if (facultyDeleteSub) facultyDeleteSub.textContent = `Choose a replacement instructor before removing ${payload.name}'s assignments.`;
  if (facultyDeleteSummary) {
    facultyDeleteSummary.innerHTML = [
      `<span><i class="fas fa-user"></i> ${payload.name}</span>`,
      `<span><i class="fas fa-book"></i> ${payload.courses} Subject(s)</span>`,
      `<span><i class="fas fa-layer-group"></i> ${payload.classes} Session(s)</span>`,
    ].join('');
  }
  const options = facultyUsersData
    .filter((u) => String(u.id) !== String(payload.id))
    .map((u) => `<option value="${u.id}">${u.name}${u.email ? ` (${u.email})` : ''}</option>`);
  facultyDeleteReplacement.innerHTML = ['<option value="">Select replacement...</option>', ...options].join('');
  if (options.length === 0) {
    setFacultyDeleteError('Add another faculty account before deleting this instructor.');
    if (facultyDeleteConfirm) facultyDeleteConfirm.disabled = true;
  } else if (facultyDeleteConfirm) {
    facultyDeleteConfirm.disabled = false;
  }
  facultyDeleteOverlay.classList.add('is-open');
  facultyDeleteOverlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeFacultyDeleteModal() {
  if (!facultyDeleteOverlay) return;
  facultyDeleteOverlay.classList.remove('is-open');
  facultyDeleteOverlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  deleteFacultyTarget = null;
  clearFacultyDeleteError();
}

async function confirmFacultyDelete() {
  if (!deleteFacultyTarget || !facultyDeleteReplacement) return;
  const replacementId = facultyDeleteReplacement.value;
  if (!replacementId) { setFacultyDeleteError('Select a replacement instructor first.'); return; }
  clearFacultyDeleteError();
  if (facultyDeleteConfirm) facultyDeleteConfirm.disabled = true;
  try {
    const response = await fetch(deleteFacultyEndpoint.replace('__USER__', deleteFacultyTarget.id), {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
      body: JSON.stringify({ replacement_user_id: Number(replacementId) }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) { setFacultyDeleteError(data?.message || 'Unable to update assignments right now.'); if (facultyDeleteConfirm) facultyDeleteConfirm.disabled = false; return; }
    showToast(data?.message || 'Faculty assignments updated successfully.', 'success');
    window.location.reload();
  } catch { setFacultyDeleteError('Unable to update assignments right now.'); if (facultyDeleteConfirm) facultyDeleteConfirm.disabled = false; }
}

/* ── FACULTY SEARCH ── */
function applyFacultyFilter() {
  const cards = Array.from(document.querySelectorAll('.js-faculty-card'));
  const term  = String(facultySearchInput?.value || '').trim().toLowerCase();
  let count   = 0;
  cards.forEach((card) => {
    const visible = term === '' || card.textContent.toLowerCase().includes(term);
    card.style.display = visible ? '' : 'none';
    if (visible) count++;
  });
  if (facultyCountLabel) facultyCountLabel.textContent = `${count} Faculty`;
}

/* ── SELECTION MODE ── */
function getScheduleCheckboxes() { return Array.from(document.querySelectorAll('.schedule-select-checkbox')); }

function updateSelectionState() {
  const checkboxes = getScheduleCheckboxes();
  const selectedCount = checkboxes.filter((c) => c.checked).length;
  checkboxes.forEach((c) => c.closest('.schedule-card')?.classList.toggle('is-selected', c.checked));
  if (deleteSelectedBtn) {
    deleteSelectedBtn.disabled = selectedCount === 0;
    deleteSelectedBtn.innerHTML = `<i class="fas fa-trash"></i> Delete Selected (${selectedCount})`;
  }
}

function setSelectionMode(enabled) {
  selectionModeEnabled = enabled;
  document.getElementById('scheduleListContainer')?.classList.toggle('selection-mode', enabled);
  if (selectDeleteBtn) selectDeleteBtn.innerHTML = enabled ? '<i class="fas fa-xmark"></i> Cancel Selection' : '<i class="fas fa-check-square"></i> Select to Delete';
  if (deleteSelectedBtn) deleteSelectedBtn.style.display = enabled ? '' : 'none';
  if (!enabled) getScheduleCheckboxes().forEach((c) => { c.checked = false; });
  updateSelectionState();
}

async function deleteSelectedSchedules() {
  const selectedIds = getScheduleCheckboxes()
    .filter((c) => c.checked)
    .flatMap((c) => {
      const card = c.closest('.schedule-card');
      const seriesIds = card?.getAttribute('data-series-ids');
      if (seriesIds) return seriesIds.split(',').map(Number).filter((v) => Number.isInteger(v) && v > 0);
      return [Number(c.value)].filter((v) => Number.isInteger(v) && v > 0);
    });
  if (selectedIds.length === 0) { showToast('Select at least one schedule to delete.', 'error'); return; }

  openWarningConfirm({
    title: 'Delete selected schedules?',
    message: `This will permanently remove ${selectedIds.length} selected schedule(s). This action cannot be undone.`,
    confirmText: 'Delete',
    onConfirm: async () => {
      if (deleteSelectedBtn) deleteSelectedBtn.disabled = true;
      try {
        const response = await fetch(bulkDeleteEndpoint, {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
          body: JSON.stringify({ schedule_ids: selectedIds }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) { showToast(data?.message || 'Failed to delete selected schedules.', 'error'); updateSelectionState(); return; }
        showToast(data?.message || 'Selected schedules deleted successfully.', 'success');
        window.location.reload();
      } catch { showToast('Unable to delete selected schedules right now.', 'error'); updateSelectionState(); }
    },
  });
}

/* ── CONTEXT SCHEDULE HELPERS ── */
function formatRoomTime(value) {
  if (!value) return '-';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString([], { month: 'short', day: '2-digit', hour: 'numeric', minute: '2-digit' });
}

function renderRoomSchedules(classroomId) {
  if (!roomScheduleList) return;
  if (!classroomId) { roomScheduleList.innerHTML = '<div class="room-schedule-empty">Select a room to view its current faculty subjects and time blocks.</div>'; return; }
  const selectedInstructorId = createInstructorSelect?.value || '';
  const rows = roomSchedulesData
    .filter((r) => String(r.classroom_id) === String(classroomId))
    .filter((r) => !selectedInstructorId || String(r.instructor_user_id) === String(selectedInstructorId))
    .sort((a, b) => new Date(a.start_at || 0) - new Date(b.start_at || 0));
  if (rows.length === 0) { roomScheduleList.innerHTML = '<div class="room-schedule-empty">No schedule yet for this room.</div>'; return; }
  roomScheduleList.innerHTML = rows.map((r) => `
    <div class="room-schedule-item">
      <div class="room-schedule-main">
        <div class="room-schedule-subject">${r.course_code} - ${r.subject}</div>
        <div class="room-schedule-meta">${r.faculty} • ${r.status}</div>
      </div>
      <div class="room-schedule-time">${formatRoomTime(r.start_at)} - ${formatRoomTime(r.end_at)}</div>
    </div>`).join('');
}

function renderInstructorSchedules(instructorId) {
  if (!instructorScheduleList) return;
  if (!instructorId) { instructorScheduleList.innerHTML = '<div class="room-schedule-empty">Select an instructor to view assigned subjects and schedule time blocks.</div>'; return; }
  const rows = roomSchedulesData
    .filter((r) => String(r.instructor_user_id) === String(instructorId))
    .sort((a, b) => new Date(a.start_at || 0) - new Date(b.start_at || 0));
  if (rows.length === 0) { instructorScheduleList.innerHTML = '<div class="room-schedule-empty">No schedule yet for this instructor.</div>'; return; }
  instructorScheduleList.innerHTML = rows.map((r) => `
    <div class="room-schedule-item">
      <div class="room-schedule-main">
        <div class="room-schedule-subject">${r.course_code} - ${r.subject}</div>
        <div class="room-schedule-meta">${r.classroom_name || 'No room'} • ${r.status}</div>
      </div>
      <div class="room-schedule-time">${formatRoomTime(r.start_at)} - ${formatRoomTime(r.end_at)}</div>
    </div>`).join('');
}

function filterCoursesByInstructor(instructorId) {
  if (!createCourseSelect) return;
  let hasVisibleSelected = false;
  Array.from(createCourseSelect.options).forEach((option, i) => {
    if (i === 0) { option.hidden = false; option.disabled = false; return; }
    const visible = !instructorId || (option.getAttribute('data-instructor-id') || '') === String(instructorId);
    option.hidden = !visible; option.disabled = !visible;
    if (visible && option.selected) hasVisibleSelected = true;
  });
  if (!hasVisibleSelected) createCourseSelect.value = '';
}

function refreshCreateSubjects() {
  if (!createCourseSelect) return;

  updateCreateCourseOptionsForYear('');
}

/* ── DATE HELPERS ── */
function toLocalDateInputValue(date) {
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}
function getSemesterEndDate(ref) {
  return (ref.getMonth() + 1) <= 5 ? new Date(ref.getFullYear(), 4, 31) : new Date(ref.getFullYear(), 11, 31);
}
function toDateTimeLocalValue(value) {
  if (!value) return '';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return '';
  return new Date(parsed.getTime() - parsed.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

/* ── CREATE OVERLAY ── */
function openCreateOverlay() {
  if (!createOverlay) return;
  createOverlay.classList.add('is-open');
  createOverlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  if (createSemesterEndInput && !createSemesterEndInput.value) {
    const ref = createSemesterStartInput?.value ? new Date(`${createSemesterStartInput.value}T00:00:00`) : new Date();
    createSemesterEndInput.value = toLocalDateInputValue(getSemesterEndDate(ref));
  }
  refreshCreateSubjects();
  renderInstructorSchedules(createInstructorSelect?.value || '');
  renderRoomSchedules(createClassroomSelect?.value || '');
}
function closeCreateOverlay() {
  if (!createOverlay) return;
  createOverlay.classList.remove('is-open');
  createOverlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

/* ── EDIT OVERLAY ── */
function clearEditError() { if (editScheduleErrorBox) { editScheduleErrorBox.textContent = ''; editScheduleErrorBox.classList.remove('is-visible'); } }
function setEditError(msg) { if (editScheduleErrorBox) { editScheduleErrorBox.textContent = msg; editScheduleErrorBox.classList.add('is-visible'); } }

function openEditOverlay(payload) {
  if (!editOverlay) return;
  editScheduleIdInput.value              = payload.id || '';
  if (editScheduleSeriesIdInput)          editScheduleSeriesIdInput.value = payload.seriesId || '';
  editScheduleClassroomInput.value       = payload.classroomId || '';
  editScheduleCourseInput.value          = payload.courseId || '';
  editScheduleEnrolledInput.value        = String(payload.enrolled ?? 0);
  if (editScheduleStatusInput)            editScheduleStatusInput.value = payload.status || 'scheduled';
  if (editScheduleApplySeriesInput)       editScheduleApplySeriesInput.checked = false;
  editScheduleStartInput.value           = toDateTimeLocalValue(payload.startAt);
  editScheduleEndInput.value             = toDateTimeLocalValue(payload.endAt);
  if (editScheduleRepeatUntilInput) {
    const ref = payload.startAt ? new Date(payload.startAt) : new Date();
    editScheduleRepeatUntilInput.value = toLocalDateInputValue(getSemesterEndDate(ref));
  }
  editScheduleSub.textContent = `${payload.subject || 'Schedule'} • ${payload.room || 'Room'}`;
  clearEditError();
  editOverlay.classList.add('is-open');
  editOverlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}
function closeEditOverlay() {
  if (!editOverlay) return;
  editOverlay.classList.remove('is-open');
  editOverlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  clearEditError();
}

/* ── EVENT LISTENERS ── */
document.getElementById('addScheduleBtn')?.addEventListener('click', (e) => { e.preventDefault(); openCreateOverlay(); });

// Form submission validation
createScheduleForm?.addEventListener('submit', (e) => {
  const courseId = createCourseSelect?.value;
  const classroomId = createClassroomSelect?.value;
  const semesterStart = createSemesterStartInput?.value;
  const semesterEnd = createSemesterEndInput?.value;
  const errors = [];
  if (!classroomId) errors.push('Please select a room');
  if (!courseId) errors.push('Please select a subject');
  if (!semesterStart) errors.push('Please enter semester start date');
  if (!semesterEnd) errors.push('Please enter semester end date');

  if (errors.length > 0) {
    e.preventDefault();
    alert('Please complete all required fields:\n\n' + errors.join('\n'));
    return false;
  }

  // Ensure course select is enabled before form submission
  if (createCourseSelect && createCourseSelect.disabled) {
    createCourseSelect.disabled = false;
  }
});

createCloseBtn?.addEventListener('click', closeCreateOverlay);
createCancelBtn?.addEventListener('click', closeCreateOverlay);
createInstructorSelect?.addEventListener('change', (e) => {
  refreshCreateSubjects();
  renderInstructorSchedules(e.target.value);
  renderRoomSchedules(createClassroomSelect?.value || '');
});
createClassroomSelect?.addEventListener('change', (e) => renderRoomSchedules(e.target.value));

createSemesterStartInput?.addEventListener('change', () => {
  if (!createSemesterEndInput || !createSemesterStartInput?.value) return;
  const start = new Date(`${createSemesterStartInput.value}T00:00:00`);
  if (Number.isNaN(start.getTime())) return;
  createSemesterEndInput.min = toLocalDateInputValue(start);
  const current = createSemesterEndInput.value ? new Date(`${createSemesterEndInput.value}T00:00:00`) : null;
  if (!current || current < start) createSemesterEndInput.value = toLocalDateInputValue(getSemesterEndDate(start));
});

editScheduleStartInput?.addEventListener('change', () => {
  if (!editScheduleRepeatUntilInput || !editScheduleStartInput?.value) return;
  const start = new Date(editScheduleStartInput.value);
  if (Number.isNaN(start.getTime())) return;
  const min = new Date(start.getFullYear(), start.getMonth(), start.getDate());
  editScheduleRepeatUntilInput.min = toLocalDateInputValue(min);
  const current = editScheduleRepeatUntilInput.value ? new Date(`${editScheduleRepeatUntilInput.value}T00:00:00`) : null;
  if (!current || current < min) editScheduleRepeatUntilInput.value = toLocalDateInputValue(getSemesterEndDate(start));
});

createOverlay?.addEventListener('click', (e) => { if (e.target === createOverlay) closeCreateOverlay(); });
editOverlay?.addEventListener('click', (e) => { if (e.target === editOverlay) closeEditOverlay(); });
facultyOverlay?.addEventListener('click', (e) => { if (e.target === facultyOverlay) closeFacultyModal(); });
facultyDeleteOverlay?.addEventListener('click', (e) => { if (e.target === facultyDeleteOverlay) closeFacultyDeleteModal(); });

editCloseBtn?.addEventListener('click', closeEditOverlay);
editCancelBtn?.addEventListener('click', closeEditOverlay);
facultyModalClose?.addEventListener('click', (e) => { e.preventDefault(); closeFacultyModal(); });
facultyDeleteClose?.addEventListener('click', (e) => { e.preventDefault(); closeFacultyDeleteModal(); });
facultyDeleteCancel?.addEventListener('click', (e) => { e.preventDefault(); closeFacultyDeleteModal(); });
facultyDeleteConfirm?.addEventListener('click', (e) => {
  e.preventDefault();
  const replacementId = facultyDeleteReplacement?.value;
  if (!replacementId) {
    setFacultyDeleteError('Select a replacement instructor first.');
    return;
  }

  openWarningConfirm({
    title: 'Delete faculty assignment?',
    message: `This will reassign ${deleteFacultyTarget?.name || 'this instructor'}'s assigned subjects to the selected replacement instructor and keep the user account in the system.`,
    confirmText: 'Delete',
    onConfirm: () => confirmFacultyDelete(),
  });
});

selectDeleteBtn?.addEventListener('click', (e) => { e.preventDefault(); setSelectionMode(!selectionModeEnabled); });
deleteSelectedBtn?.addEventListener('click', (e) => { e.preventDefault(); deleteSelectedSchedules(); });

getScheduleCheckboxes().forEach((c) => {
  c.addEventListener('click', (e) => e.stopPropagation());
  c.addEventListener('change', (e) => { e.stopPropagation(); updateSelectionState(); });
});

document.addEventListener('click', (e) => {
  const btn = e.target instanceof HTMLElement ? e.target.closest('.js-unassign-course-btn') : null;
  if (!btn) return;

  const courseId = btn.getAttribute('data-course-id');
  const offeringId = btn.getAttribute('data-offering-id');
  const courseCode = btn.getAttribute('data-course-code') || 'this subject';
  if (!courseId) return;

  openWarningConfirm({
    title: 'Unassign subject?',
    message: `This will remove "${courseCode}" from this instructor.`,
    confirmText: 'Unassign',
    onConfirm: () => offeringId ? unassignCourseOffering(offeringId) : unassignCourse(courseId),
  });
});

warningConfirmButton?.addEventListener('click', async () => {
  const action = pendingWarningAction;
  closeWarningConfirm();
  if (typeof action === 'function') {
    await action();
  }
});

warningConfirmCancel?.addEventListener('click', () => {
  closeWarningConfirm();
});

warningConfirmOverlay?.addEventListener('click', (e) => {
  if (e.target === warningConfirmOverlay) closeWarningConfirm();
});

async function unassignCourse(courseId) {
  try {
    const response = await fetch(`/admin/courses/${courseId}/unassign`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
      },
      body: JSON.stringify({}),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      alert(data?.message || 'Failed to unassign subject.');
      return;
    }

    alert('Subject unassigned successfully.');
    window.location.reload();
  } catch {
    alert('Unable to unassign subject right now.');
  }
}

async function unassignCourseOffering(offeringId) {
  try {
    const response = await fetch(`/admin/course-offerings/${offeringId}/unassign`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
      },
      body: JSON.stringify({}),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      alert(data?.message || 'Failed to unassign subject offering.');
      return;
    }

    alert('Subject offering unassigned successfully.');
    window.location.reload();
  } catch {
    alert('Unable to unassign subject offering right now.');
  }
}

document.querySelectorAll('.schedule-card').forEach((card) => {
  card.addEventListener('click', (e) => {
    if (!selectionModeEnabled) return;
    if (e.target instanceof HTMLElement && e.target.closest('.js-edit-schedule')) return;
    e.preventDefault();
    const checkbox = card.querySelector('.schedule-select-checkbox');
    if (!(checkbox instanceof HTMLInputElement)) return;
    checkbox.checked = !checkbox.checked;
    updateSelectionState();
  });
});

document.querySelectorAll('.js-faculty-card').forEach((card) => {
  card.addEventListener('click', (e) => {
    if (e.target instanceof HTMLElement && e.target.closest('.js-faculty-delete')) return;
    const id = card.getAttribute('data-faculty-id');
    if (id) openFacultyModal(id);
  });
  card.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    e.preventDefault();
    const id = card.getAttribute('data-faculty-id');
    if (id) openFacultyModal(id);
  });
});

document.querySelectorAll('.js-faculty-delete').forEach((btn) => {
  btn.addEventListener('click', (e) => {
    e.preventDefault(); e.stopPropagation();
    const id = btn.getAttribute('data-faculty-id');
    if (!id) return;
    openFacultyDeleteModal({
      id,
      name:    btn.getAttribute('data-faculty-name') || 'Faculty',
      courses: Number(btn.getAttribute('data-faculty-courses') || 0),
      classes: Number(btn.getAttribute('data-faculty-classes') || 0),
    });
  });
});

document.querySelectorAll('.js-edit-schedule').forEach((btn) => {
  btn.addEventListener('click', (e) => {
    e.preventDefault(); e.stopPropagation();
    const id = btn.getAttribute('data-schedule-id');
    if (!id) return;
    openEditOverlay({
      id,
      seriesId:    btn.getAttribute('data-schedule-series-id') || '',
      classroomId: btn.getAttribute('data-schedule-classroom-id') || '',
      courseId:    btn.getAttribute('data-schedule-course-id') || '',
      status:      btn.getAttribute('data-schedule-status') || 'scheduled',
      startAt:     btn.getAttribute('data-schedule-start') || '',
      endAt:       btn.getAttribute('data-schedule-end') || '',
      enrolled:    btn.getAttribute('data-schedule-enrolled') || '0',
      subject:     btn.getAttribute('data-schedule-subject') || 'Schedule',
      room:        btn.getAttribute('data-schedule-room') || 'Room',
    });
  });
});

editForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  clearEditError();
  const scheduleId    = editScheduleIdInput?.value;
  if (!scheduleId) { setEditError('Missing schedule id.'); return; }
  const classroomId   = (editScheduleClassroomInput?.value || '').trim();
  const courseId      = (editScheduleCourseInput?.value || '').trim();
  const startAt       = editScheduleStartInput?.value || '';
  const endAt         = editScheduleEndInput?.value || '';
  const repeatUntil   = editScheduleRepeatUntilInput?.value || '';
  const status        = editScheduleStatusInput?.value || 'scheduled';
  const applyToSeries = !!editScheduleApplySeriesInput?.checked;
  const enrolled      = Math.max(0, parseInt(editScheduleEnrolledInput?.value || '0', 10) || 0);
  if (!classroomId || !courseId || !startAt || !endAt) { setEditError('Room, subject, start, and end are required.'); return; }
  if (new Date(endAt) <= new Date(startAt)) { setEditError('End time must be after start time.'); return; }
  editScheduleSaveBtn.disabled = true;
  try {
    const response = await fetch(`/admin/schedule/${scheduleId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
      body: JSON.stringify({ classroom_id: Number(classroomId), course_id: Number(courseId), start_at: startAt, end_at: endAt, repeat_until: repeatUntil || null, status, apply_to_series: applyToSeries, enrolled }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) { const errs = data?.errors ? Object.values(data.errors).flat().join(' ') : ''; setEditError(errs || data?.message || 'Failed to update schedule.'); return; }
    closeEditOverlay();
    window.location.reload();
  } catch { setEditError('Unable to update schedule right now.'); }
  finally { editScheduleSaveBtn.disabled = false; }
});

document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  if (facultyOverlay?.classList.contains('is-open'))       { closeFacultyModal(); return; }
  if (facultyDeleteOverlay?.classList.contains('is-open')) { closeFacultyDeleteModal(); return; }
  if (createOverlay?.classList.contains('is-open'))        { closeCreateOverlay(); return; }
  if (editOverlay?.classList.contains('is-open'))          { closeEditOverlay(); return; }
  if (importOverlay?.classList.contains('is-open'))        { closeImportOverlay(); }
});

facultySearchInput?.addEventListener('input', applyFacultyFilter);
applyFacultyFilter();
</script>

</body>
</html>