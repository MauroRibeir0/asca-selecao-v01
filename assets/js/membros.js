/**
 * ASCA Selecção - Members JS
 */

let membersDataTable = null;

function loadMembers() {
    fetch(`../api/membros_api.php?action=list&_=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderMembersTable(data.data);
        });
}

function renderMembersTable(members) {
    const tbody = document.getElementById('membersTableBody');
    document.getElementById('memberCount').textContent = members.length;

    if (members.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Nenhum membro registado neste ciclo</td></tr>';
        return;
    }

    tbody.innerHTML = members.map((m, i) => {
        const joiaBadge = m.joia_status === 'paid'
            ? '<span class="badge bg-success">Paga</span>'
            : `<span class="badge bg-warning text-dark cursor-pointer" onclick="openJoiaModal(${m.id})" style="cursor:pointer" title="Clique para registar pagamento">Pendente</span>`;

        const statusBadge = {
            active: '<span class="badge bg-success">Activo</span>',
            inactive: '<span class="badge bg-secondary">Inactivo</span>',
            suspended: '<span class="badge bg-warning text-dark">Suspenso</span>',
        }[m.status] || '';

        return `<tr>
            <td>${i + 1}</td>
            <td><a href="perfil_membro.php?id=${m.id}" class="text-decoration-none text-dark"><strong>${m.full_name}</strong></a></td>
            <td>${m.phone || '—'}</td>
            <td>${joiaBadge}</td>
            <td>${APP.formatMoney(parseFloat(m.total_contributions || 0))}</td>
            <td>${APP.formatMoney(parseFloat(m.total_loans || 0))}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    ${m.joia_status !== 'paid' ? `<button class="btn btn-outline-warning" onclick="openJoiaModal(${m.id})" title="Registar Jóia"><i class="bi bi-gem"></i></button>` : ''}
                    <button class="btn btn-outline-primary" onclick="editMember(${m.id})" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteMember(${m.id}, '${m.full_name}')" title="Desactivar">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    // Init DataTable
    if (membersDataTable) membersDataTable.destroy();
    membersDataTable = $('#membersTable').DataTable({
        language: {
            search: 'Pesquisar:',
            lengthMenu: 'Mostrar _MENU_ registos',
            info: 'A mostrar _START_ a _END_ de _TOTAL_',
            paginate: { previous: '←', next: '→' },
            zeroRecords: 'Nenhum resultado encontrado',
        },
        pageLength: 15,
        order: [[1, 'asc']],
    });
}

// ─── Create / Edit ───────────────────────
document.getElementById('formMembro').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const action = formData.get('action');

    fetch('../api/membros_api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                APP.showToast(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalMembro')).hide();
                this.reset();
                loadMembers();
            } else {
                APP.showToast(data.message, 'danger');
            }
        });
});

function editMember(id) {
    fetch(`../api/membros_api.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const m = data.data;
            document.getElementById('modalMembroTitle').textContent = 'Editar Membro';
            document.getElementById('membroId').value = m.id;
            document.getElementById('membroAction').value = 'update';
            document.getElementById('membroName').value = m.full_name;
            document.getElementById('membroPhone').value = m.phone || '';
            document.getElementById('membroEmail').value = m.email || '';
            document.getElementById('membroIdNumber').value = m.id_number || '';
            document.getElementById('membroJoinDate').value = m.join_date;
            document.getElementById('membroAddress').value = m.address || '';
            document.getElementById('membroNotes').value = m.notes || '';
            document.getElementById('membroStatus').value = m.status;
            document.getElementById('statusField').style.display = 'block';
            new bootstrap.Modal(document.getElementById('modalMembro')).show();
        });
}

// Reset modal on open for "new"
document.getElementById('modalMembro').addEventListener('show.bs.modal', function (e) {
    if (e.relatedTarget) { // Triggered by button (not JS)
        document.getElementById('modalMembroTitle').textContent = 'Novo Membro';
        document.getElementById('membroAction').value = 'create';
        document.getElementById('membroId').value = '';
        document.getElementById('statusField').style.display = 'none';
        document.getElementById('formMembro').reset();

        // Clear all validation states
        this.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
            el.classList.remove('is-invalid', 'is-valid');
        });
    }
});

// Real-time validation for member fields
['membroName', 'membroPhone', 'membroEmail', 'membroIdNumber'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('blur', function () {
            APP.validateInput(this, 'check_availability');
        });
    }
});

function deleteMember(id, name) {
    Swal.fire({
        title: 'Eliminação Definitiva',
        html: `Aviso: ao eliminar o membro <b>"${name}"</b>, as suas jóias, contribuições e empréstimos desaparecem permanentemente sem possibilidade de recuperação.<br><br>Para confirmar, insira a sua senha de Administrador:`,
        input: 'password',
        inputPlaceholder: 'Senha de Administrador',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#8c8c8c',
        confirmButtonText: '<i class="bi bi-trash"></i> Eliminar Membro',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return 'A senha de administrador é obrigatória!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('admin_password', result.value);

            fetch('../api/membros_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    APP.showToast(data.message, data.success ? 'success' : 'danger');
                    if (data.success) {
                        loadMembers();
                    }
                });
        }
    });
}

// ─── Jóia Payment ────────────────────────
function openJoiaModal(memberId) {
    document.getElementById('joiaMemberId').value = memberId;
    new bootstrap.Modal(document.getElementById('modalJoia')).show();
}

document.getElementById('formJoia').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../api/membros_api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalJoia')).hide();
                loadMembers();
            }
        });
});

// ─── Jóia Select Modal (header button) ───
let cachedMembers = [];

function openJoiaSelectModal() {
    const select = document.getElementById('joiaSelectMember');
    select.innerHTML = '<option disabled>A carregar...</option>';

    fetch('../api/membros_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const pending = data.data.filter(m => m.joia_status !== 'paid');
            if (pending.length === 0) {
                select.innerHTML = '<option disabled>Todos os membros já pagaram a jóia ✅</option>';
            } else {
                select.innerHTML = pending.map(m =>
                    `<option value="${m.id}">${m.full_name}</option>`
                ).join('');
            }
        });

    new bootstrap.Modal(document.getElementById('modalJoiaSelect')).show();
}

function confirmJoiaSelect() {
    const select = document.getElementById('joiaSelectMember');
    const memberId = select.value;
    if (!memberId) {
        APP.showToast('Seleccione um membro', 'warning');
        return;
    }
    bootstrap.Modal.getInstance(document.getElementById('modalJoiaSelect')).hide();
    openJoiaModal(parseInt(memberId));
}

function exportCSV() {
    // Simple CSV export from table
    const table = document.getElementById('membersTable');
    let csv = [];
    for (let row of table.rows) {
        let cols = [];
        for (let cell of row.cells) {
            cols.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        }
        csv.push(cols.join(','));
    }
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'membros_export.csv';
    a.click();
}

document.addEventListener('DOMContentLoaded', loadMembers);
