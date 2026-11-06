@php
    $weekActivity = collect(['Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0]);

    foreach ($rows as $reportRow) {
        if (! empty($reportRow['date']) && $reportRow['sessions']->isNotEmpty()) {
            $day = \Illuminate\Support\Carbon::parse($reportRow['date'])->format('D');
            $weekActivity->put($day, $weekActivity->get($day, 0) + $reportRow['sessions']->count());
        }
    }

    $weekActivityMax = max(1, (int) $weekActivity->max());
    $selectedInstructor = $selectedInstructorId
        ? $instructors->firstWhere('id', $selectedInstructorId)
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Reports - SmartRoom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root { --report-navy:#0b1640; --report-blue:#1a2f80; --report-gold:#f5c518; --report-bg:#f3f5f9; --report-line:#e5e9f1; --report-muted:#64748b; --report-green:#0f766e; --report-red:#b91c1c; }
        body { margin:0; background:var(--report-bg); color:#182338; font-family:Inter,sans-serif; -webkit-font-smoothing:antialiased; }
        .main-content { min-height:100vh; padding:30px 34px 48px; }
        .report-page { max-width:1600px; margin:0 auto; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:20px; }
        h1 { margin:0; color:var(--report-navy); font-size:1.6rem; letter-spacing:-.035em; }
        .subhead { margin:7px 0 0; color:var(--report-muted); font-size:.86rem; }
        .button { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:40px; padding:9px 15px; border:1px solid var(--report-navy); border-radius:8px; background:var(--report-navy); color:#fff; font:650 .82rem Inter,sans-serif; text-decoration:none; cursor:pointer; transition:background .16s ease,transform .16s ease; }
        .button:hover { background:#172a67; transform:translateY(-1px); }
        .button:focus-visible,input:focus-visible,select:focus-visible { outline:3px solid rgba(37,99,235,.3); outline-offset:2px; }
        .filters,.stat,.table-card,.insight-card { background:#fff; border:1px solid var(--report-line); border-radius:12px; box-shadow:0 2px 8px rgba(11,22,64,.035); }
        .filters { display:flex; flex-wrap:wrap; align-items:end; gap:12px; padding:14px 16px; margin-bottom:16px; }
        .field { display:grid; gap:6px; min-width:190px; }
        label { color:#34435b; font-size:.74rem; font-weight:700; }
        input,select { min-height:40px; padding:8px 11px; border:1px solid #d6dce7; border-radius:7px; background:#fff; color:#182338; font:500 .83rem Inter,sans-serif; }
        .stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px; }
        .stat { position:relative; min-height:108px; padding:17px 18px; overflow:hidden; }
        .stat::before { position:absolute; inset:0 auto 0 0; width:3px; background:var(--report-blue); content:''; }
        .stat:nth-child(2)::before { background:#0f766e; }
        .stat:nth-child(3)::before { background:#d97706; }
        .stat:nth-child(4)::before { background:#64748b; }
        .stat-label { color:#536176; font-size:.75rem; font-weight:600; }
        .stat-value { margin-top:9px; color:var(--report-navy); font-size:1.7rem; font-weight:800; letter-spacing:-.04em; }
        .stat-note { margin-top:3px; color:#8390a3; font-size:.69rem; line-height:1.4; }
        .report-layout { display:grid; grid-template-columns:minmax(0,1fr) 285px; align-items:start; gap:16px; }
        .table-card { min-width:0; overflow:hidden; }
        .card-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid #edf0f5; }
        .card-heading h2 { margin:0; color:var(--report-navy); font-size:.92rem; font-weight:750; letter-spacing:-.015em; }
        .card-heading p { margin:4px 0 0; color:var(--report-muted); font-size:.72rem; }
        .row-count { flex-shrink:0; color:#64748b; font-size:.72rem; font-weight:650; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; min-width:930px; border-collapse:collapse; text-align:left; }
        th,td { padding:12px 13px; border-bottom:1px solid #edf0f5; font-size:.76rem; vertical-align:top; }
        th { background:#f8fafc; color:#536176; font-size:.66rem; font-weight:750; text-transform:uppercase; letter-spacing:.045em; white-space:nowrap; }
        td { color:#3e4b60; }
        tbody tr { transition:background .14s ease; }
        tbody tr:hover { background:#f8faff; }
        tbody tr:last-child td { border-bottom:0; }
        .primary { color:var(--report-navy); font-weight:700; }
        .muted { margin-top:4px; color:#78869b; font-size:.7rem; line-height:1.45; }
        .pill { display:inline-flex; align-items:center; padding:4px 8px; border-radius:999px; background:#e8f4f2; color:#0f766e; font-size:.67rem; font-weight:750; white-space:nowrap; }
        .pill.cancelled { background:#fef0f0; color:var(--report-red); }
        .pill.missing { background:#f1f3f6; color:#596579; }
        .empty { padding:36px 16px; color:var(--report-muted); text-align:center; }
        .table-footer { padding:12px 18px; border-top:1px solid #edf0f5; color:#738197; font-size:.72rem; }
        .report-aside { display:grid; gap:14px; }
        .insight-card { overflow:hidden; }
        .activity-list { display:grid; gap:13px; padding:16px 18px 18px; }
        .activity-item { display:grid; gap:6px; }
        .activity-label { display:flex; justify-content:space-between; gap:10px; color:#405069; font-size:.73rem; }
        .activity-label strong { color:var(--report-navy); font-weight:700; }
        .activity-track { height:7px; overflow:hidden; border-radius:99px; background:#e9edf3; }
        .activity-fill { height:100%; min-width:0; border-radius:inherit; background:linear-gradient(90deg,#0f766e,#159b8d); }
        .activity-empty { padding:2px 0; color:var(--report-muted); font-size:.75rem; line-height:1.5; }
        .scope-list { display:grid; gap:13px; padding:16px 18px; }
        .scope-item { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; font-size:.74rem; }
        .scope-label { color:#738197; }
        .scope-value { color:var(--report-navy); font-weight:700; text-align:right; }
        .notice { margin:0; padding:15px 16px; border:1px solid #f1dfaa; border-radius:12px; background:#fffbef; color:#73551a; font-size:.75rem; line-height:1.6; }
        .notice-title { display:flex; align-items:center; gap:8px; margin-bottom:7px; color:#684b10; font-size:.78rem; font-weight:750; }
        .notice p { margin:0; }
        @media(max-width:1150px) { .report-layout { grid-template-columns:minmax(0,1fr) 250px; } .main-content { padding:26px 22px 40px; } }
        @media(max-width:980px) { .report-layout { grid-template-columns:1fr; } .report-aside { grid-template-columns:repeat(2,minmax(0,1fr)); } .notice { grid-column:1/-1; } }
        @media(max-width:760px) { .main-content { padding:74px 16px 30px; } .stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .page-head { align-items:center; } }
        @media(max-width:560px) { .page-head { flex-direction:column; align-items:stretch; } .page-head .button { align-self:flex-start; } .stats { gap:9px; } .stat { min-height:98px; padding:14px; } .stat-value { font-size:1.45rem; } .filters { align-items:stretch; } .field { min-width:100%; } .filters .button { width:100%; } .report-aside { grid-template-columns:1fr; } .notice { grid-column:auto; } .card-heading { padding:14px; } }
        @media(prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto!important; transition:none!important; } }
    </style>
    @include('frontend.admin.partials.minimal-ui-overrides')
</head>
<body>
    @include('frontend.admin.partials.sidebar')

    <main class="main-content">
      <div class="report-page">
        <header class="page-head">
            <div>
                <h1>Faculty Reports</h1>
                <p class="subhead">{{ $weekStart->format('M j') }}–{{ $weekEnd->format('M j, Y') }} · Instructor schedules and attendance records</p>
            </div>
            <a class="button" href="{{ route('admin.instructor-attendance.export', request()->query()) }}">
                <i class="fas fa-download" aria-hidden="true"></i> Download CSV
            </a>
        </header>

        <form class="filters" method="GET" action="{{ route('admin.instructor-attendance') }}" aria-label="Filter faculty reports">
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
            <button class="button" type="submit"><i class="fas fa-sliders" aria-hidden="true"></i> Apply filters</button>
        </form>

        <section class="stats" aria-label="Weekly summary">
            <article class="stat"><div class="stat-label">Past scheduled classes</div><div class="stat-value">{{ number_format($stats['scheduled']) }}</div><div class="stat-note">Cancelled classes excluded</div></article>
            <article class="stat"><div class="stat-label">Classes with a session log</div><div class="stat-value">{{ number_format($stats['recordedClasses']) }}</div><div class="stat-note">At least one session record</div></article>
            <article class="stat"><div class="stat-label">Session records</div><div class="stat-value">{{ number_format($stats['sessions']) }}</div><div class="stat-note">Within the selected week</div></article>
            <article class="stat"><div class="stat-label">Cancelled classes</div><div class="stat-value">{{ number_format($stats['cancelled']) }}</div><div class="stat-note">Shown separately from class logs</div></article>
        </section>

        <div class="report-layout">
            <section class="table-card" aria-labelledby="class-records-heading">
                <div class="card-heading">
                    <div>
                        <h2 id="class-records-heading">Class and attendance records</h2>
                        <p>Scheduled classes and recorded sessions for the selected week</p>
                    </div>
                    <span class="row-count">{{ number_format($rows->count()) }} {{ $rows->count() === 1 ? 'record' : 'records' }}</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date / Time</th><th>Instructor</th><th>Course</th><th>Room</th><th>Session Log</th><th>Latest Session Details</th>
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
                                            <span class="pill">{{ $row['sessions']->count() }} {{ $row['sessions']->count() === 1 ? 'session' : 'sessions' }} logged</span>
                                        @else
                                            <span class="pill missing">No session logged</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($latestSession)
                                            <div class="primary">{{ ucfirst($latestSession->status) }}</div>
                                            <div class="muted">Started {{ $latestSession->started_at ? \Illuminate\Support\Carbon::parse($latestSession->started_at)->format('g:i A') : '—' }} · Ended {{ $latestSession->ended_at ? \Illuminate\Support\Carbon::parse($latestSession->ended_at)->format('g:i A') : '—' }}</div>
                                            <div class="muted">{{ $row['checkins'] }} student check-in(s)</div>
                                        @else
                                            <span class="muted">No session details</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="empty" colspan="6">No classes with a passed scheduled end time or completed session records found for this week and filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">Showing {{ number_format($rows->count()) }} report {{ $rows->count() === 1 ? 'record' : 'records' }} for the selected filters</div>
            </section>

            <aside class="report-aside" aria-label="Report insights">
                <section class="insight-card" aria-labelledby="activity-heading">
                    <div class="card-heading">
                        <div>
                            <h2 id="activity-heading">Weekly session activity</h2>
                            <p>Session records by class date</p>
                        </div>
                    </div>
                    <div class="activity-list">
                        @if ($stats['sessions'] > 0)
                            @foreach ($weekActivity as $day => $count)
                                <div class="activity-item">
                                    <div class="activity-label"><span>{{ $day }}</span><strong>{{ number_format($count) }}</strong></div>
                                    <div class="activity-track" role="img" aria-label="{{ $count }} session records on {{ $day }}">
                                        <div class="activity-fill" style="width:{{ $count > 0 ? max(4, ($count / $weekActivityMax) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="activity-empty">No session records were found for this week and filter.</p>
                        @endif
                    </div>
                </section>

                <section class="insight-card" aria-labelledby="scope-heading">
                    <div class="card-heading">
                        <div>
                            <h2 id="scope-heading">Report scope</h2>
                            <p>Current view selection</p>
                        </div>
                    </div>
                    <div class="scope-list">
                        <div class="scope-item"><span class="scope-label">Week</span><span class="scope-value">{{ $weekStart->format('M j') }}–{{ $weekEnd->format('M j') }}</span></div>
                        <div class="scope-item"><span class="scope-label">Instructor</span><span class="scope-value">{{ $selectedInstructor?->name ?? 'All instructors' }}</span></div>
                        <div class="scope-item"><span class="scope-label">Classes with logs</span><span class="scope-value">{{ number_format($stats['recordedClasses']) }} / {{ number_format($stats['scheduled']) }}</span></div>
                    </div>
                </section>

                <section class="notice" aria-labelledby="report-note-heading">
                    <h2 class="notice-title" id="report-note-heading"><i class="fas fa-circle-info" aria-hidden="true"></i>Interpreting these records</h2>
                    <p>Only classes whose scheduled end time has passed are listed. A session log does not confirm that the instructor taught the class. A missing log is not proof of absence, and student check-ins are shown separately.</p>
                </section>
            </aside>
        </div>
      </div>
    </main>
</body>
</html>
