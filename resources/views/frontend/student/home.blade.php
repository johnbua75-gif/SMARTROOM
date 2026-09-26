@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$stats = [
    ['icon' => 'bi-book', 'value' => $todayClassesCount ?? 0, 'label' => "Today's Classes", 'color' => 'text-warning'],
    ['icon' => 'bi-clock', 'value' => $nextClassTime ?? 'N/A', 'label' => 'Next Class', 'color' => 'text-secondary'],
    ['icon' => 'bi-building', 'value' => $availableRoomsCount ?? 0, 'label' => 'Available Rooms', 'color' => 'text-success', 'id' => 'available-rooms-count'],
];
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => true, 'route' => 'student.home'],
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
  <title>SmartDoor – Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --gold: #F5A800; --navy: #1B2A5E; }
    body { background: #F4F6FA; font-family: 'Segoe UI', sans-serif; }

    /* Sidebar */
    #sidebar { width: 230px; min-height: 100vh; background: #fff; border-right: 1px solid #e8eaf0; }
    .brand-icon { background: var(--gold); border-radius: 10px; width: 42px; height: 42px; display:grid; place-items:center; }
    .nav-link { color: #555; border-radius: 8px; padding: .55rem 1rem; font-weight: 500; }
    .nav-link:hover, .nav-link.active { background: #F0F4FF; color: var(--navy); }
    .nav-link.active::after { content:''; display:inline-block; width:7px; height:7px; background:var(--navy); border-radius:50%; margin-left:auto; }
    .avatar { width:38px; height:38px; background:var(--navy); border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:700; font-size:.85rem; }

    /* Stat cards */
    .stat-card { border-radius: 14px; border: 1px solid #e8eaf0; background: #fff; }

    /* Next class banner */
    .next-banner { background: var(--gold); border-radius: 16px; }
    .next-banner .btn-view { background: var(--navy); color: #fff; border-radius: 10px; flex:1; }
    .next-banner .btn-nav  { background: rgba(255,255,255,.25); color: var(--navy); border-radius: 10px; font-weight:600; }

    /* Schedule rows */
    .schedule-row { border-radius: 10px; transition: background .15s; }
    .schedule-row:hover { background: #F0F4FF; }
    .time-badge { background: #F0F4FF; border-radius: 8px; font-size: .7rem; font-weight: 700; color: var(--navy); line-height:1.1; width: 52px; text-align:center; padding: 4px 0; }
    .time-badge.pm { color: #E07B00; background: #FFF3DC; }

    /* Quick actions */
    .qa-card { border-radius: 14px; border: 1px solid #e8eaf0; background: #fff; cursor:pointer; transition: box-shadow .15s; }
    .qa-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .qa-icon { width:42px; height:42px; border-radius:10px; display:grid; place-items:center; font-size:1.2rem; }
    .notice-card { background: #fff; border-radius: 14px; border: 1px solid #e8eaf0; }
    .home-empty { padding: 2.25rem 1rem; text-align: center; color: #6b7280; }
    .home-empty-icon { width: 46px; height: 46px; margin: 0 auto 10px; display: grid; place-items: center; border-radius: 14px; background: #F0F4FF; color: var(--navy); font-size: 1.3rem; }
    .student-home-hero { position:relative; overflow:hidden; margin:0 -32px 24px; padding:34px 32px 30px; color:#fff; background:linear-gradient(90deg,rgba(7,14,43,.94) 0%,rgba(11,22,64,.78) 46%,rgba(29,78,216,.3) 100%),url('/images/map.png') center/cover no-repeat; }
    .student-home-hero-copy { position:relative; z-index:1; margin-bottom:24px; }
    .student-home-hero h3 { color:#fff; font-size:1.55rem; }
    .student-home-hero .hero-subtitle { color:rgba(255,255,255,.68); }
    .student-home-stats { position:relative; z-index:1; padding:16px; border:1px solid #dce6f5; border-radius:18px; background:rgba(255,255,255,.96); box-shadow:0 10px 28px rgba(7,22,64,.14); }
    .student-home-stats .stat-card { color:var(--student-text); background:#f8faff; border-color:#dce6f5; }
    .student-home-stats .stat-card .fs-2 { color:var(--student-text) !important; }
    .student-home-stats .stat-card .text-muted { color:#7585a0 !important; }
    .student-home-stats .stat-card:hover { background:#fff; }
    @media (max-width:768px) { .student-home-hero { margin:0 -16px 20px; padding:26px 20px 24px; } }
    @media (max-width: 768px) { #sidebar { width: 100%; min-height: auto; } body > .d-flex { display: block !important; } main { padding: 1.25rem !important; } }
  </style>
  <style>
    .is-summary-loading { color: transparent !important; min-width: 2.5rem; min-height: 2rem; border-radius: 6px; background: linear-gradient(90deg,#eef1f5 25%,#fff 50%,#eef1f5 75%); background-size: 200% 100%; animation: summarySkeleton 1.35s ease-in-out infinite; }
    @keyframes summarySkeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    @media (prefers-reduced-motion: reduce) { .is-summary-loading { animation: none; background: #eef1f5; } }
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
      <div><h5 class="fw-bold mb-0">Home</h5><small class="text-muted">{{ now()->format('l, F j, Y') }}</small></div>
      <span class="text-muted small"><i class="bi bi-person-check me-1"></i>Student dashboard</span>
    </div>

    <section class="student-home-hero">
      <div class="student-home-hero-copy">
        <h3 class="fw-bold mb-0">Good morning, {{ $firstName }}!</h3>
        <p class="hero-subtitle mb-0">Here's your schedule for today</p>
      </div>
      <div class="student-home-stats">
        <div class="row g-3 mb-0">
          @foreach ($stats as $s)
            <div class="col-md-4">
              <div class="stat-card p-4">
                <i class="bi {{ $s['icon'] }} fs-4 {{ $s['color'] }}"></i>
                <div class="fs-2 fw-bold mt-2" @isset($s['id']) id="{{ $s['id'] }}" @endisset @if($s['label'] === "Today's Classes") id="today-classes-count" @elseif($s['label'] === 'Next Class') id="next-class-time" @endif>{{ $s['value'] }}</div>
                <div class="text-muted small">{{ $s['label'] }}</div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- Next Class Banner -->
    @if (!empty($todaySchedules) && $todaySchedules->count() > 0)
      @php
        $nextClass = $todaySchedules->first(fn($s) => \Carbon\Carbon::parse($s->start_at)->greaterThan(now()));
      @endphp
      @if ($nextClass)
        <div class="next-banner p-4 mb-4">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="fw-bold small" style="color:var(--navy);letter-spacing:.05em">NEXT CLASS</span>
            <span class="badge rounded-pill" style="background:var(--navy);padding:.5rem .9rem">Starts in {{ now()->diffInMinutes(\Carbon\Carbon::parse($nextClass->start_at)) }} min</span>
          </div>
          <h3 class="fw-bold mb-1" style="color:var(--navy)">{{ $nextClass->course_code ?? 'Course' }}</h3>
          <p class="mb-3" style="color:var(--navy);opacity:.75">{{ $nextClass->block_section ?? 'N/A' }} • {{ $nextClass->faculty_name ?? 'Faculty' }}</p>
          <div class="d-flex gap-3 mb-3 small" style="color:var(--navy)">
            <span><i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($nextClass->start_at)->format('H:i A') }} – {{ \Carbon\Carbon::parse($nextClass->end_at ?? $nextClass->start_at)->format('H:i A') }}</span>
            <span><i class="bi bi-geo-alt me-1"></i>{{ $nextClass->classroom_name ?? 'Room TBA' }}</span>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('student.checkingRoom') }}" class="btn btn-view py-2 px-4">View Room Details</a>
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(($nextClass->classroom_name ?? 'Classroom') . ' ' . ($nextClass->building ?? '')) }}" class="btn btn-nav py-2 px-4" target="_blank" rel="noopener">Navigate</a>
          </div>
        </div>
      @endif
    @endif

    <!-- Schedule + Quick Actions -->
    <div class="row g-4">

      <!-- Today's Schedule -->
      <div class="col-lg-7">
        <div class="bg-white rounded-4 p-4 border">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Today's Schedule</h6>
          </div>
          @php($todayRows = collect($todaySchedules ?? []))
          @forelse ($todayRows as $row)
            <a href="{{ route('student.schedule') }}" class="schedule-row d-flex align-items-center gap-3 p-2 mb-2 text-decoration-none text-reset">
              <div class="time-badge {{ \Carbon\Carbon::parse($row->start_at)->greaterThan(now()) ? 'am' : 'pm' }}">
                {{ \Carbon\Carbon::parse($row->start_at)->format('H:i') }}<br>{{ \Carbon\Carbon::parse($row->start_at)->format('A') }}
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold small">
                  {{ $row->course?->code ?? $row->course_code ?? 'Course' }}
                  <span class="badge bg-light text-secondary ms-1">{{ $row->block_section ?? 'N/A' }}</span>
                  @if (\Carbon\Carbon::parse($row->start_at)->greaterThan(now()))
                    <span class="badge bg-warning text-dark ms-1">SOON</span>
                  @endif
                </div>
                <div class="text-muted" style="font-size:.75rem">
                  <i class="bi bi-person me-1"></i>{{ $row->course?->instructor?->name ?? $row->faculty_name ?? 'Faculty' }}
                  <i class="bi bi-geo-alt ms-2 me-1"></i>{{ $row->classroom?->name ?? $row->classroom_name ?? 'Room TBA' }}
                </div>
              </div>
              <i class="bi bi-chevron-right text-muted"></i>
            </a>
          @empty
            <div class="home-empty">
              <div class="home-empty-icon"><i class="bi bi-calendar-check"></i></div>
              <div class="fw-semibold text-dark">No enrolled classes today</div>
              <p class="small mb-3">Your schedule is clear for today.</p>
            </div>
          @endforelse
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="col-lg-5">
        <h6 class="fw-bold mb-3">Quick Actions</h6>

        <a href="{{ route('student.checkingRoom') }}" class="qa-card p-3 mb-3 d-flex align-items-center gap-3" style="text-decoration: none; color: inherit;">
          <div class="qa-icon bg-light" style="color:var(--navy)"><i class="bi bi-building"></i></div>
          <div><div class="fw-semibold small">Find Available Rooms</div><div class="text-muted" style="font-size:.75rem">Search for vacant classrooms</div></div>
        </a>

        <div class="notice-card p-3">
          <div class="d-flex gap-2 align-items-start">
            <div class="qa-icon bg-light text-primary flex-shrink-0"><i class="bi bi-info-circle"></i></div>
            <div>
              <div class="fw-semibold small">Stay up to date</div>
              <p class="text-muted mb-0" style="font-size:.78rem">Room changes and attendance updates will appear here when they are available.</p>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /row -->
  </main>
</div>
<script>
  (function () {
    var todayClassesCount = document.getElementById('today-classes-count');
    var nextClassTime = document.getElementById('next-class-time');
    var availableRoomsCount = document.getElementById('available-rooms-count');
    var homeSummaryLoaded = false;

    async function refreshHomeSummary() {
      if (!todayClassesCount && !nextClassTime && !availableRoomsCount) {
        return;
      }

      if (!homeSummaryLoaded) {
        [todayClassesCount, nextClassTime, availableRoomsCount].forEach(function (element) {
          element?.classList.add('is-summary-loading');
        });
      }

      try {
        var response = await fetch('{{ route('student.home.summary') }}', {
          credentials: 'same-origin',
          cache: 'no-store',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          return;
        }

        var payload = await response.json();
        if (!payload.success) return;
        if (todayClassesCount) todayClassesCount.textContent = String(payload.today_classes);
        if (nextClassTime) nextClassTime.textContent = payload.next_class;
        if (availableRoomsCount) availableRoomsCount.textContent = String(payload.available_rooms);
        [todayClassesCount, nextClassTime, availableRoomsCount].forEach(function (element) {
          element?.classList.remove('is-summary-loading');
        });
        homeSummaryLoaded = true;
      } catch (error) {
        [todayClassesCount, nextClassTime, availableRoomsCount].forEach(function (element) {
          element?.classList.remove('is-summary-loading');
        });
      }
    }

    refreshHomeSummary();
    setInterval(refreshHomeSummary, 10000);
  }());
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>