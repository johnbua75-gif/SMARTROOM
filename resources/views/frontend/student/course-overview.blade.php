@php
$studentId = optional($student)->student_id ?? 'N/A';
$presentCount = $attendanceRecords->where('status', 'present')->count();
$absentCount = $attendanceRecords->where('status', 'absent')->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $course->code }} - SmartDoor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --navy: #1B2A5E; }
    body { background: #F4F6FA; font-family: 'Segoe UI', sans-serif; }
    .panel { border: 1px solid #e8eaf0; border-radius: 14px; background: #fff; }
    .course-code { color: var(--navy); font-size: .8rem; font-weight: 800; letter-spacing: .06em; }
    .muted { color: #6b7280; font-size: .82rem; }
    .stat { border: 1px solid #e8eaf0; border-radius: 12px; background: #fff; }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">
  @include('frontend.student._sidebar')

  <main class="flex-grow-1 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <a href="{{ route('student.courses.enrolled') }}" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Enrolled courses</a>
        <h3 class="fw-bold mb-1 mt-2">{{ $course->title }}</h3>
        <div class="course-code">{{ $course->code }} <span class="muted fw-normal">&middot; {{ $course->instructor?->name ?? 'Instructor TBA' }}</span></div>
      </div>
      <span class="text-muted small"><i class="bi bi-person-check me-1"></i>{{ $studentId }}</span>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-sm-4"><div class="stat p-3"><div class="fw-bold fs-4">{{ $schedules->count() }}</div><div class="muted">Scheduled classes</div></div></div>
      <div class="col-sm-4"><div class="stat p-3"><div class="fw-bold fs-4 text-success">{{ $presentCount }}</div><div class="muted">Present</div></div></div>
      <div class="col-sm-4"><div class="stat p-3"><div class="fw-bold fs-4 text-danger">{{ $absentCount }}</div><div class="muted">Absent</div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="panel p-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-calendar3 me-2"></i>Schedule</h5>
          @forelse ($schedules as $schedule)
            <div class="border-bottom py-3">
              <div class="fw-semibold">{{ $schedule->start_at?->format('l, M j, Y') ?? 'Date TBA' }}</div>
              <div class="muted mt-1">
                <i class="bi bi-clock me-1"></i>{{ $schedule->start_at?->format('g:i A') ?? 'TBA' }} - {{ $schedule->end_at?->format('g:i A') ?? 'TBA' }}
                <span class="ms-3"><i class="bi bi-geo-alt me-1"></i>{{ $schedule->classroom?->name ?? 'Room TBA' }}</span>
              </div>
            </div>
          @empty
            <div class="text-muted py-3">No schedule has been assigned to this course yet.</div>
          @endforelse
        </div>
      </div>

      <div class="col-lg-5">
        <div class="panel p-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-clipboard-check me-2"></i>Attendance records</h5>
          @forelse ($attendanceRecords as $record)
            <div class="border-bottom py-3 d-flex justify-content-between gap-3">
              <div>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($record->session?->session_date ?? $record->created_at)->format('M j, Y') }}</div>
                <div class="muted">{{ $record->remarks ?: 'Attendance session' }}</div>
              </div>
              <span class="badge {{ $record->status === 'present' ? 'text-bg-success' : 'text-bg-danger' }} align-self-start">{{ ucfirst($record->status) }}</span>
            </div>
          @empty
            <div class="text-muted py-3">No attendance records for this course yet.</div>
          @endforelse
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
