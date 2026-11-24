<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Overview – SmartRoom</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root {
  --navy: #0b1640;
  --navy-mid: #1a2f80;
  --gold: #f5c518;
  --bg: #f6f8fc;
  --card: #fff;
  --line: #e6ebf3;
  --text: #0b1640;
  --muted: #74809b;
  --green: #15803d;
  --red: #b91c1c;
  --sidebar-w: 252px;
}
body { margin: 0; min-height: 100vh; display: flex; background: var(--bg); color: var(--text); font-family: Inter, sans-serif; }
.main { display: flex; flex: 1; min-width: 0; flex-direction: column; gap: 24px; margin-left: var(--sidebar-w); padding: 34px 40px 48px; }
.page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; flex-wrap: wrap; }
.eyebrow { margin-bottom: 6px; color: var(--muted); font-size: .7rem; font-weight: 750; letter-spacing: .15em; text-transform: uppercase; }
h1 { margin: 0; font-size: 1.75rem; font-weight: 800; letter-spacing: -.035em; }
.subtitle { margin: 7px 0 0; color: var(--muted); font-size: .88rem; }
.secure-label { display: inline-flex; align-items: center; gap: 8px; padding: 9px 13px; border: 1px solid #e8dfb8; border-radius: 999px; background: #fffdf3; color: #806600; font-size: .74rem; font-weight: 750; }
.secure-label i { color: #b38b00; }
.stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
.stat { min-width: 0; padding: 18px; border: 1px solid var(--line); border-radius: 15px; background: var(--card); box-shadow: 0 2px 8px rgba(11,22,64,.035); }
.stat-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.stat-label { color: var(--muted); font-size: .68rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
.stat-icon { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border-radius: 10px; background: #eef2ff; color: var(--navy-mid); }
.stat-icon.gold { background: #fff9db; color: #a17a00; }
.stat-value { margin-top: 15px; font-size: 1.8rem; font-weight: 800; letter-spacing: -.04em; line-height: 1; }
.stat-detail { margin-top: 7px; color: var(--muted); font-size: .73rem; }
.grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 16px; }
.panel { min-width: 0; padding: 21px 22px; border: 1px solid var(--line); border-radius: 16px; background: var(--card); box-shadow: 0 2px 8px rgba(11,22,64,.035); }
.panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 17px; }
.panel-title { margin: 0; font-size: .96rem; font-weight: 800; }
.panel-caption { margin: 4px 0 0; color: var(--muted); font-size: .75rem; }
.text-link { color: var(--navy-mid); font-size: .76rem; font-weight: 750; text-decoration: none; white-space: nowrap; }
.text-link:hover { text-decoration: underline; }
.admin-list, .log-list { display: flex; flex-direction: column; }
.admin-row, .log-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid #edf0f6; }
.admin-row:first-child, .log-row:first-child { padding-top: 0; border-top: 0; }
.admin-row:last-child, .log-row:last-child { padding-bottom: 0; }
.avatar { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border: 1px solid #dfe5f1; border-radius: 12px; background: #f4f6fb; color: var(--navy-mid); font-size: .78rem; font-weight: 800; }
.row-main { flex: 1; min-width: 0; }
.row-name { overflow: hidden; font-size: .81rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
.row-sub { overflow: hidden; margin-top: 3px; color: var(--muted); font-size: .71rem; text-overflow: ellipsis; white-space: nowrap; }
.pills { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.pill { padding: 4px 8px; border: 1px solid #dbe3f5; border-radius: 999px; background: #f5f7fc; color: #52617f; font-size: .63rem; font-weight: 800; white-space: nowrap; }
.pill.super { border-color: #f0df92; background: #fff9dc; color: #806600; }
.pill.active { border-color: #bbf7d0; background: #f0fdf4; color: var(--green); }
.pill.off { border-color: #fecaca; background: #fef2f2; color: var(--red); }
.access-icon { display: grid; width: 32px; height: 32px; flex: 0 0 32px; place-items: center; border-radius: 10px; background: #f3f5fa; color: #75809a; }
.access-icon.denied { background: #fff1f2; color: var(--red); }
.log-time { color: var(--muted); font-size: .67rem; white-space: nowrap; }
.actions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.action { display: flex; min-height: 72px; align-items: center; gap: 10px; padding: 12px; border: 1px solid var(--line); border-radius: 12px; color: var(--text); font-size: .75rem; font-weight: 750; text-decoration: none; transition: border-color .15s, transform .15s, background-color .15s; }
.action i { display: grid; width: 32px; height: 32px; flex: 0 0 32px; place-items: center; border-radius: 9px; background: #eef2ff; color: var(--navy-mid); }
.action:hover { transform: translateY(-1px); border-color: #b7c4e3; background: #fbfcff; }
.empty { padding: 20px 0 4px; color: var(--muted); font-size: .8rem; text-align: center; }
@media (max-width: 1250px) { .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 1000px) { .grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { :root { --sidebar-w: 0px; } .main { gap: 18px; padding: 24px 16px 36px; } h1 { font-size: 1.45rem; } .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; } .stat { padding: 14px; } .stat-value { font-size: 1.55rem; } .panel { padding: 17px; } .actions { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 390px) { .stats { grid-template-columns: 1fr; } .actions { grid-template-columns: 1fr; } .pills { max-width: 96px; } }
@media (prefers-reduced-motion: reduce) { .action { transition: none; } }
</style>
@include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>
@include('frontend.admin.partials.sidebar')

<main class="main">
  <header class="page-head">
    <div>
      <div class="eyebrow">{{ now()->format('l, F j, Y') }} · System administration</div>
      <h1>System Overview</h1>
      <p class="subtitle">Governance, access and campus operations at a glance.</p>
    </div>
    <span class="secure-label"><i class="fas fa-shield-halved" aria-hidden="true"></i> Super administrator</span>
  </header>

  <section class="stats" aria-label="System summary">
    <article class="stat">
      <div class="stat-top"><span class="stat-label">Total users</span><span class="stat-icon"><i class="fas fa-users" aria-hidden="true"></i></span></div>
      <div class="stat-value">{{ number_format($stats['users']) }}</div>
      <div class="stat-detail">{{ number_format($stats['active_users']) }} active accounts</div>
    </article>
    <article class="stat">
      <div class="stat-top"><span class="stat-label">Administrators</span><span class="stat-icon gold"><i class="fas fa-user-shield" aria-hidden="true"></i></span></div>
      <div class="stat-value">{{ number_format($stats['administrators'] + $stats['super_administrators']) }}</div>
      <div class="stat-detail">{{ number_format($stats['super_administrators']) }} super-admin accounts</div>
    </article>
    <article class="stat">
      <div class="stat-top"><span class="stat-label">Classrooms</span><span class="stat-icon"><i class="fas fa-school" aria-hidden="true"></i></span></div>
      <div class="stat-value">{{ number_format($stats['classrooms']) }}</div>
      <div class="stat-detail">Campus rooms in SmartRoom</div>
    </article>
    <article class="stat">
      <div class="stat-top"><span class="stat-label">Active devices</span><span class="stat-icon"><i class="fas fa-microchip" aria-hidden="true"></i></span></div>
      <div class="stat-value">{{ number_format($stats['active_devices']) }} <small style="font-size:.82rem;color:#74809b;font-weight:650;">/ {{ number_format($stats['devices']) }}</small></div>
      <div class="stat-detail">Devices marked active</div>
    </article>
    <article class="stat">
      <div class="stat-top"><span class="stat-label">Door access today</span><span class="stat-icon gold"><i class="fas fa-door-open" aria-hidden="true"></i></span></div>
      <div class="stat-value">{{ number_format($stats['access_today']) }}</div>
      <div class="stat-detail">{{ number_format($stats['denied_today']) }} denied · {{ number_format($stats['active_cards']) }} active cards</div>
    </article>
  </section>

  <section class="grid">
    <article class="panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Administrator access</h2>
          <p class="panel-caption">Review privileged accounts and their current status.</p>
        </div>
        <a class="text-link" href="{{ route('admin.users') }}">Manage users <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="admin-list">
        @if ($administrators->isEmpty())
          <p class="empty">No administrator accounts have been created.</p>
        @else
          @foreach ($administrators as $administrator)
          @php
            $initials = collect(explode(' ', trim($administrator->name)))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
                ->join('');
            $active = strtolower((string) ($administrator->status ?? 'active')) === 'active';
          @endphp
          <div class="admin-row">
            <span class="avatar" aria-hidden="true">{{ $initials ?: 'A' }}</span>
            <div class="row-main">
              <div class="row-name">{{ $administrator->name }}</div>
              <div class="row-sub">{{ $administrator->email }}</div>
            </div>
            <div class="pills">
              <span class="pill {{ $administrator->role === 'super_admin' ? 'super' : '' }}">{{ $administrator->role === 'super_admin' ? 'Super Admin' : 'Admin' }}</span>
              <span class="pill {{ $active ? 'active' : 'off' }}">{{ $active ? 'Active' : ucfirst((string) $administrator->status) }}</span>
              <a class="text-link" href="{{ route('admin.users.edit', $administrator) }}" aria-label="Manage {{ $administrator->name }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
            </div>
          </div>
          @endforeach
        @endif
      </div>
    </article>

    <article class="panel">
      <div class="panel-head">
        <div>
          <h2 class="panel-title">Recent door activity</h2>
          <p class="panel-caption">Latest recorded access attempts.</p>
        </div>
        <a class="text-link" href="{{ route('accessLogs') }}">View logs <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="log-list">
        @if ($recentAccessLogs->isEmpty())
          <p class="empty">No door activity has been recorded yet.</p>
        @else
          @foreach ($recentAccessLogs as $log)
            @php
              $granted = strtolower((string) $log->result) === 'granted';
            @endphp
            <div class="log-row">
              <span class="access-icon {{ $granted ? '' : 'denied' }}"><i class="fas {{ $granted ? 'fa-check' : 'fa-ban' }}" aria-hidden="true"></i></span>
              <div class="row-main">
                <div class="row-name">{{ $log->user?->name ?? 'Unknown user' }}</div>
                <div class="row-sub">{{ $log->classroom?->name ?? 'Unknown room' }} · {{ ucfirst((string) $log->direction) }} · {{ ucfirst((string) $log->result) }}</div>
              </div>
              <time class="log-time">{{ $log->accessed_at?->diffForHumans() ?? 'Unknown time' }}</time>
            </div>
          @endforeach
        @endif
      </div>
    </article>
  </section>

  <section class="panel">
    <div class="panel-head">
      <div>
        <h2 class="panel-title">Recent account changes</h2>
        <p class="panel-caption">Sensitive account actions recorded by SmartRoom.</p>
      </div>
      <span class="pill">{{ $recentAdminChanges->count() }} recent</span>
    </div>
    <div class="admin-list">
      @if ($recentAdminChanges->isEmpty())
        <p class="empty">Account changes will appear here as administrators manage users.</p>
      @else
        @foreach ($recentAdminChanges as $change)
        @php
          $targetName = data_get($change->metadata, 'target_name') ?? $change->target?->name ?? 'an account';
          $actorName = $change->actor?->name ?? data_get($change->metadata, 'actor_name', 'System setup');
          $summary = match ($change->action) {
            'administrator.created' => 'Created an administrator account',
            'user.created' => 'Created a user account',
            'user.profile_updated' => 'Updated account details',
            'user.role_changed' => 'Changed an account role',
            'user.status_changed' => 'Changed an account status',
            'password.reset' => 'Reset an account password',
            'user.deleted' => 'Deleted a user account',
            default => 'Updated an account',
          };
        @endphp
        <div class="admin-row">
          <span class="access-icon"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></span>
          <div class="row-main">
            <div class="row-name">{{ $summary }}</div>
            <div class="row-sub">{{ $targetName }} · by {{ $actorName }}</div>
          </div>
          <time class="log-time">{{ $change->created_at?->diffForHumans() ?? 'Unknown time' }}</time>
        </div>
        @endforeach
      @endif
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <div>
        <h2 class="panel-title">System controls</h2>
        <p class="panel-caption">Shortcuts to SmartRoom administration areas.</p>
      </div>
    </div>
    <nav class="actions" aria-label="System controls">
      <a class="action" href="{{ route('admin.users') }}"><i class="fas fa-user-gear" aria-hidden="true"></i><span>User &amp; admin access</span></a>
      <a class="action" href="{{ route('admin.classrooms') }}"><i class="fas fa-school" aria-hidden="true"></i><span>Rooms &amp; devices</span></a>
      <a class="action" href="{{ route('admin.schedule') }}"><i class="fas fa-calendar-days" aria-hidden="true"></i><span>Schedule</span></a>
      <a class="action" href="{{ route('smartlocking.index') }}"><i class="fas fa-id-card" aria-hidden="true"></i><span>SmartLocking</span></a>
      <a class="action" href="{{ route('admin.notifications.create') }}"><i class="fas fa-bullhorn" aria-hidden="true"></i><span>Announcements</span></a>
      <a class="action" href="{{ route('admin.instructor-attendance') }}"><i class="fas fa-chart-column" aria-hidden="true"></i><span>Faculty reports</span></a>
    </nav>
  </section>
</main>
</body>
</html>
