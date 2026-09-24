@php
    $qrUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($checkinUrl);
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Attendance QR</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4>Attendance Session #{{ $session->id }}</h4>
      <a href="{{ route('faculty.attendance.session', $session->id) }}" class="btn btn-sm btn-outline-secondary">Open Session</a>
    </div>

    <div class="card p-4 text-center mx-auto" style="max-width:420px">
      <img src="{{ $qrUrl }}" alt="QR code" class="mb-3" />
      <div class="small text-muted mb-2">Scan to check-in</div>
      <div class="mb-2"><a href="{{ $checkinUrl }}" target="_blank">{{ $checkinUrl }}</a></div>
      <div class="text-muted">Expires: {{ optional($session->expires_at)->format('Y-m-d H:i') ?? 'N/A' }}</div>
    </div>
  </div>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>
