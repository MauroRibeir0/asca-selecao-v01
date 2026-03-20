/**
 * ASCA Selecção - Reports Page JS
 * Handles WhatsApp individual message composition and bulk reminders.
 */
document.addEventListener('DOMContentLoaded', () => {

    // ── Common helpers ────────────────────────────────────────────────────────

    function getCycleId() {
        const el = document.querySelector('[name="cycle_id"]');
        if (el) return el.value;
        return new URLSearchParams(window.location.search).get('cycle_id') || '';
    }

    function baseUrl() {
        return (document.querySelector('meta[name="base-url"]') || {}).content || '';
    }

    function fmtMoney(v) {
        return parseFloat(v || 0).toLocaleString('pt-MZ', { minimumFractionDigits: 2 }) + ' MT';
    }

    // ── Individual message ────────────────────────────────────────────────────

    const memberSelect  = document.getElementById('waMembers');
    const msgTypeSelect = document.getElementById('waMsgType');
    const previewBtn    = document.getElementById('waPreviewBtn');
    const previewArea   = document.getElementById('waPreviewArea');
    const messageTA     = document.getElementById('waMessagePreview'); // now a <textarea>
    const sendBtn       = document.getElementById('waSendBtn');
    const copyBtn       = document.getElementById('waCopyBtn');
    const resetBtn      = document.getElementById('waResetBtn');

    let currentPhone    = '';
    let originalMessage = ''; // server-generated text (for reset)

    memberSelect.addEventListener('change', () => {
        previewBtn.disabled = !memberSelect.value;
        if (!memberSelect.value) {
            previewArea.style.display = 'none';
            sendBtn.disabled = true;
            copyBtn.disabled = true;
        }
    });

    previewBtn.addEventListener('click', loadPreview);
    msgTypeSelect.addEventListener('change', () => {
        if (memberSelect.value && previewArea.style.display !== 'none') loadPreview();
    });

    async function loadPreview() {
        const memberId = memberSelect.value;
        if (!memberId) return;

        previewBtn.disabled = true;
        previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A carregar...';

        try {
            const url = baseUrl() + '/api/reports_api.php?report=whatsapp_summary'
                + '&cycle_id=' + getCycleId()
                + '&member_id=' + memberId
                + '&msg_type='  + msgTypeSelect.value
                + '&_=' + Date.now();

            const resp = await fetch(url);
            const data = await resp.json();

            if (data.success) {
                currentPhone    = data.phone;
                originalMessage = data.message;
                messageTA.value = data.message;
                previewArea.style.display = 'block';
                sendBtn.disabled  = false;
                copyBtn.disabled  = false;
                resetBtn.style.display = '';

                // Track edits to show reset button state
                messageTA.dataset.original = data.message;
            } else {
                APP.showToast(data.message || 'Erro ao carregar dados.', 'danger');
            }
        } catch {
            APP.showToast('Erro de comunicação com o servidor.', 'danger');
        } finally {
            previewBtn.disabled = false;
            previewBtn.innerHTML = '<i class="bi bi-eye me-1"></i>Pré-visualizar';
        }
    }

    // Open WhatsApp with current textarea content (allows editing before sending)
    sendBtn.addEventListener('click', () => {
        if (!currentPhone) return;
        const text = encodeURIComponent(messageTA.value);
        window.open('https://wa.me/' + currentPhone + '?text=' + text, '_blank');
    });

    // Copy current textarea content
    copyBtn.addEventListener('click', () => {
        const text = messageTA.value;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                APP.showToast('Mensagem copiada para a área de transferência!', 'success');
                copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copiado!';
                setTimeout(() => { copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copiar Texto'; }, 2000);
            }).catch(() => fallbackCopy(text));
        } else {
            fallbackCopy(text);
        }
    });

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity  = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        APP.showToast('Mensagem copiada!', 'success');
    }

    // Reset textarea to server-generated original
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            messageTA.value = originalMessage;
        });
    }

    // ── Bulk reminders ────────────────────────────────────────────────────────

    const bulkLoadBtn = document.getElementById('waBulkLoadBtn');
    const bulkList    = document.getElementById('waBulkList');

    if (bulkLoadBtn) {
        bulkLoadBtn.addEventListener('click', async () => {
            bulkLoadBtn.disabled = true;
            bulkLoadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A carregar...';

            try {
                const url = baseUrl() + '/api/reports_api.php?report=whatsapp_bulk_list'
                    + '&cycle_id=' + getCycleId()
                    + '&_=' + Date.now();

                const resp = await fetch(url);
                const data = await resp.json();

                if (!data.success) {
                    APP.showToast(data.message || 'Erro ao carregar lista.', 'danger');
                    return;
                }

                const debtors = data.members.filter(m => m.has_debt);

                if (debtors.length === 0) {
                    bulkList.innerHTML = '<p class="text-muted small text-center py-3"><i class="bi bi-check-circle me-1 text-success"></i>Nenhum membro com dívida activa.</p>';
                } else {
                    const rows = debtors.map(m => {
                        const debtLine = m.total_owed > 0
                            ? '<small class="text-danger">Empréstimo: ' + fmtMoney(m.total_owed) + '</small>'
                            : '';
                        const intLine  = m.pending_interest > 0
                            ? '<small class="text-warning">Juros: ' + fmtMoney(m.pending_interest) + '</small>'
                            : '';
                        const details  = [debtLine, intLine].filter(Boolean).join(' &nbsp; ');

                        const msg = encodeURIComponent(
                            '\u{1F4CB} *ASCA Selec\u00E7\u00E3o \u2014 Lembrete de Pagamento*\n\n'
                            + 'Ol\u00E1 *' + m.full_name + '*,\n\n'
                            + 'Tem obriga\u00E7\u00F5es financeiras pendentes no ciclo activo.\n'
                            + (m.total_owed > 0 ? '\n\u{1F4B0} Empr\u00E9stimo em aberto: *' + fmtMoney(m.total_owed) + '*\n' : '')
                            + (m.pending_interest > 0 ? '\n\u{1F4C8} Juros pendentes: *' + fmtMoney(m.pending_interest) + '*\n' : '')
                            + '\nAgradecemos a regulariza\u00E7\u00E3o atempada.\n\n_Sistema ASCA Selec\u00E7\u00E3o_'
                        );
                        const waLink = m.phone
                            ? '<a href="https://wa.me/' + m.phone + '?text=' + msg + '" target="_blank" class="btn btn-sm" style="background:#25D366;color:#fff;border:none;white-space:nowrap;"><i class="bi bi-whatsapp me-1"></i>Enviar</a>'
                            : '<span class="btn btn-sm btn-outline-secondary disabled" title="Sem telefone">Sem tel.</span>';

                        return '<div class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2">'
                            + '<div><div class="fw-semibold small">' + m.full_name + '</div>'
                            + '<div class="d-flex gap-2 flex-wrap">' + details + '</div></div>'
                            + waLink + '</div>';
                    }).join('');

                    bulkList.innerHTML = '<div class="mb-2"><small class="text-muted">'
                        + debtors.length + ' membro(s) com dívida activa:</small></div>' + rows;
                }

                bulkList.style.display = 'block';
            } catch {
                APP.showToast('Erro de comunicação com o servidor.', 'danger');
            } finally {
                bulkLoadBtn.disabled = false;
                bulkLoadBtn.innerHTML = '<i class="bi bi-people me-1"></i>Recarregar Lista';
            }
        });
    }
});
