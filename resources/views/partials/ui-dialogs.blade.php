{{-- Shared UI helpers: accessible confirm/alert dialogs, toasts, [data-confirm] support and a visible keyboard focus ring.
     Replaces native window.alert/confirm. Safe to include more than once (guarded). --}}
<style>
:focus-visible{outline:3px solid #6785d4!important;outline-offset:2px!important}
.sui-overlay{position:fixed;inset:0;z-index:3000;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(7,22,64,.45);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px)}
.sui-overlay.sui-open{display:flex}
.sui-dialog{width:min(430px,100%);padding:26px 26px 22px;border-radius:18px;background:#fff;color:#0f172a;box-shadow:0 24px 70px rgba(7,22,64,.28);font-family:'Instrument Sans',system-ui,-apple-system,'Segoe UI',sans-serif;animation:sui-pop .16s ease-out}
.sui-head{display:flex;align-items:flex-start;gap:14px}
.sui-icon{flex:0 0 42px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:12px;font-size:1.05rem;background:#e8ecfb;color:#1a2f80}
.sui-icon.sui-danger{background:#fee2e2;color:#b91c1c}
.sui-icon.sui-warn{background:#fef3c7;color:#92400e}
.sui-title{margin:2px 0 6px;font:800 1.05rem 'Sora','Instrument Sans',system-ui,sans-serif;color:#0b1640}
.sui-message{margin:0;color:#475569;font-size:.9rem;line-height:1.55;white-space:pre-line;overflow-wrap:anywhere}
.sui-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px;margin-top:22px}
.sui-btn{min-height:40px;padding:9px 18px;border:1px solid #cdd3e0;border-radius:10px;background:#fff;color:#334155;cursor:pointer;font:700 .85rem 'Instrument Sans',system-ui,sans-serif;transition:background-color .15s,border-color .15s}
.sui-btn:hover{background:#f1f5f9}
.sui-btn.sui-primary{border-color:#0b1640;background:#0b1640;color:#fff}
.sui-btn.sui-primary:hover{background:#16286b}
.sui-btn.sui-danger{border-color:#b91c1c;background:#b91c1c;color:#fff}
.sui-btn.sui-danger:hover{background:#991b1b}
.sui-toasts{position:fixed;top:16px;left:50%;z-index:3100;display:grid;gap:8px;width:min(440px,calc(100vw - 32px));transform:translateX(-50%);pointer-events:none}
.sui-toast{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid #e2e8f0;border-left:4px solid #1a2f80;border-radius:12px;background:#fff;color:#0f172a;box-shadow:0 12px 32px rgba(15,23,42,.16);font:500 .86rem/1.45 'Instrument Sans',system-ui,sans-serif;pointer-events:auto;animation:sui-drop .2s ease-out}
.sui-toast.sui-success{border-left-color:#0f9d58}
.sui-toast.sui-error{border-left-color:#dc2626}
.sui-toast.sui-warn{border-left-color:#d97706}
.sui-toast span{flex:1;overflow-wrap:anywhere;white-space:pre-line}
.sui-toast button{border:0;background:none;color:#64748b;cursor:pointer;font-size:1.1rem;line-height:1}
@keyframes sui-pop{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:none}}
@keyframes sui-drop{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.sui-dialog,.sui-toast{animation:none}}
</style>
<script>
(function () {
  if (window.SmartUI) return;
  var overlay, dialog, titleEl, msgEl, iconEl, cancelBtn, okBtn, lastFocus, resolver, toastBox;

  function build() {
    if (overlay) return;
    overlay = document.createElement('div');
    overlay.className = 'sui-overlay';
    overlay.innerHTML =
      '<div class="sui-dialog" role="alertdialog" aria-modal="true" aria-labelledby="suiTitle" aria-describedby="suiMessage">' +
        '<div class="sui-head"><div class="sui-icon" id="suiIcon" aria-hidden="true"></div>' +
        '<div><h2 class="sui-title" id="suiTitle"></h2><p class="sui-message" id="suiMessage"></p></div></div>' +
        '<div class="sui-actions"><button type="button" class="sui-btn" id="suiCancel"></button>' +
        '<button type="button" class="sui-btn sui-primary" id="suiOk"></button></div></div>';
    document.body.appendChild(overlay);
    dialog = overlay.firstChild;
    titleEl = overlay.querySelector('#suiTitle');
    msgEl = overlay.querySelector('#suiMessage');
    iconEl = overlay.querySelector('#suiIcon');
    cancelBtn = overlay.querySelector('#suiCancel');
    okBtn = overlay.querySelector('#suiOk');
    cancelBtn.addEventListener('click', function () { close(false); });
    okBtn.addEventListener('click', function () { close(true); });
    overlay.addEventListener('mousedown', function (e) { if (e.target === overlay && !cancelBtn.hidden) close(false); });
    document.addEventListener('keydown', function (e) {
      if (!overlay.classList.contains('sui-open')) return;
      if (e.key === 'Escape') { e.preventDefault(); close(cancelBtn.hidden); }
      if (e.key === 'Tab') {
        var f = cancelBtn.hidden ? [okBtn] : [cancelBtn, okBtn];
        var i = f.indexOf(document.activeElement);
        e.preventDefault();
        f[(i + (e.shiftKey ? f.length - 1 : 1)) % f.length].focus();
      }
    });
  }

  function close(result) {
    overlay.classList.remove('sui-open');
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
    var r = resolver; resolver = null;
    if (r) r(result);
  }

  function open(message, o, withCancel) {
    build();
    o = o || {};
    lastFocus = document.activeElement;
    titleEl.textContent = o.title || (withCancel ? 'Please confirm' : 'Notice');
    msgEl.textContent = message == null ? '' : String(message);
    var danger = !!o.danger;
    iconEl.className = 'sui-icon ' + (danger ? 'sui-danger' : (withCancel ? 'sui-warn' : ''));
    iconEl.innerHTML = '<i class="fas ' + (danger ? 'fa-triangle-exclamation' : (withCancel ? 'fa-circle-question' : 'fa-circle-info')) + '"></i>';
    okBtn.textContent = o.confirmText || (withCancel ? 'Confirm' : 'OK');
    okBtn.className = 'sui-btn ' + (danger ? 'sui-danger' : 'sui-primary');
    cancelBtn.textContent = o.cancelText || 'Cancel';
    cancelBtn.hidden = !withCancel;
    overlay.classList.add('sui-open');
    (withCancel && danger ? cancelBtn : okBtn).focus();
    return new Promise(function (resolve) { resolver = resolve; });
  }

  function toast(message, type) {
    if (!toastBox) {
      toastBox = document.createElement('div');
      toastBox.className = 'sui-toasts';
      toastBox.setAttribute('aria-live', 'polite');
      document.body.appendChild(toastBox);
    }
    var t = document.createElement('div');
    t.className = 'sui-toast sui-' + (type || 'info');
    t.setAttribute('role', type === 'error' ? 'alert' : 'status');
    var text = document.createElement('span'); text.textContent = message;
    var x = document.createElement('button'); x.type = 'button'; x.setAttribute('aria-label', 'Dismiss'); x.innerHTML = '&times;';
    t.appendChild(text); t.appendChild(x); toastBox.appendChild(t);
    var timer = setTimeout(remove, type === 'error' ? 6500 : 4500);
    function remove() { clearTimeout(timer); if (t.parentNode) t.parentNode.removeChild(t); }
    x.addEventListener('click', remove);
  }

  window.SmartUI = {
    confirm: function (message, o) { return open(message, o, true); },
    alert: function (message, o) { return open(message, o, false); },
    toast: toast
  };

  // Declarative confirmation: <button data-confirm="Are you sure?" data-confirm-title="..." data-confirm-button="...">
  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest('[data-confirm]') : null;
    if (!el || el.__suiOk) return;
    e.preventDefault(); e.stopPropagation();
    window.SmartUI.confirm(el.getAttribute('data-confirm'), {
      title: el.getAttribute('data-confirm-title') || undefined,
      confirmText: el.getAttribute('data-confirm-button') || undefined,
      danger: el.getAttribute('data-confirm-danger') !== 'false'
    }).then(function (ok) {
      if (!ok) return;
      el.__suiOk = true;
      if (el.form && el.type === 'submit') { if (el.form.requestSubmit) el.form.requestSubmit(el); else el.form.submit(); }
      else el.click();
      setTimeout(function () { el.__suiOk = false; }, 0);
    });
  }, true);
})();
</script>

