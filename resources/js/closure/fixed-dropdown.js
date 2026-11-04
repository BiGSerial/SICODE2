// Menu de ações da linha em posição fixa: não é cortado pelo overflow da tabela (a última linha ficava escondida).
// Uso: botão com `data-closure-fixed-dropdown` + `data-bs-toggle="dropdown"`.
if (!window.__closureFixedDropdown) {
    window.__closureFixedDropdown = true;
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-closure-fixed-dropdown]');
        if (toggle && window.bootstrap && !window.bootstrap.Dropdown.getInstance(toggle)) {
            window.bootstrap.Dropdown.getOrCreateInstance(toggle, { popperConfig: (defaults) => ({ ...defaults, strategy: 'fixed', modifiers: [...(defaults.modifiers || []), { name: 'flip', options: { fallbackPlacements: ['top-end', 'bottom-end', 'top-start'] } }, { name: 'preventOverflow', options: { padding: 8 } }] }) });
        }
    }, true);
}
