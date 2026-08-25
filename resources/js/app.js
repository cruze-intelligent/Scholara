import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Theme is applied to <html> as early as possible by an inline script in the layout head (see
// resources/views/layouts/app.blade.php / guest.blade.php) to avoid a flash of the wrong theme
// before Alpine loads. This store just keeps the toggle button and that class in sync afterward.
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
});

Alpine.start();
