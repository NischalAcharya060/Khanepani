// theme-manager.js
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'auto';
        this.init();
    }

    init() {
        // Add preload class to prevent initial transitions
        document.body.classList.add('preload');

        // Apply initial theme
        this.applyTheme();

        // Remove preload class after first render
        setTimeout(() => {
            document.body.classList.remove('preload');
        }, 100);

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.theme === 'auto') {
                    this.applyTheme();
                }
            });
        }
    }

    applyTheme() {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Remove existing theme classes
        document.body.classList.remove('dark-mode', 'light-mode');

        // Apply appropriate theme
        if (this.theme === 'dark' || (this.theme === 'auto' && prefersDark)) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.add('light-mode');
        }
    }

    setTheme(theme) {
        this.theme = theme;
        localStorage.setItem('theme', theme);

        // Add transition class for smooth switching
        document.body.classList.add('theme-transition');
        this.applyTheme();

        // Remove transition class after animation
        setTimeout(() => {
            document.body.classList.remove('theme-transition');
        }, 300);
    }

    toggleTheme() {
        const currentIsDark = document.body.classList.contains('dark-mode');
        this.setTheme(currentIsDark ? 'light' : 'dark');
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.themeManager = new ThemeManager();
});

// Make it available globally
window.ThemeManager = ThemeManager;