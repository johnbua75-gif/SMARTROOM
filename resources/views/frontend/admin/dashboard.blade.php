<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - SmartRoom</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy: #0b1640;
  --navy-mid: #1a2f80;
  --gold: #f5c518;
  --bg: #f6f8fc;
  --card: #ffffff;
  --line: #e6ebf3;
  --text: #0b1640;
  --muted: #7b86a3;
  --green: #16a34a;
  --red: #dc2626;
  --amber: #d4a10a;
  --sidebar-w: 252px;
}
body { font-family: 'Instrument Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; }
.main { margin-left: var(--sidebar-w); flex: 1; min-width: 0; padding: 36px 40px 56px; display: flex; flex-direction: column; gap: 28px; }

/* Header */
.dash-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
.dash-eyebrow { font-size: .7rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
.dash-title { font-size: 1.7rem; font-weight: 800; letter-spacing: -.02em; }
.dash-sub { font-size: .88rem; color: var(--muted); margin-top: 4px; }
.dash-live { display: inline-flex; align-items: center; gap: 10px; padding: 8px 14px; border-radius: 999px; background: var(--card); border: 1px solid var(--line); font-size: .74rem; font-weight: 700; color: #166534; }
.dash-live .dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; animation: ping 1.8s infinite; }
.dash-live b { color: var(--navy); font-family: ui-monospace, Menlo, Consolas, monospace; letter-spacing: .03em; font-variant-numeric: tabular-nums; }
@keyframes ping { 0% { box-shadow: 0 0 0 0 rgba(34,197,94,.55); } 70% { box-shadow: 0 0 0 8px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } }

/* KPI */
.kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.kpi { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 20px 22px; display: flex; flex-direction: column; gap: 14px; }
.kpi-top { display: flex; align-items: center; justify-content: space-between; }
.kpi-label { font-size: .72rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
.kpi-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .85rem; background: #eef2ff; color: var(--navy-mid); }
.kpi-icon.gold { background: #fffbe6; color: var(--amber); }
.kpi-value { font-size: 2.2rem; font-weight: 800; letter-spacing: -.03em; line-height: 1; }
.kpi-value small { font-size: .95rem; font-weight: 600; color: var(--muted); letter-spacing: 0; }
.kpi-foot { font-size: .78rem; color: var(--muted); }
.kpi-foot b { color: var(--text); font-weight: 700; }
.kpi-foot .bad { color: var(--red); font-weight: 700; }

/* Panels */
.grid-2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; }
.panel { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 22px 24px; }
.panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.panel-title { font-size: .95rem; font-weight: 800; }
.panel-link { font-size: .76rem; font-weight: 700; color: var(--navy-mid); text-decoration: none; }
.panel-link:hover { text-decoration: underline; }

/* Room status bar */
.status-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: #eef1f7; gap: 3px; }
.status-bar span { display: block; height: 100%; }
.seg-free { background: var(--navy-mid); }
.seg-busy { background: var(--gold); }
.seg-off { background: #cbd2e1; }
.status-legend { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 14px; font-size: .8rem; color: var(--muted); }
.status-legend b { color: var(--text); font-weight: 800; margin-right: 4px; }
.status-legend i { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 7px; }

/* Lists */
.list { display: flex; flex-direction: column; }
.row { display: flex; align-items: center; gap: 14px; padding: 13px 0; border-top: 1px solid var(--line); }
.row:first-child { border-top: 0; padding-top: 0; }
.row:last-child { padding-bottom: 0; }
.row-time { flex: 0 0 92px; font-size: .78rem; font-weight: 700; color: var(--navy-mid); font-variant-numeric: tabular-nums; }
.row-main { flex: 1; min-width: 0; }
.row-title { font-size: .86rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.row-sub { font-size: .74rem; color: var(--muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tag { flex: 0 0 auto; font-size: .64rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; padding: 4px 9px; border-radius: 999px; }
.tag-live { color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; }
.tag-next { color: var(--navy-mid); background: #eef2ff; border: 1px solid #dbe3f5; }
.tag-done { color: #8a94b0; background: #f4f6fa; border: 1px solid #eceff6; }
.tag-cancel { color: #b45309; background: #fffbeb; border: 1px solid #fde68a; }
.tag-ok { color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; }
.tag-deny { color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; }
.empty { padding: 26px 0 8px; text-align: center; font-size: .82rem; color: var(--muted); }

/* Quick actions + people */
.actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.action { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-radius: 14px; border: 1px solid var(--line); text-decoration: none; color: var(--text); font-size: .82rem; font-weight: 700; transition: all .15s; }
.action i { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .8rem; background: #eef2ff; color: var(--navy-mid); }
.action:hover { border-color: var(--navy-mid); transform: translateY(-1px); }
.people { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 18px; }
.person { padding: 14px; border-radius: 14px; background: var(--bg); text-align: center; }
.person b { display: block; font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; }
.person span { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }

@media (max-width: 1100px) { .kpis { grid-template-columns: repeat(2, 1fr); } .grid-2 { grid-template-columns: 1fr; } }
@media (max-width: 768px) { :root { --sidebar-w: 0px; } .main { padding: 24px 16px 40px; } .kpis { grid-template-columns: 1fr; } }
@media (prefers-reduced-motion: reduce) { .dash-live .dot { animation: none; } }
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>

@include('frontend.admin.partials.sidebar')

@php
    $now = now();
    $adminName = trim((string) (auth()->user()?->name ?? 'Admin'));
    $firstName = explode(' ', $adminName)[0] ?: 'Admin';
    $hour = (int) $now->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $total = max(1, $stats['rooms_total']);
    $pct = fn (int $n): string => number_format(($n / $total) * 100, 2, '.', '');
@endphp

<div class="main">

  <header class="dash-head">
    <div>
      <div class="dash-eyebrow">{{ $now->format('l, F j, Y') }}</div>
      <h1 class="dash-title">{{ $greeting }}, {{ $firstName }}</h1>
      <p class="dash-sub">Here is what is happening across your rooms today.</p>
    </div>
    <div class="dash-live"><span class="dot"></span>LIVE <b id="dashClock">--:--:--</b></div>
  </header>

  <section class="kpis">
    <article class="kpi">
      <div class="kpi-top"><span class="kpi-label">Rooms free now</span><span class="kpi-icon"><i class="fas fa-door-open"></i></span></div>
      <div class="kpi-value">{{ $stats['rooms_free'] }} <small>/ {{ $stats['rooms_total'] }}</small></div>
      <div class="kpi-foot"><b>{{ $stats['rooms_busy'] }}</b> in use &middot; <b>{{ $stats['rooms_unavailable'] }}</b> unavailable</div>
    </article>
    <article class="kpi">
      <div class="kpi-top"><span class="kpi-label">Classes today</span><span class="kpi-icon"><i class="fas fa-calendar-day"></i></span></div>
      <div class="kpi-value">{{ $stats['classes_today'] }}</div>
      <div class="kpi-foot">{{ $stats['classes_cancelled'] }} cancelled</div>
    </article>
    <article class="kpi">
      <div class="kpi-top"><span class="kpi-label">Door access today</span><span class="kpi-icon gold"><i class="fas fa-key"></i></span></div>
      <div class="kpi-value">{{ $stats['access_today'] }}</div>
      <div class="kpi-foot">
        @if ($stats['access_denied'] > 0)
          <span class="bad">{{ $stats['access_denied'] }} denied</span>
        @else
          No denied attempts
        @endif
      </div>
    </article>
    <article class="kpi">
      <div class="kpi-top"><span class="kpi-label">Active RFID cards</span><span class="kpi-icon gold"><i class="fas fa-id-card"></i></span></div>
      <div class="kpi-value">{{ $stats['active_cards'] }}</div>
      <div class="kpi-foot"><a class="panel-link" href="{{ route('smartlocking.index') }}">Manage cards</a></div>
    </article>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Room availability</h2>
      <a class="panel-link" href="{{ route('admin.classrooms') }}">View rooms</a>
    </div>
    <div class="status-bar" role="img" aria-label="Room availability breakdown">
      <span class="seg-free" style="width: {{ $pct($stats['rooms_free']) }}%"></span>
      <span class="seg-busy" style="width: {{ $pct($stats['rooms_busy']) }}%"></span>
      <span class="seg-off" style="width: {{ $pct($stats['rooms_unavailable']) }}%"></span>
    </div>
    <div class="status-legend">
      <span><i class="seg-free"></i><b>{{ $stats['rooms_free'] }}</b>Free</span>
      <span><i class="seg-busy"></i><b>{{ $stats['rooms_busy'] }}</b>In use</span>
      <span><i class="seg-off"></i><b>{{ $stats['rooms_unavailable'] }}</b>Unavailable</span>
    </div>
  </section>

  <div class="grid-2">
    <section class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Today's schedule</h2>
        <a class="panel-link" href="{{ route('admin.schedule') }}">Full schedule</a>
      </div>
      <div class="list">
        @forelse ($todaySchedules as $schedule)
          @php
            $subject = $schedule->course?->title ?? $schedule->courseOffering?->course?->title ?? 'Class session';
            $isLive = in_array($schedule->id, $liveScheduleIds, true);
            if ($schedule->status === 'cancelled') { $tagClass = 'tag-cancel'; $tagText = 'Cancelled'; }
            elseif ($isLive) { $tagClass = 'tag-live'; $tagText = 'Live'; }
            elseif ($schedule->end_at <= $now) { $tagClass = 'tag-done'; $tagText = 'Done'; }
            else { $tagClass = 'tag-next'; $tagText = 'Upcoming'; }
          @endphp
          <div class="row">
            <div class="row-time">{{ $schedule->start_at->format('g:i') }} - {{ $schedule->end_at->format('g:i A') }}</div>
            <div class="row-main">
              <div class="row-title">{{ $subject }}</div>
              <div class="row-sub">{{ $schedule->classroom?->name ?? 'No room' }}@if($schedule->instructor) &middot; {{ $schedule->instructor->name }}@endif</div>
            </div>
            <span class="tag {{ $tagClass }}">{{ $tagText }}</span>
          </div>
        @empty
          <div class="empty">No classes scheduled today.</div>
        @endforelse
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Recent door activity</h2>
        <a class="panel-link" href="{{ route('accessLogs') }}">All logs</a>
      </div>
      <div class="list">
        @forelse ($recentLogs as $log)
          @php $granted = strtolower((string) $log->result) === 'granted'; @endphp
          <div class="row">
            <div class="row-main">
              <div class="row-title">{{ $log->user?->name ?? 'Unknown card' }}</div>
              <div class="row-sub">{{ $log->classroom?->name ?? 'Door' }} &middot; {{ optional($log->accessed_at)->diffForHumans() }}</div>
            </div>
            <span class="tag {{ $granted ? 'tag-ok' : 'tag-deny' }}">{{ $granted ? 'Granted' : 'Denied' }}</span>
          </div>
        @empty
          <div class="empty">No door activity yet.</div>
        @endforelse
      </div>
    </section>
  </div>

  <div class="grid-2">
    <section class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Upcoming reservations</h2>
      </div>
      <div class="list">
        @forelse ($upcomingReservations as $reservation)
          <div class="row">
            <div class="row-time">{{ $reservation->start_at->format('M j') }}<br>{{ $reservation->start_at->format('g:i') }} - {{ $reservation->end_at->format('g:i A') }}</div>
            <div class="row-main">
              <div class="row-title">{{ $reservation->classroom?->name ?? 'Room' }}</div>
              <div class="row-sub">{{ $reservation->user?->name ?? 'Unknown' }}@if($reservation->notes) &middot; {{ \Illuminate\Support\Str::limit($reservation->notes, 40) }}@endif</div>
            </div>
          </div>
        @empty
          <div class="empty">No upcoming reservations today.</div>
        @endforelse
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Quick actions</h2>
      </div>
      <div class="actions">
        <a class="action" href="{{ route('admin.users') }}"><i class="fas fa-users-cog"></i>User Management</a>
        <a class="action" href="{{ route('admin.schedule') }}"><i class="fas fa-calendar-days"></i>Schedule</a>
        <a class="action" href="{{ route('smartlocking.index') }}"><i class="fas fa-lock"></i>SmartLocking</a>
        <a class="action" href="{{ route('admin.notifications.create') }}"><i class="fas fa-bullhorn"></i>Announcement</a>
      </div>
      <div class="people">
        <div class="person"><b>{{ $stats['users_total'] }}</b><span>Users</span></div>
        <div class="person"><b>{{ $stats['faculty_total'] }}</b><span>Faculty</span></div>
        <div class="person"><b>{{ $stats['students_total'] }}</b><span>Students</span></div>
      </div>
    </section>
  </div>

</div>

<script>
(function () {
  var clock = document.getElementById('dashClock');
  function tick() {
    if (clock) clock.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }
  tick();
  setInterval(tick, 1000);
  // Keep the numbers fresh without user action.
  setTimeout(function () { window.location.reload(); }, 60000);
})();
</script>
</body>
</html>