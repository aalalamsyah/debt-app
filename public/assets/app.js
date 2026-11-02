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
