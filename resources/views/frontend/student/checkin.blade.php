@php
    $checkinUrl = route('attendance.checkin', $session->token);
    $token = $session->token;
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Check In</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <div class="card mx-auto" style="max-width:540px">
      <div class="card-body text-center">
        <h5 class="card-title">Class Check-in</h5>
        <p class="text-muted">Session: {{ $session->id }} - {{ optional($session->course)->code ?? 'Course' }}</p>
        @if ($checkinUnavailable ?? false)
          <div class="alert alert-warning mb-0">This attendance session is closed or expired. Ask your instructor to open a new session.</div>
        @else
          <p>Tap the button below to mark yourself present.</p>
          <button id="checkinBtn" class="btn btn-primary">Check In</button>
        @endif
        <div id="status" class="mt-3"></div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('checkinBtn')?.addEventListener('click', async () => {
      const btn = document.getElementById('checkinBtn');
      btn.disabled = true;
      const res = await fetch('{{ $checkinUrl }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' } });
      const data = await res.json().catch(() => ({}));
      const status = document.getElementById('status');
      if (res.ok) {
        status.innerHTML = '<div class="alert alert-success">' + (data.message || 'Checked in') + '</div>';
      } else {
        status.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Error') + '</div>';
        btn.disabled = false;
      }
    });
  </script>
</body>
</html>
