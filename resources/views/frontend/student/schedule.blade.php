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
  <title>Schedule - Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { -gold: #f5c518; -navy: #0b1640; -ink: #111827; -muted: #6b7280; -line: #e5e7eb; -ff: 'Instrument Sans', sans-serif; -ff-head: 'Sora', sans-serif; -ff-mono: 'JetBrains Mono', monospace; }
    body { background: var(--bg, #f0f2f8); color: var(--ink); font-family: var(--ff); font-size: 14px; -webkit-font-smoothing: antialiased; }

    #sidebar { width: 230px; min-height: 100vh; background: #fff; border-right: 1px solid #e8eaf0; }
    .brand-icon { background: var(--gold); border-radius: 10px; width: 42px; height: 42px; display:grid; place-items:center; }
    .nav-link { color: #555; border-radius: 10px; padding: .55rem 1rem; font-weight: 500; }
    .nav-link:hover, .nav-link.active { background: #F0F4FF; color: var(--navy); }
    .nav-link.active::after { content:''; display:inline-block; width:7px; height:7px; background:var(--navy); border-radius:50%; margin-left:auto; }
    .avatar { width:38px; height:38px; background:var(--navy); border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:700; font-size:.85rem; }
    
    main { max-width: 1320px; }
    .page-top { padding-bottom: 1.15rem; border-bottom: 1px solid var(--line); }
    .eyebrow { color: var(--muted); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 4px; }
    .page-title { color: var(--ink); font-family: var(--ff-head); font-size: 1.5rem; font-weight: 700; letter-spacing: -0.01em; margin-bottom: 4px; }
    .page-subtitle { color: var(--muted); font-size: 0.875rem; }
    .summary-panel { position: relative; overflow: hidden; background: #fff; border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow-sm, 0 2px 6px rgba(15,23,41,.06)); }
    .summary-copy { position: relative; z-index: 1; color: var(--ink); }
    .summary-label { color: var(--muted); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 8px; }
    .summary-value { font-family: var(--ff-head); font-size: 1.5rem; font-weight: 700; letter-spacing: -0.01em; color: var(--ink); }
    .summary-meta { color: var(--muted); font-size: 0.875rem; margin-top: 4px; }
    .summary-icon { position: relative; z-index: 1; display: grid; width: 48px; height: 48px; place-items: center; border: 1px solid var(--line); border-radius: 10px; background: #f8f9fb; color: var(--navy); font-size: 1.25rem; }
    .section-heading { color: var(--ink); font-family: var(--ff-head); font-size: 1rem; font-weight: 700; letter-spacing: -0.01em; }
    .schedule-row { padding: 16px 24px; border-bottom: 1px solid var(--line); transition: background 0.2s ease; }
    .schedule-row:last-child { border-bottom: none; }
    .schedule-row:hover { background: #f8f9fb; }
    .subject-list { border: 1px solid var(--line); border-radius: 14px; background: #fff; overflow: hidden; box-shadow: var(--shadow-sm, 0 2px 6px rgba(15,23,41,.06)); }
    .subject-item + .subject-item { border-top: 1px solid var(--line); }
    .subject-toggle { width: 100%; border: 0; background: #fff; padding: 16px 24px; text-align: left; display: flex; align-items: center; gap: 16px; cursor: pointer; color: var(--ink); transition: background 0.2s ease; }
    .subject-toggle:hover, .subject-toggle[aria-expanded="true"] { background: #f8f9fb; }
    .subject-icon { display: grid; width: 32px; height: 32px; flex: 0 0 32px; place-items: center; border-radius: 6px; background: #f8f9fb; border: 1px solid var(--line); color: var(--navy); }
    .subject-code { display: block; color: var(--muted); font-size: 0.75rem; font-weight: 600; }
    .subject-name { display: block; margin-top: 2px; font-family: var(--ff-head); font-size: 0.95rem; font-weight: 700; color: var(--ink); }
    .schedule-count { margin-left: auto; border: 1px solid var(--line); border-radius: 6px; background: #f8f9fb; color: var(--text-3, #7c8a9e); font-size: 0.75rem; font-weight: 600; padding: 4px 8px; white-space: nowrap; }
    .subject-toggle .subject-arrow { margin-left: 12px; transition: transform 0.2s ease; color: var(--muted); }
    .subject-toggle[aria-expanded="true"] .subject-arrow { transform: rotate(180deg); }
    .subject-schedules { border-top: 1px solid var(--line); background: #fcfcfc; }
    .subject-schedules[hidden] { display: none; }
    .time-badge { background: #f8f9fb; border: 1px solid var(--line); border-radius: 6px; color: var(--navy); font-family: var(--ff-mono); font-size: 0.75rem; font-weight: 600; width: 64px; text-align: center; padding: 6px 0; }
    .schedule-date { color: var(--ink); font-family: var(--ff-head); font-size: 0.875rem; font-weight: 700; margin-bottom: 4px; }
    .schedule-date .badge { border-radius: 6px; border: 1px solid var(--line); background: #fff !important; color: var(--muted) !important; font-size: 0.7rem; }
    .schedule-meta { color: var(--muted); font-size: 0.8rem; }
    .schedule-meta i { color: var(--text-4, #b0bac8); }
    @media (max-width: 640px) { .subject-toggle { padding: 16px; } .schedule-row { align-items: flex-start !important; padding: 16px; } .summary-panel { border-radius: 14px; } }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!- Main ->
  <main class="flex-grow-1 p-4">

    <!- Topbar ->
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

    <!- Subject List ->
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
