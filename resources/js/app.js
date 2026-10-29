import './bootstrap';
import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

// Markdown aman untuk jawaban AI.
marked.setOptions({ gfm: true, breaks: true });

// Tautan dari jawaban selalu dibuka di tab baru dan tidak membocorkan referrer.
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
    if (node.tagName === 'A') {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
    }
});

window.renderMarkdown = (text) => {
    const html = marked.parse(text ?? '');
    const clean = DOMPurify.sanitize(html, { USE_PROFILES: { html: true } });

    // Bungkus tabel agar bisa digulir horizontal di layar kecil.
    return clean
        .replace(/<table>/g, '<div class="md-table"><table>')
        .replace(/<\/table>/g, '</table></div>');
};

// Helper fetch JSON dengan CSRF, dipakai semua komponen Alpine.
window.api = async (url, { method = 'GET', body = null, formData = null } = {}) => {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    };

    let payload = null;
    if (formData) {
        payload = formData;
    } else if (body) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    const response = await fetch(url, { method, headers, body: payload, credentials: 'same-origin' });

    if (response.status === 401 || response.status === 419) {
        window.location.href = '/login';
        throw new Error('Sesi berakhir. Silakan masuk kembali.');
    }

    let data = null;
    try {
        data = await response.json();
    } catch (e) {
        data = null;
    }

    if (!response.ok) {
        const error = new Error(data?.message || `Permintaan gagal (HTTP ${response.status}).`);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
};

window.Alpine = Alpine;
Alpine.start();