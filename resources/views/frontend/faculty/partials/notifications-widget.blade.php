<style>
.topbar-profile,.tb-profile{display:none!important}
.topbar{display:none!important}
.faculty-notification-widget{display:none!important}
.sidebar{border-right:2px solid rgba(245,197,24,.55);overflow:visible!important;transition:width .22s ease}
.main{transition:margin-left .22s ease}
.faculty-sidebar-toggle{position:absolute;top:50%;right:-13px;z-index:5;width:24px;height:24px;border:1px solid rgba(245,197,24,.7);border-radius:50%;background:#17265f;color:#f5c518;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.68rem;box-shadow:0 3px 8px rgba(0,0,0,.18);transform:translateY(-50%)}
.faculty-sidebar-toggle:hover{background:#f5c518;color:#0b1640}
body.faculty-sidebar-collapsed .sidebar{width:72px}
body.faculty-sidebar-collapsed .main{margin-left:72px!important}
body.faculty-sidebar-collapsed .sidebar-logo{justify-content:center;padding-left:12px;padding-right:12px}
body.faculty-sidebar-collapsed .logo-text,body.faculty-sidebar-collapsed .nav-section-label,body.faculty-sidebar-collapsed .user-widget-info{display:none}
body.faculty-sidebar-collapsed .sidebar-nav{padding-left:10px;padding-right:10px}
body.faculty-sidebar-collapsed .sidebar-nav a{justify-content:center;padding-left:8px;padding-right:8px;font-size:0}
body.faculty-sidebar-collapsed .sidebar-nav a .nav-icon{font-size:.85rem}
body.faculty-sidebar-collapsed .sidebar-nav a.active::before{left:0}
body.faculty-sidebar-collapsed .sidebar-footer{padding-left:10px;padding-right:10px}
body.faculty-sidebar-collapsed .user-widget{justify-content:center;padding-left:7px;padding-right:7px}
body.faculty-sidebar-collapsed .sidebar-logout-btn{justify-content:center;font-size:0;padding-left:8px;padding-right:8px}
body.faculty-sidebar-collapsed .sidebar-logout-btn i{font-size:.84rem}
body.faculty-sidebar-collapsed .faculty-sidebar-toggle{right:-13px;transform:translateY(-50%) rotate(180deg)}
.faculty-notification-button{position:relative;width:40px;height:40px;border:1px solid rgba(255,255,255,.12);border-radius:10px;background:rgba(255,255,255,.07);color:rgba(255,255,255,.72);box-shadow:0 4px 14px rgba(0,0,0,.18);cursor:pointer;font-size:1rem}
.faculty-notification-button:hover{color:#f5c518;border-color:rgba(245,197,24,.45);background:rgba(245,197,24,.14)}
.faculty-notification-badge{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;padding:0 4px;border-radius:99px;background:#dc2626;color:#fff;font:700 10px/18px Arial,sans-serif;display:none}
.faculty-notification-panel{position:absolute;bottom:48px;left:0;width:min(300px,calc(100vw - 32px));background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 18px 45px rgba(15,23,42,.18);display:none;overflow:hidden}
.faculty-notification-panel.is-open{display:block}
.faculty-notification-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:.85rem;font-weight:800}
.faculty-notification-head span{font-size:.7rem;color:#64748b;font-weight:500}
.faculty-notification-empty{padding:24px 16px;color:#64748b;text-align:center;font-size:.78rem}
.faculty-access-modal{position:fixed;inset:0;z-index:2400;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(7,22,64,.34);backdrop-filter:blur(3px)}
.faculty-access-modal.is-open{display:flex}
.faculty-access-card{width:min(390px,100%);padding:28px 26px 24px;border:1px solid #bbf7d0;border-radius:18px;background:#fff;box-shadow:0 24px 70px rgba(7,22,64,.22);text-align:center}
.faculty-access-card.is-denied{border-color:#fecaca}
.faculty-access-icon{width:58px;height:58px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;border-radius:50%;background:#dcfce7;color:#15803d;font-size:1.45rem}
.faculty-access-card.is-denied .faculty-access-icon{background:#fee2e2;color:#b91c1c}
.faculty-access-title{color:#0f172a;font:800 1.1rem 'Plus Jakarta Sans',sans-serif}.faculty-access-message{margin:8px 0 20px;color:#64748b;font-size:.84rem;line-height:1.5}.faculty-access-close{height:38px;padding:0 20px;border:0;border-radius:9px;background:#0b1640;color:#fff;cursor:pointer;font:700 .82rem 'DM Sans',sans-serif}
.faculty-access-card.is-denied .faculty-access-title{color:#b91c1c}
@media(max-width:768px){.faculty-notification-widget{display:none}}
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
<div class="faculty-access-modal" id="facultyAccessModal" aria-hidden="true">
  <div class="faculty-access-card" id="facultyAccessCard" role="dialog" aria-modal="true" aria-labelledby="facultyAccessTitle">
    <div class="faculty-access-icon"><i class="fas fa-check" id="facultyAccessIcon"></i></div>
    <div class="faculty-access-title" id="facultyAccessTitle">Access Granted</div>
    <div class="faculty-access-message" id="facultyAccessMessage">Your RFID card was accepted.</div>
    <button type="button" class="faculty-access-close" id="facultyAccessClose">Continue</button>
  </div>
</div>
<script>
(function () {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar || document.querySelector('.faculty-sidebar-toggle')) return;
  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = 'faculty-sidebar-toggle';
  toggle.setAttribute('aria-label', 'Minimize sidebar');
  toggle.innerHTML = '<i class="fas fa-chevron-left" aria-hidden="true"></i>';
  sidebar.appendChild(toggle);

  if (window.localStorage.getItem('facultySidebarCollapsed') === '1') {
    document.body.classList.add('faculty-sidebar-collapsed');
  }

  toggle.addEventListener('click', function () {
    const collapsed = document.body.classList.toggle('faculty-sidebar-collapsed');
    window.localStorage.setItem('facultySidebarCollapsed', collapsed ? '1' : '0');
    toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Minimize sidebar');
  });
})();
</script>
<script>
(function () {
  const modal = document.getElementById('facultyAccessModal');
  const card = document.getElementById('facultyAccessCard');
  const title = document.getElementById('facultyAccessTitle');
  const icon = document.getElementById('facultyAccessIcon');
  const message = document.getElementById('facultyAccessMessage');
  const close = document.getElementById('facultyAccessClose');
  const currentUserId = @json((int) auth()->id());
  const pageLoadedAt = Date.now();
  let lastSeenNotificationId = 0;
  let notificationsInitialized = false;
  let queuedNotifications = [];
  let modalIsShowing = false;
  if (!modal || !card || !title || !icon || !message) return;

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    modalIsShowing = false;
    showNextNotification();
  }

  function showNextNotification() {
    if (modalIsShowing || !queuedNotifications.length) return;
    const notification = queuedNotifications.shift();
    const isDenied = notification.type === 'rfid_access_denied';
    card.classList.toggle('is-denied', isDenied);
    title.textContent = isDenied ? 'Access Denied' : 'Access Granted';
    icon.className = isDenied ? 'fas fa-xmark' : 'fas fa-check';
    message.textContent = isDenied
      ? (notification.data?.reason || notification.body || 'Access was denied.')
      : (notification.body || 'Your RFID card was accepted.');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    modalIsShowing = true;
  }

  async function pollNotifications() {
    try {
      const response = await fetch('{{ route('faculty.notifications.data') }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store',
      });
      if (!response.ok) return;
      const payload = await response.json();
      const notifications = Array.isArray(payload.data) ? payload.data : [];
      if (!notificationsInitialized) {
        notificationsInitialized = true;
        notifications.forEach(function (notification) {
          if (
            Number(notification.user_id) === currentUserId
            && ['rfid_access_granted', 'rfid_access_denied'].includes(notification.type)
            && Date.parse(notification.created_at) > pageLoadedAt
          ) {
            queuedNotifications.push(notification);
          }
        });
        if (notifications.length) {
          lastSeenNotificationId = Math.max(
            ...notifications.map(function (notification) { return Number(notification.id) || 0; })
          );
        }
        queuedNotifications.sort(function (first, second) {
          return Number(second.id) - Number(first.id);
        });
        showNextNotification();
        return;
      }

      const newNotifications = notifications
        .filter(function (notification) {
          return Number(notification.id) > lastSeenNotificationId;
        })
        .sort(function (first, second) {
          return Number(second.id) - Number(first.id);
        });

      if (notifications.length) {
        lastSeenNotificationId = Math.max(
          lastSeenNotificationId,
          ...notifications.map(function (notification) { return Number(notification.id) || 0; })
        );
      }

      newNotifications.forEach(function (notification) {
        if (
          Number(notification.user_id) !== currentUserId
          || !['rfid_access_granted', 'rfid_access_denied'].includes(notification.type)
        ) return;

        queuedNotifications.push(notification);
      });
      queuedNotifications.sort(function (first, second) {
        return Number(second.id) - Number(first.id);
      });
      showNextNotification();
    } catch (error) {
      // Notification polling is non-blocking and may retry on the next interval.
    }
  }

  close?.addEventListener('click', closeModal);
  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeModal();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeModal();
  });
  pollNotifications();
  window.setInterval(pollNotifications, 4000);
})();
</script>
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
