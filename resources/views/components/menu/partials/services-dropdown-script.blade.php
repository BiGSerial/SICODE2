<script>
    (function () {
        const root = document.currentScript.closest('.services-dropdown');
        if (!root) return;
        const dropdown = root.closest('.dropdown');

        // Fecha todos os painéis (chamado quando o dropdown fecha)
        const resetMenus = () => {
            root.querySelectorAll('.menu-panel, .sd-submenu-panel')
                .forEach(p => p.classList.remove('is-open'));
            root.querySelectorAll('.js-menu-toggle, .js-submenu-toggle')
                .forEach(t => t.classList.remove('is-active'));
        };

        // Acorda toggles do layout "panel" legado
        root.querySelectorAll('.js-menu-toggle').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const target = root.querySelector(btn.dataset.target);
                if (!target) return;

                root.querySelectorAll('.menu-panel').forEach(p => {
                    if (p !== target) p.classList.remove('is-open');
                });
                root.querySelectorAll('.js-menu-toggle').forEach(t => {
                    if (t !== btn) t.classList.remove('is-active');
                });

                const open = target.classList.toggle('is-open');
                btn.classList.toggle('is-active', open);
            });
        });

        // Acorda toggles de sub-dropdown (layout "inline" e legado)
        root.querySelectorAll('.js-submenu-toggle').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const target = root.querySelector(btn.dataset.target);
                if (!target) return;

                const mode           = btn.dataset.openMode || 'side';
                const currentGroup   = btn.closest('.submenu, .sd-group');
                const scope          = currentGroup?.parentElement || root;

                const closeSubtree = (container) => {
                    if (!container) return;
                    container.querySelectorAll('.submenu-panel, .sd-submenu-panel')
                        .forEach(p => p.classList.remove('is-open'));
                    container.querySelectorAll('.js-submenu-toggle')
                        .forEach(t => t.classList.remove('is-active'));
                };

                // Fecha irmãos do mesmo modo antes de abrir o atual
                Array.from(scope.children).forEach((child) => {
                    if (!(child instanceof HTMLElement)) return;
                    const isSibling =
                        (child.classList.contains('submenu') || child.classList.contains('sd-group')) &&
                        child !== currentGroup;
                    if (!isSibling) return;

                    const siblingToggle = child.querySelector(':scope > .js-submenu-toggle');
                    if ((siblingToggle?.dataset.openMode || 'side') !== mode) return;

                    closeSubtree(child);
                });

                // Toggle: se já está aberto, fecha; caso contrário, abre
                if (target.classList.contains('is-open')) {
                    closeSubtree(currentGroup);
                    return;
                }

                target.classList.add('is-open');
                btn.classList.add('is-active');
            });
        });

        // Reseta ao fechar o dropdown Bootstrap
        if (dropdown) {
            dropdown.addEventListener('hidden.bs.dropdown', resetMenus);
        }
    })();
</script>
