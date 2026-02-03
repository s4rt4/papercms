/* ========================================
   DARK MODE HANDLER
   ======================================== */

class DarkModeManager {
    constructor() {
        this.toggle = document.getElementById('darkModeToggle');
        this.storageKey = 'paperCMS_darkMode';

        this.init();
    }

    init() {
        // Load saved preference atau detect system preference
        this.loadPreference();

        // Bind toggle button
        if (this.toggle) {
            this.toggle.addEventListener('click', () => this.toggleDarkMode());
        }

        // Listen untuk system preference changes
        this.watchSystemPreference();

        // Keyboard Shortcut: Ctrl + Shift + D
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleDarkMode();
            }
        });
    }

    loadPreference() {
        const saved = localStorage.getItem(this.storageKey);

        if (saved !== null) {
            // User punya saved preference
            if (saved === 'dark') {
                this.enableDarkMode(false); // false = don't save again
            }
        } else {
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.enableDarkMode(false);
            }
        }
    }

    watchSystemPreference() {
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                // Only auto-switch jika user belum manually set preference
                const saved = localStorage.getItem(this.storageKey);
                if (saved === null) {
                    if (e.matches) {
                        this.enableDarkMode(false);
                    } else {
                        this.disableDarkMode(false);
                    }
                }
            });
        }
    }

    toggleDarkMode() {
        if (document.documentElement.classList.contains('dark')) {
            this.disableDarkMode(true);
        } else {
            this.enableDarkMode(true);
        }
    }

    enableDarkMode(save = true) {
        document.documentElement.classList.add('dark');

        // Switch Prism theme
        const prismTheme = document.getElementById('prism-theme');
        if (prismTheme) {
            prismTheme.href = 'assets/css/prism/prism-tomorrow.min.css';
        }

        if (save) {
            localStorage.setItem(this.storageKey, 'dark');
        }

        // Dispatch event untuk components lain yang perlu react
        document.dispatchEvent(new CustomEvent('darkModeChange', { detail: { dark: true } }));
    }

    disableDarkMode(save = true) {
        document.documentElement.classList.remove('dark');

        // Switch Prism theme
        const prismTheme = document.getElementById('prism-theme');
        if (prismTheme) {
            prismTheme.href = 'assets/css/prism/prism.min.css';
        }

        if (save) {
            localStorage.setItem(this.storageKey, 'light');
        }

        document.dispatchEvent(new CustomEvent('darkModeChange', { detail: { dark: false } }));
    }

    // Public method untuk check current state
    isDark() {
        return document.documentElement.classList.contains('dark');
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.darkModeManager = new DarkModeManager();
});
