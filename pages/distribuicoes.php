<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
requireRole(ROLE_ADMIN, ROLE_USER);

$pageTitle = 'Distribuição de Ciclo';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$isAdmin   = isAdmin();
$cycleId   = $activeCycle['id'] ?? 0;
$cycleName = sanitize($activeCycle['name'] ?? 'N/A');
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-gift me-2"></i>Distribuição de Ciclo</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Distribuição</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-primary fs-6 align-self-center"><?= $cycleName ?></span>
    </div>
</div>

<!-- Status Banner -->
<div id="statusBanner" class="mb-4"></div>

<!-- Summary KPI Cards -->
<div class="row g-3 mb-4" id="summaryCards">
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-label">Total Juros</div>
            <div class="kpi-value" id="kpiTotalInterest">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-label">Total Multas</div>
            <div class="kpi-value" id="kpiTotalLateFees">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="bi bi-person-check"></i></div>
            <div class="kpi-label">Membros Elegíveis</div>
            <div class="kpi-value" id="kpiEligibleCount">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card kpi-info" id="kpiStateCard">
            <div class="kpi-icon"><i class="bi bi-info-circle"></i></div>
            <div class="kpi-label">Estado da Distribuição</div>
            <div class="kpi-value" id="kpiState" style="font-size:1rem;">—</div>
        </div>
    </div>
</div>

<!-- Preview Table -->
<div class="table-card" id="previewSection">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-table me-1"></i>Pré-visualização da Distribuição</h6>
        <?php if ($isAdmin): ?>
        <button class="btn btn-success" id="btnDistribute" style="display:none;" onclick="confirmDistribute()">
            <i class="bi bi-check2-circle me-1"></i>Confirmar Distribuição
        </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" id="previewTable">
            <thead class="table-light">
                <tr>
                    <th>Membro</th>
                    <th class="text-end">Poupanças</th>
                    <th class="text-end">Movimentação</th>
                    <th class="text-center">Elegível</th>
                    <th class="text-end">Juros Fixos</th>
                    <th class="text-end">Excedente</th>
                    <th class="text-end">Multas</th>
                    <th class="text-end">Retorno Capital</th>
                    <th class="text-end fw-bold">TOTAL</th>
                </tr>
            </thead>
            <tbody id="previewTableBody">
                <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-hourglass-split me-1"></i>A calcular...</td></tr>
            </tbody>
            <tfoot class="table-secondary fw-bold" id="previewTableFoot" style="display:none;">
                <tr>
                    <td>TOTAL</td>
                    <td class="text-end" id="footSavings">—</td>
                    <td class="text-end">—</td>
                    <td class="text-center">—</td>
                    <td class="text-end" id="footFixed">—</td>
                    <td class="text-end" id="footSurplus">—</td>
                    <td class="text-end" id="footLateFee">—</td>
                    <td class="text-end" id="footCapital">—</td>
                    <td class="text-end" id="footTotal">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- History Section (shown when already distributed) -->
<div class="table-card mt-4" id="historySection" style="display:none;">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Histórico de Distribuição</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" id="historyTable">
            <thead class="table-light">
                <tr>
                    <th>Membro</th>
                    <th>Tipo</th>
                    <th class="text-end">Valor</th>
                    <th>Descrição</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <tr><td colspan="5" class="text-center text-muted py-3">A carregar...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var baseUrl = (document.querySelector('meta[name="base-url"]') || {}).content || '';
    var formatMoney = APP ? APP.formatMoney : function(v) { return parseFloat(v).toFixed(2) + ' MT'; };

    var typeLabels = {
        'interest':  '<span class="badge bg-primary">Juros Fixos</span>',
        'surplus':   '<span class="badge bg-info text-dark">Excedente</span>',
        'late_fee':  '<span class="badge bg-warning text-dark">Multas</span>',
    };

    function loadPreview() {
        fetch(baseUrl + '/api/distribuicoes_api.php?action=preview&_=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    document.getElementById('previewTableBody').innerHTML =
                        '<tr><td colspan="9" class="text-center text-danger py-3">' + (data.message || 'Erro') + '</td></tr>';
                    return;
                }

                var s = data.summary;

                // KPIs
                document.getElementById('kpiTotalInterest').textContent  = formatMoney(s.total_interest);
                document.getElementById('kpiTotalLateFees').textContent   = formatMoney(s.total_late_fees);
                document.getElementById('kpiEligibleCount').textContent   = s.eligible_count;
                document.getElementById('kpiState').textContent           = s.already_distributed ? 'Distribuído' : 'Pendente';
                document.getElementById('kpiStateCard').className = 'kpi-card ' + (s.already_distributed ? 'kpi-success' : 'kpi-warning');

                // Status banner
                var bannerEl = document.getElementById('statusBanner');
                if (s.already_distributed) {
                    bannerEl.innerHTML = '<div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill fs-5"></i><div><strong>Distribuição concluída.</strong> Os valores já foram registados no sistema.</div></div>';
                } else {
                    var surplusMsg = s.has_surplus
                        ? ' Excedente de <strong>' + formatMoney(s.surplus_amount) + '</strong> será distribuído proporcionalmente.'
                        : ' Juros insuficientes para entitlement total — será feita distribuição proporcional.';
                    bannerEl.innerHTML = '<div class="alert alert-info d-flex align-items-center gap-2"><i class="bi bi-info-circle-fill fs-5"></i><div>Distribuição ainda não executada para este ciclo.' + surplusMsg + '</div></div>';
                }

                // Preview table
                var rows = data.members || [];
                var tbody = document.getElementById('previewTableBody');

                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">Nenhum membro encontrado.</td></tr>';
                    return;
                }

                var totSavings = 0, totFixed = 0, totSurplus = 0, totLF = 0, totCap = 0, totTotal = 0;

                tbody.innerHTML = rows.map(function(m) {
                    totSavings  += m.total_savings;
                    totFixed    += m.interest_fixed;
                    totSurplus  += m.interest_surplus;
                    totLF       += m.late_fee_share;
                    totCap      += m.capital_return;
                    totTotal    += m.total_to_receive;

                    var eligBadge = m.is_eligible
                        ? '<span class="badge bg-success"><i class="bi bi-check"></i> Sim</span>'
                        : '<span class="badge bg-secondary">Não</span>';
                    var rowClass = m.is_eligible ? 'table-success' : '';

                    return '<tr class="' + rowClass + '">' +
                        '<td class="fw-semibold">' + m.full_name + '</td>' +
                        '<td class="text-end">' + formatMoney(m.total_savings) + '</td>' +
                        '<td class="text-end">' + formatMoney(m.total_movement) + '</td>' +
                        '<td class="text-center">' + eligBadge + '</td>' +
                        '<td class="text-end">' + formatMoney(m.interest_fixed) + '</td>' +
                        '<td class="text-end">' + formatMoney(m.interest_surplus) + '</td>' +
                        '<td class="text-end">' + formatMoney(m.late_fee_share) + '</td>' +
                        '<td class="text-end">' + formatMoney(m.capital_return) + '</td>' +
                        '<td class="text-end fw-bold">' + formatMoney(m.total_to_receive) + '</td>' +
                    '</tr>';
                }).join('');

                // Footer totals
                var foot = document.getElementById('previewTableFoot');
                foot.style.display = '';
                document.getElementById('footSavings').textContent = formatMoney(totSavings);
                document.getElementById('footFixed').textContent   = formatMoney(totFixed);
                document.getElementById('footSurplus').textContent = formatMoney(totSurplus);
                document.getElementById('footLateFee').textContent = formatMoney(totLF);
                document.getElementById('footCapital').textContent = formatMoney(totCap);
                document.getElementById('footTotal').textContent   = formatMoney(totTotal);

                // Show distribute button if not yet distributed and user is admin
                <?php if ($isAdmin): ?>
                if (!s.already_distributed) {
                    document.getElementById('btnDistribute').style.display = '';
                }
                <?php endif; ?>

                // If already distributed, load history
                if (s.already_distributed) {
                    loadHistory();
                    document.getElementById('historySection').style.display = '';
                }
            })
            .catch(function(e) {
                document.getElementById('previewTableBody').innerHTML =
                    '<tr><td colspan="9" class="text-center text-danger py-3">Erro de conexão.</td></tr>';
            });
    }

    function loadHistory() {
        fetch(baseUrl + '/api/distribuicoes_api.php?action=history&_=' + Date.now())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var rows = data.data || [];
                var tbody = document.getElementById('historyTableBody');
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sem registos.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map(function(r) {
                    var typeLabel = typeLabels[r.type] || ('<span class="badge bg-secondary">' + r.type + '</span>');
                    var dt = r.distributed_at ? new Date(r.distributed_at).toLocaleDateString('pt-MZ') : '—';
                    return '<tr>' +
                        '<td class="fw-semibold">' + r.full_name + '</td>' +
                        '<td>' + typeLabel + '</td>' +
                        '<td class="text-end">' + formatMoney(r.amount) + '</td>' +
                        '<td class="text-muted small">' + (r.description || '') + '</td>' +
                        '<td>' + dt + '</td>' +
                    '</tr>';
                }).join('');
            });
    }

    window.confirmDistribute = function() {
        Swal.fire({
            title: 'Confirmar Distribuição',
            html: 'Esta acção irá registar a distribuição final do ciclo.<br><strong>Esta operação não pode ser revertida.</strong><br><br>Deseja continuar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  '<i class="bi bi-check2-circle me-1"></i>Sim, executar distribuição',
            cancelButtonText:   'Cancelar',
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var btn = document.getElementById('btnDistribute');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A processar...';

            fetch(baseUrl + '/api/distribuicoes_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=distribute'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        title: 'Distribuição Executada!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        loadPreview();
                        loadHistory();
                        document.getElementById('historySection').style.display = '';
                        btn.style.display = 'none';
                    });
                } else {
                    Swal.fire('Erro', data.message || 'Ocorreu um erro.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar Distribuição';
                }
            })
            .catch(function() {
                Swal.fire('Erro', 'Erro de conexão.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar Distribuição';
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        loadPreview();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
