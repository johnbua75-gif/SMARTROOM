<section class="admin-live-access-feed" id="adminLiveAccessFeed" data-since-id="{{ (int) ($sinceId ?? 0) }}" aria-labelledby="adminLiveAccessTitle">
  <div class="admin-live-access-head">
    <h2 id="adminLiveAccessTitle"><span class="admin-live-dot" aria-hidden="true"></span>Live door activity</h2>
    <span class="admin-live-updated" id="adminLiveAccessUpdated">Connecting</span>
  </div>
  <ol class="admin-live-access-list" id="adminLiveAccessList" aria-live="polite" aria-relevant="additions">
    <li class="admin-live-empty">Waiting for door events</li>
  </ol>
</section>
<style>
.admin-live-access-feed{padding:16px 18px;border:1px solid #dbe3ee;border-radius:10px;background:#fff;box-shadow:0 2px 8px rgba(15,23,42,.05)}
.admin-live-access-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}
.admin-live-access-head h2{display:flex;align-items:center;gap:8px;margin:0;color:#172554;font-size:.9rem;font-weight:750}
.admin-live-dot{width:8px;height:8px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 3px #dcfce7}
.admin-live-updated{color:#64748b;font-size:.72rem;font-variant-numeric:tabular-nums}
.admin-live-access-list{display:grid;gap:6px;max-height:245px;overflow:auto;padding:0;margin:0;list-style:none}
.admin-live-event{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:start;gap:10px;padding:9px 10px;border-radius:7px;background:#f8fafc;font-size:.76rem;line-height:1.4}
.admin-live-event.is-granted{border-left:3px solid #16a34a}.admin-live-event.is-denied{border-left:3px solid #dc2626}
.admin-live-event strong{color:#0f172a}.admin-live-event span{color:#475569}.admin-live-event time{color:#64748b;white-space:nowrap;font-size:.7rem}
.live-log-reason{max-width:180px;margin-top:4px;color:#64748b;font-size:.68rem;line-height:1.3}
.admin-live-empty{padding:14px;color:#64748b;font-size:.76rem;text-align:center}
.access-log-live-highlight{animation:access-log-highlight 2s ease-out}
@keyframes access-log-highlight{0%{background:#fef3c7}100%{background:transparent}}
@media(prefers-reduced-motion:reduce){.access-log-live-highlight{animation:none}}
@media(max-width:600px){.admin-live-event{grid-template-columns:auto minmax(0,1fr)}.admin-live-event time{grid-column:2}}
</style>
<script>
(function () {
  const feed = document.getElementById('adminLiveAccessFeed');
  const list = document.getElementById('adminLiveAccessList');
  const updated = document.getElementById('adminLiveAccessUpdated');
  if (!feed || !list || !updated) return;

  const endpoint = '{{ route('admin.access-logs.live') }}';
  let lastSeenId = Number(feed.dataset.sinceId) || 0;
  let pollDelayMs = 1500;
  let pollTimer = null;
  let requestInFlight = false;
  let hasEvents = false;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[character]);
  }

  function renderEvent(entry) {
    const denied = entry.result === 'denied';
    const event = document.createElement('li');
    event.className = `admin-live-event ${denied ? 'is-denied' : 'is-granted'}`;
    const accessedAt = entry.accessed_at ? new Date(entry.accessed_at) : new Date();
    const time = accessedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    event.innerHTML = `<strong>${denied ? 'Denied' : 'Granted'}</strong><span><b>${escapeHtml(entry.user_name || 'Unknown User')}</b> · ${escapeHtml(entry.classroom_name || 'Unknown Room')}${entry.reason ? `<br>${escapeHtml(entry.reason)}` : ''}</span><time>${escapeHtml(time)}</time>`;
    list.prepend(event);
    while (list.children.length > 20) list.lastElementChild.remove();
    hasEvents = true;
  }

  function schedulePoll(delay) {
    window.clearTimeout(pollTimer);
    if (document.hidden) return;
    pollTimer = window.setTimeout(poll, delay + Math.random() * 250);
  }

  async function poll() {
    if (document.hidden || requestInFlight) return;
    requestInFlight = true;
    try {
      const url = new URL(endpoint, window.location.origin);
      url.searchParams.set('since_id', String(lastSeenId));
      const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) throw new Error(`Live access feed returned ${response.status}`);
      const payload = await response.json();
      const events = Array.isArray(payload.data) ? payload.data : [];
      events.sort((first, second) => Number(first.id) - Number(second.id));
      events.forEach(function (entry) {
        if (Number(entry.id) <= lastSeenId) return;
        lastSeenId = Number(entry.id);
        renderEvent(entry);
      });
      if (typeof window.handleNewAdminAccessLogs === 'function' && events.length) {
        window.handleNewAdminAccessLogs(events);
      }
      updated.textContent = `Updated ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
      if (!hasEvents) list.innerHTML = '<li class="admin-live-empty">No new door events</li>';
      pollDelayMs = 1500;
      schedulePoll(pollDelayMs);
    } catch (error) {
      pollDelayMs = Math.min(10000, pollDelayMs * 2);
      updated.textContent = 'Reconnecting';
      schedulePoll(pollDelayMs);
    } finally {
      requestInFlight = false;
    }
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      window.clearTimeout(pollTimer);
      pollTimer = null;
      return;
    }
    window.clearTimeout(pollTimer);
    pollTimer = null;
    poll();
  });

  poll();
})();
</script>
