@php
$studentName = optional($student)->name ?? 'Student';
$studentId = optional($student)->student_id ?? 'N/A';
$firstName = explode(' ', $studentName)[0];
$initials = collect(explode(' ', $studentName))->map(fn($word) => strtoupper($word[0]))->join('');
$nav = [
    ['icon' => 'bi-house', 'label' => 'Home', 'active' => false, 'route' => 'student.home'],
    ['icon' => 'bi-building', 'label' => 'Rooms', 'active' => true, 'route' => 'student.checkingRoom'],
    ['icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => false, 'route' => 'student.attendance'],
    ['icon' => 'bi-person', 'label' => 'Profile', 'active' => false, 'route' => 'student.profile'],
];
$availableRoomsCount = $classrooms->where('status', 'available')->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SmartDoor – Rooms</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root{--gold:#F5A800;--navy:#1B2A5E;}
    body{background:#F4F6FA;font-family:'Segoe UI',sans-serif;}

    /* Sidebar */
    #sidebar{width:220px;min-height:100vh;background:#fff;border-right:1px solid #e8eaf0;}
    .brand-icon{background:var(--gold);border-radius:10px;width:40px;height:40px;display:grid;place-items:center;}
    .nav-link{color:#555;border-radius:8px;padding:.5rem 1rem;font-weight:500;}
    .nav-link:hover,.nav-link.active{background:#F0F4FF;color:var(--navy);}
    .nav-link.active::after{content:'';display:inline-block;width:7px;height:7px;background:var(--navy);border-radius:50%;margin-left:auto;}
    .avatar{width:36px;height:36px;background:var(--navy);border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:.8rem;}

    /* Campus status banner */
    .campus-banner{background:linear-gradient(135deg,#1a8a3c,#22a84a);border-radius:16px;color:#fff;}
    .campus-icon{background:rgba(255,255,255,.2);border-radius:14px;width:60px;height:60px;display:grid;place-items:center;font-size:1.8rem;}

    /* Map */
    .map-card{background:#fff;border-radius:14px;border:1px solid #e8eaf0;overflow:hidden}
    .map-card-header{padding:14px 20px;border-bottom:1px solid #e8eaf0;display:flex;align-items:center;justify-content:space-between;background:#fff}
    .map-title-group{display:flex;align-items:center;gap:10px}
    .map-icon{width:32px;height:32px;border-radius:8px;background:var(--navy);color:#F5A800;display:flex;align-items:center;justify-content:center;font-size:.78rem}
    .map-title{font-size:.88rem;font-weight:700;color:#111827}
    .map-sub{font-size:.72rem;color:#9ca3af;margin-top:1px}
    .live-badge{display:flex;align-items:center;gap:5px;font-size:.7rem;font-weight:600;color:#15803d;background:#f0fdf4;padding:4px 10px;border-radius:6px;border:1px solid #bbf7d0}
    .live-dot{width:6px;height:6px;border-radius:50%;background:#15803d;animation:pulse 1.6s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
    .map-grid{background:#1a2340 url('/images/map.png') center/cover no-repeat;min-height:240px;position:relative;display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr}
    .map-grid::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(11,22,64,.55) 0%,rgba(11,22,64,.35) 100%);pointer-events:none;z-index:0}
    .map-cell{display:flex;align-items:center;justify-content:center;min-height:110px;position:relative;z-index:1}
    .building-pin{display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;transition:transform .2s}
    .building-pin:hover{transform:scale(1.08)}
    .building-pin.is-selected .pin-box{outline:2.5px solid #F5A800;outline-offset:3px}
    .pin-box{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;position:relative;box-shadow:0 4px 20px rgba(0,0,0,.3);border:2px solid rgba(255,255,255,.3);transition:all .2s}
    .pin-box.avail{background:rgba(22,163,74,.9)}
    .pin-box.full{background:rgba(220,38,38,.9)}
    .pin-box:hover{box-shadow:0 6px 28px rgba(0,0,0,.4);transform:translateY(-2px)}
    .pin-num{position:absolute;top:-8px;right:-8px;width:22px;height:22px;border:2px solid #fff;border-radius:50%;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.2)}
    .pin-num.g{background:#fff;color:#15803d}
    .pin-num.r{background:#fff;color:#dc2626}
    .building-lbl{font-size:.72rem;font-weight:700;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.5);letter-spacing:.02em}
    .map-north{position:absolute;top:12px;right:12px;width:28px;height:28px;background:rgba(255,255,255,.95);border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.15);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#1B2A5E;z-index:5}
    .map-legend{position:absolute;bottom:12px;left:12px;background:rgba(11,22,64,.85);backdrop-filter:blur(8px);border-radius:8px;padding:10px 14px;z-index:5;border:1px solid rgba(255,255,255,.1)}
    .legend-row{display:flex;align-items:center;gap:7px;font-size:.7rem;font-weight:600;color:rgba(255,255,255,.85);padding:2px 0}
    .legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;box-shadow:0 0 6px currentColor}

    /* Room cards */
    .room-card{border-radius:14px;border:1px solid #e8eaf0;background:#fff;}
    .amenity-tag{background:#F0F4FF;color:#555;border-radius:6px;font-size:.72rem;padding:3px 8px;}
    .btn-map{background:var(--navy);color:#fff;border-radius:10px;border:none;}
    .btn-map-gray{background:#F0F4FF;color:#aaa;border-radius:10px;border:none;}
  </style>
  <style>
    .is-room-syncing .room-card { opacity: .62; }
    .is-room-syncing .room-card::after { content: ''; display: block; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(90deg,transparent 25%,rgba(255,255,255,.55) 50%,transparent 75%); background-size: 200% 100%; animation: roomSyncSkeleton 1.35s ease-in-out infinite; pointer-events: none; }
    @keyframes roomSyncSkeleton { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    @media (prefers-reduced-motion: reduce) { .is-room-syncing .room-card::after { animation: none; background: rgba(255,255,255,.35); } }
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
      <div><h5 class="fw-bold mb-0">Rooms</h5><small class="text-muted">{{ now()->format('l, F j, Y') }}</small></div>
      <a href="{{ route('student.home') }}" class="btn btn-warning fw-semibold"><i class="bi bi-house me-1"></i>Back to Home</a>
    </div>

    <!-- Campus Status Banner -->
    <div class="campus-banner p-4 d-flex justify-content-between align-items-center mb-4">
      <div>
        <div class="small fw-bold opacity-75 mb-1" style="letter-spacing:.08em">CAMPUS STATUS</div>
        <h2 class="fw-bold mb-1"><span id="available-rooms-count">{{ $availableRoomsCount }}</span> Rooms Available</h2>
        <div class="opacity-75 small">Out of <span id="total-rooms-count">{{ $classrooms->count() }}</span> total classrooms</div>
      </div>
      <div class="campus-icon"><i class="bi bi-building"></i></div>
    </div>

    <!-- Search & Filters -->
    <div class="input-group mb-3">
      <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
      <input id="room-search" type="text" class="form-control border-start-0" placeholder="Search by room name or building...">
    </div>
    <div class="d-flex align-items-center gap-2 mb-4">
      <span class="text-muted small"><i class="bi bi-funnel me-1"></i>Filters:</span>
      <button type="button" class="room-filter btn btn-sm rounded-pill fw-semibold" data-room-filter="all" style="background:var(--navy);color:#fff">All</button>
      <button type="button" class="room-filter btn btn-sm btn-outline-secondary rounded-pill" data-room-filter="available">Available</button>
      <button type="button" class="room-filter btn btn-sm btn-outline-secondary rounded-pill" data-room-filter="occupied">Occupied</button>
    </div>

    <!-- Campus Map -->
    <div class="map-card mb-4">
      <div class="map-card-header">
        <div class="map-title-group">
          <div class="map-icon"><i class="bi bi-map"></i></div>
          <div>
            <div class="map-title">Campus Map</div>
            <div class="map-sub">PSU Assingan Campus Layout</div>
          </div>
        </div>
        <span class="live-badge"><span class="live-dot"></span> Live</span>
      </div>
      <div class="map-grid">
        @php
          $buildings_list = $classrooms->groupBy('building')->map(function($rooms, $building) {
            return [
              'name' => $building,
              'available' => $rooms->where('status', 'available')->count(),
              'is_full' => $rooms->where('status', 'available')->count() === 0
            ];
          })->values();
        @endphp

        @foreach ($buildings_list as $idx => $b)
        <div class="map-cell">
          <div class="building-pin" data-building="{{ $b['name'] }}">
            <div class="pin-box {{ $b['is_full'] ? 'full' : 'avail' }}">
              <i class="bi bi-building"></i>
              <span class="pin-num {{ $b['is_full'] ? 'r' : 'g' }}" data-building-count>{{ $b['available'] }}</span>
            </div>
            <span class="building-lbl">{{ $b['name'] }}</span>
          </div>
        </div>
        @endforeach

        @for ($fill = $buildings_list->count(); $fill < 4; $fill++)
        <div class="map-cell"></div>
        @endfor

        <div class="map-north">N</div>

        <div class="map-legend">
          <div class="legend-row"><span class="legend-dot" style="background:#16a34a;color:#16a34a"></span> Has Available Rooms</div>
          <div class="legend-row"><span class="legend-dot" style="background:#dc2626;color:#dc2626"></span> All Rooms Occupied</div>
          <div class="legend-row"><span class="legend-dot" style="background:#F5A800;color:#F5A800"></span> Selected Room</div>
        </div>
      </div>
    </div>

    <!-- Room Cards -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span id="rooms-found-count" class="fw-semibold small text-muted">{{ $classrooms->count() ?? 0 }} rooms found</span>
      <span id="room-sync-status" class="small text-muted"><i class="bi bi-arrow-repeat me-1"></i>Syncing live status...</span>
    </div>
    <div class="row g-3" id="roomResultsGrid">
      @forelse ($classrooms ?? [] as $room)
        <div class="col-md-6 room-result" data-room-card data-room-id="{{ $room->id }}" data-room-building="{{ $room->building }}" data-room-name="{{ $room->name }}" data-room-status="{{ $room->status === 'available' ? 'available' : 'occupied' }}">
          <div class="room-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-bold mb-0">{{ $room->name ?? 'Room' }}</h6>
              <span class="badge room-status-badge {{ $room->status === 'available' ? 'text-success' : 'text-danger' }}" style="background:{{ $room->status === 'available' ? '#e6f9ee' : '#fdecea' }};font-size:.72rem"><i class="room-status-icon bi {{ $room->status === 'available' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i><span class="room-status-label">{{ $room->status === 'available' ? 'AVAILABLE' : 'OCCUPIED' }}</span></span>
            </div>
            <div class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i>{{ $room->building ?? 'Building' }} • {{ $room->floor ?? 'Floor' }}</div>

            <div class="d-flex gap-3 small mb-3">
              <span><i class="bi bi-people me-1 text-muted"></i>{{ $room->capacity ?? 0 }} seats</span>
              <span class="text-muted"><i class="bi bi-info-circle me-1"></i>Details</span>
            </div>

            <div class="text-uppercase fw-semibold mb-2" style="font-size:.65rem;letter-spacing:.08em;color:#aaa">Amenities</div>
            <div class="d-flex flex-wrap gap-1 mb-3">
              <span class="amenity-tag">Standard</span>
              <span class="amenity-tag">WiFi</span>
            </div>

            <button class="btn room-map-button w-100 py-2 {{ $room->status === 'available' ? 'btn-map' : 'btn-map-gray' }}">
              <i class="bi bi-geo-alt me-1"></i>Show on Map
            </button>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-info">No classrooms available</div>
        </div>
      @endforelse
    </div>

  </main>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var roomCards = Array.from(document.querySelectorAll('[data-room-card]'));
    var activeFilter = 'all';
    var roomStatusesLoaded = false;
    var searchInput = document.getElementById('room-search');
    var syncStatus = document.getElementById('room-sync-status');

    // Building pins are positioned by CSS grid layout

    function escapeText(value) {
      return String(value ?? '').toLowerCase();
    }

    function applyRoomFilters() {
      var search = escapeText(searchInput?.value);
      var visibleCount = 0;

      roomCards.forEach(function(card) {
        var matchesSearch = [card.dataset.roomName, card.dataset.roomBuilding]
          .some(function(value) { return escapeText(value).includes(search); });
        var matchesFilter = activeFilter === 'all' || card.dataset.roomStatus === activeFilter;
        var visible = matchesSearch && matchesFilter;
        card.classList.toggle('d-none', !visible);
        if (visible) visibleCount += 1;
      });

      document.getElementById('rooms-found-count').textContent = visibleCount + (visibleCount === 1 ? ' room found' : ' rooms found');
    }

    function setRoomStatus(card, room) {
      var status = room.status || 'available';
      var isAvailable = status === 'available';
      var label = room.status_label || (isAvailable ? 'Available' : 'Occupied');
      var badge = card.querySelector('.room-status-badge');
      var icon = card.querySelector('.room-status-icon');
      var statusLabel = card.querySelector('.room-status-label');
      var mapButton = card.querySelector('.room-map-button');

      card.dataset.roomStatus = isAvailable ? 'available' : 'occupied';
      badge.className = 'badge room-status-badge ' + (isAvailable ? 'text-success' : 'text-danger');
      badge.style.background = isAvailable ? '#e6f9ee' : '#fdecea';
      icon.className = 'room-status-icon bi ' + (isAvailable ? 'bi-check-circle' : 'bi-x-circle') + ' me-1';
      statusLabel.textContent = label.toUpperCase();
      mapButton.classList.toggle('btn-map', isAvailable);
      mapButton.classList.toggle('btn-map-gray', !isAvailable);
    }

    function updateBuildingPins(rooms) {
      var buildingCounts = {};
      rooms.forEach(function(room) {
        var building = room.building || 'Unknown';
        if (!buildingCounts[building]) buildingCounts[building] = { total: 0, available: 0 };
        buildingCounts[building].total += 1;
        if (room.status === 'available') buildingCounts[building].available += 1;
      });

      document.querySelectorAll('.building-pin').forEach(function(pin) {
        var counts = buildingCounts[pin.dataset.building] || { available: 0 };
        var hasAvailable = counts.available > 0;
        var pinButton = pin.querySelector('.pin-btn');
        var count = pin.querySelector('[data-building-count]');
        pinButton.classList.toggle('bg-success', hasAvailable);
        pinButton.classList.toggle('bg-danger', !hasAvailable);
        count.className = 'badge-count bg-white text-' + (hasAvailable ? 'success' : 'danger');
        count.textContent = counts.available;
      });
    }

    function syncRoomStatuses() {
      // Only show skeleton on very first load (no flash on subsequent polls)
      syncStatus.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Updating live status...';
      fetch('{{ url('/api/v1/room-statuses') }}', { headers: { Accept: 'application/json' } })
        .then(function(response) {
          if (!response.ok) throw new Error('Room status request failed');
          return response.json();
        })
        .then(function(payload) {
          var rooms = payload.data || [];
          var roomById = new Map(rooms.map(function(room) { return [String(room.classroom_id), room]; }));
          var availableCount = 0;

          roomCards.forEach(function(card) {
            var room = roomById.get(String(card.dataset.roomId));
            if (!room) return;
            setRoomStatus(card, room);
            if (room.status === 'available') availableCount += 1;
          });

          updateBuildingPins(rooms);
          document.getElementById('available-rooms-count').textContent = availableCount;
          document.getElementById('total-rooms-count').textContent = rooms.length;
          syncStatus.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Live status updated ' + new Date().toLocaleTimeString();
          document.getElementById('roomResultsGrid')?.classList.remove('is-room-syncing');
          roomStatusesLoaded = true;
          applyRoomFilters();
        })
        .catch(function() {
          document.getElementById('roomResultsGrid')?.classList.remove('is-room-syncing');
          syncStatus.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Live status unavailable';
        });
    }

    searchInput?.addEventListener('input', applyRoomFilters);
    document.querySelectorAll('.room-filter').forEach(function(button) {
      button.addEventListener('click', function() {
        activeFilter = button.dataset.roomFilter || 'all';
        document.querySelectorAll('.room-filter').forEach(function(filterButton) {
          filterButton.classList.toggle('fw-semibold', filterButton === button);
          filterButton.classList.toggle('btn-outline-secondary', filterButton !== button);
          filterButton.style.background = filterButton === button ? 'var(--navy)' : '';
          filterButton.style.color = filterButton === button ? '#fff' : '';
        });
        applyRoomFilters();
      });
    });

    syncRoomStatuses();
    window.setInterval(syncRoomStatuses, 10000);
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
