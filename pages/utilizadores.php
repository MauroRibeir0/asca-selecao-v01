<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN);

$pageTitle = 'Gestão de Utilizadores';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Load members for dropdowns
$db      = Database::getInstance();
$members = $db->fetchAll("SELECT id, full_name FROM members WHERE status='active' ORDER BY full_name");
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-people-fill me-2"></i>Gestão de Utilizadores</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Utilizadores</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="bi bi-person-plus me-1"></i>Novo Utilizador
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon"><i class="bi bi-people"></i></div>
            <div class="kpi-label">Total</div>
            <div class="kpi-value" id="kpiTotal">—</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon"><i class="bi bi-shield-lock"></i></div>
            <div class="kpi-label">Admins</div>
            <div class="kpi-value" id="kpiAdmins">—</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card kpi-info">
            <div class="kpi-icon"><i class="bi bi-person-badge"></i></div>
            <div class="kpi-label">Staff</div>
            <div class="kpi-value" id="kpiStaff">—</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="bi bi-person-check"></i></div>
            <div class="kpi-label">Membros</div>
            <div class="kpi-value" id="kpiMembers">—</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="bi bi-person-x"></i></div>
            <div class="kpi-label">Inactivos</div>
            <div class="kpi-value" id="kpiInactive">—</div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Lista de Utilizadores</h6>
        <span class="badge bg-primary" id="userCount">0</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="usersTable">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nome Completo</th>
                    <th>Papel</th>
                    <th>Estado</th>
                    <th>Email</th>
                    <th>Última Actividade</th>
                    <th class="text-end">Acções</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr><td colspan="7" class="text-center text-muted py-4">A carregar...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ════ Modal: Create User ════ -->
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Novo Utilizador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCreate">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha *</label>
                            <input type="password" class="form-control" name="password" required minlength="6" autocomplete="new-password">
                            <div class="form-text">Mínimo 6 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Papel *</label>
                            <select class="form-select" name="role" id="createRole" required onchange="toggleMemberField('create')">
                                <option value="">Seleccionar...</option>
                                <option value="admin">Admin</option>
                                <option value="user">Staff (user)</option>
                                <option value="member">Membro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="is_active">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12" id="createMemberField" style="display:none;">
                            <label class="form-label">Membro Associado *</label>
                            <select class="form-select" name="member_id" id="createMemberId">
                                <option value="">Seleccionar membro...</option>
                                <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= sanitize($m['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Criar Utilizador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════ Modal: Edit User ════ -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar Utilizador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEdit">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id"     id="editId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" disabled>
                            <div class="form-text">O username não pode ser alterado.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="full_name" id="editFullName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Papel *</label>
                            <select class="form-select" name="role" id="editRole" required onchange="toggleMemberField('edit')">
                                <option value="admin">Admin</option>
                                <option value="user">Staff (user)</option>
                                <option value="member">Membro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="is_active" id="editIsActive">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12" id="editMemberField" style="display:none;">
                            <label class="form-label">Membro Associado *</label>
                            <select class="form-select" name="member_id" id="editMemberId">
                                <option value="">Seleccionar membro...</option>
                                <?php foreach ($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= sanitize($m['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════ Modal: Reset Password ════ -->
<div class="modal fade" id="modalResetPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key me-2"></i>Redefinir Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formResetPassword">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id"     id="resetUserId">
                    <p>A redefinir senha para: <strong id="resetUserName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Nova Senha *</label>
                        <input type="password" class="form-control" name="new_password" id="newPassword" required minlength="6" autocomplete="new-password">
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nova Senha *</label>
                        <input type="password" class="form-control" id="confirmPassword" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-key me-1"></i>Redefinir Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '';
    var dtInstance = null;
    var usersMap = {};  // id → user object; avoids JSON.stringify in onclick attributes

    var roleBadge = {
        'admin':  '<span class="badge bg-danger">Admin</span>',
        'user':   '<span class="badge bg-info text-dark">Staff</span>',
        'member': '<span class="badge bg-success">Membro</span>',
    };

    function loadUsers() {
        fetch(baseUrl + '/api/utilizadores_api.php?action=list&_=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    APP.showToast(data.message || 'Erro ao carregar utilizadores', 'danger');
                    return;
                }
                var rows = data.data || [];
                document.getElementById('userCount').textContent = rows.length;

                // KPIs
                var admins = 0, staff = 0, members = 0, inactive = 0;
                rows.forEach(function(u) {
                    usersMap[u.id] = u;  // store for onclick lookup
                    if (u.role === 'admin')  admins++;
                    if (u.role === 'user')   staff++;
                    if (u.role === 'member') members++;
                    if (!u.is_active || u.is_active == 0) inactive++;
                });
                document.getElementById('kpiTotal').textContent    = rows.length;
                document.getElementById('kpiAdmins').textContent   = admins;
                document.getElementById('kpiStaff').textContent    = staff;
                document.getElementById('kpiMembers').textContent  = members;
                document.getElementById('kpiInactive').textContent = inactive;

                var tbody = document.getElementById('usersTableBody');
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum utilizador encontrado.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map(function(u) {
                    var activeBadge = u.is_active
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>';
                    var lastLogin = u.last_login
                        ? new Date(u.last_login).toLocaleString('pt-MZ', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})
                        : '<span class="text-muted">—</span>';
                    var toggleIcon  = u.is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
                    var toggleTitle = u.is_active ? 'Desactivar' : 'Activar';

                    // Pass numeric ID only — user object is looked up from usersMap to avoid
                    // JSON.stringify double-quotes breaking the HTML onclick attribute
                    return '<tr>' +
                        '<td class="fw-semibold">' + u.username + '</td>' +
                        '<td>' + u.full_name + '</td>' +
                        '<td>' + (roleBadge[u.role] || u.role) + '</td>' +
                        '<td>' + activeBadge + '</td>' +
                        '<td>' + (u.email || '—') + '</td>' +
                        '<td>' + lastLogin + '</td>' +
                        '<td class="text-end">' +
                            '<button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(' + u.id + ')" title="Editar"><i class="bi bi-pencil"></i></button>' +
                            '<button class="btn btn-sm btn-outline-warning me-1" onclick="openResetModal(' + u.id + ')" title="Redefinir Senha"><i class="bi bi-key"></i></button>' +
                            '<button class="btn btn-sm btn-outline-secondary" onclick="toggleActive(' + u.id + ')" title="' + toggleTitle + '"><i class="bi ' + toggleIcon + '"></i></button>' +
                        '</td>' +
                    '</tr>';
                }).join('');

                // Init/Refresh DataTable
                if (dtInstance) { dtInstance.destroy(); }
                dtInstance = $('#usersTable').DataTable({
                    language: {
                        search: 'Pesquisar:',
                        lengthMenu: 'Mostrar _MENU_ registos',
                        info: 'A mostrar _START_ a _END_ de _TOTAL_',
                        infoEmpty: 'Sem registos',
                        paginate: { previous: '←', next: '→' },
                        zeroRecords: 'Nenhum resultado encontrado',
                        emptyTable: 'Nenhum dado disponível'
                    },
                    pageLength: 15,
                    order: [[1, 'asc']],
                    columnDefs: [{ orderable: false, targets: [6] }]
                });
            })
            .catch(function() {
                APP.showToast('Erro de conexão', 'danger');
            });
    }

    window.toggleMemberField = function(prefix) {
        var roleEl   = document.getElementById(prefix + 'Role');
        var fieldEl  = document.getElementById(prefix + 'MemberField');
        var memberEl = document.getElementById(prefix + 'MemberId');
        if (!roleEl || !fieldEl) return;
        var show = roleEl.value === 'member';
        fieldEl.style.display = show ? '' : 'none';
        if (memberEl) memberEl.required = show;
    };

    window.openEditModal = function(id) {
        var user = usersMap[id];
        if (!user) return;
        document.getElementById('editId').value        = user.id;
        document.getElementById('editUsername').value  = user.username;
        document.getElementById('editFullName').value  = user.full_name;
        document.getElementById('editEmail').value     = user.email || '';
        document.getElementById('editRole').value      = user.role;
        document.getElementById('editIsActive').value  = user.is_active ? '1' : '0';
        document.getElementById('editMemberId').value  = user.member_id || '';
        toggleMemberField('edit');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    };

    window.openResetModal = function(id) {
        var user = usersMap[id];
        if (!user) return;
        document.getElementById('resetUserId').value        = user.id;
        document.getElementById('resetUserName').textContent = user.username;
        document.getElementById('newPassword').value        = '';
        document.getElementById('confirmPassword').value    = '';
        new bootstrap.Modal(document.getElementById('modalResetPassword')).show();
    };

    window.toggleActive = function(id) {
        fetch(baseUrl + '/api/utilizadores_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=toggle_active&id=' + id
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) loadUsers();
        });
    };

    // Form: Create
    document.getElementById('formCreate').addEventListener('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var btn = this.querySelector('[type=submit]');
        btn.disabled = true;
        fetch(baseUrl + '/api/utilizadores_api.php', {
            method: 'POST',
            body: new URLSearchParams(fd)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalCreate')).hide();
                document.getElementById('formCreate').reset();
                document.getElementById('createMemberField').style.display = 'none';
                loadUsers();
            }
            btn.disabled = false;
        })
        .catch(function() { btn.disabled = false; APP.showToast('Erro de conexão', 'danger'); });
    });

    // Form: Edit
    document.getElementById('formEdit').addEventListener('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var btn = this.querySelector('[type=submit]');
        btn.disabled = true;
        fetch(baseUrl + '/api/utilizadores_api.php', {
            method: 'POST',
            body: new URLSearchParams(fd)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
                loadUsers();
            }
            btn.disabled = false;
        })
        .catch(function() { btn.disabled = false; APP.showToast('Erro de conexão', 'danger'); });
    });

    // Form: Reset Password
    document.getElementById('formResetPassword').addEventListener('submit', function(e) {
        e.preventDefault();
        var newPw  = document.getElementById('newPassword').value;
        var confPw = document.getElementById('confirmPassword').value;
        if (newPw !== confPw) {
            APP.showToast('As senhas não coincidem.', 'danger');
            return;
        }
        var fd  = new FormData(this);
        var btn = this.querySelector('[type=submit]');
        btn.disabled = true;
        fetch(baseUrl + '/api/utilizadores_api.php', {
            method: 'POST',
            body: new URLSearchParams(fd)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            APP.showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalResetPassword')).hide();
            }
            btn.disabled = false;
        })
        .catch(function() { btn.disabled = false; APP.showToast('Erro de conexão', 'danger'); });
    });

    document.addEventListener('DOMContentLoaded', function() {
        loadUsers();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
