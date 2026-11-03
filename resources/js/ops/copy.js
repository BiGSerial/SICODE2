// Clique no número da nota/ordem (`.copy-text[data-value]`) copia o valor e avisa o componente (`getCopy` → toast).
// Delegado no documento: continua funcionando depois de cada atualização do Livewire. Só dentro de `.ops-list`.
document.addEventListener('click', (event) => {
    const cell = event.target.closest('.ops-list .copy-text');
    if (!cell || event.target.closest('a, button, input')) { return; }

    const value = cell.getAttribute('data-value');
    if (!value) { return; }

    const done = () => window.Livewire && window.Livewire.emit('getCopy', `Valor "${value}" copiado para a área de transferência.`);

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(done);
        return;
    }

    const area = document.createElement('textarea');
    area.value = value;
    document.body.appendChild(area);
    area.select();
    document.execCommand('copy');
    document.body.removeChild(area);
    done();
});
