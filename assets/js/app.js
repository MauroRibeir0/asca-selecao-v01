/**
 * ASCA Selecção - Global JavaScript
 */

const APP = {
    BASE_URL: document.querySelector('meta[name="base-url"]')?.content || '',

    init() {
        this.initSidebarToggle();
        this.initCycleSelector();
        this.initTooltips();
        this.initConfirmDialogs();
    },

    // ─── Sidebar Toggle (mobile) ─────────
    initSidebarToggle() {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (!toggle || !sidebar) return;

        // Create backdrop element
        let backdrop = document.querySelector('.sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);
        }

        const openSidebar = () => {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        };

        const closeSidebar = () => {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        };

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
        });

        backdrop.addEventListener('click', closeSidebar);

        // Close sidebar when clicking a nav link (mobile)
        sidebar.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) closeSidebar();
            });
        });

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) closeSidebar();
        });

        // Close on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) closeSidebar();
        });
    },

    // ─── Cycle Selector ──────────────────
    initCycleSelector() {
        const selector = document.getElementById('cycleSelectorNav');
        if (selector) {
            selector.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('cycle_id', this.value);
                window.location.href = url.toString();
            });
        }
    },

    // ─── Tooltips ────────────────────────
    initTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
    },

    // ─── Confirm Dialogs ─────────────────
    initConfirmDialogs() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-confirm]');
            if (btn) {
                e.preventDefault();
                if (confirm(btn.dataset.confirm)) {
                    if (btn.href) window.location.href = btn.href;
                    if (btn.form) btn.form.submit();
                }
            }
        });
    },

    // ─── Toast Notification ──────────────
    showToast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };

        const id = 'toast-' + Date.now();
        const html = `
            <div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icons[type] || icons.info} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    },

    // ─── AJAX Helper ─────────────────────
    async request(url, method = 'GET', data = null) {
        const options = {
            method,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        };
        if (data) {
            if (data instanceof FormData) {
                options.body = data;
            } else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
        }
        try {
            const resp = await fetch(url, options);
            const json = await resp.json();
            return json;
        } catch (err) {
            console.error('Request failed:', err);
            APP.showToast('Erro de comunicação com o servidor.', 'danger');
            return { success: false, message: 'Erro de rede' };
        }
    },

    // ─── Constants & Colors ──────────────
    COLORS: {
        primary: '#2563eb',
        success: '#059669',
        info: '#0284c7',
        warning: '#d97706',
        danger: '#dc2626',
        secondary: '#64748b',
        accent: '#7c3aed'
    },

    // ─── Format money client-side ────────
    formatMoney(value) {
        return new Intl.NumberFormat('pt-MZ', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value) + ' MT';
    },

    // ─── UI Helpers ──────────────────────
    skeleton(type = 'text', count = 1) {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `<div class="skeleton skeleton-${type}"></div>`;
        }
        return html;
    },

    fadeIn(el, delay = 0) {
        if (!el) return;
        el.classList.add('fade-in');
        if (delay) el.style.animationDelay = `${delay}ms`;
    },

    // ─── Real-time Field Validation ──────
    async validateInput(input, apiAction, extraParams = {}) {
        const field = input.name;
        const value = input.value.trim();
        const id = document.querySelector('[name="id"]')?.value || 0;

        if (!value) {
            this.setFieldStatus(input, 'valid');
            return true;
        }

        // Show "checking" state
        input.classList.add('is-loading');

        const params = new URLSearchParams({
            action: apiAction,
            field: field,
            value: value,
            exclude_id: id,
            ...extraParams
        });

        const resp = await fetch(`../api/membros_api.php?${params.toString()}&_=${Date.now()}`);
        const data = await resp.json();

        input.classList.remove('is-loading');

        if (data.success && !data.available) {
            this.setFieldStatus(input, 'invalid', data.message);
            return false;
        } else {
            this.setFieldStatus(input, 'valid');
            return true;
        }
    },

    setFieldStatus(input, status, message = '') {
        const container = input.closest('.mb-3') || input.parentElement;
        let feedback = container.querySelector('.invalid-feedback');

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            container.appendChild(feedback);
        }

        if (status === 'invalid') {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            feedback.textContent = message;
        } else if (status === 'valid') {
            input.classList.remove('is-invalid');
            if (input.value) input.classList.add('is-valid');
            feedback.textContent = '';
        }
    }
};

document.addEventListener('DOMContentLoaded', () => APP.init());
