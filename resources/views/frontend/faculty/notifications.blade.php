@php
    $facultyName = request()->user()?->name ?? 'Faculty';
    $facultyDept = request()->user()?->department ?? 'Faculty';
    $facultyInitials = strtoupper(substr((string) $facultyName, 0, 1));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SmartDoor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --navy:#0b1640; --navy-mid:#1a2f80; --yellow:#f5c518; --bg:#f4f6fb; --white:#fff; --text:#101828; --muted:#718096; --border:#e3e8f1; --blue:#2563eb; --blue-bg:#eff6ff; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:var(--bg); color:var(--text); font-family:'DM Sans',sans-serif; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:230px; height:100vh; display:flex; flex-direction:column; overflow:hidden; z-index:10; background:var(--navy); color:#fff; }
        .sidebar::before { content:''; position:absolute; inset:0; background:linear-gradient(160deg,rgba(245,197,24,.06),transparent 55%); pointer-events:none; }
        .sidebar-logo { position:relative; z-index:1; display:flex; align-items:center; gap:12px; padding:28px 20px 24px 24px; color:#fff; text-decoration:none; border-bottom:1px solid rgba(255,255,255,.06); }
        .logo-mark { width:40px; height:40px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:12px; background:var(--yellow); color:var(--navy); box-shadow:0 4px 12px rgba(245,197,24,.35); }
        .logo-text { line-height:1; }.brand-psu { display:block; margin-bottom:3px; color:rgba(255,255,255,.45); font-size:.6rem; font-weight:600; letter-spacing:.18em; text-transform:uppercase; }.brand-main { font-size:1.05rem; font-weight:700; }.brand-main span { color:var(--yellow); }
        .nav-section-label { position:relative; z-index:1; display:block; padding:16px 24px 6px; color:rgba(255,255,255,.25); font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .sidebar-nav { position:relative; z-index:1; list-style:none; padding:0 12px; margin:0; }.sidebar-nav li { margin-bottom:2px; }.sidebar-nav a { display:flex; align-items:center; gap:11px; padding:11px 12px; color:rgba(255,255,255,.6); border-radius:10px; text-decoration:none; font-size:.88rem; font-weight:500; }.sidebar-nav a:hover { background:rgba(255,255,255,.06); color:#fff; }.sidebar-nav a.active { background:rgba(245,197,24,.14); color:var(--yellow); }.nav-icon { width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,.05); }.sidebar-nav a.active .nav-icon { background:rgba(245,197,24,.2); }
        .sidebar-footer { position:relative; z-index:1; margin-top:auto; padding:16px 12px 24px; border-top:1px solid rgba(255,255,255,.06); }.user-widget { display:flex; align-items:center; gap:10px; padding:10px 12px; margin-bottom:8px; border-radius:10px; background:rgba(255,255,255,.05); }.user-avatar { width:34px; height:34px; display:flex; align-items:center; justify-content:center; border:2px solid rgba(245,197,24,.4); border-radius:50%; color:var(--yellow); background:var(--navy-mid); font-size:.78rem; font-weight:700; }.user-widget-info { min-width:0; }.user-widget-name { overflow:hidden; color:#fff; font-size:.83rem; font-weight:600; text-overflow:ellipsis; white-space:nowrap; }.user-widget-role { color:rgba(255,255,255,.4); font-size:.73rem; }.sidebar-logout-btn { display:flex; align-items:center; gap:10px; width:100%; padding:9px 12px; border:0; border-radius:10px; color:rgba(255,255,255,.4); background:none; cursor:pointer; font:500 .84rem 'DM Sans',sans-serif; text-align:left; }.sidebar-logout-btn:hover { color:#f87171; background:rgba(244,63,94,.08); }
        .main { min-height:100vh; margin-left:230px; padding:42px 44px 60px; }.page-header { margin-bottom:24px; }.eyebrow { color:var(--blue); font-size:.72rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }.page-title { margin:5px 0 6px; font:800 1.7rem 'Plus Jakarta Sans',sans-serif; }.page-subtitle { margin:0; color:var(--muted); font-size:.88rem; }
        .notifications-panel { overflow:hidden; max-width:960px; border:1px solid var(--border); border-radius:16px; background:var(--white); box-shadow:0 8px 24px rgba(15,23,42,.05); }.panel-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px; border-bottom:1px solid var(--border); }.panel-title { display:flex; align-items:center; gap:10px; font:700 1rem 'Plus Jakarta Sans',sans-serif; }.panel-icon { width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:9px; color:var(--blue); background:var(--blue-bg); }.panel-count { color:var(--muted); font-size:.76rem; }
        .notification-list { display:flex; flex-direction:column; }.notification-item { display:flex; align-items:flex-start; gap:14px; padding:18px 24px; border-bottom:1px solid var(--border); }.notification-item:last-child { border-bottom:0; }.notification-item.unread { background:#f8fbff; }.notification-dot { width:9px; height:9px; margin-top:7px; flex:0 0 9px; border-radius:50%; background:transparent; }.unread .notification-dot { background:var(--blue); box-shadow:0 0 0 4px #dbeafe; }.notification-content { min-width:0; flex:1; }.notification-title { font-size:.9rem; font-weight:700; }.notification-body { margin-top:5px; color:#536176; font-size:.82rem; line-height:1.5; }.notification-time { margin-top:8px; color:#9aa6b8; font-size:.72rem; }.notification-type { flex:0 0 auto; padding:4px 8px; border:1px solid var(--border); border-radius:999px; color:var(--muted); font-size:.66rem; font-weight:700; text-transform:capitalize; }.empty-state { padding:56px 24px; color:var(--muted); text-align:center; }.empty-state i { display:block; margin-bottom:12px; color:#c7d2e3; font-size:2rem; }.empty-state strong { display:block; margin-bottom:5px; color:var(--text); font-size:.95rem; }
        .enrollment-request-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 18px; margin-top:14px; padding:12px; border:1px solid var(--border); border-radius:10px; background:#fbfcff; color:#536176; font-size:.78rem; }.enrollment-request-details strong { display:block; margin-bottom:2px; color:var(--muted); font-size:.66rem; text-transform:uppercase; }.enrollment-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }.enrollment-action { min-height:36px; padding:8px 12px; border:1px solid transparent; border-radius:8px; font:700 .78rem 'DM Sans',sans-serif; cursor:pointer; }.enrollment-action-approve { color:#126c4b; border-color:#bfe3d2; background:#edfaf3; }.enrollment-action-reject { color:#a33b3b; border-color:#efcfcc; background:#fff3f2; }
        @media (max-width:768px) { .sidebar { display:none; }.main { margin-left:0; padding:28px 16px 40px; }.panel-head,.notification-item { padding-left:16px; padding-right:16px; }.notification-type { display:none; } }
    </style>
</head>
<body>
<aside class="sidebar">
    <a href="{{ url('/dashboard') }}" class="sidebar-logo"><div class="logo-mark"><i class="fas fa-door-open"></i></div><div class="logo-text"><span class="brand-psu">PSU</span><span class="brand-main">Smart<span>Door</span></span></div></a>
    <span class="nav-section-label">Main Menu</span>
    <ul class="sidebar-nav">
        <li><a href="{{ url('/faculty_dashboard') }}" class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-line"></i></span>Dashboard</a></li>
        <li><a href="{{ url('/rooms') }}" class="{{ Request::is('rooms*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-door-open"></i></span>Rooms</a></li>
        <li><a href="{{ url('/faculty-schedule') }}" class="{{ Request::is('faculty-schedule') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clock"></i></span>Schedule</a></li>
        <li><a href="{{ url('/attendance') }}" class="{{ Request::is('attendance*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance</a></li>
    </ul>
    <span class="nav-section-label">Tools</span>
    <ul class="sidebar-nav">
        <li><a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID</a></li>
        <li><a href="{{ route('faculty.notifications') }}" class="active"><span class="nav-icon"><i class="fas fa-bell"></i></span>Notifications</a></li>
        <li><a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports</a></li>
    </ul>
    <div class="sidebar-footer"><div class="user-widget"><div class="user-avatar">{{ htmlspecialchars($facultyInitials) }}</div><div class="user-widget-info"><div class="user-widget-name">{{ htmlspecialchars($facultyName) }}</div><div class="user-widget-role">{{ htmlspecialchars($facultyDept) }}</div></div></div><form method="POST" action="{{ url('/logout') }}">@csrf<button type="submit" class="sidebar-logout-btn"><i class="fas fa-arrow-right-from-bracket"></i>Sign Out</button></form></div>
</aside>
<main class="main" data-notifications-url="{{ route('faculty.notifications.data') }}">
    <header class="page-header"><div class="eyebrow">Faculty workspace</div><h1 class="page-title">Notifications</h1><p class="page-subtitle">Review enrollment requests and stay up to date with room, schedule, and attendance updates.</p></header>
    @if (session('success'))<div role="status" style="max-width:960px;margin:0 0 18px;padding:12px 16px;border:1px solid #bfe3d2;border-radius:10px;color:#126c4b;background:#edfaf3;font-size:.84rem;font-weight:600">{{ session('success') }}</div>@endif
    <section class="notifications-panel" aria-labelledby="notificationsTitle">
        <div class="panel-head"><div class="panel-title"><span class="panel-icon"><i class="fas fa-bell"></i></span><span id="notificationsTitle">All notifications</span></div><span class="panel-count">{{ $notifications->count() }} total</span></div>
        @if ($notifications->isEmpty())
            <div class="empty-state"><i class="fas fa-bell-slash"></i><strong>No notifications yet</strong><span>New faculty updates will appear here.</span></div>
        @else
            <div class="notification-list">
                @foreach ($notifications as $notification)
                    @php($enrollmentRequest = $pendingEnrollmentRequests->get((int) data_get($notification->data, 'enrollment_id')))
                    <article class="notification-item {{ $notification->read_at ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
                        <span class="notification-dot" aria-hidden="true"></span>
                        <div class="notification-content">
                            <div class="notification-title">{{ $notification->title }}</div>
                            <div class="notification-body">{{ $notification->body ?: 'No additional details.' }}</div>
                            <div class="notification-time">{{ $notification->created_at?->diffForHumans() ?? 'Recently' }}</div>
                            @if ($notification->type === 'enrollment_request' && $enrollmentRequest)
                                <div class="enrollment-request-details">
                                    <div><strong>Student</strong>{{ $enrollmentRequest->student->name }}</div>
                                    <div><strong>Student ID</strong>{{ $enrollmentRequest->student->student_id }}</div>
                                    <div><strong>Email</strong>{{ $enrollmentRequest->student->email }}</div>
                                    <div><strong>Course</strong>{{ $enrollmentRequest->course->code }} - {{ $enrollmentRequest->course->title }}</div>
                                </div>
                                <div class="enrollment-actions">
                                    <form method="POST" action="{{ route('faculty.enrollment-requests.approve', $enrollmentRequest) }}">
                                        @csrf
                                        <button type="submit" class="enrollment-action enrollment-action-approve"><i class="fas fa-check me-1" aria-hidden="true"></i>Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('faculty.enrollment-requests.reject', $enrollmentRequest) }}">
                                        @csrf
                                        <button type="submit" class="enrollment-action enrollment-action-reject"><i class="fas fa-xmark me-1" aria-hidden="true"></i>Reject</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        @if ($notification->type)<span class="notification-type">{{ $notification->type }}</span>@endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</main>
<script>
(() => {
    const endpoint = document.querySelector('[data-notifications-url]').dataset.notificationsUrl;
    const notificationItems = Array.from(document.querySelectorAll('[data-notification-id]'));
    const newestSeenId = notificationItems.reduce((latest, item) => {
        return Math.max(latest, Number(item.dataset.notificationId) || 0);
    }, 0);

    async function refreshWhenNotificationsChange() {
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) return;

            const payload = await response.json();
            const newestId = (payload.data || []).reduce((latest, item) => {
                return Math.max(latest, Number(item.id) || 0);
            }, 0);

            if (newestId > newestSeenId) window.location.reload();
        } catch {
            // Leave the current notification list visible if polling is temporarily unavailable.
        }
    }

    window.setInterval(refreshWhenNotificationsChange, 10000);
})();
</script>
</body>
</html>
