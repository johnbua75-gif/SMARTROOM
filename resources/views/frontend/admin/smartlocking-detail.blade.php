@extends('layouts.app')

@section('body-class', 'smartlocking-detail-page')

@section('content')
<div class="smartlocking-detail-container">
    <div class="detail-toolbar">
        <a class="detail-back-link" href="{{ route('smartlocking.index') }}">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span>All RFID cards</span>
        </a>
    </div>

    <div class="detail-header">
        <div class="header-content">
            <h1>{{ $card['name'] }}</h1>
            <p class="subtitle">{{ $card['department'] }} <span aria-hidden="true">·</span> Card ID: {{ $card['cardNumber'] }}</p>
        </div>
        <div class="header-actions">
            <span class="status-badge status-{{ $card['status'] }}">
                {{ ucfirst($card['status']) }}
            </span>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="detail-grid">
        <div class="info-section">
            <h2>Card Information</h2>
            
            <div class="info-block">
                <label>Card Number</label>
                <p><code>{{ $card['cardNumber'] }}</code></p>
            </div>

            <div class="info-block">
                <label>RFID Tag</label>
                <p><code>{{ $card['rfid'] }}</code></p>
            </div>

            <div class="info-block">
                <label>Department</label>
                <p>{{ $card['department'] }}</p>
            </div>

            <div class="info-block">
                <label>Email</label>
                <p><a href="mailto:{{ $card['email'] }}">{{ $card['email'] }}</a></p>
            </div>

            <div class="info-block">
                <label>Phone</label>
                <p>{{ $card['phone'] }}</p>
            </div>

            <div class="info-block">
                <label>Expiry Date</label>
                <p>{{ $card['expiryDate'] ? date('F d, Y', strtotime($card['expiryDate'])) : 'No expiry date' }}</p>
            </div>
        </div>

        <div class="stats-section">
            <h2>Access Statistics</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">{{ $card['totalAccess'] }}</div>
                    <div class="stat-label">Total Accesses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $card['thisMonth'] }}</div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>

            <div class="info-block">
                <label>Last Access</label>
                <p class="last-access-value">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>{{ $card['lastAccess'] }}<span class="last-access-room">{{ $card['lastAccessRoom'] }}</span></span>
                </p>
            </div>
        </div>
    </div>

    <!-- User-level room metadata, separate from the RFID card. -->
    <div class="authorized-rooms-section">
        <h2>User Room Permissions</h2>
        <p class="secondary">These are account-level room permissions. This RFID card is not limited to one room; access follows the door’s reservation and class-schedule checks.</p>
        @if($card['authorizedRooms'])
            <div class="rooms-list">
                @foreach($card['authorizedRooms'] as $room)
                    <div class="room-item">
                        <div class="room-icon">
                            <i class="fas fa-building" aria-hidden="true"></i>
                        </div>
                        <div class="room-info">
                            <h4>{{ $room['room'] }}</h4>
                            <p>{{ $room['building'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="detail-empty-state">No user-level room permissions are recorded. Door access is still checked against active reservations and class schedules.</p>
        @endif
    </div>

    <!-- Schedule -->
    <div class="schedule-section">
        <h2>Class Schedule</h2>
        @if($card['schedule'])
            <div class="schedule-list">
                @foreach($card['schedule'] as $session)
                    <div class="schedule-item">
                        <div class="day-label">{{ $session['day'] }}</div>
                        <div class="time-label">{{ $session['time'] }}</div>
                        <div class="room-label">{{ $session['room'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="detail-empty-state">No class schedule is currently associated with this cardholder.</p>
        @endif
    </div>

    <!-- Access Log -->
    <div class="access-log-section">
        <h2>Recent Access Log</h2>
        @if($card['accessLog'])
            <div class="log-list">
                @foreach($card['accessLog'] as $entry)
                    <div class="log-item">
                        <div class="log-date"><i class="fas fa-clock" aria-hidden="true"></i>{{ $entry['date'] }}</div>
                        <div class="log-room"><i class="fas fa-door-open" aria-hidden="true"></i>{{ $entry['room'] }}</div>
                        <div class="log-direction">{{ $entry['direction'] }}</div>
                        <div class="log-status">
                            <span class="badge badge-{{ strtolower($entry['result']) }}">{{ $entry['result'] }}</span>
                            @if($entry['reason'])
                                <span class="log-reason">{{ $entry['reason'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="detail-empty-state">No access events have been recorded for this card.</p>
        @endif
    </div>
</div>

<style>
.smartlocking-detail-container {
    padding: 2.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
}

.header-content {
    flex: 1;
}

.detail-header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 0.5rem 0;
}

.detail-header .subtitle {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.95rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-badge.status-inactive {
    background-color: #fee2e2;
    color: #7f1d1d;
}

.status-badge.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

/* Detail Grid */
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2.5rem;
}

.info-section,
.stats-section {
    background: white;
    border: 1px solid rgba(229, 231, 235, 0.6);
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 0 rgba(255, 255, 255, 0.5);
}

.info-section h2,
.stats-section h2,
.authorized-rooms-section h2,
.schedule-section h2,
.access-log-section h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text);
    margin-top: 0;
    margin-bottom: 1.5rem;
}

.info-block {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(243, 244, 246, 0.7);
}

.info-block:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-block label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.5rem;
}

.info-block p {
    font-size: 1rem;
    color: var(--text);
    margin: 0;
}

.info-block code {
    background-color: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}

.info-block a {
    color: var(--blue-light);
    text-decoration: none;
}

.info-block .secondary {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem !important;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(245, 197, 24, 0.25);
}

.stat-value {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Sections */
.authorized-rooms-section,
.schedule-section,
.access-log-section {
    background: white;
    border: 1px solid rgba(229, 231, 235, 0.6);
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 0 rgba(255, 255, 255, 0.5);
}

/* Rooms List */
.rooms-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.room-item {
    padding: 1rem;
    background: rgba(245, 197, 24, 0.03);
    border-radius: 6px;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    border: 1px solid rgba(245, 197, 24, 0.1);
}

.room-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(245, 197, 24, 0.1);
    border-radius: 8px;
    color: var(--primary);
}

.room-info h4 {
    margin: 0 0 0.25rem 0;
    color: var(--text);
}

.room-info p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Schedule List */
.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.schedule-item {
    padding: 1rem;
    background: rgba(245, 197, 24, 0.04);
    border-radius: 6px;
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 1rem;
    align-items: center;
    border-left: 0;
    border: 1px solid rgba(245, 197, 24, 0.15);
    border-left: 0;
}

.day-label {
    font-weight: 600;
    color: var(--primary);
}

.time-label {
    color: var(--text);
}

.room-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    text-align: right;
}

/* Access Log */
.log-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.log-item {
    padding: 1rem;
    background: rgba(245, 197, 24, 0.03);
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 2rem;
    border: 1px solid rgba(229, 231, 235, 0.3);
}

.log-date,
.log-room {
    font-size: 0.95rem;
    color: var(--text);
}

.log-status {
    margin-left: auto;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}

.badge-entry {
    background-color: #d1fae5;
    color: #065f46;
}

.badge-exit {
    background-color: #fee2e2;
    color: #7f1d1d;
}

.badge-granted {
    background-color: #dcfce7;
    color: #166534;
}

.badge-denied {
    background-color: #fee2e2;
    color: #991b1b;
}

/* Responsive */
@media (max-width: 1024px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }

    .schedule-item {
        grid-template-columns: 1fr;
    }

    .time-label {
        grid-column: 1;
    }

    .room-label {
        grid-column: 1;
        text-align: left;
    }
}

@media (max-width: 768px) {
    .smartlocking-detail-container {
        padding: 1.5rem;
    }

    .detail-header {
        flex-direction: column;
        gap: 1rem;
    }

    .rooms-list {
        grid-template-columns: 1fr;
    }

    .log-item {
        flex-direction: column;
        gap: 0.5rem;
    }

    .log-status {
        margin-left: 0;
    }
}

body.smartlocking-detail-page {
    background: #f4f7fb;
    color: #0b1640;
}

body.smartlocking-detail-page .sidebar {
    background: linear-gradient(180deg, #0b1640 0%, #112060 100%);
}

body.smartlocking-detail-page .sidebar-logo,
body.smartlocking-detail-page .sidebar-logo-text,
body.smartlocking-detail-page .sidebar-logo-text .brand-main {
    color: #fff;
}

body.smartlocking-detail-page .sidebar-nav a.active {
    color: #f5c518;
    background: rgba(245, 197, 24, 0.16);
}

body.smartlocking-detail-page .sidebar-logout {
    color: rgba(255, 255, 255, 0.78);
}

body.smartlocking-detail-page .main-content {
    background: #f4f7fb;
    color: #0b1640;
}

.smartlocking-detail-container {
    --primary: #f5c518;
    --primary-light: #fde97a;
    --blue-light: #1a2f80;
    --text: #0b1640;
    --text-secondary: #5a6785;
    max-width: 1340px;
    padding: 1.75rem 2rem 3rem;
}

.detail-toolbar {
    margin-bottom: 1rem;
}

.detail-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 40px;
    color: #1a2f80;
    font-size: 0.88rem;
    font-weight: 650;
    text-decoration: none;
}

.detail-back-link:hover {
    color: #0b1640;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.detail-back-link:focus-visible {
    outline: 3px solid #f5c518;
    outline-offset: 3px;
    border-radius: 3px;
}

.detail-header {
    align-items: center;
    margin-bottom: 1.75rem;
}

.detail-header h1 {
    color: #0b1640;
    font-size: 1.75rem;
    line-height: 1.25;
}

.detail-header .subtitle {
    color: #5a6785;
}

.detail-grid {
    grid-template-columns: minmax(0, 1.1fr) minmax(300px, 0.9fr);
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

.info-section,
.stats-section,
.authorized-rooms-section,
.schedule-section,
.access-log-section {
    border-color: #dbe3f5;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(11, 22, 64, 0.045);
}

.info-section h2,
.stats-section h2,
.authorized-rooms-section h2,
.schedule-section h2,
.access-log-section h2 {
    color: #0b1640;
    font-size: 1.1rem;
    margin-bottom: 1.15rem;
}

.stat-card {
    background: transparent;
    border: 0;
    border-bottom: 1px solid #e4e9f2;
    border-radius: 0;
    box-shadow: none;
    color: #0b1640;
    padding: 1rem 0;
    text-align: left;
}

.stat-value {
    color: #0b1640;
    font-size: 1.8rem;
    font-variant-numeric: tabular-nums;
    margin-bottom: 0.15rem;
}

.stat-label {
    color: #5a6785;
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    opacity: 1;
}

.last-access-value {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
}

.last-access-value > i,
.log-date > i,
.log-room > i {
    color: #5a6785;
    flex: 0 0 auto;
}

.last-access-room {
    display: block;
    margin-top: 0.2rem;
    color: #5a6785;
    font-size: 0.86rem;
}

.detail-empty-state {
    max-width: 70ch;
    margin: 0;
    padding: 0.85rem 0;
    color: #5a6785;
    font-size: 0.9rem;
    line-height: 1.55;
}

.log-item {
    gap: 1.1rem;
    flex-wrap: wrap;
    border-color: #e4e9f2;
    background: #fbfcfe;
}

.log-date,
.log-room {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
}

.log-direction {
    color: #5a6785;
    font-size: 0.84rem;
}

.log-status {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.log-reason {
    color: #5a6785;
    font-size: 0.82rem;
}

@media (max-width: 768px) {
    .smartlocking-detail-container {
        padding: 1rem 0.25rem 2rem;
    }

    .detail-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .info-section,
    .stats-section,
    .authorized-rooms-section,
    .schedule-section,
    .access-log-section {
        padding: 1.25rem;
    }

    .log-item {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.6rem;
    }

    .log-status {
        margin-left: 0;
    }
}
</style>
@endsection
