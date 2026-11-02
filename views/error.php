<section class="error-page">
  <div class="error-card">
    <div class="error-code">404</div>
    <div class="error-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7.5"/><path d="m16.5 16.5 4 4M8.5 11h5M11 8.5v5"/></svg>
    </div>
    <span class="error-eyebrow">HALAMAN TIDAK DITEMUKAN</span>
    <h1>Oops, halaman ini tidak tersedia.</h1>
    <p>Alamat yang Anda buka mungkin salah, sudah berubah, atau halaman tersebut memang tidak tersedia.</p>
    <div class="error-url"><span>Alamat yang diminta</span><code><?= e((string)($_SERVER['REQUEST_URI'] ?? '')) ?></code></div>
    <a class="btn pri error-home-link" href="?">
      <svg viewBox="0 0 24 24" fill="none"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg>
      Kembali ke Dashboard
    </a>
  </div>
</section>
