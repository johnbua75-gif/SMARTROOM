<?php
$faculty_name     = $faculty_name ?? request()->user()?->name ?? 'Faculty';
$faculty_dept     = $faculty_dept ?? request()->user()?->department ?? 'Department';
$faculty_initials = $faculty_initials ?? strtoupper(substr((string) $faculty_name, 0, 1));
$semester         = $semester ?? "1st Semester 2025â€“2026";

$facultyName     = $facultyName     ?? $faculty_name;
$facultyDept     = $facultyDept     ?? $faculty_dept;
$facultyInitials = $facultyInitials ?? $faculty_initials;
$facultyEmail    = $facultyEmail    ?? request()->user()?->email ?? '';

$facultyCourses   = $facultyCourses ?? [];
$classrooms       = $classrooms ?? [];
$facultySchedules = $facultySchedules ?? [];

$dayMeta = [
  1 => ['day' => 'Monday',    'abbr' => 'Mon'],
  2 => ['day' => 'Tuesday',   'abbr' => 'Tue'],
  3 => ['day' => 'Wednesday', 'abbr' => 'Wed'],
  4 => ['day' => 'Thursday',  'abbr' => 'Thu'],
  5 => ['day' => 'Friday',    'abbr' => 'Fri'],
];

// Calculate current week date range
$now = now();
$monday = $now->copy()->startOfWeek();
$friday = $monday->copy()->addDays(4);
$weekRange = $monday->format('M d') . ' â€“ ' . $friday->format('M d, Y');

$palette = ['blue', 'violet', 'emerald', 'amber'];

$byDay = [];
foreach ($dayMeta as $isoDay => $meta) {
  $byDay[$isoDay] = [
    'day'           => $meta['day'],
    'abbr'          => $meta['abbr'],
    'total_minutes' => 0,
    'classes'       => [],
    'seen'          => [],
  ];
}

$calendarSchedule = [];

foreach ($facultySchedules as $entry) {
  $start = $entry->start_at;
  $end   = $entry->end_at;
  if (!$start) continue;
  $isoDay = (int) $start->dayOfWeekIso;
  if (!isset($byDay[$isoDay])) continue;

  $courseCode  = (string) ($entry->course?->code  ?? 'N/A');
  $courseTitle = (string) ($entry->course?->title ?? 'Untitled Subject');
  $roomName    = (string) ($entry->classroom?->name     ?? 'Room N/A');
  $building    = (string) ($entry->classroom?->building ?? '');
  $location    = trim($roomName . ($building !== '' ? ', ' . $building : ''));
  $color       = $palette[abs((int) crc32($courseCode)) % count($palette)];

  $durationText = $start->format('g:i A');
  if ($end) {
    $durationText .= ' â€“ ' . $end->format('g:i A');
  }

  $classData = [
    'id'           => (int)    ($entry->id           ?? 0),
    'classroom_id' => (int)    ($entry->classroom_id ?? 0),
    'code'         => $courseCode,
    'section'      => (string) ($entry->block_section ?? ''),
    'sort_at'      => $start->timestamp,
    'time'         => $start->format('g:i A'),
    'date'         => $start->format('M d, Y'),
    'start_at'     => $start->toIso8601String(),
    'end_at'       => $end?->toIso8601String(),
    'subject'      => $courseTitle,
    'duration'     => $durationText,
    'location'     => $location,
    'students'     => (int)    ($entry->enrolled ?? 0),
    'status'       => (string) ($entry->status   ?? 'scheduled'),
    'color'        => $color,
  ];

  $calendarSchedule[] = $classData;

  // Keep the weekly grid deduplicated so repeated semester occurrences don't stack visually.
  $weekKey = sprintf('%s|%s|%s|%s', $courseCode, $start->format('H:i'), $end?->format('H:i') ?? '', (int) ($entry->classroom_id ?? 0));
  if (!isset($byDay[$isoDay]['seen'][$weekKey])) {
    $byDay[$isoDay]['seen'][$weekKey] = true;
    if ($end) {
      $byDay[$isoDay]['total_minutes'] += max(0, $start->diffInMinutes($end));
    }
    $byDay[$isoDay]['classes'][] = $classData;
  }
}

$schedule = [];
foreach ($byDay as $dayData) {
  usort($dayData['classes'], fn($a, $b) => ($a['sort_at'] ?? 0) <=> ($b['sort_at'] ?? 0));
  $hours      = $dayData['total_minutes'] / 60;
  $hoursLabel = $hours > 0
    ? (fmod($hours, 1.0) === 0.0 ? (string)((int)$hours) : number_format($hours, 1)) . ' hrs'
    : '0 hrs';
  $schedule[] = [
    'day'       => $dayData['day'],
    'abbr'      => $dayData['abbr'],
    'total_hrs' => $hoursLabel,
    'classes'   => $dayData['classes'],
  ];
}

$remainingSessions = collect($facultySchedules)
  ->filter(fn($e) => in_array((string)($e->status ?? 'scheduled'), ['scheduled', 'ongoing'], true)
    && $e->end_at?->greaterThanOrEqualTo(now()))
  ->count();

$classesPerWeek = array_reduce(
  $schedule,
  fn(int $total, array $day): int => $total + count($day['classes']),
  0
);

// Calculate total students from deduplicated schedules (count per-subject only once)
$uniqueStudentCounts = [];
foreach ($schedule as $dayData) {
  foreach ($dayData['classes'] as $cls) {
    $courseKey = $cls['code'];
    if (!isset($uniqueStudentCounts[$courseKey])) {
      $uniqueStudentCounts[$courseKey] = $cls['students'];
    }
  }
}
$totalUniqueStudents = array_sum($uniqueStudentCounts);

$stats = [
  ['value' => (string) $classesPerWeek,                                    'label' => 'Classes / Week',      'icon' => 'ti-calendar-week',   'color' => 'blue',    'sub' => 'From your schedules'],
  ['value' => (string) $totalUniqueStudents,                               'label' => 'Total Students',      'icon' => 'ti-users',           'color' => 'violet',  'sub' => 'Current enrollment'],
  ['value' => (string) count($facultyCourses),                             'label' => 'Subjects',            'icon' => 'ti-books',           'color' => 'emerald', 'sub' => 'Assigned courses'],
  ['value' => (string) $remainingSessions,                                 'label' => 'Remaining Sessions',  'icon' => 'ti-clock-play',      'color' => 'amber',   'sub' => 'Upcoming classes'],
  ['value' => (string) (count($facultyCourses) * 3),                       'label' => 'Total Units',         'icon' => 'ti-bolt',            'color' => 'blue',    'sub' => 'Estimated load'],
];

$subjects = collect($facultyCourses)->map(function ($course, int $idx) use ($palette, $facultySchedules) {
  $courseSchedules = collect($facultySchedules)->filter(fn($s) => (int)($s->course_id ?? 0) === (int)$course->id);
  return [
    'code'     => (string) ($course->code  ?? 'N/A'),
    'name'     => (string) ($course->title ?? 'Untitled Subject'),
    'sections' => max(1, $courseSchedules->count()),
    'students' => (int) $courseSchedules->sum('enrolled'),
    'classes'  => $courseSchedules->count(),
    'units'    => 3,
    'room_assigned' => (int) ($course->classroom_id ?? 0) > 0 || $courseSchedules->isNotEmpty(),
    'color'    => $palette[$idx % count($palette)],
  ];
})->values()->all();

$exportSchedules = collect($facultySchedules)->map(fn($s) => [
  'id'       => (int) $s->id,
  'title'    => (string) ($s->course?->title  ?? 'Untitled Subject'),
  'code'     => (string) ($s->course?->code   ?? 'N/A'),
  'start_at' => optional($s->start_at)->toIso8601String(),
  'end_at'   => optional($s->end_at)->toIso8601String(),
  'location' => trim((string)($s->classroom?->name ?? 'Room N/A') . ' ' . (string)($s->classroom?->building ?? '')),
])->values()->all();

$classroomOptions = collect($classrooms)->map(fn($c) => [
  'id'       => (int)    $c->id,
  'name'     => (string) $c->name,
  'building' => (string) $c->building,
  'capacity' => (int)    ($c->capacity ?? 0),
])->values()->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartDoor â€” Semester Schedule</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sw:260px;
  --sidebar-w: 230px;

  /* Sidebar */
  --navy:#0b1640;
  --navy-mid:#1a2f80;
  --navy-light:#e8ecfb;
  --yellow:#f5c518;
  --yellow-light:#fef9e7;

  /* Content surface */
  --bg:#f8f9fb;
  --surface:#ffffff;
  --surface-2:#f5f7fa;
  --border:#e8eaef;
  --border-2:#d5dae5;

  /* Text */
  --tx:#111827;
  --tx-2:#4b5563;
  --tx-3:#9ca3af;
  --tx-4:#b0bdd0;

  /* Accents */
  --blue:#2563eb;--blue-lt:#eff4ff;--blue-bdr:#bfd1fd;--blue-dk:#1d4ed8;
  --violet:#7c3aed;--violet-lt:#f5f0ff;--violet-bdr:#ddd6fe;
  --emerald:#059669;--emerald-lt:#ecfdf5;--emerald-bdr:#a7f3d0;
  --amber:#d97706;--amber-lt:#fffbeb;--amber-bdr:#fcd38a;
  --red:#dc2626;--red-lt:#fef2f2;--red-bdr:#fecaca;

  --ff:'Instrument Sans',sans-serif;
  --ff-head:'Sora',sans-serif;
  --ff-mono:'JetBrains Mono',monospace;
  --r:10px;--r2:8px;--r3:6px;
  --shadow-sm:0 1px 2px rgba(0,0,0,.04);
  --shadow:0 4px 12px rgba(0,0,0,.06);
  --shadow-lg:0 12px 32px rgba(0,0,0,.1);
}

html{scroll-behavior:smooth}
body{font-family:var(--ff);background:var(--bg);color:var(--tx);display:flex;min-height:100vh;-webkit-font-smoothing:antialiased;font-size:14px}
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:var(--border-2);border-radius:99px}
::-webkit-scrollbar-track{background:transparent}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SIDEBAR
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.sidebar {
  position: fixed; left: 0; top: 0;
  width: var(--sidebar-w); height: 100vh;
  background: var(--navy);
  display: flex; flex-direction: column;
  overflow: hidden; z-index: 100;
}
.sidebar::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(160deg, rgba(245,197,24,0.06) 0%, transparent 55%);
  pointer-events: none;
}
.sidebar::after {
  content: ''; position: absolute;
  bottom: -60px; right: -60px;
  width: 180px; height: 180px; border-radius: 50%;
  border: 1px solid rgba(245,197,24,0.08); pointer-events: none;
}
.sidebar-logo {
  display: flex; align-items: center; gap: 12px;
  padding: 28px 20px 24px 24px; text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 8px;
}
.logo-mark {
  width: 40px; height: 40px; background: var(--yellow); border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: var(--navy); flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(245,197,24,0.4);
}
.logo-text { line-height: 1; }
.logo-text .brand-psu {
  font-size: 0.6rem; font-weight: 600; letter-spacing: 0.18em;
  color: rgba(255,255,255,0.45); text-transform: uppercase;
  display: block; margin-bottom: 3px;
}
.logo-text .brand-main { font-size: 1.05rem; font-weight: 700; color: #fff; letter-spacing: -0.01em; }
.logo-text .brand-main span { color: var(--yellow); }
.nav-section-label {
  font-size: 0.68rem; font-weight: 700; letter-spacing: 0.12em;
  text-transform: uppercase; color: rgba(255,255,255,0.25); padding: 16px 24px 6px;
}
.sidebar-nav { list-style: none; overflow-y: auto; padding: 0 12px; }
.sidebar-nav::-webkit-scrollbar { width: 0; }
.sidebar-nav li { margin-bottom: 2px; }
.sidebar-nav a {
  display: flex; align-items: center; gap: 11px; padding: 11px 12px;
  text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.88rem; font-weight: 500;
  border-radius: var(--r2);
  transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
  position: relative; overflow: hidden;
}
.sidebar-nav a .nav-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.85rem; background: rgba(255,255,255,0.05); flex-shrink: 0; transition: all 0.22s;
}
.sidebar-nav a:hover { color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.06); }
.sidebar-nav a:hover .nav-icon { background: rgba(255,255,255,0.1); }
.sidebar-nav a.active { background: rgba(245,197,24,0.14); color: var(--yellow); }
.sidebar-nav a.active .nav-icon { background: rgba(245,197,24,0.2); color: var(--yellow); }
.sidebar-nav a.active::before {
  content: ''; position: absolute; left: 0; top: 20%; bottom: 20%;
  width: 3px; background: var(--yellow); border-radius: 0 2px 2px 0;
}
.sidebar-footer {
  margin-top: auto; padding: 16px 12px 24px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.user-widget {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px;
  border-radius: var(--r2); background: rgba(255,255,255,0.05); margin-bottom: 8px;
}
.user-avatar {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  background: var(--navy-mid); border: 2px solid rgba(245,197,24,0.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem; font-weight: 700; color: var(--yellow);
}
.user-widget-info { flex: 1; min-width: 0; }
.user-widget-name {
  font-size: 0.83rem; font-weight: 600; color: #fff;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-widget-role { font-size: 0.73rem; color: rgba(255,255,255,0.4); }
.sidebar-logout-btn {
  display: flex; align-items: center; gap: 10px; padding: 9px 12px;
  color: rgba(255,255,255,0.4); font-size: 0.84rem; font-weight: 500;
  border-radius: var(--r2); transition: all 0.22s; width: 100%;
  background: none; border: none; cursor: pointer; font-family: inherit;
}
.sidebar-logout-btn:hover { color: #f87171; background: rgba(244,63,94,0.08); }

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   MAIN
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* Topbar */
.topbar{
  background:var(--surface);border-bottom:1px solid var(--border);
  padding:0 30px;height:56px;
  display:flex;align-items:center;justify-content:space-between;gap:16px;
  position:sticky;top:0;z-index:100;
  }
.tb-right{display:flex;align-items:center;gap:10px}
.tb-icon-btn{
  width:36px;height:36px;border-radius:8px;
  background:var(--surface-2);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  color:var(--tx-3);cursor:pointer;transition:all .15s;position:relative;
}
.tb-icon-btn:hover{background:#eef1fb;border-color:var(--blue-bdr);color:var(--blue)}
.tb-profile{
  display:flex;align-items:center;gap:9px;padding:5px 10px 5px 5px;
  border-radius:var(--r2);cursor:pointer;border:1px solid var(--border);
  background:var(--surface-2);transition:all .15s;position:relative;
}
.tb-profile:hover{border-color:var(--blue-bdr);background:#f0f4ff}
.tb-prof-avatar{
  width:30px;height:30px;border-radius:50%;
  background:#e0e7ff;color:var(--blue);
  display:flex;align-items:center;justify-content:center;
  font-size:.78rem;font-weight:700;flex-shrink:0;
}
.tb-prof-name{font-size:.78rem;font-weight:600;color:var(--tx)}
.tb-prof-role{font-size:.66rem;color:var(--tx-3)}

/* Profile dropdown */
.prof-dd{
  position:absolute;top:calc(100% + 8px);right:0;min-width:220px;
  background:var(--surface);border:1px solid var(--border);border-radius:10px;
  box-shadow:var(--shadow-lg);padding:10px;display:none;z-index:500;
}
.prof-dd.open{display:block}
.prof-dd-row{display:flex;flex-direction:column;gap:1px;margin-bottom:8px;padding:6px 8px;border-radius:6px;background:var(--surface-2)}
.prof-dd-lbl{font-size:.63rem;text-transform:uppercase;letter-spacing:.1em;color:var(--tx-4);font-weight:700}
.prof-dd-val{font-size:.78rem;color:var(--tx-2);margin-top:2px}
.prof-signout{
  width:100%;border:1px solid var(--red-bdr);border-radius:8px;padding:7px;
  font-size:.78rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;
  background:var(--red-lt);color:var(--red);cursor:pointer;transition:all .15s;font-family:var(--ff);
}
.prof-signout:hover{background:#fee2e2}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   CONTENT
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.content{padding:26px 30px 60px;display:flex;flex-direction:column;gap:20px}

/* Page header */
.page-header{
  background:linear-gradient(90deg,rgba(7,14,43,.94) 0%,rgba(11,22,64,.78) 46%,rgba(29,78,216,.3) 100%),url('/images/map.png') center/cover no-repeat;
  border-radius:0;padding:30px 30px;
  margin:-26px -30px 0;
  min-height:220px;
  display:flex;flex-direction:column;align-items:stretch;justify-content:flex-end;gap:24px;
  position:relative;overflow:hidden;
  border:0;
  animation:fadeUp .35s ease both;
}
.page-header::before{display:none}
.page-header::after{display:none}
.ph-left{display:flex;align-items:center;gap:16px;position:relative}
.ph-icon{
  width:46px;height:46px;border-radius:12px;
  background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);
  display:flex;align-items:center;justify-content:center;
  color:var(--yellow);font-size:1.1rem;flex-shrink:0;backdrop-filter:blur(4px);
}
.ph-title h1{font-family:var(--ff-head);font-size:1.2rem;font-weight:700;color:#fff;letter-spacing:-.03em}
.ph-title p{font-size:.75rem;color:rgba(255,255,255,.64);margin-top:5px;display:flex;align-items:center;gap:6px}
.ph-actions{display:flex;gap:8px;position:relative}
.btn{
  display:inline-flex;align-items:center;gap:6px;padding:9px 16px;
  border-radius:8px;border:none;cursor:pointer;font-family:var(--ff);
  font-size:.77rem;font-weight:600;transition:all .15s;white-space:nowrap;
}
.btn i{font-size:14px}
.btn:hover{opacity:.9}
.btn-ghost-white{background:var(--surface-2);border:1px solid var(--border);color:var(--tx-2)}
.btn-ghost-white:hover{background:var(--border);color:var(--tx)}
.btn-gold{background:var(--navy);color:#fff;font-weight:700}
.btn-gold:hover{background:var(--navy-mid)}
.btn-primary{background:var(--blue);color:#fff}
.btn-primary:hover{background:var(--blue-dk)}
.btn-outline{background:var(--surface);border:1px solid var(--border);color:var(--tx-2)}
.btn-outline:hover{background:var(--surface-2);border-color:var(--border-2)}

/* Flash */
.flash{padding:10px 14px;border-radius:8px;font-size:.82rem;font-weight:600}
.flash-ok{background:var(--emerald-lt);border:1px solid var(--emerald-bdr);color:#065f46}
.flash-err{background:var(--red-lt);border:1px solid var(--red-bdr);color:#991b1b}

/* Stats grid */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;animation:fadeUp .35s ease .06s both}
.page-header .stats-grid{width:100%;animation:none}
.stat-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);padding:16px;position:relative;overflow:hidden;
  transition:border-color .18s;
}
.stat-card:hover{border-color:var(--border-2)}
.stat-card::after{display:none}
.stat-card.blue{--accent-a:var(--blue)}
.stat-card.violet{--accent-a:var(--violet)}
.stat-card.emerald{--accent-a:var(--emerald)}
.stat-card.amber{--accent-a:var(--amber)}
.stat-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px}
.stat-ic{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.stat-card.blue .stat-ic{background:var(--blue-lt);color:var(--blue)}
.stat-card.violet .stat-ic{background:var(--violet-lt);color:var(--violet)}
.stat-card.emerald .stat-ic{background:var(--emerald-lt);color:var(--emerald)}
.stat-card.amber .stat-ic{background:var(--amber-lt);color:var(--amber)}
.stat-val{font-family:var(--ff-head);font-size:1.7rem;font-weight:700;letter-spacing:-.04em;line-height:1;color:var(--tx)}
.stat-label{font-size:.72rem;font-weight:600;color:var(--tx-3);letter-spacing:.01em;margin-bottom:2px}
.stat-sub{font-size:.65rem;color:var(--tx-4)}
.stat-accent-bar{position:absolute;bottom:0;left:0;right:0;height:2px}
.stat-card.blue .stat-accent-bar{background:linear-gradient(90deg,var(--blue),#60a5fa)}
.stat-card.violet .stat-accent-bar{background:linear-gradient(90deg,var(--violet),#c084fc)}
.stat-card.emerald .stat-accent-bar{background:linear-gradient(90deg,var(--emerald),#34d399)}
.stat-card.amber .stat-accent-bar{background:linear-gradient(90deg,var(--amber),#fbbf24)}

/* Toolbar */
.toolbar{
  display:flex;align-items:center;gap:10px;
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);padding:10px 14px;
  animation:fadeUp .35s ease .1s both;
}
.search-box{
  display:flex;align-items:center;gap:7px;background:var(--surface-2);
  border:1px solid var(--border);border-radius:var(--r2);
  padding:7px 14px;flex:0 0 240px;transition:all .15s;
}
.search-box:focus-within{border-color:var(--blue-bdr);box-shadow:0 0 0 3px rgba(37,99,235,.08)}
.search-box i{color:var(--tx-4);font-size:13px}
.search-box input{border:none;outline:none;background:transparent;font-size:.79rem;font-family:var(--ff);color:var(--tx);width:100%}
.search-box input::placeholder{color:var(--tx-4)}
.spacer{flex:1}
.sem-badge{
  display:flex;align-items:center;gap:6px;
  background:var(--blue-lt);border:1px solid var(--blue-bdr);
  border-radius:var(--r3);padding:6px 12px;
  font-size:.71rem;font-weight:600;color:var(--blue-dk);
}
.view-switcher{display:flex;gap:3px;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:3px}
.vs-btn{
  display:flex;align-items:center;gap:5px;padding:5px 11px;
  border:none;border-radius:6px;background:none;cursor:pointer;
  font-family:var(--ff);font-size:.72rem;font-weight:600;color:var(--tx-3);transition:all .15s;
}
.vs-btn.active{background:var(--surface);color:var(--blue);box-shadow:0 1px 3px rgba(10,17,40,.08)}
.vs-btn i{font-size:13px}
.tb-action-btn{
  display:flex;align-items:center;gap:6px;padding:7px 12px;
  border-radius:8px;border:1px solid var(--border);background:var(--surface);
  font-family:var(--ff);font-size:.75rem;font-weight:600;color:var(--tx-2);cursor:pointer;transition:all .15s;
}
.tb-action-btn:hover{border-color:var(--blue-bdr);color:var(--blue);background:var(--blue-lt)}
.tb-action-btn i{font-size:13px}
.tb-add-btn{
  display:flex;align-items:center;gap:6px;padding:7px 14px;
  border-radius:8px;background:var(--navy);
  font-family:var(--ff);font-size:.75rem;font-weight:600;color:#fff;cursor:pointer;border:none;transition:all .15s;
}
.tb-add-btn:hover{background:var(--navy-mid)}
.tb-add-btn i{font-size:13px}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   CALENDAR VIEW
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.calendar-section{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);overflow:hidden;
  animation:fadeUp .35s ease .14s both;
}
.cal-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px;border-bottom:1px solid var(--border);
  background:var(--surface);
}
.cal-header-left{display:flex;align-items:center;gap:10px}
.cal-header-icon{width:32px;height:32px;border-radius:8px;background:var(--blue-lt);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:14px}
.cal-header h2{font-family:var(--ff-head);font-size:.92rem;font-weight:700;color:var(--tx);letter-spacing:-.01em}
.cal-sub{font-size:.7rem;color:var(--tx-3);margin-top:1px}
.cal-month-nav{display:flex;align-items:center;gap:8px}
.cal-nav-btn{width:30px;height:30px;border:1px solid var(--border);border-radius:7px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--tx-3);transition:all .15s;font-size:13px}
.cal-nav-btn:hover{border-color:var(--blue-bdr);color:var(--blue);background:var(--blue-lt)}
.cal-month-label{font-size:.82rem;font-weight:700;color:var(--tx);min-width:120px;text-align:center}

.cal-grid{padding:16px 20px 20px}
.cal-weekdays{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:4px}
.cal-weekday{text-align:center;font-size:.62rem;font-weight:700;color:var(--tx-4);letter-spacing:.08em;text-transform:uppercase;padding:4px 0}
.cal-days{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.cal-day{
  aspect-ratio:1/1;border-radius:8px;display:flex;flex-direction:column;
  align-items:center;justify-content:center;cursor:default;
  transition:all .15s;position:relative;gap:3px;
  padding:4px;min-height:52px;
}
.cal-day.has-classes{
  background:var(--blue-lt);border:1px solid var(--blue-bdr);cursor:pointer;
}
.cal-day.has-classes:hover{background:#dde9ff;border-color:var(--blue)}
.cal-day.empty{opacity:0}
.cal-day.today .cal-day-num{background:var(--blue);color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center}
.cal-day-num{font-size:.78rem;font-weight:600;color:var(--tx-2);line-height:1}
.cal-day.has-classes .cal-day-num{color:var(--blue-dk);font-weight:700}
.cal-day-dots{display:flex;gap:2px;flex-wrap:wrap;justify-content:center;max-width:28px}
.cal-day-dot{width:5px;height:5px;border-radius:50%}
.cal-day-dot.blue{background:var(--blue)}
.cal-day-dot.violet{background:var(--violet)}
.cal-day-dot.emerald{background:var(--emerald)}
.cal-day-dot.amber{background:var(--amber)}
.cal-day-count{font-size:.56rem;font-weight:700;color:var(--blue-dk)}

/* Week View Header */
.week-view-header{
  display:flex;align-items:center;justify-content:space-between;
  background:var(--surface);
  border:1px solid var(--border);border-radius:var(--r);
  padding:20px 24px;margin-bottom:16px;
  box-shadow:var(--shadow-sm);animation:fadeUp .35s ease .12s both;
}
.wvh-left h3{margin:0;font-size:1.15rem;font-weight:700;color:var(--tx)}
.wvh-left p{margin:4px 0 0 0;font-size:.85rem;color:var(--tx-3)}
.wvh-right{display:flex;align-items:center;gap:16px;font-size:.85rem;color:var(--tx-3)}
.wvh-right strong{color:var(--blue);font-weight:700}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SCHEDULE GRID
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.sched-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;align-items:start}
.day-col{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);overflow:hidden;
  transition:border-color .18s;
  animation:fadeUp .4s ease var(--d,.15s) both;
}
.day-col:hover{border-color:var(--border-2)}
.day-col-header{
  display:flex;align-items:center;gap:8px;padding:10px 12px;
  border-bottom:1px solid var(--border);
  background:var(--surface-2);
}
.day-abbr{
  width:28px;height:28px;border-radius:6px;
  background:var(--navy);color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-family:var(--ff-head);font-size:.58rem;font-weight:700;letter-spacing:.04em;flex-shrink:0;
}
.day-col.today-col .day-abbr{background:var(--blue)}
.day-name{font-family:var(--ff-head);font-size:.82rem;font-weight:700;color:var(--tx)}
.day-hrs{
  margin-left:auto;display:flex;align-items:center;gap:3px;
  background:transparent;border:none;
  padding:0;
  font-size:.6rem;font-weight:600;color:var(--tx-4);
}
.day-hrs i{font-size:11px}
.day-classes{display:flex;flex-direction:column;gap:0;padding:0}
.no-class{padding:28px 14px;text-align:center;font-size:.72rem;color:var(--tx-4);display:flex;flex-direction:column;align-items:center;gap:6px}
.no-class i{font-size:18px;opacity:.3}

/* Class card */
.cls-card{
  border-bottom:1px solid var(--border);padding:14px 14px 14px 16px;
  cursor:pointer;transition:all .15s;position:relative;
  background:var(--surface);
}
.cls-card:last-child{border-bottom:none}
.cls-card:hover{background:var(--card-lt)}
.cls-card::before{
  content:'';position:absolute;left:0;top:6px;bottom:6px;width:3px;
  background:var(--card-c);border-radius:3px;
}
.cls-card.blue{--card-c:var(--blue);--card-lt:var(--blue-lt);--card-bdr:var(--blue-bdr);--card-txt:var(--blue-dk)}
.cls-card.violet{--card-c:var(--violet);--card-lt:var(--violet-lt);--card-bdr:var(--violet-bdr);--card-txt:var(--violet)}
.cls-card.emerald{--card-c:var(--emerald);--card-lt:var(--emerald-lt);--card-bdr:var(--emerald-bdr);--card-txt:var(--emerald)}
.cls-card.amber{--card-c:var(--amber);--card-lt:var(--amber-lt);--card-bdr:var(--amber-bdr);--card-txt:var(--amber)}
.cls-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.cls-code{
  font-family:var(--ff-mono);font-size:.6rem;font-weight:700;
  padding:3px 8px;border-radius:4px;
  background:var(--card-lt);color:var(--card-txt);border:1px solid var(--card-bdr);
}
.cls-time{font-family:var(--ff-mono);font-size:.62rem;color:var(--tx-3);font-weight:500}
.cls-name{font-size:.8rem;font-weight:600;color:var(--tx);margin-bottom:8px;line-height:1.4}
.cls-meta{display:flex;align-items:center;justify-content:space-between}
.cls-room{display:flex;align-items:center;gap:4px;font-size:.66rem;color:var(--tx-3)}
.cls-room i{font-size:11px}
.cls-students{display:flex;align-items:center;gap:4px;font-size:.66rem;color:var(--tx-3)}
.cls-students span{font-family:var(--ff-mono);font-weight:600;color:var(--card-txt)}
.status-badge{
  display:inline-flex;align-items:center;gap:3px;padding:2px 6px;
  border-radius:var(--r3);font-size:.58rem;font-weight:600;
}
.status-cancelled{background:var(--red-lt);border:1px solid var(--red-bdr);color:var(--red)}
.status-completed{background:var(--emerald-lt);border:1px solid var(--emerald-bdr);color:var(--emerald)}
.status-ongoing{background:var(--blue-lt);border:1px solid var(--blue-bdr);color:var(--blue-dk)}
.block-badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:4px;background:var(--surface-2);border:1px solid var(--border);color:var(--tx-2);font-size:.58rem;font-weight:600}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   TIMELINE VIEW
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.timeline-section{display:none;flex-direction:column;gap:14px}
.tl-day-block{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow-sm)}
.tl-day-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.tl-list{display:flex;flex-direction:column}
.tl-item{
  display:flex;align-items:center;gap:12px;padding:11px 16px;
  border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;
  position:relative;
}
.tl-item:last-child{border-bottom:none}
.tl-item:hover{background:var(--surface-2)}
.tl-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--card-c)}
.tl-item.blue{--card-c:var(--blue);--card-lt:var(--blue-lt);--card-bdr:var(--blue-bdr);--card-txt:var(--blue-dk)}
.tl-item.violet{--card-c:var(--violet);--card-lt:var(--violet-lt);--card-bdr:var(--violet-bdr);--card-txt:var(--violet)}
.tl-item.emerald{--card-c:var(--emerald);--card-lt:var(--emerald-lt);--card-bdr:var(--emerald-bdr);--card-txt:var(--emerald)}
.tl-item.amber{--card-c:var(--amber);--card-lt:var(--amber-lt);--card-bdr:var(--amber-bdr);--card-txt:var(--amber)}
.tl-time-col{min-width:105px;display:flex;flex-direction:column;gap:2px}
.tl-time-start{font-family:var(--ff-mono);font-size:.72rem;font-weight:600;color:var(--tx-2)}
.tl-time-end{font-family:var(--ff-mono);font-size:.65rem;color:var(--tx-4)}
.tl-main{flex:1;display:flex;flex-direction:column;gap:3px}
.tl-subj{font-size:.82rem;font-weight:600;color:var(--tx)}
.tl-sub{font-size:.68rem;color:var(--tx-3);display:flex;align-items:center;gap:6px}
.tl-code-tag{
  font-family:var(--ff-mono);font-size:.6rem;font-weight:600;
  padding:2px 6px;border-radius:var(--r3);
  background:var(--card-lt);color:var(--card-txt);border:1px solid var(--card-bdr);
}
.tl-no-class{padding:16px;font-size:.75rem;color:var(--tx-4)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SUBJECT OVERVIEW
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.subj-section{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);padding:20px;box-shadow:var(--shadow-sm);
  animation:fadeUp .4s ease .4s both;
}
.section-header{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.section-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px}
.section-icon.amber{background:var(--amber-lt);color:var(--amber)}
.section-icon.blue{background:var(--blue-lt);color:var(--blue)}
.section-h2{font-family:var(--ff-head);font-size:.9rem;font-weight:700;color:var(--tx);letter-spacing:-.01em}
.subj-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.subj-card{
  border:1px solid var(--border);border-radius:var(--r2);
  padding:14px;background:var(--surface-2);
  transition:transform .18s,border-color .18s,box-shadow .18s;
  position:relative;overflow:hidden;
}
.subj-card:hover{border-color:var(--card-bdr)}
.subj-card.blue{--card-lt:var(--blue-lt);--card-bdr:var(--blue-bdr);--card-txt:var(--blue-dk);--card-c:var(--blue)}
.subj-card.violet{--card-lt:var(--violet-lt);--card-bdr:var(--violet-bdr);--card-txt:var(--violet);--card-c:var(--violet)}
.subj-card.emerald{--card-lt:var(--emerald-lt);--card-bdr:var(--emerald-bdr);--card-txt:var(--emerald);--card-c:var(--emerald)}
.subj-card.amber{--card-lt:var(--amber-lt);--card-bdr:var(--amber-bdr);--card-txt:var(--amber);--card-c:var(--amber)}
.subj-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--card-c)}
.sc-code-tag{display:inline-block;font-family:var(--ff-mono);font-size:.62rem;font-weight:600;padding:2px 8px;border-radius:var(--r3);margin-bottom:8px;background:var(--card-lt);color:var(--card-txt);border:1px solid var(--card-bdr)}
.sc-name{font-size:.79rem;font-weight:600;color:var(--tx);margin-bottom:10px;line-height:1.35;min-height:36px}
.sc-divider{height:1px;background:var(--border);margin-bottom:9px}
.sc-rows{display:flex;flex-direction:column;gap:4px}
.sc-row{display:flex;justify-content:space-between;font-size:.68rem}
.sc-lbl{color:var(--tx-3)}
.sc-val{font-weight:700;color:var(--tx-2);font-family:var(--ff-mono)}
.sc-status{margin-top:9px;padding:6px 8px;border-radius:6px;background:var(--amber-lt);border:1px solid var(--amber-bdr);color:var(--amber);font-size:.66rem;font-weight:600;line-height:1.3}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   ADD SCHEDULE CARD
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.add-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;box-shadow:var(--shadow-sm)}
.add-card-title{font-size:.85rem;font-weight:700;color:var(--tx);margin-bottom:2px}
.add-card-note{font-size:.72rem;color:var(--tx-3)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   MODALS â€” BASE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.modal-overlay{
  position:fixed;inset:0;z-index:2000;display:none;
  align-items:center;justify-content:center;
  background:rgba(0,0,0,.3);backdrop-filter:blur(2px);padding:20px;
}
.modal-overlay.open{display:flex}
.modal{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);box-shadow:var(--shadow-lg);
  max-height:calc(100vh - 40px);overflow:auto;
  animation:scaleIn .2s ease both;
}
@keyframes scaleIn{from{opacity:0;transform:scale(.96) translateY(8px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px;border-bottom:1px solid var(--border);
  position:sticky;top:0;background:var(--surface);z-index:1;
}
.modal-title{font-family:var(--ff-head);font-size:.95rem;font-weight:700;color:var(--tx);letter-spacing:-.01em}
.modal-sub{font-size:.72rem;color:var(--tx-3);margin-top:3px}
.modal-close{
  width:30px;height:30px;border-radius:7px;border:1px solid var(--border);
  background:var(--surface-2);display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:var(--tx-3);transition:all .15s;font-size:13px;
}
.modal-close:hover{background:var(--red-lt);border-color:var(--red-bdr);color:var(--red)}

/* Add Schedule Modal */
.add-modal{width:min(920px,100%)}
.add-modal .modal-body{padding:18px 20px;display:flex;flex-direction:column;gap:14px}
.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.fg{display:flex;flex-direction:column;gap:5px}
.fg.full{grid-column:1/-1}
.fg.half2{grid-column:span 2}
.flabel{font-size:.66rem;font-weight:700;color:var(--tx-2);letter-spacing:.06em;text-transform:uppercase}
.finput,.fselect{
  height:36px;border:1px solid var(--border);border-radius:7px;
  background:var(--surface-2);padding:0 10px;font-size:.8rem;
  color:var(--tx);font-family:var(--ff);outline:none;
  transition:border-color .15s,box-shadow .15s;width:100%;
}
.finput:focus,.fselect:focus{border-color:var(--blue-bdr);box-shadow:0 0 0 3px rgba(37,99,235,.08);background:var(--surface)}
.form-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;grid-column:1/-1}
.avail-panel{
  grid-column:1/-1;border:1px dashed var(--border-2);border-radius:10px;
  padding:14px;background:var(--surface-2);display:flex;flex-direction:column;gap:10px;
}
.avail-title{font-size:.67rem;font-weight:700;color:var(--tx-2);letter-spacing:.1em;text-transform:uppercase}
.avail-list{display:flex;flex-direction:column;gap:6px}
.avail-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 10px;border-radius:7px;background:var(--surface);border:1px solid var(--border)}
.avail-name{font-size:.77rem;font-weight:600;color:var(--tx)}
.avail-meta{font-size:.68rem;color:var(--tx-3);margin-top:1px}
.avail-status{font-size:.62rem;font-weight:600;padding:2px 7px;border-radius:var(--r3)}
.avail-status.is-available{background:var(--emerald-lt);border:1px solid var(--emerald-bdr);color:var(--emerald)}
.avail-status.is-occupied{background:var(--red-lt);border:1px solid var(--red-bdr);color:var(--red)}
.avail-status.is-reserved{background:var(--amber-lt);border:1px solid var(--amber-bdr);color:var(--amber)}
.suggest-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 10px;border-radius:7px;background:var(--surface);border:1px solid var(--border)}
.suggest-btn{
  border:1px solid var(--blue-bdr);background:var(--blue-lt);border-radius:6px;
  padding:4px 10px;font-size:.68rem;font-weight:700;color:var(--blue-dk);
  cursor:pointer;font-family:var(--ff);transition:all .15s;
}
.suggest-btn:hover{background:var(--blue);color:#fff}
.form-actions{display:flex;align-items:center;gap:10px;grid-column:1/-1;padding-top:4px;border-top:1px solid var(--border)}
.form-hint{font-size:.69rem;color:var(--tx-3);margin-left:4px}

/* Day Classes Modal */
.day-modal{width:min(580px,100%)}
.day-modal .modal-body{padding:16px 20px 20px}
.day-modal-date{font-size:.77rem;color:var(--tx-3);display:flex;align-items:center;gap:6px;margin-bottom:14px}
.day-modal-date i{font-size:13px}
.day-cls-list{display:flex;flex-direction:column;gap:8px}
.day-cls-item{
  border:1px solid var(--border);border-radius:10px;overflow:hidden;cursor:pointer;
  transition:all .15s;position:relative;
}
.day-cls-item:hover{border-color:var(--card-bdr)}
.day-cls-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--card-c)}
.day-cls-item.blue{--card-c:var(--blue);--card-lt:var(--blue-lt);--card-bdr:var(--blue-bdr);--card-txt:var(--blue-dk)}
.day-cls-item.violet{--card-c:var(--violet);--card-lt:var(--violet-lt);--card-bdr:var(--violet-bdr);--card-txt:var(--violet)}
.day-cls-item.emerald{--card-c:var(--emerald);--card-lt:var(--emerald-lt);--card-bdr:var(--emerald-bdr);--card-txt:var(--emerald)}
.day-cls-item.amber{--card-c:var(--amber);--card-lt:var(--amber-lt);--card-bdr:var(--amber-bdr);--card-txt:var(--amber)}
.day-cls-inner{padding:12px 14px}
.day-cls-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.day-cls-name{font-size:.83rem;font-weight:600;color:var(--tx);margin-bottom:6px}
.day-cls-info{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.day-cls-meta{display:flex;align-items:center;gap:4px;font-size:.68rem;color:var(--tx-3)}
.day-cls-meta i{font-size:11px}
.day-no-class{text-align:center;padding:30px;font-size:.8rem;color:var(--tx-4);display:flex;flex-direction:column;align-items:center;gap:8px}
.day-no-class i{font-size:28px}

/* Session Modal */
.session-modal{width:min(500px,100%)}
.session-modal .modal-body{padding:16px 20px 20px;display:flex;flex-direction:column;gap:12px}
.session-detail-header{display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border)}
.session-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.session-detail-header.blue .session-icon{background:var(--blue-lt);color:var(--blue)}
.session-detail-header.violet .session-icon{background:var(--violet-lt);color:var(--violet)}
.session-detail-header.emerald .session-icon{background:var(--emerald-lt);color:var(--emerald)}
.session-detail-header.amber .session-icon{background:var(--amber-lt);color:var(--amber)}
.session-header-txt .sess-name{font-size:.88rem;font-weight:700;color:var(--tx);line-height:1.3}
.session-header-txt .sess-code{display:inline-block;margin-top:5px;font-family:var(--ff-mono);font-size:.62rem;font-weight:600;padding:2px 7px;border-radius:var(--r3);background:var(--card-lt);color:var(--card-txt);border:1px solid var(--card-bdr)}
.session-detail-header.blue{--card-lt:var(--blue-lt);--card-bdr:var(--blue-bdr);--card-txt:var(--blue-dk)}
.session-detail-header.violet{--card-lt:var(--violet-lt);--card-bdr:var(--violet-bdr);--card-txt:var(--violet)}
.session-detail-header.emerald{--card-lt:var(--emerald-lt);--card-bdr:var(--emerald-bdr);--card-txt:var(--emerald)}
.session-detail-header.amber{--card-lt:var(--amber-lt);--card-bdr:var(--amber-bdr);--card-txt:var(--amber)}
.session-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.session-info-item{padding:10px 12px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border)}
.session-info-lbl{font-size:.62rem;font-weight:700;color:var(--tx-4);letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px}
.session-info-val{font-size:.78rem;font-weight:600;color:var(--tx-2);display:flex;align-items:center;gap:5px}
.session-info-val i{font-size:12px;color:var(--tx-4)}
.makeup-section{border:1px solid var(--border);border-radius:8px;padding:12px}
.makeup-title{font-size:.67rem;font-weight:700;color:var(--tx-3);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px}
.makeup-list{display:flex;flex-direction:column;gap:6px}
.makeup-skeleton{display:flex;flex-direction:column;gap:6px}.makeup-skeleton span{height:28px;border-radius:6px;background:linear-gradient(90deg,#f1f3f8 25%,#fff 50%,#f1f3f8 75%);background-size:200% 100%;animation:makeupSkeleton 1.35s ease-in-out infinite}@keyframes makeupSkeleton{0%{background-position:200% 0}100%{background-position:-200% 0}}@media(prefers-reduced-motion:reduce){.makeup-skeleton span{animation:none;background:#f1f3f8}}
.makeup-item{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:7px;background:var(--emerald-lt);border:1px solid var(--emerald-bdr)}
.makeup-item-lbl{font-size:.74rem;color:#065f46;font-weight:500}
.makeup-meta{font-size:.66rem;color:var(--emerald)}
.session-act{display:flex;align-items:center;gap:8px;justify-content:flex-end;padding-top:4px;border-top:1px solid var(--border)}
.btn-danger{background:var(--red-lt);border:1px solid var(--red-bdr);color:var(--red);border-radius:7px;padding:8px 14px;font-size:.77rem;font-weight:600;cursor:pointer;font-family:var(--ff);transition:all .15s}
.btn-danger:hover{background:#fee2e2}
.btn-danger:disabled{opacity:.5;cursor:not-allowed}
.btn-remind{background:var(--surface-2);border:1px solid var(--border);color:var(--tx-2);border-radius:7px;padding:8px 14px;font-size:.77rem;font-weight:600;cursor:pointer;font-family:var(--ff);transition:all .15s}
.btn-remind:hover{border-color:var(--blue-bdr);color:var(--blue)}

/* Responsive */
@media(max-width:1400px){.sched-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr)}.subj-grid{grid-template-columns:repeat(2,1fr)}.sched-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:960px){.sidebar{display:none}.main{margin-left:0}}
@media(max-width:680px){.sched-grid{grid-template-columns:1fr}.content{padding:16px 14px 40px}.page-header{margin:-16px -14px 0;padding:24px 20px;min-height:190px}.form-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.stats-grid{grid-template-columns:1fr 1fr}}

@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
</style>
@include('partials.pro-motion')
</head>
<body>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SIDEBAR
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="sidebar">
  <a href="<?= htmlspecialchars(url('/dashboard')) ?>" class="sidebar-logo">
    <div class="logo-mark"><i class="fas fa-door-open"></i></div>
    <div class="logo-text">
      <span class="brand-psu">PSU</span>
      <span class="brand-main">Smart<span>Door</span></span>
    </div>
  </a>

  <span class="nav-section-label">Main Menu</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ url('/faculty_dashboard') }}"
         class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
        Dashboard
      </a>
    </li>
    <li>
      <a href="{{ url('/rooms') }}"
         class="{{ Request::is('rooms*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-door-open"></i></span>
        Rooms
      </a>
    </li>
    <li>
      <a href="{{ url('/faculty-schedule') }}"
         class="{{ Request::is('faculty-schedule') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clock"></i></span>
        Schedule
      </a>
    </li>
    <li>
      <a href="{{ url('/attendance') }}" class="{{ Request::is('attendance*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>
        Attendance
      </a>
    </li>
  </ul>

  <span class="nav-section-label">Tools</span>
  <ul class="sidebar-nav">
    <li>
      <a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-id-card"></i></span>
        RFID Verification
      </a>
    </li>
    <li><a href="{{ route('faculty.notifications') }}" class="{{ Request::routeIs('faculty.notifications') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-bell"></i></span>Notifications</a></li>
    <li>
      <a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
        Reports
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-widget">
      <div class="user-avatar"><?= htmlspecialchars($facultyInitials) ?></div>
      <div class="user-widget-info">
        <div class="user-widget-name"><?= htmlspecialchars($facultyName) ?></div>
        <div class="user-widget-role"><?= htmlspecialchars($facultyDept) ?></div>
      </div>
    </div>
    <form method="POST" action="<?= htmlspecialchars(url('/logout')) ?>">
      <?= csrf_field(); ?>
      <button type="submit" class="sidebar-logout-btn">
        <i class="fas fa-arrow-right-from-bracket"></i>
        Sign Out
      </button>
    </form>
  </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â• MAIN â•â•â•â•â•â•â•â•â•â•â• -->
<div class="main">
  <!-- Topbar -->
  <div class="topbar">
    <div class="tb-right">
      <div class="tb-profile" id="profileToggle">
        <div class="tb-prof-avatar"><?= htmlspecialchars($facultyInitials) ?></div>
        <div>
          <div class="tb-prof-name"><?= htmlspecialchars($facultyName) ?></div>
          <div class="tb-prof-role"><?= htmlspecialchars($facultyDept) ?></div>
        </div>
        <i class="ti ti-chevron-down" style="font-size:12px;color:var(--tx-4)"></i>
        <div class="prof-dd" id="profileDropdown">
          <div class="prof-dd-row">
            <span class="prof-dd-lbl">Email</span>
            <span class="prof-dd-val"><?= htmlspecialchars($facultyEmail) ?></span>
          </div>
          <div class="prof-dd-row">
            <span class="prof-dd-lbl">Position</span>
            <span class="prof-dd-val"><?= htmlspecialchars($facultyDept) ?></span>
          </div>
          <form method="POST" action="<?= htmlspecialchars(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="prof-signout"><i class="ti ti-logout"></i> Sign Out</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    @if(session('status'))
      <div class="flash flash-ok">{{ session('status') }}</div>
    @endif
    @if($errors->any())
      <div class="flash flash-err">{{ $errors->first() }}</div>
    @endif

    <!-- Page Header -->
    <div class="page-header">
      <div class="ph-left">
        <div class="ph-icon"><i class="ti ti-calendar-week"></i></div>
        <div class="ph-title">
          <h1>Semester Schedule</h1>
          <p>
            <i class="ti ti-user" style="font-size:12px"></i>
            <?= htmlspecialchars($facultyName) ?> &nbsp;Â·&nbsp; <?= htmlspecialchars($semester) ?>
          </p>
        </div>
      </div>
      <div class="stats-grid">
      <?php foreach ($stats as $s): ?>
      <div class="stat-card <?= htmlspecialchars($s['color']) ?>">
        <div class="stat-top">
          <div class="stat-val"><?= htmlspecialchars($s['value']) ?></div>
          <div class="stat-ic"><i class="ti <?= htmlspecialchars($s['icon']) ?>"></i></div>
        </div>
        <div class="stat-label"><?= htmlspecialchars($s['label']) ?></div>
        <div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div>
        <div class="stat-accent-bar"></div>
      </div>
      <?php endforeach ?>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="search-box">
        <i class="ti ti-search"></i>
        <input type="text" id="classFilter" placeholder="Filter classesâ€¦">
      </div>
      <div class="spacer"></div>
      <div class="sem-badge"><i class="ti ti-calendar" style="font-size:12px"></i> <?= htmlspecialchars($semester) ?></div>
      <div class="view-switcher">
        <button class="vs-btn active" id="btnGrid" type="button"><i class="ti ti-layout-grid"></i> Grid</button>
        <button class="vs-btn" id="btnCal" type="button"><i class="ti ti-calendar-month"></i> Calendar</button>
        <button class="vs-btn" id="btnTl" type="button"><i class="ti ti-list-details"></i> Timeline</button>
      </div>
      <a class="tb-action-btn" href="{{ route('faculty.schedule.export.ics') }}">
        <i class="ti ti-file-export"></i> Export
      </a>
      <button class="tb-add-btn" id="openAddModal2" type="button">
        <i class="ti ti-plus"></i> Add Schedule
      </button>
    </div>

    <!-- â•â•â•â•â•â• CALENDAR VIEW â•â•â•â•â•â• -->
    <div class="calendar-section" id="calendarView" style="display:none">
      <div class="cal-header">
        <div class="cal-header-left">
          <div class="cal-header-icon"><i class="ti ti-calendar-month"></i></div>
          <div>
            <h2>Monthly Schedule</h2>
            <div class="cal-sub">Click a highlighted date to see all classes</div>
          </div>
        </div>
        <div class="cal-month-nav">
          <button class="cal-nav-btn" id="calPrev"><i class="ti ti-chevron-left"></i></button>
          <div class="cal-month-label" id="calMonthLabel"></div>
          <button class="cal-nav-btn" id="calNext"><i class="ti ti-chevron-right"></i></button>
        </div>
      </div>
      <div class="cal-grid">
        <div class="cal-weekdays">
          <div class="cal-weekday">Sun</div>
          <div class="cal-weekday">Mon</div>
          <div class="cal-weekday">Tue</div>
          <div class="cal-weekday">Wed</div>
          <div class="cal-weekday">Thu</div>
          <div class="cal-weekday">Fri</div>
          <div class="cal-weekday">Sat</div>
        </div>
        <div class="cal-days" id="calDays"></div>
      </div>
    </div>

    <!-- â•â•â•â•â•â• SCHEDULE GRID VIEW â•â•â•â•â•â• -->
    <div class="sched-grid" id="gridView">
      <?php foreach ($schedule as $di => $day):
        $delay = round(.14 + $di * .05, 2);
      ?>
      <div class="day-col" style="--d:<?= $delay ?>s">
        <div class="day-col-header">
          <div class="day-abbr"><?= htmlspecialchars($day['abbr']) ?></div>
          <div>
            <div class="day-name"><?= htmlspecialchars($day['day']) ?></div>
          </div>
          <div class="day-hrs"><i class="ti ti-clock"></i> <?= htmlspecialchars($day['total_hrs']) ?></div>
        </div>
        <div class="day-classes">
          <?php if (empty($day['classes'])): ?>
            <div class="no-class">
              <i class="ti ti-calendar-off"></i>
              No classes
            </div>
          <?php else: ?>
            <?php foreach ($day['classes'] as $cls): ?>
            <div class="cls-card <?= htmlspecialchars($cls['color']) ?>"
              data-id="<?= htmlspecialchars((string) $cls['id']) ?>"
              data-room-id="<?= htmlspecialchars((string) $cls['classroom_id']) ?>"
              data-subject="<?= htmlspecialchars($cls['subject']) ?>"
              data-code="<?= htmlspecialchars($cls['code']) ?>"
              data-block="<?= htmlspecialchars($cls['section']) ?>"
              data-location="<?= htmlspecialchars($cls['location']) ?>"
              data-date="<?= htmlspecialchars($cls['date']) ?>"
              data-time="<?= htmlspecialchars($cls['duration']) ?>"
              data-start-at="<?= htmlspecialchars((string) $cls['start_at']) ?>"
              data-end-at="<?= htmlspecialchars((string) $cls['end_at']) ?>"
              data-status="<?= htmlspecialchars($cls['status']) ?>"
              data-color="<?= htmlspecialchars($cls['color']) ?>">
              <div class="cls-top">
                <span class="cls-code"><?= htmlspecialchars($cls['code']) ?></span>
                <?php if ($cls['section'] !== ''): ?><span class="block-badge"><?= htmlspecialchars($cls['section']) ?></span><?php endif ?>
                <?php if (!empty($cls['status']) && $cls['status'] === 'cancelled'): ?>
                  <span class="status-badge status-cancelled"><i class="ti ti-x" style="font-size:9px"></i> Cancelled</span>
                <?php elseif (!empty($cls['status']) && $cls['status'] === 'ongoing'): ?>
                  <span class="status-badge status-ongoing">Live</span>
                <?php endif ?>
                <span class="cls-time"><?= htmlspecialchars($cls['time']) ?></span>
              </div>
              <div class="cls-name"><?= htmlspecialchars($cls['subject']) ?></div>
              <div class="cls-meta">
                <span class="cls-room"><i class="ti ti-map-pin"></i><?= htmlspecialchars($cls['location']) ?></span>
                <span class="cls-students"><i class="ti ti-users"></i><span><?= htmlspecialchars($cls['students']) ?></span></span>
              </div>
            </div>
            <?php endforeach ?>
          <?php endif ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>

    <!-- â•â•â•â•â•â• TIMELINE VIEW â•â•â•â•â•â• -->
    <div class="timeline-section" id="timelineView">
      <?php foreach ($schedule as $day): ?>
      <div class="tl-day-block">
        <div class="tl-day-hd">
          <div class="day-abbr"><?= htmlspecialchars($day['abbr']) ?></div>
          <div>
            <div class="day-name"><?= htmlspecialchars($day['day']) ?></div>
          </div>
          <div class="day-hrs" style="margin-left:auto"><i class="ti ti-clock"></i> <?= htmlspecialchars($day['total_hrs']) ?></div>
        </div>
        <div class="tl-list">
          <?php if (empty($day['classes'])): ?>
            <div class="tl-no-class">No sessions scheduled.</div>
          <?php else: ?>
            <?php foreach ($day['classes'] as $cls): ?>
            <div class="tl-item <?= htmlspecialchars($cls['color']) ?>"
              data-id="<?= htmlspecialchars((string)$cls['id']) ?>"
              data-room-id="<?= htmlspecialchars((string)$cls['classroom_id']) ?>"
              data-subject="<?= htmlspecialchars($cls['subject']) ?>"
              data-code="<?= htmlspecialchars($cls['code']) ?>"
              data-block="<?= htmlspecialchars($cls['section']) ?>"
              data-location="<?= htmlspecialchars($cls['location']) ?>"
              data-date="<?= htmlspecialchars($cls['date']) ?>"
              data-time="<?= htmlspecialchars($cls['duration']) ?>"
              data-start-at="<?= htmlspecialchars((string)$cls['start_at']) ?>"
              data-end-at="<?= htmlspecialchars((string)$cls['end_at']) ?>"
              data-status="<?= htmlspecialchars($cls['status']) ?>"
              data-color="<?= htmlspecialchars($cls['color']) ?>">
              <div class="tl-time-col">
                <div class="tl-time-start"><?= htmlspecialchars($cls['time']) ?></div>
              </div>
              <div class="tl-main">
                <div class="tl-subj"><?= htmlspecialchars($cls['subject']) ?></div>
                <div class="tl-sub">
                  <i class="ti ti-map-pin" style="font-size:10px"></i>
                  <?= htmlspecialchars($cls['location']) ?>
                  <span class="tl-code-tag"><?= htmlspecialchars($cls['code']) ?></span>
                  <?php if ($cls['section'] !== ''): ?><span class="block-badge"><?= htmlspecialchars($cls['section']) ?></span><?php endif ?>
                </div>
              </div>
              <div>
                <?php if (!empty($cls['status']) && $cls['status'] !== 'scheduled'): ?>
                  <span class="status-badge status-<?= htmlspecialchars($cls['status']) ?>"><?= htmlspecialchars($cls['status']) ?></span>
                <?php endif ?>
              </div>
            </div>
            <?php endforeach ?>
          <?php endif ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- â•â•â•â•â•â•â•â•â•â•â• ADD SCHEDULE MODAL â•â•â•â•â•â•â•â•â•â•â• -->
@php
  $bsitCatalog=[['code'=>'A_CC 101','title'=>'Introduction to Computing'],['code'=>'A_CC 102','title'=>'Fundamental of Programming'],['code'=>'A_CC 103','title'=>'Intermediate Programming'],['code'=>'A_CC 104','title'=>'Data Structures and Algorithms'],['code'=>'A_CC 105','title'=>'Information Management 1'],['code'=>'A_CC 106','title'=>'Application Development & Emerging Tech'],['code'=>'A_OOP 101','title'=>'Object Oriented Programming'],['code'=>'A_WD 101','title'=>'Web Development'],['code'=>'A_WS 101','title'=>'Web Systems and Technologies 1'],['code'=>'A_NET 101','title'=>'Networking 1'],['code'=>'A_NET 102','title'=>'Networking 2'],['code'=>'A_SAD 101','title'=>'System Analysis and Design'],['code'=>'A_HCI 101','title'=>'Human Computer Interaction 1'],['code'=>'A_HCI 102','title'=>'Human Computer Interaction 2'],['code'=>'A_MD 101','title'=>'Mobile Application Development 1'],['code'=>'A_IM 102','title'=>'Information Management 2'],['code'=>'A_CAP 101','title'=>'Capstone Project 1'],['code'=>'A_CAP 102','title'=>'Capstone Project 2'],['code'=>'A_IAS 101','title'=>'Information Assurance and Security 1'],['code'=>'A_IAS 102','title'=>'Information Assurance and Security 2'],['code'=>'A_IPT 101','title'=>'Integrative Programming and Technologies'],['code'=>'A_SIA 101','title'=>'Systems Integration and Architecture'],['code'=>'A_OS 101','title'=>'Operating System Applications'],['code'=>'A_SA 101','title'=>'System Administration and Maintenance'],['code'=>'A_MT 101','title'=>'Multimedia Technologies'],['code'=>'A_MS 101','title'=>'Discrete Mathematics'],['code'=>'A_MS 102','title'=>'Quantitative Methods'],['code'=>'A_SP 101','title'=>'Social and Professional Issues'],['code'=>'A_GE 1','title'=>'Understanding the Self'],['code'=>'A_GE 2','title'=>'Readings in Philippine History'],['code'=>'A_GE 3','title'=>'Art Appreciation'],['code'=>'A_GE 4','title'=>'Purposive Communication'],['code'=>'A_GE 5','title'=>'The Contemporary World'],['code'=>'A_GE 6','title'=>'Science, Technology and Society'],['code'=>'A_GE 7','title'=>'Mathematics in the Modern World'],['code'=>'A_GE_8','title'=>'Ethics'],['code'=>'A_GE_9','title'=>'The Life and Works of Rizal'],['code'=>'A_CO 101','title'=>'Computer Organization'],['code'=>'A_ELEC1','title'=>'Elective 1'],['code'=>'A_ELEC2','title'=>'Elective 2'],['code'=>'A_ELEC3','title'=>'Elective 3'],['code'=>'A_ELEC4','title'=>'Elective 4'],['code'=>'A_TECH 101','title'=>'Technopreneurship'],['code'=>'A_GEE 1','title'=>'Living in the IT Era'],['code'=>'A_GEE 2','title'=>'The Entrepreneurial Mind'],['code'=>'A_GEE 3','title'=>'Reading Visual Art'],['code'=>'A_GEE 4','title'=>'Global Citizenship'],['code'=>'A_IC 1','title'=>'Personality Development'],['code'=>'A_NSTP 1','title'=>'ROTC/CWTS 1'],['code'=>'A_NSTP 2','title'=>'ROTC/CWTS 2'],['code'=>'A_PE1','title'=>'PATH-FIT I'],['code'=>'A_PE2','title'=>'PATH-FIT II'],['code'=>'A_PE3','title'=>'PATH-FIT III'],['code'=>'A_PE4','title'=>'PATH-FIT IV']];
  $facultyLookup=collect($facultyCourses??[])->keyBy(fn($c)=>(string)($c->code??''));
  $bsitOptions=collect($bsitCatalog)->filter(fn($item)=>$facultyLookup->has((string)$item['code']));
  $remainingFacultyCourses=collect($facultyCourses??[])->filter()->reject(fn($c)=>in_array((string)($c->code??''),collect($bsitCatalog)->pluck('code')->all(),true))->values();
  $courseRoomAssignments = collect($facultyCourses ?? [])
    ->mapWithKeys(fn($course) => [(int) $course->id => (int) ($course->classroom_id ?? 0)])
    ->filter(fn($classroomId) => $classroomId > 0);
  $scheduledRoomAssignments = collect($facultySchedules ?? [])
    ->sortByDesc('start_at')
    ->groupBy('course_id')
    ->map(fn($courseSchedules) => (int) ($courseSchedules->first()->classroom_id ?? 0))
    ->filter(fn($classroomId) => $classroomId > 0);
  $courseRoomAssignments = $courseRoomAssignments->union($scheduledRoomAssignments);
@endphp

<div class="modal-overlay" id="addModal" aria-hidden="true">
  <div class="modal add-modal" role="dialog" aria-labelledby="addModalTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="addModalTitle">Add Schedule For My Subject</div>
        <div class="modal-sub">Only subjects assigned to your account can be scheduled.</div>
      </div>
      <button class="modal-close" id="closeAddModal" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      @if($facultyCourses->isEmpty())
        <div class="alert alert-warning mb-0">
          No subjects are assigned to your account yet, so schedule creation is unavailable.
        </div>
      @else
      <form method="POST" action="{{ route('faculty.schedule.store') }}" class="form-grid">
        @csrf
        <div class="fg full">
          <label class="flabel">Subject</label>
          <select name="course_id" class="fselect" required>
            <option value="">Select your subjectâ€¦</option>
            @foreach($bsitOptions as $option)
              @php $course=$facultyLookup->get((string)$option['code']); @endphp
              @if($course)
                <option value="{{ $course->id }}" data-room-id="{{ $courseRoomAssignments->get($course->id, 0) }}" {{ (string)old('course_id')===(string)$course->id?'selected':'' }}>
                  {{ $option['code'] }} â€“ {{ $course->title }}
                </option>
              @endif
            @endforeach
            @if($remainingFacultyCourses->isNotEmpty())
              <option value="" disabled>â€” Other Subjects â€”</option>
              @foreach($remainingFacultyCourses as $course)
                <option value="{{ $course->id }}" data-room-id="{{ $courseRoomAssignments->get($course->id, 0) }}" {{ (string)old('course_id')===(string)$course->id?'selected':'' }}>
                  {{ $course->code }} â€“ {{ $course->title }}
                </option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="fg">
          <label class="flabel" for="facultyBlockSection">Block</label>
          <select id="facultyBlockSection" name="block_section" class="fselect" required>
            <option value="">Select blockâ€¦</option>
            <option value="Block A" {{ old('block_section') === 'Block A' ? 'selected' : '' }}>Block A</option>
            <option value="Block B" {{ old('block_section') === 'Block B' ? 'selected' : '' }}>Block B</option>
          </select>
        </div>
        <div class="fg">
          <label class="flabel">Semester Start</label>
          <input type="date" name="semester_start" class="finput" value="{{ old('semester_start') }}" required>
        </div>
        <div class="fg">
          <label class="flabel">Semester End</label>
          <input type="date" name="semester_end" class="finput" value="{{ old('semester_end') }}" required>
        </div>

        <div class="form-row">
          <div class="fg"><label class="flabel">Day 1</label>
            <select name="day1" class="fselect">
              <option value="">Select dayâ€¦</option>
              <option value="1" {{ old('day1')=='1'?'selected':'' }}>Monday</option>
              <option value="2" {{ old('day1')=='2'?'selected':'' }}>Tuesday</option>
              <option value="3" {{ old('day1')=='3'?'selected':'' }}>Wednesday</option>
              <option value="4" {{ old('day1')=='4'?'selected':'' }}>Thursday</option>
              <option value="5" {{ old('day1')=='5'?'selected':'' }}>Friday</option>
            </select>
          </div>
          <div class="fg"><label class="flabel">Day 1 Start</label>
            <input type="time" name="day1_start" class="finput" value="{{ old('day1_start') }}">
          </div>
          <div class="fg"><label class="flabel">Day 1 End</label>
            <input type="time" name="day1_end" class="finput" value="{{ old('day1_end') }}">
          </div>
        </div>

        <div class="form-row">
          <div class="fg"><label class="flabel">Day 2</label>
            <select name="day2" class="fselect">
              <option value="">Select dayâ€¦</option>
              <option value="1" {{ old('day2')=='1'?'selected':'' }}>Monday</option>
              <option value="2" {{ old('day2')=='2'?'selected':'' }}>Tuesday</option>
              <option value="3" {{ old('day2')=='3'?'selected':'' }}>Wednesday</option>
              <option value="4" {{ old('day2')=='4'?'selected':'' }}>Thursday</option>
              <option value="5" {{ old('day2')=='5'?'selected':'' }}>Friday</option>
            </select>
          </div>
          <div class="fg"><label class="flabel">Day 2 Start</label>
            <input type="time" name="day2_start" class="finput" value="{{ old('day2_start') }}">
          </div>
          <div class="fg"><label class="flabel">Day 2 End</label>
            <input type="time" name="day2_end" class="finput" value="{{ old('day2_end') }}">
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Expected Enrolled</label>
          <input type="number" min="0" name="enrolled" class="finput" value="{{ old('enrolled', 0) }}">
        </div>

        <!-- Availability Preview -->
        <div class="avail-panel">
          <div class="avail-title">Availability Preview</div>
          <div class="avail-list" id="availList"><div style="font-size:.72rem;color:var(--tx-4)">Select a subject, dates, and days to preview.</div></div>
          <div class="avail-title" style="margin-top:8px">Suggested Time Slots</div>
          <div id="suggestList"><div style="font-size:.72rem;color:var(--tx-4)">No suggestions yet.</div></div>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" type="submit"><i class="ti ti-calendar-plus"></i> Add Schedule</button>
          <span class="form-hint">Choose any weekday, or two different weekdays. Conflicts will be blocked.</span>
        </div>
      </form>
      @endif
    </div>
  </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â• DAY CLASSES MODAL â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal-overlay" id="dayModal" aria-hidden="true">
  <div class="modal day-modal" role="dialog" aria-labelledby="dayModalTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="dayModalTitle">Classes on â€”</div>
        <div class="modal-sub" id="dayModalSub"></div>
      </div>
      <button class="modal-close" id="closeDayModal" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="day-modal-date" id="dayModalDate"></div>
      <div class="day-cls-list" id="dayClsList"></div>
    </div>
  </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â• SESSION MODAL â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal-overlay" id="sessionModal" aria-hidden="true">
  <div class="modal session-modal" role="dialog" aria-labelledby="sessionModalTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="sessionModalTitle">Class Session</div>
        <div class="modal-sub" id="sessionModalSub"></div>
      </div>
      <button class="modal-close" id="closeSessionModal" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="session-detail-header" id="sessionHeader">
        <div class="session-icon"><i class="ti ti-school"></i></div>
        <div class="session-header-txt">
          <div class="sess-name" id="sessName"></div>
          <div class="sess-code" id="sessCode"></div>
        </div>
      </div>
      <div class="session-info-grid">
        <div class="session-info-item">
          <div class="session-info-lbl">Date</div>
          <div class="session-info-val"><i class="ti ti-calendar"></i><span id="sessDate"></span></div>
        </div>
        <div class="session-info-item">
          <div class="session-info-lbl">Time</div>
          <div class="session-info-val"><i class="ti ti-clock"></i><span id="sessTime"></span></div>
        </div>
        <div class="session-info-item">
          <div class="session-info-lbl">Location</div>
          <div class="session-info-val"><i class="ti ti-map-pin"></i><span id="sessLoc"></span></div>
        </div>
        <div class="session-info-item">
          <div class="session-info-lbl">Block</div>
          <div class="session-info-val"><i class="ti ti-layout-grid"></i><span id="sessBlock"></span></div>
        </div>
        <div class="session-info-item">
          <div class="session-info-lbl">Status</div>
          <div class="session-info-val" id="sessStatus"></div>
        </div>
      </div>
      <div class="makeup-section">
        <div class="makeup-title">Make-up Suggestions</div>
        <div class="makeup-list" id="makeupList" aria-busy="true"><div class="makeup-skeleton" aria-hidden="true"><span></span><span></span><span></span></div></div>
      </div>
      <form id="sessionCancelForm" method="POST">
        @csrf @method('PATCH')
        <div class="session-info-item" style="margin:12px 0">
          <label class="session-info-lbl" for="cancellationReason">Cancellation reason</label>
          <textarea id="cancellationReason" name="cancellation_reason" required maxlength="500" placeholder="Explain why this class is being cancelled..." style="width:100%;min-height:70px;margin-top:6px;padding:9px;border:1px solid var(--border);border-radius:8px;font:inherit"></textarea>
        </div>
        <div class="session-act">
          <button type="button" class="btn-remind" id="remindBtn">Send Reminder</button>
          <button type="submit" class="btn-danger" id="cancelBtn">Cancel Session</button>
        </div>
      </form>
    </div>
  </div>
</div>

@php
  $classroomPayload=collect($classrooms??[])->map(fn($c)=>['id'=>(int)$c->id,'name'=>(string)$c->name,'building'=>(string)$c->building,'capacity'=>(int)($c->capacity??0)])->values();
@endphp
<script type="application/json" id="classroomData">@json($classroomPayload)</script>
<script type="application/json" id="scheduleData">@json($calendarSchedule)</script>

<script>
// â”€â”€ Schedule data â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const schedule = JSON.parse(document.getElementById('scheduleData').textContent || '[]');
const classrooms = JSON.parse(document.getElementById('classroomData').textContent || '[]');

// â”€â”€ Endpoints â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const availEndpoint = "{{ url('/api/v1/room-availability/check') }}";
const roomStatusEndpoint = "{{ url('/api/v1/room-statuses') }}";
const cancelEndpoint = "{{ route('faculty.schedule.cancel', ['schedule' => '__ID__']) }}";
const cancellationReason = document.getElementById('cancellationReason');

// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function toDateKey(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
function buildDT(dateKey, t) { return dateKey && t ? `${dateKey}T${t}:00` : null; }
function minsFromTime(t) { const [h,m]=String(t||'').split(':').map(Number); return isFinite(h)&&isFinite(m)?h*60+m:null; }
function isoDay(d) { return d.getDay()===0?7:d.getDay(); }
function firstOccurrence(semStart, day) {
  const d = new Date(`${semStart}T00:00:00`);
  if (isNaN(d)) return null;
  let diff = day - isoDay(d);
  if (diff < 0) diff += 7;
  d.setDate(d.getDate()+diff);
  return d;
}
async function checkAvail(roomId, start, end) {
  try {
    const u = new URL(availEndpoint, location.origin);
    u.searchParams.set('classroom_id', roomId);
    u.searchParams.set('start_at', start);
    u.searchParams.set('end_at', end);
    const r = await fetch(u, {credentials:'same-origin',headers:{Accept:'application/json'}});
    if (!r.ok) return null;
    return (await r.json().catch(()=>null))?.data || null;
  } catch { return null; }
}

// â”€â”€ Modals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.add('open');
  el.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.classList.remove('open');
  el.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
}

['addModal','dayModal','sessionModal'].forEach(id => {
  document.getElementById(id)?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal(id);
  });
});

// â”€â”€ Add Schedule Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
['openAddModal','openAddModal2'].forEach(id => {
  document.getElementById(id)?.addEventListener('click', () => {
    openModal('addModal');
    queueAvailCheck();
  });
});
document.getElementById('closeAddModal')?.addEventListener('click', () => closeModal('addModal'));

let availTimer = null, availToken = 0;
function queueAvailCheck() {
  clearTimeout(availTimer);
  availTimer = setTimeout(runAvailCheck, 300);
}

async function runAvailCheck() {
  const token = ++availToken;
  const form = document.querySelector('#addModal form');
  if (!form) return;
  const courseOption = form.querySelector('[name=course_id] option:checked');
  const roomId = courseOption?.dataset.roomId || '';
  const semStart = form.querySelector('[name=semester_start]')?.value;
  const day1 = form.querySelector('[name=day1]')?.value;
  const day1s = form.querySelector('[name=day1_start]')?.value;
  const day1e = form.querySelector('[name=day1_end]')?.value;
  const day2 = form.querySelector('[name=day2]')?.value;
  const day2s = form.querySelector('[name=day2_start]')?.value;
  const day2e = form.querySelector('[name=day2_end]')?.value;
  const enrolled = parseInt(form.querySelector('[name=enrolled]')?.value||0);

  const availList = document.getElementById('availList');
  const suggestList = document.getElementById('suggestList');

  if (!roomId || !semStart) {
    availList.innerHTML = '<div style="font-size:.72rem;color:var(--tx-4)">Select a subject with an admin-assigned room, dates, and days to preview.</div>';
    return;
  }

  availList.innerHTML = '<div style="font-size:.72rem;color:var(--tx-3)">Checkingâ€¦</div>';

  const configs = [
    {label:'Day 1', isoDay: parseInt(day1), start: day1s, end: day1e},
    {label:'Day 2', isoDay: parseInt(day2), start: day2s, end: day2e},
  ].filter(c => c.isoDay && c.start && c.end);

  if (!configs.length) { availList.innerHTML = '<div style="font-size:.72rem;color:var(--tx-4)">Select days and times to preview.</div>'; return; }

  const items = [], timeSugg = [];

  for (const cfg of configs) {
    const date = firstOccurrence(semStart, cfg.isoDay);
    if (!date || availToken !== token) return;
    const dk = toDateKey(date);
    const sa = buildDT(dk, cfg.start), ea = buildDT(dk, cfg.end);
    if (!sa || !ea) continue;
    const av = await checkAvail(roomId, sa, ea);
    if (availToken !== token) return;
    if (!av) continue;

    const status = av.status || (av.available ? 'available' : 'occupied');
    items.push({ label: cfg.label, dateLabel: `${dk} ${cfg.start}â€“${cfg.end}`, status, reason: av.reason });

    if (!av.available) {
      const dur = (minsFromTime(cfg.end)||0) - (minsFromTime(cfg.start)||0);
      let cursor = (minsFromTime(cfg.start)||0) + 30;
      while (cursor + dur <= 19*60 && timeSugg.length < 3) {
        const ts = `${String(Math.floor(cursor/60)).padStart(2,'0')}:${String(cursor%60).padStart(2,'0')}`;
        const te = `${String(Math.floor((cursor+dur)/60)).padStart(2,'0')}:${String((cursor+dur)%60).padStart(2,'0')}`;
        const a2 = await checkAvail(roomId, buildDT(dk,ts), buildDT(dk,te));
        if (availToken !== token) return;
        if (a2?.available) timeSugg.push({ dayKey: cfg.label, start: ts, end: te, dk });
        cursor += 30;
      }
    }
  }

  if (availToken !== token) return;

  availList.innerHTML = items.map(i => `
    <div class="avail-item">
      <div>
        <div class="avail-name">${i.label}</div>
        <div class="avail-meta">${i.dateLabel}${i.reason?` Â· ${i.reason}`:''}</div>
      </div>
      <span class="avail-status is-${i.status}">${i.status}</span>
    </div>`).join('') || '<div style="font-size:.72rem;color:var(--tx-4)">No data.</div>';

  suggestList.innerHTML = timeSugg.map(s => `
    <div class="suggest-item">
      <div>
        <div style="font-size:.77rem;font-weight:600;color:var(--tx)">${s.dayKey} Â· ${s.start}â€“${s.end}</div>
        <div style="font-size:.68rem;color:var(--tx-3)">Suggested slot on ${s.dk}</div>
      </div>
      <button class="suggest-btn" data-day="${s.dayKey}" data-start="${s.start}" data-end="${s.end}">Apply</button>
    </div>`).join('') || '<div style="font-size:.72rem;color:var(--tx-4)">No time suggestions.</div>';

}

document.querySelector('#addModal form')?.addEventListener('change', queueAvailCheck);
document.querySelector('#addModal form')?.addEventListener('input', queueAvailCheck);

document.getElementById('suggestList')?.addEventListener('click', e => {
  const btn = e.target.closest('.suggest-btn');
  if (!btn) return;
  const form = document.querySelector('#addModal form');
  const day = btn.dataset.day, s = btn.dataset.start, en = btn.dataset.end;
  if (day === 'Day 1') {
    form.querySelector('[name=day1_start]').value = s;
    form.querySelector('[name=day1_end]').value = en;
  } else if (day === 'Day 2') {
    form.querySelector('[name=day2_start]').value = s;
    form.querySelector('[name=day2_end]').value = en;
  }
  queueAvailCheck();
});

document.querySelector('#addModal form')?.addEventListener('submit', e => {
  const form = e.currentTarget;
  const subject = form.querySelector('[name=course_id] option:checked')?.textContent?.trim() || 'your subject';
  const semesterStart = form.querySelector('[name=semester_start]')?.value || 'the selected start date';
  const semesterEnd = form.querySelector('[name=semester_end]')?.value || 'the selected end date';
  const day1 = form.querySelector('[name=day1] option:checked')?.textContent?.trim();
  const day1Start = form.querySelector('[name=day1_start]')?.value;
  const day1End = form.querySelector('[name=day1_end]')?.value;
  const day2 = form.querySelector('[name=day2] option:checked')?.textContent?.trim();
  const day2Start = form.querySelector('[name=day2_start]')?.value;
  const day2End = form.querySelector('[name=day2_end]')?.value;
  const sessions = [
    day1 && day1Start && day1End ? `${day1} ${day1Start}â€“${day1End}` : null,
    day2 && day2Start && day2End ? `${day2} ${day2Start}â€“${day2End}` : null,
  ].filter(Boolean).join(', ');

  const message = [
    'Review this semester schedule before saving:',
    `Subject: ${subject}`,
    `Semester: ${semesterStart} to ${semesterEnd}`,
    `Sessions: ${sessions || 'not fully selected'}`,
    '',
    'This will create all sessions for the semester. Continue only if these details are correct.',
  ].join('\n');

  if (! window.confirm(message)) {
    e.preventDefault();
  }
});
// â”€â”€ View Switcher â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const gridView = document.getElementById('gridView');
const calView = document.getElementById('calendarView');
const tlView = document.getElementById('timelineView');
const btnGrid = document.getElementById('btnGrid');
const btnCal = document.getElementById('btnCal');
const btnTl = document.getElementById('btnTl');

function setView(v) {
  gridView.style.display = v==='grid' ? 'grid' : 'none';
  calView.style.display  = v==='cal'  ? 'block' : 'none';
  tlView.style.display   = v==='tl'   ? 'flex' : 'none';
  tlView.style.flexDirection = 'column';
  btnGrid.classList.toggle('active', v==='grid');
  btnCal.classList.toggle('active', v==='cal');
  btnTl.classList.toggle('active', v==='tl');
  if (v === 'cal') renderCalendar();
}
btnGrid.addEventListener('click', () => setView('grid'));
btnCal.addEventListener('click', () => setView('cal'));
btnTl.addEventListener('click', () => setView('tl'));
setView('grid');

// â”€â”€ Class filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.getElementById('classFilter')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.cls-card').forEach(c => {
    const show = !q || c.textContent.toLowerCase().includes(q);
    c.style.opacity = show ? '1' : '.2';
  });
  document.querySelectorAll('.tl-item').forEach(c => {
    c.style.display = !q || c.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// â”€â”€ Calendar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let calYear, calMonth;
const now = new Date();
const scheduleDates = schedule
  .map(cls => new Date(cls.start_at))
  .filter(date => !Number.isNaN(date.getTime()));
const currentMonthHasClasses = scheduleDates.some(date =>
  date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth()
);
const calendarReference = currentMonthHasClasses
  ? now
  : (scheduleDates.sort((a, b) => Math.abs(a - now) - Math.abs(b - now))[0] || now);
calYear = calendarReference.getFullYear();
calMonth = calendarReference.getMonth();

function buildDateMap() {
  const map = {};
  schedule.forEach(cls => {
    if (!cls.start_at) return;
    const d = new Date(cls.start_at);
    if (isNaN(d)) return;
    const dk = toDateKey(d);
    if (!map[dk]) map[dk] = [];
    map[dk].push(cls);
  });
  return map;
}

function renderCalendar() {
  const dateMap = buildDateMap();
  const label = new Date(calYear, calMonth, 1).toLocaleDateString('en-US', {month:'long', year:'numeric'});
  document.getElementById('calMonthLabel').textContent = label;

  const firstDay = new Date(calYear, calMonth, 1).getDay();
  const daysInMonth = new Date(calYear, calMonth+1, 0).getDate();
  const todayKey = toDateKey(now);

  const grid = document.getElementById('calDays');
  grid.innerHTML = '';

  for (let i = 0; i < firstDay; i++) {
    const el = document.createElement('div');
    el.className = 'cal-day empty';
    grid.appendChild(el);
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const dateKey = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const classes = dateMap[dateKey] || [];
    const isToday = dateKey === todayKey;

    const el = document.createElement('div');
    el.className = 'cal-day' + (classes.length ? ' has-classes' : '') + (isToday ? ' today' : '');

    const numEl = document.createElement('div');
    numEl.className = 'cal-day-num';
    numEl.textContent = d;
    el.appendChild(numEl);

    if (classes.length) {
      const dotsEl = document.createElement('div');
      dotsEl.className = 'cal-day-dots';
      const unique = [...new Set(classes.map(c=>c.color))].slice(0,4);
      unique.forEach(color => {
        const dot = document.createElement('div');
        dot.className = `cal-day-dot ${color}`;
        dotsEl.appendChild(dot);
      });
      el.appendChild(dotsEl);

      const countEl = document.createElement('div');
      countEl.className = 'cal-day-count';
      countEl.textContent = classes.length + ' class' + (classes.length>1?'es':'');
      el.appendChild(countEl);

      el.addEventListener('click', () => openDayModal(dateKey, classes));
    }

    grid.appendChild(el);
  }
}

document.getElementById('calPrev')?.addEventListener('click', () => {
  calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; }
  renderCalendar();
});
document.getElementById('calNext')?.addEventListener('click', () => {
  calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; }
  renderCalendar();
});

// â”€â”€ Day Classes Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openDayModal(dateKey, classes) {
  const d = new Date(dateKey + 'T12:00:00');
  const dayName = d.toLocaleDateString('en-US', {weekday:'long', month:'long', day:'numeric', year:'numeric'});
  document.getElementById('dayModalTitle').textContent = 'Classes on ' + d.toLocaleDateString('en-US',{weekday:'long'});
  document.getElementById('dayModalSub').textContent = d.toLocaleDateString('en-US',{month:'long', day:'numeric', year:'numeric'});
  document.getElementById('dayModalDate').innerHTML = `<i class="ti ti-calendar"></i> ${dayName} Â· ${classes.length} class${classes.length>1?'es':''}`;

  const list = document.getElementById('dayClsList');
  if (!classes.length) {
    list.innerHTML = `<div class="day-no-class"><i class="ti ti-calendar-off"></i>No classes on this day.</div>`;
  } else {
    list.innerHTML = classes.map(cls => {
      const status = cls.status && cls.status !== 'scheduled'
        ? `<span class="status-badge status-${cls.status}">${cls.status}</span>` : '';
      return `<div class="day-cls-item ${cls.color}"
          data-id="${cls.id||''}"
          data-room-id="${cls.classroom_id||''}"
          data-subject="${cls.subject||''}"
          data-code="${cls.code||''}"
          data-block="${cls.section||''}"
          data-location="${cls.location||''}"
          data-date="${cls.date||''}"
          data-time="${cls.duration||''}"
          data-start-at="${cls.start_at||''}"
          data-end-at="${cls.end_at||''}"
          data-status="${cls.status||'scheduled'}"
          data-color="${cls.color}">
        <div class="day-cls-inner">
          <div class="day-cls-top">
            <span class="cls-code">${cls.code}</span>
            ${status}
          </div>
          <div class="day-cls-name">${cls.subject}</div>
          <div class="day-cls-info">
            <span class="day-cls-meta"><i class="ti ti-clock"></i>${cls.duration||cls.time}</span>
            <span class="day-cls-meta"><i class="ti ti-map-pin"></i>${cls.location}</span>
            <span class="day-cls-meta"><i class="ti ti-layout-grid"></i>${cls.section || 'Block not set'}</span>
            <span class="day-cls-meta"><i class="ti ti-users"></i>${cls.students} students</span>
          </div>
        </div>
      </div>`;
    }).join('');

    list.querySelectorAll('.day-cls-item').forEach(el => {
      el.addEventListener('click', () => openSessionFromEl(el));
    });
  }

  openModal('dayModal');
}

document.getElementById('closeDayModal')?.addEventListener('click', () => closeModal('dayModal'));

// â”€â”€ Session Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openSessionFromEl(el) {
  const data = {
    id: el.dataset.id||'',
    roomId: el.dataset.roomId||'',
    subject: el.dataset.subject||'Class',
    code: el.dataset.code||'',
    block: el.dataset.block||'Block not set',
    location: el.dataset.location||'',
    date: el.dataset.date||'',
    time: el.dataset.time||'',
    startAt: el.dataset.startAt||'',
    endAt: el.dataset.endAt||'',
    status: (el.dataset.status||'scheduled').toLowerCase(),
    color: el.dataset.color||'blue',
  };
  openSessionModal(data);
}

function openSessionModal(data) {
  document.getElementById('sessionModalTitle').textContent = data.subject;
  document.getElementById('sessionModalSub').textContent = `${data.code} Â· ${data.status.charAt(0).toUpperCase()+data.status.slice(1)}`;
  document.getElementById('sessName').textContent = data.subject;
  document.getElementById('sessCode').textContent = data.code;
  document.getElementById('sessDate').textContent = data.date;
  document.getElementById('sessTime').textContent = data.time;
  document.getElementById('sessLoc').textContent = data.location;
  document.getElementById('sessBlock').textContent = data.block;

  const statusEl = document.getElementById('sessStatus');
  statusEl.innerHTML = `<span class="status-badge status-${data.status}">${data.status}</span>`;

  const header = document.getElementById('sessionHeader');
  header.className = `session-detail-header ${data.color}`;

  const cancelForm = document.getElementById('sessionCancelForm');
  cancelForm.action = cancelEndpoint.replace('__ID__', data.id);
  document.getElementById('cancelBtn').disabled = ['cancelled','completed'].includes(data.status);
  if (cancellationReason) cancellationReason.value = '';

  const makeupList = document.getElementById('makeupList');
  makeupList.setAttribute('aria-busy', 'true');
  makeupList.innerHTML = '<div class="makeup-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>';
  loadMakeupSuggestions(data, makeupList);

  closeModal('dayModal');
  setTimeout(() => openModal('sessionModal'), 100);
}

async function loadMakeupSuggestions(data, el) {
  if (!data.roomId || !data.startAt || !data.endAt) {
    el.innerHTML = '<div style="font-size:.73rem;color:var(--tx-4)">No suggestions available.</div>';
    el.setAttribute('aria-busy', 'false');
    return;
  }
  const start = new Date(data.startAt);
  const end = new Date(data.endAt);
  if (isNaN(start) || isNaN(end)) { el.innerHTML = '<div style="font-size:.73rem;color:var(--tx-4)">No suggestions.</div>'; el.setAttribute('aria-busy', 'false'); return; }
  const dur = Math.round((end-start)/60000);
  const sugg = [];
  for (let offset = 1; offset <= 7 && sugg.length < 3; offset++) {
    const nd = new Date(start);
    nd.setDate(nd.getDate() + offset);
    const dk = toDateKey(nd);
    const ts = `${String(nd.getHours()).padStart(2,'0')}:${String(nd.getMinutes()).padStart(2,'0')}`;
    const em = nd.getHours()*60+nd.getMinutes()+dur;
    const te = `${String(Math.floor(em/60)).padStart(2,'0')}:${String(em%60).padStart(2,'0')}`;
    const av = await checkAvail(data.roomId, buildDT(dk,ts), buildDT(dk,te));
    if (av?.available) sugg.push(`${dk} ${ts}â€“${te}`);
  }
  el.innerHTML = sugg.length
    ? sugg.map(s=>`<div class="makeup-item"><span class="makeup-item-lbl"><i class="ti ti-calendar-check" style="font-size:12px"></i> ${s}</span><span class="makeup-meta">Available</span></div>`).join('')
    : '<div style="font-size:.73rem;color:var(--tx-4)">No available slots found.</div>';
  el.setAttribute('aria-busy', 'false');
}

document.getElementById('closeSessionModal')?.addEventListener('click', () => closeModal('sessionModal'));
document.getElementById('remindBtn')?.addEventListener('click', () => alert('Reminder queued.'));
document.getElementById('sessionCancelForm')?.addEventListener('submit', e => {
  if (!cancellationReason?.value.trim()) {
    e.preventDefault();
    cancellationReason?.focus();
    return;
  }
  if (!confirm('Cancel this class occurrence only? Other teachers will see the room as free during this time.')) e.preventDefault();
});

document.querySelectorAll('.cls-card, .tl-item').forEach(el => {
  el.addEventListener('click', () => openSessionFromEl(el));
});

// â”€â”€ Profile dropdown â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.getElementById('profileToggle')?.addEventListener('click', e => {
  e.stopPropagation();
  document.getElementById('profileDropdown')?.classList.toggle('open');
});
document.addEventListener('click', () => document.getElementById('profileDropdown')?.classList.remove('open'));

// â”€â”€ Escape key â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    ['sessionModal','dayModal','addModal'].forEach(closeModal);
  }
});

</script>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>