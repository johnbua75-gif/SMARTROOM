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
    :root { --enrolled-navy: #0b1640; --enrolled-ink: #16213f; --enrolled-muted: #71809a; --enrolled-border: #e3e8f2; }
    body { background: #f4f6fb; color: var(--enrolled-ink); }
    .enrolled-page { max-width: 1440px; margin: 0 auto; }
    .enrolled-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--enrolled-border); }
    .enrolled-title { margin: 0 0 6px; color: var(--enrolled-navy); font-size: 1.8rem; font-weight: 800; }
    .enrolled-intro { margin: 0; color: var(--enrolled-muted); font-size: .92rem; }
    .enrolled-details { display: flex; align-items: center; gap: 12px; }
    .enrolled-count, .student-id { display: inline-flex; align-items: center; gap: 8px; min-height: 38px; padding: 8px 12px; background: #fff; border: 1px solid var(--enrolled-border); border-radius: 9px; color: var(--enrolled-navy); font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .enrolled-count i, .student-id i { color: #5870b8; font-size: .95rem; }
    .course-list { display: grid; gap: 12px; }
    .course-card { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, .55fr) minmax(220px, auto); align-items: center; gap: 24px; min-height: 112px; padding: 20px 24px; color: var(--enrolled-ink); text-decoration: none; background: #fff; border: 1px solid var(--enrolled-border); border-radius: 12px; box-shadow: 0 4px 14px rgba(15,23,41,.045); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .course-card:hover { color: var(--enrolled-ink); border-color: #cbd5eb; box-shadow: 0 10px 22px rgba(15,23,41,.08); transform: translateY(-2px); }
    .course-card:focus-visible { outline: 3px solid rgba(37,99,235,.35); outline-offset: 3px; }
    .course-code { display: block; margin-bottom: 7px; color: var(--enrolled-navy); font-size: .75rem; font-weight: 800; letter-spacing: .04em; }
    .course-title { margin: 0; color: var(--enrolled-ink); font-size: 1.05rem; font-weight: 800; line-height: 1.35; }
    .course-meta { display: flex; align-items: center; gap: 9px; color: var(--enrolled-muted); font-size: .84rem; }
    .course-meta i { color: #8090af; }
    .course-open { display: flex; align-items: center; justify-content: flex-end; gap: 10px; color: var(--enrolled-navy); font-size: .82rem; font-weight: 800; }
    .course-open i { color: #5870b8; font-size: 1rem; transition: transform .2s ease; }
    .course-card:hover .course-open i { transform: translateX(3px); }
    .course-empty { padding: 56px 24px; color: var(--enrolled-muted); text-align: center; background: #fff; border: 1px dashed #cbd5e6; border-radius: 12px; }
    .course-empty i { display: block; margin-bottom: 12px; color: #9aa8c0; font-size: 2rem; }
    @media (max-width: 767.98px) {
      .enrolled-header { align-items: flex-start; flex-direction: column; }
      .enrolled-details { flex-wrap: wrap; }
      .course-card { grid-template-columns: minmax(0, 1fr) auto; gap: 14px; padding: 18px; }
      .course-meta { grid-column: 1; }
      .course-open { grid-column: 1 / -1; justify-content: flex-start; }
    }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">
  @include('frontend.student._sidebar')

  <main class="flex-grow-1 p-4">
    <div class="enrolled-page">
      <header class="enrolled-header">
        <div>
          <h1 class="enrolled-title">Enrolled courses</h1>
          <p class="enrolled-intro">Open a course to view its schedule and attendance records</p>
        </div>
        <div class="enrolled-details">
          <span class="enrolled-count"><i class="bi bi-journal-check" aria-hidden="true"></i>{{ $courses->count() }} {{ \Illuminate\Support\Str::plural('course', $courses->count()) }}</span>
          <span class="student-id"><i class="bi bi-person-check" aria-hidden="true"></i>{{ $studentId }}</span>
        </div>
      </header>

      <div class="course-list">
      @forelse ($courses as $course)
        <a href="{{ route('student.courses.overview', $course) }}" class="course-card">
          <div>
            <span class="course-code">{{ $course->code }}</span>
            <h2 class="course-title">{{ $course->title }}</h2>
          </div>
          <div class="course-meta"><i class="bi bi-person" aria-hidden="true"></i><span>{{ $course->instructor?->name ?? 'Instructor TBA' }}</span></div>
          <div class="course-open"><span>View schedule and attendance</span><i class="bi bi-arrow-right" aria-hidden="true"></i></div>
        </a>
      @empty
        <div class="course-empty">
          <i class="bi bi-book" aria-hidden="true"></i>
          <p class="mt-3 mb-3">You have no enrolled courses yet.</p>
          <a href="{{ route('student.courses') }}" class="btn btn-primary">Browse courses</a>
        </div>
      @endforelse
      </div>
    </div>
  </main>
</div>
</body>
</html>
