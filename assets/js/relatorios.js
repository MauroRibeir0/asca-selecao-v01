/**
 * ASCA Selecção - Reports Page JS
 * Handles WhatsApp message composition and sharing
 */
document.addEventListener('DOMContentLoaded', () => {
    const memberSelect = document.getElementById('waMembers');
    const msgTypeSelect = document.getElementById('waMsgType');
    const previewBtn = document.getElementById('waPreviewBtn');
    const previewArea = document.getElementById('waPreviewArea');
    const messageDiv = document.getElementById('waMessagePreview');
    const sendBtn = document.getElementById('waSendBtn');
    const copyBtn = document.getElementById('waCopyBtn');

    let currentData = null;

    // Enable/disable preview button
    memberSelect.addEventListener('change', () => {
        previewBtn.disabled = !memberSelect.value;
        if (!memberSelect.value) {
            previewArea.style.display = 'none';
            sendBtn.disabled = true;
            copyBtn.disabled = true;
        }
    });

    // Fetch and preview message
    previewBtn.addEventListener('click', async () => {
        const memberId = memberSelect.value;
        const msgType = msgTypeSelect.value;
        if (!memberId) return;

        previewBtn.disabled = true;
        previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A carregar...';

        try {
            const cycleId = document.querySelector('[name="cycle_id"]')?.value ||
                new URLSearchParams(window.location.search).get('cycle_id') || '';

            const resp = await fetch(`../api/reports_api.php?report=whatsapp_summary&cycle_id=${cycleId}&member_id=${memberId}&msg_type=${msgType}`);
            const data = await resp.json();

            if (data.success) {
                currentData = data;
                messageDiv.textContent = data.message;
                previewArea.style.display = 'block';
                sendBtn.disabled = false;
                copyBtn.disabled = false;
            } else {
                APP.showToast(data.message || 'Erro ao carregar dados.', 'danger');
            }
        } catch (err) {
            APP.showToast('Erro de comunicação com o servidor.', 'danger');
        } finally {
            previewBtn.disabled = false;
            previewBtn.innerHTML = '<i class="bi bi-eye me-1"></i>Pré-visualizar';
        }
    });

    // Also update preview when message type changes
    msgTypeSelect.addEventListener('change', () => {
        if (memberSelect.value && previewArea.style.display !== 'none') {
            previewBtn.click();
        }
    });

    // Open WhatsApp
    sendBtn.addEventListener('click', () => {
        if (!currentData) return;
        const phone = currentData.phone;
        const text = encodeURIComponent(currentData.message);
        window.open(`https://wa.me/${phone}?text=${text}`, '_blank');
    });

    // Copy to clipboard
    copyBtn.addEventListener('click', () => {
        if (!currentData) return;
        navigator.clipboard.writeText(currentData.message).then(() => {
            APP.showToast('Mensagem copiada para a área de transferência!', 'success');
            copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copiado!';
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copiar Texto';
            }, 2000);
        }).catch(() => {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = currentData.message;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            APP.showToast('Mensagem copiada!', 'success');
        });
    });
});
