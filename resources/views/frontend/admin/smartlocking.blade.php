<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartLocking – SmartDoor</title>
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
  --blue-bg:       #dbeafe;
  --blue-border:   #93c5fd;
  --blue-text:     #1d4ed8;
  --red:           #dc2626;
  --red-bg:        #fee2e2;
  --shadow:        0 1px 3px rgba(0,0,0,0.06);
  --shadow-card:   0 2px 8px rgba(0,0,0,0.07);
  --radius:        14px;
  --radius-sm:     10px;
  --sidebar-w:     240px;
}

body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }

/* ══════════════════════════════════════════════
   SIDEBAR — EXACT COPY FROM CLASSROOMS
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
.ic-avatar--initials {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e2e8f0;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
}
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
  border-radius: 24px; padding: 8px 18px; width: 340px;
  transition: border-color 0.2s;
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

/* ══════════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════════ */
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
.settings-panel { display: none; animation: fadeIn 0.25s both; }
.settings-panel.is-visible { display: block; }
.settings-layout { display: grid; grid-template-columns: 220px 1fr; gap: 20px; align-items: start; }
.settings-nav, .settings-card { background: var(--white); border: 1.5px solid var(--border); border-radius: 14px; box-shadow: var(--shadow-card); }
.settings-nav { padding: 8px; }
.settings-nav-item { width: 100%; display: flex; align-items: center; gap: 10px; border: 0; border-radius: 9px; padding: 11px 12px; color: var(--text-secondary); background: transparent; cursor: pointer; font: 600 0.8rem 'Inter', sans-serif; text-align: left; }
.settings-nav-item i { width: 18px; text-align: center; }
.settings-nav-item:hover, .settings-nav-item.active { color: #3b5bdb; background: #eef2ff; }
.settings-card { padding: 24px; }
.settings-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding-bottom: 18px; border-bottom: 1px solid var(--border); }
.settings-card-header h2 { font-size: 1.1rem; font-weight: 800; margin-bottom: 4px; }
.settings-card-header p { color: var(--text-secondary); font-size: 0.8rem; }
.settings-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: var(--green-bg); color: var(--green); font-size: 0.7rem; font-weight: 700; white-space: nowrap; }
.settings-status i { font-size: 0.45rem; }
.settings-section { padding: 20px 0; border-bottom: 1px solid var(--border); }
.settings-section.is-hidden { display: none; }
.settings-section:last-of-type { border-bottom: 0; }
.settings-section h3 { font-size: 0.88rem; font-weight: 800; margin-bottom: 12px; }
.settings-section > p { color: var(--text-secondary); font-size: 0.76rem; margin-bottom: 14px; }
.settings-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.settings-field { display: grid; gap: 6px; color: var(--text-secondary); font-size: 0.74rem; font-weight: 700; }
.settings-field input, .settings-field select { width: 100%; border: 1.5px solid var(--border); border-radius: 9px; padding: 10px 11px; outline: none; background: #fff; color: var(--text); font: 0.84rem 'Inter', sans-serif; }
.settings-field input:focus, .settings-field select:focus { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
.setting-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; }
.setting-row + .setting-row { border-top: 1px solid #f1f3f7; }
.setting-row strong { display: block; color: var(--text); font-size: 0.82rem; margin-bottom: 3px; }
.setting-row span { color: var(--text-secondary); font-size: 0.74rem; }
.switch { position: relative; display: inline-flex; width: 40px; height: 23px; flex: 0 0 auto; }
.switch input { opacity: 0; width: 0; height: 0; }
.switch-slider { position: absolute; inset: 0; border-radius: 999px; background: #cbd5e1; cursor: pointer; transition: background 0.2s; }
.switch-slider::before { content: ''; position: absolute; width: 17px; height: 17px; left: 3px; top: 3px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s; }
.switch input:checked + .switch-slider { background: #3b5bdb; }
.switch input:checked + .switch-slider::before { transform: translateX(17px); }
.settings-actions { display: flex; justify-content: flex-end; gap: 9px; padding-top: 20px; }
.btn-settings-save { display: inline-flex; align-items: center; gap: 8px; border: 0; border-radius: 9px; padding: 10px 15px; color: #fff; background: #3b5bdb; cursor: pointer; font: 700 0.82rem 'Inter', sans-serif; box-shadow: 0 3px 10px rgba(59,91,219,0.24); }
.btn-settings-save:hover { background: #2f4ac9; }
@media (max-width:768px) { .settings-layout { grid-template-columns: 1fr; } .settings-nav { display: flex; overflow-x: auto; } .settings-nav-item { white-space: nowrap; } .settings-field-grid { grid-template-columns: 1fr; } .settings-card-header { flex-direction: column; } }

/* SECTION HEADER */
.section-header {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
  animation: fadeIn 0.35s both 0.1s;
}
.section-header-left h2 { font-size: 1.2rem; font-weight: 800; color: var(--text); letter-spacing: -0.01em; margin-bottom: 3px; }
.section-header-left p { font-size: 0.84rem; color: var(--text-secondary); }
.search-inline {
  display: flex; align-items: center; gap: 8px;
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 24px; padding: 9px 18px; width: 280px; box-shadow: var(--shadow);
  transition: border-color 0.2s;
}
.search-inline:focus-within { border-color: #93c5fd; }
.search-inline i { color: var(--text-light); font-size: 0.82rem; }
.search-inline input { border: none; outline: none; background: transparent; font-size: 0.86rem; font-family: 'Inter', sans-serif; color: var(--text); width: 100%; }
.search-inline input::placeholder { color: var(--text-light); }
.btn-add-card {
  display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer;
  border-radius: 10px; padding: 10px 15px; background: #3b5bdb; color: #fff;
  font: 700 0.82rem 'Inter', sans-serif; box-shadow: 0 3px 10px rgba(59,91,219,0.24);
  transition: background 0.18s, transform 0.18s;
}
.btn-add-card:hover { background: #2f4ac9; transform: translateY(-1px); }
.modal-backdrop { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(11,22,64,0.5); z-index: 1000; }
.modal-backdrop.is-open { display: flex; }
.card-modal { width: min(520px, 100%); background: var(--white); border-radius: 16px; box-shadow: 0 20px 60px rgba(11,22,64,0.25); overflow: hidden; }
.card-modal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 22px 24px 16px; border-bottom: 1px solid var(--border); }
.card-modal-header h3 { font-size: 1.08rem; font-weight: 800; margin-bottom: 4px; }
.card-modal-header p { color: var(--text-secondary); font-size: 0.78rem; }
.modal-close { border: 0; background: none; color: var(--text-secondary); cursor: pointer; font-size: 1rem; padding: 3px; }
.card-form { display: grid; gap: 14px; padding: 20px 24px 24px; }
.card-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.card-form label { display: grid; gap: 6px; color: var(--text-secondary); font-size: 0.75rem; font-weight: 700; }
.card-form input, .card-form select { width: 100%; border: 1.5px solid var(--border); border-radius: 9px; padding: 10px 11px; outline: none; background: #fff; color: var(--text); font: 0.84rem 'Inter', sans-serif; }
.card-form input:focus, .card-form select:focus { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59,130,246,0.08); }
.card-form-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 4px; }
.btn-modal-cancel { border: 1px solid var(--border); background: #fff; color: var(--text-secondary); border-radius: 9px; padding: 10px 14px; cursor: pointer; font: 700 0.82rem 'Inter', sans-serif; }
.form-errors { display: none; padding: 10px 12px; border: 1px solid #fecaca; border-radius: 9px; background: var(--red-bg); color: #991b1b; font-size: 0.76rem; line-height: 1.45; }
.form-errors.is-visible { display: block; }
@media (max-width:768px) { .section-header { flex-direction: column; } .section-header-left { width: 100%; } .search-inline { width: 100%; } .card-form-grid { grid-template-columns: 1fr; } }

/* STAT CARDS ROW */
.stat-row {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
  animation: fadeIn 0.35s both 0.14s;
}
.stat-tile {
  border-radius: 14px; padding: 22px 24px;
  position: relative; overflow: hidden;
  display: flex; flex-direction: column; justify-content: space-between;
  min-height: 110px; cursor: default;
}
.stat-tile-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
.stat-tile-icon-wrap { width: 42px; height: 42px; border-radius: 11px; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; flex-shrink: 0; }
.stat-tile-label { font-size: 0.76rem; font-weight: 600; color: rgba(255,255,255,0.8); margin-bottom: 6px; letter-spacing: 0.02em; }
.stat-tile-val { font-size: 2.4rem; font-weight: 800; line-height: 1; color: #fff; letter-spacing: -0.03em; }
.tile-blue   { background: linear-gradient(135deg, #3b5bdb 0%, #4c6ef5 100%); }
.tile-green  { background: linear-gradient(135deg, #2f9e44 0%, #40c057 100%); }
.tile-purple { background: linear-gradient(135deg, #7048e8 0%, #9775fa 100%); }
.tile-orange { background: linear-gradient(135deg, #e67700 0%, #fd7e14 100%); }

/* CARDS GRID */
.cards-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
  animation: fadeIn 0.35s both 0.18s;
}

/* INSTRUCTOR CARD */
.instructor-card {
  background: var(--white); border-radius: 16px;
  border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
  overflow: hidden;
  transition: transform 0.22s, box-shadow 0.22s;
}
.instructor-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.09); }

.ic-header { padding: 18px 20px 14px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--border); }
.ic-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); flex-shrink: 0; }
.ic-name { font-size: 0.95rem; font-weight: 700; color: var(--text); margin-bottom: 1px; }
.ic-dept { font-size: 0.76rem; color: var(--text-secondary); }
.ic-email { font-size: 0.72rem; color: #3b5bdb; }

/* RFID CARD VISUAL */
.rfid-card {
  margin: 16px 20px;
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #3b82f6 100%);
  border-radius: 14px; padding: 18px 20px 16px;
  position: relative; overflow: hidden;
  box-shadow: 0 8px 24px rgba(37,99,235,0.35);
}
.rfid-card::before {
  content: ''; position: absolute;
  top: -40px; right: -40px; width: 150px; height: 150px;
  border-radius: 50%; background: rgba(255,255,255,0.06);
  pointer-events: none;
}
.rfid-card::after {
  content: ''; position: absolute;
  bottom: -50px; left: 20px; width: 130px; height: 130px;
  border-radius: 50%; background: rgba(255,255,255,0.04);
  pointer-events: none;
}

/* Row 1: institution + badge */
.rfid-row1 { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; position: relative; z-index: 1; }
.rfid-institution { font-size: 0.82rem; font-weight: 700; color: rgba(255,255,255,0.9); letter-spacing: 0.02em; }
.rfid-system { font-size: 0.68rem; color: rgba(255,255,255,0.5); margin-top: 2px; }

/* Row 2: chip + wifi */
.rfid-row2 { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; position: relative; z-index: 1; }
.rfid-chip {
  width: 36px; height: 28px; border-radius: 5px;
  background: linear-gradient(135deg, #f5c518 0%, #e8a000 100%);
  display: flex; align-items: center; justify-content: center; position: relative;
}
.rfid-chip::before { content: ''; position: absolute; inset: 4px; border-radius: 2px; border: 1px solid rgba(0,0,0,0.2); }
.rfid-chip-dot { width: 6px; height: 6px; background: rgba(0,0,0,0.25); border-radius: 50%; position: relative; z-index: 1; }
.rfid-wifi { color: rgba(255,255,255,0.45); font-size: 1rem; }

.rfid-status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 100px;
  font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em;
  flex-shrink: 0;
}
.rfid-status-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.7); flex-shrink: 0; }
.badge-active  { background: #22c55e; color: #fff; }
.badge-pending { background: #f97316; color: #fff; }
.badge-inactive { background: #64748b; color: #fff; }

.rfid-number-label { font-size: 0.58rem; font-weight: 600; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 3px; position: relative; z-index: 1; }
.rfid-number { font-size: 1.2rem; font-weight: 700; color: #fff; letter-spacing: 0.16em; margin-bottom: 16px; font-family: 'Courier New', monospace; position: relative; z-index: 1; }

.rfid-footer { display: flex; align-items: flex-end; justify-content: space-between; position: relative; z-index: 1; }
.rfid-instructor-label { font-size: 0.58rem; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
.rfid-instructor-name { font-size: 0.86rem; font-weight: 700; color: #fff; margin-bottom: 1px; }
.rfid-instructor-dept { font-size: 0.68rem; color: var(--yellow); }
.rfid-expires-label { font-size: 0.58rem; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; text-align: right; }
.rfid-expires-val { font-size: 0.92rem; font-weight: 700; color: #fff; text-align: right; }

.rfid-tag { margin-top: 12px; font-size: 0.63rem; color: rgba(255,255,255,0.35); font-family: 'Courier New', monospace; display: flex; align-items: center; gap: 6px; position: relative; z-index: 1; }
.rfid-tag i { font-size: 0.7rem; color: rgba(255,255,255,0.3); }

/* CARD ACTIONS */
.ic-actions { display: flex; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--border); }
.btn-reissue {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px;
  padding: 9px; border-radius: 9px; border: none; cursor: pointer;
  font-size: 0.82rem; font-weight: 700; font-family: 'Inter', sans-serif;
  background: #3b5bdb; color: #fff; transition: all 0.18s;
  box-shadow: 0 2px 8px rgba(59,91,219,0.22);
}
.btn-reissue:hover { background: #2f4ac9; transform: translateY(-1px); }
.btn-deactivate {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px;
  padding: 9px; border-radius: 9px; cursor: pointer;
  font-size: 0.82rem; font-weight: 700; font-family: 'Inter', sans-serif;
  background: #fff; color: var(--red); border: 1.5px solid #fecaca; transition: all 0.18s;
}
.btn-deactivate:hover { background: var(--red-bg); }
.btn-activate {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 7px;
  padding: 9px; border-radius: 9px; cursor: pointer;
  font-size: 0.82rem; font-weight: 700; font-family: 'Inter', sans-serif;
  background: #fff; color: #15803d; border: 1.5px solid #86efac; transition: all 0.18s;
}
.btn-activate:hover { background: #f0fdf4; }
.btn-activate:disabled,
.btn-deactivate:disabled { cursor: wait; opacity: 0.65; }

/* ASSIGNED ROOMS */
.ic-rooms { padding: 14px 20px 18px; }
.ic-rooms-title { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; }
.ic-rooms-title i { font-size: 0.72rem; }
.room-name-label { font-size: 0.88rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.access-policy-note { margin: 0 0 10px; color: var(--text-secondary); font-size: 0.76rem; line-height: 1.45; }
.room-sched-table { width: 100%; border-collapse: collapse; }
.room-sched-table tr { border-bottom: 1px solid #f3f4f6; }
.room-sched-table tr:last-child { border-bottom: none; }
.room-sched-table td { padding: 6px 0; font-size: 0.78rem; }
.room-sched-table .td-day  { color: var(--text-secondary); width: 80px; }
.room-sched-table .td-time { color: var(--text-secondary); width: 100px; }
.room-sched-table .td-subj { color: #3b5bdb; font-weight: 600; text-align: right; }
.room-sched-table .td-room { display: block; margin-top: 2px; color: var(--text-secondary); font-size: 0.68rem; font-weight: 500; }

/* ANIMATIONS */
@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
.instructor-card:nth-child(1) { animation: fadeIn 0.35s both 0.2s; }
.instructor-card:nth-child(2) { animation: fadeIn 0.35s both 0.28s; }
.instructor-card:nth-child(3) { animation: fadeIn 0.35s both 0.36s; }
.instructor-card:nth-child(4) { animation: fadeIn 0.35s both 0.44s; }

/* RESPONSIVE */
@media (max-width:1100px) { .cards-grid { grid-template-columns: 1fr; } .stat-row { grid-template-columns: repeat(2,1fr); } }
@media (max-width:768px) { :root { --sidebar-w: 0px; } .sidebar { display: none; } .content { padding: 20px 16px 40px; } .topbar { padding: 0 16px; } .topbar-search { width: 200px; } }
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>

<!-- ════════════════════════════════════════════
     SIDEBAR — EXACT COPY FROM CLASSROOMS
════════════════════════════════════════════ -->
<div class="sidebar">
  <a href="#" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu" style="font-size:0.6rem;font-weight:700;letter-spacing:0.18em;color:rgba(255,255,255,0.45);display:block;margin-bottom:3px;text-transform:uppercase;">PSU</span>
      <span class="brand-main">Smart<span>Room</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main Menu</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ route('admin.classrooms') }}">
        <span class="nav-icon"><i class="fas fa-school"></i></span>
        Room Management
      </a>
    </li>
    <li>
      <a href="{{ url('/admin/schedule') }}">
        <span class="nav-icon"><i class="fas fa-calendar-days"></i></span>
        Schedule
      </a>
    </li>
    <li>
      <a href="{{ url('/admin/users') }}">
        <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
        User Management
      </a>
    </li>
    <li>
      <a href="{{ url('/smartlocking') }}" class="active">
        <span class="nav-icon"><i class="fas fa-lock"></i></span>
        SmartLocking
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'Admin User') }}&background=0ea5e9&color=fff" alt="{{ auth()->user()?->name ?? 'Admin User' }}">
      <div class="user-widget-info">
        <div class="user-widget-name">{{ auth()->user()?->name ?? 'Admin User' }}</div>
        <div class="user-widget-role">{{ ucfirst(auth()->user()?->role ?? 'admin') }}</div>
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

  <!-- CONTENT -->
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
      <button type="button" class="tab-btn active" id="cardsTab">
        <i class="fas fa-credit-card"></i> RFID Access Cards
      </button>
      <button class="tab-btn" onclick="window.location.href='/admin/accessLogs';">
        <i class="fas fa-wave-square"></i> Access Logs
      </button>
      <button type="button" class="tab-btn" id="settingsTab">
        <i class="fas fa-gear"></i> Settings
      </button>
    </div>

    <div id="cardsView">
      <!-- Section Header -->
      <div class="section-header">
      <div class="section-header-left">
        <h2>RFID Access Cards</h2>
        <p>Manage and monitor RFID cards for instructor access</p>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
        <div class="search-inline">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search instructors, RFID tags, rooms...">
        </div>
        <button type="button" class="btn-add-card" id="openAddCardModal"><i class="fas fa-plus"></i> Add RFID Card</button>
      </div>
      </div>

    <div class="modal-backdrop" id="addCardModal" role="dialog" aria-modal="true" aria-labelledby="addCardTitle">
      <div class="card-modal">
        <div class="card-modal-header">
          <div>
            <h3 id="addCardTitle">Add RFID Access Card</h3>
            <p>Register a card for an instructor.</p>
          </div>
          <button type="button" class="modal-close" id="closeAddCardModal" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>
        <form class="card-form" id="addCardForm">
          <div class="form-errors" id="addCardErrors"></div>
          <div class="card-form-grid">
            <label>Card number
              <input name="card_number" required maxlength="255" placeholder="CARD-0002">
            </label>
            <label>RFID UID
              <input name="rfid_uid" required maxlength="255" placeholder="87:3E:D2:06">
              <small style="color:var(--text-secondary);font-size:.68rem;font-weight:500;">Use the reader UID only. Do not include RFID-.</small>
            </label>
          </div>
          <label>Instructor
            <select name="user_id" required>
              <option value="">Select instructor</option>
              @foreach(($instructors ?? []) as $instructor)
                <option value="{{ $instructor->id }}">{{ $instructor->name }}{{ $instructor->department ? ' · '.$instructor->department : '' }}</option>
              @endforeach
            </select>
          </label>
          <label>Expires on
            <input type="date" name="expires_at">
          </label>
          <label>Status
            <select name="status">
              <option value="active">Active</option>
              <option value="pending">Pending</option>
            </select>
          </label>
          <div class="card-form-actions">
            <button type="button" class="btn-modal-cancel" id="cancelAddCard">Cancel</button>
            <button type="submit" class="btn-add-card"><i class="fas fa-check"></i> Create Card</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Stat Tiles -->
      <div class="stat-row">
      <div class="stat-tile tile-blue">
        <div class="stat-tile-top">
          <div>
            <div class="stat-tile-label">Active RFID Cards</div>
          </div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-credit-card"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['active_cards'] ?? 0 }}</div>
      </div>
      <div class="stat-tile tile-green">
        <div class="stat-tile-top">
          <div>
            <div class="stat-tile-label">Total Instructors</div>
          </div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-user-group"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['total_instructors'] ?? 0 }}</div>
      </div>
      <div class="stat-tile tile-purple">
        <div class="stat-tile-top">
          <div>
            <div class="stat-tile-label">Scheduled Rooms</div>
          </div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-school"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['scheduled_rooms'] ?? 0 }}</div>
      </div>
      <div class="stat-tile tile-orange">
        <div class="stat-tile-top">
          <div>
            <div class="stat-tile-label">Pending Cards</div>
          </div>
          <div class="stat-tile-icon-wrap"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-tile-val">{{ $stats['pending_cards'] ?? 0 }}</div>
      </div>
    </div>

      <div class="cards-grid">
        @foreach(($cards ?? []) as $card)
          @php
            $cardIsActive = ($card['status'] ?? 'active') === 'active';
            $statusClass = match($card['status'] ?? 'active') {
                'pending' => 'badge-pending',
                'inactive' => 'badge-inactive',
                default => 'badge-active',
            };
            $statusLabel = strtoupper($card['status'] ?? 'active');
            $initials = collect(explode(' ', (string) ($card['name'] ?? 'U')))
                ->filter()
                ->take(2)
                ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                ->join('');
          @endphp
          <div class="instructor-card">
            <div class="ic-header">
              <div class="ic-avatar ic-avatar--initials">{{ $initials !== '' ? $initials : 'U' }}</div>
              <div>
                <div class="ic-name">{{ $card['name'] }}</div>
                <div class="ic-dept">{{ $card['department'] }}</div>
                <div class="ic-email"><a href="mailto:{{ $card['email'] }}">{{ $card['email'] }}</a></div>
              </div>
            </div>

            <div class="rfid-card">
              <div class="rfid-row1">
                <div>
                  <div class="rfid-institution">PSU Assingan Campus</div>
                  <div class="rfid-system">SmartDoor System</div>
                </div>
                <span class="rfid-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
              </div>
              <div class="rfid-row2">
                <div class="rfid-chip"><div class="rfid-chip-dot"></div></div>
                <i class="fas fa-wifi rfid-wifi"></i>
              </div>
              <div class="rfid-number-label">Card Number</div>
              <div class="rfid-number">{{ $card['card_number'] }}</div>
              <div class="rfid-footer">
                <div>
                  <div class="rfid-instructor-label">Instructor</div>
                  <div class="rfid-instructor-name">{{ $card['name'] }}</div>
                  <div class="rfid-instructor-dept">{{ $card['department'] }}</div>
                </div>
                <div>
                  <div class="rfid-expires-label">Expires</div>
                  <div class="rfid-expires-val">{{ $card['expires'] }}</div>
                </div>
              </div>
              <div class="rfid-tag"><i class="fas fa-barcode"></i> {{ $card['rfid_uid'] }}</div>
            </div>

            <div class="ic-actions">
              <a class="btn-reissue" href="{{ route('smartlocking.show', $card['id']) }}"><i class="fas fa-eye"></i> View Details</a>
              <button
                type="button"
                class="{{ $cardIsActive ? 'btn-deactivate' : 'btn-activate' }} js-toggle-card"
                data-card-id="{{ $card['id'] }}"
                data-next-status="{{ $cardIsActive ? 'inactive' : 'active' }}"
                data-action-label="{{ $cardIsActive ? 'Deactivate' : 'Activate' }}"
              >
                <i class="fas {{ $cardIsActive ? 'fa-circle-xmark' : 'fa-circle-check' }}" aria-hidden="true"></i>
                {{ $cardIsActive ? 'Deactivate' : 'Activate' }}
              </button>
            </div>

            <div class="ic-rooms">
              <div class="ic-rooms-title"><i class="fas fa-door-open"></i> Reservation &amp; Class Access</div>
              <p class="access-policy-note">This card works at any door where its owner has an active reservation or official class schedule.</p>
              <table class="room-sched-table">
                @forelse(($card['schedule'] ?? []) as $row)
                  <tr>
                    <td class="td-day">{{ $row['day'] }}</td>
                    <td class="td-time">{{ str_replace('-', ' - ', $row['time']) }}</td>
                    <td class="td-subj">{{ $row['subject'] }}<small class="td-room">{{ $row['room'] }}</small></td>
                  </tr>
                @empty
                  <tr>
                    <td class="td-day">-</td>
                    <td class="td-time">No schedule assigned</td>
                    <td class="td-subj">An active room reservation can still grant access.</td>
                  </tr>
                @endforelse
              </table>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <section class="settings-panel" id="settingsView" aria-labelledby="settingsHeading">
      <div class="settings-layout">
        <nav class="settings-nav" aria-label="Settings sections">
          <button type="button" class="settings-nav-item active" data-settings-panel="door"><i class="fas fa-door-closed"></i> Door Controller</button>
          <button type="button" class="settings-nav-item" data-settings-panel="reader"><i class="fas fa-id-card"></i> RFID Reader</button>
          <button type="button" class="settings-nav-item" data-settings-panel="notifications"><i class="fas fa-bell"></i> Notifications</button>
        </nav>
        <form class="settings-card" id="smartDoorSettingsForm">
          <div class="settings-card-header">
            <div>
              <h2 id="settingsHeading">SmartDoor Settings</h2>
              <p>Configure the controller and access behavior for your rooms.</p>
            </div>
            <span class="settings-status"><i class="fas fa-circle"></i> Controller online</span>
          </div>
          <div class="settings-section" data-settings-content="door">
            <h3>Door controller</h3>
            <p>These rules apply when a valid RFID card is presented.</p>
            <div class="settings-field-grid">
              <label class="settings-field">Default unlock duration
                <select name="unlock_duration"><option value="3">3 seconds</option><option value="5">5 seconds</option><option value="10">10 seconds</option></select>
              </label>
              <label class="settings-field">Access mode
                <select name="access_mode"><option value="scheduled">Scheduled access</option><option value="always">Always allow active cards</option></select>
              </label>
            </div>
          </div>
          <div class="settings-section" data-settings-content="door">
            <h3>Security controls</h3>
            <div class="setting-row"><div><strong>Require an active schedule</strong><span>Reject cards outside their assigned class schedule.</span></div><label class="switch"><input type="checkbox" name="require_schedule" checked><span class="switch-slider"></span></label></div>
            <div class="setting-row"><div><strong>Lock after denied attempt</strong><span>Temporarily lock the reader after a rejected card.</span></div><label class="switch"><input type="checkbox" name="lock_after_denied" checked><span class="switch-slider"></span></label></div>
            <div class="setting-row"><div><strong>Enable access logging</strong><span>Record every granted and denied entry attempt.</span></div><label class="switch"><input type="checkbox" name="access_logging" checked><span class="switch-slider"></span></label></div>
          </div>
          <div class="settings-section is-hidden" data-settings-content="reader">
            <h3>Reader connection</h3>
            <div class="settings-field-grid">
              <label class="settings-field">Reader device
                <input name="reader_name" value="SmartDoor Reader 01" maxlength="100">
              </label>
              <label class="settings-field">Heartbeat interval
                <select name="heartbeat"><option value="15">Every 15 seconds</option><option value="30">Every 30 seconds</option><option value="60">Every minute</option></select>
              </label>
            </div>
          </div>
          <div class="settings-section is-hidden" data-settings-content="notifications">
            <h3>Lost RFID cards</h3>
            @forelse($lostCardReports as $report)
              <div class="setting-row">
                <div>
                  <strong>{{ $report->title }}</strong>
                  <span>{{ $report->body }} · {{ $report->created_at?->diffForHumans() }}</span>
                </div>
                <span class="chip {{ $report->read_at ? '' : 'bad' }}">{{ $report->read_at ? 'Reviewed' : 'Needs review' }}</span>
              </div>
            @empty
              <p>No lost-card reports.</p>
            @endforelse
            <h3>Notifications</h3>
            <p>Choose which SmartDoor events should notify administrators.</p>
            <div class="setting-row"><div><strong>Denied access alerts</strong><span>Notify admins when a card is rejected.</span></div><label class="switch"><input type="checkbox" name="denied_alerts" checked><span class="switch-slider"></span></label></div>
            <div class="setting-row"><div><strong>Reader offline alerts</strong><span>Notify admins when a controller misses its heartbeat.</span></div><label class="switch"><input type="checkbox" name="offline_alerts" checked><span class="switch-slider"></span></label></div>
            <div class="setting-row"><div><strong>Daily access summary</strong><span>Send a daily summary of access activity.</span></div><label class="switch"><input type="checkbox" name="daily_summary"><span class="switch-slider"></span></label></div>
          </div>
          <div class="settings-actions"><button type="submit" class="btn-settings-save"><i class="fas fa-floppy-disk"></i> Save Settings</button></div>
        </form>
      </div>
    </section>

    </div>

  </div>
</div>

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

const cardsTab = document.getElementById('cardsTab');
const settingsTab = document.getElementById('settingsTab');
const cardsView = document.getElementById('cardsView');
const settingsView = document.getElementById('settingsView');
const settingsForm = document.getElementById('smartDoorSettingsForm');
const settingsStorageKey = 'smartdoor.settings';

function setActiveTab(tab) {
  const showSettings = tab === 'settings';
  cardsTab.classList.toggle('active', !showSettings);
  settingsTab.classList.toggle('active', showSettings);
  cardsView.hidden = showSettings;
  settingsView.classList.toggle('is-visible', showSettings);
}

function loadSmartDoorSettings() {
  const savedSettings = JSON.parse(localStorage.getItem(settingsStorageKey) || '{}');

  Object.entries(savedSettings).forEach(([name, value]) => {
    const field = settingsForm.elements.namedItem(name);
    if (!field) return;

    if (field.type === 'checkbox') {
      field.checked = value === true;
    } else {
      field.value = value;
    }
  });
}

cardsTab.addEventListener('click', () => setActiveTab('cards'));
settingsTab.addEventListener('click', () => setActiveTab('settings'));
document.querySelectorAll('.settings-nav-item').forEach((item) => {
  item.addEventListener('click', () => {
    document.querySelectorAll('.settings-nav-item').forEach((navItem) => navItem.classList.remove('active'));
    item.classList.add('active');
    const selectedPanel = item.dataset.settingsPanel;
    document.querySelectorAll('[data-settings-content]').forEach((panel) => {
      panel.classList.toggle('is-hidden', panel.dataset.settingsContent !== selectedPanel);
    });
  });
});
settingsForm.addEventListener('submit', (event) => {
  event.preventDefault();
  const values = {};

  new FormData(settingsForm).forEach((value, name) => {
    values[name] = value;
  });
  settingsForm.querySelectorAll('input[type="checkbox"]').forEach((field) => {
    values[field.name] = field.checked;
  });

  localStorage.setItem(settingsStorageKey, JSON.stringify(values));
  showToast('SmartDoor settings saved on this device.', 'success');
});
loadSmartDoorSettings();
if (new URLSearchParams(window.location.search).get('tab') === 'settings') {
  setActiveTab('settings');
}

document.querySelectorAll('.js-toggle-card').forEach((button) => {
  button.addEventListener('click', async (event) => {
    event.preventDefault();

    const cardId = button.getAttribute('data-card-id');
    const nextStatus = button.getAttribute('data-next-status');
    const actionLabel = button.getAttribute('data-action-label');
    if (!cardId || !['active', 'inactive'].includes(nextStatus)) return;

    if (!confirm(`${actionLabel} this RFID card?`)) return;

    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Saving...';

    try {
      const response = await fetch(`/admin/access-cards/${cardId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': "{{ csrf_token() }}",
        },
        body: JSON.stringify({ status: nextStatus }),
      });

      if (!response.ok) {
        const errorBody = await response.json().catch(() => ({}));
        throw new Error(errorBody?.message || `Failed to ${actionLabel.toLowerCase()} card.`);
      }

      window.location.reload();
    } catch (error) {
      showToast(error.message || `Failed to ${actionLabel.toLowerCase()} card.`, 'error');
      button.disabled = false;
      button.innerHTML = originalContent;
    }
  });
});

const addCardModal = document.getElementById('addCardModal');
const addCardForm = document.getElementById('addCardForm');
const addCardErrors = document.getElementById('addCardErrors');

function closeAddCardModal() {
  addCardModal.classList.remove('is-open');
  addCardForm.reset();
  addCardErrors.classList.remove('is-visible');
  addCardErrors.textContent = '';
}

document.getElementById('openAddCardModal').addEventListener('click', () => {
  addCardModal.classList.add('is-open');
  addCardForm.querySelector('[name="card_number"]').focus();
});
document.getElementById('closeAddCardModal').addEventListener('click', closeAddCardModal);
document.getElementById('cancelAddCard').addEventListener('click', closeAddCardModal);
addCardModal.addEventListener('click', (event) => {
  if (event.target === addCardModal) closeAddCardModal();
});

addCardForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  addCardErrors.classList.remove('is-visible');
  const submitButton = addCardForm.querySelector('[type="submit"]');
  submitButton.disabled = true;

  try {
    const response = await fetch('{{ route('admin.access-cards.store') }}', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify(Object.fromEntries(new FormData(addCardForm))),
    });

    if (!response.ok) {
      const body = await response.json().catch(() => ({}));
      const messages = Object.values(body.errors || {}).flat();
      throw new Error(messages.join(' ') || body.message || 'Unable to create the card.');
    }

    showToast('RFID card created successfully.', 'success');
    setTimeout(() => window.location.reload(), 500);
  } catch (error) {
    addCardErrors.textContent = error.message;
    addCardErrors.classList.add('is-visible');
  } finally {
    submitButton.disabled = false;
  }
});
</script>

</body>
</html>