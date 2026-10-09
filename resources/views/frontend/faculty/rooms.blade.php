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
<title>Room Availability â€“ SmartDoor</title>
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
  --bg:         #f8f9fb;
  --border:     #e8eaef;
  --text:       #111827;
  --text-2:     #4b5563;
  --text-3:     #9ca3af;
  --text-4:     #d1d5db;
  --accent:     #1a2b6d;
  --accent2:    #4a6cf7;
  --green:      #16a34a;
  --green-bg:   #f0fdf4;
  --green-bd:   #bbf7d0;
  --green-text: #15803d;
  --red:        #dc2626;
  --red-bg:     #fef2f2;
  --red-bd:     #fca5a5;
  --orange:     #f59e0b;
  --shadow-sm:  0 1px 3px rgba(0,0,0,.04);
  --shadow-md:  0 4px 14px rgba(0,0,0,.06);
  --r-xs: 6px; --r-sm: 8px; --r: 12px; --r-lg: 14px;
  --sidebar-w: 230px;
  --fh: 'Plus Jakarta Sans',sans-serif;
  --fb: 'DM Sans',sans-serif;
}

body { font-family:var(--fb); background:var(--bg); color:var(--text); min-height:100vh; display:flex; -webkit-font-smoothing:antialiased; }

/* â•â• SIDEBAR (UNCHANGED) â•â• */
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

/* â•â• MAIN â•â• */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* â”€â”€ TOPBAR â”€â”€ */
.topbar{background:var(--white);border-bottom:1px solid var(--border);padding:0 32px;height:56px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.topbar-search{flex:1;max-width:420px;display:none!important;display:flex;align-items:center;gap:10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-sm);padding:8px 16px;transition:border-color .2s,box-shadow .2s}
.topbar-search:focus-within{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09)}
.topbar-search i{color:var(--text-4);font-size:.88rem}
.topbar-search input{border:none;outline:none;background:transparent;font-size:.88rem;font-family:var(--fb);color:var(--text);width:100%}
.topbar-search input::placeholder{color:var(--text-4)}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:16px}
.topbar-profile{position:relative;display:flex;align-items:center;gap:10px;cursor:pointer}
.topbar-profile-name{font-size:.88rem;font-weight:700;color:var(--text);line-height:1.2}
.topbar-profile-role{font-size:.75rem;color:var(--text-3)}
.topbar-avatar{width:34px;height:34px;border-radius:50%;background:#eef2ff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:var(--accent2);overflow:hidden}
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

/* â•â• CONTENT â•â• */
.content{padding:28px 32px 52px;display:flex;flex-direction:column;gap:22px}

/* â”€â”€ PAGE HEADER â”€â”€ */
/* -- HERO BANNER -- */
.hero-banner{position:relative;border-radius:0;overflow:hidden;min-height:250px;display:flex;flex-direction:column;justify-content:flex-end;background:linear-gradient(135deg,#0b1640 0%,rgba(26,47,128,.78) 40%,rgba(11,22,64,.12) 100%),url('/images/map.png') center/cover no-repeat;margin:-28px -32px -10px}
.hero-banner::before{content:'';position:absolute;inset:0;background:linear-gradient(to right,rgba(11,22,64,.92) 0%,rgba(11,22,64,.7) 45%,rgba(11,22,64,.2) 75%,transparent 100%);z-index:1}
.hero-content{position:relative;z-index:2;padding:28px 32px 24px}
.hero-brand{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.hero-logo{width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.hero-logo i{font-size:1.1rem;color:var(--yellow)}
.hero-brand-text{display:flex;flex-direction:column}
.hero-brand-name{font-family:var(--fh);font-size:.88rem;font-weight:700;color:#fff;letter-spacing:.06em;text-transform:uppercase}
.hero-brand-campus{font-size:.68rem;color:rgba(255,255,255,.5);letter-spacing:.04em;margin-top:1px}
.hero-label{font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--yellow);margin-bottom:6px}
.hero-title{font-family:var(--fh);font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-.02em;margin-bottom:4px}
.hero-sub{font-size:.82rem;color:rgba(255,255,255,.6);max-width:440px}
.page-header{display:none}
.page-title{display:none}
.page-sub{display:none}


.header-actions{display:flex;gap:10px;align-items:center}
.btn-outline{display:flex;align-items:center;gap:7px;padding:9px 16px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:var(--white);font-family:var(--fb);font-size:.82rem;font-weight:600;color:var(--text-2);cursor:pointer;transition:background .15s}
.btn-outline:hover{background:var(--bg)}
.btn-primary{display:flex;align-items:center;gap:7px;padding:9px 18px;border:none;border-radius:var(--r-sm);background:var(--accent);color:#fff;font-family:var(--fb);font-size:.82rem;font-weight:600;cursor:pointer;transition:opacity .15s}
.btn-primary:hover{opacity:.88}
.booking-toolbar{background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px 20px;display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:14px;align-items:end;box-shadow:0 4px 16px rgba(0,0,0,.08);position:relative;z-index:10}
.booking-toolbar-copy{align-self:center}
.booking-toolbar-title{font-family:var(--fh);font-size:.95rem;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px}
.booking-toolbar-title i{color:var(--accent2);font-size:.86rem}
.booking-toolbar-sub{font-size:.75rem;line-height:1.45;color:var(--text-3);margin-top:5px;max-width:230px}
.booking-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.booking-field label{font-size:.68rem;font-weight:800;color:var(--text-3);letter-spacing:.05em;text-transform:uppercase}
.booking-field input{height:42px;border:1px solid var(--border);border-radius:var(--r-sm);padding:0 11px;background:#fbfcfe;font: .83rem var(--fb);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s,background .15s}
.booking-field input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09);background:#fff}
.booking-search-btn{height:42px;white-space:nowrap;padding-inline:17px}
.booking-result{grid-column:2/-1;width:100%;display:none;border-radius:8px;padding:8px 10px;font-size:.76rem;font-weight:600}
.booking-result.is-visible{display:block}.booking-result.ok{background:var(--green-bg);color:var(--green-text)}.booking-result.warn{background:var(--red-bg);color:#991b1b}
.my-reservations{background:linear-gradient(135deg,#fff 0%,#fbfcff 100%);border:1px solid var(--border);border-radius:var(--r-lg);padding:22px 24px;box-shadow:var(--shadow-sm)}
.my-reservations-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px}
.my-reservations-heading{display:flex;align-items:center;gap:11px}
.my-reservations-icon{width:34px;height:34px;border-radius:10px;background:#eef2ff;color:var(--accent2);display:flex;align-items:center;justify-content:center;font-size:.85rem}
.my-reservations-title{font-family:var(--fh);font-size:1rem;font-weight:800;color:var(--text)}
.my-reservations-sub{font-size:.75rem;color:var(--text-3);margin-top:2px}
.reservation-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.reservation-item{display:flex;align-items:center;gap:13px;min-width:0;padding:13px 14px;background:var(--white);border:1px solid var(--border);border-radius:var(--r);box-shadow:0 2px 7px rgba(15,23,41,.035);transition:border-color .16s,box-shadow .16s,transform .16s}
.reservation-item:hover{border-color:#c7d2fe;box-shadow:0 7px 16px rgba(15,23,41,.07);transform:translateY(-1px)}
.reservation-date{width:48px;min-width:48px;padding:7px 4px;text-align:center;background:#f2f5ff;border:1px solid #dbe3ff;border-radius:9px;color:var(--accent)}
.reservation-date-day{display:block;font-size:.62rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--accent2)}
.reservation-date-number{display:block;font-family:var(--fh);font-size:1.15rem;font-weight:800;line-height:1.1;margin-top:2px}
.reservation-details{flex:1;min-width:0}
.reservation-room{font-weight:800;font-size:.84rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.reservation-location{font-size:.7rem;color:var(--text-3);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.reservation-time{font-size:.75rem;color:var(--text-2);margin-top:7px;display:flex;align-items:center;gap:5px}.reservation-time i{color:var(--accent2);font-size:.68rem}
.reservation-side{display:flex;flex-direction:column;align-items:flex-end;gap:10px;flex-shrink:0}
.reservation-status{font-size:.64rem;font-weight:800;color:var(--green-text);background:var(--green-bg);border:1px solid var(--green-bd);border-radius:999px;padding:4px 8px;text-transform:uppercase;letter-spacing:.04em}
.reservation-status.pending{color:#b45309;background:#fffbeb;border-color:#fde68a}.reservation-status.cancelled{color:var(--red);background:var(--red-bg);border-color:var(--red-bd)}
.reservation-cancel{border:0;background:transparent;color:var(--text-3);border-radius:7px;padding:5px 7px;cursor:pointer;font:600 .72rem var(--fb);white-space:nowrap}.reservation-cancel:hover{background:var(--red-bg);color:var(--red)}.reservation-cancel:disabled{opacity:.55;cursor:not-allowed}
.reservation-empty{grid-column:1/-1;display:flex;align-items:center;gap:9px;padding:16px;border:1px dashed var(--border);border-radius:var(--r-sm);color:var(--text-3);font-size:.78rem}.reservation-empty i{color:var(--text-4)}
.reservation-skeleton{border:1px solid var(--border);border-radius:var(--r);padding:14px;display:flex;align-items:center;gap:13px}
@keyframes skeletonShimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
@media(prefers-reduced-motion:reduce){.sk-bar{animation:none!important;background:#f0f2f5!important}}
.sk-bar{display:block;border-radius:5px;background:linear-gradient(90deg,#f0f2f5 25%,#e8eaef 38%,#f0f2f5 63%);background-size:200% 100%;animation:skeletonShimmer 1.6s ease-in-out infinite}
.sk-bar.circle{border-radius:50%}
.reservation-skeleton .sk-date{width:48px;height:52px;border-radius:9px}
.reservation-skeleton .sk-lines{flex:1;display:flex;flex-direction:column;gap:6px}
.reservation-skeleton .sk-line{height:10px;border-radius:5px}
.reservation-skeleton .sk-pill{width:60px;height:22px;border-radius:12px;margin-left:auto;flex-shrink:0}
@media(max-width:1000px){.booking-toolbar{grid-template-columns:1fr 1fr;align-items:end}.booking-toolbar-copy{grid-column:1/-1}.booking-toolbar-sub{max-width:none}.booking-result{grid-column:1/-1}}
@media(max-width:768px){.reservation-list{grid-template-columns:1fr}.booking-toolbar{grid-template-columns:1fr;padding:16px}.booking-toolbar-copy,.booking-result{grid-column:auto}.booking-field{min-width:100%}.booking-search-btn{width:100%}.my-reservations{padding:18px}.my-reservations-head{align-items:flex-start}.my-reservations-sub{max-width:190px}.reservation-item{align-items:flex-start}.reservation-side{margin-left:auto}}

/* â”€â”€ SEARCH + FILTER â”€â”€ */
.search-filter-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.search-wrap{flex:1;position:relative;min-width:200px}
.search-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-4);font-size:.8rem;pointer-events:none}
.search-wrap input{width:100%;padding:9px 14px 9px 34px;border:1px solid var(--border);border-radius:var(--r-sm);background:var(--white);font-size:.85rem;font-family:var(--fb);color:var(--text);outline:none;transition:border .15s,box-shadow .15s}
.search-wrap input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09);background:#fff}
.search-wrap input::placeholder{color:var(--text-4)}
.sf-divider{width:1px;height:26px;background:var(--border);flex-shrink:0}
.filter-label{font-size:.75rem;font-weight:700;color:var(--text-3);white-space:nowrap}
.filter-pills{display:flex;gap:5px}
.filter-btn{padding:6px 14px;border-radius:var(--r-xs);font-size:.78rem;font-weight:600;font-family:var(--fb);border:1px solid var(--border);cursor:pointer;color:var(--text-3);background:var(--white);text-decoration:none;transition:all .15s}
.filter-btn:hover{background:#e8ecfb;color:var(--accent2);border-color:#c7d2fe}
.filter-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}

/* â”€â”€ CAMPUS MAP â”€â”€ */
/* -- CAMPUS MAP -- */
.map-card{background:var(--white);border-radius:var(--r);border:1px solid var(--border);overflow:hidden}
.map-card-header{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--white)}
.map-title-group{display:flex;align-items:center;gap:10px}
.map-icon{width:32px;height:32px;border-radius:8px;background:var(--navy);color:var(--yellow);display:flex;align-items:center;justify-content:center;font-size:.78rem}
.map-title{font-family:var(--fh);font-size:.88rem;font-weight:700;color:var(--text)}
.map-sub{font-size:.72rem;color:var(--text-3);margin-top:1px}
.live-badge{display:flex;align-items:center;gap:5px;font-size:.7rem;font-weight:600;color:var(--green);background:var(--green-bg);padding:4px 10px;border-radius:var(--r-xs);border:1px solid var(--green-bd)}
.live-dot{width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulse 1.6s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.map-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:0;background:#1a2340 url('/images/map.png') center/cover no-repeat;min-height:220px;position:relative}
.map-grid::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(11,22,64,.55) 0%,rgba(11,22,64,.35) 100%);pointer-events:none;z-index:0}
.map-cell{background:transparent;display:flex;align-items:center;justify-content:center;min-height:100px;position:relative;z-index:1}
.building-pin{display:flex;flex-direction:column;align-items:center;gap:6px;transition:transform .2s}
.building-pin.js-building-pin{cursor:pointer}
.building-pin.js-building-pin:hover{transform:scale(1.08)}
.building-pin.is-selected .pin-box{outline:2.5px solid var(--yellow);outline-offset:3px}
.pin-box{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;position:relative;box-shadow:0 4px 20px rgba(0,0,0,.3);border:2px solid rgba(255,255,255,.3);backdrop-filter:blur(2px);transition:all .2s}
.pin-box.avail{background:rgba(22,163,74,.9)}
.pin-box.full{background:rgba(220,38,38,.9)}
.pin-box:hover{box-shadow:0 6px 28px rgba(0,0,0,.4);transform:translateY(-2px)}
.pin-num{position:absolute;top:-8px;right:-8px;width:22px;height:22px;border:2px solid #fff;border-radius:50%;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.2)}
.pin-num.g{background:#fff;color:var(--green)}
.pin-num.r{background:#fff;color:var(--red)}
.building-lbl{font-size:.72rem;font-weight:700;color:#fff;text-align:center;max-width:90px;line-height:1.3;text-shadow:0 1px 4px rgba(0,0,0,.5);letter-spacing:.02em}
.map-north{position:absolute;top:12px;right:12px;width:28px;height:28px;background:rgba(255,255,255,.95);border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.15);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--navy);z-index:5;backdrop-filter:blur(4px)}
.map-legend{position:absolute;bottom:12px;left:12px;background:rgba(11,22,64,.85);backdrop-filter:blur(8px);border-radius:8px;padding:10px 14px;z-index:5;border:1px solid rgba(255,255,255,.1)}
.legend-row{display:flex;align-items:center;gap:7px;font-size:.7rem;font-weight:600;color:rgba(255,255,255,.85);padding:2px 0}
.legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;box-shadow:0 0 6px currentColor}

/* â”€â”€ ROOMS SECTION â”€â”€ */
.rooms-header{display:flex;align-items:center;justify-content:space-between}
.rooms-section-title{font-family:var(--fh);font-size:.95rem;font-weight:700;color:var(--text)}
.rooms-count-badge{font-size:.76rem;color:var(--text-3);background:var(--white);padding:4px 12px;border-radius:var(--r-xs);border:1px solid var(--border);font-weight:500}

/* -- ROOM CARDS -- */
.rooms-grid{display:flex;flex-direction:column;gap:14px}
.room-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r);transition:border-color .18s;display:flex;flex-direction:row;overflow:hidden}
.room-card:hover{border-color:var(--text-4)}
.room-thumb{width:170px;min-height:150px;background:linear-gradient(135deg,#eef2ff 0%,#e0e7ff 100%);display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;overflow:hidden}
.room-thumb::after{content:'';position:absolute;inset:45% 0 0;background:linear-gradient(180deg,transparent,rgba(11,22,64,.58));pointer-events:none}
.room-thumb-image{width:100%;height:100%;min-height:150px;display:block;object-fit:cover}
.room-thumb i{position:absolute;z-index:0;font-size:2rem;color:var(--accent2);opacity:.3}
.room-thumb-label{position:absolute;bottom:10px;left:10px;right:10px;background:rgba(255,255,255,.9);backdrop-filter:blur(4px);border-radius:var(--r-xs);padding:5px 8px;font-size:.64rem;font-weight:600;color:var(--text-2);text-align:center}
.card-body{flex:1;padding:16px 20px;display:flex;flex-direction:column;min-width:0}
.card-right{width:170px;padding:16px;display:flex;flex-direction:column;align-items:stretch;justify-content:space-between;border-left:1px solid var(--border);flex-shrink:0;text-align:center}
.card-right .card-actions{flex-direction:column;width:100%;gap:6px}
.card-right .btn-check,.card-right .btn-res{width:100%;flex:none;font-size:.76rem;padding:9px 10px}
.card-strip{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.room-id{font-size:.66rem;font-weight:500;color:var(--text-4)}
.status-pill{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:600;padding:3px 9px;border-radius:var(--r-xs)}
.pill-avail{background:var(--green-bg);color:var(--green-text);border:1px solid var(--green-bd)}
.pill-res{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.pill-occ{background:var(--red-bg);color:var(--red);border:1px solid var(--red-bd)}
.pill-maint{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.room-name{font-family:var(--fh);font-size:1rem;font-weight:700;color:var(--text)}
.room-loc{display:flex;align-items:center;gap:5px;font-size:.76rem;color:var(--text-3);margin-bottom:8px}
.room-loc i{font-size:.6rem;color:var(--text-4)}
.room-meta{display:flex;gap:0;margin-bottom:10px}
.meta-chip{display:flex;align-items:center;gap:4px;font-size:.74rem;font-weight:500;color:var(--text-3);background:var(--bg);border:1px solid var(--border);padding:4px 10px}
.meta-chip:first-child{border-radius:var(--r-xs) 0 0 var(--r-xs);border-right:none}
.meta-chip:last-child{border-radius:0 var(--r-xs) var(--r-xs) 0}
.meta-chip i{font-size:.6rem;color:var(--text-4)}
.meta-chip.tg{background:var(--green-bg);color:var(--green-text);border-color:var(--green-bd);font-weight:600}
.meta-chip.tg i{color:var(--green)}
.meta-chip.ty{background:#fff7ed;color:#c2410c;border-color:#fed7aa;font-weight:600}
.meta-chip.ty i{color:#c2410c}
.meta-chip.tr{background:var(--red-bg);color:var(--red);border-color:var(--red-bd);font-weight:600}
.meta-chip.tr i{color:var(--red)}
.am-row{display:flex;flex-wrap:wrap;gap:4px;margin-top:auto}
.am-chip{display:inline-flex;align-items:center;gap:4px;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-xs);padding:3px 8px;font-size:.7rem;color:var(--text-3);font-weight:500}
.issue-note{display:none;margin-top:6px;padding:7px 9px;border-radius:var(--r-xs);background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.72rem;font-weight:600;line-height:1.3}
.issue-note.is-visible{display:block}
.card-actions{display:flex;gap:6px}
.btn-check,.btn-res{flex:1;padding:9px;border-radius:var(--r-sm);font-size:.78rem;font-weight:600;font-family:var(--fb);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:all .15s}
.btn-check{background:var(--bg);color:var(--text-2);border:1px solid var(--border)}
.btn-check:hover{background:#eef2ff;color:var(--accent2);border-color:#c7d2fe}
.btn-res.on{background:var(--accent);color:#fff}
.btn-res.on:hover{background:var(--navy-mid)}
.btn-res.off{background:var(--bg);color:var(--text-4);cursor:not-allowed;border:1px solid var(--border)}
.btn-view-temp{flex:1;padding:9px;border-radius:var(--r-sm);font-size:.78rem;font-weight:600;font-family:var(--fb);border:1px solid #10b981;background:#f0fdf4;color:#059669;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:all .15s}
.btn-view-temp:hover{background:#dcfce7}

/* â”€â”€ CHECK AVAILABILITY OVERLAY â”€â”€ */
.check-result{display:none;font-size:.77rem;border-radius:8px;padding:8px 10px;border:1px solid var(--border);background:#f8fafc;color:var(--text-2)}
.check-result.is-visible{display:block}
.check-result.ok{background:#ecfdf5;border-color:#86efac;color:#166534}
.check-result.warn{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
.check-cancelled{display:none;border:1px solid var(--border);border-radius:8px;background:#fff;padding:9px 10px}
.check-cancelled.is-visible{display:block}
.check-cancelled-title{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-3);margin-bottom:6px}
.check-cancelled-list{display:flex;flex-direction:column;gap:6px}
.check-cancelled-item{font-size:.76rem;color:var(--text-2);background:#f9fafb;border:1px solid var(--border);border-radius:7px;padding:7px 8px}

/* â”€â”€ ANIMATIONS â”€â”€ */
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.hero-banner      {animation:fadeUp .35s both .02s}
.search-filter-row{animation:fadeUp .35s both .07s}
.map-card         {animation:fadeUp .35s both .12s}
.rooms-header     {animation:fadeUp .35s both .17s}
.rooms-grid       {animation:fadeUp .35s both .22s}


@media(max-width:900px){.content{padding:20px 18px 40px}.hero-banner{margin:-20px -18px -10px}}
@media(max-width:768px){:root{--sidebar-w:0px}.sidebar{display:none}}
@media(max-width:900px){.room-card{flex-direction:column}.room-thumb{width:100%;min-height:100px}.card-right{width:100%;border-left:none;border-top:1px solid var(--border);flex-direction:row;align-items:center;padding:12px 16px}.card-right .card-actions{flex-direction:row}.card-right .btn-check,.card-right .btn-res{flex:1;width:auto}}

/* â”€â”€ RESERVE OVERLAY â”€â”€ */
.reserve-overlay{position:fixed;inset:0;background:rgba(0,0,0,.3);backdrop-filter:blur(2px);display:none;align-items:center;justify-content:center;z-index:2100;padding:18px}
.reserve-overlay.is-open{display:flex}
.reserve-modal{width:min(440px,100%);background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:0 16px 40px rgba(0,0,0,.12);overflow:hidden}
.reserve-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);background:var(--white)}
.reserve-title{font-family:var(--fh);font-size:.95rem;font-weight:800;color:var(--text)}
.reserve-sub{font-size:.75rem;color:var(--text-3);margin-top:2px}
.reserve-close{width:28px;height:28px;border-radius:var(--r-xs);border:1px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;color:var(--text-3);cursor:pointer;transition:all .15s}
.reserve-close:hover{background:var(--bg);color:var(--text-2)}
.reserve-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:10px}
.reserve-group{display:flex;flex-direction:column;gap:5px}
.reserve-label{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-3)}
.reserve-input{height:38px;border:1px solid var(--border);border-radius:var(--r-sm);padding:0 11px;font-size:.83rem;font-family:var(--fb);color:var(--text);background:var(--bg);outline:none;transition:border-color .15s,box-shadow .15s,background .15s}
.reserve-input:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.09);background:#fff}
.reserve-textarea{min-height:72px;resize:vertical;padding:10px 11px}
.reserve-error{display:none;font-size:.77rem;color:var(--red);background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:8px 10px}
.reserve-error.is-visible{display:block}
.reserve-actions{display:flex;gap:8px;padding-top:2px}
.reserve-cancel,.reserve-submit{flex:1;height:39px;border-radius:9px;font-size:.82rem;font-weight:700;font-family:var(--fb);cursor:pointer;transition:all .15s}
.reserve-cancel{border:1.5px solid var(--border);background:var(--white);color:var(--text-2)}
.reserve-cancel:hover{background:var(--bg)}
.reserve-submit{border:none;background:var(--accent);color:#fff}
.reserve-submit:hover{background:var(--navy-mid)}
.reserve-submit:disabled{opacity:.7;cursor:not-allowed}

.temp-sched-info{padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--r-sm);font-size:.82rem;margin-bottom:12px;line-height:1.5}
.temp-sched-info strong{color:var(--text-2);font-weight:600}
.temp-schedule-list{max-height:400px;overflow-y:auto}
.temp-schedule-list .check-cancelled-item{margin-bottom:10px}

/* â”€â”€ TOAST â”€â”€ */
.toast-wrap{position:fixed;right:18px;bottom:18px;display:flex;flex-direction:column;gap:8px;z-index:2200;pointer-events:none}
.toast{min-width:240px;max-width:360px;padding:10px 12px;border-radius:10px;border:1px solid var(--border);box-shadow:var(--shadow-md);font-size:.8rem;font-weight:600;opacity:0;transform:translateY(10px);transition:opacity .2s,transform .2s;background:var(--white);color:var(--text-2)}
.toast.is-visible{opacity:1;transform:translateY(0)}
.toast.toast-success{background:#ecfdf5;border-color:#86efac;color:#166534}

/* ============================================================
   PSU SMARTDOOR — VISUAL REDESIGN OVERRIDES
   UI-only: existing forms, IDs, routes, PHP variables and JS
   hooks are intentionally preserved.
   ============================================================ */
:root{
  --navy:#0b1f5b;
  --navy-deep:#071640;
  --navy-2:#173b91;
  --blue:#2463eb;
  --blue-soft:#eaf2ff;
  --yellow:#f6c515;
  --page:#f5f8fd;
  --card:#ffffff;
  --line:#e4eaf4;
  --muted:#7d8ba5;
  --shadow-soft:0 10px 30px rgba(21,44,91,.07);
  --shadow-card:0 18px 45px rgba(21,44,91,.10);
}

body{
  background:
    radial-gradient(circle at 88% 4%,rgba(56,117,255,.08),transparent 22%),
    linear-gradient(180deg,#f8faff 0%,#f4f7fc 100%);
}

/* Cleaner top bar — existing profile/dropdown behavior remains untouched. */
.topbar{
  height:70px;
  padding:0 30px;
  background:rgba(255,255,255,.92);
  border-bottom:1px solid rgba(225,232,243,.95);
  backdrop-filter:blur(14px);
}
.topbar-right{gap:12px}
.topbar-profile{
  padding:6px 8px 6px 12px;
  border-radius:14px;
  transition:background .18s,box-shadow .18s;
}
.topbar-profile:hover{background:#f5f8fd;box-shadow:0 5px 18px rgba(15,35,80,.06)}
.topbar-avatar{width:38px;height:38px;background:#edf3ff;color:var(--navy-2);border-color:#dbe6fb}

/* Main canvas */
.content{
  padding:26px 34px 56px;
  gap:20px;
}

/* Hero redesigned to look like a real PSU Asingan campus landing section. */
.hero-banner{
  min-height:300px;
  margin:-26px -34px -2px;
  border-radius:0 0 30px 30px;
  justify-content:center;
  background-color:#dceaff;
  background-image:
    linear-gradient(90deg,rgba(247,250,255,.99) 0%,rgba(247,250,255,.94) 30%,rgba(247,250,255,.62) 51%,rgba(247,250,255,.10) 76%),
    url('/images/map.png');
  background-position:center;
  background-size:cover;
  box-shadow:0 16px 38px rgba(18,49,104,.08);
}
.hero-banner::before{
  background:
    linear-gradient(135deg,rgba(255,255,255,.55),transparent 42%),
    linear-gradient(0deg,rgba(10,31,91,.04),transparent 45%);
  z-index:1;
}
.hero-banner::after{
  content:'';
  position:absolute;
  right:-90px;
  bottom:-130px;
  width:390px;
  height:230px;
  border-radius:50%;
  border:2px solid rgba(36,99,235,.12);
  box-shadow:0 0 0 26px rgba(36,99,235,.035),0 0 0 52px rgba(246,197,21,.035);
  transform:rotate(-12deg);
  z-index:1;
}
.hero-content{
  width:100%;
  padding:44px 42px 42px;
  color:var(--navy);
}
.hero-brand{
  gap:14px;
  margin-bottom:18px;
}
.hero-logo{
  width:54px;height:54px;border-radius:16px;
  background:var(--yellow);
  border:0;
  color:var(--navy);
  box-shadow:0 10px 22px rgba(246,197,21,.28);
}
.hero-logo i{font-size:1.3rem;color:var(--navy)}
.hero-brand-name{
  font-size:1rem;
  color:var(--navy);
  letter-spacing:.025em;
}
.hero-brand-campus{
  font-size:.74rem;
  color:#6680ad;
  letter-spacing:.14em;
  text-transform:uppercase;
  font-weight:700;
}
.hero-label{
  display:inline-flex;
  align-items:center;
  gap:8px;
  color:var(--blue);
  font-size:.68rem;
  letter-spacing:.16em;
  margin-bottom:7px;
}
.hero-label::before{
  content:'';width:28px;height:3px;border-radius:999px;background:var(--yellow);
}
.hero-title{
  color:var(--navy);
  font-size:clamp(2rem,3.5vw,3rem);
  line-height:1.02;
  letter-spacing:-.045em;
  margin-bottom:10px;
  text-shadow:0 2px 10px rgba(255,255,255,.4);
}
.hero-sub{
  color:#617394;
  font-size:.9rem;
  line-height:1.65;
  max-width:520px;
}

/* Booking/search card: more premium, but all existing IDs and inputs stay intact. */
.booking-toolbar{
  margin-top:-4px;
  background:rgba(255,255,255,.97);
  border:1px solid #e1e8f2;
  border-radius:22px;
  padding:20px 22px;
  grid-template-columns:minmax(240px,1.45fr) minmax(190px,1fr) minmax(190px,1fr) auto;
  gap:16px;
  box-shadow:var(--shadow-card);
  z-index:20;
}
.booking-toolbar-copy{padding:2px 4px}
.booking-toolbar-title{
  font-size:1.02rem;
  color:var(--navy);
  letter-spacing:-.015em;
}
.booking-toolbar-title i{
  width:38px;height:38px;border-radius:12px;
  display:inline-flex;align-items:center;justify-content:center;
  background:#e9f1ff;
  color:var(--blue);
  font-size:.95rem;
}
.booking-toolbar-sub{
  margin-top:7px;
  max-width:270px;
  color:#8997ad;
  line-height:1.55;
}
.booking-field{gap:7px}
.booking-field label{
  color:#8290a8;
  font-size:.66rem;
  letter-spacing:.09em;
}
.booking-field input{
  height:48px;
  border:1px solid #dfe7f2;
  border-radius:13px;
  padding:0 13px;
  background:#f9fbfe;
  font-size:.85rem;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
}
.booking-field input:hover{border-color:#cbd8ea;background:#fff}
.booking-field input:focus{
  border-color:#7da7ff;
  box-shadow:0 0 0 4px rgba(36,99,235,.10);
  background:#fff;
}
.booking-search-btn{
  height:48px;
  padding:0 21px;
  border-radius:13px;
  background:linear-gradient(135deg,#17398f,#214aa9);
  box-shadow:0 10px 20px rgba(24,57,143,.20);
  font-weight:700;
}
.booking-search-btn:hover{opacity:1;transform:translateY(-1px);box-shadow:0 13px 24px rgba(24,57,143,.25)}

/* Search/filter row */
.search-filter-row{
  padding:2px 0;
  gap:12px;
}
.search-wrap input{
  min-height:46px;
  border-radius:13px;
  border-color:#e0e7f1;
  box-shadow:0 3px 12px rgba(22,43,85,.025);
}
.search-wrap input:focus{border-color:#8db1f5;box-shadow:0 0 0 4px rgba(36,99,235,.08)}
.filter-label{color:#8290a8}
.filter-btn{padding:8px 15px;border-radius:10px;border-color:#e0e7f1;background:#fff}
.filter-btn.active{background:var(--navy);border-color:var(--navy);box-shadow:0 6px 14px rgba(11,31,91,.15)}

/* Campus map */
.map-card{
  border-radius:20px;
  border-color:#e1e8f2;
  box-shadow:var(--shadow-soft);
  background:#fff;
}
.map-card-header{padding:17px 21px;background:#fff}
.map-icon{width:36px;height:36px;border-radius:11px;background:#eaf1ff;color:var(--blue)}
.map-title{font-size:.94rem;color:var(--navy)}
.map-sub{color:#8a98ad}
.live-badge{border-radius:999px;padding:5px 11px}
.map-grid{min-height:260px}

/* Room list cards */
.rooms-header{margin-top:2px}
.rooms-section-title{font-family:var(--fh);color:var(--navy);font-weight:800}
.rooms-count-badge{border-radius:999px;background:#eef4ff;color:#4567a6;border:1px solid #dce7fa}
.rooms-grid{gap:16px}
.room-card{
  border-radius:18px;
  border-color:#e2e9f3;
  box-shadow:0 7px 22px rgba(22,43,85,.055);
}
.room-card:hover{
  border-color:#cbdafa;
  box-shadow:0 16px 30px rgba(22,43,85,.10);
  transform:translateY(-2px);
}


.availability-highlights{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  background:rgba(255,255,255,.94);
  border:1px solid #e4eaf4;
  border-radius:20px;
  box-shadow:0 10px 30px rgba(21,44,91,.055);
  overflow:hidden;
}
.availability-highlight{
  min-height:88px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:13px;
  padding:16px 24px;
  position:relative;
}
.availability-highlight:not(:last-child)::after{
  content:'';position:absolute;right:0;top:22px;bottom:22px;width:1px;background:#e6ebf3;
}
.availability-highlight-icon{
  width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex:0 0 44px;
}
.availability-highlight-icon.blue{background:#eaf2ff;color:#2563eb}
.availability-highlight-icon.green{background:#e8faf3;color:#10a879}
.availability-highlight-icon.purple{background:#f0edff;color:#7357e8}
.availability-highlight strong{display:block;color:#173574;font-family:var(--fh);font-size:.82rem;font-weight:800}
.availability-highlight small{display:block;margin-top:3px;color:#8a98ad;font-size:.71rem}
@media(max-width:768px){
  .availability-highlights{grid-template-columns:1fr}
  .availability-highlight{justify-content:flex-start;padding:15px 20px}
  .availability-highlight:not(:last-child)::after{right:20px;left:20px;top:auto;bottom:0;width:auto;height:1px}
}

/* Existing reservation / modal components inherit the same visual language. */
.my-reservations{border-radius:20px;box-shadow:var(--shadow-soft);border-color:#e1e8f2}
.reserve-modal{border-radius:20px;box-shadow:0 24px 70px rgba(7,22,64,.18)}

@media(max-width:1100px){
  .booking-toolbar{grid-template-columns:1fr 1fr;}
  .booking-toolbar-copy{grid-column:1/-1}
  .booking-toolbar-sub{max-width:none}
  .booking-search-btn{width:100%}
}
@media(max-width:900px){
  .content{padding:20px 18px 42px}
  .hero-banner{margin:-20px -18px -2px;border-radius:0 0 24px 24px}
  .hero-content{padding:34px 24px 36px}
}
@media(max-width:768px){
  .topbar{height:62px;padding:0 16px}
  .content{gap:16px}
  .hero-banner{min-height:260px}
  .hero-title{font-size:2rem}
  .booking-toolbar{grid-template-columns:1fr;padding:17px}
  .booking-toolbar-copy{grid-column:auto}
  .booking-search-btn{width:100%}
}

</style>
@include('partials.pro-motion')
</head>
<body>

<!-- â•â•â• SIDEBAR â€” DO NOT CHANGE â•â•â• -->
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
        <span class="nav-icon"><i class="fas fa-id-card"></i></span>RFID
      </a>
    </li>
    <li><a href="{{ route('faculty.notifications') }}" class="{{ Request::routeIs('faculty.notifications') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-bell"></i></span>Notifications</a></li>
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

<!-- â•â•â• MAIN â•â•â• -->
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

    <!-- Hero Banner -->
    <div class="hero-banner">
      <div class="hero-content">
        <div class="hero-brand">
          <div class="hero-logo"><i class="fas fa-building-columns"></i></div>
          <div class="hero-brand-text">
            <div class="hero-brand-name">Pangasinan State University</div>
            <div class="hero-brand-campus">Asingan Campus</div>
          </div>
        </div>
        <div class="hero-label">PSU SmartRoom</div>
        <h1 class="hero-title">Room Availability</h1>
        <div class="hero-sub">Real-time overview of PSU SmartRoom availability across all buildings.</div>
      </div>
    </div>

    <section class="booking-toolbar" aria-labelledby="bookingTitle">
      <div class="booking-toolbar-copy">
        <div class="booking-toolbar-title" id="bookingTitle"><i class="fas fa-calendar-plus"></i> Find a free room</div>
        <div class="booking-toolbar-sub">Choose a time, check all rooms, then use Reserve on an available room.</div>
      </div>
      <div class="booking-field"><label for="bookingStartAt">Start</label><input id="bookingStartAt" type="datetime-local"></div>
      <div class="booking-field"><label for="bookingEndAt">End</label><input id="bookingEndAt" type="datetime-local"></div>
      <button type="button" class="btn-primary booking-search-btn" id="findFreeRoomsBtn"><i class="fas fa-magnifying-glass"></i> Find Free Rooms</button>
      <div class="booking-result" id="bookingResult"></div>
    </section>

    <!-- Search + Filter -->
    <form method="GET" action="">
      <div class="search-filter-row">
        <div class="search-wrap">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" name="search" placeholder="Search by room name or buildingâ€¦" value="<?= htmlspecialchars($search) ?>">
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
        $roomSearchText = strtolower((string) ($room['name'] ?? '') . ' ' . (string) ($room['building'] ?? ''));
        $roomImages = ['room-1.png', 'room-2.png', 'computer-lab.png'];
        $roomImage = str_contains($roomSearchText, 'lab') || str_contains($roomSearchText, 'computer')
          ? 'computer-lab.png'
          : $roomImages[(int) ($room['id'] ?? 0) % count($roomImages)];
        $a = $status === 'available';
        $isReserved = $status === 'reserved';
        $isMaintenance = in_array($status, ['maintenance', 'unavailable'], true);
        $pillCls = $a ? 'pill-avail' : ($isReserved ? 'pill-res' : ($isMaintenance ? 'pill-maint' : 'pill-occ'));
        $pillIcon = $a ? 'fas fa-circle-check' : ($isReserved ? 'fas fa-calendar-check' : ($isMaintenance ? 'fas fa-triangle-exclamation' : 'fas fa-circle-xmark'));
        $pillLbl = $a ? 'Available' : ($isReserved ? 'Reserved' : ($isMaintenance ? 'Unavailable' : 'Occupied'));
        $tCls = $a ? 'tg' : ($isReserved ? 'ty' : 'tr');
      ?>
      <div class="room-card js-room-card" data-room-id="<?= (int) $room['id'] ?>" data-building="<?= htmlspecialchars((string) $room['building'], ENT_QUOTES) ?>">
        <div class="room-thumb">
          <i class="fas fa-door-open" aria-hidden="true"></i>
          <img class="room-thumb-image" src="<?= htmlspecialchars(asset('images/' . $roomImage), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($room['name']) ?> classroom" loading="lazy">
          <div class="room-thumb-label"><?= htmlspecialchars($room['building']) ?> &middot; <?= htmlspecialchars($room['floor']) ?></div>
        </div>
        <div class="card-body">
          <div class="card-strip">
            <span class="room-name"><?= htmlspecialchars($room['name']) ?></span>
            <span class="room-id">ID &middot; <?= $room['id'] ?></span>
          </div>
          <div class="room-loc">
            <i class="fas fa-location-dot"></i>
            <?= htmlspecialchars($room['building']) ?> &bull; <?= htmlspecialchars($room['floor']) ?>
          </div>
          <div class="room-meta">
            <div class="meta-chip"><i class="fas fa-users"></i> <?= $room['seats'] ?> seats</div>
            <div class="meta-chip <?= $tCls ?>" data-time-chip><i class="fas fa-clock"></i> <span data-time-info><?= htmlspecialchars($room['time_info']) ?></span></div>
          </div>
          <div class="am-row">
            <?php foreach ($room['amenities'] as $am): ?>
            <span class="am-chip"><?= amenity_icon($am) ?> <?= htmlspecialchars($am) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card-right">
          <div>
            <span class="status-pill <?= $pillCls ?>" data-status-pill><i class="<?= $pillIcon ?>"></i> <span data-status-label><?= $pillLbl ?></span></span>
            <div class="issue-note <?= (!empty($room['issue_note']) && in_array($status, ['maintenance', 'unavailable'], true)) ? 'is-visible' : '' ?>" data-issue-note>
              <i class="fas fa-triangle-exclamation"></i>
              <span data-issue-note-text><?= htmlspecialchars((string) ($room['issue_note'] ?? '')) ?></span>
            </div>
          </div>
          <div class="card-actions">
            <button class="btn-check js-check-btn" data-room-id="<?= (int) $room['id'] ?>" data-room-name="<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>">
              <i class="fas fa-magnifying-glass-clock"></i> Check
            </button>
            <button class="btn-res js-reserve-btn <?= $a ? 'on' : 'off' ?>" data-room-id="<?= (int) $room['id'] ?>" data-room-name="<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>" <?= $a ? '' : 'disabled' ?>>
              <i class="fas fa-calendar-plus"></i> Reserve
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Upcoming reservations -->
    <section class="my-reservations" aria-labelledby="myReservationsTitle">
      <div class="my-reservations-head">
        <div class="my-reservations-heading">
          <span class="my-reservations-icon"><i class="fas fa-calendar-check"></i></span>
          <div>
            <div class="my-reservations-title" id="myReservationsTitle">My Reservations</div>
            <div class="my-reservations-sub">Your upcoming room bookings</div>
          </div>
        </div>
        <button type="button" class="btn-outline" id="refreshReservationsBtn"><i class="fas fa-rotate"></i> Refresh</button>
      </div>
      <div class="reservation-list" id="reservationList" aria-busy="true" aria-live="polite">
        <div class="reservation-skeleton" aria-hidden="true"><div class="sk-bar sk-date"></div><div class="sk-lines"><div class="sk-bar sk-line" style="width:70%"></div><div class="sk-bar sk-line" style="width:50%"></div><div class="sk-bar sk-line" style="width:40%"></div></div><div class="sk-bar sk-pill"></div></div>
        <div class="reservation-skeleton" aria-hidden="true"><div class="sk-bar sk-date"></div><div class="sk-lines"><div class="sk-bar sk-line" style="width:55%"></div><div class="sk-bar sk-line" style="width:65%"></div><div class="sk-bar sk-line" style="width:35%"></div></div><div class="sk-bar sk-pill"></div></div>
      </div>
    </section>

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
  <div class="reserve-modal" role="dialog" aria-modal="true" aria-labelledby="checkTitle" style="width:min(1100px,calc(100% - 40px));max-height:min(820px,calc(100vh - 40px));overflow:hidden;display:flex;flex-direction:column">
    <div style="position:relative;min-height:110px;display:flex;align-items:flex-end;padding:20px 24px;overflow:hidden;background:linear-gradient(100deg,rgba(11,22,64,.92),rgba(11,22,64,.45)),url('/images/map.png') center/cover no-repeat;color:#fff;flex-shrink:0;border-radius:var(--r-lg,16px) var(--r-lg,16px) 0 0">
      <div style="position:absolute;top:0;right:0;width:100px;height:100%;background:linear-gradient(135deg,rgba(245,197,24,.2),transparent);pointer-events:none"></div>
      <div style="position:absolute;inset:0;background:linear-gradient(180deg,transparent 30%,rgba(11,22,64,.4));pointer-events:none"></div>
      <div style="position:relative;z-index:1">
        <div style="font-size:.65rem;font-weight:800;letter-spacing:.16em;opacity:.7;text-transform:uppercase">ROOM SCHEDULE</div>
        <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.5rem;font-weight:800;display:flex;align-items:center;gap:8px" id="checkTitle"><i class="fas fa-calendar-alt" style="font-size:.9rem;opacity:.7"></i> <span id="checkRoomName">Room</span></div>
      </div>
      <button type="button" class="reserve-close" id="checkCloseBtn" aria-label="Close" style="position:absolute;top:14px;right:14px;z-index:2;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;backdrop-filter:blur(6px)">
        <i class="fas fa-xmark"></i>
      </button>
    </div>
    <form id="checkForm" class="reserve-body" style="flex:1;overflow-y:auto;max-height:calc(100vh - 200px)">
      <input type="hidden" id="checkRoomId" name="classroom_id">

      <div id="checkTimetableWrap" style="margin-bottom:16px;overflow-x:auto;border:1px solid #d4daf0;border-radius:10px">
        <table id="checkTimetable" style="width:100%;min-width:780px;border-collapse:collapse;font-size:.7rem"></table>
      </div>
      <div id="checkTimetableLegend" style="display:none;gap:14px;margin-bottom:14px;font-size:.72rem;color:#5b6577;font-weight:600">
        <span style="display:inline-flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:3px;background:#dde3fa;border:1px solid #c7d0ee;display:inline-block"></span> Class</span>
        <span style="display:inline-flex;align-items:center;gap:5px;margin-left:12px"><span style="width:10px;height:10px;border-radius:3px;background:#fdf0b8;border:1px solid #f0dc7a;display:inline-block"></span> Reservation</span>
      </div>

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
  function currentCsrfToken() {
    var xsrfCookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return xsrfCookie ? decodeURIComponent(xsrfCookie[1]) : csrfToken;
  }

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
  var checkTimetable = document.getElementById('checkTimetable');
  var checkTimetableWrap = document.getElementById('checkTimetableWrap');
  var checkTimetableLegend = document.getElementById('checkTimetableLegend');
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

  function showReservationSkeleton() {
    reservationList.setAttribute('aria-busy', 'true');
    reservationList.innerHTML = '<div class="reservation-skeleton" aria-hidden="true"><div class="sk-bar sk-date"></div><div class="sk-lines"><div class="sk-bar sk-line" style="width:70%"></div><div class="sk-bar sk-line" style="width:50%"></div><div class="sk-bar sk-line" style="width:40%"></div></div><div class="sk-bar sk-pill"></div></div>'
      + '<div class="reservation-skeleton" aria-hidden="true"><div class="sk-bar sk-date"></div><div class="sk-lines"><div class="sk-bar sk-line" style="width:60%"></div><div class="sk-bar sk-line" style="width:45%"></div><div class="sk-bar sk-line" style="width:55%"></div></div><div class="sk-bar sk-pill"></div></div>';
  }

  function finishReservationLoading() {
    reservationList.setAttribute('aria-busy', 'false');
  }

  async function loadMyReservations() {
    if (!reservationList) return;
    showReservationSkeleton();
    try {
      var response = await fetch('{{ route('faculty.reservations.mine') }}', {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      var payload = await response.json().catch(function () { return {}; });
      if (!response.ok) throw new Error(payload.message || 'Unable to load reservations.');
      var reservations = Array.isArray(payload.data) ? payload.data : [];
      if (!reservations.length) {
        reservationList.innerHTML = '<div class="reservation-empty"><i class="fas fa-calendar-xmark"></i> No upcoming reservations.</div>';
        finishReservationLoading();
        return;
      }
      reservationList.innerHTML = reservations.map(function (reservation) {
        var room = reservation.classroom || {};
        var startDate = new Date(reservation.start_at);
        var status = String(reservation.status || 'reserved');
        var statusClass = status.toLowerCase() === 'pending' ? ' pending' : (status.toLowerCase() === 'cancelled' ? ' cancelled' : '');
        var location = [room.building, room.floor].filter(Boolean).join(' · ');
        return '<article class="reservation-item">'
          + '<div class="reservation-date"><span class="reservation-date-day">' + escapeHtml(startDate.toLocaleDateString([], { weekday: 'short' })) + '</span>'
          + '<strong class="reservation-date-number">' + escapeHtml(startDate.toLocaleDateString([], { day: 'numeric' })) + '</strong></div>'
          + '<div class="reservation-details"><div class="reservation-room">' + escapeHtml(room.name || 'Unknown room') + '</div>'
          + (location ? '<div class="reservation-location"><i class="fas fa-location-dot"></i> ' + escapeHtml(location) + '</div>' : '')
          + '<div class="reservation-time"><i class="fas fa-clock"></i> ' + escapeHtml(formatReservationTime(reservation.start_at, reservation.end_at)) + '</div></div>'
          + '<div class="reservation-side"><span class="reservation-status' + statusClass + '">' + escapeHtml(status) + '</span>'
          + '<button type="button" class="reservation-cancel js-cancel-reservation" aria-label="Cancel reservation" title="Cancel reservation" data-reservation-id="' + reservation.id + '"><i class="fas fa-xmark"></i></button></div></article>';
      }).join('');
      finishReservationLoading();
    } catch (error) {
      reservationList.innerHTML = '<div class="reservation-empty">' + escapeHtml(error.message) + '</div>';
      finishReservationLoading();
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

  /* ── Timetable grid for check modal ── */
  var TT_HOURS=[7,8,9,10,11,12,13,14,15,16,17,18,19];
  var TT_LABELS=['7-8','8-9','9-10','10-11','11-12','12-1','1-2','2-3','3-4','4-5','5-6','6-7','7-8'];
  var TT_DAYS=['MON','TUE','WED','THU','FRI','SAT','SUN'];
  var TT_DAYMAP={1:'MON',2:'TUE',3:'WED',4:'THU',5:'FRI',6:'SAT',0:'SUN'};
  function renderTimetable(payload){
    if(!checkTimetable)return;
    var entries=[];
    (payload.schedules||[]).forEach(function(s){entries.push(Object.assign({},s,{kind:'class'}));});
    (payload.reservations||[]).forEach(function(r){entries.push(Object.assign({},r,{kind:'reservation',course:r.course||'Reserved'}));});
    var grid={};TT_DAYS.forEach(function(d){grid[d]={};});
    entries.forEach(function(e){
      var st=e.start_time?e.start_time.split(':').map(Number):null;
      var en=e.end_time?e.end_time.split(':').map(Number):null;
      var startDate=e.start_at?new Date(e.start_at):null;
      var endDate=e.end_at?new Date(e.end_at):null;
      var dayOfWeek=Number.isInteger(Number(e.day_of_week))?Number(e.day_of_week):(startDate?startDate.getDay():1);
      var dk=TT_DAYMAP[dayOfWeek]||'MON';
      var sh=st?st[0]+(st[1]/60):startDate?startDate.getHours():7;
      var eh=en?en[0]+(en[1]/60):endDate?endDate.getHours():sh+1;
      var firstHour=Math.floor(sh),lastHour=Math.ceil(eh),sp=Math.max(1,lastHour-firstHour);
      sh=firstHour;
      if(grid[dk]){grid[dk][sh]={course:e.course_code||e.course_title||'Scheduled',title:e.course_title||e.course||'Scheduled class',span:sp,kind:e.kind};
        for(var f=sh+1;f<sh+sp;f++)grid[dk][f]='skip';}
    });
    var ths='background:#0b1640;color:#fff;font-weight:700;padding:9px 4px;font-size:.65rem;text-align:center;border:1px solid #1a2f80;';
    var dts='background:#f1f4ff;color:#0b1640;font-weight:800;padding:9px 8px;font-size:.72rem;text-align:center;border:1px solid #d4daf0;';
    var tds='background:#fff;height:44px;min-width:58px;border:1px solid #d4daf0;padding:0;text-align:center;vertical-align:middle;';
    var h='<thead><tr><th style="'+ths+'min-width:60px">TIME</th>';
    TT_LABELS.forEach(function(l){h+='<th style="'+ths+'">'+l+'</th>';});
    h+='</tr></thead><tbody>';
    TT_DAYS.forEach(function(day){
      h+='<tr><th style="'+dts+'">'+day+'</th>';
      for(var i=0;i<TT_HOURS.length;i++){
        var hr=TT_HOURS[i],c=grid[day][hr];
        if(c==='skip')continue;
        if(c&&c.course){
          var cs=c.span>1?' colspan="'+Math.min(c.span,TT_HOURS.length-i)+'"':'';
          var bg=c.kind==='reservation'?'background:linear-gradient(135deg,#fef5cd,#fdf0b8);color:#7a5d0a;border:1px solid #f0dc7a;':'background:linear-gradient(135deg,#e8ecfb,#dde3fa);color:#0b1640;border:1px solid #c7d0ee;';
          h+='<td style="'+tds+'"'+cs+'><div title="'+escapeHtml(c.title)+'" style="'+bg+'border-radius:4px;margin:2px;padding:4px 3px;font-size:.62rem;font-weight:800;line-height:1.2;min-height:38px;display:flex;flex-direction:column;align-items:center;justify-content:center"><span>'+escapeHtml(c.course)+'</span><small style="font-size:.55rem;font-weight:600;margin-top:2px">'+escapeHtml(c.title)+'</small></div></td>';
        }else{h+='<td style="'+tds+'"></td>';}
      }h+='</tr>';
    });h+='</tbody>';
    checkTimetable.innerHTML=h;
    checkTimetableWrap.style.display='block';
    checkTimetableLegend.style.display=entries.length?'flex':'none';
  }
  function ttWeekStart(d){var v=new Date(d);var dy=v.getDay();v.setDate(v.getDate()-(dy===0?6:dy-1));return v.toISOString().slice(0,10);}

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

    // Render empty timetable, then fetch data
    if(checkTimetable){renderTimetable({schedules:[],reservations:[]});
      fetch('<?= url('/api/v1/map/rooms') ?>/'+encodeURIComponent(roomId)+'/fixed-schedules?week_start='+ttWeekStart(new Date()),{headers:{Accept:'application/json'}})
        .then(function(r){if(!r.ok)throw new Error();return r.json();})
        .then(function(p){renderTimetable(p.data||{});})
        .catch(function(){});
    }
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
      addViewTempScheduleButton(card, roomStatus.classroom_id);
    } else {
      removeViewTempScheduleButton(card);
    }

    if (status === 'occupied') {
      iconClass = 'fas fa-circle-xmark';
      pillClass = 'pill-occ';
      timeClass = 'tr';
      label = 'Occupied';
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
                'X-XSRF-TOKEN': currentCsrfToken(),
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
