@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$subjectGroups = collect($schedules ?? [])->groupBy('course_id');
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => false, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => false, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => false, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => false, 'route' => 'student.profile'],
];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Schedule – Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --gold: #F5A800; --navy: #1B2A5E; --ink: #102044; --muted: #718096; --line: #e5eaf2; --ff: 'Instrument Sans', sans-serif; --ff-head: 'Sora', sans-serif; --ff-mono: 'JetBrains Mono', monospace; }
    body { background: #f3f6fb; color: var(--ink); font-family: var(--ff); font-size: 14px; -webkit-font-smoothing: antialiased; }

    #sidebar { width: 230px; min-height: 100vh; background: #fff; border-right: 1px solid #e8eaf0; }
    .brand-icon { background: var(--gold); border-radius: 10px; width: 42px; height: 42px; display:grid; place-items:center; }
    .nav-link { color: #555; border-radius: 8px; padding: .55rem 1rem; font-weight: 500; }
    .nav-link:hover, .nav-link.active { background: #F0F4FF; color: var(--navy); }
    .nav-link.active::after { content:''; display:inline-block; width:7px; height:7px; background:var(--navy); border-radius:50%; margin-left:auto; }
    .avatar { width:38px; height:38px; background:var(--navy); border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:700; font-size:.85rem; }
    
    main { max-width: 1320px; }
    .page-top { padding-bottom: 1.15rem; border-bottom: 1px solid var(--line); }
    .eyebrow { color: #5470a8; font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .page-title { color: var(--ink); font-family: var(--ff-head); font-size: 1.55rem; letter-spacing: -.03em; }
    .page-subtitle { color: var(--muted); font-size: .78rem; }
    .summary-panel { position: relative; overflow: hidden; border: 1px solid #dfe7f5; border-radius: 18px; background: linear-gradient(120deg, #1b2a5e 0%, #263d85 70%, #3159a0 100%); box-shadow: 0 12px 28px rgba(27, 42, 94, .14); color: #fff; }
    .summary-panel::after { content: ''; position: absolute; right: -42px; top: -70px; width: 190px; height: 190px; border: 1px solid rgba(255,255,255,.12); border-radius: 50%; }
    .summary-copy { position: relative; z-index: 1; }
    .summary-label { color: rgba(255,255,255,.66); font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .summary-value { font-family: var(--ff-head); font-size: 1.8rem; font-weight: 800; letter-spacing: -.04em; }
    .summary-meta { color: rgba(255,255,255,.72); font-size: .78rem; }
    .summary-icon { position: relative; z-index: 1; display: grid; width: 58px; height: 58px; place-items: center; border: 1px solid rgba(255,255,255,.18); border-radius: 16px; background: rgba(255,255,255,.1); color: #ffd45c; font-size: 1.5rem; }
    .section-heading { color: var(--ink); font-family: var(--ff-head); font-size: .95rem; font-weight: 800; }
    .schedule-row { padding: 1rem 1.25rem; border-bottom: 1px solid #edf0f5; transition: background 0.15s; }
    .schedule-row:last-child { border-bottom: none; }
    .schedule-row:hover { background: #F0F4FF; }
    .subject-list { border: 1px solid var(--line); border-radius: 16px; background: #fff; overflow: hidden; box-shadow: 0 5px 18px rgba(26, 45, 82, .05); }
    .subject-item + .subject-item { border-top: 1px solid var(--line); }
    .subject-toggle { width: 100%; border: 0; background: #fff; padding: 1.1rem 1.3rem; text-align: left; display: flex; align-items: center; gap: .9rem; cursor: pointer; color: var(--ink); }
    .subject-toggle:hover, .subject-toggle[aria-expanded="true"] { background: #f7f9fe; }
    .subject-icon { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border-radius: 10px; background: #fff4d6; color: #b97800; }
    .subject-code { display: block; color: #5470a8; font-size: .68rem; font-weight: 800; letter-spacing: .08em; }
    .subject-name { display: block; margin-top: 2px; font-family: var(--ff-head); font-size: .9rem; font-weight: 700; }
    .schedule-count { margin-left: auto; border: 1px solid #dce5f5; border-radius: 999px; background: #f6f8fd; color: #5470a8; font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .subject-toggle .subject-arrow { margin-left: auto; transition: transform .2s ease; }
    .subject-toggle[aria-expanded="true"] .subject-arrow { transform: rotate(180deg); }
    .subject-schedules { border-top: 1px solid #e8eaf0; }
    .subject-schedules[hidden] { display: none; }
    .time-badge { background: #eef3ff; border-radius: 10px; color: var(--navy); font-family: var(--ff-mono); font-size: .7rem; font-weight: 600; width: 68px; text-align: center; padding: 8px 0; }
    .schedule-date { color: var(--ink); font-family: var(--ff-head); font-size: .8rem; font-weight: 700; }
    .schedule-meta { color: var(--muted); font-size: .72rem; }
    @media (max-width: 640px) { .subject-toggle { padding: 1rem; } .schedule-row { align-items: flex-start !important; padding: .9rem 1rem; } .summary-panel { border-radius: 14px; } }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!-- Main -->
  <main class="flex-grow-1 p-4">

    <!-- Topbar -->
    <div class="page-top d-flex justify-content-between align-items-center mb-4">
      <div><div class="eyebrow">Student portal</div><h1 class="page-title fw-bold mb-1">My Schedule</h1><div class="page-subtitle">{{ now()->format('l, F j, Y') }} <span class="mx-1">·</span> Your enrolled subjects</div></div>
    </div>

    <div class="summary-panel d-flex justify-content-between align-items-center p-4 mb-4">
      <div class="summary-copy">
        <div class="summary-label">Semester overview</div>
        <div class="summary-value">{{ $subjectGroups->count() }} {{ $subjectGroups->count() === 1 ? 'subject' : 'subjects' }}</div>
        <div class="summary-meta">{{ $schedules->count() }} scheduled {{ $schedules->count() === 1 ? 'class' : 'classes' }} across the semester</div>
      </div>
      <div class="summary-icon"><i class="bi bi-calendar2-week"></i></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="section-heading">Enrolled subjects</div>
      <div class="page-subtitle">Select a subject to view its schedule</div>
    </div>

    <!-- Subject List -->
    <div class="subject-list">
      @forelse ($subjectGroups as $courseId => $courseSchedules)
        @php($subject = $courseSchedules->first()->course)
        @php($subjectPanelId = 'subject-schedules-'.$courseId)
        <div class="subject-item">
          <button type="button" class="subject-toggle" aria-expanded="false" aria-controls="{{ $subjectPanelId }}">
            <span class="subject-icon"><i class="bi bi-bookmark"></i></span>
            <span><span class="subject-code">{{ $subject?->code ?? 'COURSE' }}</span><span class="subject-name">{{ $subject?->title ?? 'Subject details unavailable' }}</span></span>
            <span class="badge schedule-count">{{ $courseSchedules->count() }} {{ $courseSchedules->count() === 1 ? 'schedule' : 'schedules' }}</span>
            <i class="bi bi-chevron-down subject-arrow"></i>
          </button>
          <div id="{{ $subjectPanelId }}" class="subject-schedules" hidden>
            @foreach ($courseSchedules as $s)
              <div class="schedule-row d-flex align-items-center gap-3">
                <div class="time-badge">
                  {{ $s->start_at ? \Carbon\Carbon::parse($s->start_at)->format('H:i') : 'N/A' }}
                </div>
                <div class="flex-grow-1">
                  <div class="schedule-date">{{ $s->start_at?->format('l, F j, Y') ?? 'Date unavailable' }} <span class="badge bg-light text-secondary ms-1">{{ $s->block_section ?? 'N/A' }}</span></div>
                  <div class="schedule-meta">
                    <i class="bi bi-clock me-1"></i>{{ $s->start_at?->format('g:i A') ?? 'TBA' }} - {{ $s->end_at?->format('g:i A') ?? 'TBA' }}
                    <i class="bi bi-person ms-2 me-1"></i>{{ $s->course?->instructor?->name ?? 'Instructor TBA' }}
                    <i class="bi bi-geo-alt ms-2 me-1"></i>{{ $s->classroom?->name ?? 'Room TBA' }}@if ($s->classroom?->building), {{ $s->classroom->building }}@endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @empty
        <div class="p-4 text-center text-muted">
          <i class="bi bi-calendar-x fs-1"></i>
          @if (!($hasEnrolledCourses ?? false))
            <p class="mt-2">No enrolled course yet. Your schedule will appear here after a course is added.</p>
          @else
            <p class="mt-2">No scheduled classes at this time.</p>
          @endif
        </div>
      @endforelse
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('.subject-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
      var panel = document.getElementById(button.getAttribute('aria-controls'));
      var expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!expanded));
      if (panel) {
        panel.hidden = expanded;
      }
    });
  });
</script>
</body>
</html>
