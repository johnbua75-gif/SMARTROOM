<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – SmartDoor</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ── RESET & BASE ───────────────────────────────── */
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
  --orange-bg:     #ffedd5;
  --orange-border: #fdba74;
  --orange-text:   #c2410c;
  --red:           #dc2626;
  --red-bg:        #fee2e2;
  --shadow:        0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-card:   0 2px 8px rgba(0,0,0,0.06);
  --radius:        14px;
  --radius-sm:     10px;
  --sidebar-w:     240px;
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
}

/* ══════════════════════════════════════════════
   SIDEBAR  — DO NOT CHANGE
══════════════════════════════════════════════ */
.sidebar {
  position: fixed;
  left: 0; top: 0;
  width: var(--sidebar-w);
  height: 100vh;
  background: var(--navy);
  display: flex;
  flex-direction: column;
  padding: 0;
  overflow: hidden;
  z-index: 100;
}
.sidebar::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(245,197,24,0.06) 0%, transparent 55%);
  pointer-events: none;
}
.sidebar::after {
  content: '';
  position: absolute;
  bottom: -60px; right: -60px;
  width: 180px; height: 180px;
  border-radius: 50%;
  border: 1px solid rgba(245,197,24,0.08);
  pointer-events: none;
}
.sidebar-logo {
  display: flex; align-items: center; gap: 12px;
  padding: 28px 20px 24px 24px;
  text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  margin-bottom: 8px;
}
.logo-mark {
  width: 40px; height: 40px;
  background: var(--yellow);
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

/* ══════════════════════════════════════════════
   MAIN LAYOUT
══════════════════════════════════════════════ */
.main {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ── TOPBAR ─────────────────────────────────────── */
.topbar {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 36px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: 0 1px 0 var(--border);
}

.topbar-search {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg);
  border: 1.5px solid var(--border);
  border-radius: 24px;
  padding: 8px 18px;
  width: 340px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.topbar-search:focus-within {
  border-color: #93c5fd;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.topbar-search i { color: var(--text-light); font-size: 0.9rem; }
.topbar-search input {
  border: none; outline: none; background: transparent;
  font-size: 0.9rem; font-family: 'Inter', sans-serif;
  color: var(--text); width: 100%;
}
.topbar-search input::placeholder { color: var(--text-light); }

.topbar-right {
  display: flex; align-items: center; gap: 20px;
}

.notif-btn {
  position: relative; background: none; border: none;
  cursor: pointer; color: var(--text-secondary); font-size: 1.2rem;
  padding: 6px; border-radius: 8px; transition: background 0.2s;
}
.notif-btn:hover { background: var(--bg); }
.notif-badge {
  position: absolute; top: 4px; right: 4px;
  width: 8px; height: 8px; background: var(--red);
  border-radius: 50%; border: 1.5px solid var(--white);
}

.topbar-profile {
  display: flex; align-items: center; gap: 12px; cursor: pointer;
}
.topbar-profile-info { text-align: right; }
.topbar-profile-name {
  font-size: 0.88rem; font-weight: 700; color: var(--text); line-height: 1.2;
}
.topbar-profile-role { font-size: 0.78rem; color: var(--text-secondary); }
.topbar-profile img {
  width: 40px; height: 40px; border-radius: 50%; object-fit: cover;
  border: 2px solid var(--border);
  box-shadow: var(--shadow);
}

/* ── PAGE CONTENT ────────────────────────────────── */
.content {
  padding: 32px 36px 48px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ── PAGE HEADER ─────────────────────────────────── */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.page-header-left h1 {
  font-size: 1.55rem;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.02em;
  margin-bottom: 4px;
}
.page-header-left p {
  font-size: 0.92rem;
  color: var(--text-secondary);
  font-weight: 400;
}
.date-badge {
  display: flex; align-items: center; gap: 8px;
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 24px;
  padding: 7px 16px;
  font-size: 0.88rem;
  color: var(--text-secondary);
  font-weight: 500;
  box-shadow: var(--shadow);
  white-space: nowrap;
}
.date-badge i { color: var(--text-light); font-size: 0.82rem; }

/* ── STAT CARDS ──────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.stat-card {
  background: var(--white);
  border-radius: var(--radius);
  border: 1.5px solid var(--border);
  padding: 22px 22px 20px;
  box-shadow: var(--shadow-card);
  position: relative;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.stat-trend {
  position: absolute;
  top: 18px; right: 18px;
  font-size: 0.78rem; font-weight: 700;
  display: flex; align-items: center; gap: 3px;
}
.trend-up   { color: var(--green); }
.trend-down { color: var(--red); }

.stat-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  margin-bottom: 16px;
}
.icon-yellow { background: var(--yellow-bg); color: #b45309; }
.icon-blue   { background: var(--blue-bg);   color: var(--blue-text); }
.icon-green  { background: var(--green-bg);  color: var(--green); }
.icon-red    { background: var(--red-bg);    color: var(--red); }

.stat-value {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.04em;
  line-height: 1;
  margin-bottom: 5px;
}
.stat-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  font-weight: 500;
}

/* ── CHARTS ROW ──────────────────────────────────── */
.charts-row {
  display: grid;
  grid-template-columns: 1.7fr 1fr;
  gap: 16px;
}

.chart-card {
  background: var(--white);
  border-radius: var(--radius);
  border: 1.5px solid var(--border);
  padding: 24px 24px 20px;
  box-shadow: var(--shadow-card);
}

.chart-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.chart-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
}
.chart-period-btn {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-secondary);
  background: var(--bg);
  border: 1.5px solid var(--border);
  border-radius: 20px;
  padding: 4px 14px;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  transition: border-color 0.2s;
}
.chart-period-btn:hover { border-color: #93c5fd; }
.chart-wrap { height: 220px; }

/* ── RECENT STATUS ───────────────────────────────── */
.status-card {
  background: var(--white);
  border-radius: var(--radius);
  border: 1.5px solid var(--border);
  box-shadow: var(--shadow-card);
  overflow: hidden;
}

.status-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 24px 16px;
}
.status-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
}
.view-all-link {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--navy-mid);
  text-decoration: none;
  transition: color 0.2s;
}
.view-all-link:hover { color: var(--navy); }

.status-table { width: 100%; border-collapse: collapse; }

.status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 24px;
  border-top: 1px solid var(--border);
  transition: background 0.15s;
}
.status-row:hover { background: var(--bg); }

.status-room-info {
  display: flex; align-items: center; gap: 14px;
}
.status-room-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.room-name {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 2px;
}
.room-meta {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.status-badge {
  font-size: 0.8rem;
  font-weight: 700;
  border-radius: 8px;
  padding: 5px 16px;
  letter-spacing: 0.01em;
}
.badge-occupied    { background: var(--blue-bg);    color: var(--blue-text);    border: 1.5px solid var(--blue-border); }
.badge-available   { background: var(--green-bg);   color: var(--green);        border: 1.5px solid var(--green-border); }
.badge-maintenance { background: var(--orange-bg);  color: var(--orange-text);  border: 1.5px solid var(--orange-border); }

/* ── ANIMATIONS ──────────────────────────────────── */
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.page-header { animation: fadeSlideUp 0.4s both 0.04s; }
.stats-grid  { animation: fadeSlideUp 0.4s both 0.1s; }
.charts-row  { animation: fadeSlideUp 0.4s both 0.18s; }
.status-card { animation: fadeSlideUp 0.4s both 0.25s; }

/* ── RESPONSIVE ──────────────────────────────────── */
@media (max-width: 1200px) {
  .stats-grid  { grid-template-columns: repeat(2,1fr); }
  .charts-row  { grid-template-columns: 1fr; }
  .content     { padding: 24px 20px 40px; }
  .topbar      { padding: 0 20px; }
}
@media (max-width: 768px) {
  :root { --sidebar-w: 0px; }
  .sidebar { display: none; }
  .topbar-search { width: 200px; }
  .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
}
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>

@include('frontend.admin.partials.sidebar')

{{-- ═══ MAIN ══════════════════════════════════════ --}}
<div class="main">

  {{-- TOPBAR --}}
  <div class="topbar">
    <div style="font-size:0.84rem;color:var(--text-secondary);display:flex;align-items:center;gap:7px;">
      <i class="fas fa-clock" style="font-size:0.78rem;color:var(--text-light);"></i>
      <span>{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
    </div>
  </div>

  {{-- CONTENT --}}
  <div class="content">

    {{-- Page Header --}}
    <div class="page-header">
      <div class="page-header-left">
        <h1>Welcome back, Elena!</h1>
        <p>Here's what's happening in your campus today.</p>
      </div>
      <div class="date-badge">
        <i class="fas fa-clock"></i>
        <span>{{ \Carbon\Carbon::now()->format('l, F j') }}</span>
      </div>
    </div>

    {{-- Stat Cards --}}
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> +2</div>
        <div class="stat-icon-wrap icon-yellow">
          <i class="fas fa-door-open"></i>
        </div>
        <div class="stat-value">42</div>
        <div class="stat-label">Total Classrooms</div>
      </div>

      <div class="stat-card">
        <div class="stat-trend trend-down"><i class="fas fa-arrow-down"></i> -4</div>
        <div class="stat-icon-wrap icon-blue">
          <i class="fas fa-user-group"></i>
        </div>
        <div class="stat-value">18</div>
        <div class="stat-label">Rooms Occupied</div>
      </div>

      <div class="stat-card">
        <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> +12</div>
        <div class="stat-icon-wrap icon-green">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-value">86</div>
        <div class="stat-label">Scheduled Today</div>
      </div>

      <div class="stat-card">
        <div class="stat-trend trend-down"><i class="fas fa-arrow-down"></i> -1</div>
        <div class="stat-icon-wrap icon-red">
          <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div class="stat-value">3</div>
        <div class="stat-label">Reported Issues</div>
      </div>
    </div>

    {{-- Charts --}}
    <div class="charts-row">
      {{-- Occupancy Line Chart --}}
      <div class="chart-card">
        <div class="chart-card-header">
          <div class="chart-card-title">Classroom Occupancy Trends</div>
          <select class="chart-period-btn" id="occupancyPeriod" aria-label="Occupancy period">
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
          </select>
        </div>
        <div class="chart-wrap">
          <canvas id="occupancyChart"></canvas>
        </div>
      </div>

      {{-- Weekly Bar Chart --}}
      <div class="chart-card">
        <div class="chart-card-header">
          <div class="chart-card-title">Weekly Peak Usage (%)</div>
        </div>
        <div class="chart-wrap">
          <canvas id="usageChart"></canvas>
        </div>
      </div>
    </div>

    {{-- Recent Status Changes --}}
    <div class="status-card">
      <div class="status-card-header">
        <div class="status-card-title">Recent Status Changes</div>
        <a href="#" class="view-all-link">View All</a>
      </div>

      <div class="status-row">
        <div class="status-room-info">
          <div class="status-room-icon icon-blue">
            <i class="fas fa-door-open"></i>
          </div>
          <div>
            <div class="room-name">Room 302</div>
            <div class="room-meta">Dr. Ramos • 10 mins ago</div>
          </div>
        </div>
        <span class="status-badge badge-occupied">Occupied</span>
      </div>

      <div class="status-row">
        <div class="status-room-info">
          <div class="status-room-icon icon-green">
            <i class="fas fa-door-open"></i>
          </div>
          <div>
            <div class="room-name">Lab 105</div>
            <div class="room-meta">Prof. Cruz • 25 mins ago</div>
          </div>
        </div>
        <span class="status-badge badge-available">Available</span>
      </div>

      <div class="status-row">
        <div class="status-room-info">
          <div class="status-room-icon icon-yellow">
            <i class="fas fa-door-open"></i>
          </div>
          <div>
            <div class="room-name">Room 201</div>
            <div class="room-meta">Engr. Rivera • 1 hour ago</div>
          </div>
        </div>
        <span class="status-badge badge-maintenance">Maintenance</span>
      </div>

      <div class="status-row">
        <div class="status-room-info">
          <div class="status-room-icon icon-blue">
            <i class="fas fa-door-open"></i>
          </div>
          <div>
            <div class="room-name">Room 404</div>
            <div class="room-meta">Dr. Santos • 2 hours ago</div>
          </div>
        </div>
        <span class="status-badge badge-occupied">Occupied</span>
      </div>
    </div>

  </div>{{-- /content --}}
</div>{{-- /main --}}

<script>
/* ── Occupancy Area Chart ── */
const occCtx  = document.getElementById('occupancyChart').getContext('2d');
const occGrad = occCtx.createLinearGradient(0, 0, 0, 220);
occGrad.addColorStop(0, 'rgba(30,58,138,0.16)');
occGrad.addColorStop(1, 'rgba(30,58,138,0.0)');

const occupancyChart = new Chart(occCtx, {
  type: 'line',
  data: {
    labels: ['7AM','9AM','11AM','1PM','3PM','5PM','7PM'],
    datasets: [{
      data: [15, 25, 42, 45, 44, 30, 10],
      borderColor: '#1d4ed8',
      backgroundColor: occGrad,
      fill: true,
      tension: 0.45,
      pointRadius: 0,
      pointHoverRadius: 5,
      pointHoverBackgroundColor: '#fff',
      pointHoverBorderColor: '#1d4ed8',
      pointHoverBorderWidth: 2.5,
      borderWidth: 2.5,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#fff',
        titleColor: '#111827',
        bodyColor: '#6b7280',
        borderColor: '#e5e7eb',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
        callbacks: { label: ctx => ` ${ctx.parsed.y} rooms` }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        border: { display: false },
        ticks: { color: '#9ca3af', font: { size: 11, weight: '500', family: 'Inter' } }
      },
      y: {
        grid: { color: '#f3f4f6', drawBorder: false },
        border: { display: false, dash: [3,3] },
        ticks: {
          color: '#9ca3af',
          font: { size: 11, weight: '500', family: 'Inter' },
          stepSize: 15, padding: 6
        },
        min: 0, max: 60
      }
    }
  }
});

document.getElementById('occupancyPeriod')?.addEventListener('change', function () {
  const periods = {
    today: { labels: ['7AM','9AM','11AM','1PM','3PM','5PM','7PM'], data: [15, 25, 42, 45, 44, 30, 10] },
    week: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], data: [34, 48, 55, 42, 61, 29, 18] },
    month: { labels: ['Week 1','Week 2','Week 3','Week 4'], data: [44, 58, 51, 67] },
  };
  const selected = periods[this.value];
  occupancyChart.data.labels = selected.labels;
  occupancyChart.data.datasets[0].data = selected.data;
  occupancyChart.update();
});

/* ── Weekly Bar Chart ── */
const barCtx = document.getElementById('usageChart').getContext('2d');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: ['Tue','','Thu','Fri','','Sat'],
    datasets: [{
      data: [82, 68, 65, 95, 88, 30],
      backgroundColor: '#f5c518',
      hoverBackgroundColor: '#fbbf24',
      borderRadius: 6,
      borderSkipped: false,
      barPercentage: 0.62,
      categoryPercentage: 0.72,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#fff',
        titleColor: '#111827',
        bodyColor: '#6b7280',
        borderColor: '#e5e7eb',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
        callbacks: { label: ctx => ` ${ctx.parsed.y}%` }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        border: { display: false },
        ticks: { color: '#9ca3af', font: { size: 11, weight: '500', family: 'Inter' } }
      },
      y: {
        grid: { display: false },
        border: { display: false },
        ticks: {
          color: '#9ca3af',
          font: { size: 11, weight: '500', family: 'Inter' },
          stepSize: 25, padding: 6,
          callback: v => v
        },
        min: 0, max: 110
      }
    }
  }
});
</script>

</body>
</html>