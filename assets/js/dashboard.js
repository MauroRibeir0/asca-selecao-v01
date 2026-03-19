/**
 * ASCA Selecção - Dashboard JS
 */

let chartContribVsLoans = null;
let chartDistribution = null;

function loadDashboard() {
    // Show skeletons in tables/activity during load
    document.getElementById('upcomingDueTable').innerHTML = `
        <tr><td colspan="5" class="p-3">${APP.skeleton('text', 3)}</td></tr>
    `;
    document.getElementById('recentActivityList').innerHTML = APP.skeleton('text', 5);

    fetch(`../api/dashboard_api.php?_=${Date.now()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                APP.showToast(data.message || 'Erro ao carregar dashboard', 'danger');
                return;
            }
            updateKPIs(data.kpis);
            updateCharts(data.charts, data.kpis);
            updateUpcomingDue(data.upcoming_due);
            updateRecentActivity(data.recent_activity);
            updateAlerts(data.kpis, data.members_low_movement_list);

            // Animate cards
            document.querySelectorAll('.kpi-card, .chart-card, .quick-action, .table-card').forEach((el, i) => {
                APP.fadeIn(el, i * 50);
            });
        })
        .catch(() => APP.showToast('Erro de conexão', 'danger'));
}

function updateKPIs(kpis) {
    document.getElementById('kpiMembers').textContent = kpis.total_members;
    document.getElementById('kpiFund').textContent = APP.formatMoney(kpis.total_fund);
    document.getElementById('kpiLoaned').textContent = APP.formatMoney(kpis.total_loaned);
    document.getElementById('kpiInterest').textContent = APP.formatMoney(kpis.total_interest);
    document.getElementById('kpiOverdue').textContent = kpis.overdue_loans_count;
    document.getElementById('kpiAvailable').textContent = APP.formatMoney(kpis.capital_available);
    document.getElementById('kpiLateFees').textContent = APP.formatMoney(kpis.total_late_fees);
    document.getElementById('kpiRecovery').textContent = kpis.recovery_rate + '%';
}

function updateCharts(charts, kpis) {
    // ── Bar Chart: Contributions vs Loans ──
    const months = [...new Set([
        ...charts.monthly_contributions.map(r => r.month),
        ...charts.monthly_loans.map(r => r.month)
    ])].sort();

    const contribData = months.map(m => {
        const found = charts.monthly_contributions.find(r => r.month === m);
        return found ? parseFloat(found.total) : 0;
    });
    const loanData = months.map(m => {
        const found = charts.monthly_loans.find(r => r.month === m);
        return found ? parseFloat(found.total) : 0;
    });

    const labels = months.map(m => {
        const [y, mo] = m.split('-');
        const names = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return names[parseInt(mo)] + ' ' + y.slice(2);
    });

    if (chartContribVsLoans) chartContribVsLoans.destroy();
    const ctx1 = document.getElementById('chartContribVsLoans');
    if (ctx1) {
        chartContribVsLoans = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Contribuições',
                        data: contribData,
                        backgroundColor: 'rgba(13, 148, 136, 0.7)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Empréstimos',
                        data: loanData,
                        backgroundColor: 'rgba(14, 165, 233, 0.7)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => (v / 1000) + 'k MT'
                        }
                    }
                }
            }
        });
    }

    // ── Doughnut Chart: Distribution ──
    if (chartDistribution) chartDistribution.destroy();
    const ctx2 = document.getElementById('chartDistribution');
    if (ctx2) {
        chartDistribution = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Fundo Disponível', 'Empréstimos Activos', 'Juros', 'Multas'],
                datasets: [{
                    data: [
                        Math.max(0, kpis.capital_available),
                        kpis.active_loans_total,
                        kpis.total_interest,
                        kpis.total_late_fees
                    ],
                    backgroundColor: [
                        'rgba(13, 148, 136, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(220, 38, 38, 0.8)'
                    ],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 } } }
                },
                cutout: '65%'
            }
        });
    }
}

function updateUpcomingDue(items) {
    const tbody = document.getElementById('upcomingDueTable');
    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-check-circle me-1"></i>Sem obrigações pendentes</td></tr>';
        return;
    }
    tbody.innerHTML = items.map(l => {
        const typeBadge = l.type === 'loan'
            ? '<span class="badge bg-info-subtle text-info border border-info-subtle">Empréstimo</span>'
            : '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Mensalidade</span>';

        const urgencyBadge = l.urgency === 'overdue'
            ? '<span class="badge bg-danger">Em Atraso</span>'
            : '<span class="badge bg-warning text-dark">Pendente</span>';

        const href = l.type === 'loan'
            ? `emprestimos.php`
            : `contribuicoes.php`;

        const phone = l.phone || '';
        const dueDateFormatted = new Date(l.due_date).toLocaleDateString('pt-MZ');
        const amountFormatted = APP.formatMoney(parseFloat(l.amount));
        const typeLabel = l.type === 'loan' ? 'Empréstimo' : 'Contribuição Mensal';

        return `
            <tr class="cursor-pointer" onclick="window.location.href='${href}'" title="Clique para ver detalhes">
                <td>
                    <div class="fw-bold">${l.full_name}</div>
                    <div style="font-size: 0.75rem;" class="mt-1">${typeBadge}</div>
                </td>
                <td class="fw-600">${amountFormatted}</td>
                <td>${dueDateFormatted}</td>
                <td>${urgencyBadge}</td>
                <td>
                    ${phone ? `
                        <button class="btn btn-sm btn-success p-1 lh-1" onclick="event.stopPropagation(); sendWhatsAppMessage('${phone}', '${l.full_name.split(' ')[0]}', '${typeLabel}', '${amountFormatted}', '${dueDateFormatted}', '${l.urgency}')" title="Enviar lembrete via WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </button>
                    ` : '<span class="text-muted" title="Telefone não registado">—</span>'}
                </td>
            </tr>
        `;
    }).join('');
}

function sendWhatsAppMessage(phone, name, type, amount, dueDate, urgency) {
    let msg = '';
    if (urgency === 'overdue') {
        msg = `Olá ${name}, notamos que o seu ${type} no valor de ${amount} está em atraso (vencimento: ${dueDate}). Por favor, regularize assim que possível. Esta mensagem foi gerada automaticamente pelo sistema ASCA.`;
    } else {
        msg = `Olá ${name}, lembramos o vencimento da sua obrigação (${type}) no valor de ${amount} para o dia ${dueDate}. Esta mensagem foi gerada automaticamente pelo sistema ASCA.`;
    }

    // Clean phone number
    let cleanPhone = phone.replace(/\D/g, '');
    if (cleanPhone.length === 9) cleanPhone = '258' + cleanPhone;

    const url = `https://wa.me/${cleanPhone}/?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

function updateRecentActivity(activities) {
    const container = document.getElementById('recentActivityList');
    if (!activities || activities.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">Sem actividades recentes</div>';
        return;
    }

    const icons = {
        'login': 'bi-box-arrow-in-right text-primary',
        'logout': 'bi-box-arrow-right text-secondary',
        'loan_created': 'bi-bank text-info',
        'loan_repaid': 'bi-check-circle text-success',
        'contribution_created': 'bi-cash-coin text-success',
        'member_created': 'bi-person-plus text-primary',
    };

    container.innerHTML = activities.map(a => {
        const icon = icons[a.action] || 'bi-circle text-secondary';
        const time = new Date(a.created_at).toLocaleString('pt-MZ', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        return `
            <div class="d-flex align-items-start gap-2 mb-2 pb-2 border-bottom">
                <i class="bi ${icon} mt-1"></i>
                <div class="flex-grow-1">
                    <div class="small">${a.description || a.action}</div>
                    <div class="text-muted" style="font-size:.7rem;">${time}</div>
                </div>
            </div>`;
    }).join('');
}

let alertTimeout = null;
function updateAlerts(kpis, lowMovementMembers) {
    const container = document.getElementById('alertsContainer');
    let html = '';

    if (kpis.overdue_loans_count > 0) {
        html += `<div class="alert-card alert-card-danger">
            <i class="bi bi-exclamation-circle fs-5"></i>
            <div><strong>${kpis.overdue_loans_count}</strong> empréstimo(s) em atraso — total: ${APP.formatMoney(kpis.overdue_loans_total)}</div>
        </div>`;
    }
    if (kpis.pending_joias > 0) {
        html += `<div class="alert-card alert-card-warning">
            <i class="bi bi-exclamation-triangle fs-5"></i>
            <div><strong>${kpis.pending_joias}</strong> membro(s) com jóia pendente</div>
        </div>`;
    }
    if (lowMovementMembers && lowMovementMembers.length > 0) {
        html += `<div class="alert-card alert-card-warning">
            <i class="bi bi-person-exclamation fs-5"></i>
            <div><strong>${lowMovementMembers.length}</strong> membro(s) abaixo da movimentação mínima de 50.000 MT</div>
        </div>`;
    }

    container.style.display = 'block';
    container.style.opacity = '1';
    container.innerHTML = html;

    if (alertTimeout) clearTimeout(alertTimeout);

    if (html !== '') {
        alertTimeout = setTimeout(() => {
            $('#alertsContainer').fadeOut(1000, function () {
                $(this).empty().show().css('opacity', '1');
            });
        }, 10000);
    }
}

document.addEventListener('DOMContentLoaded', loadDashboard);
