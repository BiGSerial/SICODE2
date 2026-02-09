<script>
    (function() {
        const root = document.currentScript.closest('.services-dropdown');
        if (!root) return;
        const dropdown = root.closest('.dropdown');

        const resetMenus = () => {
            root.querySelectorAll('.menu-panel').forEach((panel) => panel.classList.remove('is-open'));
            root.querySelectorAll('.submenu-panel').forEach((panel) => panel.classList.remove('is-open'));
            root.querySelectorAll('.js-menu-toggle').forEach((toggle) => toggle.classList.remove('is-active'));
            root.querySelectorAll('.js-submenu-toggle').forEach((toggle) => toggle.classList.remove('is-active'));
        };

        root.querySelectorAll('.js-menu-toggle').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const target = root.querySelector(btn.dataset.target);
                if (!target) return;

                root.querySelectorAll('.menu-panel').forEach((panel) => {
                    if (panel !== target) panel.classList.remove('is-open');
                });
                root.querySelectorAll('.js-menu-toggle').forEach((toggle) => {
                    if (toggle !== btn) toggle.classList.remove('is-active');
                });

                const open = target.classList.toggle('is-open');
                btn.classList.toggle('is-active', open);
            });
        });

        root.querySelectorAll('.js-submenu-toggle').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const target = root.querySelector(btn.dataset.target);
                if (!target) return;

                root.querySelectorAll('.submenu-panel').forEach((panel) => {
                    if (panel !== target) panel.classList.remove('is-open');
                });
                root.querySelectorAll('.js-submenu-toggle').forEach((toggle) => {
                    if (toggle !== btn) toggle.classList.remove('is-active');
                });

                const open = target.classList.toggle('is-open');
                btn.classList.toggle('is-active', open);
            });
        });

        if (dropdown) {
            dropdown.addEventListener('hidden.bs.dropdown', resetMenus);
        }
    })();
</script>
