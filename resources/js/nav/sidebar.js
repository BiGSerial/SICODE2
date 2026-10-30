// Menu lateral da Engenharia (.snav): lembra os grupos abertos, filtra itens e fecha a gaveta no celular.
// Tudo progressivo: sem JS o menu continua funcionando (grupos são <details>, links são links).

const STORAGE_KEY = 'snav:groups';
const MOBILE_BREAKPOINT = 1200;

function readState() {
    try {
        return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}') || {};
    } catch (error) {
        return {};
    }
}

function writeState(state) {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (error) {
        // armazenamento indisponível (modo privado): segue sem lembrar
    }
}

function restoreGroups(root) {
    const state = readState();

    // Grupos do menu (data-snav-group) e painéis recolhíveis do rodapé, como a ocupação (data-snav-persist).
    root.querySelectorAll('details[data-snav-group], details[data-snav-persist]').forEach((details) => {
        const key = details.dataset.snavGroup || `panel.${details.dataset.snavPersist}`;
        const hasActive = details.querySelector('.snav-item.is-active');

        // O grupo da tela atual sempre abre; os demais seguem a última escolha do usuário.
        if (!hasActive && key in state) {
            details.open = Boolean(state[key]);
        }

        details.addEventListener('toggle', () => {
            const current = readState();
            current[key] = details.open;
            writeState(current);
        });
    });
}

function bindFilter(root) {
    const input = root.querySelector('[data-snav-filter]');

    if (!input) {
        return;
    }

    const empty = root.querySelector('.snav-empty');
    const normalize = (text) => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    input.addEventListener('input', () => {
        const term = normalize(input.value.trim());
        let visible = 0;

        root.querySelectorAll('[data-snav-item]').forEach((item) => {
            const label = normalize(item.querySelector('[data-snav-label]')?.textContent || '');
            const match = term === '' || label.includes(term);

            item.hidden = !match;
            visible += match ? 1 : 0;
        });

        root.querySelectorAll('details[data-snav-group]').forEach((details) => {
            const any = details.querySelector('[data-snav-item]:not([hidden])');

            details.hidden = !any;

            if (term !== '' && any) {
                details.open = true;
            }
        });

        if (empty) {
            empty.hidden = visible > 0;
        }
    });
}

function bindMobile(root) {
    const closeIfMobile = () => {
        if (window.innerWidth < MOBILE_BREAKPOINT) {
            document.body.classList.remove('toggle-sidebar');
        }
    };

    root.addEventListener('click', (event) => {
        if (event.target.closest('a.snav-item, a.snav-module, a.snav-foot-link')) {
            closeIfMobile();
        }
    });

    // Toque fora da gaveta fecha (no celular a gaveta fica sobre o conteúdo).
    document.addEventListener('click', (event) => {
        if (!document.body.classList.contains('toggle-sidebar') || window.innerWidth >= MOBILE_BREAKPOINT) {
            return;
        }

        if (!event.target.closest('#sidebar') && !event.target.closest('.toggle-sidebar-btn')) {
            document.body.classList.remove('toggle-sidebar');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeIfMobile();
        }
    });
}

function init() {
    const root = document.querySelector('.snav');

    if (!root || root.dataset.snavReady) {
        return;
    }

    root.dataset.snavReady = '1';
    restoreGroups(root);
    bindFilter(root);
    bindMobile(root);
}

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('livewire:load', init);
