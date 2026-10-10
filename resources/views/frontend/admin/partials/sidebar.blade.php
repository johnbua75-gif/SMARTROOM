@php
    $adminUser = auth()->user();
    $adminName = $adminUser?->name ?? 'Admin User';
    $adminRole = ucfirst((string) ($adminUser?->role ?? 'admin'));
    $adminInitials = collect(explode(' ', trim($adminName)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->join('');
    $currentPath = request()->path();
    $dashboardActive = in_array($currentPath, ['dashboard', 'admin/dashboard'], true);
    $roomsActive = request()->is('admin/classrooms*');
    $scheduleActive = request()->is('admin/schedule*');
    $usersActive = request()->is('admin/users*');
    $smartLockingActive = request()->is('smartlocking*') || request()->is('admin/accessLogs*') || request()->is('admin/access-logs*');
    $announcementsActive = request()->is('admin/notifications*');
@endphp
<style>
:root { --sidebar-w: 252px; }
.admin-sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 120;
    display: flex;
    flex: 0 0 252px;
    flex-direction: column;
    width: 252px;
    height: 100vh;
    padding: 0;
    overflow: hidden;
    border-right: 1px solid rgba(245,197,24,.18);
    background: linear-gradient(180deg,#0b1640 0%,#112060 100%);
    box-shadow: 1px 0 12px rgba(11,22,64,.14);
    color: #fff;
}
.admin-sidebar .sidebar-logo {
    display: flex;
    align-items: center;
    gap: 11px;
    min-height: 82px;
    padding: 18px 20px;
    margin: 0 0 12px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    color: #fff;
    text-decoration: none;
}
.admin-sidebar .logo-mark {
    display: flex;
    flex: 0 0 42px;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    overflow: hidden;
    background: #f5c518;
}
.admin-sidebar .admin-sidebar-logo-img { display: block; width: 100%; height: 100%; object-fit: cover; }
.admin-sidebar .logo-text { display: grid; gap: 3px; line-height: 1; }
.admin-sidebar .brand-psu { color: rgba(255,255,255,.56); font-size: .58rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
.admin-sidebar .brand-main { color: #fff; font-size: .98rem; font-weight: 750; }
.admin-sidebar .brand-main span { color: #f5c518; }
.admin-sidebar .nav-section-label { padding: 14px 22px 8px; color: rgba(255,255,255,.46); font-size: .66rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
.admin-sidebar .sidebar-nav { display: grid; gap: 4px; padding: 0 10px; margin: 0; overflow-y: auto; list-style: none; }
.admin-sidebar .sidebar-nav li { margin: 0; }
.admin-sidebar .sidebar-nav a {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    min-height: 44px;
    padding: 7px 11px;
    border-radius: 6px;
    color: rgba(255,255,255,.75);
    font-size: .84rem;
    font-weight: 550;
    text-decoration: none;
    transition: background-color .16s ease,color .16s ease;
}
.admin-sidebar .sidebar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
.admin-sidebar .sidebar-nav a:focus-visible,
.admin-sidebar .sidebar-logout-btn:focus-visible,
.admin-sidebar-toggle:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
.admin-sidebar .sidebar-nav a.active { background: #303748; color: #f5c518; }
.admin-sidebar .sidebar-nav a.active::before { position: absolute; inset: 9px auto 9px 0; width: 3px; border-radius: 0 2px 2px 0; background: #f5c518; content: ''; }
.admin-sidebar .nav-icon { display: flex; flex: 0 0 30px; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; background: rgba(255,255,255,.06); font-size: .82rem; }
.admin-sidebar .sidebar-nav a.active .nav-icon { background: rgba(245,197,24,.13); color: #f5c518; }
.admin-sidebar .sidebar-footer { display: grid; gap: 8px; padding: 12px 10px 14px; margin-top: auto; border-top: 1px solid rgba(245,197,24,.18); }
.admin-sidebar .user-widget { display: flex; align-items: center; gap: 10px; min-width: 0; padding: 9px; border-radius: 6px; background: rgba(255,255,255,.055); }
.admin-sidebar-user-avatar { display: flex; flex: 0 0 34px; align-items: center; justify-content: center; width: 34px; height: 34px; border: 1px solid rgba(245,197,24,.45); border-radius: 50%; background: #1a2f80; color: #f5c518; font-size: .7rem; font-weight: 750; }
.admin-sidebar .user-widget-info { min-width: 0; }
.admin-sidebar .user-widget-name { overflow: hidden; color: #fff; font-size: .78rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
.admin-sidebar .user-widget-role { margin-top: 2px; color: rgba(255,255,255,.55); font-size: .68rem; }
.admin-sidebar .sidebar-logout-btn { display: flex; align-items: center; gap: 10px; width: 100%; min-height: 40px; padding: 8px 10px; border: 0; border-radius: 6px; background: transparent; color: rgba(255,255,255,.72); cursor: pointer; font-family: inherit; font-size: .82rem; font-weight: 500; text-align: left; }
.admin-sidebar .sidebar-logout-btn:hover { background: rgba(248,113,113,.1); color: #fecaca; }
.admin-sidebar ~ .main-content { margin-left: 252px; }
.admin-sidebar-toggle,
.admin-sidebar-backdrop { display: none; }
@media (max-width: 768px) {
    :root { --sidebar-w: 0px; }
    .admin-sidebar { z-index: 151; width: min(280px, calc(100vw - 48px)); transform: translateX(-102%); transition: transform .2s ease; }
    body.admin-sidebar-open .admin-sidebar { transform: translateX(0); }
    .admin-sidebar-toggle { position: fixed; top: 12px; left: 12px; z-index: 150; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; border: 1px solid #dbe3f5; border-radius: 7px; background: #fff; color: #0b1640; box-shadow: 0 2px 8px rgba(11,22,64,.12); cursor: pointer; }
    .admin-sidebar-backdrop { position: fixed; inset: 0; z-index: 150; display: block; background: rgba(11,22,64,.38); opacity: 0; pointer-events: none; transition: opacity .2s ease; }
    body.admin-sidebar-open .admin-sidebar-backdrop { opacity: 1; pointer-events: auto; }
    .admin-sidebar-toggle ~ .main,
    .admin-sidebar-toggle ~ .main-content { padding-top: 64px !important; }
    .main-content { margin-left: 0 !important; }
}
@media (prefers-reduced-motion: reduce) {
    .admin-sidebar, .admin-sidebar-backdrop, .admin-sidebar .sidebar-nav a { transition: none; }
}
</style>
<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
    <i class="fas fa-bars" aria-hidden="true"></i>
</button>
<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>
<aside class="sidebar admin-sidebar" id="adminSidebar" aria-label="Administrator navigation">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
        <span class="logo-mark"><img class="admin-sidebar-logo-img" src="{{ asset('images/logo.png') }}" alt=""></span>
        <span class="logo-text"><span class="brand-psu">PSU</span><span class="brand-main">Smart<span>Room</span></span></span>
    </a>
    <span class="nav-section-label">Main Menu</span>
    <ul class="sidebar-nav">
        <li><a href="{{ route('admin.dashboard') }}" @class(['active' => $dashboardActive])><span class="nav-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span><span>Dashboard</span></a></li>
        <li><a href="{{ route('admin.classrooms') }}" @class(['active' => $roomsActive])><span class="nav-icon"><i class="fas fa-school" aria-hidden="true"></i></span><span>Room Management</span></a></li>
        <li><a href="{{ route('admin.schedule') }}" @class(['active' => $scheduleActive])><span class="nav-icon"><i class="fas fa-calendar-days" aria-hidden="true"></i></span><span>Schedule</span></a></li>
        <li><a href="{{ route('admin.users') }}" @class(['active' => $usersActive])><span class="nav-icon"><i class="fas fa-users-cog" aria-hidden="true"></i></span><span>User Management</span></a></li>
        <li><a href="{{ route('smartlocking.index') }}" @class(['active' => $smartLockingActive])><span class="nav-icon"><i class="fas fa-lock" aria-hidden="true"></i></span><span>SmartLocking</span></a></li>
        <li><a href="{{ route('admin.notifications.create') }}" @class(['active' => $announcementsActive])><span class="nav-icon"><i class="fas fa-bullhorn" aria-hidden="true"></i></span><span>Announcements</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="user-widget">
            <span class="admin-sidebar-user-avatar" aria-hidden="true">{{ $adminInitials ?: 'A' }}</span>
            <div class="user-widget-info"><div class="user-widget-name">{{ $adminName }}</div><div class="user-widget-role">{{ $adminRole }}</div></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout-btn"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Sign Out</span></button>
        </form>
    </div>
</aside>
<script>
(function () {
    const toggle = document.getElementById('adminSidebarToggle');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    if (!toggle || !backdrop) return;

    function setOpen(isOpen) {
        document.body.classList.toggle('admin-sidebar-open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    }

    toggle.addEventListener('click', function () {
        setOpen(!document.body.classList.contains('admin-sidebar-open'));
    });
    backdrop.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen(false);
    });
    document.querySelectorAll('#adminSidebar a').forEach(function (link) {
        link.addEventListener('click', function () { setOpen(false); });
    });
})();
</script>
