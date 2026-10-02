@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$initials = collect(explode(' ', $studentName))->filter()->map(fn ($word) => strtoupper($word[0]))->join('');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartDoor - Courses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --course-gold: #f5c518; --course-navy: #0b1640; --course-ink: #16213f; --course-muted: #71809a; --course-border: #e3e8f2; --course-bg: #f4f6fb; --course-green: #168a67; }
    body { background: var(--course-bg); color: var(--course-ink); font-family: 'DM Sans', sans-serif; }
    .courses-title, .course-overview h2, .course-card h3, .course-code, .course-filter, .course-count, .student-id { font-family: 'Plus Jakarta Sans', sans-serif; }
    .courses-page { max-width: 1440px; margin: 0 auto; }
    .courses-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
    .courses-kicker { color: var(--course-muted); font-size: .68rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .courses-title { margin: 5px 0 6px; color: var(--course-navy); font-size: clamp(1.65rem, 2.3vw, 2.35rem); font-weight: 800; letter-spacing: -.035em; }
    .courses-intro { max-width: 54ch; margin: 0; color: var(--course-muted); font-size: .92rem; }
    .student-id { display: inline-flex; align-items: center; gap: 8px; padding: 9px 12px; color: var(--course-navy); background: #fff; border: 1px solid var(--course-border); border-radius: 10px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .student-id i { color: #5870b8; font-size: 1rem; }
    .course-overview { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 24px; margin-bottom: 25px; padding: 24px 28px; overflow: hidden; position: relative; background: var(--course-navy); border-radius: 16px; box-shadow: 0 12px 28px rgba(11,22,64,.14); }
    .course-overview::after { content: ''; position: absolute; right: -46px; bottom: -80px; width: 230px; height: 180px; border: 1px solid rgba(245,197,24,.22); border-radius: 50%; transform: rotate(-18deg); }
    .course-overview h2 { position: relative; z-index: 1; margin: 0 0 6px; color: #fff; font-size: 1.22rem; font-weight: 800; letter-spacing: -.02em; }
    .course-overview p { position: relative; z-index: 1; max-width: 58ch; margin: 0; color: rgba(255,255,255,.68); font-size: .85rem; }
    .overview-stats { position: relative; z-index: 1; display: flex; align-items: center; gap: 24px; }
    .overview-stat { min-width: 75px; }
    .overview-stat strong { display: block; color: var(--course-gold); font-size: 1.45rem; line-height: 1; }
    .overview-stat span { display: block; margin-top: 5px; color: rgba(255,255,255,.62); font-size: .7rem; font-weight: 700; }
    .course-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .course-search-wrap { display: flex; align-items: center; flex: 1 1 520px; max-width: 650px; padding: 0 15px; background: #fff; border: 1px solid var(--course-border); border-radius: 10px; box-shadow: 0 3px 10px rgba(15,23,41,.035); }
    .course-search-wrap i { color: #8290aa; font-size: 1rem; }
    .course-search { min-width: 0; padding: 12px 10px; color: var(--course-ink); background: transparent; border: 0; box-shadow: none !important; }
    .course-search:focus { outline: none; }
    .course-filter { width: auto; min-width: 142px; padding: 11px 34px 11px 13px; color: var(--course-navy); background-color: #fff; border: 1px solid var(--course-border); border-radius: 10px; font-size: .82rem; font-weight: 700; }
    .course-count { color: var(--course-muted); font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .course-card { position: relative; display: flex; flex-direction: column; height: 100%; overflow: hidden; background: #fff; border: 1px solid var(--course-border); border-radius: 14px; box-shadow: 0 4px 14px rgba(15,23,41,.045); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .course-card::before { content: ''; display: block; height: 4px; background: var(--course-gold); }
    .course-card:hover { border-color: #cbd5eb; box-shadow: 0 12px 24px rgba(15,23,41,.09); transform: translateY(-3px); }
    .course-card-body { display: flex; flex: 1; flex-direction: column; padding: 21px 22px 22px; }
    .course-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 22px; }
    .course-code { color: var(--course-navy); font-size: .76rem; font-weight: 800; letter-spacing: .06em; }
    .course-card h3 { min-height: 2.9rem; margin: 0 0 18px; color: var(--course-ink); font-size: 1.04rem; font-weight: 800; line-height: 1.4; letter-spacing: -.015em; }
    .course-meta { display: flex; align-items: flex-start; gap: 9px; min-height: 22px; color: var(--course-muted); font-size: .82rem; line-height: 1.45; }
    .course-meta i { flex: 0 0 16px; margin-top: 2px; color: #8090af; }
    .course-description { display: -webkit-box; overflow: hidden; min-height: 42px; margin: 13px 0 24px; color: #8a96aa; font-size: .78rem; line-height: 1.55; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .enrolled-badge { display: inline-flex; align-items: center; gap: 4px; padding: 5px 8px; color: var(--course-green); background: #eaf7f1; border: 1px solid #ccebdc; border-radius: 999px; font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .course-actions { margin-top: auto; padding-top: 2px; }
    .btn-enroll, .btn-unenroll, .btn-outline-primary { width: 100%; padding: 10px 14px; border-radius: 8px; font-size: .8rem; font-weight: 800; transition: all .2s ease; }
    .btn-enroll { color: var(--course-navy); background: #fff; border: 1px solid #cbd4e6; }
    .btn-enroll:hover { color: var(--course-navy); background: #f5f7fc; border-color: #95a8d0; }
    .btn-unenroll { color: #b54747; background: #fff; border: 1px solid #e8caca; }
    .btn-unenroll:hover { color: #9f3030; background: #fff6f6; border-color: #df9b9b; }
    .btn-outline-primary { color: #fff; background: var(--course-navy); border-color: var(--course-navy); }
    .btn-outline-primary:hover { color: var(--course-navy); background: #eef1fb; border-color: var(--course-navy); }
    .course-empty { padding: 56px 24px; color: var(--course-muted); text-align: center; background: #fff; border: 1px dashed #cbd5e6; border-radius: 14px; }
    .course-empty i { display: block; margin-bottom: 12px; color: #9aa8c0; font-size: 2rem; }
    .course-empty p { margin: 0; font-size: .88rem; }
    @media (max-width: 767.98px) {
      .courses-topbar, .course-toolbar { align-items: stretch; flex-direction: column; }
      .student-id, .course-search-wrap, .course-filter { max-width: none; width: 100%; }
      .course-overview { grid-template-columns: 1fr; padding: 21px; }
      .overview-stats { gap: 34px; }
    }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">
  @include('frontend.student._sidebar')

  <main class="flex-grow-1 p-4">
    <div class="courses-page">
      <header class="courses-topbar">
        <div>
          <div class="courses-kicker">Academic workspace</div>
          <h1 class="courses-title">Courses</h1>
          <p class="courses-intro">Build your semester schedule by enrolling in the subjects you need for attendance and class access.</p>
        </div>
        <div class="d-flex flex-column align-items-md-end gap-3">
          <span class="student-id"><i class="bi bi-person-badge"></i>{{ $studentId }}</span>
          <a href="{{ route('student.courses.enrolled') }}" class="btn btn-sm btn-outline-primary" style="width:auto"><i class="bi bi-calendar2-check me-1"></i>View enrolled courses</a>
        </div>
      </header>

      <section class="course-overview" aria-labelledby="courseOverviewTitle">
        <div>
          <h2 id="courseOverviewTitle">Find your next subject</h2>
          <p>Search the available catalog, review the instructor, and keep your enrolled subjects connected to your schedule.</p>
        </div>
        <div class="overview-stats" aria-label="Course summary">
          <div class="overview-stat"><strong>{{ $courses->count() }}</strong><span>Available</span></div>
          <div class="overview-stat"><strong>{{ count($enrolledCourseIds) }}</strong><span>Enrolled</span></div>
        </div>
      </section>

    @if (session('success'))
      <div class="alert alert-success border-0" role="alert">{{ session('success') }}</div>
    @endif

    <div class="course-toolbar">
      <label class="course-search-wrap" for="course-search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="course-search" class="course-search form-control" placeholder="Search by code, subject, or instructor" autocomplete="off">
      </label>
      <div class="d-flex align-items-center gap-3">
        <select id="course-filter" class="course-filter" aria-label="Filter courses">
          <option value="all">All courses</option>
          <option value="enrolled">Enrolled</option>
          <option value="available">Not enrolled</option>
        </select>
        <span id="course-search-count" class="course-count">{{ $courses->count() }} courses</span>
      </div>
    </div>

    <div class="row g-3">
      @forelse ($courses as $course)
        @php($isEnrolled = in_array($course->id, $enrolledCourseIds, true))
        <div class="col-md-6 col-xl-4 course-result" data-course-status="{{ $isEnrolled ? 'enrolled' : 'available' }}" data-course-search="{{ strtolower($course->code.' '.$course->title.' '.($course->instructor?->name ?? '')) }}">
          <article class="course-card">
            <div class="course-card-body">
            <div class="course-card-head">
              <span class="course-code">{{ $course->code }}</span>
              @if ($isEnrolled)
                <span class="enrolled-badge"><i class="bi bi-check-circle"></i>Enrolled</span>
              @endif
            </div>
            <h3>{{ $course->title }}</h3>
            <div class="course-meta"><i class="bi bi-person"></i><span>{{ $course->instructor?->name ?? 'Instructor TBA' }}</span></div>
            @if ($course->description)
              <p class="course-description">{{ $course->description }}</p>
            @else
              <p class="course-description">Course details will be available soon.</p>
            @endif

            <div class="course-actions">
              @if ($isEnrolled)
                <a href="{{ route('student.courses.overview', $course) }}" class="btn btn-outline-primary mb-2"><i class="bi bi-calendar3 me-1"></i>View schedule &amp; attendance</a>
                <form method="POST" action="{{ route('student.courses.unenroll', $course) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-unenroll"><i class="bi bi-x-circle me-1"></i>Remove course</button>
                </form>
              @else
                <form method="POST" action="{{ route('student.courses.enroll', $course) }}">
                  @csrf
                  <button type="submit" class="btn btn-enroll"><i class="bi bi-plus-circle me-1"></i>Enroll in course</button>
                </form>
              @endif
            </div>
            </div>
          </article>
        </div>
      @empty
        <div class="col-12">
          <div class="course-empty"><i class="bi bi-journal-x"></i><p>No courses are available for enrollment yet.</p></div>
        </div>
      @endforelse
    </div>
    <div id="course-search-empty" class="course-empty d-none">
      <i class="bi bi-search"></i>
      <p>No courses match your current filters.</p>
    </div>
  </main>
</div>
<script>
  (function () {
    var searchInput = document.getElementById('course-search');
    var countLabel = document.getElementById('course-search-count');
    var filterSelect = document.getElementById('course-filter');
    var emptyState = document.getElementById('course-search-empty');
    var courseCards = Array.from(document.querySelectorAll('.course-result'));

    function filterCourses() {
      var query = String(searchInput?.value || '').trim().toLowerCase();
      var filter = String(filterSelect?.value || 'all');
      var visibleCount = 0;

      courseCards.forEach(function (card) {
        var matchesSearch = query === '' || card.dataset.courseSearch.includes(query);
        var matchesFilter = filter === 'all' || card.dataset.courseStatus === filter;
        var visible = matchesSearch && matchesFilter;
        card.classList.toggle('d-none', !visible);
        if (visible) {
          visibleCount += 1;
        }
      });

      if (countLabel) {
        countLabel.textContent = visibleCount + (visibleCount === 1 ? ' course' : ' courses');
      }

      emptyState?.classList.toggle('d-none', visibleCount !== 0 || courseCards.length === 0);
    }

    searchInput?.addEventListener('input', filterCourses);
    filterSelect?.addEventListener('change', filterCourses);
  }());
</script>
</body>
</html>
