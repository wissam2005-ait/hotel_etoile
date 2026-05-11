/**
 * theme.js — Dark / Light mode toggle
 * À inclure dans toutes les pages avant </body>
 */

(function() {
    const STORAGE_KEY = 'wissam_theme';

    /* ── Apply theme ──────────────────────────── */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);

        const btn = document.getElementById('theme-toggle');
        if (!btn) return;
        const icon = btn.querySelector('.theme-icon');
        const label = btn.querySelector('.theme-label');
        if (theme === 'dark') {
            icon.textContent = '☀';
            if (label) label.textContent = 'Clair';
            btn.setAttribute('title', 'Passer en mode clair');
        } else {
            icon.textContent = '☾';
            if (label) label.textContent = 'Sombre';
            btn.setAttribute('title', 'Passer en mode sombre');
        }
    }

    /* ── Toggle ───────────────────────────────── */
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    /* ── Inject button ────────────────────────── */
    function injectButton() {
        if (document.getElementById('theme-toggle')) return;

        const btn = document.createElement('button');
        btn.id = 'theme-toggle';
        btn.setAttribute('aria-label', 'Changer le thème');
        btn.innerHTML = `<span class="theme-icon">☾</span><span class="theme-label">Sombre</span>`;
        btn.addEventListener('click', toggleTheme);
        document.body.appendChild(btn);

        /* Inject styles */
        const style = document.createElement('style');
        style.textContent = `
            #theme-toggle {
                position: fixed;
                bottom: 28px;
                right: 28px;
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 10px 18px;
                background: var(--toggle-bg, #1E1A14);
                color: var(--toggle-color, #C9A96E);
                border: 1px solid var(--toggle-border, rgba(201,169,110,0.3));
                border-radius: 50px;
                font-family: 'Outfit', 'Inter', sans-serif;
                font-size: 0.78rem;
                font-weight: 500;
                letter-spacing: 0.05em;
                cursor: pointer;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
            }
            #theme-toggle:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 28px rgba(0,0,0,0.25);
                background: var(--gold, #C9A96E);
                color: white;
                border-color: var(--gold, #C9A96E);
            }
            .theme-icon { font-size: 1rem; line-height: 1; }

            [data-theme="light"] #theme-toggle {
                --toggle-bg: white;
                --toggle-color: #3A3228;
                --toggle-border: rgba(201,169,110,0.4);
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
        `;
        document.head.appendChild(style);
    }

    /* ── Init ─────────────────────────────────── */
    function init() {
        const saved = localStorage.getItem(STORAGE_KEY);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(saved || (prefersDark ? 'dark' : 'light'));
        injectButton();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* Apply saved theme immediately to avoid flash */
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) document.documentElement.setAttribute('data-theme', saved);
})();