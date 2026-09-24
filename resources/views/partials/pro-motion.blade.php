<style>
@keyframes proFadeUpGlobal {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.pro-motion-target-global {
  opacity: 0;
  transform: translateY(8px);
}

.pro-motion-target-global.pro-motion-in-global {
  animation: proFadeUpGlobal 380ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

:where(button, .btn, .btn-primary, .btn-secondary, .card-action-btn, a):active {
  transform: translateY(1px);
}

@media (prefers-reduced-motion: reduce) {
  .pro-motion-target-global,
  .pro-motion-target-global.pro-motion-in-global {
    opacity: 1;
    transform: none;
    animation: none !important;
  }

  :where(*, *::before, *::after) {
    scroll-behavior: auto !important;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  var selectors = [
    '.page-header',
    '.hero',
    '.stats-strip',
    '.summary-grid',
    '.filter-bar',
    '.search-filter-row',
    '.controls-bar',
    '.table-card',
    '.table-wrap',
    '.chart-card',
    '.activity-card',
    '.room-grid',
    '.rooms-grid',
    '.schedule-grid',
    '.mini-chart-card',
    '.bottom-row',
    '.room-activity-card'
  ].join(',');

  var targets = document.querySelectorAll(selectors);
  var delayStep = 55;

  targets.forEach(function (node, idx) {
    node.classList.add('pro-motion-target-global');
    setTimeout(function () {
      node.classList.add('pro-motion-in-global');
    }, idx * delayStep);
  });
});
</script>
