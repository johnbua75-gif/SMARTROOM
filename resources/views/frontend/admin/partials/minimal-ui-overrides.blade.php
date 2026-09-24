<style>
/* Minimal UI overrides for admin pages (sidebar intentionally untouched). */
.main {
  background: linear-gradient(180deg, #f7f9fc 0%, #f4f6f9 100%);
}

.main :where(
  .stat-card,
  .sum-card,
  .room-card,
  .schedule-card,
  .instructor-card,
  .stat-mini,
  .chart-card,
  .activity-card,
  .quick-card,
  .table-wrap,
  .table-card,
  .log-table-wrap,
  .modal,
  .modal-panel,
  .create-modal,
  .edit-container
) {
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
}

.main :where(
  .stat-card,
  .sum-card,
  .room-card,
  .schedule-card,
  .instructor-card,
  .stat-mini,
  .chart-card,
  .activity-card,
  .quick-card,
  .table-wrap,
  .table-card,
  .log-table-wrap,
  .modal,
  .modal-panel,
  .create-modal,
  .edit-container
):hover {
  transform: none !important;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06) !important;
}

.main :where(
  .btn-primary,
  .btn-add,
  .btn-export,
  .btn-reissue,
  .btn-deactivate,
  .card-action-btn,
  .act-btn,
  .btn-view-detail,
  .btn-icon,
  .page-btn,
  .tab-btn,
  .btn-filter,
  .view-btn,
  .filter-tab,
  .cal-arrow,
  .cal-day,
  .cal-icon-btn,
  .sc-more-btn,
  .sc-edit-btn,
  .notif-btn,
  .modal-close,
  .btn-cancel,
  .ai-action,
  a
) {
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.main :where(
  .btn-primary,
  .btn-add,
  .btn-export,
  .btn-reissue,
  .btn-deactivate,
  .card-action-btn,
  .act-btn,
  .btn-view-detail,
  .btn-icon,
  .page-btn,
  .tab-btn,
  .btn-filter,
  .view-btn,
  .filter-tab,
  .cal-arrow,
  .cal-day,
  .cal-icon-btn,
  .sc-more-btn,
  .sc-edit-btn,
  .notif-btn,
  .modal-close,
  .btn-cancel,
  .ai-action
):hover {
  transform: none !important;
  box-shadow: none !important;
}

.main :where(.tab-btn:hover, .filter-tab:hover, .view-btn:hover:not(.active), .cal-day:hover) {
  background: #f3f5f8 !important;
  color: var(--text-secondary) !important;
}

.main :where(tbody tr:hover, .status-row:hover, .log-table tbody tr:hover) {
  background: #fafbfc !important;
}

.main .ai-action:hover {
  text-decoration: none !important;
}

/* Minimal motion layer: subtle, clean, non-intrusive. */
@keyframes proFadeUp {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.main .pro-motion-target {
  opacity: 0;
  transform: translateY(8px);
}

.main .pro-motion-target.pro-motion-in {
  animation: proFadeUp 380ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.main :where(
  .btn-primary,
  .btn-add,
  .btn-export,
  .btn-reissue,
  .btn-deactivate,
  .card-action-btn,
  .act-btn,
  .btn-view-detail,
  .btn-icon,
  .page-btn,
  .tab-btn,
  .btn-filter,
  .view-btn,
  .filter-tab,
  .cal-arrow,
  .cal-day,
  .cal-icon-btn,
  .sc-more-btn,
  .sc-edit-btn,
  .notif-btn,
  .modal-close,
  .btn-cancel,
  .ai-action
) {
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, transform 0.18s ease;
}

.main :where(
  .btn-primary,
  .btn-add,
  .btn-export,
  .btn-reissue,
  .btn-deactivate,
  .card-action-btn,
  .act-btn,
  .btn-view-detail,
  .btn-icon,
  .page-btn,
  .tab-btn,
  .btn-filter,
  .view-btn,
  .filter-tab,
  .cal-arrow,
  .cal-day,
  .cal-icon-btn,
  .sc-more-btn,
  .sc-edit-btn,
  .notif-btn,
  .modal-close,
  .btn-cancel,
  .ai-action
):active {
  transform: translateY(1px);
}

@media (prefers-reduced-motion: reduce) {
  .main .pro-motion-target,
  .main .pro-motion-target.pro-motion-in {
    opacity: 1;
    transform: none;
    animation: none !important;
  }

  .main :where(
    .btn-primary,
    .btn-add,
    .btn-export,
    .btn-reissue,
    .btn-deactivate,
    .card-action-btn,
    .act-btn,
    .btn-view-detail,
    .btn-icon,
    .page-btn,
    .tab-btn,
    .btn-filter,
    .view-btn,
    .filter-tab,
    .cal-arrow,
    .cal-day,
    .cal-icon-btn,
    .sc-more-btn,
    .sc-edit-btn,
    .notif-btn,
    .modal-close,
    .btn-cancel,
    .ai-action,
    a
  ) {
    transition: none !important;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  var root = document.querySelector('.main');
  if (!root) {
    return;
  }

  var targets = root.querySelectorAll([
    '.page-header',
    '.stats-strip',
    '.summary-grid',
    '.filter-bar',
    '.controls-bar',
    '.manage-box',
    '.table-card',
    '.table-wrap',
    '.chart-card',
    '.activity-card',
    '.room-grid',
    '.schedule-grid',
    '.mini-chart-card',
    '.bottom-row',
    '.room-activity-card'
  ].join(','));

  var delayStep = 55;
  targets.forEach(function (node, idx) {
    node.classList.add('pro-motion-target');
    setTimeout(function () {
      node.classList.add('pro-motion-in');
    }, idx * delayStep);
  });
});
</script>
