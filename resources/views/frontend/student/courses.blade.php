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
  <style>
    :root { --gold: #F5A800; --navy: #1B2A5E; }
    body { background: #F4F6FA; font-family: 'Segoe UI', sans-serif; }
    .course-card { border: 1px solid #e8eaf0; border-radius: 14px; background: #fff; height: 100%; }
    .course-code { color: var(--navy); font-size: .75rem; font-weight: 800; letter-spacing: .06em; }
    .course-card h6 { min-height: 2.75rem; }
    .course-meta { color: #6b7280; font-size: .8rem; }
    .enrolled-badge { background: #e8f7ee; color: #168044; font-size: .72rem; }
    .btn-enroll { background: var(--navy); border-color: var(--navy); }
    .btn-enroll:hover { background: #263d85; border-color: #263d85; }
    .btn-unenroll { color: #6b7280; border-color: #d9dee8; }
    .btn-unenroll:hover { color: #b42318; border-color: #f0b7b2; background: #fff7f6; }
    .course-search { border: 1px solid #d9dee8; border-radius: 10px; background: #fff; }
    .course-search:focus { border-color: var(--navy); box-shadow: 0 0 0 .2rem rgba(27, 42, 94, .12); }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">
  @include('frontend.student._sidebar')

  <main class="flex-grow-1 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="fw-bold mb-0">Courses</h5>
        <small class="text-muted">Enroll in courses for attendance and schedules</small>
      </div>
      <div class="d-flex align-items-center gap-3">
        <a href="{{ route('student.courses.enrolled') }}" class="btn btn-sm btn-enroll text-white">
          <i class="bi bi-bookmark-check me-1"></i>Enrolled courses
        </a>
        <span class="text-muted small"><i class="bi bi-person-check me-1"></i>{{ $studentId }}</span>
      </div>
    </div>

    <div class="mb-4">
      <h3 class="fw-bold mb-1">Course enrollment</h3>
      <p class="text-muted mb-0">Your enrolled courses will appear in your schedule and attendance records.</p>
    </div>

    @if (session('success'))
      <div class="alert alert-success border-0" role="alert">{{ session('success') }}</div>
    @endif

    <div class="row mb-3">
      <div class="col-lg-7">
        <label class="visually-hidden" for="course-search">Search courses</label>
        <div class="input-group">
          <span class="input-group-text course-search border-end-0"><i class="bi bi-search"></i></span>
          <input type="search" id="course-search" class="form-control course-search border-start-0" placeholder="Search by course code, title, or instructor" autocomplete="off">
        </div>
      </div>
      <div class="col-lg-5 d-flex align-items-center justify-content-lg-end mt-2 mt-lg-0">
        <span id="course-search-count" class="text-muted small">{{ $courses->count() }} courses</span>
      </div>
    </div>

    <div class="row g-3">
      @forelse ($courses as $course)
        @php($isEnrolled = in_array($course->id, $enrolledCourseIds, true))
        <div class="col-md-6 col-xl-4 course-result" data-course-search="{{ strtolower($course->code.' '.$course->title.' '.($course->instructor?->name ?? '')) }}">
          <div class="course-card p-4 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
              <span class="course-code">{{ $course->code }}</span>
              @if ($isEnrolled)
                <span class="badge enrolled-badge rounded-pill"><i class="bi bi-check-circle me-1"></i>Enrolled</span>
              @endif
            </div>
            <h6 class="fw-bold mb-2">{{ $course->title }}</h6>
            <div class="course-meta mb-1"><i class="bi bi-person me-2"></i>{{ $course->instructor?->name ?? 'Instructor TBA' }}</div>
            @if ($course->description)
              <p class="course-meta mb-4">{{ $course->description }}</p>
            @else
              <div class="mb-4"></div>
            @endif

            <div class="mt-auto">
              @if ($isEnrolled)
                <a href="{{ route('student.courses.overview', $course) }}" class="btn btn-outline-primary w-100 mb-2">View Schedule &amp; Attendance</a>
                <form method="POST" action="{{ route('student.courses.unenroll', $course) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-unenroll w-100">Remove Course</button>
                </form>
              @else
                <form method="POST" action="{{ route('student.courses.enroll', $course) }}">
                  @csrf
                  <button type="submit" class="btn btn-enroll text-white w-100">Enroll in Course</button>
                </form>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="bg-white rounded-4 border p-5 text-center text-muted">No courses are available for enrollment yet.</div>
        </div>
      @endforelse
    </div>
    <div id="course-search-empty" class="bg-white rounded-4 border p-5 text-center text-muted d-none">
      <i class="bi bi-search fs-1"></i>
      <p class="mt-3 mb-0">No courses match your search.</p>
    </div>
  </main>
</div>
<script>
  (function () {
    var searchInput = document.getElementById('course-search');
    var countLabel = document.getElementById('course-search-count');
    var emptyState = document.getElementById('course-search-empty');
    var courseCards = Array.from(document.querySelectorAll('.course-result'));

    function filterCourses() {
      var query = String(searchInput?.value || '').trim().toLowerCase();
      var visibleCount = 0;

      courseCards.forEach(function (card) {
        var visible = query === '' || card.dataset.courseSearch.includes(query);
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
  }());
</script>
</body>
</html>
