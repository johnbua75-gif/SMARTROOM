@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => false, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => false, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => false, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => false, 'route' => 'student.profile'],
];

// Ensure $schedules is a collection
$schedulesCollection = collect($schedules ?? []);
$validSchedules = $schedulesCollection->filter(fn ($schedule) => $schedule->start_at instanceof \Carbon\CarbonInterface);

// Count unique courses/subjects
$totalSubjects = $validSchedules->map(fn ($schedule) => $schedule->course?->code ?? $schedule->course_id)->unique()->count();
$totalUnits = $validSchedules->count();
$thisWeekHours = round($validSchedules->sum(function ($schedule) {
  return $schedule->duration_hours ?? 2;
}), 1);

$stats = [
    ['icon' => 'bi-calendar3', 'color' => 'text-primary', 'label' => 'Total Subjects', 'value' => $totalSubjects],
    ['icon' => 'bi-clock', 'color' => 'text-warning', 'label' => 'Total Units', 'value' => $totalUnits],
    ['icon' => 'bi-person', 'color' => 'text-success', 'label' => 'This Week', 'value' => $thisWeekHours . ' hrs'],
];

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
$active_day = now()->format('D');

// Group schedules by date using the real schedule relationship data.
$groupedByDate = $validSchedules->groupBy(function ($s) {
  return $s->start_at?->format('D');
})->map(fn($items) => $items->values())->toArray();

$activeSchedules = $groupedByDate[$active_day] ?? [];

$schedulePayload = $validSchedules->map(function ($schedule): array {
  return [
    'day' => $schedule->start_at->format('D'),
    'day_label' => $schedule->start_at->format('l'),
    'course' => $schedule->course?->code ?? $schedule->course_id ?? 'Course',
    'block' => $schedule->block_section ?? 'SEC',
    'instructor' => $schedule->course?->instructor?->name ?? 'Faculty',
    'room' => $schedule->classroom?->name ?? 'Room',
    'building' => $schedule->classroom?->building ?? 'Building',
    'start' => $schedule->start_at->format('H:i'),
    'end' => $schedule->end_at instanceof \Carbon\CarbonInterface
      ? $schedule->end_at->format('H:i')
      : $schedule->start_at->copy()->addHours($schedule->duration_hours ?? 2)->format('H:i'),
    'start_display' => $schedule->start_at->format('h:i A'),
    'session_type' => $schedule->session_type ?? 'Class',
  ];
})->values();

// Weekly overview - count classes per day.
$weekly = [];
$weekStart = now()->startOfWeek();
foreach (\range(0, 4) as $offset) {
  $dayDate = $weekStart->copy()->addDays($offset);
  $day = $dayDate->format('l');
  $dayAbbr = substr($day, 0, 3);
  $weekly[$day] = count($groupedByDate[$dayAbbr] ?? []);
}
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SmartDoor – Schedule</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root{--gold:#F5A800;--navy:#1B2A5E;--indigo:#4F46E5;--purple:#7C3AED;}
    body{background:#F4F6FA;font-family:'Segoe UI',sans-serif;}

    /* Sidebar */
    #sidebar{width:220px;min-height:100vh;background:#fff;border-right:1px solid #e8eaf0;}
    .brand-icon{background:var(--gold);border-radius:10px;width:40px;height:40px;display:grid;place-items:center;}
    .nav-link{color:#555;border-radius:8px;padding:.5rem 1rem;font-weight:500;}
    .nav-link:hover,.nav-link.active{background:#F0F4FF;color:var(--navy);}
    .nav-link.active::after{content:'';display:inline-block;width:7px;height:7px;background:var(--navy);border-radius:50%;margin-left:auto;}
    .avatar{width:36px;height:36px;background:var(--navy);border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:.8rem;}

    /* Stat cards */
    .stat-card{border-radius:14px;border:1px solid #e8eaf0;background:#fff;}

    /* Day tabs */
    .day-tab{border-radius:10px;padding:.45rem 1.1rem;font-weight:600;font-size:.9rem;border:none;background:transparent;color:#555;transition:all .15s;}
    .day-tab.active{background:var(--navy);color:#fff;}
    .day-tab:hover:not(.active){background:#F0F4FF;color:var(--navy);}
    .day-nav-btn{background:#fff;border:1px solid #e0e0e0;border-radius:8px;width:32px;height:32px;display:grid;place-items:center;cursor:pointer;}

    /* Class cards */
    .class-card{border-radius:14px;border:1px solid #e8eaf0;background:#fff;overflow:hidden;position:relative;}
    .class-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--accent);}
    .class-card.accent-1 { --accent: #4F46E5; }
    .class-card.accent-2 { --accent: #7C3AED; }
    .class-card.accent-3 { --accent: #EC4899; }
    .class-card.accent-4 { --accent: #F59E0B; }
    .class-card .inner{padding:1.2rem 1.2rem 1.2rem 1.5rem;}
    .time-col{min-width:52px;font-size:.78rem;font-weight:600;color:#888;line-height:1.8;}
    .time-badge{font-size:.75rem;font-weight:600;padding:.3rem .7rem;border-radius:20px;background:#EEF2FF;color:var(--indigo);}
    .subject-code{background:#F0F4FF;color:#555;border-radius:6px;font-size:.7rem;padding:2px 8px;font-weight:600;}
    .meta-item{font-size:.78rem;color:#888;}
    .btn-view{background:var(--navy);color:#fff;border-radius:8px;border:none;font-size:.82rem;padding:.4rem 1rem;}
    .btn-dir{background:#fff;color:var(--navy);border:1px solid #e0e0e0;border-radius:8px;font-size:.82rem;padding:.4rem 1rem;}

    /* Weekly Overview */
    .weekly-card{border-radius:14px;border:1px solid #e8eaf0;background:#fff;}
    .week-dot{width:10px;height:10px;border-radius:50%;background:var(--indigo);flex-shrink:0;}
    .week-row{border-bottom:1px solid #f0f0f0;padding:.75rem 0;}
    .week-row:last-child{border-bottom:none;}
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!-- Main -->
  <main class="flex-grow-1 p-4">

    <!-- Topbar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div><h5 class="fw-bold mb-0">Schedule</h5><small class="text-muted">{{ now()->format('l, F j, Y') }}</small></div>
      <a href="{{ route('student.home') }}" class="btn btn-warning fw-semibold"><i class="bi bi-house me-1"></i>Back to Home</a>
    </div>

    <!-- Title + Actions -->
    <div class="d-flex justify-content-between align-items-start mb-4">
      <div>
        <h3 class="fw-bold mb-1">My Schedule</h3>
        <div class="text-muted small">Spring Semester 2026</div>
      </div>
      <div class="d-flex gap-2">
        <button id="shareSchedule" type="button" class="btn btn-outline-secondary btn-sm"><i class="bi bi-share me-1"></i>Share</button>
        <button id="downloadSchedule" type="button" class="btn btn-sm fw-semibold text-white" style="background:var(--navy)"><i class="bi bi-download me-1"></i>Download</button>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      @foreach($stats as $s)
        <div class="col-md-4">
          <div class="stat-card p-4 d-flex align-items-center gap-3">
            <i class="bi {{ $s['icon'] }} fs-3 {{ $s['color'] }}"></i>
            <div>
              <div class="text-muted small">{{ $s['label'] }}</div>
              <div class="fs-3 fw-bold">{{ $s['value'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Day Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4">
      <button id="previousScheduleDay" type="button" class="day-nav-btn" aria-label="Previous day"><i class="bi bi-chevron-left" style="font-size:.75rem"></i></button>
      @foreach($days as $d)
        <button type="button" class="day-tab {{ $d === now()->format('D') ? 'active' : '' }}" data-day="{{ $d }}">{{ $d }}</button>
      @endforeach
      <button id="nextScheduleDay" type="button" class="day-nav-btn ms-auto" aria-label="Next day"><i class="bi bi-chevron-right" style="font-size:.75rem"></i></button>
    </div>

    <!-- Class List -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 id="scheduleDayHeading" class="fw-bold mb-0">{{ now()->format('l') }}'s Classes</h6>
      <span id="scheduleDayCount" class="text-muted small">{{ count($activeSchedules) }} {{ count($activeSchedules) === 1 ? 'class' : 'classes' }}</span>
    </div>

    <div id="scheduleClassList" class="d-flex flex-column gap-3 mb-4">
      @php($activeRows = collect($activeSchedules ?? [])->filter(fn ($schedule) => $schedule->start_at instanceof \Carbon\CarbonInterface))
      @forelse ($activeRows as $s)
        @php
          $colors = ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B'];
          $colorIndex = ($loop->index ?? 0) % count($colors);
          $borderClass = 'accent-' . ($colorIndex + 1);

          $startTime = $s->start_at;
          $endTime = $s->end_at instanceof \Carbon\CarbonInterface
            ? $s->end_at
            : $startTime->copy()->addHours($s->duration_hours ?? 2);
        @endphp
        <div class="class-card {{ $borderClass }}">
          <div class="inner">
            <div class="d-flex gap-3">
              <!-- Time Column -->
              <div class="time-col text-end">
                <div>{{ $startTime->format('H:i') }}</div>
                <div>{{ $endTime->format('H:i') }}</div>
              </div>
              <!-- Content -->
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold">{{ $s->course?->code ?? $s->course_code ?? 'Course' }}</span>
                    <span class="subject-code">{{ $s->block_section ?? 'SEC' }}</span>
                  </div>
                  <span class="time-badge">{{ $startTime->format('h:i A') }} - {{ $endTime->format('h:i A') }}</span>
                </div>
                <div class="d-flex flex-wrap gap-3 mb-3">
                  <span class="meta-item"><i class="bi bi-person me-1"></i>{{ $s->course?->instructor?->name ?? $s->faculty_name ?? 'Faculty' }}</span>
                  <span class="meta-item"><i class="bi bi-geo-alt me-1"></i>{{ $s->classroom?->name ?? $s->classroom_name ?? 'Room' }}, {{ $s->classroom?->building ?? $s->building ?? 'Building' }}</span>
                  <span class="meta-item"><i class="bi bi-clock me-1"></i>{{ $s->session_type ?? 'Class' }}</span>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn-view">View Room</button>
                  <button class="btn-dir">Get Directions</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          @if (!($hasEnrolledCourses ?? false))
            No enrolled course yet. Your schedule will appear here after a course is added.
          @else
            No classes scheduled for today.
          @endif
        </div>
      @endforelse
    </div>

    <!-- Weekly Overview -->
    <div class="weekly-card p-4">
      <h6 class="fw-bold mb-3">Weekly Overview</h6>
      @forelse ($weekly ?? [] as $day => $count)
        <div class="week-row d-flex align-items-center gap-3">
          <div class="week-dot"></div>
          <span class="fw-semibold flex-grow-1">{{ $day }}</span>
          <span class="text-muted small">{{ $count }} {{ $count === 1 ? 'class' : 'classes' }}</span>
        </div>
      @empty
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle me-2"></i>No schedule data available.
        </div>
      @endforelse
    </div>

  </main>
</div>
<script type="application/json" id="studentScheduleData">{!! $schedulePayload->toJson() !!}</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const scheduleData = JSON.parse(document.getElementById('studentScheduleData').textContent || '[]');
  const scheduleDays = @json($days);
  let selectedScheduleDay = @json($active_day);
  const scheduleList = document.getElementById('scheduleClassList');
  const scheduleHeading = document.getElementById('scheduleDayHeading');
  const scheduleCount = document.getElementById('scheduleDayCount');

  function escapeSchedule(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
  }

  function renderScheduleDay() {
    const rows = scheduleData.filter(schedule => schedule.day === selectedScheduleDay);
    document.querySelectorAll('.day-tab').forEach(tab => tab.classList.toggle('active', tab.dataset.day === selectedScheduleDay));
    const dayLabel = rows[0]?.day_label || new Date().toLocaleDateString('en-US', { weekday: 'long' });
    scheduleHeading.textContent = `${dayLabel}'s Classes`;
    scheduleCount.textContent = `${rows.length} ${rows.length === 1 ? 'class' : 'classes'}`;
    scheduleList.innerHTML = rows.length ? rows.map((schedule, index) => `
      <div class="class-card accent-${(index % 4) + 1}"><div class="inner"><div class="d-flex gap-3">
        <div class="time-col text-end"><div>${escapeSchedule(schedule.start)}</div><div>${escapeSchedule(schedule.end)}</div></div>
        <div class="flex-grow-1"><div class="d-flex justify-content-between align-items-center mb-2"><div class="d-flex align-items-center gap-2"><span class="fw-bold">${escapeSchedule(schedule.course)}</span><span class="subject-code">${escapeSchedule(schedule.block)}</span></div><span class="time-badge">${escapeSchedule(schedule.start_display)} - ${escapeSchedule(schedule.end)}</span></div>
        <div class="d-flex flex-wrap gap-3 mb-3"><span class="meta-item"><i class="bi bi-person me-1"></i>${escapeSchedule(schedule.instructor)}</span><span class="meta-item"><i class="bi bi-geo-alt me-1"></i>${escapeSchedule(schedule.room)}, ${escapeSchedule(schedule.building)}</span><span class="meta-item"><i class="bi bi-clock me-1"></i>${escapeSchedule(schedule.session_type)}</span></div>
        <div class="d-flex gap-2"><a class="btn-view text-decoration-none" href="{{ route('student.checkingRoom') }}">View Room</a><a class="btn-dir text-decoration-none" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(schedule.room + ' ' + schedule.building)}">Get Directions</a></div></div>
      </div></div></div>`).join('') : '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No classes scheduled for this day.</div>';
  }

  document.querySelectorAll('.day-tab').forEach(tab => tab.addEventListener('click', () => { selectedScheduleDay = tab.dataset.day; renderScheduleDay(); }));
  function moveScheduleDay(direction) { const index = scheduleDays.indexOf(selectedScheduleDay); selectedScheduleDay = scheduleDays[(index + direction + scheduleDays.length) % scheduleDays.length]; renderScheduleDay(); }
  document.getElementById('previousScheduleDay').addEventListener('click', () => moveScheduleDay(-1));
  document.getElementById('nextScheduleDay').addEventListener('click', () => moveScheduleDay(1));
  document.getElementById('shareSchedule').addEventListener('click', async () => {
    const text = 'My SmartDoor class schedule';
    try {
      if (navigator.share) {
        await navigator.share({ title: 'My Schedule', text, url: window.location.href });
      } else if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(window.location.href);
        window.alert('Schedule link copied.');
      } else {
        window.prompt('Copy your schedule link:', window.location.href);
      }
    } catch (error) {
      if (error.name !== 'AbortError') window.alert('Unable to share the schedule right now.');
    }
  });
  document.getElementById('downloadSchedule').addEventListener('click', () => { const csv = ['Day,Course,Block,Instructor,Room,Building,Start,End', ...scheduleData.map(s => [s.day,s.course,s.block,s.instructor,s.room,s.building,s.start,s.end].map(value => `"${String(value).replaceAll('"','""')}"`).join(','))].join('\n'); const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' })); link.download = 'my-schedule.csv'; link.click(); URL.revokeObjectURL(link.href); });
</script>
</body>
</html>