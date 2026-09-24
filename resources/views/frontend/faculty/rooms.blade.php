<?php
$facultyName = $facultyName ?? request()->user()?->name ?? 'Faculty';
$facultyDept = $facultyDept ?? request()->user()?->department ?? 'Faculty';
$facultyEmail = $facultyEmail ?? request()->user()?->email ?? '';
$facultyInitials = $facultyInitials ?? strtoupper(substr((string) $facultyName, 0, 1));

$filter = $filter ?? request('filter', 'all');
$search = $search ?? request('search', '');
$filtered_rooms = $filtered_rooms ?? ($rooms ?? []);
$available_count = $available_count ?? 0;
$occupied_count = $occupied_count ?? 0;
$total_count = $total_count ?? 0;
$mapBuildings = $mapBuildings ?? [];

function amenity_icon($amenity) {
    $icons = [
        'Projector'    => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="2"/><circle cx="12" cy="12" r="2"/></svg>',
        'AC'           => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93L4.93 19.07"/></svg>',
        'WiFi'         => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
        'Whiteboard'   => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="1"/><path d="M8 21h8M12 17v4"/></svg>',
        'Computers'    => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
        'Sound System' => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>',
    ];
    return $icons[$amenity] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Availability – SmartDoor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root {
  --yellow:     #f5c518;
  --navy:       #0b1640;
  --navy-mid:   #1a2f80;
  --navy-light: #e8ecfb;
  --white:      #ffffff;
  --bg:         #f0f2f5;
  --border:     #e5e7eb;
  --text:       #1a1d2e;
  --text-2:     #3d4a5c;
  --text-3:     #6b7280;
  --text-4:     #b0bac8;
  --accent:     #1a2b6d;
  --accent2:    #4a6cf7;
  --green:      #16a34a;
  --green-bg:   #dcfce7;
  --green-bd:   #bbf7d0;
  --green-text: #15803d;
  --red:        #dc2626;
  --red-bg:     #fee2e2;
  --red-bd:     #fca5a5;
  --orange:     #f59e0b;
  --shadow-sm:  0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.05);
  --shadow-md:  0 4px 16px rgba(0,0,0,.08),0 1px 4px rgba(0,0,0,.04);
  --r-xs: 6px; --r-sm: 10px; --r: 14px; --r-lg: 18px;
  --sidebar-w: 230px;
  --fh: 'Plus Jakarta Sans',sans-serif;
  --fb: 'DM Sans',sans-serif;
}

body { font-family:var(--fb); background:var(--bg); color:var(--text); min-height:100vh; display:flex; -webkit-font-smoothing:antialiased; }

/* ══ SIDEBAR (UNCHANGED) ══ */
.sidebar{position:fixed;left:0;top:0;width:var(--sidebar-w);height:100vh;background:var(--navy);display:flex;flex-direction:column;overflow:hidden;z-index:100}
.sidebar::before{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(245,197,24,.06) 0%,transparent 55%);pointer-events:none}
.sidebar::after{content:'';position:absolute;bottom:-60px;right:-60px;width:180px;height:180px;border-radius:50%;border:1px solid rgba(245,197,24,.08);pointer-events:none}
.sidebar-logo{display:flex;align-items:center;gap:12px;padding:28px 20px 24px 24px;text-decoration:none;border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:8px}
.logo-mark{width:40px;height:40px;background:var(--yellow);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--navy);flex-shrink:0;box-shadow:0 4px 12px rgba(245,197,24,.4)}
.logo-text .brand-psu{font-size:.6rem;font-weight:600;letter-spacing:.18em;color:rgba(255,255,255,.45);text-transform:uppercase;display:block;margin-bottom:3px}
.logo-text .brand-main{font-size:1.05rem;font-weight:700;color:#fff;letter-spacing:-.01em}
.logo-text .brand-main span{color:var(--yellow)}
.nav-section-label{font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.25);padding:16px 24px 6px}
.sidebar-nav{list-style:none;overflow-y:auto;padding:0 12px}
.sidebar-nav::-webkit-scrollbar{width:0}
.sidebar-nav li{margin-bottom:2px}
.sidebar-nav a{display:flex;align-items:center;gap:11px;padding:11px 12px;text-decoration:none;color:rgba(255,255,255,.6);font-size:.88rem;font-weight:500;border-radius:var(--r-sm);transition:all .22s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden}
.sidebar-nav a .nav-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;background:rgba(255,255,255,.05);flex-shrink:0;transition:all .22s}
.sidebar-nav a:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.06)}
.sidebar-nav a:hover .nav-icon{background:rgba(255,255,255,.1)}
.sidebar-nav a.active{background:rgba(245,197,24,.14);color:var(--yellow)}
.sidebar-nav a.active .nav-icon{background:rgba(245,197,24,.2);color:var(--yellow)}
.sidebar-nav a.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--yellow);border-radius:0 2px 2px 0}
.sidebar-footer{margin-top:auto;padding:16px 12px 24px;border-top:1px solid rgba(255,255,255,.06)}
.user-widget{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--r-sm);background:rgba(255,255,255,.05);margin-bottom:8px}
.user-avatar{width:34px;height:34px;border-radius:50%;flex-shrink:0;background:var(--navy-mid);border:2px solid rgba(245,197,24,.4);display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:var(--yellow)}
.user-widget-name{font-size:.83rem;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-widget-role{font-size:.73rem;color:rgba(255,255,255,.4)}
.sidebar-logout-btn{display:flex;align-items:center;gap:10px;padding:9px 12px;color:rgba(255,255,255,.4);font-size:.84rem;font-weight:500;border-radius:var(--r-sm);transition:all .22s;width:100%;background:none;border:none;cursor:pointer;font-family:inherit}
.sidebar-logout-btn:hover{color:#f87171;background:rgba(244,63,94,.08)}

/* ══ MAIN ══ */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* ── TOPBAR ── */
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.topbar-search{flex:1;max-width:420px;display:flex;align-items:center;gap:10px;background:var(--bg);border:1.5px solid var(--border);border-radius:24px;padding:9px 18px;transition:border-color .2s,box-shadow .2s}
.topbar-search:focus-within{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09)}
.topbar-search i{color:var(--text-4);font-size:.88rem}
.topbar-search input{border:none;outline:none;background:transparent;font-size:.88rem;font-family:var(--fb);color:var(--text);width:100%}
.topbar-search input::placeholder{color:var(--text-4)}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:16px}
.topbar-profile{position:relative;display:flex;align-items:center;gap:10px;cursor:pointer}
.topbar-profile-name{font-size:.88rem;font-weight:700;color:var(--text);line-height:1.2}
.topbar-profile-role{font-size:.75rem;color:var(--text-3)}
.topbar-avatar{width:38px;height:38px;border-radius:50%;background:#e0e7ff;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:var(--accent2);overflow:hidden}
.topbar-avatar img{width:100%;height:100%;object-fit:cover}

/* Profile dropdown */
.profile-dropdown{position:absolute;top:115%;right:0;min-width:230px;background:var(--white);border-radius:var(--r-sm);border:1px solid var(--border);box-shadow:var(--shadow-md);padding:10px 12px 8px;display:none;z-index:2000}
.profile-dropdown.is-open{display:block}
.profile-dropdown-item{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px}
.profile-dropdown-icon{width:28px;height:28px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:var(--bg);color:var(--text-3);font-size:.82rem;flex-shrink:0}
.profile-dropdown-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);font-weight:700;padding:2px 0 3px}
.profile-dropdown-value{font-size:.82rem;color:var(--text-2)}
.profile-signout-btn{width:100%;margin-top:4px;border:none;outline:none;border-radius:999px;padding:7px 10px;font-size:.82rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;background:var(--red-bg);color:var(--red);cursor:pointer;transition:background .16s,transform .08s;font-family:inherit}
.profile-signout-btn:hover{background:#fee2e2;transform:translateY(-1px)}

/* ══ CONTENT ══ */
.content{padding:28px 32px 52px;display:flex;flex-direction:column;gap:22px}

/* ── PAGE HEADER ── */
.page-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px}
.page-title{font-family:var(--fh);font-size:1.35rem;font-weight:800;color:var(--text)}
.page-sub{font-size:.82rem;color:var(--text-3);margin-top:3px}
.header-actions{display:flex;gap:10px;align-items:center}
.btn-outline{display:flex;align-items:center;gap:7px;padding:9px 16px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:var(--white);font-family:var(--fb);font-size:.82rem;font-weight:600;color:var(--text-2);cursor:pointer;transition:background .15s}
.btn-outline:hover{background:var(--bg)}
.btn-primary{display:flex;align-items:center;gap:7px;padding:9px 18px;border:none;border-radius:var(--r-sm);background:var(--accent);color:#fff;font-family:var(--fb);font-size:.82rem;font-weight:600;cursor:pointer;transition:opacity .15s}
.btn-primary:hover{opacity:.88}
.booking-toolbar{background:var(--white);border:1.5px solid var(--border);border-radius:var(--r);padding:16px 20px;box-shadow:var(--shadow-sm);display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap}
.booking-toolbar-title{width:100%;font-family:var(--fh);font-size:.92rem;font-weight:800;color:var(--text)}
.booking-toolbar-sub{width:100%;font-size:.76rem;color:var(--text-3);margin-top:-7px}
.booking-field{display:flex;flex-direction:column;gap:5px;min-width:190px;flex:1}
.booking-field label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3)}
.booking-field input{height:39px;border:1.5px solid var(--border);border-radius:9px;padding:0 10px;background:var(--bg);font: .83rem var(--fb);color:var(--text);outline:none}
.booking-field input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09);background:#fff}
.booking-search-btn{height:39px;white-space:nowrap}
.booking-result{width:100%;display:none;border-radius:8px;padding:8px 10px;font-size:.76rem;font-weight:600}
.booking-result.is-visible{display:block}.booking-result.ok{background:var(--green-bg);color:var(--green-text)}.booking-result.warn{background:var(--red-bg);color:#991b1b}
.my-reservations{background:var(--white);border:1.5px solid var(--border);border-radius:var(--r);padding:18px 20px;box-shadow:var(--shadow-sm)}
.my-reservations-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px}.my-reservations-title{font-family:var(--fh);font-size:.95rem;font-weight:800}.reservation-list{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.reservation-item{border:1px solid var(--border);border-radius:10px;padding:11px 12px;display:flex;justify-content:space-between;gap:12px;align-items:center}.reservation-room{font-weight:800;font-size:.8rem;color:var(--text)}.reservation-time{font-size:.74rem;color:var(--text-3);margin-top:3px}.reservation-status{font-size:.68rem;font-weight:700;color:var(--green-text);text-transform:uppercase}.reservation-cancel{border:1px solid var(--red-bd);background:#fff;color:var(--red);border-radius:7px;padding:7px 9px;cursor:pointer;font:700 .7rem var(--fb);white-space:nowrap}.reservation-cancel:hover{background:var(--red-bg)}.reservation-empty{color:var(--text-3);font-size:.78rem}
@media(max-width:768px){.reservation-list{grid-template-columns:1fr}.booking-field{min-width:100%}.booking-search-btn{width:100%}}

/* ── SEARCH + FILTER ── */
.search-filter-row{background:var(--white);border-radius:var(--r);padding:16px 20px;box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.search-wrap{flex:1;position:relative;min-width:200px}
.search-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-4);font-size:.8rem;pointer-events:none}
.search-wrap input{width:100%;padding:10px 14px 10px 36px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:var(--bg);font-size:.85rem;font-family:var(--fb);color:var(--text);outline:none;transition:border .15s,box-shadow .15s}
.search-wrap input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09);background:#fff}
.search-wrap input::placeholder{color:var(--text-4)}
.sf-divider{width:1px;height:26px;background:var(--border);flex-shrink:0}
.filter-label{font-size:.75rem;font-weight:700;color:var(--text-3);white-space:nowrap}
.filter-pills{display:flex;gap:5px}
.filter-btn{padding:6px 16px;border-radius:20px;font-size:.78rem;font-weight:600;font-family:var(--fb);border:1.5px solid transparent;cursor:pointer;color:var(--text-3);background:var(--bg);text-decoration:none;transition:all .15s}
.filter-btn:hover{background:#e8ecfb;color:var(--accent2);border-color:#c7d2fe}
.filter-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}

/* ── CAMPUS MAP ── */
.map-card{background:var(--white);border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden}
.map-card-header{padding:18px 22px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.map-title-group{display:flex;align-items:center;gap:10px}
.map-icon{width:32px;height:32px;border-radius:8px;background:#eff2ff;color:var(--accent2);display:flex;align-items:center;justify-content:center;font-size:.82rem}
.map-title{font-family:var(--fh);font-size:.95rem;font-weight:700;color:var(--text)}
.map-sub{font-size:.74rem;color:var(--text-3);margin-top:1px}
.live-badge{display:flex;align-items:center;gap:5px;font-size:.72rem;font-weight:700;color:var(--green);background:var(--green-bg);padding:4px 11px;border-radius:20px;border:1px solid var(--green-bd)}
.live-dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 1.6s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.map-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:2px;background:#cde0cd;min-height:240px;position:relative}
.map-cell{background:#edf7ed;display:flex;align-items:center;justify-content:center;min-height:105px;position:relative}
.map-cell:last-child{background:#f4f7f4}
.building-pin{display:flex;flex-direction:column;align-items:center;gap:7px}
.building-pin.js-building-pin{cursor:pointer}
.building-pin.is-selected .pin-box{outline:2px solid var(--yellow);outline-offset:2px}
.pin-box{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;position:relative;box-shadow:0 4px 14px rgba(0,0,0,.18)}
.pin-box.avail{background:linear-gradient(135deg,#22c55e,#16a34a)}
.pin-box.full {background:linear-gradient(135deg,#f87171,#dc2626)}
.pin-num{position:absolute;top:-7px;right:-7px;width:20px;height:20px;border:2px solid #fff;border-radius:50%;font-size:9px;font-weight:800;display:flex;align-items:center;justify-content:center}
.pin-num.g{background:#fff;color:var(--green)}
.pin-num.r{background:#fff;color:var(--red)}
.building-lbl{font-size:11px;font-weight:700;color:var(--text-2);text-align:center;max-width:90px;line-height:1.35}
.map-north{position:absolute;top:12px;right:12px;width:26px;height:26px;background:#fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.1);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--navy);z-index:5}
.map-legend{position:absolute;bottom:12px;left:12px;background:rgba(255,255,255,.96);backdrop-filter:blur(4px);border-radius:10px;padding:10px 14px;box-shadow:var(--shadow-sm);z-index:5;border:1px solid var(--border)}
.legend-row{display:flex;align-items:center;gap:7px;font-size:.72rem;font-weight:600;color:var(--text-2);padding:2px 0}
.legend-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}

/* ── ROOMS SECTION ── */
.rooms-header{display:flex;align-items:center;justify-content:space-between}
.rooms-section-title{font-family:var(--fh);font-size:.95rem;font-weight:700;color:var(--text)}
.rooms-count-badge{font-size:.78rem;color:var(--text-3);background:var(--white);padding:4px 13px;border-radius:20px;border:1.5px solid var(--border);font-weight:600;box-shadow:var(--shadow-sm)}

/* ── ROOM CARDS GRID ── */
.rooms-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
.room-card{background:var(--white);border:1.5px solid var(--border);border-radius:var(--r);padding:20px;box-shadow:var(--shadow-sm);transition:transform .18s,box-shadow .18s;display:flex;flex-direction:column}
.room-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
.card-strip{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.room-id{font-size:.68rem;font-weight:700;letter-spacing:.07em;color:var(--text-4);text-transform:uppercase}
.status-pill{display:inline-flex;align-items:center;gap:5px;font-size:.7rem;font-weight:700;padding:4px 10px;border-radius:20px}
.pill-avail{background:var(--green-bg);color:var(--green-text);border:1px solid var(--green-bd)}
.pill-res  {background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.pill-occ  {background:var(--red-bg);color:var(--red);border:1px solid var(--red-bd)}
.pill-maint{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.room-name{font-family:var(--fh);font-size:1.05rem;font-weight:800;color:var(--text);margin-bottom:4px}
.room-loc{display:flex;align-items:center;gap:5px;font-size:.77rem;color:var(--text-3);margin-bottom:14px}
.room-loc i{font-size:.65rem;color:var(--text-4)}
.room-meta{display:flex;margin-bottom:14px}
.meta-chip{display:flex;align-items:center;gap:5px;font-size:.76rem;font-weight:500;color:var(--text-3);background:var(--bg);border:1px solid var(--border);padding:5px 10px}
.meta-chip:first-child{border-radius:var(--r-xs) 0 0 var(--r-xs);border-right:none}
.meta-chip:last-child {border-radius:0 var(--r-xs) var(--r-xs) 0}
.meta-chip i{font-size:.65rem;color:var(--text-4)}
.meta-chip.tg{background:var(--green-bg);color:var(--green-text);border-color:var(--green-bd);font-weight:600}
.meta-chip.tg i{color:var(--green)}
.meta-chip.ty{background:#fff7ed;color:#c2410c;border-color:#fed7aa;font-weight:600}
.meta-chip.ty i{color:#c2410c}
.meta-chip.tr{background:var(--red-bg);color:var(--red);border-color:var(--red-bd);font-weight:600}
.meta-chip.tr i{color:var(--red)}
.am-label{font-size:.67rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text-4);margin-bottom:7px}
.am-row{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:16px}
.am-chip{display:inline-flex;align-items:center;gap:4px;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-xs);padding:3px 9px;font-size:.72rem;color:var(--text-3);font-weight:500}
.issue-note{display:none;margin-bottom:12px;padding:8px 10px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.74rem;font-weight:600;line-height:1.35}
.issue-note.is-visible{display:block}
.card-actions{display:flex;gap:8px;margin-top:auto}
.btn-check,.btn-res{flex:1;padding:10px;border-radius:var(--r-sm);font-size:.8rem;font-weight:700;font-family:var(--fb);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s}
.btn-check{background:var(--bg);color:var(--text-2);border:1.5px solid var(--border)}
.btn-check:hover{background:#e8ecfb;color:var(--accent2);border-color:#c7d2fe}
.btn-res.on{background:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(26,43,109,.22)}
.btn-res.on:hover{background:var(--navy-mid);transform:translateY(-1px);box-shadow:0 4px 14px rgba(26,43,109,.3)}
.btn-res.off{background:var(--bg);color:var(--text-4);cursor:not-allowed;border:1.5px solid var(--border)}
.btn-view-temp{flex:1;padding:10px;border-radius:var(--r-sm);font-size:.8rem;font-weight:700;font-family:var(--fb);border:1.5px solid #10b981;background:#ecfdf5;color:#059669;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s}
.btn-view-temp:hover{background:#d1fae5;transform:translateY(-1px)}

/* ── CHECK AVAILABILITY OVERLAY ── */
.check-result{display:none;font-size:.77rem;border-radius:8px;padding:8px 10px;border:1px solid var(--border);background:#f8fafc;color:var(--text-2)}
.check-result.is-visible{display:block}
.check-result.ok{background:#ecfdf5;border-color:#86efac;color:#166534}
.check-result.warn{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
.check-cancelled{display:none;border:1px solid var(--border);border-radius:8px;background:#fff;padding:9px 10px}
.check-cancelled.is-visible{display:block}
.check-cancelled-title{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-3);margin-bottom:6px}
.check-cancelled-list{display:flex;flex-direction:column;gap:6px}
.check-cancelled-item{font-size:.76rem;color:var(--text-2);background:#f9fafb;border:1px solid var(--border);border-radius:7px;padding:7px 8px}

/* ── ANIMATIONS ── */
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.page-header      {animation:fadeUp .35s both .02s}
.search-filter-row{animation:fadeUp .35s both .07s}
.map-card         {animation:fadeUp .35s both .12s}
.rooms-header     {animation:fadeUp .35s both .17s}
.rooms-grid       {animation:fadeUp .35s both .22s}

@media(max-width:1200px){.rooms-grid{grid-template-columns:1fr}}
@media(max-width:900px){.content{padding:20px 18px 40px}}
@media(max-width:768px){:root{--sidebar-w:0px}.sidebar{display:none}}

/* ── RESERVE OVERLAY ── */
.reserve-overlay{position:fixed;inset:0;background:rgba(11,22,64,.42);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;z-index:2100;padding:18px}
.reserve-overlay.is-open{display:flex}
.reserve-modal{width:min(460px,100%);background:var(--white);border:1.5px solid var(--border);border-radius:var(--r-lg);box-shadow:0 18px 45px rgba(11,22,64,.28);overflow:hidden}
.reserve-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);background:#f8faff}
.reserve-title{font-family:var(--fh);font-size:.95rem;font-weight:800;color:var(--text)}
.reserve-sub{font-size:.75rem;color:var(--text-3);margin-top:2px}
.reserve-close{width:30px;height:30px;border-radius:50%;border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--text-3);cursor:pointer;transition:all .15s}
.reserve-close:hover{background:var(--bg);color:var(--text-2)}
.reserve-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:10px}
.reserve-group{display:flex;flex-direction:column;gap:5px}
.reserve-label{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-3)}
.reserve-input{height:39px;border:1.5px solid var(--border);border-radius:9px;padding:0 11px;font-size:.83rem;font-family:var(--fb);color:var(--text);background:var(--bg);outline:none;transition:border-color .15s,box-shadow .15s,background .15s}
.reserve-input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09);background:#fff}
.reserve-textarea{min-height:72px;resize:vertical;padding:10px 11px}
.reserve-error{display:none;font-size:.77rem;color:var(--red);background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:8px 10px}
.reserve-error.is-visible{display:block}
.reserve-actions{display:flex;gap:8px;padding-top:2px}
.reserve-cancel,.reserve-submit{flex:1;height:39px;border-radius:9px;font-size:.82rem;font-weight:700;font-family:var(--fb);cursor:pointer;transition:all .15s}
.reserve-cancel{border:1.5px solid var(--border);background:var(--white);color:var(--text-2)}
.reserve-cancel:hover{background:var(--bg)}
.reserve-submit{border:none;background:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(26,43,109,.2)}
.reserve-submit:hover{background:var(--navy-mid)}
.reserve-submit:disabled{opacity:.7;cursor:not-allowed}

.temp-sched-info{padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-sm);font-size:.82rem;margin-bottom:12px;line-height:1.5}
.temp-sched-info strong{color:var(--text-2);font-weight:600}
.temp-schedule-list{max-height:400px;overflow-y:auto}
.temp-schedule-list .check-cancelled-item{margin-bottom:10px}

/* ── TOAST ── */
.toast-wrap{position:fixed;right:18px;bottom:18px;display:flex;flex-direction:column;gap:8px;z-index:2200;pointer-events:none}
.toast{min-width:240px;max-width:360px;padding:10px 12px;border-radius:10px;border:1px solid var(--border);box-shadow:var(--shadow-md);font-size:.8rem;font-weight:600;opacity:0;transform:translateY(10px);transition:opacity .2s,transform .2s;background:var(--white);color:var(--text-2)}
.toast.is-visible{opacity:1;transform:translateY(0)}
.toast.toast-success{background:#ecfdf5;border-color:#86efac;color:#166534}
</style>
@include('partials.pro-motion')
</head>
<body>

<!-- ═══ SIDEBAR — DO NOT CHANGE ═══ -->
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
      <a href="{{ url('/faculty_dashboard') }}" class="{{ Request::is('faculty_dashboard') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-line"></i></span>Dashboard
     
      </a>
    </li>
    <li>
      <a href="{{ url('/rooms') }}" class="{{ Request::is('rooms*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-door-open"></i></span>Rooms
      </a>
    </li>
    <li>
      <a href="{{ url('/faculty-schedule') }}" class="{{ Request::is('faculty-schedule') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-clock"></i></span>Schedule
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
    <!-- AI Recommendations removed from sidebar -->
    <li>
      <a href="{{ route('faculty.rfid.verification') }}" class="{{ Request::is('rfid-verification') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID Verification
      </a>
    </li>
    <li>
      <a href="{{ url('/reports') }}" class="{{ Request::is('reports*') ? 'active' : '' }}">
        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports
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
        <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
      </button>
    </form>
  </div>
</div>

<!-- ═══ MAIN ═══ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search for classrooms, faculty, or subjects...">
    </div>
    <div class="topbar-right">
      <div class="topbar-profile">
        <div>
          <div class="topbar-profile-name"><?= htmlspecialchars($facultyName) ?></div>
          <div class="topbar-profile-role"><?= htmlspecialchars($facultyDept) ?></div>
        </div>
        <div class="topbar-avatar">
          <span><?= htmlspecialchars($facultyInitials) ?></span>
        </div>
        <div class="profile-dropdown">
          <div class="profile-dropdown-item">
            <span class="profile-dropdown-icon"><i class="fas fa-envelope"></i></span>
            <div>
              <div class="profile-dropdown-label">Email</div>
              <div class="profile-dropdown-value"><?= htmlspecialchars($facultyEmail) ?></div>
            </div>
          </div>
          <div class="profile-dropdown-item">
            <span class="profile-dropdown-icon"><i class="fas fa-briefcase"></i></span>
            <div>
              <div class="profile-dropdown-label">Position</div>
              <div class="profile-dropdown-value"><?= htmlspecialchars($facultyDept) ?></div>
            </div>
          </div>
          <form method="POST" action="<?= htmlspecialchars(url('/logout')) ?>">
            <?= csrf_field(); ?>
            <button type="submit" class="profile-signout-btn">
              <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <div class="page-title">Room Availability</div>
        <div class="page-sub">Real-time overview of PSU SmartRoom availability across all buildings.</div>
      </div>
    </div>

    <section class="booking-toolbar" aria-labelledby="bookingTitle">
      <div class="booking-toolbar-title" id="bookingTitle"><i class="fas fa-calendar-plus"></i> Find a free room</div>
      <div class="booking-toolbar-sub">Choose a time, check all rooms, then use Reserve on an available room.</div>
      <div class="booking-field"><label for="bookingStartAt">Start</label><input id="bookingStartAt" type="datetime-local"></div>
      <div class="booking-field"><label for="bookingEndAt">End</label><input id="bookingEndAt" type="datetime-local"></div>
      <button type="button" class="btn-primary booking-search-btn" id="findFreeRoomsBtn"><i class="fas fa-magnifying-glass"></i> Find Free Rooms</button>
      <div class="booking-result" id="bookingResult"></div>
    </section>

    <section class="my-reservations" aria-labelledby="myReservationsTitle">
      <div class="my-reservations-head"><div class="my-reservations-title" id="myReservationsTitle"><i class="fas fa-calendar-check"></i> My Reservations</div><button type="button" class="btn-outline" id="refreshReservationsBtn"><i class="fas fa-rotate"></i> Refresh</button></div>
      <div class="reservation-list" id="reservationList"><div class="reservation-empty">Loading reservations...</div></div>
    </section>

    <!-- Search + Filter -->
    <form method="GET" action="">
      <div class="search-filter-row">
        <div class="search-wrap">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" name="search" placeholder="Search by room name or building…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="sf-divider"></div>
        <span class="filter-label"><i class="fas fa-filter"></i>&nbsp; Show:</span>
        <div class="filter-pills">
          <a href="?filter=all&search=<?= urlencode($search) ?>"       class="filter-btn <?= $filter==='all'       ? 'active':'' ?>">All Rooms</a>
          <a href="?filter=available&search=<?= urlencode($search) ?>" class="filter-btn <?= $filter==='available' ? 'active':'' ?>">Available</a>
          <a href="?filter=occupied&search=<?= urlencode($search) ?>"  class="filter-btn <?= $filter==='occupied'  ? 'active':'' ?>">Occupied</a>
        </div>
      </div>
    </form>

    <!-- Campus Map -->
    <div class="map-card">
      <div class="map-card-header">
        <div class="map-title-group">
          <div class="map-icon"><i class="fas fa-map"></i></div>
          <div>
            <div class="map-title">Campus Map</div>
            <div class="map-sub">PSU Assingan Campus Layout</div>
          </div>
        </div>
        <span class="live-badge"><span class="live-dot"></span> Live</span>
      </div>
      <div class="map-grid">
        <?php for ($i = 0; $i < 3; $i++):
          $building = $mapBuildings[$i] ?? ['building' => 'N/A', 'available' => 0, 'is_full' => true];
          $isFull = (bool) ($building['is_full'] ?? true);
        ?>
        <div class="map-cell">
          <div class="building-pin js-building-pin" data-building="<?= htmlspecialchars((string) ($building['building'] ?? 'N/A'), ENT_QUOTES) ?>">
            <div class="pin-box <?= $isFull ? 'full' : 'avail' ?>">
              <i class="fas fa-building"></i>
              <span class="pin-num <?= $isFull ? 'r' : 'g' ?>" data-building-available><?= (int) ($building['available'] ?? 0) ?></span>
            </div>
            <span class="building-lbl" data-building-label><?= htmlspecialchars((string) ($building['building'] ?? 'N/A')) ?></span>
          </div>
        </div>
        <?php endfor; ?>
        <div class="map-cell"></div>
        <div class="map-north">N</div>
        <div class="map-legend">
          <div class="legend-row"><div class="legend-dot" style="background:#22c55e"></div> Has Available Rooms</div>
          <div class="legend-row"><div class="legend-dot" style="background:#dc2626"></div> All Rooms Occupied</div>
          <div class="legend-row"><div class="legend-dot" style="background:#f5c518"></div> Selected Room</div>
        </div>
      </div>
    </div>

    <!-- Rooms Header -->
    <div class="rooms-header">
      <div class="rooms-section-title">Classrooms &amp; Labs</div>
      <span class="rooms-count-badge"><?= count($filtered_rooms) ?> rooms found</span>
    </div>

    <!-- Rooms Grid -->
    <div class="rooms-grid">
      <?php foreach ($filtered_rooms as $room):
        $status = (string) ($room['status'] ?? 'available');
        $a = $status === 'available';
        $isReserved = $status === 'reserved';
        $isMaintenance = in_array($status, ['maintenance', 'unavailable'], true);
        $pillCls = $a ? 'pill-avail' : ($isReserved ? 'pill-res' : ($isMaintenance ? 'pill-maint' : 'pill-occ'));
        $pillIcon = $a ? 'fas fa-circle-check' : ($isReserved ? 'fas fa-calendar-check' : ($isMaintenance ? 'fas fa-triangle-exclamation' : 'fas fa-circle-xmark'));
        $pillLbl = $a ? 'Available' : ($isReserved ? 'Reserved' : ($isMaintenance ? 'Unavailable' : 'Occupied'));
        $tCls = $a ? 'tg' : ($isReserved ? 'ty' : 'tr');
      ?>
      <div class="room-card js-room-card" data-room-id="<?= (int) $room['id'] ?>" data-building="<?= htmlspecialchars((string) $room['building'], ENT_QUOTES) ?>">
        <div class="card-strip">
          <span class="room-id">ID · <?= $room['id'] ?></span>
          <span class="status-pill <?= $pillCls ?>" data-status-pill><i class="<?= $pillIcon ?>"></i> <span data-status-label><?= $pillLbl ?></span></span>
        </div>
        <div class="room-name"><?= htmlspecialchars($room['name']) ?></div>
        <div class="room-loc">
          <i class="fas fa-location-dot"></i>
          <?= htmlspecialchars($room['building']) ?> &bull; <?= htmlspecialchars($room['floor']) ?>
        </div>
        <div class="room-meta">
          <div class="meta-chip"><i class="fas fa-users"></i> <?= $room['seats'] ?> seats</div>
          <div class="meta-chip <?= $tCls ?>" data-time-chip><i class="fas fa-clock"></i> <span data-time-info><?= htmlspecialchars($room['time_info']) ?></span></div>
        </div>
        <div class="am-label">Amenities</div>
        <div class="am-row">
          <?php foreach ($room['amenities'] as $am): ?>
          <span class="am-chip"><?= amenity_icon($am) ?> <?= htmlspecialchars($am) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="issue-note <?= (!empty($room['issue_note']) && in_array($status, ['maintenance', 'unavailable'], true)) ? 'is-visible' : '' ?>" data-issue-note>
          <i class="fas fa-triangle-exclamation"></i>
          <span data-issue-note-text><?= htmlspecialchars((string) ($room['issue_note'] ?? '')) ?></span>
        </div>
        <div class="card-actions">
          <button
            class="btn-check js-check-btn"
            data-room-id="<?= (int) $room['id'] ?>"
            data-room-name="<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>"
          >
            <i class="fas fa-magnifying-glass-clock"></i> Check Availability
          </button>
          <button
            class="btn-res js-reserve-btn <?= $a ? 'on' : 'off' ?>"
            data-room-id="<?= (int) $room['id'] ?>"
            data-room-name="<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>"
            <?= $a ? '' : 'disabled' ?>
          >
            <i class="fas fa-calendar-plus"></i> Reserve
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<div class="reserve-overlay" id="reserveOverlay" aria-hidden="true">
  <div class="reserve-modal" role="dialog" aria-modal="true" aria-labelledby="reserveTitle">
    <div class="reserve-head">
      <div>
        <div class="reserve-title" id="reserveTitle">Reserve Room</div>
        <div class="reserve-sub" id="reserveRoomName">Select your preferred time slot</div>
      </div>
      <button type="button" class="reserve-close" id="reserveCloseBtn" aria-label="Close reserve dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <form id="reserveForm" class="reserve-body">
      <input type="hidden" id="reserveRoomId" name="classroom_id">

      <div class="reserve-group">
        <label class="reserve-label" for="reserveStartAt">Start</label>
        <input class="reserve-input" id="reserveStartAt" name="start_at" type="datetime-local" required>
      </div>

      <div class="reserve-group">
        <label class="reserve-label" for="reserveEndAt">End</label>
        <input class="reserve-input" id="reserveEndAt" name="end_at" type="datetime-local" required>
      </div>

      <div class="reserve-group">
        <label class="reserve-label" for="reserveNotes">Notes (Optional)</label>
        <textarea class="reserve-input reserve-textarea" id="reserveNotes" name="notes" placeholder="Purpose or class details..."></textarea>
      </div>

      <div class="reserve-error" id="reserveError"></div>

      <div class="reserve-actions">
        <button type="button" class="reserve-cancel" id="reserveCancelBtn">Cancel</button>
        <button type="submit" class="reserve-submit" id="reserveSubmitBtn">Confirm Reservation</button>
      </div>
    </form>
  </div>
</div>

<div class="reserve-overlay" id="checkOverlay" aria-hidden="true">
  <div class="reserve-modal" role="dialog" aria-modal="true" aria-labelledby="checkTitle">
    <div class="reserve-head">
      <div>
        <div class="reserve-title" id="checkTitle">Check Availability</div>
        <div class="reserve-sub" id="checkRoomName">Select a time range</div>
      </div>
      <button type="button" class="reserve-close" id="checkCloseBtn" aria-label="Close availability dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <form id="checkForm" class="reserve-body">
      <input type="hidden" id="checkRoomId" name="classroom_id">

      <div class="reserve-group">
        <label class="reserve-label" for="checkStartAt">Start</label>
        <input class="reserve-input" id="checkStartAt" name="start_at" type="datetime-local" required>
      </div>

      <div class="reserve-group">
        <label class="reserve-label" for="checkEndAt">End</label>
        <input class="reserve-input" id="checkEndAt" name="end_at" type="datetime-local" required>
      </div>

      <div class="check-result" id="checkResultBox"></div>

      <div class="check-cancelled" id="checkCancelledBox">
        <div class="check-cancelled-title">Cancelled Classes In This Time</div>
        <div class="check-cancelled-list" id="checkCancelledList"></div>
      </div>

      <div class="reserve-actions">
        <button type="button" class="reserve-cancel" id="checkCancelBtn">Close</button>
        <button type="submit" class="reserve-submit" id="checkSubmitBtn">Run Check</button>
      </div>
    </form>
  </div>
</div>

<div class="reserve-overlay" id="roomDetailOverlay" aria-hidden="true">
  <div class="reserve-modal" role="dialog" aria-modal="true" aria-labelledby="roomDetailTitle">
    <div class="reserve-head">
      <div>
        <div class="reserve-title" id="roomDetailTitle">Room Details</div>
        <div class="reserve-sub" id="roomDetailSub">Loading room information...</div>
      </div>
      <button type="button" class="reserve-close" id="roomDetailCloseBtn" aria-label="Close room details dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <div class="reserve-body">
      <input type="hidden" id="roomDetailRoomId">

      <div class="reserve-group">
        <label class="reserve-label" for="detailViewMode">Schedule View</label>
        <select class="reserve-input" id="detailViewMode">
          <option value="day">Selected Day</option>
          <option value="week">Selected Week</option>
        </select>
      </div>

      <div class="reserve-group">
        <label class="reserve-label" for="detailDate">Date</label>
        <input class="reserve-input" id="detailDate" type="date">
      </div>

      <div class="check-result" id="detailStatusBox"></div>

      <div class="check-cancelled is-visible" id="detailNextScheduleBox">
        <div class="check-cancelled-title">Next Upcoming Schedule</div>
        <div class="check-cancelled-list" id="detailNextScheduleList"></div>
      </div>

      <div class="check-cancelled is-visible" id="detailScheduleBox">
        <div class="check-cancelled-title">Fixed Schedule</div>
        <div class="check-cancelled-list" id="detailScheduleList"></div>
      </div>

      <div class="check-cancelled is-visible" id="detailReservationBox">
        <div class="check-cancelled-title">Room Reservations</div>
        <div class="check-cancelled-list" id="detailReservationList"></div>
      </div>

      <div class="reserve-actions">
        <button type="button" class="reserve-cancel" id="roomDetailCloseActionBtn">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toastWrap" aria-live="polite" aria-atomic="true"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var csrfToken = '<?= csrf_token() ?>';
  var reserveOverlay = document.getElementById('reserveOverlay');
  var reserveForm = document.getElementById('reserveForm');
  var reserveRoomId = document.getElementById('reserveRoomId');
  var reserveRoomName = document.getElementById('reserveRoomName');
  var reserveStartAt = document.getElementById('reserveStartAt');
  var reserveEndAt = document.getElementById('reserveEndAt');
  var reserveNotes = document.getElementById('reserveNotes');
  var reserveError = document.getElementById('reserveError');
  var reserveSubmitBtn = document.getElementById('reserveSubmitBtn');
  var checkOverlay = document.getElementById('checkOverlay');
  var checkForm = document.getElementById('checkForm');
  var checkRoomId = document.getElementById('checkRoomId');
  var checkRoomName = document.getElementById('checkRoomName');
  var checkStartAt = document.getElementById('checkStartAt');
  var checkEndAt = document.getElementById('checkEndAt');
  var checkResultBox = document.getElementById('checkResultBox');
  var checkCancelledBox = document.getElementById('checkCancelledBox');
  var checkCancelledList = document.getElementById('checkCancelledList');
  var checkSubmitBtn = document.getElementById('checkSubmitBtn');
  var toastWrap = document.getElementById('toastWrap');
  var roomDetailOverlay = document.getElementById('roomDetailOverlay');
  var roomDetailSub = document.getElementById('roomDetailSub');
  var roomDetailRoomId = document.getElementById('roomDetailRoomId');
  var detailViewMode = document.getElementById('detailViewMode');
  var detailDate = document.getElementById('detailDate');
  var detailStatusBox = document.getElementById('detailStatusBox');
  var detailNextScheduleList = document.getElementById('detailNextScheduleList');
  var detailScheduleList = document.getElementById('detailScheduleList');
  var detailReservationList = document.getElementById('detailReservationList');
  var bookingStartAt = document.getElementById('bookingStartAt');
  var bookingEndAt = document.getElementById('bookingEndAt');
  var findFreeRoomsBtn = document.getElementById('findFreeRoomsBtn');
  var bookingResult = document.getElementById('bookingResult');
  var reservationList = document.getElementById('reservationList');
  var refreshReservationsBtn = document.getElementById('refreshReservationsBtn');
  var selectedBuilding = null;
  var detailPollTimer = null;

  function closeAll() {
    document.querySelectorAll('.profile-dropdown').forEach(el => el.classList.remove('is-open'));
  }
  document.querySelectorAll('.topbar-profile').forEach(function (profile) {
    var dd = profile.querySelector('.profile-dropdown');
    if (!dd) return;
    profile.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = dd.classList.contains('is-open');
      closeAll();
      if (!open) dd.classList.add('is-open');
    });
  });
  document.addEventListener('click', closeAll);

  function localIso(date) {
    return new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 19);
  }

  function localDateTimeInput(date) {
    return new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
  }

  function formatDateOnly(date) {
    return new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 10);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatScheduleRange(startAt, endAt) {
    var startLabel = startAt ? new Date(startAt).toLocaleString() : '-';
    var endLabel = endAt ? new Date(endAt).toLocaleString() : '-';
    return startLabel + ' - ' + endLabel;
  }

  function openReserveOverlay(roomId, roomName) {
    if (!reserveOverlay || !reserveForm) {
      return;
    }

    var now = new Date();
    var startDefault = new Date(now.getTime() + 30 * 60000);
    var endDefault = new Date(startDefault.getTime() + 60 * 60000);

    reserveRoomId.value = String(roomId);
    reserveRoomName.textContent = roomName;
    reserveStartAt.value = bookingStartAt && bookingStartAt.value ? bookingStartAt.value : localDateTimeInput(startDefault);
    reserveEndAt.value = bookingEndAt && bookingEndAt.value ? bookingEndAt.value : localDateTimeInput(endDefault);
    reserveNotes.value = '';
    reserveError.textContent = '';
    reserveError.classList.remove('is-visible');
    reserveSubmitBtn.disabled = false;

    reserveOverlay.classList.add('is-open');
    reserveOverlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    reserveStartAt.focus();
  }

  function setBookingDefaults() {
    if (!bookingStartAt || !bookingEndAt) return;
    var now = new Date();
    var start = new Date(now.getTime() + 30 * 60000);
    var end = new Date(start.getTime() + 60 * 60000);
    bookingStartAt.value = localDateTimeInput(start);
    bookingEndAt.value = localDateTimeInput(end);
  }

  function formatReservationTime(startAt, endAt) {
    return new Date(startAt).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })
      + ' - ' + new Date(endAt).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }

  async function loadMyReservations() {
    if (!reservationList) return;
    try {
      var response = await fetch('{{ route('faculty.reservations.mine') }}', {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      var payload = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(payload.message || 'Unable to load reservations.');
      var reservations = Array.isArray(payload.data) ? payload.data : [];
      if (!reservations.length) {
        reservationList.innerHTML = '<div class="reservation-empty">No upcoming reservations.</div>';
        return;
      }
      reservationList.innerHTML = reservations.map(function (reservation) {
        var room = reservation.classroom || {};
        return '<div class="reservation-item">'
          + '<div><div class="reservation-room">' + escapeHtml(room.name || 'Unknown room') + '</div>'
          + '<div class="reservation-time">' + escapeHtml(formatReservationTime(reservation.start_at, reservation.end_at)) + '</div>'
          + '<div class="reservation-status">' + escapeHtml(reservation.status) + '</div></div>'
          + '<button type="button" class="reservation-cancel js-cancel-reservation" data-reservation-id="' + reservation.id + '"><i class="fas fa-xmark"></i> Cancel</button></div>';
      }).join('');
    } catch (error) {
      reservationList.innerHTML = '<div class="reservation-empty">' + escapeHtml(error.message) + '</div>';
    }
  }

  async function cancelReservation(reservationId, button) {
    if (!confirm('Cancel this room reservation?')) return;
    button.disabled = true;
    var response = await fetch('/api/v1/reservations/' + reservationId, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
    });
    var payload = await response.json().catch(function () { return {}; });
    if (!response.ok) {
      showToast(payload.message || 'Unable to cancel reservation.', 'error');
      button.disabled = false;
      return;
    }
    showToast(payload.message || 'Reservation cancelled.', 'success');
    loadMyReservations();
    refreshRoomStatuses();
  }

  async function findFreeRooms() {
    if (!bookingStartAt.value || !bookingEndAt.value) {
      bookingResult.textContent = 'Choose both a start and end time.';
      bookingResult.className = 'booking-result is-visible warn';
      return;
    }
    findFreeRoomsBtn.disabled = true;
    var query = new URLSearchParams({ start_at: localIso(new Date(bookingStartAt.value)), end_at: localIso(new Date(bookingEndAt.value)) });
    try {
      var response = await fetch('/api/v1/room-statuses?' + query.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      var payload = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(payload.message || 'Unable to check rooms.');
      var items = Array.isArray(payload.data) ? payload.data : [];
      var freeRooms = items.filter(function (item) { return item.status === 'available'; });
      items.forEach(updateRoomCard);
      bookingResult.textContent = freeRooms.length + ' room' + (freeRooms.length === 1 ? '' : 's') + ' available for the selected time.';
      bookingResult.className = 'booking-result is-visible ' + (freeRooms.length ? 'ok' : 'warn');
    } catch (error) {
      bookingResult.textContent = error.message;
      bookingResult.className = 'booking-result is-visible warn';
    } finally {
      findFreeRoomsBtn.disabled = false;
    }
  }

  function closeReserveOverlay() {
    if (!reserveOverlay) {
      return;
    }

    reserveOverlay.classList.remove('is-open');
    reserveOverlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function openCheckOverlay(roomId, roomName) {
    if (!checkOverlay || !checkForm) {
      return;
    }

    var now = new Date();
    var startDefault = new Date(now.getTime() + 30 * 60000);
    var endDefault = new Date(startDefault.getTime() + 60 * 60000);

    checkRoomId.value = String(roomId);
    checkRoomName.textContent = roomName;
    checkStartAt.value = localDateTimeInput(startDefault);
    checkEndAt.value = localDateTimeInput(endDefault);
    checkResultBox.textContent = '';
    checkResultBox.classList.remove('is-visible', 'ok', 'warn');
    checkCancelledList.innerHTML = '';
    checkCancelledBox.classList.remove('is-visible');
    checkSubmitBtn.disabled = false;

    checkOverlay.classList.add('is-open');
    checkOverlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeCheckOverlay() {
    if (!checkOverlay) {
      return;
    }

    checkOverlay.classList.remove('is-open');
    checkOverlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function openRoomDetailOverlay(roomId, roomName) {
    if (!roomDetailOverlay) {
      return;
    }

    roomDetailRoomId.value = String(roomId);
    roomDetailSub.textContent = roomName;
    detailViewMode.value = 'day';
    detailDate.value = formatDateOnly(new Date());
    detailStatusBox.textContent = '';
    detailStatusBox.classList.remove('is-visible', 'ok', 'warn');
    detailNextScheduleList.innerHTML = '<div class="check-cancelled-item">Loading next upcoming schedule...</div>';
    detailScheduleList.innerHTML = '<div class="check-cancelled-item">Loading fixed schedule...</div>';
    detailReservationList.innerHTML = '<div class="check-cancelled-item">Loading reservations...</div>';

    roomDetailOverlay.classList.add('is-open');
    roomDetailOverlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    refreshRoomDetail();
    clearInterval(detailPollTimer);
    detailPollTimer = setInterval(refreshRoomDetail, 15000);
  }

  function closeRoomDetailOverlay() {
    if (!roomDetailOverlay) {
      return;
    }

    roomDetailOverlay.classList.remove('is-open');
    roomDetailOverlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    clearInterval(detailPollTimer);
    detailPollTimer = null;
  }

  function showToast(message, type) {
    if (!toastWrap || !message) {
      return;
    }

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (type || 'success');
    toast.textContent = message;
    toastWrap.appendChild(toast);

    requestAnimationFrame(function () {
      toast.classList.add('is-visible');
    });

    setTimeout(function () {
      toast.classList.remove('is-visible');
      setTimeout(function () {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 220);
    }, 2600);
  }

  function updateRoomCard(roomStatus) {
    var card = document.querySelector('.room-card[data-room-id="' + roomStatus.classroom_id + '"]');
    if (!card) {
      return;
    }

    var statusPill = card.querySelector('[data-status-pill]');
    var statusLabel = card.querySelector('[data-status-label]');
    var timeChip = card.querySelector('[data-time-chip]');
    var timeInfo = card.querySelector('[data-time-info]');
    var reserveBtn = card.querySelector('.js-reserve-btn');
    var issueNoteBox = card.querySelector('[data-issue-note]');
    var issueNoteText = card.querySelector('[data-issue-note-text]');

    var status = roomStatus.status || 'available';
    var iconClass = 'fas fa-circle-check';
    var pillClass = 'pill-avail';
    var timeClass = 'tg';
    var label = 'Available';
    var canReserve = true;

    if (status === 'reserved') {
      iconClass = 'fas fa-calendar-check';
      pillClass = 'pill-res';
      timeClass = 'ty';
      label = 'Reserved';
      canReserve = false;
      addViewTempScheduleButton(card, roomStatus.classroom_id);
    } else {
      removeViewTempScheduleButton(card);
    }

    if (status === 'occupied') {
      iconClass = 'fas fa-circle-xmark';
      pillClass = 'pill-occ';
      timeClass = 'tr';
      label = 'Occupied';
      canReserve = false;
    }

    if (status === 'closed') {
      iconClass = 'fas fa-circle-xmark';
      pillClass = 'pill-occ';
      timeClass = 'tr';
      label = 'Closed';
      canReserve = false;
    }

    if (status === 'maintenance' || status === 'unavailable') {
      iconClass = 'fas fa-triangle-exclamation';
      pillClass = 'pill-maint';
      timeClass = 'tr';
      label = 'Unavailable';
      canReserve = false;
    }

    if (issueNoteBox && issueNoteText) {
      var reason = String(roomStatus.reason || '').trim();
      if ((status === 'maintenance' || status === 'unavailable') && reason !== '') {
        issueNoteText.textContent = reason;
        issueNoteBox.classList.add('is-visible');
      } else {
        issueNoteText.textContent = '';
        issueNoteBox.classList.remove('is-visible');
      }
    }

    if (statusPill) {
      statusPill.classList.remove('pill-avail', 'pill-res', 'pill-occ', 'pill-maint');
      statusPill.classList.add(pillClass);
      statusPill.innerHTML = '<i class="' + iconClass + '"></i> <span data-status-label>' + label + '</span>';
    }

    if (timeChip) {
      timeChip.classList.remove('tg', 'ty', 'tr');
      timeChip.classList.add(timeClass);
    }

    if (timeInfo) {
      timeInfo.textContent = roomStatus.time_info || 'Available all day';
    }

    if (reserveBtn) {
      reserveBtn.disabled = !canReserve;
      reserveBtn.classList.remove('on', 'off');
      reserveBtn.classList.add(canReserve ? 'on' : 'off');
    }
  }

  function addViewTempScheduleButton(card, roomId) {
    if (card.querySelector('.js-view-temp-schedule')) {
      return; // Already has the button
    }

    var cardActions = card.querySelector('.card-actions');
    if (!cardActions) return;

    // Fetch reservation data for this room
    var roomName = card.querySelector('.room-name')?.textContent || 'Room ' + roomId;
    var btn = document.createElement('button');
    btn.className = 'btn-view-temp js-view-temp-schedule';
    btn.innerHTML = '<i class="fas fa-book"></i> View Schedule';
    btn.dataset.roomId = roomId;
    btn.dataset.roomName = roomName;
    btn.type = 'button';
    
    // Fetch and store reservation data
    fetch('/api/v1/classrooms/' + roomId, {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    }).then(r => r.json()).then(payload => {
      var classroom = payload.data || {};
      var reservations = classroom.reservations || [];
      var activeRes = reservations.find(r => r.status === 'reserved');
      if (activeRes) {
        btn.dataset.reservation = JSON.stringify({
          id: activeRes.id,
          user: activeRes.user?.name || 'Unknown',
          start_at: activeRes.start_at,
          end_at: activeRes.end_at
        });
      }
    }).catch(() => {});

    cardActions.insertBefore(btn, cardActions.firstChild);
  }

  function removeViewTempScheduleButton(card) {
    var btn = card.querySelector('.js-view-temp-schedule');
    if (btn) {
      btn.remove();
    }
  }

  async function refreshRoomStatuses() {
    var now = new Date();
    var end = new Date(now.getTime() + (60 * 60 * 1000));
    var query = new URLSearchParams({
      start_at: localIso(now),
      end_at: localIso(end)
    });

    var response = await fetch('/api/v1/room-statuses?' + query.toString(), {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      return;
    }

    var payload = await response.json();
    var items = Array.isArray(payload.data) ? payload.data : [];
    items.forEach(updateRoomCard);
  }

  async function refreshMapBuildings() {
    var response = await fetch('/api/v1/map/buildings', {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      return;
    }

    var payload = await response.json();
    var buildings = Array.isArray(payload.data) ? payload.data : [];
    var byName = new Map(buildings.map(function (item) {
      return [String(item.building || '').toLowerCase(), item];
    }));

    document.querySelectorAll('.js-building-pin').forEach(function (pin) {
      var building = String(pin.dataset.building || '');
      var data = byName.get(building.toLowerCase());
      if (!data) {
        return;
      }

      var pinBox = pin.querySelector('.pin-box');
      var pinNum = pin.querySelector('[data-building-available]');
      var available = Number(data.available || 0);

      if (pinNum) {
        pinNum.textContent = String(available);
        pinNum.classList.remove('g', 'r');
        pinNum.classList.add(available > 0 ? 'g' : 'r');
      }

      if (pinBox) {
        pinBox.classList.remove('avail', 'full');
        pinBox.classList.add(available > 0 ? 'avail' : 'full');
      }
    });
  }

  async function filterRoomsByBuilding(building) {
    if (!building || building === 'N/A') {
      selectedBuilding = null;
      document.querySelectorAll('.js-building-pin').forEach(function (pin) {
        pin.classList.remove('is-selected');
      });
      document.querySelectorAll('.js-room-card').forEach(function (card) {
        card.style.display = '';
      });
      var allCount = document.querySelectorAll('.js-room-card').length;
      var countBadgeAll = document.querySelector('.rooms-count-badge');
      if (countBadgeAll) {
        countBadgeAll.textContent = allCount + ' rooms found';
      }
      return;
    }

    var isSameSelection = selectedBuilding === building;
    if (isSameSelection) {
      await filterRoomsByBuilding('');
      return;
    }

    var response = await fetch('/api/v1/map/buildings/' + encodeURIComponent(building) + '/rooms', {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      return;
    }

    var payload = await response.json();
    var rooms = Array.isArray(payload.data) ? payload.data : [];
    var roomIdSet = new Set(rooms.map(function (room) {
      return String(room.id);
    }));

    selectedBuilding = building;

    document.querySelectorAll('.js-building-pin').forEach(function (pin) {
      pin.classList.toggle('is-selected', pin.dataset.building === building);
    });

    document.querySelectorAll('.js-room-card').forEach(function (card) {
      var roomId = String(card.dataset.roomId || '');
      card.style.display = roomIdSet.has(roomId) ? '' : 'none';
    });

    rooms.forEach(function (room) {
      updateRoomCard({
        classroom_id: room.id,
        status: room.status,
        time_info: room.time_info
      });
    });

    var countBadge = document.querySelector('.rooms-count-badge');
    if (countBadge) {
      countBadge.textContent = rooms.length + ' rooms found';
    }
  }

  async function refreshRoomDetail() {
    var roomId = roomDetailRoomId ? Number(roomDetailRoomId.value) : 0;
    if (!roomId) {
      return;
    }

    var selectedDate = detailDate && detailDate.value ? detailDate.value : formatDateOnly(new Date());
    var mode = detailViewMode ? detailViewMode.value : 'day';

    var scheduleQuery = new URLSearchParams();
    if (mode === 'week') {
      scheduleQuery.set('week_start', selectedDate);
    } else {
      scheduleQuery.set('date', selectedDate);
    }

    var statusResponse = fetch('/api/v1/map/rooms/' + roomId + '/status', {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    var schedulesResponse = fetch('/api/v1/map/rooms/' + roomId + '/fixed-schedules?' + scheduleQuery.toString(), {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    var responses = await Promise.all([statusResponse, schedulesResponse]);
    if (!responses[0].ok || !responses[1].ok) {
      detailStatusBox.textContent = 'Unable to load room details right now.';
      detailStatusBox.classList.add('is-visible', 'warn');
      return;
    }

    var statusPayload = await responses[0].json();
    var schedulesPayload = await responses[1].json();

    var statusData = statusPayload.data || {};
    var scheduleData = schedulesPayload.data || {};
    var schedules = Array.isArray(scheduleData.schedules) ? scheduleData.schedules : [];
    var reservations = Array.isArray(scheduleData.reservations) ? scheduleData.reservations : [];
    var roomName = scheduleData.classroom_name || statusData.classroom_name || 'Room';
    roomDetailSub.textContent = roomName;

    var statusLabel = statusData.status_label || 'Available';
    detailStatusBox.textContent = 'Current Status: ' + statusLabel;
    detailStatusBox.classList.remove('ok', 'warn');
    detailStatusBox.classList.add('is-visible', statusData.status === 'available' ? 'ok' : 'warn');

    if (statusData.next_schedule) {
      detailNextScheduleList.innerHTML = '<div class="check-cancelled-item">'
        + '<strong>' + escapeHtml(statusData.next_schedule.course || 'Untitled Subject') + '</strong><br>'
        + '<span>' + escapeHtml(statusData.next_schedule.instructor || 'Unassigned Instructor') + '</span><br>'
        + '<span>' + escapeHtml(formatScheduleRange(statusData.next_schedule.start_at, statusData.next_schedule.end_at)) + '</span>'
        + '</div>';
    } else {
      detailNextScheduleList.innerHTML = '<div class="check-cancelled-item">No upcoming fixed schedule.</div>';
    }

    if (schedules.length === 0) {
      detailScheduleList.innerHTML = '<div class="check-cancelled-item">'
        + (mode === 'week' ? 'No fixed schedule for this week' : 'No fixed schedule for this day')
        + '</div>';
    } else {
      detailScheduleList.innerHTML = schedules.map(function (entry) {
        return '<div class="check-cancelled-item">'
          + '<strong>' + escapeHtml(entry.course || 'Untitled Subject') + '</strong> '
          + '<span>(' + escapeHtml(entry.status || 'scheduled') + ')</span><br>'
          + '<span>' + escapeHtml(entry.instructor || 'Unassigned Instructor') + '</span><br>'
          + '<span>' + escapeHtml(formatScheduleRange(entry.start_at, entry.end_at)) + '</span>'
          + '</div>';
      }).join('');
    }

    detailReservationList.innerHTML = reservations.length
      ? reservations.map(function (reservation) {
        return '<div class="check-cancelled-item">'
          + '<strong>' + escapeHtml(reservation.is_mine ? 'Your reservation' : (reservation.reserved_by || 'Faculty')) + '</strong> '
          + '<span>(' + escapeHtml(reservation.status || 'reserved') + ')</span><br>'
          + '<span>' + escapeHtml(formatScheduleRange(reservation.start_at, reservation.end_at)) + '</span>'
          + (reservation.notes ? '<br><span>' + escapeHtml(reservation.notes) + '</span>' : '')
          + '</div>';
      }).join('')
      : '<div class="check-cancelled-item">No reservations for this period.</div>';
  }

  async function createReservation(roomId, startAt, endAt, notes) {
    reserveSubmitBtn.disabled = true;
    reserveError.textContent = '';
    reserveError.classList.remove('is-visible');

    if (!startAt || !endAt) {
      reserveError.textContent = 'Start and end date/time are required.';
      reserveError.classList.add('is-visible');
      reserveSubmitBtn.disabled = false;
      return;
    }

    if (typeof window.showGlobalLoading === 'function') {
      window.showGlobalLoading('Creating reservation...');
    }

    try {
        var response;
        try {
          response = await fetch('/api/v1/reservations', {
              credentials: 'same-origin',
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                classroom_id: Number(roomId),
                start_at: startAt,
                end_at: endAt,
                notes: notes
              })
            });
        } catch (fetchErr) {
          reserveError.textContent = 'Network error. Please check your connection and try again.';
          reserveError.classList.add('is-visible');
          return;
        }

        var payload = await response.json().catch(function () { return {}; });

        if (!response.ok) {
          // Prefer explicit server message; fall back to validation errors if present.
          var msg = payload.message || 'Room is already reserved/occupied for the selected time.';
          if (payload.errors) {
            if (Array.isArray(payload.errors) && payload.errors.length) {
              msg = payload.errors[0];
            } else if (typeof payload.errors === 'object') {
              var first = Object.values(payload.errors)[0];
              if (Array.isArray(first)) msg = first[0]; else msg = String(first);
            }
          }
          reserveError.textContent = msg;
          reserveError.classList.add('is-visible');
          return;
        }

        closeReserveOverlay();
        showToast(payload.message || 'Reservation created successfully.', 'success');
        refreshRoomStatuses();
    } finally {
        if (typeof window.hideGlobalLoading === 'function') {
          window.hideGlobalLoading();
        }
        reserveSubmitBtn.disabled = false;
    }
  }

  function renderCancelledClasses(classes) {
    if (!checkCancelledList || !checkCancelledBox) {
      return;
    }

    if (!Array.isArray(classes) || classes.length === 0) {
      checkCancelledList.innerHTML = '';
      checkCancelledBox.classList.remove('is-visible');
      return;
    }

    checkCancelledList.innerHTML = classes.map(function (item) {
      var startLabel = item.start_at ? new Date(item.start_at).toLocaleString() : '-';
      var endLabel = item.end_at ? new Date(item.end_at).toLocaleString() : '-';
      var code = item.course_code ? ('[' + item.course_code + '] ') : '';

      return '<div class="check-cancelled-item">'
        + '<strong>' + code + (item.subject || 'Untitled Subject') + '</strong><br>'
        + '<span>' + (item.instructor || 'Unassigned') + '</span><br>'
        + '<span>' + startLabel + ' - ' + endLabel + '</span>'
        + '</div>';
    }).join('');

    checkCancelledBox.classList.add('is-visible');
  }

  async function runAvailabilityCheck(roomId, startAt, endAt) {
    checkSubmitBtn.disabled = true;
    checkResultBox.textContent = '';
    checkResultBox.classList.remove('is-visible', 'ok', 'warn');

    try {
      var query = new URLSearchParams({
        classroom_id: String(roomId),
        start_at: startAt,
        end_at: endAt,
      });

      var response = await fetch('/api/v1/room-availability/check?' + query.toString(), {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      var payload = await response.json().catch(function () {
        return {};
      });

      if (!response.ok) {
        checkResultBox.textContent = payload.message || 'Unable to check availability.';
        checkResultBox.classList.add('is-visible', 'warn');
        renderCancelledClasses([]);
        return;
      }

      var data = payload.data || {};
      var isAvailable = Boolean(data.available);
      checkResultBox.textContent = isAvailable
        ? 'Room is available for the selected time.'
        : (data.reason || 'Room is not available for the selected time.');
      checkResultBox.classList.add('is-visible', isAvailable ? 'ok' : 'warn');
      renderCancelledClasses(data.cancelled_classes || []);
    } finally {
      checkSubmitBtn.disabled = false;
    }
  }

  if (reserveForm) {
    reserveForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var roomId = Number(reserveRoomId.value);
      var startAt = reserveStartAt.value;
      var endAt = reserveEndAt.value;
      var notes = reserveNotes.value || '';

      createReservation(roomId, startAt, endAt, notes);
    });
  }

  document.getElementById('reserveCloseBtn')?.addEventListener('click', closeReserveOverlay);
  document.getElementById('reserveCancelBtn')?.addEventListener('click', closeReserveOverlay);
  document.getElementById('checkCloseBtn')?.addEventListener('click', closeCheckOverlay);
  document.getElementById('checkCancelBtn')?.addEventListener('click', closeCheckOverlay);

  if (reserveOverlay) {
    reserveOverlay.addEventListener('click', function (event) {
      if (event.target === reserveOverlay) {
        closeReserveOverlay();
      }
    });
  }

  if (checkOverlay) {
    checkOverlay.addEventListener('click', function (event) {
      if (event.target === checkOverlay) {
        closeCheckOverlay();
      }
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
      return;
    }

    if (reserveOverlay && reserveOverlay.classList.contains('is-open')) {
      closeReserveOverlay();
      return;
    }

    if (checkOverlay && checkOverlay.classList.contains('is-open')) {
      closeCheckOverlay();
    }
  });

  if (checkForm) {
    checkForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var roomId = Number(checkRoomId.value);
      var startAt = checkStartAt.value;
      var endAt = checkEndAt.value;

      if (!startAt || !endAt) {
        checkResultBox.textContent = 'Start and end date/time are required.';
        checkResultBox.classList.add('is-visible', 'warn');
        return;
      }

      runAvailabilityCheck(roomId, startAt, endAt);
    });
  }

  document.querySelectorAll('.js-check-btn').forEach(function (button) {
    button.addEventListener('click', function () {
      openCheckOverlay(button.dataset.roomId, button.dataset.roomName || 'Room');
    });
  });

  document.querySelectorAll('.js-reserve-btn').forEach(function (button) {
    button.addEventListener('click', function () {
      if (button.disabled) {
        return;
      }

      openReserveOverlay(button.dataset.roomId, button.dataset.roomName || 'Room');
    });
  });

  reservationList?.addEventListener('click', function (event) {
    var button = event.target.closest('.js-cancel-reservation');
    if (button) cancelReservation(button.dataset.reservationId, button);
  });
  refreshReservationsBtn?.addEventListener('click', loadMyReservations);
  findFreeRoomsBtn?.addEventListener('click', findFreeRooms);

  document.querySelectorAll('.js-building-pin').forEach(function (pin) {
    pin.addEventListener('click', function () {
      filterRoomsByBuilding(pin.dataset.building || '');
    });
  });

  document.querySelectorAll('.js-room-card').forEach(function (card) {
    card.addEventListener('click', function (event) {
      if (event.target.closest('.btn-check') || event.target.closest('.btn-res')) {
        return;
      }

      openRoomDetailOverlay(card.dataset.roomId, card.querySelector('.room-name')?.textContent || 'Room');
    });
  });

  document.getElementById('roomDetailCloseBtn')?.addEventListener('click', closeRoomDetailOverlay);
  document.getElementById('roomDetailCloseActionBtn')?.addEventListener('click', closeRoomDetailOverlay);
  detailViewMode?.addEventListener('change', refreshRoomDetail);
  detailDate?.addEventListener('change', refreshRoomDetail);

  if (roomDetailOverlay) {
    roomDetailOverlay.addEventListener('click', function (event) {
      if (event.target === roomDetailOverlay) {
        closeRoomDetailOverlay();
      }
    });
  }

  refreshRoomStatuses();
  refreshMapBuildings();
  setBookingDefaults();
  loadMyReservations();
  setInterval(function () {
    refreshRoomStatuses();
    refreshMapBuildings();
  }, 15000);

  // Temporary Schedule Modal Handlers
  var tempScheduleOverlay = document.getElementById('tempScheduleOverlay');
  var tempScheduleList = document.getElementById('tempScheduleList');
  var tempScheduleRoomName = document.getElementById('tempScheduleRoomName');
  var tempScheduleReservationInfo = document.getElementById('tempScheduleReservationInfo');
  var tempScheduleError = document.getElementById('tempScheduleError');

  function openTempScheduleOverlay(roomId, roomName, reservation) {
    if (!tempScheduleOverlay) return;
    
    tempScheduleRoomName.textContent = roomName;
    tempScheduleReservationInfo.innerHTML = '';
    tempScheduleList.innerHTML = '<div class="check-cancelled-item">Loading temporary schedules...</div>';
    tempScheduleError.textContent = '';
    tempScheduleError.classList.remove('is-visible');
    
    tempScheduleOverlay.classList.add('is-open');
    tempScheduleOverlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    
    fetchTempSchedules(roomId, reservation);
  }

  function closeTempScheduleOverlay() {
    if (!tempScheduleOverlay) return;
    tempScheduleOverlay.classList.remove('is-open');
    tempScheduleOverlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  async function fetchTempSchedules(roomId, reservation) {
    if (!reservation || !reservation.id) {
      tempScheduleError.textContent = 'No active reservation found for this room.';
      tempScheduleError.classList.add('is-visible');
      tempScheduleList.innerHTML = '';
      return;
    }

    try {
      var response = await fetch('/api/v1/schedules/reservation/temporary?reservation_id=' + reservation.id + '&classroom_id=' + roomId, {
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        tempScheduleError.textContent = 'Failed to load temporary schedules.';
        tempScheduleError.classList.add('is-visible');
        tempScheduleList.innerHTML = '';
        return;
      }

      var payload = await response.json();
      var data = payload.data || {};
      var reservationData = data.reservation || {};
      var schedules = data.schedules || [];

      // Display reservation info
      var reservStart = new Date(reservationData.start_at).toLocaleString();
      var reservEnd = new Date(reservationData.end_at).toLocaleString();
      tempScheduleReservationInfo.innerHTML = '<div class="temp-sched-info">'
        + '<strong>Reserved by:</strong> ' + escapeHtml(reservationData.user) + '<br>'
        + '<strong>Time:</strong> ' + escapeHtml(reservStart) + ' - ' + escapeHtml(reservEnd)
        + '</div>';

      if (schedules.length === 0) {
        tempScheduleList.innerHTML = '<div class="check-cancelled-item">No temporary schedules during this reservation.</div>';
        return;
      }

      // Display schedules
      tempScheduleList.innerHTML = schedules.map(function (schedule) {
        var scheduleStart = new Date(schedule.start_at).toLocaleString();
        var scheduleEnd = new Date(schedule.end_at).toLocaleString();
        return '<div class="check-cancelled-item">'
          + '<strong>' + escapeHtml(schedule.course?.title || 'Untitled Course') + '</strong><br>'
          + '<span>Code: ' + escapeHtml(schedule.course?.code || 'N/A') + '</span><br>'
          + '<span>Status: ' + escapeHtml(schedule.status || 'scheduled') + '</span><br>'
          + '<span>Enrolled: ' + (schedule.enrolled || 0) + ' students</span><br>'
          + '<span>' + escapeHtml(scheduleStart) + ' - ' + escapeHtml(scheduleEnd) + '</span>'
          + '</div>';
      }).join('');
    } catch (error) {
      tempScheduleError.textContent = 'Error loading temporary schedules: ' + error.message;
      tempScheduleError.classList.add('is-visible');
      tempScheduleList.innerHTML = '';
    }
  }

  // Add click handlers for view temp schedule buttons
  document.addEventListener('click', function (e) {
    if (e.target.closest('.js-view-temp-schedule')) {
      var btn = e.target.closest('.js-view-temp-schedule');
      var roomId = Number(btn.dataset.roomId);
      var roomName = btn.dataset.roomName;
      var reservation = JSON.parse(btn.dataset.reservation || 'null');
      openTempScheduleOverlay(roomId, roomName, reservation);
    }
  });

  // Close temp schedule overlay
  var tempScheduleCloseBtn = document.getElementById('tempScheduleCloseBtn');
  if (tempScheduleCloseBtn) {
    tempScheduleCloseBtn.addEventListener('click', closeTempScheduleOverlay);
  }
});
</script>

@include('partials.loading-feedback')

<!-- Temporary Schedule Modal -->
<div class="reserve-overlay" id="tempScheduleOverlay" aria-hidden="true">
  <div class="reserve-modal" role="dialog" aria-modal="true" aria-labelledby="tempScheduleTitle">
    <div class="reserve-head">
      <div>
        <div class="reserve-title" id="tempScheduleTitle">Temporary Schedules</div>
        <div class="reserve-sub" id="tempScheduleRoomName">Loading...</div>
      </div>
      <button type="button" class="reserve-close" id="tempScheduleCloseBtn" aria-label="Close temporary schedules dialog">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <div class="reserve-body">
      <div id="tempScheduleReservationInfo"></div>
      <div class="reserve-error" id="tempScheduleError"></div>
      <div class="temp-schedule-list" id="tempScheduleList"></div>
      <div class="reserve-actions" style="margin-top: 10px;">
        <button type="button" class="reserve-cancel" onclick="document.getElementById('tempScheduleOverlay').classList.remove('is-open'); document.getElementById('tempScheduleOverlay').setAttribute('aria-hidden', 'true'); document.body.style.overflow = '';">Close</button>
      </div>
    </div>
  </div>
</div>

@include('partials.loading-feedback')
@include('frontend.faculty.partials.notifications-widget')
</body>
</html>