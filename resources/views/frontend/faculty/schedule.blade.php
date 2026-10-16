<?php
$faculty_name     = $faculty_name ?? request()->user()?->name ?? 'Faculty';
$faculty_dept     = $faculty_dept ?? request()->user()?->department ?? 'Department';
$faculty_initials = $faculty_initials ?? strtoupper(substr((string) $faculty_name, 0, 1));
$semester         = $semester ?? "1st Semester 2025–2026";

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
$weekRange = $monday->format('M d') . ' – ' . $friday->format('M d, Y');

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
    $durationText .= ' – ' . $end->format('g:i A');
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
<title>SmartDoor — Semester Schedule</title>
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

/* ══════════════════════════════════════
   SIDEBAR
══════════════════════════════════════ */
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

/* ══════════════════════════════════════
   MAIN
══════════════════════════════════════ */
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

/* ══════════════════════════════════════
   CONTENT
══════════════════════════════════════ */
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

/* ══════════════════════════════════════
   CALENDAR VIEW
══════════════════════════════════════ */
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
.cal-day-count{font-size:.68rem;font-weight:700;color:var(--blue-dk)}

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

/* ══════════════════════════════════════
   SCHEDULE GRID
══════════════════════════════════════ */
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
  font-family:var(--ff-head);font-size:.68rem;font-weight:700;letter-spacing:.04em;flex-shrink:0;
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
  border-radius:var(--r3);font-size:.68rem;font-weight:600;
}
.status-cancelled{background:var(--red-lt);border:1px solid var(--red-bdr);color:var(--red)}
.status-completed{background:var(--emerald-lt);border:1px solid var(--emerald-bdr);color:var(--emerald)}
.status-ongoing{background:var(--blue-lt);border:1px solid var(--blue-bdr);color:var(--blue-dk)}
.block-badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:4px;background:var(--surface-2);border:1px solid var(--border);color:var(--tx-2);font-size:.68rem;font-weight:600}

/* ══════════════════════════════════════
   TIMELINE VIEW
══════════════════════════════════════ */
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

/* ══════════════════════════════════════
   SUBJECT OVERVIEW
══════════════════════════════════════ */
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

/* ══════════════════════════════════════
   ADD SCHEDULE CARD
══════════════════════════════════════ */
.add-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;box-shadow:var(--shadow-sm)}
.add-card-title{font-size:.85rem;font-weight:700;color:var(--tx);margin-bottom:2px}
.add-card-note{font-size:.72rem;color:var(--tx-3)}

/* ══════════════════════════════════════
   MODALS — BASE
══════════════════════════════════════ */
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

/* ══════════════════════════════════════
   SCHEDULE DESIGN REFRESH
   Clearer hierarchy, larger readable type, today highlight, better class cards
══════════════════════════════════════ */
.page-header{min-height:240px}
.ph-icon{width:52px;height:52px;font-size:1.25rem}
.ph-title h1{font-size:1.75rem}
.ph-title p{font-size:.92rem;color:rgba(255,255,255,.88)}
.stats-grid{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}
.stat-card{padding:18px 18px 16px;box-shadow:0 8px 24px rgba(7,14,43,.2)}
.stat-val{font-size:2rem}
.stat-label{font-size:.84rem;color:var(--tx-2);font-weight:700;margin-bottom:3px}
.stat-sub{font-size:.76rem;color:#6b7280}
.stat-ic{width:40px;height:40px;font-size:18px;border-radius:11px}

.toolbar{padding:12px 16px;gap:12px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
.search-box{flex:0 1 300px;padding:9px 14px}
.search-box input{font-size:.9rem}
.sem-badge{font-size:.82rem;padding:9px 14px;border-radius:8px}
.vs-btn{font-size:.84rem;padding:8px 14px;min-height:36px}
.tb-action-btn,.tb-add-btn{font-size:.85rem;padding:9px 16px;min-height:40px}

.sched-grid{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
.day-col{border-radius:14px;box-shadow:var(--shadow-sm)}
.day-col-header{padding:14px;background:linear-gradient(180deg,#fff,var(--surface-2));flex-wrap:wrap}
.day-abbr{width:36px;height:36px;border-radius:10px;font-size:.76rem}
.day-name{font-size:1rem}
.day-hrs{font-size:.76rem;color:var(--tx-2);font-weight:600;gap:5px;flex-wrap:wrap}
.day-hrs-sep{opacity:.5}
.day-col.today-col{border-color:var(--blue-bdr);box-shadow:0 0 0 3px rgba(37,99,235,.12),var(--shadow-sm)}
.day-col.today-col .day-col-header{background:var(--blue-lt)}
.today-col .day-name::after{content:'Today';margin-left:8px;padding:2px 8px;border-radius:999px;background:var(--blue);color:#fff;font:700 .62rem var(--ff);letter-spacing:.05em;text-transform:uppercase;vertical-align:middle}
.no-class{padding:38px 14px;font-size:.86rem;color:var(--tx-3)}
.no-class i{font-size:24px;opacity:.35}

.cls-card{padding:16px 16px 16px 18px}
.cls-card:focus-visible{outline:3px solid #6785d4;outline-offset:-3px}
.cls-timebar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:8px}
.cls-time{display:inline-flex;align-items:center;gap:6px;font-family:var(--ff);font-size:.88rem;font-weight:700;color:var(--card-txt)}
.cls-time i{font-size:15px}
.cls-name{font-size:.97rem;font-weight:700;line-height:1.35;margin-bottom:10px}
.cls-tags{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.cls-code{font-size:.74rem;padding:3px 9px}
.block-badge{font-size:.76rem}
.cls-meta{gap:10px;flex-wrap:wrap;justify-content:flex-start}
.cls-room,.cls-students{font-size:.82rem;color:var(--tx-2)}
.cls-room i,.cls-students i{font-size:13px}
.cls-students{margin-left:auto}
.cls-students span{font-size:.84rem}
.status-badge{font-size:.74rem;padding:3px 9px;font-weight:700}
.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;animation:livepulse 1.6s ease-in-out infinite}
@keyframes livepulse{0%,100%{opacity:1}50%{opacity:.35}}
.cls-card[data-status="cancelled"]{background:#fffafa}
.cls-card[data-status="cancelled"]::before{background:var(--red)}
.cls-card[data-status="cancelled"] .cls-name,.cls-card[data-status="cancelled"] .cls-time{text-decoration:line-through;color:var(--tx-3)}

.tl-time-start{font-size:.86rem}.tl-time-end{font-size:.76rem}.tl-subj{font-size:.95rem}.tl-sub{font-size:.8rem}.tl-code-tag{font-size:.72rem}
.cal-weekday{font-size:.72rem}.cal-day-num{font-size:.88rem}.cal-sub{font-size:.8rem}
.section-h2{font-size:1rem}.sc-name{font-size:.92rem}.sc-code-tag{font-size:.74rem}.sc-row{font-size:.8rem}

@media (max-width:768px){
  .page-header{min-height:0}
  .ph-title h1{font-size:1.4rem}
  .toolbar{padding:12px}
  .search-box{flex:1 1 100%}
  .spacer{display:none}
  .view-switcher{flex:1 1 100%;justify-content:space-between}
  .vs-btn{flex:1;justify-content:center}
}
@media (prefers-reduced-motion:reduce){.live-dot{animation:none}}

/* ── Refinement pass: calmer hierarchy, fewer boxes, cleaner KPIs ── */

/* KPI cards: icon tile on the left, value + label + note stacked on the right */
.page-header .stats-grid{gap:14px}
.stat-card{
  display:grid;grid-template-columns:52px minmax(0,1fr);
  grid-template-areas:"ic val" "ic label" "ic sub";
  column-gap:14px;row-gap:1px;align-items:center;
  padding:16px 18px;border:0;border-radius:16px;
  background:rgba(255,255,255,.97);
  box-shadow:inset 4px 0 0 var(--accent-a),0 10px 28px rgba(7,14,43,.22);
}
.stat-card:hover{transform:translateY(-2px);box-shadow:inset 4px 0 0 var(--accent-a),0 14px 32px rgba(7,14,43,.28)}
.stat-top{display:contents}
.stat-accent-bar{display:none}
.stat-ic{grid-area:ic;grid-row:1 / 4;width:52px;height:52px;border-radius:14px;font-size:22px;align-self:center}
.stat-val{grid-area:val;font-size:2.1rem;line-height:1.05}
.stat-label{grid-area:label;font-size:.86rem;font-weight:700;color:var(--tx);margin:2px 0 0}
.stat-sub{grid-area:sub;font-size:.76rem;color:#6b7280}

/* Toolbar: quieter semester tag, clear grouping */
.toolbar{padding:12px 16px;border-radius:14px}
.sem-badge{background:var(--surface-2);border-color:var(--border);color:var(--tx-2);font-weight:600}
.view-switcher{margin-left:4px}
.tb-action-btn{margin-left:4px}

/* Week columns: one clean header, no duplicated day label */
.day-col-header{flex-direction:column;align-items:flex-start;gap:3px;padding:14px 16px;flex-wrap:nowrap}
.day-col .day-abbr{display:none}
.day-col .day-col-header > div:not(.day-hrs){display:block}
.day-hrs{margin-left:0;font-size:.78rem;color:var(--tx-3);font-weight:600}
.day-hrs:empty{display:none}
.day-hrs i{font-size:12px}
.day-classes{padding:10px;gap:10px}
.no-class{padding:28px 10px;font-size:.84rem;color:var(--tx-3)}
.no-class i{font-size:22px}

/* Class cards: distinct, calm tiles */
.cls-card{
  border:1px solid var(--border);border-radius:12px;
  padding:14px 14px 12px 18px;
  box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.cls-card:last-child{border-bottom:1px solid var(--border)}
.cls-card:hover{box-shadow:0 6px 16px rgba(15,23,42,.08);transform:translateY(-1px)}
.cls-card::before{top:10px;bottom:10px;left:6px;width:4px}
.cls-timebar{margin-bottom:6px}
.cls-time{font-size:.82rem}
.cls-name{font-size:1rem;margin-bottom:8px}
.cls-tags{align-items:center;gap:0;margin-bottom:10px}
.cls-code{font-size:.72rem;padding:2px 8px}
.cls-tags .block-badge{background:none;border:0;padding:0;color:var(--tx-2);font-size:.8rem;font-weight:600}
.cls-tags .block-badge::before{content:'\2022';margin:0 8px;color:var(--tx-4)}
.cls-meta{border-top:1px solid var(--border);padding-top:10px;margin-top:0;justify-content:space-between;flex-wrap:nowrap}
.cls-students{margin-left:0}
.cls-room{min-width:0}

/* Cancelled: dim and badge, no heavy strike-through */
.cls-card[data-status="cancelled"]{background:#fafafa;opacity:.82}
.cls-card[data-status="cancelled"] .cls-time{text-decoration:none;color:var(--tx-3)}
.cls-card[data-status="cancelled"] .cls-name{text-decoration:none;color:var(--tx-2)}

/* ══════════════════════════════════════
   SCHEDULE LAYOUT v3  (matches the reference design)
══════════════════════════════════════ */
.content{padding:28px 32px 56px;gap:18px;max-width:1520px;width:100%;margin-inline:auto}

/* Header */
.sch-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}
.sch-head h1{font-family:var(--ff-head);font-size:1.8rem;font-weight:800;letter-spacing:-.03em;line-height:1.15;color:var(--tx)}
.sch-head p{margin-top:5px;font-size:.92rem;color:var(--tx-3)}
.sch-head-actions{display:flex;gap:10px;flex-wrap:wrap}
.sch-btn{display:inline-flex;align-items:center;gap:7px;min-height:40px;padding:0 16px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--tx);font:600 .86rem var(--ff);cursor:pointer;text-decoration:none;transition:background-color .15s,border-color .15s}
.sch-btn:hover{background:var(--surface-2);border-color:var(--border-2)}
.sch-btn i{font-size:16px}
.sch-btn-primary{background:var(--navy);border-color:var(--navy);color:#fff}
.sch-btn-primary:hover{background:var(--navy-mid);border-color:var(--navy-mid)}

/* Next class hero */
.next-class{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr) auto;overflow:hidden;border-radius:18px;background:linear-gradient(120deg,#0b1640 0%,#15266f 100%);color:#fff;box-shadow:0 12px 32px rgba(11,22,64,.2)}
.next-class::after{content:'';position:absolute;right:-70px;top:-70px;width:280px;height:280px;border-radius:50%;border:36px solid rgba(255,255,255,.04);pointer-events:none}
.nc-date{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;min-width:160px;padding:22px 26px;background:var(--yellow);color:var(--navy);text-align:center}
.nc-day{font-size:.84rem;font-weight:700}
.nc-time{font-family:var(--ff-head);font-size:2.6rem;font-weight:800;letter-spacing:-.04em;line-height:1}
.nc-range{font-size:.76rem;font-weight:700;opacity:.82}
.nc-body{display:flex;flex-direction:column;justify-content:center;gap:10px;min-width:0;padding:22px 28px}
.nc-top{display:flex;align-items:center;gap:10px}
.nc-label{font-size:.84rem;font-weight:600;color:rgba(255,255,255,.74)}
.nc-pill{padding:3px 11px;border-radius:999px;background:rgba(255,255,255,.15);font-size:.76rem;font-weight:700}
.nc-title{font-family:var(--ff-head);font-size:1.65rem;font-weight:800;letter-spacing:-.02em;line-height:1.2}
.nc-chips{display:flex;flex-wrap:wrap;gap:8px}
.nc-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid rgba(255,255,255,.22);border-radius:9px;background:rgba(255,255,255,.07);font-size:.82rem;font-weight:600}
.nc-chip i{font-size:15px;opacity:.85}
.nc-actions{position:relative;z-index:1;display:flex;flex-direction:column;justify-content:center;gap:10px;min-width:220px;padding:22px 28px}
.nc-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 18px;border:1px solid rgba(255,255,255,.32);border-radius:10px;background:transparent;color:#fff;font:700 .86rem var(--ff);cursor:pointer;text-decoration:none;transition:background-color .15s}
.nc-btn:hover{background:rgba(255,255,255,.12)}
.nc-btn-primary{background:#fff;border-color:#fff;color:var(--navy)}
.nc-btn-primary:hover{background:#eef1fb}

/* KPI row */
.kpi-row{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
.kpi{display:flex;align-items:center;gap:14px;padding:16px 18px;border:1px solid var(--border);border-radius:14px;background:var(--surface);box-shadow:var(--shadow-sm)}
.kpi-ic{display:flex;align-items:center;justify-content:center;flex:0 0 46px;width:46px;height:46px;border-radius:12px;font-size:21px}
.kpi.blue .kpi-ic{background:var(--blue-lt);color:var(--blue)}
.kpi.violet .kpi-ic{background:var(--violet-lt);color:var(--violet)}
.kpi.emerald .kpi-ic{background:var(--emerald-lt);color:var(--emerald)}
.kpi.amber .kpi-ic{background:var(--amber-lt);color:var(--amber)}
.kpi-val{font-family:var(--ff-head);font-size:1.7rem;font-weight:800;letter-spacing:-.03em;line-height:1.05;color:var(--tx)}
.kpi-lab{margin-top:3px;font-size:.8rem;font-weight:600;line-height:1.25;color:var(--tx-3)}

/* Conflict alert */
.conflict-alert{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border:1px solid var(--red-bdr);border-radius:12px;background:var(--red-lt);color:#b42318;font-size:.88rem;font-weight:600;line-height:1.45}
.conflict-alert i{font-size:18px;margin-top:1px}

/* Filter bar */
.filter-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.filter-bar .search-box{flex:1 1 320px;min-height:44px;padding:0 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
.filter-bar .search-box input{font-size:.9rem}
.filter-bar .view-switcher{margin:0;padding:3px;border:1px solid var(--border);border-radius:10px;background:var(--surface-2)}
.filter-bar .vs-btn{min-height:36px;padding:0 16px;font-size:.86rem;color:var(--tx-2)}
.filter-bar .vs-btn.active{border:1px solid var(--border);background:var(--surface);color:var(--tx);box-shadow:0 1px 3px rgba(10,17,40,.1)}

/* Week grid: widths come from --cols so busy days get more room */
.sched-grid{grid-template-columns:var(--cols,repeat(5,minmax(0,1fr)));gap:14px}
.day-col{border-radius:14px;box-shadow:none}
.day-col-header{flex-direction:row;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:16px 16px 12px;border-bottom:0;background:transparent}
.day-head-text{min-width:0}
.day-name{font-family:var(--ff-head);font-size:1rem;font-weight:800;color:var(--tx)}
.day-hrs{margin:3px 0 0;font-size:.8rem;font-weight:500;color:var(--tx-3)}
.today-pill{padding:3px 11px;border-radius:999px;background:var(--blue);color:#fff;font:700 .72rem var(--ff)}
.today-col .day-name::after{content:none !important}
.day-col.today-col{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.day-col.today-col .day-col-header{background:transparent}
.day-classes{padding:0 12px 14px;gap:10px}
.no-class{padding:22px 8px;font-size:.88rem;color:var(--tx-2)}
.no-class i{display:none}

/* Class cards */
.cls-card{padding:12px 14px;border:1px solid var(--border);border-left:4px solid var(--card-c);border-radius:10px;background:var(--surface);box-shadow:none}
.cls-card::before{display:none}
.cls-card:last-child{border-bottom:1px solid var(--border)}
.cls-card:hover{background:var(--surface-2);box-shadow:none;transform:none}
.cls-card.has-conflict{border:1.5px solid var(--red)}
.cls-timebar{margin-bottom:6px}
.cls-time{font-size:.8rem}
.cls-name{margin:0 0 3px;font-size:.98rem;font-weight:700}
.cls-sub{margin-bottom:10px;font-size:.76rem;color:var(--tx-3)}
.cls-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:0;border-top:0;font-size:.8rem}
.cls-card[data-status="cancelled"]{background:var(--surface-2)}

/* Responsive */
@media (max-width:1180px){
  .kpi-row{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
  .next-class{grid-template-columns:1fr}
  .nc-date{flex-direction:row;justify-content:flex-start;gap:14px;padding:14px 22px}
  .nc-actions{flex-direction:row;flex-wrap:wrap;min-width:0;padding:0 22px 22px}
  .sched-grid{grid-template-columns:repeat(auto-fit,minmax(230px,1fr))}
}
@media (max-width:640px){
  .content{padding:20px 14px 40px}
  .sch-head-actions{width:100%}
  .sch-btn{flex:1;justify-content:center}
  .filter-bar .view-switcher{width:100%}
  .filter-bar .vs-btn{flex:1;justify-content:center}
  .nc-title{font-size:1.3rem}
}

/* Dark mode (toggle in the header, remembered in localStorage) */
html[data-theme="dark"]{
  color-scheme:dark;
  --bg:#0e1322;--surface:#161c30;--surface-2:#1c2340;--border:#273055;--border-2:#34406b;
  --tx:#e9edf9;--tx-2:#b9c1de;--tx-3:#8c96bb;--tx-4:#6a7498;
  --blue-lt:#1a2650;--blue-bdr:#2b3f86;--blue-dk:#8fb0ff;
  --violet-lt:#261d4a;--violet-bdr:#41318a;
  --emerald-lt:#0f2a26;--emerald-bdr:#14523f;
  --amber-lt:#2e2410;--amber-bdr:#6b4e14;
  --red-lt:#2d1517;--red-bdr:#6b2328;
}
html[data-theme="dark"] .sch-btn-primary{background:var(--blue);border-color:var(--blue)}
html[data-theme="dark"] .conflict-alert{color:#fca5a5}
html[data-theme="dark"] .cls-card.has-conflict{background:var(--red-lt)}
html[data-theme="dark"] .nc-btn-primary{color:#0b1640}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.cls-card, .tl-item').forEach(function (el) {
    el.tabIndex = 0;
    el.setAttribute('role', 'button');
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
    });
  });
});
</script>
<script>
(function () {
  try { if (localStorage.getItem('schedTheme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) {}
})();
document.addEventListener('DOMContentLoaded', function () {
  var root = document.documentElement;
  var btn = document.getElementById('themeToggle');
  function sync() {
    if (!btn) return;
    var dark = root.getAttribute('data-theme') === 'dark';
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    var label = btn.querySelector('span'); if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
    var icon = btn.querySelector('i'); if (icon) icon.className = 'ti ' + (dark ? 'ti-sun' : 'ti-moon');
  }
  if (btn) {
    btn.addEventListener('click', function () {
      var dark = root.getAttribute('data-theme') === 'dark';
      if (dark) root.removeAttribute('data-theme'); else root.setAttribute('data-theme', 'dark');
      try { localStorage.setItem('schedTheme', dark ? 'light' : 'dark'); } catch (e) {}
      sync();
    });
    sync();
  }
  document.querySelectorAll('[data-open-class]').forEach(function (b) {
    b.addEventListener('click', function () {
      var card = document.querySelector('.cls-card[data-id="' + b.getAttribute('data-open-class') + '"]');
      if (card) card.click();
    });
  });
});
</script>
@include('partials.pro-motion')
</head>
<body>

<!-- ═══════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════ -->
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
        RFID
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

<!-- ═══════════ MAIN ═══════════ -->
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

<?php
// ── Derived data for the redesigned header (all computed from the schedule already loaded above) ──
$parseMin = function (?string $iso): ?int {
  if (!$iso) return null;
  $c = \Carbon\Carbon::parse($iso);
  return (int) ($c->hour * 60 + $c->minute);
};
$fmtMin = fn (int $m): string => \Carbon\Carbon::today()->addMinutes($m)->format('g:i A');

// Overlapping (non-cancelled) classes on the same weekday
$conflictIds = [];
$conflictNotes = [];
foreach ($schedule as $dayRow) {
  $list = array_values(array_filter($dayRow['classes'], fn ($c) => ($c['status'] ?? '') !== 'cancelled' && !empty($c['end_at'])));
  for ($i = 0; $i < count($list); $i++) {
    for ($j = $i + 1; $j < count($list); $j++) {
      $aS = $parseMin($list[$i]['start_at']); $aE = $parseMin($list[$i]['end_at']);
      $bS = $parseMin($list[$j]['start_at']); $bE = $parseMin($list[$j]['end_at']);
      if ($aS !== null && $bS !== null && $aE !== null && $bE !== null && $aS < $bE && $bS < $aE) {
        $conflictIds[] = $list[$i]['id'];
        $conflictIds[] = $list[$j]['id'];
        $fromTxt = $fmtMin(max($aS, $bS));
        $toTxt = $fmtMin(min($aE, $bE));
        if (substr($fromTxt, -2) === substr($toTxt, -2)) { $fromTxt = substr($fromTxt, 0, -3); }
        $conflictNotes[] = ['day' => $dayRow['day'], 'from' => $fromTxt, 'to' => $toTxt];
      }
    }
  }
}

// Next upcoming class (skips cancelled ones)
$nowTs = now();
$upcoming = collect($calendarSchedule)
  ->filter(fn ($c) => ($c['status'] ?? '') !== 'cancelled' && !empty($c['start_at']) && \Carbon\Carbon::parse($c['start_at'])->gt($nowTs))
  ->sortBy('sort_at')
  ->first();
$nextCard = null;
$nextIn = '';
if ($upcoming) {
  $uStart = \Carbon\Carbon::parse($upcoming['start_at']);
  $uEnd = !empty($upcoming['end_at']) ? \Carbon\Carbon::parse($upcoming['end_at']) : null;
  $dayIdx = $uStart->dayOfWeekIso - 1;
  foreach (($schedule[$dayIdx]['classes'] ?? []) as $c) {
    if ($c['code'] === $upcoming['code'] && \Carbon\Carbon::parse($c['start_at'])->format('H:i') === $uStart->format('H:i')) { $nextCard = $c; break; }
  }
  $daysAway = (int) round(abs($nowTs->copy()->startOfDay()->diffInDays($uStart->copy()->startOfDay())));
  if ($daysAway === 0) {
    $mins = (int) floor(abs($nowTs->diffInMinutes($uStart)));
    $hrsAway = (int) round($mins / 60);
    $nextIn = $mins < 60 ? 'In ' . max(1, $mins) . ' min' : 'In ' . $hrsAway . ' hr' . ($hrsAway === 1 ? '' : 's');
  } elseif ($daysAway === 1) {
    $nextIn = 'Tomorrow';
  } else {
    $nextIn = 'In ' . $daysAway . ' days';
  }
}

// Weekly teaching time
$weekHours = array_sum(array_column($byDay, 'total_minutes')) / 60;
$weekHoursLabel = $weekHours > 0
  ? (fmod($weekHours, 1.0) === 0.0 ? (string) ((int) $weekHours) : number_format($weekHours, 1)) . ' hrs'
  : '0 hrs';

$kpis = [
  ['v' => (string) $classesPerWeek,              'l' => 'Classes per week',     'i' => 'ti-calendar-week', 'c' => 'blue'],
  ['v' => (string) $totalUniqueStudents,         'l' => 'Students enrolled',    'i' => 'ti-users',         'c' => 'violet'],
  ['v' => (string) count($facultyCourses),       'l' => 'Subjects',             'i' => 'ti-books',         'c' => 'emerald'],
  ['v' => $weekHoursLabel,                       'l' => 'Teaching time / week', 'i' => 'ti-clock',         'c' => 'amber'],
  ['v' => (string) (count($facultyCourses) * 3), 'l' => 'Total units',          'i' => 'ti-bolt',          'c' => 'blue'],
];

// Day column widths: busy days get more room, free days stay narrow
$gridCols = implode(' ', array_map(function ($d) {
  if (!empty($d['classes'])) return 'minmax(0,1.6fr)';
  return (($d['abbr'] ?? '') === now()->format('D')) ? 'minmax(96px,.9fr)' : 'minmax(96px,.65fr)';
}, $schedule));
?>
    <div class="sch-head">
      <div class="sch-head-text">
        <h1>Semester Schedule</h1>
        <p><?= htmlspecialchars($facultyName) ?> &bull; <?= htmlspecialchars($semester) ?></p>
      </div>
      <div class="sch-head-actions">
        <button type="button" class="sch-btn" id="themeToggle" aria-pressed="false"><i class="ti ti-moon" aria-hidden="true"></i><span>Dark mode</span></button>
        <a class="sch-btn" href="{{ route('faculty.schedule.export.ics') }}"><i class="ti ti-file-export" aria-hidden="true"></i>Export</a>
        <button type="button" class="sch-btn sch-btn-primary" id="openAddModal2"><i class="ti ti-plus" aria-hidden="true"></i>Add schedule</button>
      </div>
    </div>

    <?php if ($upcoming): ?>
    <section class="next-class" aria-label="Next class">
      <div class="nc-date">
        <span class="nc-day"><?= htmlspecialchars($uStart->format('l')) ?></span>
        <span class="nc-time"><?= htmlspecialchars($uStart->format('g:i')) ?></span>
        <span class="nc-range"><?= htmlspecialchars($uStart->format('A')) ?><?= $uEnd ? ' - ' . htmlspecialchars($uEnd->format('g:i A')) : '' ?></span>
      </div>
      <div class="nc-body">
        <div class="nc-top"><span class="nc-label">Next class</span><span class="nc-pill"><?= htmlspecialchars($nextIn) ?></span></div>
        <h2 class="nc-title"><?= htmlspecialchars($upcoming['subject']) ?></h2>
        <div class="nc-chips">
          <span class="nc-chip"><i class="ti ti-book-2" aria-hidden="true"></i><?= htmlspecialchars($upcoming['code']) ?></span>
          <span class="nc-chip"><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($upcoming['location']) ?></span>
          <?php if ($upcoming['section'] !== ''): ?><span class="nc-chip"><i class="ti ti-layout-grid" aria-hidden="true"></i><?= htmlspecialchars($upcoming['section']) ?></span><?php endif ?>
          <span class="nc-chip"><i class="ti ti-users" aria-hidden="true"></i><?= (int) $upcoming['students'] ?> students</span>
        </div>
      </div>
      <div class="nc-actions">
        <?php if ($nextCard): ?><button type="button" class="nc-btn nc-btn-primary" data-open-class="<?= (int) $nextCard['id'] ?>">View class</button><?php endif ?>
        <a class="nc-btn" href="{{ route('faculty.attendance') }}">Open attendance</a>
      </div>
    </section>
    <?php endif ?>

    <div class="kpi-row">
      <?php foreach ($kpis as $k): ?>
      <div class="kpi <?= htmlspecialchars($k['c']) ?>">
        <div class="kpi-ic"><i class="ti <?= htmlspecialchars($k['i']) ?>" aria-hidden="true"></i></div>
        <div>
          <div class="kpi-val"><?= htmlspecialchars($k['v']) ?></div>
          <div class="kpi-lab"><?= htmlspecialchars($k['l']) ?></div>
        </div>
      </div>
      <?php endforeach ?>
    </div>

    <?php if (!empty($conflictNotes)): $cn = $conflictNotes[0]; ?>
    <div class="conflict-alert" role="alert">
      <i class="ti ti-alert-triangle" aria-hidden="true"></i>
      <span>Schedule conflict on <?= htmlspecialchars($cn['day']) ?>: two classes overlap from <?= htmlspecialchars($cn['from']) ?> to <?= htmlspecialchars($cn['to']) ?>.<?= count($conflictNotes) > 1 ? ' (+' . (count($conflictNotes) - 1) . ' more)' : '' ?> Move one of them to avoid double booking.</span>
    </div>
    <?php endif ?>

    <div class="filter-bar">
      <label class="search-box">
        <i class="ti ti-search" aria-hidden="true"></i>
        <input type="text" id="classFilter" placeholder="Filter by subject, room or block" aria-label="Filter classes by subject, room or block">
      </label>
      <div class="view-switcher" role="group" aria-label="Schedule view">
        <button class="vs-btn active" id="btnGrid" type="button">Grid</button>
        <button class="vs-btn" id="btnCal" type="button">Calendar</button>
        <button class="vs-btn" id="btnTl" type="button">Timeline</button>
      </div>
    </div>


    <!-- ══════ CALENDAR VIEW ══════ -->
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
          <button aria-label="Previous month" type="button" class="cal-nav-btn" id="calPrev"><i class="ti ti-chevron-left"></i></button>
          <div class="cal-month-label" id="calMonthLabel"></div>
          <button aria-label="Next month" type="button" class="cal-nav-btn" id="calNext"><i class="ti ti-chevron-right"></i></button>
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

    <!-- ══════ SCHEDULE GRID VIEW ══════ -->
    <div class="sched-grid" id="gridView" style="--cols: <?= $gridCols ?>">
      <?php foreach ($schedule as $di => $day):
        $delay = round(.14 + $di * .05, 2);
      ?>
      <div class="day-col<?= ($day['abbr'] ?? '') === now()->format('D') ? ' today-col' : '' ?>" style="--d:<?= $delay ?>s">
        <div class="day-col-header">
          <div class="day-head-text">
            <div class="day-name"><?= htmlspecialchars($day['day']) ?></div>
            <div class="day-hrs"><?php $cc = count($day['classes']); ?><?= $cc > 0 ? $cc . ' ' . ($cc === 1 ? 'class' : 'classes') . ' &bull; ' . htmlspecialchars($day['total_hrs']) : 'Free day' ?></div>
          </div>
          <?php if (($day['abbr'] ?? '') === now()->format('D')): ?><span class="today-pill">Today</span><?php endif ?>
        </div>
        <div class="day-classes">
          <?php if (empty($day['classes'])): ?>
            <div class="no-class">
              <i class="ti ti-calendar-off"></i>
              No classes
            </div>
          <?php else: ?>
            <?php foreach ($day['classes'] as $cls): ?>
            <div class="cls-card <?= htmlspecialchars($cls['color']) ?><?= in_array($cls['id'], $conflictIds, true) ? ' has-conflict' : '' ?>"
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
              <div class="cls-timebar">
                <span class="cls-time"><i class="ti ti-clock"></i><?= htmlspecialchars($cls['duration']) ?></span>
                <?php if (!empty($cls['status']) && $cls['status'] === 'cancelled'): ?>
                  <span class="status-badge status-cancelled"><i class="ti ti-x" style="font-size:11px"></i> Cancelled</span>
                <?php elseif (!empty($cls['status']) && $cls['status'] === 'ongoing'): ?>
                  <span class="status-badge status-ongoing"><i class="live-dot"></i> Live now</span>
                <?php elseif (in_array($cls['id'], $conflictIds, true)): ?>
                  <span class="status-badge status-cancelled"><i class="ti ti-alert-triangle" style="font-size:11px"></i> Conflict</span>
                <?php endif ?>
              </div>
              <div class="cls-name"><?= htmlspecialchars($cls['subject']) ?></div>
              <div class="cls-sub"><?= htmlspecialchars($cls['code']) ?><?= $cls['section'] !== '' ? ' &bull; ' . htmlspecialchars($cls['section']) : '' ?></div>
              <div class="cls-meta">
                <span class="cls-room"><i class="ti ti-map-pin"></i><?= htmlspecialchars($cls['location']) ?></span>
                <span class="cls-students" title="Enrolled students"><i class="ti ti-users"></i><span><?= htmlspecialchars($cls['students']) ?></span></span>
              </div>
            </div>
            <?php endforeach ?>
          <?php endif ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>

    <!-- ══════ TIMELINE VIEW ══════ -->
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
                  <i class="ti ti-map-pin" style="font-size:11px"></i>
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

<!-- ═══════════ ADD SCHEDULE MODAL ═══════════ -->
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
  $offeringRoomAssignments = collect($facultyOfferings ?? [])->mapWithKeys(function ($offering) use ($facultySchedules) {
    $roomId = $offering->classroom_id ?: collect($facultySchedules ?? [])
      ->where('course_offering_id', $offering->id)
      ->sortByDesc('start_at')
      ->first()?->classroom_id;

    return [(int) $offering->id => (int) ($roomId ?? 0)];
  });
@endphp

<div class="modal-overlay" id="addModal" aria-hidden="true">
  <div class="modal add-modal" role="dialog" aria-labelledby="addModalTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="addModalTitle">Add Schedule For My Subject</div>
        <div class="modal-sub">Only subjects assigned to your account can be scheduled.</div>
      </div>
      <button aria-label="Close dialog" class="modal-close" id="closeAddModal" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      @if($facultyOfferings->isEmpty())
        <div class="alert alert-warning mb-0">
          No subject offerings are assigned to your account yet, so schedule creation is unavailable.
        </div>
      @else
      <form method="POST" action="{{ route('faculty.schedule.store') }}" class="form-grid">
        @csrf
        <div class="fg full">
          <label class="flabel" for="facultyOfferingSelect">Subject Offering</label>
          <select id="facultyOfferingSelect" name="course_offering_id" class="fselect" required>
            <option value="">Select an assigned subject offering…</option>
            @foreach($facultyOfferings as $offering)
              <option
                value="{{ $offering->id }}"
                data-room-id="{{ $offeringRoomAssignments->get($offering->id, 0) }}"
                data-term-start="{{ $offering->term_start->toDateString() }}"
                data-term-end="{{ $offering->term_end->toDateString() }}"
                data-block-section="{{ $offering->block_section }}"
                {{ (string) old('course_offering_id') === (string) $offering->id ? 'selected' : '' }}
              >
                {{ $offering->course?->code }} – {{ $offering->course?->title }} | {{ $offering->block_section }} | {{ $offering->term_start->format('M j, Y') }} to {{ $offering->term_end->format('M j, Y') }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="fg">
          <label class="flabel">Section</label>
          <div class="finput" id="facultyOfferingSection">Select an offering</div>
        </div>
        <div class="fg">
          <label class="flabel">Semester Start</label>
          <input aria-label="Semester start" type="date" name="semester_start" class="finput" value="{{ old('semester_start') }}" readonly required>
        </div>
        <div class="fg">
          <label class="flabel">Semester End</label>
          <input aria-label="Semester end" type="date" name="semester_end" class="finput" value="{{ old('semester_end') }}" readonly required>
        </div>

        <div class="form-row">
          <div class="fg"><label class="flabel">Day 1</label>
            <select aria-label="Day 1" name="day1" class="fselect">
              <option value="">Select day…</option>
              <option value="1" {{ old('day1')=='1'?'selected':'' }}>Monday</option>
              <option value="2" {{ old('day1')=='2'?'selected':'' }}>Tuesday</option>
              <option value="3" {{ old('day1')=='3'?'selected':'' }}>Wednesday</option>
              <option value="4" {{ old('day1')=='4'?'selected':'' }}>Thursday</option>
              <option value="5" {{ old('day1')=='5'?'selected':'' }}>Friday</option>
            </select>
          </div>
          <div class="fg"><label class="flabel">Day 1 Start</label>
            <input aria-label="Day 1 start" type="time" name="day1_start" class="finput" value="{{ old('day1_start') }}">
          </div>
          <div class="fg"><label class="flabel">Day 1 End</label>
            <input aria-label="Day 1 end" type="time" name="day1_end" class="finput" value="{{ old('day1_end') }}">
          </div>
        </div>

        <div class="form-row">
          <div class="fg"><label class="flabel">Day 2</label>
            <select aria-label="Day 2" name="day2" class="fselect">
              <option value="">Select day…</option>
              <option value="1" {{ old('day2')=='1'?'selected':'' }}>Monday</option>
              <option value="2" {{ old('day2')=='2'?'selected':'' }}>Tuesday</option>
              <option value="3" {{ old('day2')=='3'?'selected':'' }}>Wednesday</option>
              <option value="4" {{ old('day2')=='4'?'selected':'' }}>Thursday</option>
              <option value="5" {{ old('day2')=='5'?'selected':'' }}>Friday</option>
            </select>
          </div>
          <div class="fg"><label class="flabel">Day 2 Start</label>
            <input aria-label="Day 2 start" type="time" name="day2_start" class="finput" value="{{ old('day2_start') }}">
          </div>
          <div class="fg"><label class="flabel">Day 2 End</label>
            <input aria-label="Day 2 end" type="time" name="day2_end" class="finput" value="{{ old('day2_end') }}">
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Expected Enrolled</label>
          <input aria-label="Enrolled" type="number" min="0" name="enrolled" class="finput" value="{{ old('enrolled', 0) }}">
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

<!-- ═══════════ DAY CLASSES MODAL ═══════════ -->
<div class="modal-overlay" id="dayModal" aria-hidden="true">
  <div class="modal day-modal" role="dialog" aria-labelledby="dayModalTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="dayModalTitle">Classes on —</div>
        <div class="modal-sub" id="dayModalSub"></div>
      </div>
      <button aria-label="Close dialog" class="modal-close" id="closeDayModal" type="button"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="day-modal-date" id="dayModalDate"></div>
      <div class="day-cls-list" id="dayClsList"></div>
    </div>
  </div>
</div>

<!-- ═══════════ SESSION MODAL ═══════════ -->
<div class="modal-overlay" id="sessionModal" aria-hidden="true">
  <div class="modal session-modal" role="dialog" aria-labelledby="sessionModalTitle">
    <div class="modal-head">
      <div>
        <div class="modal-title" id="sessionModalTitle">Class Session</div>
        <div class="modal-sub" id="sessionModalSub"></div>
      </div>
      <button aria-label="Close dialog" class="modal-close" id="closeSessionModal" type="button"><i class="ti ti-x"></i></button>
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
// ── Schedule data ─────────────────────────────────────────────
const schedule = JSON.parse(document.getElementById('scheduleData').textContent || '[]');
const classrooms = JSON.parse(document.getElementById('classroomData').textContent || '[]');

// ── Endpoints ─────────────────────────────────────────────────
const availEndpoint = "{{ url('/api/v1/room-availability/check') }}";
const roomStatusEndpoint = "{{ url('/api/v1/room-statuses') }}";
const cancelEndpoint = "{{ route('faculty.schedule.cancel', ['schedule' => '__ID__']) }}";
const cancellationReason = document.getElementById('cancellationReason');

// ── Helpers ───────────────────────────────────────────────────
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

// ── Modals ────────────────────────────────────────────────────
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

// ── Add Schedule Modal ─────────────────────────────────────────
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
  const offeringOption = form.querySelector('[name=course_offering_id] option:checked');
  const roomId = offeringOption?.dataset.roomId || '';
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

  availList.innerHTML = '<div style="font-size:.72rem;color:var(--tx-3)">Checking…</div>';

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
    items.push({ label: cfg.label, dateLabel: `${dk} ${cfg.start}–${cfg.end}`, status, reason: av.reason });

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
        <div class="avail-meta">${i.dateLabel}${i.reason?` · ${i.reason}`:''}</div>
      </div>
      <span class="avail-status is-${i.status}">${i.status}</span>
    </div>`).join('') || '<div style="font-size:.72rem;color:var(--tx-4)">No data.</div>';

  suggestList.innerHTML = timeSugg.map(s => `
    <div class="suggest-item">
      <div>
        <div style="font-size:.77rem;font-weight:600;color:var(--tx)">${s.dayKey} · ${s.start}–${s.end}</div>
        <div style="font-size:.68rem;color:var(--tx-3)">Suggested slot on ${s.dk}</div>
      </div>
      <button type="button" class="suggest-btn" data-day="${s.dayKey}" data-start="${s.start}" data-end="${s.end}">Apply</button>
    </div>`).join('') || '<div style="font-size:.72rem;color:var(--tx-4)">No time suggestions.</div>';

}

document.getElementById('facultyOfferingSelect')?.addEventListener('change', event => {
  const option = event.currentTarget.options[event.currentTarget.selectedIndex];
  const form = event.currentTarget.form;
  if (!form) return;

  form.querySelector('[name=semester_start]').value = option?.dataset.termStart || '';
  form.querySelector('[name=semester_end]').value = option?.dataset.termEnd || '';
  document.getElementById('facultyOfferingSection').textContent = option?.dataset.blockSection || 'Select an offering';
  queueAvailCheck();
});

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
  if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }
  const subject = form.querySelector('[name=course_offering_id] option:checked')?.textContent?.trim() || 'your subject';
  const semesterStart = form.querySelector('[name=semester_start]')?.value || 'the selected start date';
  const semesterEnd = form.querySelector('[name=semester_end]')?.value || 'the selected end date';
  const day1 = form.querySelector('[name=day1] option:checked')?.textContent?.trim();
  const day1Start = form.querySelector('[name=day1_start]')?.value;
  const day1End = form.querySelector('[name=day1_end]')?.value;
  const day2 = form.querySelector('[name=day2] option:checked')?.textContent?.trim();
  const day2Start = form.querySelector('[name=day2_start]')?.value;
  const day2End = form.querySelector('[name=day2_end]')?.value;
  const sessions = [
    day1 && day1Start && day1End ? `${day1} ${day1Start}–${day1End}` : null,
    day2 && day2Start && day2End ? `${day2} ${day2Start}–${day2End}` : null,
  ].filter(Boolean).join(', ');

  const message = [
    'Review this semester schedule before saving:',
    `Subject: ${subject}`,
    `Semester: ${semesterStart} to ${semesterEnd}`,
    `Sessions: ${sessions || 'not fully selected'}`,
    '',
    'This will create all sessions for the semester. Continue only if these details are correct.',
  ].join('\n');

  e.preventDefault();
  const submitter = e.submitter;
  SmartUI.confirm(message, { title: 'Save semester schedule?', confirmText: 'Save schedule' }).then(ok => {
    if (!ok) return;
    form.dataset.confirmed = '1';
    if (form.requestSubmit) { form.requestSubmit(submitter || undefined); } else { form.submit(); }
  });
});
// ── View Switcher ─────────────────────────────────────────────
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

// ── Class filter ─────────────────────────────────────────────
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

// ── Calendar ──────────────────────────────────────────────────
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

// ── Day Classes Modal ─────────────────────────────────────────
function openDayModal(dateKey, classes) {
  const d = new Date(dateKey + 'T12:00:00');
  const dayName = d.toLocaleDateString('en-US', {weekday:'long', month:'long', day:'numeric', year:'numeric'});
  document.getElementById('dayModalTitle').textContent = 'Classes on ' + d.toLocaleDateString('en-US',{weekday:'long'});
  document.getElementById('dayModalSub').textContent = d.toLocaleDateString('en-US',{month:'long', day:'numeric', year:'numeric'});
  document.getElementById('dayModalDate').innerHTML = `<i class="ti ti-calendar"></i> ${dayName} · ${classes.length} class${classes.length>1?'es':''}`;

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

// ── Session Modal ─────────────────────────────────────────────
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
  document.getElementById('sessionModalSub').textContent = `${data.code} · ${data.status.charAt(0).toUpperCase()+data.status.slice(1)}`;
  document.getElementById('sessName').textContent = data.subject;
  document.getElementById('sessCode').textContent = data.code;
  document.getElementById('sessDate').textContent = data.date;
  document.getElementById('sessTime').textContent = data.time;
  document.getElementById('sessLoc').textContent = data.location;
  document.getElementById('sessBlock').textContent = data.block;

  const statusEl = document.getElementById('sessStatus');
  statusEl.textContent = '';
  const statusBadge = document.createElement('span');
  statusBadge.className = 'status-badge status-' + String(data.status).replace(/[^a-z0-9_-]/gi, '');
  statusBadge.textContent = data.status;
  statusEl.appendChild(statusBadge);

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
    if (av?.available) sugg.push(`${dk} ${ts}–${te}`);
  }
  el.innerHTML = sugg.length
    ? sugg.map(s=>`<div class="makeup-item"><span class="makeup-item-lbl"><i class="ti ti-calendar-check" style="font-size:12px"></i> ${s}</span><span class="makeup-meta">Available</span></div>`).join('')
    : '<div style="font-size:.73rem;color:var(--tx-4)">No available slots found.</div>';
  el.setAttribute('aria-busy', 'false');
}

document.getElementById('closeSessionModal')?.addEventListener('click', () => closeModal('sessionModal'));
document.getElementById('remindBtn')?.addEventListener('click', () => SmartUI.toast('Reminder queued.', 'success'));
document.getElementById('sessionCancelForm')?.addEventListener('submit', e => {
  const form = e.currentTarget;
  if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }
  if (!cancellationReason?.value.trim()) {
    e.preventDefault();
    cancellationReason?.focus();
    return;
  }
  e.preventDefault();
  const submitter = e.submitter;
  SmartUI.confirm('Cancel this class occurrence only? Other teachers will see the room as free during this time.', { title: 'Cancel this class?', confirmText: 'Cancel class', cancelText: 'Keep class', danger: true }).then(ok => {
    if (!ok) return;
    form.dataset.confirmed = '1';
    if (form.requestSubmit) { form.requestSubmit(submitter || undefined); } else { form.submit(); }
  });
});

document.querySelectorAll('.cls-card, .tl-item').forEach(el => {
  el.addEventListener('click', () => openSessionFromEl(el));
});

// ── Profile dropdown ──────────────────────────────────────────
document.getElementById('profileToggle')?.addEventListener('click', e => {
  e.stopPropagation();
  document.getElementById('profileDropdown')?.classList.toggle('open');
});
document.addEventListener('click', () => document.getElementById('profileDropdown')?.classList.remove('open'));

// ── Escape key ────────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    ['sessionModal','dayModal','addModal'].forEach(closeModal);
  }
});

</script>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>