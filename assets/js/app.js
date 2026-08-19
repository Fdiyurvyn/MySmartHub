(() => {
    const themeKey = 'mysmarthub-theme';
    const savedTheme = localStorage.getItem(themeKey);
    const preferredTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';

    document.documentElement.dataset.theme = savedTheme || preferredTheme;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.navbar').forEach((navbar) => {
            let container = navbar.querySelector('.navbar-cta');
            if (!container) {
                container = document.createElement('div');
                container.className = 'navbar-cta';
                navbar.appendChild(container);
            }

            if (container.querySelector('.theme-toggle')) {
                return;
            }

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'theme-toggle';
            toggle.setAttribute('aria-label', 'Ganti tema');
            toggle.title = 'Ganti tema';
            container.appendChild(toggle);

            const updateLabel = () => {
                const isLight = document.documentElement.dataset.theme === 'light';
                toggle.textContent = isLight ? '☀' : '☾';
                toggle.title = isLight ? 'Aktifkan dark mode' : 'Aktifkan light mode';
                toggle.setAttribute('aria-label', toggle.title);
                toggle.setAttribute('aria-pressed', String(isLight));
            };

            toggle.addEventListener('click', () => {
                const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
                document.documentElement.dataset.theme = nextTheme;
                localStorage.setItem(themeKey, nextTheme);
                updateLabel();
            });

            updateLabel();
        });

        const forms = document.querySelectorAll('form');
        forms.forEach((form) => {
            form.addEventListener('submit', () => {
                form.classList.add('is-submitting');
            });
        });
    });
})();
