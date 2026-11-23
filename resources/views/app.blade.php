<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f6f2">
    <title>Pendekin — Tautan lebih ringkas</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body>
    <div class="page-glow page-glow-one" aria-hidden="true"></div>
    <div class="page-glow page-glow-two" aria-hidden="true"></div>

    <div class="site-shell">
        <header class="site-header">
            <a class="brand" href="/" aria-label="Pendekin, beranda">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 36 36" fill="none">
                        <path d="M14.4 21.6 11.6 24.4a5 5 0 0 1-7.1-7.1l5-5a5 5 0 0 1 7.1 0" />
                        <path d="m21.6 14.4 2.8-2.8a5 5 0 0 1 7.1 7.1l-5 5a5 5 0 0 1-7.1 0" />
                        <path d="m13.5 22.5 9-9" />
                    </svg>
                </span>
                <span class="brand-name">pendekin<span>.</span></span>
            </a>

            <div class="header-right">
                <div class="health-indicator" id="health-indicator" aria-live="polite">
                    <span class="health-dot"></span>
                    <span id="health-label">Memeriksa layanan</span>
                </div>
                <div class="header-user" id="header-user" hidden>
                    <span class="user-avatar" id="user-avatar">P</span>
                    <span class="header-user-name" id="header-user-name"></span>
                    <button class="button button-quiet button-small" id="logout-button" type="button">Keluar</button>
                </div>
            </div>
        </header>

        <main>
            <section class="auth-layout" id="auth-view">
                <div class="intro-panel">
                    <div class="eyebrow"><span class="eyebrow-icon">✳</span> LINK LEBIH RINGKAS, CERITA LEBIH PANJANG
                    </div>
                    <h1>Bagikan tautan.<br><span>Bukan kerumitannya.</span></h1>
                    <p class="intro-copy">Satu tempat sederhana untuk merapikan link, membagikannya, dan melihat
                        seberapa jauh link itu berjalan.</p>

                    <div class="preview-card">
                        <div class="preview-topline">
                            <span class="preview-live"><i></i> LINK AKTIF</span>
                            <span class="preview-menu" aria-hidden="true">•••</span>
                        </div>
                        <div class="preview-content">
                            <div class="preview-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7" />
                                </svg>
                            </div>
                            <div class="preview-link-text">
                                <strong>pendekin.id/cerita</strong>
                                <span>tokobunga.example/promo-musim-semi...</span>
                            </div>
                            <span class="preview-arrow" aria-hidden="true">↗</span>
                        </div>
                        <div class="preview-footer">
                            <span><b>1.284</b> klik</span>
                            <span class="preview-chart"
                                aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
                            <span>7 hari terakhir</span>
                        </div>
                    </div>

                    <div class="intro-footnote">
                        <div class="avatar-stack" aria-hidden="true">
                            <span>R</span><span>A</span><span>D</span><span>+</span></div>
                        <span>Rapi untuk dibagikan, mudah untuk dipantau.</span>
                    </div>
                </div>

                <div class="auth-card">
                    <div class="auth-card-heading">
                        <span class="card-kicker" id="auth-kicker">SELAMAT DATANG KEMBALI</span>
                        <h2 id="auth-title">Masuk ke akunmu</h2>
                        <p id="auth-subtitle">Lanjutkan mengelola tautan pendekmu.</p>
                    </div>

                    <div class="auth-tabs" role="tablist" aria-label="Jenis akun">
                        <button class="auth-tab is-selected" id="login-tab" type="button" role="tab"
                            aria-selected="true">Masuk</button>
                        <button class="auth-tab" id="register-tab" type="button" role="tab"
                            aria-selected="false">Buat akun</button>
                    </div>

                    <form id="auth-form" novalidate>
                        <label class="form-field" id="name-field" hidden>
                            <span>Nama</span>
                            <input name="name" type="text" autocomplete="name" placeholder="Nama lengkap">
                        </label>
                        <label class="form-field">
                            <span>Email</span>
                            <input name="email" type="email" autocomplete="email" placeholder="nama@email.com"
                                required>
                        </label>
                        <label class="form-field">
                            <span>Kata sandi</span>
                            <input name="password" type="password" autocomplete="current-password"
                                placeholder="Minimal 8 karakter" required>
                        </label>
                        <label class="form-field" id="password-confirmation-field" hidden>
                            <span>Ulangi kata sandi</span>
                            <input name="password_confirmation" type="password" autocomplete="new-password"
                                placeholder="Masukkan ulang kata sandi">
                        </label>
                        <div class="form-message" id="auth-message" role="alert" hidden></div>
                        <button class="button button-primary button-wide" id="auth-submit" type="submit">
                            <span>Masuk ke akun</span>
                        </button>
                    </form>

                    <p class="auth-note"><span class="lock-icon" aria-hidden="true">⌑</span> Akunmu aman. Tautanmu
                        tetap milikmu.</p>
                </div>
            </section>

            <section class="dashboard" id="dashboard-view" hidden>
                <div class="dashboard-heading">
                    <div>
                        <div class="eyebrow"><span class="eyebrow-icon">✳</span> RUANG KERJAMU</div>
                        <h1>Semua tautan,<br class="mobile-break"> <span>satu tempat.</span></h1>
                        <p>Ringkas tautan baru atau pantau yang sudah kamu bagikan.</p>
                    </div>
                    <a class="button button-outline" href="#create-link">+ <span>Buat tautan</span></a>
                </div>

                <div class="stats-overview" aria-label="Ringkasan tautan">
                    <article class="overview-card">
                        <div class="overview-icon icon-lilac" aria-hidden="true">↗</div>
                        <div><span class="overview-label">Total tautan</span><strong id="total-links">—</strong></div>
                        <span class="overview-caption">di akunmu</span>
                    </article>
                    <article class="overview-card">
                        <div class="overview-icon icon-mint" aria-hidden="true">✓</div>
                        <div><span class="overview-label">Tautan aktif</span><strong id="active-links">—</strong>
                        </div>
                        <span class="overview-caption">di halaman ini</span>
                    </article>
                    <article class="overview-card">
                        <div class="overview-icon icon-peach" aria-hidden="true">◷</div>
                        <div><span class="overview-label">Perlu perhatian</span><strong id="inactive-links">—</strong>
                        </div>
                        <span class="overview-caption">di halaman ini</span>
                    </article>
                </div>

                <div class="workspace-grid">
                    <section class="panel create-panel" id="create-link">
                        <div class="panel-heading">
                            <div>
                                <span class="card-kicker">MULAI DARI SINI</span>
                                <h2>Buat tautan pendek</h2>
                            </div>
                            <span class="panel-heading-icon" aria-hidden="true">↗</span>
                        </div>
                        <form id="create-form" novalidate>
                            <label class="form-field">
                                <span>URL tujuan <b>*</b></span>
                                <div class="input-with-icon">
                                    <input name="original_url" type="url" inputmode="url"
                                        placeholder="https://website.com/halaman-panjang" required>
                                </div>
                            </label>
                            <div class="form-row">
                                <label class="form-field">
                                    <span>Alias khusus <small>opsional</small></span>
                                    <div class="input-with-prefix">
                                        <span id="short-url-prefix">/</span>
                                        <input name="alias" type="text" placeholder="promo-kamu" minlength="3"
                                            maxlength="30" pattern="[A-Za-z0-9-]+">
                                    </div>
                                    <small class="field-hint">3–30 karakter, huruf, angka, atau tanda hubung.</small>
                                </label>
                                <label class="form-field">
                                    <span>Kedaluwarsa <small>opsional</small></span>
                                    <input name="expires_at" type="datetime-local">
                                    <small class="field-hint">Kosongkan agar link berlaku selamanya.</small>
                                </label>
                            </div>
                            <div class="form-message" id="create-message" role="alert" hidden></div>
                            <button class="button button-primary button-wide" id="create-submit" type="submit">
                                <span>Buat tautan pendek</span>
                            </button>
                        </form>
                        <div class="create-footnote"><span aria-hidden="true">✦</span> Tanpa alias? Kami buatkan kode
                            unik untukmu.</div>
                    </section>

                    <section class="panel links-panel">
                        <div class="links-panel-heading">
                            <div>
                                <span class="card-kicker">KOLEKSI TAUTAN</span>
                                <h2>Tautanmu <span id="links-count"></span></h2>
                            </div>
                            <label class="search-box">
                                <span aria-hidden="true">⌕</span>
                                <input id="link-search" type="search" placeholder="Cari tautan..."
                                    aria-label="Cari tautan">
                            </label>
                        </div>
                        <div class="links-list" id="links-list" aria-live="polite">
                            <div class="loading-state"><span class="spinner"></span> Memuat tautanmu...</div>
                        </div>
                        <div class="empty-state" id="empty-state" hidden>

                            <strong>Belum ada tautan di sini</strong>
                            <span>Buat tautan pendek pertamamu dari form di sebelah.</span>
                        </div>
                        <div class="pagination" id="pagination" hidden>
                            <span id="pagination-label"></span>
                            <div>
                                <button class="button button-quiet button-small" id="previous-page" type="button">←
                                    Sebelumnya</button>
                                <button class="button button-quiet button-small" id="next-page"
                                    type="button">Berikutnya →</button>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <span>© {{ date('Y') }} Pendekin</span>
            <span>Lebih ringkas, lebih mudah diingat.</span>
        </footer>
    </div>

    <dialog class="stats-dialog" id="stats-dialog" aria-labelledby="stats-title">
        <div class="dialog-heading">
            <div>
                <span class="card-kicker">PERFORMA TAUTAN</span>
                <h2 id="stats-title">Statistik klik</h2>
                <p class="dialog-url" id="stats-link-url"></p>
            </div>
            <button class="dialog-close" id="close-stats" type="button" aria-label="Tutup statistik">×</button>
        </div>
        <form class="date-filter" id="stats-filter">
            <label class="form-field"><span>Dari</span><input name="from" type="date"></label>
            <label class="form-field"><span>Sampai</span><input name="to" type="date"></label>
            <button class="button button-outline button-small" type="submit">Terapkan</button>
        </form>
        <div class="stats-total"><strong id="stats-total">—</strong><span>total klik dalam rentang ini</span></div>
        <div class="daily-stats" id="daily-stats"></div>
        <div class="loading-state" id="stats-loading" hidden><span class="spinner"></span> Memuat statistik...</div>
        <div class="form-message" id="stats-message" role="alert" hidden></div>
    </dialog>

    <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>

</html>
