/**
 * ASCA Selecção - Settings Page JS
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formDefinicoes');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Guardar Alterações?',
            text: 'As definições do ciclo serão actualizadas. Esta acção é registada no histórico.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1a73e8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(form);
                // Ensure unchecked checkbox sends 0
                if (!formData.has('allow_multiple_loans')) {
                    formData.set('allow_multiple_loans', '0');
                }

                fetch('../api/definicoes_api.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Guardado!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#0d9488'
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            title: 'Erro',
                            text: 'Falha na comunicação com o servidor.',
                            icon: 'error'
                        });
                    });
            }
        });
    });
});
