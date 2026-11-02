function openM(id, memberId) {
  const d = document.getElementById(id);
  const s = d.querySelector('select[name=member_id]');
  if (s) s.value = memberId || '';
  const dt = d.querySelector('input[type=date]');
  if (dt) dt.value = dt.max;
  d.showModal();
  (d.querySelector(memberId ? 'input[name=amount]' : 'select,input')).focus();
}
function fmt(el) {
  const v = el.value.replace(/\D/g, '');
  el.value = v ? Number(v).toLocaleString('id-ID') : '';
}
function payAll() {
  const o = document.querySelector('#mPay select').selectedOptions[0];
  if (o && o.dataset.sisa) document.getElementById('payAmt').value = Number(o.dataset.sisa).toLocaleString('id-ID');
}
function filt(v) {
  v = v.toLowerCase();
  document.querySelectorAll('#tb tr').forEach(r => r.style.display = r.dataset.n.includes(v) ? '' : 'none');
}


// Light/Dark theme preference.
(function(){const root=document.documentElement,key='debt-app-theme',toggle=document.getElementById('themeToggle'),mq=window.matchMedia('(prefers-color-scheme: dark)');function apply(mode){const actual=mode==='system'?(mq.matches?'dark':'light'):mode;root.dataset.theme=actual;if(toggle){toggle.setAttribute('aria-label',actual==='dark'?'Aktifkan tema terang':'Aktifkan tema gelap');toggle.title=actual==='dark'?'Aktifkan tema terang':'Aktifkan tema gelap';}}if(toggle)toggle.addEventListener('click',()=>{const next=root.dataset.theme==='dark'?'light':'dark';localStorage.setItem(key,next);apply(next);});mq.addEventListener?.('change',()=>{if((localStorage.getItem(key)||'light')==='system')apply('system');});})();

// Mobile sidebar drawer.
(function(){const toggle=document.getElementById('menuToggle'),backdrop=document.getElementById('sidebarBackdrop');if(!toggle||!backdrop)return;function close(){document.body.classList.remove('sidebar-open');toggle.setAttribute('aria-expanded','false');}toggle.addEventListener('click',()=>{const open=document.body.classList.toggle('sidebar-open');toggle.setAttribute('aria-expanded',String(open));});backdrop.addEventListener('click',close);document.querySelectorAll('.side-nav a').forEach(a=>a.addEventListener('click',close));document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});})();

function openEditMember(id, name, phone) {
  document.getElementById('editMemberId').value = id;
  document.getElementById('editMemberName').value = name;
  document.getElementById('editMemberPhone').value = phone;
  const dialog = document.getElementById('mEditMember');
  dialog.showModal();
  document.getElementById('editMemberName').focus();
}


// Custom application dialogs: no native prompt()/confirm() popups.
(function () {
  const dialog = document.getElementById('appActionDialog');
  if (!dialog) return;
  const title = document.getElementById('appActionTitle');
  const message = document.getElementById('appActionMessage');
  const reason = document.getElementById('appActionReason');
  const reasonLabel = document.getElementById('appActionReasonLabel');
  const confirmButton = document.getElementById('appActionConfirm');
  const cancelButton = document.getElementById('appActionCancel');
  let onConfirm = null;

  function openDialog(options) {
    title.textContent = options.title || 'Konfirmasi tindakan';
    message.textContent = options.message || '';
    reason.hidden = !options.requireReason;
    reasonLabel.hidden = !options.requireReason;
    reason.value = '';
    confirmButton.textContent = options.confirmText || 'Lanjutkan';
    confirmButton.classList.toggle('danger-action', !!options.danger);
    onConfirm = options.onConfirm;
    dialog.showModal();
    if (options.requireReason) reason.focus();
    else cancelButton.focus();
  }

  cancelButton.addEventListener('click', () => dialog.close());
  confirmButton.addEventListener('click', () => {
    if (reason.hidden === false && !reason.value.trim()) {
      reason.setCustomValidity('Alasan pembatalan wajib diisi.');
      reason.reportValidity();
      reason.addEventListener('input', () => reason.setCustomValidity(''), { once: true });
      return;
    }
    const action = onConfirm;
    dialog.close();
    if (typeof action === 'function') action(reason.value.trim());
  });

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.hasAttribute('data-cancel-debt')) {
      event.preventDefault();
      openDialog({
        title: 'Batalkan hutang',
        message: 'Transaksi ini akan ditandai sebagai dibatalkan dan tidak lagi dihitung dalam saldo. Tuliskan alasan pembatalan untuk melanjutkan.',
        requireReason: true,
        confirmText: 'Batalkan hutang',
        danger: true,
        onConfirm: value => {
          const field = form.querySelector('input[name="reason"]');
          if (field) field.value = value;
          form.submit();
        }
      });
      return;
    }
    if (form.hasAttribute('data-confirm')) {
      event.preventDefault();
      openDialog({
        title: 'Konfirmasi pembatalan',
        message: form.getAttribute('data-confirm'),
        confirmText: 'Ya, batalkan',
        danger: true,
        onConfirm: () => form.submit()
      });
    }
  });

  const toast = document.querySelector('.toast-notice');
  if (toast) {
    const dismiss = () => {
      toast.classList.add('toast-hide');
      window.setTimeout(() => toast.remove(), 220);
    };
    toast.querySelector('.toast-close')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 4800);
  }
})();
