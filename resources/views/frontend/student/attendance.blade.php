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
  <title>Attendance – Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --gold: #F5A800; --navy: #1B2A5E; }
    body { background: #F4F6FA; font-family: 'Segoe UI', sans-serif; }

    #sidebar { width: 230px; min-height: 100vh; background: #fff; border-right: 1px solid #e8eaf0; }
    .brand-icon { background: var(--gold); border-radius: 10px; width: 42px; height: 42px; display:grid; place-items:center; }
    .nav-link { color: #555; border-radius: 8px; padding: .55rem 1rem; font-weight: 500; }
    .nav-link:hover, .nav-link.active { background: #F0F4FF; color: var(--navy); }
    .nav-link.active::after { content:''; display:inline-block; width:7px; height:7px; background:var(--navy); border-radius:50%; margin-left:auto; }
    .avatar { width:38px; height:38px; background:var(--navy); border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:700; font-size:.85rem; }
    
    .stat-card { border-radius: 14px; border: 1px solid #e8eaf0; background: #fff; }
    .attendance-table { border-radius: 10px; overflow: hidden; border: 1px solid #e8eaf0; }
    .attendance-table th { background: #f8f9fa; border-bottom: 2px solid #e8eaf0; }
    .attendance-present { color: #059669; }
    .attendance-absent { color: #dc2626; }
    .attendance-hero { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:22px 24px; margin-bottom:18px; border-radius:14px; background:linear-gradient(135deg,var(--navy),#263d85); color:#fff; }
    .attendance-hero-icon { width:48px; height:48px; display:grid; place-items:center; border-radius:13px; background:rgba(245,197,24,.16); color:var(--gold); font-size:1.35rem; flex-shrink:0; }
    .attendance-hero-copy { display:flex; align-items:center; gap:14px; }
    .attendance-hero-title { font-size:1.05rem; font-weight:800; }
    .attendance-hero-subtitle { margin-top:3px; color:rgba(255,255,255,.68); font-size:.8rem; }
    .scan-qr-btn { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:9px; padding:10px 15px; background:var(--gold); color:var(--navy); font-weight:800; white-space:nowrap; }
    .scan-qr-btn:hover { background:#eab308; }
    .scanner-modal { position:fixed; inset:0; z-index:3000; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(11,22,64,.6); backdrop-filter:blur(5px); }
    .scanner-modal.is-open { display:flex; }
    .scanner-card { width:min(100%,460px); overflow:hidden; border:1px solid var(--student-border); border-radius:16px; background:#fff; box-shadow:0 20px 55px rgba(15,23,42,.2); }
    .scanner-head { display:flex; align-items:center; justify-content:space-between; padding:17px 20px; border-bottom:1px solid var(--student-border); }
    .scanner-title { color:var(--student-text); font-weight:800; }
    .scanner-close { width:32px; height:32px; border:1px solid var(--student-border); border-radius:8px; background:#f8fafc; color:var(--student-muted); }
    .scanner-body { padding:20px; text-align:center; }
    #qr-reader { width:100%; max-width:360px; margin:0 auto; overflow:hidden; border:1px solid var(--student-border); border-radius:12px; }
    .scanner-help { margin:13px 0 0; color:var(--student-muted); font-size:.8rem; }
    .scanner-status { min-height:24px; margin-top:12px; color:var(--student-blue); font-size:.82rem; font-weight:700; }
    @media (max-width:640px) { .attendance-hero { align-items:flex-start; flex-direction:column; } .scan-qr-btn { width:100%; justify-content:center; } }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!-- Main -->
  <main class="flex-grow-1 p-4">

    <!-- Topbar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div><h5 class="fw-bold mb-0">My Attendance</h5><small class="text-muted">{{ now()->format('l, F j, Y') }}</small></div>
      <a href="{{ route('student.home') }}" class="btn btn-warning fw-semibold"><i class="bi bi-house me-1"></i> Back to Home</a>
    </div>

    <div class="attendance-hero">
      <div class="attendance-hero-copy">
        <div class="attendance-hero-icon"><i class="bi bi-qr-code-scan"></i></div>
        <div>
          <div class="attendance-hero-title">Check in to your class</div>
          <div class="attendance-hero-subtitle">Scan the QR code shown by your instructor.</div>
        </div>
      </div>
      <button type="button" class="scan-qr-btn" id="openQrScanner"><i class="bi bi-camera"></i> Scan Attendance QR</button>
    </div>

    <!-- Attendance Stats -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-card p-4">
          <i class="bi bi-check-circle fs-4 text-success"></i>
          <div class="fs-2 fw-bold mt-2" id="student-attended-count">{{ $totalAttended ?? 0 }}</div>
          <div class="text-muted small">Classes Attended</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card p-4">
          <i class="bi bi-x-circle fs-4 text-danger"></i>
          <div class="fs-2 fw-bold mt-2" id="student-absent-count">{{ $totalAbsent ?? 0 }}</div>
          <div class="text-muted small">Classes Missed</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card p-4">
          <i class="bi bi-percent fs-4 text-warning"></i>
          <div class="fs-2 fw-bold mt-2" id="student-attendance-rate">{{ $attendanceRate ?? 0 }}%</div>
          <div class="text-muted small">Attendance Rate</div>
        </div>
      </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-3 overflow-hidden border">
      <table class="table mb-0">
        <thead class="table-light">
          <tr>
            <th>Course</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($attendanceRecords ?? [] as $record)
            <tr>
              <td>{{ $record->session?->course?->code ?? 'Course' }}{{ $record->session?->course?->title ? ' - '.$record->session->course->title : '' }}</td>
              <td>{{ optional($record->session)->created_at?->format('M d, Y') ?? $record->created_at->format('M d, Y') }}</td>
              <td>
                @if ($record->time_in)
                  <span class="badge {{ strtolower((string) $record->status) === 'late' ? 'bg-warning text-dark' : 'bg-success' }}">
                    {{ strtolower((string) $record->status) === 'late' ? 'Late' : 'Present' }}
                  </span>
                @else
                  <span class="badge bg-danger">Absent</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="text-center text-muted py-4">
                <i class="bi bi-inbox"></i> No attendance records found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
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
      <div class="scanner-status" id="scannerStatus" role="status"></div>
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
    } catch (error) {
      // Keep the last known attendance summary when the network is unavailable.
    }
  }

  refreshStudentAttendanceSummary();
  window.setInterval(refreshStudentAttendanceSummary, 5000);
</script>
</body>
</html>
