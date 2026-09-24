<style>
.topbar-profile,.tb-profile{display:none!important}
.faculty-notification-widget{position:fixed;top:14px;right:24px;z-index:2300;font-family:inherit}
.faculty-notification-button{position:relative;width:40px;height:40px;border:1px solid rgba(148,163,184,.35);border-radius:10px;background:#fff;color:#334155;box-shadow:0 4px 14px rgba(15,23,42,.12);cursor:pointer;font-size:1rem}
.faculty-notification-button:hover{color:#2563eb;border-color:#93c5fd;background:#eff6ff}
.faculty-notification-badge{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 4px;border-radius:99px;background:#dc2626;color:#fff;font:700 10px/18px Arial,sans-serif;display:none}
.faculty-notification-panel{position:absolute;top:48px;right:0;width:min(340px,calc(100vw - 32px));background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 18px 45px rgba(15,23,42,.18);display:none;overflow:hidden}
.faculty-notification-panel.is-open{display:block}
.faculty-notification-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:.85rem;font-weight:800}
.faculty-notification-head span{font-size:.7rem;color:#64748b;font-weight:500}
.faculty-notification-empty{padding:24px 16px;color:#64748b;text-align:center;font-size:.78rem}
@media(max-width:768px){.faculty-notification-widget{top:10px;right:12px}.faculty-notification-button{width:36px;height:36px}}
</style>
<div class="faculty-notification-widget" id="facultyNotificationWidget">
  <button type="button" class="faculty-notification-button" id="facultyNotificationButton" aria-label="Notifications" aria-expanded="false">
    <i class="fas fa-bell"></i><span class="faculty-notification-badge" id="facultyNotificationBadge">0</span>
  </button>
  <div class="faculty-notification-panel" id="facultyNotificationPanel" role="dialog" aria-label="Notifications">
    <div class="faculty-notification-head">Notifications <span>Faculty updates</span></div>
    <div class="faculty-notification-empty">No notifications yet.</div>
  </div>
</div>
<script>
(function () {
  const button = document.getElementById('facultyNotificationButton');
  const panel = document.getElementById('facultyNotificationPanel');
  if (!button || !panel) return;
  button.addEventListener('click', function (event) {
    event.stopPropagation();
    const isOpen = panel.classList.toggle('is-open');
    button.setAttribute('aria-expanded', String(isOpen));
  });
  document.addEventListener('click', function (event) {
    if (!event.target.closest('#facultyNotificationWidget')) {
      panel.classList.remove('is-open');
      button.setAttribute('aria-expanded', 'false');
    }
  });
})();
</script>
