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
