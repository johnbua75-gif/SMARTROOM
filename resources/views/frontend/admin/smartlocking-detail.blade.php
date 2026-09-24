@extends('layouts.app')

@section('content')
<div class="smartlocking-detail-container">
    <!-- Header -->
    <div class="detail-header">
        <div class="header-content">
            <h1>{{ $card['name'] }}</h1>
            <p class="subtitle">{{ $card['department'] }} • Card ID: {{ $card['cardNumber'] }}</p>
        </div>
        <div class="header-actions">
            <span class="status-badge status-{{ $card['status'] }}">
                {{ ucfirst($card['status']) }}
            </span>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="detail-grid">
        <!-- Left Column: Card Information -->
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
                <p>{{ date('F d, Y', strtotime($card['expiryDate'])) }}</p>
            </div>
        </div>

        <!-- Right Column: Access Statistics -->
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
                <p>{{ $card['lastAccess'] }} at {{ $card['lastAccessRoom'] }}</p>
            </div>

            <div class="info-block">
                <label>Primary Room</label>
                <p><strong>{{ $card['room'] }}</strong></p>
                <p class="secondary">{{ $card['building'] }} • {{ $card['floor'] }}</p>
            </div>
        </div>
    </div>

    <!-- Authorized Rooms -->
    <div class="authorized-rooms-section">
        <h2>Authorized Rooms</h2>
        @if($card['authorizedRooms'])
            <div class="rooms-list">
                @foreach($card['authorizedRooms'] as $room)
                    <div class="room-item">
                        <div class="room-icon">
                            <i class=\"fas fa-building\"></i>
                        </div>
                        <div class="room-info">
                            <h4>{{ $room['room'] }}</h4>
                            <p>{{ $room['building'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
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
        @endif
    </div>

    <!-- Access Log -->
    <div class="access-log-section">
        <h2>Recent Access Log</h2>
        @if($card['accessLog'])
            <div class="log-list">
                @foreach($card['accessLog'] as $entry)
                    <div class="log-item">
                        <div class="log-date">📅 {{ $entry['date'] }}</div>
                        <div class="log-room">🚪 {{ $entry['room'] }}</div>
                        <div class="log-status">
                            @if($entry['status'] === 'Entry')
                                <span class="badge badge-entry">{{ $entry['status'] }}</span>
                            @else
                                <span class="badge badge-exit">{{ $entry['status'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
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
    border-left: 4px solid var(--primary);
    border: 1px solid rgba(245, 197, 24, 0.15);
    border-left: 4px solid var(--primary);
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
</style>
@endsection
