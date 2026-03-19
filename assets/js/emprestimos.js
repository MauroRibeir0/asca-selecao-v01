/**
 * ASCA Selecção - Loans JS
 * Full CRUD + scaled interest calculation + cascade repayment logic
 */

let allLoans = [];
let currentStatus = '';
let loansDataTable = null;

// ── DATA LOADING ────────────────────────────────────────────────

function loadLoans() {
    const url = APP.BASE_URL ? `${APP.BASE_URL}/api/emprestimos_api.php` : '../api/emprestimos_api.php';
    fetch(`${url}?action=list&_=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            allLoans = data.data;
            applyLoanFilters();
        });
}

function loadMembersForLoan() {
    const url = APP.BASE_URL ? `${APP.BASE_URL}/api/membros_api.php` : '../api/membros_api.php';
    $.getJSON(`${url}?action=list_all&_=${Date.now()}`, function (data) {
        if (!data.success) {
            console.error("API Error:", data.message);
            return;
        }

        // Modal new/edit dropdown
        const modalSelect = $('#loanMember');
        if (modalSelect.length) {
            let html = '<option value="">Seleccionar...</option>';
            const margin = data.loan_tolerance_margin || 0;
            data.data.forEach(m => {
                html += `<option value="${m.id}"
                    data-savings="${parseFloat(m.total_poupado || 0)}"
                    data-juros="${parseFloat(m.total_juros || 0)}"
                    data-joia="${m.joia_status || 'pending'}"
                    data-active-loans="${parseFloat(m.total_active_loans || 0)}"
                    data-margin="${margin}">${m.full_name}</option>`;
            });
            modalSelect.html(html);
        }

        // Table filter dropdown
        const filterSelect = $('#filterMember');
        if (filterSelect.length) {
            let html = '<option value="">Todos os Membros</option>';
            data.data.forEach(m => {
                html += `<option value="${m.id}">${m.full_name}</option>`;
            });
            filterSelect.html(html);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to load members for loans:", textStatus, errorThrown);
        APP.showToast("Erro ao carregar membros para empréstimos.", "danger");
    });
}

// ── FILTERS ─────────────────────────────────────────────────────

function applyLoanFilters() {
    const memberId = document.getElementById('filterMember')?.value || '';
    const month = document.getElementById('filterMonth')?.value || '';

    let filtered = allLoans;
    if (currentStatus) filtered = filtered.filter(l => l.status === currentStatus);
    if (memberId) filtered = filtered.filter(l => l.member_id == memberId);
    if (month) filtered = filtered.filter(l => l.disbursement_date.startsWith(month));

    renderLoansTable(filtered);
}

document.getElementById('filterMember')?.addEventListener('change', applyLoanFilters);
document.getElementById('filterMonth')?.addEventListener('change', applyLoanFilters);
document.getElementById('clearMonthFilter')?.addEventListener('click', () => {
    const fm = document.getElementById('filterMonth');
    if (fm) fm.value = '';
    applyLoanFilters();
});

// ── RENDER TABLE ─────────────────────────────────────────────────

function renderLoansTable(loans) {
    const tbody = document.getElementById('loansTableBody');
    if (!tbody) return;

    let totalLoaned = 0, totalRepaid = 0, totalInterest = 0, overdueCount = 0;
    loans.forEach(l => {
        totalLoaned += parseFloat(l.amount);
        totalRepaid += parseFloat(l.total_repaid || 0);
        totalInterest += parseFloat(l.total_interest_paid || 0);
        if (l.status === 'overdue') overdueCount++;
    });

    const elTotal = document.getElementById('loanTotal');
    const elRepaid = document.getElementById('loanRepaid');
    const elInterest = document.getElementById('loanInterest');
    const elOverdue = document.getElementById('loanOverdue');
    if (elTotal) elTotal.textContent = APP.formatMoney(totalLoaned);
    if (elRepaid) elRepaid.textContent = APP.formatMoney(totalRepaid);
    if (elInterest) elInterest.textContent = APP.formatMoney(totalInterest);
    if (elOverdue) elOverdue.textContent = overdueCount;

    if (loans.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Nenhum empréstimo encontrado</td></tr>';
        return;
    }

    const monthNames = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const statusMap = {
        active: '<span class="badge bg-primary">Activo</span>',
        paid: '<span class="badge bg-success">Pago</span>',
        overdue: '<span class="badge bg-danger">Em Atraso</span>',
        defaulted: '<span class="badge bg-dark">Incumprido</span>',
    };

    // Group by disbursement month
    const groups = {};
    loans.forEach(l => {
        const key = l.disbursement_date.substring(0, 7);
        if (!groups[key]) groups[key] = [];
        groups[key].push(l);
    });

    const sortedMonths = Object.keys(groups).sort((a, b) => b.localeCompare(a));
    let html = '';

    sortedMonths.forEach(monthKey => {
        const items = groups[monthKey];
        const [y, m] = monthKey.split('-').map(Number);
        const monthLabel = monthNames[m] + ' ' + y;
        const groupTotal = items.reduce((s, l) => s + parseFloat(l.amount), 0);

        // Group header
        html += `<tr class="table-active" style="background:var(--primary-light, #e8f0fe);">
            <td colspan="4">
                <strong><i class="bi bi-calendar3 me-2"></i>${monthLabel}</strong>
                <span class="badge bg-primary ms-2">${items.length} empréstimo(s)</span>
            </td>
            <td colspan="4"><strong>Total Desembolsado: ${APP.formatMoney(groupTotal)}</strong></td>
        </tr>`;

        items.forEach(l => {
            const isActive = l.status === 'active' || l.status === 'overdue';

            let actions = '';
            if (isActive) {
                actions += `<button class="btn btn-sm btn-outline-success me-1" onclick="openRepayModal(${l.id})" title="Reembolsar"><i class="bi bi-credit-card"></i></button>`;
            }
            actions += `<button class="btn btn-sm btn-outline-primary me-1" onclick="editLoan(${l.id})" title="Editar"><i class="bi bi-pencil"></i></button>`;
            if (typeof IS_ADMIN !== 'undefined' && IS_ADMIN && l.status !== 'paid') {
                actions += `<button class="btn btn-sm btn-outline-danger" onclick="deleteLoan(${l.id}, '${l.full_name}')" title="Eliminar"><i class="bi bi-trash"></i></button>`;
            }

            html += `<tr class="${l.status === 'overdue' ? 'table-danger' : ''}">
                <td><a href="perfil_membro.php?id=${l.member_id}" class="text-decoration-none text-dark"><strong>${l.full_name}</strong></a></td>
                <td>${APP.formatMoney(parseFloat(l.amount))}</td>
                <td>${new Date(l.disbursement_date + 'T00:00:00').toLocaleDateString('pt-MZ')}</td>
                <td>${new Date(l.due_date + 'T00:00:00').toLocaleDateString('pt-MZ')}</td>
                <td>${APP.formatMoney(parseFloat(l.total_repaid || 0))}</td>
                <td>${APP.formatMoney(parseFloat(l.total_interest_paid || 0))}</td>
                <td>${statusMap[l.status] || l.status}</td>
                <td>${actions}</td>
            </tr>`;
        });
    });

    tbody.innerHTML = html;

    if (loansDataTable) { loansDataTable.destroy(); loansDataTable = null; }
}

// ── TAB FILTERING ─────────────────────────────────────────────────

document.querySelectorAll('#loanTabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('#loanTabs .nav-link').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentStatus = this.dataset.status || '';
        applyLoanFilters();
    });
});

// ── MODAL: NEW LOAN ───────────────────────────────────────────────

function openNewLoanModal() {
    document.getElementById('loanModalTitle').textContent = 'Novo Empréstimo';
    document.getElementById('loanAction').value = 'create';
    document.getElementById('loanEditId').value = '';
    document.getElementById('loanSubmitLabel').textContent = 'Desembolsar';
    document.getElementById('loanMember').disabled = false;
    document.getElementById('formEmprestimo').reset();
    document.getElementById('interestPreview').style.display = 'none';
    document.getElementById('loanDisbDate').value = new Date().toISOString().split('T')[0];
}

// ── EDIT LOAN ─────────────────────────────────────────────────────

function editLoan(id) {
    fetch(`../api/emprestimos_api.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { APP.showToast(data.message, 'danger'); return; }
            const l = data.data;

            document.getElementById('loanModalTitle').textContent = 'Editar Empréstimo';
            document.getElementById('loanAction').value = 'update';
            document.getElementById('loanEditId').value = l.id;
            document.getElementById('loanSubmitLabel').textContent = 'Guardar';
            document.getElementById('loanAmount').value = l.amount;
            document.getElementById('loanDisbDate').value = l.disbursement_date;
            document.getElementById('loanNotes').value = l.notes || '';

            // Disable member select for edits (member cannot be changed)
            const memberSelect = document.getElementById('loanMember');
            const opt = document.createElement('option');
            opt.value = l.member_id;
            opt.text = l.full_name;
            opt.selected = true;
            memberSelect.innerHTML = '';
            memberSelect.appendChild(opt);
            memberSelect.disabled = true;

            // Update interest preview
            updateInterestPreview();

            new bootstrap.Modal(document.getElementById('modalEmprestimo')).show();
        });
}

// ── DELETE LOAN ───────────────────────────────────────────────────

function deleteLoan(id, name) {
    Swal.fire({
        title: 'Eliminar Empréstimo',
        html: `Tem a certeza que deseja eliminar o empréstimo de <b>${name}</b>?<br><small class="text-danger">Apenas possível se não houver reembolsos. Esta acção é irreversível.</small>`,
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
        fetch('../api/emprestimos_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                APP.showToast(data.message, data.success ? 'success' : 'danger');
                if (data.success) loadLoans();
            });
    });
}

// ── INTEREST PREVIEW (New Loan Modal) ────────────────────────────

function updateInterestPreview() {
    const amount = parseFloat(document.getElementById('loanAmount')?.value) || 0;
    const disbDate = document.getElementById('loanDisbDate')?.value;
    const preview = document.getElementById('interestPreview');
    const text = document.getElementById('interestPreviewText');
    const rate = typeof LOAN_INTEREST_PCT !== 'undefined' ? LOAN_INTEREST_PCT : 15;
    const days = typeof LOAN_REPAYMENT_DAYS !== 'undefined' ? LOAN_REPAYMENT_DAYS : 30;

    if (amount > 0 && disbDate) {
        const interest = (amount * rate / 100).toFixed(2);
        const due = new Date(disbDate);
        due.setDate(due.getDate() + days);
        if (text) text.textContent = `Juros (${rate}%): ${APP.formatMoney(parseFloat(interest))} | Vencimento: ${due.toLocaleDateString('pt-MZ')}`;
        if (preview) preview.style.display = 'block';
    } else {
        if (preview) preview.style.display = 'none';
    }
}

document.getElementById('loanAmount')?.addEventListener('input', updateInterestPreview);
document.getElementById('loanDisbDate')?.addEventListener('change', updateInterestPreview);

// ── CREATE/UPDATE LOAN SUBMIT ─────────────────────────────────────

const fEmp = document.getElementById('formEmprestimo');
if (fEmp) fEmp.addEventListener('submit', function (e) {
    e.preventDefault();

    const action = document.getElementById('loanAction').value;
    const select = document.getElementById('loanMember');
    const amountInput = document.getElementById('loanAmount');
    const form = this;

    const doSubmit = () => {
        const fd = new FormData(form);
        // Re-enable member select so its value is included
        if (select.disabled) fd.append('member_id', select.value);

        fetch('../api/emprestimos_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ title: action === 'create' ? 'Empréstimo Desembolsado' : 'Empréstimo Actualizado', text: data.message, icon: 'success', confirmButtonColor: '#0d9488' });
                    bootstrap.Modal.getInstance(document.getElementById('modalEmprestimo'))?.hide();
                    form.reset();
                    document.getElementById('loanMember').disabled = false;
                    loadLoans();
                } else {
                    Swal.fire({ title: 'Erro', text: data.message, icon: 'error', confirmButtonColor: '#d33' });
                }
            });
    };

    if (action === 'create' && select && amountInput) {
        const option = select.options[select.selectedIndex];
        if (!option || !option.value) { Swal.fire({ title: 'Atenção', text: 'Seleccione um membro.', icon: 'warning' }); return; }

        const memberName = option.text;
        const savings = parseFloat(option.dataset.savings || 0);
        const joiaStatus = option.dataset.joia || 'pending';
        const activeLoans = parseFloat(option.dataset.activeLoans || 0);
        const juros = parseFloat(option.dataset.juros || 0);
        const amount = parseFloat(amountInput.value || 0);
        const totalCommitted = activeLoans + amount;
        const margin = parseFloat(option.dataset.margin || 0);

        if (joiaStatus !== 'paid') {
            Swal.fire({ title: 'Jóia Pendente', html: `O membro <b>${memberName}</b> ainda não pagou a <b>Jóia de Adesão</b>.<br><br>Não é possível desembolsar empréstimos sem a jóia paga.`, icon: 'error', confirmButtonColor: '#d33' });
            return;
        }

        const maxAllowed = savings + juros + margin;
        if (totalCommitted > maxAllowed && maxAllowed > 0) {
            Swal.fire({ title: 'Limite Excedido', html: `O total de empréstimos de <b>${memberName}</b> (${APP.formatMoney(totalCommitted)}) excede o limite permitido de <b>${APP.formatMoney(maxAllowed)}</b>.<br><small>(Poupança: ${APP.formatMoney(savings)} + Juros: ${APP.formatMoney(juros)} + Margem: ${APP.formatMoney(margin)})</small>`, icon: 'error', confirmButtonColor: '#d33' });
            return;
        } else if (totalCommitted > (savings + juros)) {
            Swal.fire({
                title: 'Risco de Inadimplência',
                html: `O total de empréstimos de <b>${memberName}</b> excederá o total acumulado (Poupança + Juros: <b>${APP.formatMoney(savings + juros)}</b>).<br>Ainda dentro da margem de tolerância (<b>${APP.formatMoney(margin)}</b>). Pretende prosseguir?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, prosseguir',
                cancelButtonText: 'Cancelar'
            }).then(result => { if (result.isConfirmed) doSubmit(); });
            return;
        }
    }

    doSubmit();
});

// ── REPAY MODAL ───────────────────────────────────────────────────

function openRepayModal(loanId) {
    const elId = document.getElementById('repayLoanId');
    if (elId) elId.value = loanId;

    // Reset form
    document.getElementById('formReembolso').reset();
    document.getElementById('repayPrincipal').textContent = '—';
    document.getElementById('repayInterest').textContent = 'A carregar...';
    document.getElementById('repayDueDate').textContent = '—';
    document.getElementById('repayAmount').value = '';
    document.getElementById('repayAmountHint').textContent = '';
    document.getElementById('overdueAlert').classList.add('d-none');
    document.getElementById('payTotal').checked = true;
    document.getElementById('repayPaidDate').value = new Date().toISOString().split('T')[0];

    // Store loan id for date-change listener
    const modal = document.getElementById('modalReembolso');
    modal.dataset.loanId = loanId;

    // Fetch loan details
    fetch(`../api/emprestimos_api.php?action=get&id=${loanId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const l = data.data;
            modal.dataset.dueDate = l.due_date;
            modal.dataset.loanAmount = l.amount;
            document.getElementById('repayDueDate').textContent = new Date(l.due_date + 'T00:00:00').toLocaleDateString('pt-MZ');
            refreshRepayCalculation();
        });

    new bootstrap.Modal(modal).show();
}

/**
 * Recalculate interest display based on the entered payment date.
 */
function refreshRepayCalculation() {
    const modal = document.getElementById('modalReembolso');
    const loanId = modal.dataset.loanId;
    const paidDate = document.getElementById('repayPaidDate')?.value || new Date().toISOString().split('T')[0];

    fetch(`../api/emprestimos_api.php?action=calc_interest&loan_id=${loanId}&paid_date=${paidDate}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const principal = parseFloat(data.remaining_principal);
            const interest = parseFloat(data.effective_interest);
            const isOverdue = data.is_overdue;
            const rate = parseFloat(data.scaled_interest_rate);
            const periods = parseInt(data.overdue_periods);

            modal.dataset.currentPrincipal = principal;
            modal.dataset.currentInterest = interest;
            modal.dataset.isOverdue = isOverdue ? '1' : '0';

            document.getElementById('repayPrincipal').textContent = APP.formatMoney(principal);
            document.getElementById('repayInterest').textContent = APP.formatMoney(interest);

            // Overdue alert
            const overdueAlert = document.getElementById('overdueAlert');
            const overdueText = document.getElementById('overdueAlertText');
            if (isOverdue) {
                overdueAlert.classList.remove('d-none');
                if (overdueText) overdueText.innerHTML =
                    `Taxa escalada: <strong>${rate.toFixed(0)}%</strong> (${periods} período(s) de 30 dias em atraso — a taxa aumenta 15% por cada período).<br>` +
                    `Juros devidos: <strong>${APP.formatMoney(interest)}</strong>`;

                // Disable "Apenas Juros" option when overdue
                const radioInterest = document.getElementById('payInterest');
                const optDiv = document.getElementById('optInterestOnly');
                if (radioInterest) radioInterest.disabled = true;
                if (optDiv) {
                    optDiv.style.opacity = '0.5';
                    optDiv.title = 'Não disponível: empréstimo em atraso. Os juros são escalados.';
                }
                // If "interest_only" was selected, switch to total
                if (radioInterest?.checked) {
                    document.getElementById('payTotal').checked = true;
                }
            } else {
                overdueAlert.classList.add('d-none');
                const radioInterest = document.getElementById('payInterest');
                const optDiv = document.getElementById('optInterestOnly');
                if (radioInterest) radioInterest.disabled = false;
                if (optDiv) { optDiv.style.opacity = '1'; optDiv.title = ''; }
            }

            // Update amount field based on selected type
            updateRepayAmount();
        });
}

function updateRepayAmount() {
    const modal = document.getElementById('modalReembolso');
    const principal = parseFloat(modal.dataset.currentPrincipal || 0);
    const interest = parseFloat(modal.dataset.currentInterest || 0);
    const elAmount = document.getElementById('repayAmount');
    const elHint = document.getElementById('repayAmountHint');

    const type = document.querySelector('input[name="payment_type"]:checked')?.value || 'total';

    if (type === 'total') {
        elAmount.value = (principal + interest).toFixed(2);
        elAmount.readOnly = true;
        if (elHint) elHint.textContent = `Capital (${APP.formatMoney(principal)}) + Juros (${APP.formatMoney(interest)})`;
    } else if (type === 'interest_only') {
        elAmount.value = interest.toFixed(2);
        elAmount.readOnly = true;
        if (elHint) elHint.textContent = `Apenas juros (capital de ${APP.formatMoney(principal)} → novo empréstimo)`;
    } else {
        // Partial: user types amount
        elAmount.value = '';
        elAmount.readOnly = false;
        elAmount.focus();
        if (elHint) elHint.textContent = `Mínimo: ${APP.formatMoney(interest)} (juros). Saldo do capital → novo empréstimo.`;
    }
}

// Listeners for payment type radios
document.querySelectorAll('input[name="payment_type"]').forEach(radio => {
    radio.addEventListener('change', updateRepayAmount);
});

// Listener for paid date change — recalculate scaled interest
document.getElementById('repayPaidDate')?.addEventListener('change', function () {
    const modal = document.getElementById('modalReembolso');
    if (!modal.dataset.loanId) return;
    // Brief debounce
    clearTimeout(window._repayCalcTimer);
    window._repayCalcTimer = setTimeout(refreshRepayCalculation, 300);
});

// ── REPAY FORM SUBMIT ─────────────────────────────────────────────

const fRepay = document.getElementById('formReembolso');
if (fRepay) fRepay.addEventListener('submit', function (e) {
    e.preventDefault();

    const modal = document.getElementById('modalReembolso');
    const interest = parseFloat(modal.dataset.currentInterest || 0);
    const type = document.querySelector('input[name="payment_type"]:checked')?.value || 'total';
    const amount = parseFloat(document.getElementById('repayAmount')?.value || 0);

    // Client-side: block partial < interest
    if (type === 'partial' && amount < interest - 0.01) {
        Swal.fire({
            title: 'Valor Insuficiente',
            html: `Para pagamento parcial, o valor mínimo é <b>${APP.formatMoney(interest)}</b> (valor dos juros).<br>Aumente o valor ou seleccione "Apenas Juros".`,
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Client-side: block interest_only if overdue
    if (type === 'interest_only' && modal.dataset.isOverdue === '1') {
        Swal.fire({ title: 'Não Permitido', text: 'Pagamento apenas de juros não é permitido quando o empréstimo está em atraso.', icon: 'error' });
        return;
    }

    fetch('../api/emprestimos_api.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Reembolso Registado',
                    html: data.message,
                    icon: 'success',
                    confirmButtonColor: '#0d9488'
                });
                bootstrap.Modal.getInstance(document.getElementById('modalReembolso'))?.hide();
                this.reset();
                loadLoans();
            } else {
                APP.showToast(data.message, 'danger');
            }
        });
});

// ── INIT ────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadLoans();
    loadMembersForLoan();
});
