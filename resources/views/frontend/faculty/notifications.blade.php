@php
    $facultyName = request()->user()?->name ?? 'Faculty';
    $facultyDept = request()->user()?->department ?? 'Faculty';
    $facultyInitials = strtoupper(substr((string) $facultyName, 0, 1));
    $notificationTotal = $notifications->count();
    $notificationUnread = $notifications->filter(fn ($notification) => $notification->read_at === null)->count();
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
        .main { min-height:100vh; margin-left:230px; padding:38px 44px 60px; }.page-header { display:flex; align-items:flex-end; justify-content:space-between; gap:24px; max-width:1080px; margin-bottom:24px; }.page-title { margin:0 0 5px; color:var(--navy); font:800 1.8rem 'Plus Jakarta Sans',sans-serif; }.page-subtitle { max-width:62ch; margin:0; color:var(--muted); font-size:.88rem; line-height:1.5; }.notification-summary { display:flex; flex:0 0 auto; gap:8px; }.summary-count { display:flex; align-items:center; gap:7px; min-height:38px; padding:8px 11px; border:1px solid var(--border); border-radius:9px; color:var(--muted); background:var(--white); font-size:.76rem; }.summary-count strong { color:var(--navy); font-size:.9rem; font-variant-numeric:tabular-nums; }.summary-count.unread-count { color:var(--navy); border-color:#ead88d; background:#fffbed; }
        .notifications-panel { overflow:hidden; max-width:1080px; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:0 4px 16px rgba(15,23,42,.06); }.panel-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:17px 22px; border-bottom:1px solid var(--border); }.panel-title { display:flex; align-items:center; gap:10px; color:var(--navy); font:700 1rem 'Plus Jakarta Sans',sans-serif; }.panel-icon { width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:8px; color:var(--navy); background:var(--yellow-light); }.panel-count { color:var(--muted); font-size:.76rem; font-variant-numeric:tabular-nums; }
        .notification-list { display:flex; flex-direction:column; }.notification-item { display:grid; grid-template-columns:9px minmax(0,1fr) auto; align-items:start; gap:14px; padding:20px 22px; border-bottom:1px solid var(--border); }.notification-item:last-child { border-bottom:0; }.notification-item.unread { background:#fffdf4; }.notification-dot { width:8px; height:8px; margin-top:6px; flex:0 0 8px; border-radius:50%; background:transparent; }.unread .notification-dot { background:var(--navy-mid); box-shadow:0 0 0 3px var(--navy-light); }.notification-content { min-width:0; }.notification-heading { display:flex; align-items:center; flex-wrap:wrap; gap:8px 12px; }.notification-title { color:var(--text); font-size:.92rem; font-weight:700; line-height:1.35; }.notification-body { margin-top:5px; color:#536176; font-size:.84rem; line-height:1.55; }.notification-time { margin-top:8px; color:#7b8798; font-size:.74rem; }.notification-type { align-self:start; padding:5px 8px; border:1px solid var(--border); border-radius:7px; color:#4c5a70; background:#f7f8fb; font-size:.68rem; font-weight:700; white-space:nowrap; }.unread-label { color:#755900; font-size:.68rem; font-weight:700; }.empty-state { padding:52px 24px; color:var(--muted); text-align:center; }.empty-state i { display:block; margin-bottom:12px; color:#9da8b8; font-size:1.7rem; }.empty-state strong { display:block; margin-bottom:5px; color:var(--text); font-size:.95rem; }
        .enrollment-request-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px 22px; margin-top:15px; padding:13px 0 0; border-top:1px solid var(--border); color:#536176; font-size:.8rem; }.enrollment-request-details strong { display:block; margin-bottom:3px; color:#59677b; font-size:.67rem; font-weight:700; text-transform:uppercase; }.enrollment-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:15px; }.enrollment-action { min-height:40px; padding:9px 14px; border:1px solid transparent; border-radius:7px; font:700 .8rem 'DM Sans',sans-serif; cursor:pointer; transition:background-color .16s ease,border-color .16s ease; }.enrollment-action:focus-visible,.mobile-menu summary:focus-visible,.mobile-menu-link:focus-visible { outline:3px solid #6785d4; outline-offset:2px; }.enrollment-action-approve { color:#126c4b; border-color:#bfe3d2; background:#edfaf3; }.enrollment-action-approve:hover { border-color:#80c8a7; background:#e0f5eb; }.enrollment-action-reject { color:#a33b3b; border-color:#efcfcc; background:#fff3f2; }.enrollment-action-reject:hover { border-color:#deaaa5; background:#fce8e6; }
        .mobile-nav { display:none; }.mobile-menu summary { display:flex; align-items:center; gap:8px; min-height:40px; padding:8px 11px; border:1px solid rgba(255,255,255,.24); border-radius:7px; color:#fff; cursor:pointer; font-size:.82rem; font-weight:700; list-style:none; }.mobile-menu summary::-webkit-details-marker { display:none; }.mobile-menu summary i { color:var(--yellow); }.mobile-menu { position:relative; }.mobile-menu-panel { position:absolute; top:calc(100% + 8px); right:0; z-index:30; display:grid; gap:3px; width:min(270px,calc(100vw - 32px)); padding:8px; border:1px solid var(--border); border-radius:9px; background:var(--white); box-shadow:0 10px 26px rgba(15,23,42,.16); }.mobile-menu-link { display:flex; align-items:center; gap:10px; min-height:42px; padding:9px 11px; border-radius:6px; color:var(--text); text-decoration:none; font-size:.84rem; }.mobile-menu-link i { width:18px; color:var(--navy-mid); text-align:center; }.mobile-menu-link:hover,.mobile-menu-link.active { color:var(--navy); background:var(--navy-light); }.mobile-brand { display:flex; align-items:center; gap:9px; color:#fff; text-decoration:none; font:700 .92rem 'Plus Jakarta Sans',sans-serif; }.mobile-brand .logo-mark { width:32px; height:32px; border-radius:8px; box-shadow:none; }
        @media (max-width:900px) { .main { padding:32px 24px 48px; }.page-header { align-items:flex-start; flex-direction:column; gap:14px; } }
        @media (max-width:768px) { .sidebar { display:none; }.mobile-nav { position:sticky; top:0; z-index:20; display:flex; align-items:center; justify-content:space-between; min-height:58px; padding:9px 16px; background:var(--navy); box-shadow:0 2px 8px rgba(15,23,42,.12); }.main { margin-left:0; padding:24px 16px 36px; }.page-header { margin-bottom:19px; }.page-title { font-size:1.55rem; }.notification-summary { width:100%; }.summary-count { flex:1; }.notifications-panel { border-radius:10px; }.panel-head { padding:14px 15px; }.notification-item { grid-template-columns:8px minmax(0,1fr); gap:11px; padding:16px 15px; }.notification-type { grid-column:2; grid-row:2; justify-self:start; }.enrollment-request-details { grid-template-columns:1fr 1fr; gap:11px 14px; }.enrollment-actions { display:grid; grid-template-columns:1fr 1fr; }.enrollment-actions form,.enrollment-action { width:100%; }.notification-heading { align-items:flex-start; flex-direction:column; gap:4px; } }
        @media (max-width:380px) { .enrollment-request-details { grid-template-columns:1fr; }.notification-item { gap:9px; padding-inline:12px; } }
        .notification-type[data-type="course_assignment"] { color:#273b70; border-color:#cbd5eb; background:#eef2fa; }
        .notification-type[data-type="enrollment_request"] { color:#755900; border-color:#ead88d; background:#fffbed; }
        .notification-type[data-type="enrollment_status"] { color:#126c4b; border-color:#bfe3d2; background:#edfaf3; }
        .mobile-menu-panel button.mobile-menu-link { width:100%; border:0; background:transparent; cursor:pointer; font:inherit; text-align:left; }
    </style>
</head>
<body>
<aside class="sidebar">
    <a href="{{ route('faculty.dashboard') }}" class="sidebar-logo"><div class="logo-mark"><i class="fas fa-door-open"></i></div><div class="logo-text"><span class="brand-psu">PSU</span><span class="brand-main">Smart<span>Door</span></span></div></a>
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
<nav class="mobile-nav" aria-label="Faculty navigation">
    <a href="{{ route('faculty.dashboard') }}" class="mobile-brand"><span class="logo-mark"><i class="fas fa-door-open" aria-hidden="true"></i></span><span>SmartRoom</span></a>
    <details id="notifications-mobile-menu" class="mobile-menu">
        <summary aria-label="Open faculty navigation"><i class="fas fa-bars" aria-hidden="true"></i>Menu</summary>
        <div class="mobile-menu-panel">
            <a class="mobile-menu-link" href="{{ route('faculty.dashboard') }}"><i class="fas fa-chart-line" aria-hidden="true"></i>Dashboard</a>
            <a class="mobile-menu-link" href="{{ route('faculty.rooms') }}"><i class="fas fa-door-open" aria-hidden="true"></i>Rooms</a>
            <a class="mobile-menu-link" href="{{ route('faculty.schedule') }}"><i class="fas fa-clock" aria-hidden="true"></i>Schedule</a>
            <a class="mobile-menu-link" href="{{ route('faculty.attendance') }}"><i class="fas fa-clipboard-check" aria-hidden="true"></i>Attendance</a>
            <a class="mobile-menu-link" href="{{ route('faculty.rfid.verification') }}"><i class="fas fa-id-card" aria-hidden="true"></i>RFID</a>
            <a class="mobile-menu-link active" href="{{ route('faculty.notifications') }}" aria-current="page"><i class="fas fa-bell" aria-hidden="true"></i>Notifications</a>
            <a class="mobile-menu-link" href="{{ route('faculty.reports') }}"><i class="fas fa-chart-bar" aria-hidden="true"></i>Reports</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mobile-menu-link"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>Sign out</button>
            </form>
        </div>
    </details>
</nav>
<main class="main" data-notifications-url="{{ route('faculty.notifications.data') }}">
    <header class="page-header">
        <div>
            <h1 class="page-title">Notifications</h1>
            <p class="page-subtitle">Review course assignments and enrollment requests alongside your latest faculty updates.</p>
        </div>
        <div class="notification-summary" aria-label="Notification summary">
            <span class="summary-count unread-count" aria-label="{{ $notificationUnread }} unread notifications"><strong>{{ $notificationUnread }}</strong> unread</span>
            <span class="summary-count" aria-label="{{ $notificationTotal }} total notifications"><strong>{{ $notificationTotal }}</strong> total</span>
        </div>
    </header>
    @if (session('success'))<div class="feedback-message feedback-success" role="status">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="feedback-message feedback-error" role="alert">{{ session('error') }}</div>@endif
    <section class="notifications-panel" aria-labelledby="notificationsTitle">
        <div class="panel-head"><div class="panel-title"><span class="panel-icon"><i class="fas fa-bell" aria-hidden="true"></i></span><span id="notificationsTitle">Recent activity</span></div><span class="panel-count">Newest first</span></div>
        @if ($notifications->isEmpty())
            <div class="empty-state"><i class="fas fa-bell-slash"></i><strong>No notifications yet</strong><span>New faculty updates will appear here.</span></div>
        @else
            <div class="notification-list">
                @foreach ($notifications as $notification)
                    @php($enrollmentRequest = $pendingEnrollmentRequests->get((int) data_get($notification->data, 'enrollment_id')))
                    @php($notificationLabel = match ($notification->type) { 'course_assignment' => 'Course assignment', 'enrollment_request' => 'Enrollment request', 'enrollment_status' => 'Enrollment update', default => ucwords(str_replace('_', ' ', $notification->type ?: 'Update')) })
                    @php($notificationIcon = match ($notification->type) { 'course_assignment' => 'fa-book-open', 'enrollment_request' => 'fa-user-plus', 'enrollment_status' => 'fa-user-check', default => 'fa-bell' })
                    <article class="notification-item {{ $notification->read_at ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
                        <span class="notification-dot" aria-hidden="true"></span>
                        <div class="notification-content">
                            <div class="notification-heading"><span class="notification-title">{{ $notification->title }}</span>@if (!$notification->read_at)<span class="unread-label">Unread</span>@endif</div>
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
                        <span class="notification-type" data-type="{{ $notification->type }}"><i class="fas {{ $notificationIcon }}" aria-hidden="true"></i>{{ $notificationLabel }}</span>
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
