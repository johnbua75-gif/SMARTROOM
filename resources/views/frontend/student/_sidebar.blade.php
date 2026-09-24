@php
    $studentSidebarName = optional($student)->name ?? 'Student';
    $studentSidebarId = optional($student)->student_id ?? 'N/A';
    $studentSidebarInitials = collect(explode(' ', $studentSidebarName))
        ->filter()
        ->map(fn ($word) => strtoupper($word[0]))
        ->join('');
@endphp

<aside id="sidebar" class="student-sidebar" role="navigation" aria-label="Student sidebar">
  <a href="{{ route('student.home') }}" class="sidebar-logo">
    <div class="logo-mark"><i class="bi bi-door-open-fill"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Door</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ route('student.home') }}" class="{{ request()->routeIs('student.home') ? 'active' : '' }}">
        <span class="nav-icon"><i class="bi bi-grid-1x2-fill"></i></span>Dashboard
      </a>
    </li>
    <li>
      <a href="{{ route('student.checkingRoom') }}" class="{{ request()->routeIs('student.checkingRoom') ? 'active' : '' }}">
        <span class="nav-icon"><i class="bi bi-door-open-fill"></i></span>Rooms
      </a>
    </li>
    <li>
      <a href="{{ route('student.courses') }}" class="{{ request()->routeIs('student.courses*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="bi bi-journal-bookmark-fill"></i></span>Courses
      </a>
    </li>
    <li>
      <a href="{{ route('student.schedule') }}" class="{{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <span class="nav-icon"><i class="bi bi-calendar3"></i></span>Schedule
      </a>
    </li>
    <li>
      <a href="{{ route('student.attendance') }}" class="{{ request()->routeIs('student.attendance*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="bi bi-clipboard-check-fill"></i></span>Attendance
      </a>
    </li>
    <li>
      <a href="{{ route('student.profile') }}" class="{{ request()->routeIs('student.profile') ? 'active' : '' }}">
        <span class="nav-icon"><i class="bi bi-person-fill"></i></span>Profile
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <div class="user-avatar">{{ $studentSidebarInitials ?: 'S' }}</div>
      <div class="user-widget-info">
        <div class="user-widget-name">{{ $studentSidebarName }}</div>
        <div class="user-widget-role">{{ $studentSidebarId }}</div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="sidebar-logout-btn">
        <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>Sign Out
      </button>
    </form>
  </div>
</aside>
