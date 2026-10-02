<style>
  :root {
    --student-navy: #0b1640;
    --student-navy-mid: #1a2f80;
    --student-gold: #f5c518;
    --student-bg: #f3f5fb;
    --student-border: #e4e8f2;
    --student-text: #0d1526;
    --student-muted: #7585a0;
    --student-blue: #2563eb;
  }

  body {
    background: var(--student-bg);
    color: var(--student-text);
    font-family: 'Segoe UI', sans-serif;
  }

  #sidebar.student-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 230px !important;
    height: 100vh;
    min-height: 100vh;
    flex: 0 0 230px;
    padding: 0 !important;
    overflow: visible;
    z-index: 100;
    background: var(--student-navy) !important;
    border-right: 2px solid rgba(245, 197, 24, .55) !important;
    color: #fff;
  }

  #sidebar.student-sidebar::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg, rgba(245, 197, 24, .06) 0%, transparent 55%);
    pointer-events: none;
  }

  #sidebar.student-sidebar::after {
    content: '';
    position: absolute;
    right: -60px;
    bottom: -60px;
    width: 180px;
    height: 180px;
    border: 1px solid rgba(245, 197, 24, .08);
    border-radius: 50%;
    pointer-events: none;
  }

  #sidebar.student-sidebar .sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 28px 20px 24px 24px;
    margin-bottom: 8px;
    color: #fff;
    text-decoration: none;
    border-bottom: 1px solid rgba(255, 255, 255, .06);
  }

  #sidebar.student-sidebar .logo-mark {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border-radius: 12px;
    background: var(--student-gold);
    color: var(--student-navy);
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(245, 197, 24, .4);
  }

  #sidebar.student-sidebar .logo-text {
    line-height: 1;
  }

  #sidebar.student-sidebar .brand-psu {
    display: block;
    margin-bottom: 3px;
    color: rgba(255, 255, 255, .45);
    font-size: .6rem;
    font-weight: 600;
    letter-spacing: .18em;
    text-transform: uppercase;
  }

  #sidebar.student-sidebar .brand-main {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
  }

  #sidebar.student-sidebar .brand-main span {
    color: var(--student-gold);
  }

  #sidebar.student-sidebar .nav-section-label {
    display: block;
    padding: 16px 24px 6px;
    color: rgba(255, 255, 255, .25);
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
  }

  #sidebar.student-sidebar .sidebar-nav {
    list-style: none;
    overflow-y: auto;
    padding: 0 12px;
    margin: 0;
  }

  #sidebar.student-sidebar .sidebar-nav::-webkit-scrollbar {
    width: 0;
  }

  #sidebar.student-sidebar .sidebar-nav li {
    margin-bottom: 2px;
  }

  #sidebar.student-sidebar .sidebar-nav a {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 12px;
    overflow: hidden;
    color: rgba(255, 255, 255, .6);
    font-size: .88rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    transition: all .22s cubic-bezier(.4, 0, .2, 1);
  }

  #sidebar.student-sidebar .sidebar-nav a .nav-icon,
  #sidebar.student-sidebar .sidebar-logout-btn .nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    color: inherit;
    font-size: .85rem;
    background: rgba(255, 255, 255, .05);
    border-radius: 8px;
  }

  #sidebar.student-sidebar .sidebar-nav a:hover {
    color: rgba(255, 255, 255, .9);
    background: rgba(255, 255, 255, .06);
  }

  #sidebar.student-sidebar .sidebar-nav a:hover .nav-icon {
    background: rgba(255, 255, 255, .1);
  }

  #sidebar.student-sidebar .sidebar-nav a.active {
    color: var(--student-gold);
    background: rgba(245, 197, 24, .14);
  }

  #sidebar.student-sidebar .sidebar-nav a.active .nav-icon {
    color: var(--student-gold);
    background: rgba(245, 197, 24, .2);
  }

  #sidebar.student-sidebar .sidebar-nav a.active::before {
    content: '';
    position: absolute;
    top: 20%;
    bottom: 20%;
    left: 0;
    width: 3px;
    background: var(--student-gold);
    border-radius: 0 2px 2px 0;
  }

  #sidebar.student-sidebar .sidebar-footer {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 16px 12px 24px;
    border-top: 1px solid rgba(255, 255, 255, .06);
  }

  #sidebar.student-sidebar .user-widget {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 10px 12px;
    margin-bottom: 8px;
    background: rgba(255, 255, 255, .05);
    border-radius: 8px;
  }

  #sidebar.student-sidebar .user-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    color: var(--student-gold);
    font-size: .78rem;
    font-weight: 700;
    background: var(--student-navy-mid);
    border: 2px solid rgba(245, 197, 24, .4);
    border-radius: 50%;
  }

  #sidebar.student-sidebar .user-widget-info {
    min-width: 0;
  }

  #sidebar.student-sidebar .user-widget-name {
    overflow: hidden;
    color: #fff;
    font-size: .83rem;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  #sidebar.student-sidebar .user-widget-role {
    overflow: hidden;
    color: rgba(255, 255, 255, .4);
    font-size: .73rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  #sidebar.student-sidebar .sidebar-logout-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 12px;
    color: rgba(255, 255, 255, .4);
    font-size: .84rem;
    font-weight: 500;
    font-family: inherit;
    text-align: left;
    background: none;
    border: 0;
    border-radius: 8px;
    cursor: pointer;
    transition: all .22s;
  }

  #sidebar.student-sidebar .sidebar-logout-btn:hover {
    color: #f87171;
    background: rgba(244, 63, 94, .08);
  }

  .student-sidebar-toggle {
    position: absolute;
    top: 50%;
    right: -13px;
    z-index: 5;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(245, 197, 24, .7);
    border-radius: 50%;
    background: #17265f;
    color: var(--student-gold);
    cursor: pointer;
    font-size: .68rem;
    box-shadow: 0 3px 8px rgba(0, 0, 0, .18);
    transform: translateY(-50%);
  }

  .student-sidebar-toggle:hover { background: var(--student-gold); color: var(--student-navy); }
  body.student-sidebar-collapsed #sidebar.student-sidebar { width: 72px !important; flex-basis: 72px; }
  body.student-sidebar-collapsed main.flex-grow-1 { margin-left: 72px; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-logo { justify-content: center; padding-left: 12px; padding-right: 12px; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .logo-text,
  body.student-sidebar-collapsed #sidebar.student-sidebar .nav-section-label,
  body.student-sidebar-collapsed #sidebar.student-sidebar .user-widget-info { display: none; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-nav { padding-left: 10px; padding-right: 10px; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-nav a { justify-content: center; padding-left: 8px; padding-right: 8px; font-size: 0; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-nav a .nav-icon { font-size: .85rem; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-footer { padding-left: 10px; padding-right: 10px; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .user-widget { justify-content: center; padding-left: 7px; padding-right: 7px; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-logout-btn { justify-content: center; padding-left: 8px; padding-right: 8px; font-size: 0; }
  body.student-sidebar-collapsed #sidebar.student-sidebar .sidebar-logout-btn .nav-icon { font-size: .85rem; }
  body.student-sidebar-collapsed .student-sidebar-toggle { right: -13px; transform: translateY(-50%) rotate(180deg); }

  main.flex-grow-1 {
    min-width: 0;
    margin-left: 230px;
    padding: 0 32px 28px !important;
    min-height: 100vh;
    background: #f4f6fb;
    color: var(--student-text);
  }

  main.flex-grow-1 > * {
    max-width: 1320px;
    margin-right: auto;
    margin-left: auto;
  }

  main.flex-grow-1 h1,
  main.flex-grow-1 h2,
  main.flex-grow-1 h3,
  main.flex-grow-1 h4,
  main.flex-grow-1 h5,
  main.flex-grow-1 h6 {
    color: var(--student-text);
    letter-spacing: -0.02em;
  }

  main.flex-grow-1 .bg-white,
  main.flex-grow-1 .stat-card,
  main.flex-grow-1 .schedule-card,
  main.flex-grow-1 .room-card,
  main.flex-grow-1 .weekly-card,
  main.flex-grow-1 .notice-card,
  main.flex-grow-1 .profile-card,
  main.flex-grow-1 .qa-card,
  main.flex-grow-1 .course-card,
  main.flex-grow-1 .map-card,
  main.flex-grow-1 .summary-panel,
  main.flex-grow-1 .subject-list,
  main.flex-grow-1 .profile-header,
  main.flex-grow-1 .campus-banner {
    border-color: rgba(15, 26, 60, .1) !important;
    border-radius: 14px !important;
    box-shadow: 0 8px 24px rgba(15, 26, 60, .06) !important;
  }

  main.flex-grow-1 .form-control,
  main.flex-grow-1 .input-group-text,
  main.flex-grow-1 select {
    border-color: rgba(15, 26, 60, .14) !important;
    background: #fff;
    color: var(--student-text);
  }

  main.flex-grow-1 .form-control:focus,
  main.flex-grow-1 select:focus {
    border-color: var(--student-blue) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12) !important;
  }

  main.flex-grow-1 .btn {
    min-height: 38px;
    border-radius: 9px;
    font-weight: 600;
  }

  main.flex-grow-1 .text-muted {
    color: var(--student-muted) !important;
  }

  main.flex-grow-1 > .d-flex.justify-content-between.align-items-center.mb-4:not(.page-top) {
    display: none !important;
  }

  main.flex-grow-1 > .d-flex.justify-content-between:first-child {
    min-height: 48px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--student-border);
  }

  main h3,
  main h5,
  main h6 {
    color: var(--student-text);
  }

  .stat-card,
  .schedule-card,
  .room-card,
  .weekly-card,
  .notice-card,
  .profile-card,
  main .bg-white {
    border-color: var(--student-border) !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
  }

  .stat-card,
  .qa-card,
  .notice-card,
  .profile-card,
  .schedule-card,
  .room-card,
  .weekly-card {
    border-radius: 12px !important;
  }

  .btn-warning,
  .btn-primary {
    border: 0;
    background: var(--student-gold);
    color: var(--student-navy);
  }

  .btn-warning:hover,
  .btn-primary:hover {
    background: #eab308;
    color: var(--student-navy);
  }

  @media (max-width: 768px) {
    body > .d-flex {
      display: block !important;
    }

    #sidebar {
      width: 100% !important;
      min-height: auto;
      flex-basis: auto;
    }

    #sidebar.student-sidebar {
      position: relative;
      width: 100% !important;
      height: auto;
      min-height: auto;
    }

    .student-sidebar-toggle { display: none; }
    body.student-sidebar-collapsed main.flex-grow-1 { margin-left: 0; }

    #sidebar.student-sidebar .sidebar-footer {
      position: relative;
    }

    main.flex-grow-1 {
      margin-left: 0;
      padding: 0 16px 20px !important;
    }

    main.flex-grow-1 > * {
      max-width: none;
    }
  }
</style>
