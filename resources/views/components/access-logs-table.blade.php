<!-- resources/views/components/access-logs-table.blade.php -->
<!-- This partial contains the access logs table, modal, and related scripts/styles for reuse. -->

<!-- Log Table -->
<div class="table-card">
  <div class="table-card-header">
    <div class="table-card-header-left">
      <h2>Access Event Log</h2>
      <p>Showing 12 of 47 events today</p>
    </div>
    <div class="live-badge"><div class="live-dot"></div> LIVE</div>
  </div>
  <table class="log-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Instructor</th>
        <th>Room</th>
        <th>RFID Tag</th>
        <th>Time In</th>
        <th>Time Out</th>
        <th>Duration</th>
        <th>Method</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="logTableBody">
      <!-- Rows inserted by JS -->
    </tbody>
  </table>
  <div class="table-footer">
    <div class="table-footer-info">Showing <span>1–12</span> of <span>47</span> events</div>
    <div class="pagination">
      <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
      <button class="page-btn active">1</button>
      <button class="page-btn">2</button>
      <button class="page-btn">3</button>
      <button class="page-btn">4</button>
      <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal">
    <div class="modal-header">
      <h3>Access Event Detail</h3>
      <button class="modal-close" onclick="closeModalBtn()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- injected by JS -->
    </div>
  </div>
</div>

<script>
// ...existing JS for logs, renderTable, filterLogs, openModal, closeModal, etc...
const logs = [
  { id:1,  name:'Prof. Maria Santos', dept:'Computer Science', avatar:'https://randomuser.me/api/portraits/women/68.jpg', room:'CS Lab 301',          rfid:'RFID-A1B2C304E5F6', timeIn:'07:58', timeOut:'10:05', dur:127, method:'RFID', status:'granted' },
  { id:2,  name:'Dr. Roberto Cruz',   dept:'Engineering',       avatar:'https://randomuser.me/api/portraits/men/32.jpg',   room:'Engineering Lab 401', rfid:'RFID-B2C304E5F6A1', timeIn:'08:55', timeOut:'11:03', dur:128, method:'RFID', status:'granted' },
  { id:3,  name:'Prof. Ana Reyes',    dept:'Business Admin',    avatar:'https://randomuser.me/api/portraits/women/12.jpg', room:'Business Room 203',   rfid:'RFID-C304E5F6A1B2', timeIn:'09:12', timeOut:'—',     dur:null, method:'RFID', status:'granted' },
  { id:4,  name:'Dr. Carlos Mendoza', dept:'Mathematics',       avatar:'https://randomuser.me/api/portraits/men/54.jpg',   room:'Math Room 105',       rfid:'RFID-D4E5F6A1B2C3', timeIn:'07:45', timeOut:'—',     dur:null, method:'RFID', status:'denied'  },
  { id:5,  name:'Prof. Maria Santos', dept:'Computer Science',  avatar:'https://randomuser.me/api/portraits/women/68.jpg', room:'CS Lab 301',          rfid:'RFID-A1B2C304E5F6', timeIn:'12:58', timeOut:'15:02', dur:124, method:'RFID', status:'granted' },
  { id:6,  name:'Dr. Roberto Cruz',   dept:'Engineering',       avatar:'https://randomuser.me/api/portraits/men/32.jpg',   room:'Engineering Lab 401', rfid:'RFID-B2C304E5F6A1', timeIn:'13:50', timeOut:'16:05', dur:135, method:'RFID', status:'granted' },
  { id:7,  name:'Prof. Ana Reyes',    dept:'Business Admin',    avatar:'https://randomuser.me/api/portraits/women/12.jpg', room:'Business Room 203',   rfid:'RFID-C304E5F6A1B2', timeIn:'13:55', timeOut:'16:10', dur:135, method:'RFID', status:'granted' },
  { id:8,  name:'Prof. Maria Santos', dept:'Computer Science',  avatar:'https://randomuser.me/api/portraits/women/68.jpg', room:'CS Lab 301',          rfid:'RFID-A1B2C304E5F6', timeIn:'10:01', timeOut:'12:04', dur:123, method:'PIN',  status:'granted' },
  { id:9,  name:'Dr. Carlos Mendoza', dept:'Mathematics',       avatar:'https://randomuser.me/api/portraits/men/54.jpg',   room:'Math Room 105',       rfid:'RFID-D4E5F6A1B2C3', timeIn:'08:02', timeOut:'10:10', dur:128, method:'RFID', status:'granted' },
  { id:10, name:'Dr. Roberto Cruz',   dept:'Engineering',       avatar:'https://randomuser.me/api/portraits/men/32.jpg',   room:'Engineering Lab 401', rfid:'RFID-B2C304E5F6A1', timeIn:'06:30', timeOut:'—',     dur:null, method:'RFID', status:'denied'  },
  { id:11, name:'Prof. Ana Reyes',    dept:'Business Admin',    avatar:'https://randomuser.me/api/portraits/women/12.jpg', room:'Business Room 203',   rfid:'RFID-C304E5F6A1B2', timeIn:'09:58', timeOut:'12:03', dur:125, method:'RFID', status:'timeout' },
  { id:12, name:'Dr. Carlos Mendoza', dept:'Mathematics',       avatar:'https://randomuser.me/api/portraits/men/54.jpg',   room:'Math Room 105',       rfid:'RFID-D4E5F6A1B2C3', timeIn:'10:00', timeOut:'12:12', dur:132, method:'PIN',  status:'granted' },
];

let filteredLogs = [...logs];

function statusBadge(s) {
  const map = { granted:'status-granted', denied:'status-denied', timeout:'status-timeout' };
  const label = { granted:'Granted', denied:'Denied', timeout:'Timeout' };
  return `<span class="status-badge ${map[s]}">${label[s]}</span>`;
}
function methodBadge(m) {
  const cls = m === 'RFID' ? 'method-rfid' : 'method-pin';
  const icon = m === 'RFID' ? 'fa-credit-card' : 'fa-keyboard';
  return `<span class="method-badge ${cls}"><i class="fas ${icon}" style="font-size:0.65rem;"></i> ${m}</span>`;
}
function durBar(d) {
  if (!d) return '<span style="color:var(--text-light);font-size:0.8rem;">—</span>';
  const pct = Math.min(100, Math.round(d / 150 * 100));
  return `<div class="duration-val">${d} min</div><div class="duration-bar-wrap"><div class="duration-bar" style="width:${pct}%;"></div></div>`;
}

function renderTable(data) {
  const tbody = document.getElementById('logTableBody');
  tbody.innerHTML = data.map(l => `
    <tr>
      <td style="color:var(--text-light);font-size:0.78rem;font-weight:600;">#${String(l.id).padStart(3,'0')}</td>
      <td>
        <div class="instr-cell">
          <img class="instr-avatar" src="${l.avatar}" alt="${l.name}">
          <div>
            <div class="instr-name">${l.name}</div>
            <div class="instr-dept">${l.dept}</div>
          </div>
        </div>
      </td>
      <td><span class="room-chip"><i class="fas fa-door-open"></i> ${l.room}</span></td>
      <td><span class="rfid-mono">${l.rfid}</span></td>
      <td><div class="time-val">${l.timeIn}</div><div class="date-val">Mar 29, 2025</div></td>
      <td><div class="time-val">${l.timeOut}</div>${l.timeOut !== '—' ? '<div class="date-val">Mar 29, 2025</div>' : ''}</td>
      <td>${durBar(l.dur)}</td>
      <td>${methodBadge(l.method)}</td>
      <td>${statusBadge(l.status)}</td>
      <td><button class="btn-view-detail" onclick="openModal(${l.id})"><i class="fas fa-eye"></i></button></td>
    </tr>
  `).join('');
}

function filterLogs() {
  const q = document.getElementById('logSearch').value.toLowerCase();
  filteredLogs = logs.filter(l =>
    l.name.toLowerCase().includes(q) ||
    l.room.toLowerCase().includes(q) ||
    l.rfid.toLowerCase().includes(q) ||
    l.dept.toLowerCase().includes(q)
  );
  renderTable(filteredLogs);
}

function openModal(id) {
  const l = logs.find(x => x.id === id);
  if (!l) return;
  document.getElementById('modalBody').innerHTML = `
    <div class="modal-map" style="margin-bottom:16px;">
      <div class="modal-map-label">Access Location</div>
      <div class="modal-map-room">${l.room}</div>
      <div class="modal-map-floor">PSU Assingan Campus</div>
      <i class="fas fa-building modal-map-icon"></i>
    </div>
    <p class="modal-section-title">Event Details</p>
    <div class="detail-grid">
      <div class="detail-item"><div class="detail-item-label">Instructor</div><div class="detail-item-val">${l.name}</div></div>
      <div class="detail-item"><div class="detail-item-label">Department</div><div class="detail-item-val">${l.dept}</div></div>
      <div class="detail-item"><div class="detail-item-label">RFID Tag</div><div class="detail-item-val" style="font-family:monospace;font-size:0.78rem;">${l.rfid}</div></div>
      <div class="detail-item"><div class="detail-item-label">Method</div><div class="detail-item-val">${l.method}</div></div>
      <div class="detail-item"><div class="detail-item-label">Time In</div><div class="detail-item-val">${l.timeIn} — Mar 29, 2025</div></div>
      <div class="detail-item"><div class="detail-item-label">Time Out</div><div class="detail-item-val">${l.timeOut === '—' ? 'Still Inside' : l.timeOut + ' — Mar 29, 2025'}</div></div>
      <div class="detail-item"><div class="detail-item-label">Duration</div><div class="detail-item-val">${l.dur ? l.dur + ' minutes' : '—'}</div></div>
      <div class="detail-item"><div class="detail-item-label">Status</div><div class="detail-item-val">${l.status.charAt(0).toUpperCase()+l.status.slice(1)}</div></div>
    </div>
  `;
  document.getElementById('modalOverlay').classList.add('open');
}
function closeModal(e) { if (e.target === document.getElementById('modalOverlay')) closeModalBtn(); }
function closeModalBtn() { document.getElementById('modalOverlay').classList.remove('open'); }

renderTable(logs);
</script>
