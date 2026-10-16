@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => false, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => false, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => true, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => false, 'route' => 'student.profile'],
];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Attendance - Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    main.attendance-page { padding: 28px 32px 48px !important; font-family: 'DM Sans', sans-serif; }
    .attendance-page-header { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; padding:8px 0 22px; margin-bottom:24px; border-bottom:1px solid var(--student-border); }
    .attendance-breadcrumb { display:flex; align-items:center; gap:8px; margin-bottom:10px; color:var(--student-muted); font-size:.76rem; font-weight:600; }
    .attendance-breadcrumb a { color:var(--student-muted); text-decoration:none; }
    .attendance-breadcrumb a:hover { color:var(--student-navy); text-decoration:underline; text-underline-offset:3px; }
    .attendance-page-title { margin:0; color:var(--student-text); font-family:'Sora',sans-serif; font-size:1.85rem; font-weight:800; line-height:1.2; }
    .attendance-page-subtitle { margin:6px 0 0; color:var(--student-muted); font-size:.9rem; }
    .attendance-date { display:inline-flex; align-items:center; gap:8px; padding:9px 12px; border:1px solid var(--student-border); border-radius:8px; background:#fff; color:#475569; font-size:.78rem; font-weight:600; white-space:nowrap; }
    .attendance-date i { color:var(--student-blue); }

    .attendance-checkin-panel { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:18px; padding:22px 24px; margin-bottom:26px; border:1px solid #d7dfef; border-radius:12px; background:#fff; box-shadow:0 4px 16px rgba(15,26,60,.045); }
    .attendance-checkin-icon { width:48px; height:48px; display:grid; place-items:center; border-radius:10px; background:#eef2ff; color:var(--student-navy); font-size:1.25rem; }
    .attendance-checkin-copy h2 { margin:0; color:var(--student-text); font-family:'Sora',sans-serif; font-size:1.02rem; font-weight:700; }
    .attendance-checkin-copy p { margin:5px 0 0; color:var(--student-muted); font-size:.83rem; line-height:1.5; }
    .scan-qr-btn { display:inline-flex; align-items:center; justify-content:center; gap:9px; min-height:44px; padding:0 18px; border:1px solid var(--student-navy); border-radius:8px; background:var(--student-navy); color:#fff; font-family:'DM Sans',sans-serif; font-size:.84rem; font-weight:700; white-space:nowrap; cursor:pointer; transition:background .18s ease,border-color .18s ease,transform .18s ease; }
    .scan-qr-btn:hover { border-color:#1a2f80; background:#1a2f80; transform:translateY(-1px); }
    .scan-qr-btn:focus-visible, .scanner-close:focus-visible { outline:3px solid rgba(37,99,235,.38); outline-offset:3px; }
    .scan-qr-btn:disabled { opacity:.65; cursor:wait; transform:none; }

    .attendance-summary { margin-bottom:26px; }
    .attendance-section-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
    .attendance-section-heading h2 { margin:0; color:var(--student-text); font-family:'Plus Jakarta Sans',sans-serif; font-size:1rem; font-weight:700; }
    .attendance-live-label { display:inline-flex; align-items:center; gap:7px; color:#64748b; font-size:.73rem; font-weight:600; }
    .attendance-live-dot { width:7px; height:7px; border-radius:50%; background:#16a36a; }
    .attendance-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .attendance-stat { min-width:0; padding:17px 18px; border:1px solid var(--student-border); border-radius:10px; background:#fff; box-shadow:0 2px 8px rgba(15,23,42,.035); }
    .attendance-stat-label { display:flex; align-items:center; gap:9px; color:#65738a; font-size:.78rem; font-weight:600; }
    .attendance-stat-icon { width:32px; height:32px; display:grid; place-items:center; flex:0 0 32px; border-radius:8px; font-size:.9rem; }
    .attendance-stat[data-tone="present"] .attendance-stat-icon { background:#e8f7ef; color:#137447; }
    .attendance-stat[data-tone="absent"] .attendance-stat-icon { background:#fff0ef; color:#b42318; }
    .attendance-stat[data-tone="rate"] .attendance-stat-icon { background:#fff6df; color:#946200; }
    .attendance-stat-value { margin-top:14px; color:var(--student-text); font-family:'Sora',sans-serif; font-size:1.85rem; font-weight:800; line-height:1; }
    .attendance-stat-note { margin-top:5px; color:#8793a6; font-size:.72rem; }

    .attendance-history { overflow:hidden; border:1px solid var(--student-border); border-radius:11px; background:#fff; box-shadow:0 3px 12px rgba(15,23,42,.04); }
    .attendance-history-header { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:17px 20px; border-bottom:1px solid var(--student-border); }
    .attendance-history-title { display:flex; align-items:center; gap:10px; }
    .attendance-history-icon { width:34px; height:34px; display:grid; place-items:center; border-radius:8px; background:#eef2ff; color:var(--student-navy); }
    .attendance-history-title h2 { margin:0; color:var(--student-text); font-family:'Sora',sans-serif; font-size:.95rem; font-weight:700; }
    .attendance-history-subtitle { margin-top:2px; color:var(--student-muted); font-size:.73rem; }
    .attendance-record-count { padding:5px 9px; border:1px solid var(--student-border); border-radius:6px; background:#f8fafc; color:#64748b; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .attendance-table-wrap { overflow-x:auto; }
    .attendance-records { width:100%; border-collapse:collapse; }
    .attendance-records thead { background:#f8fafc; }
    .attendance-records th { padding:11px 18px; border-bottom:1px solid var(--student-border); color:#718096; font-size:.68rem; font-weight:700; text-align:left; text-transform:uppercase; }
    .attendance-records th:first-child, .attendance-records td:first-child { padding-left:20px; }
    .attendance-records th:last-child, .attendance-records td:last-child { padding-right:20px; }
    .attendance-records td { padding:14px 18px; border-bottom:1px solid #edf0f5; color:#263348; font-size:.82rem; vertical-align:middle; }
    .attendance-records tbody tr:last-child td { border-bottom:0; }
    .attendance-records tbody tr:hover { background:#fafbfe; }
    .attendance-course-cell { font-weight:600; }
    .attendance-date-cell { color:#64748b !important; white-space:nowrap; }
    .attendance-status { display:inline-flex; align-items:center; gap:7px; padding:5px 9px; border:1px solid transparent; border-radius:6px; font-size:.71rem; font-weight:700; white-space:nowrap; }
    .attendance-status::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .attendance-status--present { border-color:#ccebd8; background:#edf8f1; color:#167647; }
    .attendance-status--late { border-color:#f5dfaa; background:#fff7e5; color:#8a5b00; }
    .attendance-status--absent { border-color:#f1d2d0; background:#fff3f2; color:#b42318; }
    .attendance-empty { padding:40px 20px !important; color:var(--student-muted) !important; text-align:center; }
    .attendance-empty i { display:block; margin-bottom:9px; color:#9aa6b8; font-size:1.3rem; }

    .scanner-modal { position:fixed; inset:0; z-index:3000; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(8,18,48,.62); backdrop-filter:blur(3px); }
    .scanner-modal.is-open { display:flex; }
    .scanner-card { width:min(100%,460px); overflow:hidden; border:1px solid #dce3ef; border-radius:12px; background:#fff; box-shadow:0 20px 60px rgba(7,17,45,.28); }
    .scanner-head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--student-border); }
    .scanner-title { color:var(--student-text); font-family:'Sora',sans-serif; font-size:.92rem; font-weight:700; }
    .scanner-close { width:36px; height:36px; display:grid; place-items:center; border:1px solid var(--student-border); border-radius:8px; background:#fff; color:#64748b; cursor:pointer; }
    .scanner-close:hover { background:#f1f5f9; color:var(--student-text); }
    .scanner-body { padding:22px; text-align:center; }
    #qr-reader { width:100%; max-width:360px; margin:0 auto; overflow:hidden; border:1px solid var(--student-border); border-radius:9px; }
    .scanner-help { margin:14px 0 0; color:#64748b; font-size:.8rem; line-height:1.5; }
    .scanner-status { min-height:24px; margin-top:12px; color:var(--student-navy); font-size:.82rem; font-weight:600; }

    @media (max-width:768px) {
      main.attendance-page { padding:20px 16px 32px !important; }
      .attendance-page-header { align-items:flex-start; flex-direction:column; gap:12px; padding-top:4px; }
      .attendance-date { align-self:flex-start; }
      .attendance-checkin-panel { grid-template-columns:auto minmax(0,1fr); gap:12px; padding:17px; }
      .scan-qr-btn { grid-column:1 / -1; width:100%; }
      .attendance-summary-grid { gap:9px; }
      .attendance-stat { padding:13px 12px; }
      .attendance-stat-label { align-items:flex-start; flex-direction:column; gap:7px; font-size:.72rem; }
      .attendance-stat-value { margin-top:10px; font-size:1.55rem; }
      .attendance-stat-note { min-height:2em; font-size:.67rem; }
      .attendance-history-header { padding:14px; }
      .attendance-table-wrap { overflow:visible; }
      .attendance-records, .attendance-records tbody { display:block; }
      .attendance-records thead { position:absolute; width:1px; height:1px; padding:0; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
      .attendance-records tbody { display:grid; gap:9px; padding:10px; }
      .attendance-records tbody tr { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; padding:11px 12px; border:1px solid #e8edf4; border-radius:8px; background:#fff; }
      .attendance-records tbody tr:hover { background:#fff; }
      .attendance-records td, .attendance-records td:first-child, .attendance-records td:last-child { padding:3px 0; border:0; }
      .attendance-records td:first-child { grid-column:1 / -1; color:var(--student-text); font-size:.82rem; }
      .attendance-records td:nth-child(2) { color:#64748b; font-size:.72rem; }
      .attendance-records td:nth-child(3) { justify-self:end; }
      .attendance-records td[data-label]::before { content:attr(data-label) ': '; margin-right:4px; color:#8995a7; font-size:.66rem; font-weight:700; text-transform:uppercase; }
      .attendance-records td:first-child::before { display:block; margin-bottom:3px; }
      .attendance-empty { display:block; padding:28px 12px !important; }
    }

    @media (max-width:360px) {
      .attendance-summary-grid { grid-template-columns:1fr; }
      .attendance-stat { display:grid; grid-template-columns:1fr auto; align-items:center; gap:4px 12px; }
      .attendance-stat-label { grid-row:span 2; }
      .attendance-stat-value { margin:0; text-align:right; }
      .attendance-stat-note { min-height:0; margin:0; text-align:right; }
      .attendance-page-title { font-size:1.55rem; }
    }
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
  <main class="flex-grow-1 attendance-page">
    <header class="attendance-page-header">
      <div>
        <nav class="attendance-breadcrumb" aria-label="Breadcrumb">
          <a href="{{ route('student.home') }}">Home</a>
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <span aria-current="page">Attendance</span>
        </nav>
        <h1 class="attendance-page-title">My attendance</h1>
        <p class="attendance-page-subtitle">Review your class check-ins and attendance history.</p>
      </div>
      <time class="attendance-date" datetime="{{ now()->toDateString() }}">
        <i class="bi bi-calendar3" aria-hidden="true"></i>
        {{ now()->format('D, M j, Y') }}
      </time>
    </header>

    <section class="attendance-checkin-panel" aria-label="Attendance check-in">
      <div class="attendance-checkin-icon" aria-hidden="true"><i class="bi bi-qr-code-scan"></i></div>
      <div class="attendance-checkin-copy">
        <h2>Check in to class</h2>
        <p>Scan the session QR code displayed by your instructor.</p>
      </div>
      <button type="button" class="scan-qr-btn" id="openQrScanner">
        <i class="bi bi-camera" aria-hidden="true"></i>
        Scan QR code
      </button>
    </section>

    <section class="attendance-summary" aria-label="Attendance summary">
      <div class="attendance-section-heading">
        <h2>Attendance summary</h2>
        <span class="attendance-live-label"><span class="attendance-live-dot" aria-hidden="true"></span>Updates automatically</span>
      </div>
      <div class="attendance-summary-grid">
        <div class="attendance-stat" data-tone="present">
          <div class="attendance-stat-label"><span class="attendance-stat-icon"><i class="bi bi-check2" aria-hidden="true"></i></span>Classes attended</div>
          <div class="attendance-stat-value" id="student-attended-count">{{ $totalAttended ?? 0 }}</div>
          <div class="attendance-stat-note">Present and late check-ins</div>
        </div>
        <div class="attendance-stat" data-tone="absent">
          <div class="attendance-stat-label"><span class="attendance-stat-icon"><i class="bi bi-x" aria-hidden="true"></i></span>Classes missed</div>
          <div class="attendance-stat-value" id="student-absent-count">{{ $totalAbsent ?? 0 }}</div>
          <div class="attendance-stat-note">Recorded as absent</div>
        </div>
        <div class="attendance-stat" data-tone="rate">
          <div class="attendance-stat-label"><span class="attendance-stat-icon"><i class="bi bi-percent" aria-hidden="true"></i></span>Attendance rate</div>
          <div class="attendance-stat-value" id="student-attendance-rate">{{ $attendanceRate ?? 0 }}%</div>
          <div class="attendance-stat-note">Based on closed sessions</div>
        </div>
      </div>
    </section>

    <section class="attendance-history" aria-labelledby="attendanceHistoryTitle">
      <div class="attendance-history-header">
        <div class="attendance-history-title">
          <span class="attendance-history-icon" aria-hidden="true"><i class="bi bi-list-check"></i></span>
          <div>
            <h2 id="attendanceHistoryTitle">Attendance history</h2>
            <div class="attendance-history-subtitle">Your recorded class sessions</div>
          </div>
        </div>
        <span class="attendance-record-count">{{ count($attendanceRecords ?? []) }} records</span>
      </div>
      <div class="attendance-table-wrap">
      <table class="attendance-records">
        <thead class="table-light">
          <tr>
            <th scope="col">Course</th>
            <th scope="col">Date</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($attendanceRecords ?? [] as $record)
            <tr>
              <td class="attendance-course-cell" data-label="Course">{{ $record->session?->course?->code ?? 'Course' }}{{ $record->session?->course?->title ? ' - '.$record->session->course->title : '' }}</td>
              <td class="attendance-date-cell" data-label="Date">{{ optional($record->session)->created_at?->format('M d, Y') ?? $record->created_at->format('M d, Y') }}</td>
              <td>
                @php
                  $recordStatus = strtolower((string) $record->status);
                  $isAttended = (bool) $record->present || in_array($recordStatus, ['present', 'late'], true);
                  $statusClass = $recordStatus === 'late' ? 'late' : ($isAttended ? 'present' : 'absent');
                @endphp
                @if ($isAttended)
                  <span class="attendance-status attendance-status--{{ $statusClass }}">
                    {{ $recordStatus === 'late' ? 'Late' : 'Present' }}
                  </span>
                @else
                  <span class="attendance-status attendance-status--{{ $statusClass }}">Absent</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="attendance-empty">
                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                No attendance records yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      </div>
    </section>
  </main>
</div>
<div class="scanner-modal" id="scannerModal" aria-hidden="true">
  <div class="scanner-card" role="dialog" aria-modal="true" aria-labelledby="scannerTitle">
    <div class="scanner-head">
      <div class="scanner-title" id="scannerTitle"><i class="bi bi-qr-code-scan me-2 text-primary"></i>Scan Attendance QR</div>
      <button type="button" class="scanner-close" id="closeQrScanner" aria-label="Close scanner"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="scanner-body">
      <div id="qr-reader"></div>
      <p class="scanner-help">Allow camera access, then point your camera at the instructor's QR code.</p>
      <div class="scanner-status" id="scannerStatus" role="status" aria-live="polite"></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
  const scannerModal = document.getElementById('scannerModal');
  const scannerStatus = document.getElementById('scannerStatus');
  let qrScanner = null;
  let scannerLocked = false;
  let attendanceSummaryLoaded = false;

  function setScannerStatus(message, isError = false) {
    scannerStatus.textContent = message;
    scannerStatus.style.color = isError ? '#dc2626' : 'var(--student-blue)';
  }

  async function stopQrScanner() {
    if (!qrScanner) return;
    try { await qrScanner.stop(); } catch (error) { /* camera may already be stopped */ }
    qrScanner.clear();
    qrScanner = null;
  }

  async function closeQrScanner() {
    await stopQrScanner();
    scannerLocked = false;
    scannerModal.classList.remove('is-open');
    scannerModal.setAttribute('aria-hidden', 'true');
  }

  async function submitScannedCheckIn(decodedText) {
    let url;
    try { url = new URL(decodedText, window.location.origin); } catch (error) { throw new Error('Invalid QR code.'); }
    if (url.origin !== window.location.origin || !url.pathname.startsWith('/attendance/checkin/')) {
      throw new Error('This QR code is not a SmartDoor attendance code.');
    }

    await stopQrScanner();
    setScannerStatus('Recording your attendance...');
    const response = await fetch(url.pathname + url.search, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Unable to record attendance.');
    setScannerStatus(payload.message || 'Attendance recorded successfully.');
    setTimeout(closeQrScanner, 1600);
  }

  document.getElementById('openQrScanner')?.addEventListener('click', async () => {
    scannerModal.classList.add('is-open');
    scannerModal.setAttribute('aria-hidden', 'false');
    setScannerStatus('Starting camera...');
    if (typeof Html5Qrcode === 'undefined') { setScannerStatus('Scanner could not load. Refresh and try again.', true); return; }
    qrScanner = new Html5Qrcode('qr-reader');
    try {
      await qrScanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } }, async decodedText => {
        if (scannerLocked) return;
        scannerLocked = true;
        try { await submitScannedCheckIn(decodedText); } catch (error) { scannerLocked = false; setScannerStatus(error.message, true); } 
      }, () => {});
      setScannerStatus('Point your camera at the QR code.');
    } catch (error) {
      setScannerStatus('Camera access is unavailable. Check browser permissions.', true);
    }
  });
  document.getElementById('closeQrScanner')?.addEventListener('click', closeQrScanner);
  scannerModal?.addEventListener('click', event => { if (event.target === scannerModal) closeQrScanner(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && scannerModal?.classList.contains('is-open')) closeQrScanner(); });

  async function refreshStudentAttendanceSummary() {
    if (document.hidden) return;

    if (!attendanceSummaryLoaded) {
      ['student-attended-count', 'student-absent-count', 'student-attendance-rate'].forEach(function (id) {
        document.getElementById(id)?.classList.add('is-summary-loading');
      });
    }

    try {
      const response = await fetch('{{ route('student.attendance.summary') }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store',
      });
      const payload = await response.json();
      if (!response.ok || !payload.success) return;
      document.getElementById('student-attended-count').textContent = payload.attended;
      document.getElementById('student-absent-count').textContent = payload.absent;
      document.getElementById('student-attendance-rate').textContent = `${payload.rate}%`;
      attendanceSummaryLoaded = true;
    } catch (error) {
      // Keep the last known attendance summary when the network is unavailable.
    } finally {
      ['student-attended-count', 'student-absent-count', 'student-attendance-rate'].forEach(function (id) {
        document.getElementById(id)?.classList.remove('is-summary-loading');
      });
    }
  }

  refreshStudentAttendanceSummary();
  window.setInterval(refreshStudentAttendanceSummary, 15000);
</script>
</body>
</html>
