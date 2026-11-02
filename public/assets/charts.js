(function () {
  const rows = Array.isArray(window.debtDashboardData) ? window.debtDashboardData : [];
  const labels = rows.map(row => row.label);
  const debt = rows.map(row => Number(row.debt));
  const payment = rows.map(row => Number(row.payment));
  const root = document.documentElement;
  const css = name => getComputedStyle(root).getPropertyValue(name).trim();
  const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);

  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.color = css('--mute');
  Chart.defaults.borderColor = css('--line');
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.tooltip.callbacks.label = context => `${context.dataset.label}: ${money(context.parsed.y)}`;

  const lineCanvas = document.getElementById('trendChart');
  if (lineCanvas) new Chart(lineCanvas, {
    type: 'line',
    data: { labels, datasets: [
      { label: 'Hutang baru', data: debt, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.10)', fill: true, tension: .32, pointRadius: labels.length > 45 ? 0 : 3, pointHoverRadius: 5, borderWidth: 2 },
      { label: 'Pembayaran', data: payment, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.06)', fill: false, tension: .32, pointRadius: labels.length > 45 ? 0 : 3, pointHoverRadius: 5, borderWidth: 2 }
    ] },
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom', align: 'start' } }, scales: { y: { beginAtZero: true, ticks: { callback: value => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value) } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0 } } } }
  });

  const barCanvas = document.getElementById('comparisonChart');
  if (barCanvas) new Chart(barCanvas, {
    type: 'bar',
    data: { labels, datasets: [
      { label: 'Hutang baru', data: debt, backgroundColor: '#3b82f6', borderRadius: 5, maxBarThickness: 18 },
      { label: 'Pembayaran', data: payment, backgroundColor: '#22c55e', borderRadius: 5, maxBarThickness: 18 }
    ] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', align: 'start' } }, scales: { y: { beginAtZero: true, ticks: { callback: value => new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value) } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } } } }
  });
})();
