<div id="globalLoadingOverlay" class="global-loading-overlay" aria-live="polite" aria-busy="false">
  <div class="global-loading-card" role="status" aria-label="Loading">
    <div class="global-loading-spinner" aria-hidden="true"></div>
    <div id="globalLoadingText" class="global-loading-text">Please wait...</div>
  </div>
</div>

<style>
.global-loading-overlay {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  background: rgba(11, 22, 64, 0.42);
  backdrop-filter: blur(2px);
  z-index: 3500;
}

.global-loading-overlay.is-visible {
  display: flex;
}

.global-loading-card {
  min-width: 220px;
  max-width: 360px;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid #dbe3f3;
  background: #ffffff;
  box-shadow: 0 16px 40px rgba(11, 22, 64, 0.24);
  display: flex;
  align-items: center;
  gap: 10px;
}

.global-loading-spinner {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #dbeafe;
  border-top-color: #1d4ed8;
  animation: global-loading-spin 0.8s linear infinite;
}

.global-loading-text {
  font-size: 0.84rem;
  font-weight: 600;
  color: #1f2937;
}

@keyframes global-loading-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<script>
(function () {
  var overlay = document.getElementById('globalLoadingOverlay');
  var textEl = document.getElementById('globalLoadingText');

  window.showGlobalLoading = function (message) {
    if (!overlay) {
      return;
    }

    textEl.textContent = message || 'Please wait...';
    overlay.classList.add('is-visible');
    overlay.setAttribute('aria-busy', 'true');
  };

  window.hideGlobalLoading = function () {
    if (!overlay) {
      return;
    }

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-busy', 'false');
  };
})();
</script>
