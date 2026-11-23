const tokenStorageKey = 'pendekin_token';
const userStorageKey = 'pendekin_user';

const state = {
    token: localStorage.getItem(tokenStorageKey),
    user: readStoredUser(),
    links: [],
    currentPage: 1,
    lastPage: 1,
    totalLinks: 0,
    statsLink: null,
    isRegistering: false,
};

const elements = {
    authView: document.querySelector('#auth-view'),
    dashboardView: document.querySelector('#dashboard-view'),
    headerUser: document.querySelector('#header-user'),
    headerUserName: document.querySelector('#header-user-name'),
    userAvatar: document.querySelector('#user-avatar'),
    authForm: document.querySelector('#auth-form'),
    authMessage: document.querySelector('#auth-message'),
    authTitle: document.querySelector('#auth-title'),
    authSubtitle: document.querySelector('#auth-subtitle'),
    authKicker: document.querySelector('#auth-kicker'),
    authSubmit: document.querySelector('#auth-submit'),
    nameField: document.querySelector('#name-field'),
    passwordConfirmationField: document.querySelector('#password-confirmation-field'),
    createForm: document.querySelector('#create-form'),
    createMessage: document.querySelector('#create-message'),
    createSubmit: document.querySelector('#create-submit'),
    linksList: document.querySelector('#links-list'),
    emptyState: document.querySelector('#empty-state'),
    pagination: document.querySelector('#pagination'),
    statsDialog: document.querySelector('#stats-dialog'),
};

function readStoredUser() {
    try {
        return JSON.parse(localStorage.getItem(userStorageKey) || 'null');
    } catch {
        return null;
    }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
}

function apiErrorMessage(body, fallback) {
    const messages = Object.values(body?.errors || {}).flat();

    return messages.length > 0 ? messages.join(' ') : (body?.message || fallback);
}

async function apiRequest(path, { method = 'GET', body, authenticated = true } = {}) {
    const headers = { Accept: 'application/json' };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    if (authenticated && state.token) {
        headers.Authorization = `Bearer ${state.token}`;
    }

    let response;

    try {
        response = await fetch(`/api${path}`, {
            method,
            headers,
            body: body === undefined ? undefined : JSON.stringify(body),
        });
    } catch {
        throw new Error('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.');
    }

    const responseBody = await response.json().catch(() => null);

    if (response.status === 401 && authenticated && state.token) {
        clearAuthentication();
        showAuth();
    }

    if (!response.ok) {
        throw new Error(apiErrorMessage(responseBody, 'Permintaan belum berhasil. Silakan coba lagi.'));
    }

    return responseBody;
}

function setMessage(element, message, isSuccess = false) {
    element.textContent = message;
    element.classList.toggle('is-success', isSuccess);
    element.hidden = !message;
}

function setButtonBusy(button, busy, label) {
    button.disabled = busy;
    button.querySelector('span').textContent = busy ? label : button.dataset.label;
}

function setAuthMode(isRegistering) {
    state.isRegistering = isRegistering;
    elements.authForm.reset();
    setMessage(elements.authMessage, '');
    elements.nameField.hidden = !isRegistering;
    elements.passwordConfirmationField.hidden = !isRegistering;
    elements.authForm.elements.password.autocomplete = isRegistering ? 'new-password' : 'current-password';
    elements.authTitle.textContent = isRegistering ? 'Buat akun Pendekin' : 'Masuk ke akunmu';
    elements.authSubtitle.textContent = isRegistering
        ? 'Mulai rapikan dan pantau tautanmu.'
        : 'Lanjutkan mengelola tautan pendekmu.';
    elements.authKicker.textContent = isRegistering ? 'MULAI GRATIS' : 'SELAMAT DATANG KEMBALI';
    elements.authSubmit.querySelector('span').textContent = isRegistering ? 'Buat akun' : 'Masuk ke akun';
    elements.authSubmit.dataset.label = isRegistering ? 'Buat akun' : 'Masuk ke akun';
    document.querySelector('#login-tab').classList.toggle('is-selected', !isRegistering);
    document.querySelector('#login-tab').setAttribute('aria-selected', String(!isRegistering));
    document.querySelector('#register-tab').classList.toggle('is-selected', isRegistering);
    document.querySelector('#register-tab').setAttribute('aria-selected', String(isRegistering));
}

function showAuth() {
    elements.authView.hidden = false;
    elements.dashboardView.hidden = true;
    elements.headerUser.hidden = true;
    setAuthMode(false);
}

function showDashboard(user) {
    elements.authView.hidden = true;
    elements.dashboardView.hidden = false;
    elements.headerUser.hidden = false;
    const displayName = user?.name || user?.email || 'Pengguna';
    elements.headerUserName.textContent = displayName;
    elements.userAvatar.textContent = displayName.trim().charAt(0).toUpperCase() || 'P';
}

function saveAuthentication(token, user) {
    state.token = token;
    state.user = user;
    localStorage.setItem(tokenStorageKey, token);
    localStorage.setItem(userStorageKey, JSON.stringify(user));
}

function clearAuthentication() {
    state.token = null;
    state.user = null;
    localStorage.removeItem(tokenStorageKey);
    localStorage.removeItem(userStorageKey);
    state.links = [];
    state.currentPage = 1;
    state.lastPage = 1;
    state.totalLinks = 0;
}

function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.className = `toast${isError ? ' is-error' : ''}`;
    toast.textContent = message;
    document.querySelector('#toast-region').append(toast);
    window.setTimeout(() => toast.remove(), 3600);
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`));
}

function formatDateTime(value) {
    if (!value) {
        return 'Tidak kedaluwarsa';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

function linkStatus(link) {
    const isExpired = link.expires_at && new Date(link.expires_at).getTime() <= Date.now();

    if (isExpired) {
        return { label: 'Kedaluwarsa', active: false };
    }

    if (!link.is_active) {
        return { label: 'Nonaktif', active: false };
    }

    return { label: 'Aktif', active: true };
}

function renderLinks() {
    const searchTerm = document.querySelector('#link-search').value.trim().toLowerCase();
    const filteredLinks = state.links.filter((link) => (
        `${link.code} ${link.short_url} ${link.original_url}`.toLowerCase().includes(searchTerm)
    ));

    document.querySelector('#links-count').textContent = state.totalLinks ? `(${state.totalLinks})` : '';
    document.querySelector('#total-links').textContent = state.totalLinks;
    document.querySelector('#active-links').textContent = state.links.filter((link) => linkStatus(link).active).length;
    document.querySelector('#inactive-links').textContent = state.links.filter((link) => !linkStatus(link).active).length;
    elements.linksList.innerHTML = '';
    elements.linksList.hidden = filteredLinks.length === 0;
    elements.emptyState.hidden = filteredLinks.length > 0;
    elements.pagination.hidden = state.lastPage <= 1;

    if (filteredLinks.length === 0) {
        elements.emptyState.querySelector('strong').textContent = state.totalLinks === 0
            ? 'Belum ada tautan di sini'
            : 'Tautan tidak ditemukan';
        elements.emptyState.querySelector('span:last-child').textContent = state.totalLinks === 0
            ? 'Buat tautan pendek pertamamu dari form di sebelah.'
            : 'Coba kata kunci lain untuk mencari tautanmu.';
    }

    for (const link of filteredLinks) {
        const status = linkStatus(link);
        const row = document.createElement('article');
        row.className = 'link-row';
        row.innerHTML = `
            <div class="link-primary">
                <a class="link-short" href="${escapeHtml(link.short_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(link.short_url)}</a>
                <span class="link-code"><span class="status-pill${status.active ? '' : ' is-inactive'}">${status.label}</span> &nbsp;·&nbsp; ${escapeHtml(formatDateTime(link.expires_at))}</span>
            </div>
            <div class="link-destination">
                <span>URL TUJUAN</span>
                <a href="${escapeHtml(link.original_url)}" target="_blank" rel="noopener noreferrer" title="${escapeHtml(link.original_url)}">${escapeHtml(link.original_url)}</a>
            </div>
            <div class="link-actions">
                <button class="icon-button" type="button" data-action="copy" data-url="${escapeHtml(link.short_url)}" aria-label="Salin tautan">Salin</button>
                <button class="icon-button" type="button" data-action="stats" data-id="${link.id}" aria-label="Lihat statistik">Klik</button>
                <button class="icon-button icon-button-danger" type="button" data-action="deactivate" data-id="${link.id}" ${status.active ? '' : 'disabled'} aria-label="Nonaktifkan tautan">Matikan</button>
            </div>
        `;
        elements.linksList.append(row);
    }

    document.querySelector('#pagination-label').textContent = `Halaman ${state.currentPage} dari ${state.lastPage}`;
    document.querySelector('#previous-page').disabled = state.currentPage <= 1;
    document.querySelector('#next-page').disabled = state.currentPage >= state.lastPage;
}

async function loadLinks(page = 1) {
    elements.linksList.innerHTML = '<div class="loading-state"><span class="spinner"></span> Memuat tautanmu...</div>';
    elements.linksList.hidden = false;
    elements.emptyState.hidden = true;

    try {
        const result = await apiRequest(`/links?page=${page}`);
        state.links = result.data || [];
        state.currentPage = result.meta?.current_page || page;
        state.lastPage = result.meta?.last_page || 1;
        state.totalLinks = result.meta?.total ?? state.links.length;
        renderLinks();
    } catch (error) {
        elements.linksList.innerHTML = '';
        elements.linksList.hidden = true;
        elements.emptyState.hidden = false;
        elements.emptyState.querySelector('strong').textContent = 'Tautan belum bisa dimuat';
        elements.emptyState.querySelector('span:last-child').textContent = error.message;
    }
}

async function loadHealth() {
    const indicator = document.querySelector('#health-indicator');
    const label = document.querySelector('#health-label');

    try {
        const response = await fetch('/health', { headers: { Accept: 'application/json' } });
        const health = await response.json();

        if (response.ok && health.database === 'connected') {
            indicator.classList.add('is-online');
            label.textContent = health.cache === 'disconnected' ? 'Layanan siap · cache offline' : 'Layanan tersedia';
        } else {
            indicator.classList.add('is-offline');
            label.textContent = 'Layanan sedang terganggu';
        }
    } catch {
        indicator.classList.add('is-offline');
        label.textContent = 'Tidak terhubung ke layanan';
    }
}

async function submitAuthentication(event) {
    event.preventDefault();
    setMessage(elements.authMessage, '');
    const formData = new FormData(elements.authForm);
    const payload = {
        email: formData.get('email'),
        password: formData.get('password'),
    };

    if (state.isRegistering) {
        payload.name = formData.get('name');
        payload.password_confirmation = formData.get('password_confirmation');
    }

    setButtonBusy(elements.authSubmit, true, state.isRegistering ? 'Membuat akun...' : 'Memeriksa...');

    try {
        const result = await apiRequest(state.isRegistering ? '/register' : '/login', {
            method: 'POST',
            body: payload,
            authenticated: false,
        });

        const user = result.data;
        if (!result.token || !user) {
            throw new Error('Respons autentikasi tidak lengkap. Silakan coba lagi.');
        }

        saveAuthentication(result.token, user);
        showDashboard(user);
        await loadLinks();
        showToast(state.isRegistering ? 'Akun berhasil dibuat. Selamat datang!' : 'Berhasil masuk. Selamat datang kembali!');
    } catch (error) {
        setMessage(elements.authMessage, error.message);
    } finally {
        setButtonBusy(elements.authSubmit, false);
    }
}

async function createLink(event) {
    event.preventDefault();
    setMessage(elements.createMessage, '');
    const formData = new FormData(elements.createForm);
    const payload = { original_url: formData.get('original_url').trim() };
    const alias = formData.get('alias').trim();
    const expiresAt = formData.get('expires_at');

    if (alias) {
        payload.alias = alias;
    }

    if (expiresAt) {
        payload.expires_at = new Date(expiresAt).toISOString();
    }

    setButtonBusy(elements.createSubmit, true, 'Membuat...');

    try {
        const result = await apiRequest('/links', { method: 'POST', body: payload });
        elements.createForm.reset();
        await loadLinks(1);
        showToast(`Tautan pendek ${result.data.code} berhasil dibuat.`);
    } catch (error) {
        setMessage(elements.createMessage, error.message);
    } finally {
        setButtonBusy(elements.createSubmit, false);
    }
}

async function deactivateLink(linkId) {
    if (!window.confirm('Nonaktifkan tautan ini? Tautan tidak akan bisa digunakan lagi.')) {
        return;
    }

    try {
        await apiRequest(`/links/${linkId}/deactivate`, { method: 'PATCH' });
        await loadLinks(state.currentPage);
        showToast('Tautan berhasil dinonaktifkan.');
    } catch (error) {
        showToast(error.message, true);
    }
}

async function copyLink(shortUrl) {
    try {
        await navigator.clipboard.writeText(shortUrl);
        showToast('Tautan berhasil disalin.');
    } catch {
        showToast('Tautan tidak dapat disalin. Salin alamat dari daftar tautan.', true);
    }
}

function buildStatsUrl() {
    const linkId = state.statsLink.id;
    const filters = new FormData(document.querySelector('#stats-filter'));
    const query = new URLSearchParams();

    for (const field of ['from', 'to']) {
        const value = filters.get(field);
        if (value) {
            query.set(field, value);
        }
    }

    return `/links/${linkId}/stats${query.size ? `?${query}` : ''}`;
}

async function loadStats() {
    const loading = document.querySelector('#stats-loading');
    const list = document.querySelector('#daily-stats');
    const message = document.querySelector('#stats-message');
    loading.hidden = false;
    list.innerHTML = '';
    setMessage(message, '');

    try {
        const result = await apiRequest(buildStatsUrl());
        const stats = result.data;
        document.querySelector('#stats-total').textContent = stats.total_clicks;

        if (stats.daily_stats.length === 0) {
            list.innerHTML = '<div class="empty-state"><strong>Belum ada data klik</strong><span>Statistik akan muncul setelah tautan dikunjungi.</span></div>';
            return;
        }

        const maximumClicks = Math.max(...stats.daily_stats.map((item) => item.clicks), 1);
        for (const item of stats.daily_stats) {
            const row = document.createElement('div');
            row.className = 'daily-stat-row';
            const width = Math.max((item.clicks / maximumClicks) * 100, item.clicks > 0 ? 2 : 0);
            row.innerHTML = `
                <span>${escapeHtml(formatDate(item.date))}</span>
                <span class="daily-stat-track"><span class="daily-stat-bar" style="width: ${width}%"></span></span>
                <span class="daily-stat-value">${Number(item.clicks)}</span>
            `;
            list.append(row);
        }
    } catch (error) {
        setMessage(message, error.message);
    } finally {
        loading.hidden = true;
    }
}

document.querySelector('#login-tab').addEventListener('click', () => setAuthMode(false));
document.querySelector('#register-tab').addEventListener('click', () => setAuthMode(true));
elements.authForm.addEventListener('submit', submitAuthentication);
elements.createForm.addEventListener('submit', createLink);
document.querySelector('#logout-button').addEventListener('click', async () => {
    try {
        await apiRequest('/logout', { method: 'POST' });
    } catch (error) {
        showToast(error.message, true);
    } finally {
        clearAuthentication();
        showAuth();
        showToast('Kamu sudah keluar dari akun.');
    }
});

document.querySelector('#link-search').addEventListener('input', renderLinks);
elements.linksList.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button || button.disabled) {
        return;
    }

    if (button.dataset.action === 'copy') {
        copyLink(button.dataset.url);
    } else if (button.dataset.action === 'stats') {
        state.statsLink = state.links.find((link) => String(link.id) === button.dataset.id);
        if (state.statsLink) {
            document.querySelector('#stats-link-url').textContent = state.statsLink.short_url;
            document.querySelector('#stats-filter').reset();
            elements.statsDialog.showModal();
            loadStats();
        }
    } else if (button.dataset.action === 'deactivate') {
        deactivateLink(button.dataset.id);
    }
});

document.querySelector('#previous-page').addEventListener('click', () => {
    if (state.currentPage > 1) {
        loadLinks(state.currentPage - 1);
    }
});
document.querySelector('#next-page').addEventListener('click', () => {
    if (state.currentPage < state.lastPage) {
        loadLinks(state.currentPage + 1);
    }
});
document.querySelector('#close-stats').addEventListener('click', () => elements.statsDialog.close());
document.querySelector('#stats-filter').addEventListener('submit', (event) => {
    event.preventDefault();
    loadStats();
});

elements.statsDialog.addEventListener('click', (event) => {
    if (event.target === elements.statsDialog) {
        elements.statsDialog.close();
    }
});

document.querySelector('#short-url-prefix').textContent = `${window.location.origin}/`;
elements.authSubmit.dataset.label = elements.authSubmit.querySelector('span').textContent;
elements.createSubmit.dataset.label = elements.createSubmit.querySelector('span').textContent;
loadHealth();

if (state.token) {
    showDashboard(state.user);
    loadLinks();
} else {
    showAuth();
}
