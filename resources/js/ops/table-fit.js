// A tabela da lista usa `table-layout: fixed` com as larguras em px no `<colgroup>` (a soma é o mínimo que cada coluna precisa).
// O navegador não reparte a sobra quando todas as colunas têm largura: num monitor largo as linhas terminavam antes do fim do painel.
// Aqui a sobra é dividida entre as colunas, proporcional à largura de cada uma, para a tabela ocupar toda a área. Se não couber, fica
// a largura do `colgroup` e a rolagem lateral do `.ops-table-wrap`. Sem tocar nas views: vale para toda `.ops-list`.
const scaled = new WeakMap();   // col => { base, set } (largura original e a última aplicada por este módulo)
const watched = new WeakSet();

const baseWidth = (col) => {
    const mine = scaled.get(col);
    const current = parseFloat(col.style.width) || 0;

    // O Livewire devolve o `style` original a cada atualização: só vale como base o que não foi escrito por aqui.
    return mine && mine.set === current ? mine.base : current;
};

const fit = (table) => {
    const wrap = table.parentElement;
    const cols = Array.from(table.querySelectorAll(':scope > colgroup > col'));
    if (!wrap || !cols.length || !wrap.clientWidth) { return; }   // escondida (carregando): mede quando voltar

    const base = cols.map(baseWidth);
    const sum = base.reduce((total, width) => total + width, 0);
    if (!sum || base.some((width) => !width)) { return; }   // coluna sem largura já absorve a sobra sozinha

    const factor = Math.max(1, Math.floor(wrap.clientWidth) / sum);

    cols.forEach((col, index) => {
        const set = Math.floor(base[index] * factor * 100) / 100;
        scaled.set(col, { base: base[index], set });
        col.style.width = set + 'px';
    });
};

const fitAll = () => {
    document.querySelectorAll('.ops-list .ops-table-wrap > .ops-table').forEach((table) => {
        fit(table);

        if (window.ResizeObserver && !watched.has(table.parentElement)) {
            watched.add(table.parentElement);
            new ResizeObserver(() => fit(table)).observe(table.parentElement);
        }
    });
};

// A tabela só aparece depois do primeiro ajuste (CSS `html:not(.ops-fit-ready) .ops-table`, com um prazo de segurança), senão ela
// nasceria estreita e esticaria na frente do usuário.
const ready = () => document.documentElement.classList.add('ops-fit-ready');
const start = () => { fitAll(); ready(); };

if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }

window.addEventListener('resize', fitAll);
document.addEventListener('livewire:load', () => {
    start();

    if (!window.Livewire || !window.Livewire.hook) { return; }

    // O Livewire compara o `style` da coluna com o do servidor (largura original) e o desfaria a cada atualização: o novo
    // elemento herda a largura já ajustada, e a coluna não encolhe e estica de novo.
    window.Livewire.hook('element.updating', (from, to) => {
        if (from.tagName === 'COL' && to.tagName === 'COL' && scaled.has(from)) { to.style.width = from.style.width; }
    });
    window.Livewire.hook('message.processed', fitAll);
});
