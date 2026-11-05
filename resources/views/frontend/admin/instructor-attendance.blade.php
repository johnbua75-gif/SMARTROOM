<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Attendance - SmartRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root { --navy:#0b1640; --blue:#1a2f80; --gold:#f5c518; --bg:#f4f6f9; --line:#e8ecf3; --muted:#6b7280; --green:#15803d; --red:#b91c1c; }
        body { margin:0; background:var(--bg); color:#111827; font-family:Inter,sans-serif; }
        .main-content { min-height:100vh; padding:34px 38px; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:24px; }
        h1 { margin:0; color:var(--navy); font-size:1.65rem; }
        .subhead { margin:7px 0 0; color:var(--muted); font-size:.9rem; }
        .button { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:0; border-radius:8px; background:var(--navy); color:#fff; font:600 .85rem Inter,sans-serif; text-decoration:none; cursor:pointer; }
        .filters,.stat,.table-wrap { background:#fff; border:1px solid var(--line); border-radius:12px; }
        .filters { display:flex; flex-wrap:wrap; align-items:end; gap:14px; padding:16px; margin-bottom:18px; }
        .field { display:grid; gap:6px; min-width:190px; }
        label { color:#374151; font-size:.75rem; font-weight:700; }
        input,select { min-height:40px; padding:8px 10px; border:1px solid #d1d5db; border-radius:7px; background:#fff; color:#111827; font:500 .85rem Inter,sans-serif; }
        .stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
        .stat { padding:16px; }
        .stat-label { color:var(--muted); font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .stat-value { margin-top:8px; color:var(--navy); font-size:1.65rem; font-weight:800; }
        .notice { margin-bottom:18px; padding:12px 14px; border:1px solid #fde68a; border-radius:9px; background:#fffbeb; color:#854d0e; font-size:.83rem; line-height:1.5; }
        .table-wrap { overflow:auto; }
        table { width:100%; border-collapse:collapse; text-align:left; }
        th,td { padding:12px 14px; border-bottom:1px solid #edf0f5; font-size:.8rem; vertical-align:top; }
        th { background:#f9fafb; color:#4b5563; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
        td { color:#374151; }
        tbody tr:last-child td { border-bottom:0; }
        .primary { color:var(--navy); font-weight:700; }
        .muted { margin-top:4px; color:var(--muted); font-size:.75rem; }
        .pill { display:inline-block; padding:4px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:.7rem; font-weight:700; white-space:nowrap; }
        .pill.cancelled { background:#fef2f2; color:var(--red); }
        .pill.missing { background:#f3f4f6; color:#4b5563; }
        .empty { padding:36px 16px; color:var(--muted); text-align:center; }
        @media(max-width:900px) { .main-content { padding:24px 16px; } .stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:560px) { .page-head { flex-direction:column; } .stats { grid-template-columns:1fr 1fr; } }
    </style>
    @include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>
    @include('frontend.admin.partials.sidebar')

    <main class="main-content">
        <header class="page-head">
            <div>
                <h1>Instructor Attendance</h1>
                <p class="subhead">{{ $weekStart->format('M j') }}–{{ $weekEnd->format('M j, Y') }} · Scheduled classes and recorded class sessions</p>
            </div>
            <a class="button" href="{{ route('admin.instructor-attendance.export', request()->query()) }}">
                <i class="fas fa-download" aria-hidden="true"></i> Download CSV
            </a>
        </header>

        <form class="filters" method="GET" action="{{ route('admin.instructor-attendance') }}">
            <div class="field">
                <label for="week">Week containing</label>
                <input id="week" name="week" type="date" value="{{ $weekStart->toDateString() }}">
            </div>
            <div class="field">
                <label for="instructor_id">Instructor</label>
                <select id="instructor_id" name="instructor_id">
                    <option value="">All instructors</option>
                    @foreach ($instructors as $instructor)
                        <option value="{{ $instructor->id }}" @selected($selectedInstructorId === (int) $instructor->id)>
                            {{ $instructor->name }}{{ $instructor->department ? ' · '.$instructor->department : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="button" type="submit"><i class="fas fa-filter" aria-hidden="true"></i> Apply filters</button>
        </form>

        <section class="stats" aria-label="Weekly summary">
            <article class="stat"><div class="stat-label">Scheduled classes</div><div class="stat-value">{{ number_format($stats['scheduled']) }}</div></article>
            <article class="stat"><div class="stat-label">Classes with a session</div><div class="stat-value">{{ number_format($stats['recordedClasses']) }}</div></article>
            <article class="stat"><div class="stat-label">Attendance sessions</div><div class="stat-value">{{ number_format($stats['sessions']) }}</div></article>
            <article class="stat"><div class="stat-label">Cancelled classes</div><div class="stat-value">{{ number_format($stats['cancelled']) }}</div></article>
        </section>

        <p class="notice">
            A recorded session means an attendance session exists for the scheduled class. It is not proof by itself that the instructor taught the class; “No session logged” is not automatically classified as an absence.
        </p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date / Time</th><th>Instructor</th><th>Course</th><th>Room</th><th>Record</th><th>Session Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $schedule = $row['schedule'];
                            $latestSession = $row['sessions']->last();
                            $cancelled = $schedule?->status === 'cancelled';
                        @endphp
                        <tr>
                            <td>
                                <div class="primary">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('D, M j') }}</div>
                                <div class="muted">{{ $schedule?->start_at?->format('g:i A') ?? 'Unscheduled' }}{{ $schedule?->end_at ? ' – '.$schedule->end_at->format('g:i A') : '' }}</div>
                            </td>
                            <td><div class="primary">{{ $row['instructor'] }}</div><div class="muted">{{ $row['department'] ?: 'No department' }}</div></td>
                            <td><div class="primary">{{ $row['course'] ?: 'Unassigned course' }}</div><div class="muted">{{ $row['section'] ?: 'No section' }}</div></td>
                            <td>{{ $row['room'] ?: 'No room' }}</td>
                            <td>
                                @if ($cancelled)
                                    <span class="pill cancelled">Cancelled</span>
                                @elseif ($row['sessions']->isNotEmpty())
                                    <span class="pill">{{ $row['sessions']->count() }} session(s) logged</span>
                                @else
                                    <span class="pill missing">No session logged</span>
                                @endif
                            </td>
                            <td>
                                @if ($latestSession)
                                    <div class="primary">{{ ucfirst($latestSession->status) }}</div>
                                    <div class="muted">Started {{ $latestSession->started_at ?: '—' }} · Ended {{ $latestSession->ended_at ?: '—' }}</div>
                                    <div class="muted">{{ $row['checkins'] }} student check-in(s)</div>
                                @else
                                    <span class="muted">No session details</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="6">No schedules or attendance sessions found for this week and filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
