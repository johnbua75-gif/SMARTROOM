<?php
$facultyName = $facultyName ?? request()->user()?->name ?? 'Faculty';
$facultyDept = $facultyDept ?? request()->user()?->department ?? 'Faculty';
$facultyInitials = $facultyInitials ?? strtoupper(substr((string) $facultyName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile – SmartDoor</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;color:#0f1729;}
    .wrap{max-width:980px;margin:36px auto;padding:18px;background:#fff;border:1px solid #e6eef8;border-radius:12px}
    .h{display:flex;align-items:center;gap:12px}
    .avatar{width:56px;height:56px;border-radius:10px;background:#e0e7ff;display:flex;align-items:center;justify-content:center;font-weight:800}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="h">
      <div class="avatar"><?= htmlspecialchars($facultyInitials) ?></div>
      <div>
        <div style="font-weight:800;font-size:1.05rem"><?= htmlspecialchars($facultyName) ?></div>
        <div style="color:#55617a;margin-top:3px"><?= htmlspecialchars($facultyDept) ?></div>
      </div>
    </div>
    <hr style="margin:16px 0;border:none;border-top:1px solid #eef3fb">
    <p>This is a placeholder profile page. The full profile UI will be available soon.</p>
  </div>
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>
