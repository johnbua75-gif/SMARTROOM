@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$studentEmail = optional($student)->email ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$verified = str_contains(strtolower($studentEmail), '@psu.edu.ph');
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => false, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => false, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => false, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => true, 'route' => 'student.profile'],
];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile - Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
#sidebar { width: 230px; min-height: 100vh; background: #fff; border-right: 1px solid #e8eaf0; }
    .brand-icon { background: var(--gold); border-radius: 10px; width: 42px; height: 42px; display:grid; place-items:center; }
    .nav-link { color: #555; border-radius: 10px; padding: .55rem 1rem; font-weight: 500; }
    .nav-link:hover, .nav-link.active { background: #F0F4FF; color: var(--navy); }
    .nav-link.active::after { content:''; display:inline-block; width:7px; height:7px; background:var(--navy); border-radius:50%; margin-left:auto; }
    .avatar { width:38px; height:38px; background:var(--navy); border-radius:50%; display:grid; place-items:center; color:#fff; font-weight:700; font-size:.85rem; }
    
    /* Typography & Utilities */
.text-muted { color: var(--text-4, #b0bac8) !important; }
    .badge { border-radius: 6px; font-weight: 500; }
    .btn { border-radius: 10px; transition: all 0.2s ease; border: 1px solid transparent; }
    
    .profile-header { background: #fff; border-radius: 14px; color: var(--text, #0f1729); border: 1px solid var(--border, #e4e8f0); box-shadow: var(--shadow-sm, 0 2px 6px rgba(15,23,41,.06)); }
    .profile-avatar { width: 100px; height: 100px; background: #f8f9fb; border: 1px solid var(--border, #e4e8f0); border-radius: 50%; display: grid; place-items: center; color: var(--navy); font-size: 2.5rem; font-weight: 700; box-shadow: 0 1px 3px rgba(0,0,0,.02); }
    .verification-banner {
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
      padding: 18px 20px; border-radius: 14px; background: linear-gradient(135deg, #eefdf7 0%, #f6fbff 100%);
      border: 1px solid #d8f3e6; margin-bottom: 20px;
    }
    .verification-status {
      display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(34,197,94,.12);
      color: #166534; font-size: .74rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; border: 1px solid rgba(34,197,94,.2);
    }
    .verification-subtext { color: #475569; font-size: .82rem; margin: 0; }
    .profile-card { border-radius: 14px; border: 1px solid var(--border, #e4e8f0); background: #fff; box-shadow: var(--shadow-sm, 0 2px 6px rgba(15,23,41,.06)); }
    .profile-row { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; transition: all 0.2s ease; }
    .profile-row:hover { background: #f8f9fb; }
    .profile-row:last-child { border-bottom: none; }
    .profile-label { color: var(--text-3, #7c8a9e); font-weight: 500; }
    .profile-value { color: var(--text, #0f1729); font-weight: 600; }
    .profile-meta-grid {
      display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-top: 20px;
    }
    .profile-meta-item {
      background: #fff; border: 1px solid var(--border, #e4e8f0); border-radius: 14px; padding: 18px 18px 16px;
      box-shadow: var(--shadow-sm, 0 2px 6px rgba(15,23,41,.06));
    }
    .meta-label { color: var(--text-3, #7c8a9e); font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
    .meta-value { font-weight: 700; color: var(--text, #0f1729); margin-top: 8px; font-size: 1rem; }
    .meta-pill { display:inline-flex; margin-top:8px; padding:4px 8px; border-radius:999px; font-size:.68rem; font-weight:700; }
    .meta-pill.success { background:#ecfdf5; color:#166534; }
    .meta-pill.pending { background:#fff7ed; color:#9a5b00; }
    @media (max-width:768px) { .profile-meta-grid { grid-template-columns: 1fr; } }
  </style>
  @include('frontend.student._theme')
</head>
<body>
<div class="d-flex">

  @include('frontend.student._sidebar')

  <!- Main ->
  <main class="flex-grow-1 p-4">

    <!- Topbar ->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div><h5 class="fw-bold mb-0">My Profile</h5><small class="text-muted">{{ now()->format('l, F j, Y') }}</small></div>
      <a href="{{ route('student.home') }}" class="btn btn-warning fw-semibold"><i class="bi bi-house me-1"></i> Back to Home</a>
    </div>

    <!- Profile Header ->
    <div class="profile-header p-5 mb-4 text-center">
      <div class="profile-avatar mx-auto mb-3">{{ $initials }}</div>
      <h3 class="fw-bold mb-1">{{ $studentName }}</h3>
      <p class="mb-0" style="opacity: 0.9;">{{ $studentId }}</p>
    </div>

    <div class="verification-banner">
      <div>
        <div class="verification-status"><i class="bi bi-shield-check"></i> Verification active</div>
        <p class="verification-subtext mt-2">Your account is verified for campus access and classroom participation.</p>
      </div>
      <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-2">{{ $verified ? 'PSU email verified' : 'Campus email pending' }}</span>
    </div>

    <div class="profile-meta-grid">
      <div class="profile-meta-item">
        <div class="meta-label">University ID</div>
        <div class="meta-value">{{ $studentId }}</div>
        <span class="meta-pill success"><i class="bi bi-check-circle-fill me-1"></i>Confirmed</span>
      </div>
      <div class="profile-meta-item">
        <div class="meta-label">Campus email</div>
        <div class="meta-value">{{ $studentEmail }}</div>
        <span class="meta-pill {{ $verified ? 'success' : 'pending' }}">{{ $verified ? 'Verified' : 'Review required' }}</span>
      </div>
      <div class="profile-meta-item">
        <div class="meta-label">Profile status</div>
        <div class="meta-value">{{ ucfirst(optional($student)->status ?? 'Active') }}</div>
        <span class="meta-pill success"><i class="bi bi-person-check-fill me-1"></i>Active</span>
      </div>
    </div>

    <!- Profile Details ->
    <div class="profile-card mt-4">
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-person me-2"></i>Full Name</span>
        <span class="profile-value">{{ $studentName }}</span>
      </div>
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-card-text me-2"></i>Student ID</span>
        <span class="profile-value">{{ $studentId }}</span>
      </div>
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-envelope me-2"></i>Email</span>
        <span class="profile-value">{{ optional($student)->email ?? 'N/A' }}</span>
      </div>
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-check-circle me-2"></i>Status</span>
        <span class="profile-value"><span class="badge bg-success">{{ ucfirst(optional($student)->status ?? 'Active') }}</span></span>
      </div>
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-calendar me-2"></i>Member Since</span>
        <span class="profile-value">{{ optional($student)->created_at ? optional($student)->created_at->format('M d, Y') : 'N/A' }}</span>
      </div>
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-telephone me-2"></i>Emergency Contact</span>
        <span class="profile-value">Not provided yet</span>
      </div>
      <div class="profile-row">
        <span class="profile-label"><i class="bi bi-people me-2"></i>Student Information</span>
        <span class="profile-value">Campus account verified</span>
      </div>
    </div>

    <!- Settings Section ->
    <div class="mt-5">
      <h6 class="fw-bold mb-3">Settings</h6>
      <div class="profile-card">
        <div class="profile-row">
          <span class="profile-label"><i class="bi bi-lock me-2"></i>Change Password</span>
          <a href="{{ route('password.change') }}" class="btn btn-sm btn-outline-primary">Change</a>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
