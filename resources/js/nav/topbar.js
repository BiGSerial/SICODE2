// Menu superior (docs/MenuSuperior.md). Um único conjunto de ouvintes delegados no documento para TODOS os dropdowns `.tb-menu`:
// grupos recolhíveis, busca, teclado e contadores. Nada de <script> dentro de cada menu.

const norm = (text) => String(text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
const STORE = 'sicode.topbar.counts';

// ---------- Grupos ----------
function setGroup(group, open) {
    group.classList.toggle('is-open', open);
    const toggle = group.querySelector(':scope > .tb-group-toggle');

    if (toggle) { toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); }
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('.tb-menu .tb-group-toggle');

    if (!toggle) { return; }

    event.preventDefault();
    event.stopPropagation();
    setGroup(toggle.closest('.tb-group'), !toggle.closest('.tb-group').classList.contains('is-open'));
});

// ---------- Busca (menus com muitos itens) ----------
function filter(menu, query) {
    const term = norm(query);

    menu.querySelectorAll('.tb-row').forEach((row) => {
        row.hidden = term !== '' && !norm(row.dataset.tbText).includes(term);
    });

    menu.querySelectorAll('.tb-group').forEach((group) => {
        const any = group.querySelector('.tb-row:not([hidden])');

        group.hidden = term !== '' && !any;

        if (term !== '') { setGroup(group, !!any); } else { setGroup(group, group.dataset.tbOpen === '1'); }
    });

    const empty = menu.querySelector('.tb-empty');

    if (empty) { empty.hidden = term === '' || !!menu.querySelector('.tb-row:not([hidden])'); }
}

document.addEventListener('input', (event) => {
    const input = event.target.closest('.tb-menu .tb-search');

    if (input) { filter(input.closest('.tb-menu'), input.value); }
});

// Ao abrir o menu: foco na busca (quando existe); ao fechar: limpa o filtro.
document.addEventListener('shown.bs.dropdown', (event) => {
    event.target.parentElement?.querySelector('.tb-menu .tb-search')?.focus();
});

document.addEventListener('hidden.bs.dropdown', (event) => {
    const menu = event.target.parentElement?.querySelector('.tb-menu');
    const input = menu?.querySelector('.tb-search');

    if (input && input.value) { input.value = ''; filter(menu, ''); }
});

// ---------- Teclado ----------
const focusables = (menu) => [...menu.querySelectorAll('.tb-link, .tb-chip, .tb-group-toggle, .tb-search')].filter((el) => !el.closest('[hidden]') && el.offsetParent !== null);

document.addEventListener('keydown', (event) => {
    const menu = event.target.closest('.tb-menu');

    if (!menu) { return; }

    const items = focusables(menu);
    const index = items.indexOf(event.target);
    const go = (i) => { event.preventDefault(); items[(i + items.length) % items.length]?.focus(); };

    switch (event.key) {
        case 'ArrowDown': go(index + 1); break;
        case 'ArrowUp': go(index - 1); break;
        case 'Home': go(0); break;
        case 'End': go(items.length - 1); break;
        case 'ArrowRight':
            if (event.target.classList.contains('tb-group-toggle')) { event.preventDefault(); setGroup(event.target.closest('.tb-group'), true); }
            break;
        case 'ArrowLeft':
            if (event.target.classList.contains('tb-group-toggle')) { event.preventDefault(); setGroup(event.target.closest('.tb-group'), false); }
            break;
        case 'Enter':
            if (event.target.classList.contains('tb-search')) { event.preventDefault(); menu.querySelector('.tb-row:not([hidden]) .tb-link')?.click(); }
            break;
        default:
    }
});

// ---------- Contadores (um componente Livewire só, em vez de um por item) ----------
function applyCounts(counts) {
    document.querySelectorAll('[data-tb-count]').forEach((badge) => {
        const value = Number(counts[badge.dataset.tbCount] || 0);

        badge.textContent = value > 99 ? '99+' : String(value);
        badge.hidden = value <= 0;
    });

    // Soma por grupo e por menu (gatilho).
    document.querySelectorAll('.tb-group').forEach((group) => {
        const sum = [...group.querySelectorAll('[data-tb-count]')].reduce((acc, el) => acc + Number(counts[el.dataset.tbCount] || 0), 0);
        const agg = group.querySelector(':scope > .tb-group-toggle .tb-count-agg');

        if (agg) { agg.textContent = sum > 99 ? '99+' : String(sum); agg.hidden = sum <= 0; }
    });

    document.querySelectorAll('.tb-trigger-count').forEach((badge) => {
        const menu = badge.closest('.dropdown')?.querySelector('.tb-menu');
        const sum = menu ? [...menu.querySelectorAll('[data-tb-count]')].reduce((acc, el) => acc + Number(counts[el.dataset.tbCount] || 0), 0) : 0;

        badge.textContent = sum > 99 ? '99+' : String(sum);
        badge.hidden = sum <= 0;
    });
}

window.addEventListener('topbar:counts', (event) => {
    const counts = (event.detail && event.detail.counts) || {};

    applyCounts(counts);

    try { sessionStorage.setItem(STORE, JSON.stringify(counts)); } catch (error) { /* sem storage: só não pré-preenche na próxima página */ }
});

// Pré-preenche com o último valor conhecido, para o número não "piscar" a cada navegação.
document.addEventListener('DOMContentLoaded', () => {
    try {
        const saved = JSON.parse(sessionStorage.getItem(STORE) || 'null');

        if (saved) { applyCounts(saved); }
    } catch (error) { /* ignora */ }
});
