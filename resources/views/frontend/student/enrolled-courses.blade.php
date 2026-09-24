@php
$studentId = optional($student)->student_id ?? 'N/A';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartDoor - Enrolled Courses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --navy: #1B2A5E; }
    body { background: #F4F6FA; font-family: 'Segoe UI', sans-serif; }
    .course-card { border: 1px solid #e8eaf0; border-radius: 14px; background: #fff; height: 100%; }
    .course-code { color: var(--navy); font-size: .75rem; font-weight: 800; letter-spacing: .06em; }
    .course-meta { color: #6b7280; font-size: .8rem; }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">
  @include('frontend.student._sidebar')

  <main class="flex-grow-1 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="fw-bold mb-0">Enrolled courses</h5>
        <small class="text-muted">Open a course to view its schedule and attendance records</small>
      </div>
      <span class="text-muted small"><i class="bi bi-person-check me-1"></i>{{ $studentId }}</span>
    </div>

    <div class="row g-3">
      @forelse ($courses as $course)
        <div class="col-md-6 col-xl-4">
          <a href="{{ route('student.courses.overview', $course) }}" class="text-decoration-none text-reset">
            <div class="course-card p-4">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <span class="course-code">{{ $course->code }}</span>
                <i class="bi bi-arrow-up-right text-primary"></i>
              </div>
              <h6 class="fw-bold mb-3">{{ $course->title }}</h6>
              <div class="course-meta"><i class="bi bi-person me-2"></i>{{ $course->instructor?->name ?? 'Instructor TBA' }}</div>
              <div class="course-meta mt-3">View schedule and attendance <i class="bi bi-chevron-right ms-1"></i></div>
            </div>
          </a>
        </div>
      @empty
        <div class="col-12">
          <div class="bg-white rounded-4 border p-5 text-center text-muted">
            <i class="bi bi-book fs-1"></i>
            <p class="mt-3 mb-3">You have no enrolled courses yet.</p>
            <a href="{{ route('student.courses') }}" class="btn btn-primary">Browse courses</a>
          </div>
        </div>
      @endforelse
    </div>
  </main>
</div>
</body>
</html>
