// import './bootstrap';
import './closure';
import './home';

window.addEventListener('livewire-upload-error', () => {
    const message = 'O arquivo não foi enviado. Cada arquivo pode ter no máximo 40 MB. Verifique também o formato permitido e tente novamente.';

    if (window.Swal) {
        window.Swal.fire({
            icon: 'warning',
            title: 'Upload não concluído',
            text: message,
        });

        return;
    }

    window.alert(message);
});
