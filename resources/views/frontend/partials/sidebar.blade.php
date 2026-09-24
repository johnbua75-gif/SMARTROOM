<aside class="sidebar" role="navigation" aria-label="Main sidebar">
  <a href="{{ route('faculty.dashboard') }}" class="sidebar-logo">
    <div class="logo-mark">SR</div>
    <div class="logo-text">
      <span class="brand-psu">SmartRoom</span>
      <span class="brand-main">SmartDoor <span>System</span></span>
    </div>
  </a>

  <div class="nav-section-label">Navigation</div>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ route('faculty.dashboard') }}" class="{{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-house"></i></span>
        <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="{{ route('faculty.attendance') }}" class="{{ request()->routeIs('faculty.attendance*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span>
        <span>Attendance</span>
      </a>
    </li>
    <li>
      <a href="{{ route('faculty.schedule') }}" class="{{ request()->routeIs('faculty.schedule*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-calendar-days"></i></span>
        <span>Schedules</span>
      </a>
    </li>
    <!-- Courses link removed (no faculty.courses route defined) -->
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <div class="user-avatar">{{ strtoupper(substr(request()->user()?->name ?? 'U',0,1)) }}</div>
      <div>
        <div class="user-widget-name">{{ request()->user()?->name ?? 'User' }}</div>
        <div class="user-widget-role">{{ request()->user()?->department ?? '' }}</div>
      </div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout-btn">
        <span class="nav-icon"><i class="fas fa-right-from-bracket"></i></span>
        <span>Sign Out</span>
      </button>
    </form>
  </div>
</aside>
