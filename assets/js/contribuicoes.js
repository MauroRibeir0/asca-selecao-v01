/**
 * ASCA Selecção - Contributions JS
 * CRUD + cycle date validation + mandatory late-fee enforcement
 */

let allContributions = [];
let contribDataTable = null;

// ── DATA LOADING ────────────────────────────────────────────────

function loadContributions() {
    const url = APP.BASE_URL ? `${APP.BASE_URL}/api/contribuicoes_api.php` : '../api/contribuicoes_api.php';
    fetch(`${url}?action=list&_=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            allContributions = data.data;
            applyFilters();
        });
}

function loadMembersDropdown() {
    const url = APP.BASE_URL ? `${APP.BASE_URL}/api/membros_api.php` : '../api/membros_api.php';
    $.getJSON(`${url}?action=list_all&_=${Date.now()}`, function (data) {
        if (!data.success || !data.data) {
            console.warn("No members returned or success=false", data);
            return;
        }

        const modalSelect = $('#contribMember');
        const filterSelect = $('#filterMember');

        let modalHtml = '<option value="">Seleccionar membro...</option>';
        let filterHtml = '<option value="">Todos os Membros</option>';

        data.data.forEach(m => {
            const opt = `<option value="${m.id}">${m.full_name}</option>`;
            modalHtml += opt;
            filterHtml += opt;
        });

        if (modalSelect.length) modalSelect.html(modalHtml);
        if (filterSelect.length) filterSelect.html(filterHtml);
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to load members:", textStatus, errorThrown);
        APP.showToast("Erro ao carregar lista de membros.", "danger");
    });
}

// ── FILTERS ─────────────────────────────────────────────────────

function applyFilters() {
    const memberId = document.getElementById('filterMember')?.value || '';
    const month = document.getElementById('filterMonth')?.value || '';

    let filtered = allContributions;
    if (memberId) filtered = filtered.filter(c => c.member_id == memberId);
    if (month) filtered = filtered.filter(c => c.reference_month.startsWith(month));

    renderContribTable(filtered);
}

document.getElementById('filterMember')?.addEventListener('change', applyFilters);
document.getElementById('filterMonth')?.addEventListener('change', applyFilters);
document.getElementById('clearMonthFilter')?.addEventListener('click', () => {
    const fm = document.getElementById('filterMonth');
    if (fm) fm.value = '';
    applyFilters();
});

// ── RENDER TABLE ─────────────────────────────────────────────────

function renderContribTable(contributions) {
    const tbody = document.getElementById('contribTableBody');
    if (!tbody) return;

    let totalAmount = 0, totalFees = 0, lateCount = 0;
    contributions.forEach(c => {
        totalAmount += parseFloat(c.amount);
        totalFees += parseFloat(c.late_fee || 0);
        if (parseInt(c.is_late)) lateCount++;
    });

    const elTotal = document.getElementById('contribTotal');
    const elFees = document.getElementById('contribLateFees');
    const elCount = document.getElementById('contribLateCount');
    if (elTotal) elTotal.textContent = APP.formatMoney(totalAmount);
    if (elFees) elFees.textContent = APP.formatMoney(totalFees);
    if (elCount) elCount.textContent = lateCount;

    if (contributions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Sem contribuições registadas</td></tr>';
        return;
    }

    const monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const methods = { cash: 'Dinheiro', mpesa: 'M-Pesa', emola: 'E-mola', mkesh: 'Mkesh', conta_movel: 'Conta Móvel', bank_transfer: 'Transf. Bancária' };

    // Group by reference month
    const groups = {};
    contributions.forEach(c => {
        const key = c.reference_month.substring(0, 7);
        if (!groups[key]) groups[key] = [];
        groups[key].push(c);
    });

    const sortedMonths = Object.keys(groups).sort((a, b) => b.localeCompare(a));
    let html = '';

    sortedMonths.forEach(monthKey => {
        const items = groups[monthKey];
        const [y, m] = monthKey.split('-').map(Number);
        const monthLabel = monthNames[m] + ' ' + y;
        const groupTotal = items.reduce((s, c) => s + parseFloat(c.amount), 0);
        const groupFees = items.reduce((s, c) => s + parseFloat(c.late_fee || 0), 0);

        // Group header
        html += `<tr class="table-active" style="background:var(--primary-light, #e8f0fe);">
            <td colspan="5">
                <strong><i class="bi bi-calendar3 me-2"></i>${monthLabel}</strong>
                <span class="badge bg-primary ms-2">${items.length} pgto(s)</span>
            </td>
            <td colspan="2"><strong>${APP.formatMoney(groupTotal)}</strong></td>
            <td>${groupFees > 0 ? '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + APP.formatMoney(groupFees) + '</span>' : '<span class="text-success">Sem multas</span>'}</td>
            <td></td>
        </tr>`;

        // Member rows
        items.forEach(c => {
            const isLate = parseInt(c.is_late);
            const statusBadge = isLate
                ? '<span class="badge bg-danger"><i class="bi bi-clock me-1"></i>Atraso</span>'
                : '<span class="badge bg-success"><i class="bi bi-check me-1"></i>No prazo</span>';

            const actions = `
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editContrib(${c.id})" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                ${IS_ADMIN ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteContrib(${c.id}, '${c.full_name}')" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>` : ''}
            `;

            html += `<tr class="${isLate ? 'table-warning' : ''}">
                <td><a href="perfil_membro.php?id=${c.member_id}" class="text-decoration-none text-dark"><strong>${c.full_name}</strong></a></td>
                <td>${monthNames[m].substring(0, 3)} ${y}</td>
                <td>${APP.formatMoney(parseFloat(c.amount))}</td>
                <td>${new Date(c.paid_date + 'T00:00:00').toLocaleDateString('pt-MZ')}</td>
                <td>${new Date(c.due_date + 'T00:00:00').toLocaleDateString('pt-MZ')}</td>
                <td>${isLate ? '<span class="text-danger fw-bold">' + APP.formatMoney(parseFloat(c.late_fee)) + '</span>' : '<span class="text-muted">—</span>'}</td>
                <td><span class="badge bg-secondary">${methods[c.payment_method] || c.payment_method}</span></td>
                <td>${statusBadge}</td>
                <td>${actions}</td>
            </tr>`;
        });
    });

    tbody.innerHTML = html;

    if (contribDataTable) contribDataTable.destroy();
    contribDataTable = null;
}

// ── MODAL: NEW ───────────────────────────────────────────────────

function openNewContribModal() {
    document.getElementById('contribModalTitle').textContent = 'Registar Contribuição';
    document.getElementById('contribAction').value = 'create';
    document.getElementById('contribId').value = '';
    document.getElementById('contribSubmitLabel').textContent = 'Registar';
    document.getElementById('formContribuicao').reset();
    document.getElementById('lateFeeAlert').classList.add('d-none');
    document.getElementById('onTimeAlert').classList.add('d-none');
    // Reset date to today
    document.getElementById('contribPaidDate').value = new Date().toISOString().split('T')[0];
}

// ── EDIT CONTRIBUTION ───────────────────────────────────────────

function editContrib(id) {
    fetch(`../api/contribuicoes_api.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { APP.showToast(data.message, 'danger'); return; }
            const c = data.data;

            document.getElementById('contribModalTitle').textContent = 'Editar Contribuição';
            document.getElementById('contribAction').value = 'update';
            document.getElementById('contribId').value = c.id;
            document.getElementById('contribSubmitLabel').textContent = 'Guardar';

            // Populate form
            document.getElementById('contribMember').value = c.member_id;
            document.getElementById('contribMonth').value = c.reference_month.substring(0, 7);
            document.getElementById('contribAmount').value = c.amount;
            document.getElementById('contribPaidDate').value = c.paid_date;
            document.getElementById('contribReceipt').value = c.receipt_ref || '';
            // Payment method
            const select = document.querySelector('[name="payment_method"]');
            if (select) select.value = c.payment_method;

            // Trigger late fee check
            checkLateFee();

            new bootstrap.Modal(document.getElementById('modalContribuicao')).show();
        });
}

// ── DELETE CONTRIBUTION ─────────────────────────────────────────

function deleteContrib(id, name) {
    Swal.fire({
        title: 'Eliminar Contribuição',
        html: `Tem a certeza que deseja eliminar a contribuição de <b>${name}</b>?<br><small class="text-danger">Esta acção é irreversível.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('../api/contribuicoes_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                APP.showToast(data.message, data.success ? 'success' : 'danger');
                if (data.success) loadContributions();
            });
    });
}

// ── LATE FEE LIVE PREVIEW ───────────────────────────────────────

function checkLateFee() {
    const month = document.getElementById('contribMonth')?.value;
    const paidDate = document.getElementById('contribPaidDate')?.value;
    const amount = parseFloat(document.getElementById('contribAmount')?.value) || 0;

    const alertLate = document.getElementById('lateFeeAlert');
    const alertOnTime = document.getElementById('onTimeAlert');
    const textLate = document.getElementById('lateFeeText');
    const textOnTime = document.getElementById('onTimeText');

    if (!month || !paidDate) {
        alertLate?.classList.add('d-none');
        alertOnTime?.classList.add('d-none');
        return;
    }

    // Validate cycle range
    if (typeof CYCLE_START !== 'undefined' && CYCLE_START && typeof CYCLE_END !== 'undefined' && CYCLE_END) {
        if (month < CYCLE_START || month > CYCLE_END) {
            alertLate?.classList.remove('d-none');
            if (textLate) textLate.textContent = `O mês ${month} está fora do ciclo vigente (${CYCLE_START} – ${CYCLE_END}). Seleccione um mês válido.`;
            alertOnTime?.classList.add('d-none');
            return;
        }
    }

    // Calculate due date (10th of next month)
    const [y, m] = month.split('-').map(Number);
    let dueMonth = m + 1, dueYear = y;
    if (dueMonth > 12) { dueMonth = 1; dueYear++; }
    const dueDate = `${dueYear}-${String(dueMonth).padStart(2, '0')}-10`;

    if (paidDate > dueDate) {
        const fee = amount > 0 ? (amount * 0.15).toFixed(2) : 0;
        if (textLate) textLate.innerHTML =
            `Pagamento fora do prazo (limite: ${new Date(dueDate + 'T00:00:00').toLocaleDateString('pt-MZ')}).<br>` +
            `<strong>Multa obrigatória: ${APP.formatMoney(parseFloat(fee))} (15%)</strong><br>` +
            `<small>A multa será automaticamente calculada e registada.</small>`;
        alertLate?.classList.remove('d-none');
        alertOnTime?.classList.add('d-none');
    } else {
        if (textOnTime) textOnTime.textContent = `Pagamento dentro do prazo (limite: ${new Date(dueDate + 'T00:00:00').toLocaleDateString('pt-MZ')}). Sem multa.`;
        alertOnTime?.classList.remove('d-none');
        alertLate?.classList.add('d-none');
    }
}

document.getElementById('contribMonth')?.addEventListener('change', checkLateFee);
document.getElementById('contribPaidDate')?.addEventListener('change', checkLateFee);
document.getElementById('contribAmount')?.addEventListener('input', checkLateFee);

// ── FORM SUBMIT ─────────────────────────────────────────────────

document.getElementById('formContribuicao')?.addEventListener('submit', function (e) {
    e.preventDefault();

    // Client-side cycle range check
    const month = document.getElementById('contribMonth')?.value || '';
    if (typeof CYCLE_START !== 'undefined' && CYCLE_START && (month < CYCLE_START || month > CYCLE_END)) {
        APP.showToast(`O mês ${month} está fora do ciclo vigente (${CYCLE_START} – ${CYCLE_END}).`, 'danger');
        return;
    }

    fetch('../api/contribuicoes_api.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalContribuicao'))?.hide();
                this.reset();
                document.getElementById('lateFeeAlert')?.classList.add('d-none');
                document.getElementById('onTimeAlert')?.classList.add('d-none');
                loadContributions();
            }
        });
});

// ── PENDING MEMBERS ─────────────────────────────────────────────

function loadPending() {
    const month = document.getElementById('pendingMonth').value;
    fetch(`../api/contribuicoes_api.php?action=pending&month=${month}`)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('pendingList');
            if (!data.data || data.data.length === 0) {
                container.innerHTML = '<div class="text-success text-center py-3"><i class="bi bi-check-circle me-2"></i>Todos os membros pagaram!</div>';
                return;
            }
            container.innerHTML = '<ul class="list-group">' + data.data.map(m =>
                `<li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><strong>${m.full_name}</strong><br><small class="text-muted">${m.phone || ''}</small></div>
                    <span class="badge bg-warning text-dark">Pendente</span>
                </li>`
            ).join('') + '</ul>';
        });
}

// ── INIT (robusto) ─────────────────────────────────────────────

function initContribPage() {
    // Garantir que os selects existam antes de carregar
    loadMembersDropdown();
    loadContributions();

    // Quando o modal for aberto, recarregar membros se ainda não estiverem carregados
    document.getElementById('modalContribuicao')?.addEventListener('shown.bs.modal', () => {
        const sel = document.getElementById('contribMember');
        if (sel && sel.options.length <= 1) {
            loadMembersDropdown();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContribPage);
} else {
    // DOM já carregado
    initContribPage();
}
