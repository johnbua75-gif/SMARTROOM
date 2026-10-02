$utf8NoBom = New-Object System.Text.UTF8Encoding $false

# 1. checkingRoom.blade.php
$p1 = 'c:\xampp\smartroom\resources\views\frontend\student\checkingRoom.blade.php'
$c1 = [System.IO.File]::ReadAllText($p1)
$c1 = $c1 -replace '(?s):root\{--gold:#F5A800;--navy:#1B2A5E;\}\s*body\{background:#F4F6FA;font-family:''Segoe UI'',sans-serif;\}', ":root { --gold: #f5c518; --navy: #0b1640; }`n    body { background: #f8f9fb; font-family: 'Segoe UI', sans-serif; color: #111827; }`n    h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.01em; color: #111827; }"
$c1 = $c1 -replace '(?s)/\* Campus status banner \*/.*?</style>\s*<style>.*?</style>', @"
/* Campus status banner */
    .campus-banner { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.04); color: #111827; }
    .campus-banner h2 { color: #111827; }
    .campus-banner .small, .campus-banner .opacity-75 { color: #6b7280; opacity: 1; }
    .campus-icon { background: #f8f9fb; border-radius: 8px; width: 60px; height: 60px; display: grid; place-items: center; font-size: 1.8rem; color: var(--navy); border: 1px solid #e5e7eb; }

    /* Map */
    .map-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden; }
    .map-card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; background: #fff; }
    .map-title-group { display: flex; align-items: center; gap: 10px; }
    .map-icon { width: 32px; height: 32px; border-radius: 8px; background: #f8f9fb; color: var(--navy); border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: .875rem; }
    .map-title { font-size: .875rem; font-weight: 700; color: #111827; }
    .map-sub { font-size: .75rem; color: #9ca3af; margin-top: 1px; }
    .live-badge { display: flex; align-items: center; gap: 5px; font-size: .75rem; font-weight: 600; color: #111827; background: #f8f9fb; padding: 4px 10px; border-radius: 6px; border: 1px solid #e5e7eb; }
    .live-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; animation: pulse 1.6s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
    .map-grid { background: #f8f9fb; min-height: 240px; position: relative; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; }
    .map-cell { display: flex; align-items: center; justify-content: center; min-height: 110px; position: relative; z-index: 1; }
    .building-pin { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; transition: transform 0.2s ease; }
    .building-pin:hover { transform: scale(1.05); }
    .building-pin.is-selected .pin-box { outline: 2px solid var(--gold); outline-offset: 2px; }
    .pin-box { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #111827; position: relative; background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: all 0.2s ease; }
    .pin-box.avail { color: #10b981; border-color: #10b981; }
    .pin-box.full { color: #ef4444; border-color: #ef4444; }
    .pin-box:hover { box-shadow: 0 4px 6px rgba(0,0,0,.05); transform: translateY(-2px); }
    .pin-num { position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; border: 1px solid #e5e7eb; border-radius: 50%; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; background: #fff; }
    .pin-num.g { color: #10b981; border-color: #10b981; }
    .pin-num.r { color: #ef4444; border-color: #ef4444; }
    .building-lbl { font-size: .75rem; font-weight: 600; color: #6b7280; letter-spacing: .01em; }
    .map-north { position: absolute; top: 12px; right: 12px; width: 28px; height: 28px; background: #fff; border: 1px solid #e5e7eb; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.04); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: #111827; z-index: 5; }
    .map-legend { position: absolute; bottom: 12px; left: 12px; background: #fff; border-radius: 8px; padding: 10px 14px; z-index: 5; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .legend-row { display: flex; align-items: center; gap: 8px; font-size: .75rem; font-weight: 500; color: #6b7280; padding: 2px 0; }
    .legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

    /* Room cards */
    .room-card { border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: transform 0.2s ease, box-shadow 0.2s ease; padding: 16px 24px !important; }
    .room-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,.05); }
    .amenity-tag { background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; border-radius: 6px; font-size: .75rem; padding: 4px 8px; }
    .btn-map { background: #fff; color: var(--navy); border: 1px solid #e5e7eb; border-radius: 8px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,.02); }
    .btn-map:hover { background: #f8f9fb; }
    .btn-map-gray { background: #f9fafb; color: #9ca3af; border: 1px solid #e5e7eb; border-radius: 8px; font-weight: 500; pointer-events: none; }
    
    .is-room-syncing .room-card { opacity: .7; }
    .is-room-syncing .room-card::after { content: ''; display: block; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(90deg,transparent 25%,rgba(255,255,255,.5) 50%,transparent 75%); background-size: 200% 100%; animation: roomSyncSkeleton 1.35s ease-in-out infinite; pointer-events: none; }
    @keyframes roomSyncSkeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    @media (prefers-reduced-motion: reduce) { .is-room-syncing .room-card::after { animation: none; background: rgba(255,255,255,.35); } }
  </style>
"@
[System.IO.File]::WriteAllText($p1, $c1, $utf8NoBom)

# 2. courses.blade.php
$p2 = 'c:\xampp\smartroom\resources\views\frontend\student\courses.blade.php'
$c2 = [System.IO.File]::ReadAllText($p2)
$c2 = $c2 -replace '(?s)<style>.*?</style>', @"
  <style>
    :root { --gold: #f5c518; --navy: #0b1640; }
    body { background: #f8f9fb; font-family: 'Segoe UI', sans-serif; color: #111827; }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.01em; color: #111827; }
    .course-card { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: transform 0.2s ease, box-shadow 0.2s ease; padding: 16px 24px !important; }
    .course-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,.05); }
    .course-code { color: var(--navy); font-size: .75rem; font-weight: 700; letter-spacing: .02em; }
    .course-card h6 { min-height: 2.75rem; }
    .course-meta { color: #6b7280; font-size: .875rem; }
    .enrolled-badge { background: #f9fafb; color: #10b981; border: 1px solid #e5e7eb; font-size: .75rem; border-radius: 6px; padding: 4px 8px; }
    .btn-enroll { background: #fff; color: var(--navy); border: 1px solid #e5e7eb; border-radius: 8px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,.02); }
    .btn-enroll:hover { background: #f8f9fb; color: var(--navy); }
    .btn-unenroll { background: #fff; color: #ef4444; border: 1px solid #e5e7eb; border-radius: 8px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,.02); }
    .btn-unenroll:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
    .btn-outline-primary { color: var(--navy); border-color: #e5e7eb; border-radius: 8px; }
    .btn-outline-primary:hover { background: #f8f9fb; color: var(--navy); border-color: #e5e7eb; }
    .course-search { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
    .course-search:focus { border-color: var(--navy); box-shadow: none; outline: 1px solid var(--navy); }
  </style>
"@
[System.IO.File]::WriteAllText($p2, $c2, $utf8NoBom)

# 3. course-overview.blade.php
$p3 = 'c:\xampp\smartroom\resources\views\frontend\student\course-overview.blade.php'
$c3 = [System.IO.File]::ReadAllText($p3)
$c3 = $c3 -replace '(?s)<style>.*?</style>', @"
  <style>
    :root { --navy: #0b1640; }
    body { background: #f8f9fb; font-family: 'Segoe UI', sans-serif; color: #111827; }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.01em; color: #111827; }
    .panel { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.04); padding: 16px 24px !important; }
    .course-code { color: var(--navy); font-size: .875rem; font-weight: 700; letter-spacing: .02em; }
    .muted { color: #6b7280; font-size: .875rem; }
    .stat { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.04); padding: 16px 24px !important; }
    .text-success { color: #10b981 !important; }
    .text-danger { color: #ef4444 !important; }
    .text-bg-success { background-color: #10b981 !important; color: #fff !important; border-radius: 6px; }
    .text-bg-danger { background-color: #ef4444 !important; color: #fff !important; border-radius: 6px; }
    .border-bottom { border-bottom: 1px solid #e5e7eb !important; }
  </style>
"@
[System.IO.File]::WriteAllText($p3, $c3, $utf8NoBom)

# 4. enrolled-courses.blade.php
$p4 = 'c:\xampp\smartroom\resources\views\frontend\student\enrolled-courses.blade.php'
$c4 = [System.IO.File]::ReadAllText($p4)
$c4 = $c4 -replace '(?s)<style>.*?</style>', @"
  <style>
    :root { --navy: #0b1640; }
    body { background: #f8f9fb; font-family: 'Segoe UI', sans-serif; color: #111827; }
    h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.01em; color: #111827; }
    .course-card { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: transform 0.2s ease, box-shadow 0.2s ease; padding: 16px 24px !important; }
    .course-card:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,.05); }
    .course-code { color: var(--navy); font-size: .75rem; font-weight: 700; letter-spacing: .02em; }
    .course-meta { color: #6b7280; font-size: .875rem; }
  </style>
"@
[System.IO.File]::WriteAllText($p4, $c4, $utf8NoBom)
