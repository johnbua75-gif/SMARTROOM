@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
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
    .profile-card { border-radius: 14px; border: 1px solid var(--border, #e4e8f0); background: #fff; box-shadow: var(--shadow-sm, 0 2px 6px rgba(15,23,41,.06)); }
    .profile-row { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; transition: all 0.2s ease; }
    .profile-row:hover { background: #f8f9fb; }
    .profile-row:last-child { border-bottom: none; }
    .profile-label { color: var(--text-3, #7c8a9e); font-weight: 500; }
    .profile-value { color: var(--text, #0f1729); font-weight: 600; }
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

    <!- Profile Details ->
    <div class="profile-card">
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
