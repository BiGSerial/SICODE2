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
                const mode = btn.dataset.openMode || 'side';
                const currentSubmenu = btn.closest('.submenu');
                const scope = currentSubmenu?.parentElement || root;

                const closeSubtree = (container) => {
                    if (!container) return;
                    container.querySelectorAll('.submenu-panel').forEach((panel) => panel.classList.remove('is-open'));
                    container.querySelectorAll('.js-submenu-toggle').forEach((toggle) => toggle.classList.remove('is-active'));
                };

                Array.from(scope.children).forEach((child) => {
                    if (!(child instanceof HTMLElement) || !child.classList.contains('submenu') || child === currentSubmenu) {
                        return;
                    }

                    const siblingToggle = child.querySelector(':scope > .js-submenu-toggle');
                    const siblingMode = siblingToggle?.dataset.openMode || 'side';

                    // Keep independent levels/modes isolated; only close siblings of the same behavior.
                    if (mode === 'down' && siblingMode !== 'down') {
                        return;
                    }
                    if (mode === 'side' && siblingMode !== 'side') {
                        return;
                    }

                    closeSubtree(child);
                });

                if (target.classList.contains('is-open')) {
                    closeSubtree(currentSubmenu);
                    return;
                }

                target.classList.add('is-open');
                btn.classList.add('is-active');
            });
        });

        if (dropdown) {
            dropdown.addEventListener('hidden.bs.dropdown', resetMenus);
        }
    })();
</script>
