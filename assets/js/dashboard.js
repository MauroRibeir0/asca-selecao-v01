/**
 * ASCA Selecção - Dashboard JS
 */

let chartContribVsLoans = null;
let chartDistribution   = null;

// ─────────────────────────────────────────────────────────────────────────────
// Entry point
// ─────────────────────────────────────────────────────────────────────────────
function loadDashboard() {
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>A actualizar...';
    }

    document.getElementById('upcomingDueTable').innerHTML =
        '<tr><td colspan="5" class="p-3">' + APP.skeleton('text', 3) + '</td></tr>';
    document.getElementById('recentActivityList').innerHTML = APP.skeleton('text', 5);

    fetch('../api/dashboard_api.php?_=' + Date.now())
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                APP.showToast(data.message || 'Erro ao carregar dashboard', 'danger');
                return;
            }
            updateKPIs(data.kpis);
            updateCycleStrip(data.cycle_progress, data.kpis);
            updateCharts(data.charts, data.kpis);
            updateUpcomingDue(data.upcoming_due);
            updateRecentActivity(data.recent_activity);
            updateAlerts(data.kpis, data.members_low_movement_list);

            // Stamp last updated time
            const badge = document.getElementById('lastUpdatedBadge');
            if (badge) {
                badge.textContent = 'Actualizado às ' + new Date().toLocaleTimeString('pt-MZ', { hour: '2-digit', minute: '2-digit' });
                badge.style.setProperty('display', 'inline-block', 'important');
            }

            document.querySelectorAll('.kpi-card, .chart-card, .quick-action, .table-card').forEach((el, i) => {
                APP.fadeIn(el, i * 40);
            });
        })
        .catch(() => APP.showToast('Erro de conexão', 'danger'))
        .finally(() => {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Actualizar';
            }
        });
}

// ─────────────────────────────────────────────────────────────────────────────
// Animated counter
// ─────────────────────────────────────────────────────────────────────────────
function animateCounter(el, target, format, duration) {
    if (!el) return;
    duration = duration || 900;
    var start = performance.now();
    function step(now) {
        var progress = Math.min((now - start) / duration, 1);
        progress = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        var current = target * progress;
        el.textContent = format(current);
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = format(target);
    }
    requestAnimationFrame(step);
}

// ─────────────────────────────────────────────────────────────────────────────
// KPI cards
// ─────────────────────────────────────────────────────────────────────────────
function updateKPIs(kpis) {
    animateCounter(document.getElementById('kpiMembers'),   kpis.total_members,       v => Math.round(v).toString());
    animateCounter(document.getElementById('kpiFund'),      kpis.total_fund,           v => APP.formatMoney(v));
    animateCounter(document.getElementById('kpiAvailable'), kpis.capital_available,    v => APP.formatMoney(v));
    animateCounter(document.getElementById('kpiLoaned'),    kpis.total_loaned,         v => APP.formatMoney(v));
    animateCounter(document.getElementById('kpiOverdue'),   kpis.overdue_loans_count,  v => Math.round(v).toString());
    animateCounter(document.getElementById('kpiInterest'),  kpis.total_interest,       v => APP.formatMoney(v));
    animateCounter(document.getElementById('kpiLateFees'),  kpis.total_late_fees,      v => APP.formatMoney(v));
    animateCounter(document.getElementById('kpiRecovery'),  kpis.recovery_rate,        v => v.toFixed(1) + '%');

    // Colour kpiAvailable red if negative
    const availEl = document.getElementById('kpiAvailable');
    if (availEl) {
        const parent = availEl.closest('.kpi-card');
        if (kpis.capital_available < 0 && parent) {
            parent.classList.remove('kpi-success');
            parent.classList.add('kpi-danger');
        }
    }

    // Add subtitle to kpiOverdue: show total amount
    const overdueEl = document.getElementById('kpiOverdue');
    if (overdueEl && kpis.overdue_loans_count > 0) {
        let sub = overdueEl.nextElementSibling;
        if (!sub || !sub.classList.contains('kpi-sub')) {
            sub = document.createElement('div');
            sub.className = 'kpi-sub text-danger';
            sub.style.cssText = 'font-size:.72rem;margin-top:.15rem;';
            overdueEl.after(sub);
        }
        sub.textContent = APP.formatMoney(kpis.overdue_loans_total);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Cycle progress strip + compliance + pending interest
// ─────────────────────────────────────────────────────────────────────────────
function updateCycleStrip(cycleProgress, kpis) {
    const strip = document.getElementById('cycleStrip');
    if (!strip) return;

    // Monthly compliance
    const compLabel = document.getElementById('complianceLabel');
    const compBar   = document.getElementById('complianceBar');
    const total     = kpis.total_members_active || kpis.total_members;
    const paid      = kpis.paid_this_month || 0;
    if (compLabel) {
        compLabel.textContent = paid + '/' + total + ' membros';
        const pct = total > 0 ? Math.round((paid / total) * 100) : 0;
        if (compBar) compBar.style.width = pct + '%';
        if (pct < 50) {
            compLabel.className = 'fw-bold text-danger';
            if (compBar) compBar.className = 'progress-bar bg-danger';
        } else if (pct < 80) {
            compLabel.className = 'fw-bold text-warning';
            if (compBar) compBar.className = 'progress-bar bg-warning';
        }
    }

    // Pending interest
    const piEl = document.getElementById('stripPendingInterest');
    if (piEl) piEl.textContent = APP.formatMoney(kpis.pending_interest || 0);

    // Cycle progress
    if (cycleProgress) {
        const datesLabel    = document.getElementById('cycleDatesLabel');
        const progressBar   = document.getElementById('cycleProgressBar');
        const elapsedLabel  = document.getElementById('cycleElapsedLabel');
        const pctLabel      = document.getElementById('cyclePctLabel');

        const fmt = d => new Date(d).toLocaleDateString('pt-MZ', { day: '2-digit', month: 'short', year: 'numeric' });
        if (datesLabel)   datesLabel.textContent   = fmt(cycleProgress.start_date) + ' → ' + fmt(cycleProgress.end_date);
        if (progressBar)  progressBar.style.width  = cycleProgress.pct + '%';
        if (elapsedLabel) elapsedLabel.textContent  = cycleProgress.elapsed_days + ' de ' + cycleProgress.total_days + ' dias';
        if (pctLabel)     pctLabel.textContent      = cycleProgress.pct + '%';
    }

    strip.style.display = '';
}

// ─────────────────────────────────────────────────────────────────────────────
// Charts
// ─────────────────────────────────────────────────────────────────────────────
function updateCharts(charts, kpis) {
    const ptMonths = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    // ── Bar: Contributions vs Loans (monthly) ──────────────────────────────
    const months = [...new Set([
        ...charts.monthly_contributions.map(r => r.month),
        ...charts.monthly_loans.map(r => r.month),
    ])].sort();

    const contribData = months.map(m => parseFloat((charts.monthly_contributions.find(r => r.month === m) || {}).total || 0));
    const loanData    = months.map(m => parseFloat((charts.monthly_loans.find(r => r.month === m) || {}).total || 0));
    const labels      = months.map(m => { const [, mo] = m.split('-'); return ptMonths[parseInt(mo)] + ' ' + m.slice(2, 4); });

    if (chartContribVsLoans) chartContribVsLoans.destroy();
    const ctx1 = document.getElementById('chartContribVsLoans');
    if (ctx1) {
        chartContribVsLoans = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Contribuições', data: contribData, backgroundColor: 'rgba(5,150,105,.75)', borderRadius: 5 },
                    { label: 'Empréstimos',   data: loanData,    backgroundColor: 'rgba(2,132,199,.75)',  borderRadius: 5 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v) + ' MT' }
                    }
                }
            }
        });
    }

    // ── Doughnut: Fund composition ─────────────────────────────────────────
    // Shows where the TOTAL FUND is: in hand vs lent out vs outstanding interest.
    // Uses activeDebt (outstanding balance, not loan principal) for accurate picture.
    const capAvail    = Math.max(0, kpis.capital_available);
    const activeDebt  = Math.max(0, kpis.total_loaned);       // = outstanding balance
    const pendingInt  = Math.max(0, kpis.pending_interest);

    if (chartDistribution) chartDistribution.destroy();
    const ctx2 = document.getElementById('chartDistribution');
    if (ctx2) {
        chartDistribution = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Capital em Caixa', 'Empréstimos em Aberto', 'Juros Pendentes'],
                datasets: [{
                    data: [capAvail, activeDebt, pendingInt],
                    backgroundColor: ['rgba(5,150,105,.82)', 'rgba(2,132,199,.82)', 'rgba(217,119,6,.82)'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + APP.formatMoney(ctx.parsed)
                        }
                    }
                },
                cutout: '62%'
            }
        });
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Upcoming due table
// ─────────────────────────────────────────────────────────────────────────────
function updateUpcomingDue(items) {
    const tbody  = document.getElementById('upcomingDueTable');
    const countEl = document.getElementById('upcomingCount');

    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">'
            + '<i class="bi bi-check-circle text-success me-1"></i>Sem obrigações pendentes</td></tr>';
        if (countEl) countEl.textContent = '';
        return;
    }

    if (countEl) {
        const overdueN = items.filter(i => i.urgency === 'overdue').length;
        countEl.innerHTML = overdueN > 0
            ? '<span class="badge bg-danger me-1">' + overdueN + ' em atraso</span>'
            : '<span class="text-muted">' + items.length + ' pendente(s)</span>';
    }

    tbody.innerHTML = items.map(l => {
        const isOverdue = l.urgency === 'overdue';

        const typeBadge = l.type === 'loan'
            ? '<span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.68rem;">Empréstimo</span>'
            : '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.68rem;">Mensalidade</span>';

        const urgencyBadge = isOverdue
            ? '<span class="badge bg-danger" style="font-size:.68rem;">Em Atraso</span>'
            : '<span class="badge bg-warning text-dark" style="font-size:.68rem;">Pendente</span>';

        const href     = l.type === 'loan' ? 'emprestimos.php' : 'contribuicoes.php';
        const dateFmt  = new Date(l.due_date + 'T00:00:00').toLocaleDateString('pt-MZ');
        const amtFmt   = APP.formatMoney(parseFloat(l.amount));
        const rowStyle = isOverdue ? ' style="background:#fff5f5;"' : '';
        const typeLabel = l.type === 'loan' ? 'empréstimo' : 'mensalidade';

        const waBtn = l.phone
            ? `<button class="btn btn-sm p-1 lh-1"
                       style="background:#25D366;color:#fff;border:none;border-radius:6px;"
                       title="Enviar lembrete via WhatsApp"
                       onclick="event.stopPropagation(); openWhatsAppModal('${(l.phone||'').replace(/'/g,"\\'")}', '${l.full_name.split(' ')[0].replace(/'/g,"\\'")}', '${l.full_name.replace(/'/g,"\\'")}', '${typeLabel}', '${amtFmt}', '${dateFmt}', '${l.urgency}')">
                   <i class="bi bi-whatsapp"></i>
               </button>`
            : '<span class="text-muted small" title="Telefone não registado">—</span>';

        return `<tr class="cursor-pointer"${rowStyle} onclick="window.location.href='${href}'" title="Abrir ${typeLabel}">
            <td>
                <div class="fw-semibold small">${l.full_name}</div>
                <div class="mt-1">${typeBadge}</div>
            </td>
            <td class="text-end fw-semibold small">${amtFmt}</td>
            <td class="small">${dateFmt}</td>
            <td class="text-center">${urgencyBadge}</td>
            <td class="text-center">${waBtn}</td>
        </tr>`;
    }).join('');
}

// ─────────────────────────────────────────────────────────────────────────────
// WhatsApp modal (editable preview before sending)
// ─────────────────────────────────────────────────────────────────────────────
let _waPhone    = '';
let _waOriginal = '';

function openWhatsAppModal(phone, firstName, fullName, type, amount, dueDate, urgency) {
    // Build formatted message
    let msg;
    if (urgency === 'overdue') {
        msg = `\u{1F534} *ASCA Selec\u00E7\u00E3o \u2014 Pagamento em Atraso*\n\n`
            + `Ol\u00E1 *${firstName}*,\n\n`
            + `Notamos que o seu *${type}* no valor de *${amount}* est\u00E1 em atraso (venceu em ${dueDate}).\n\n`
            + `Por favor, regularize o mais brevemente poss\u00EDvel para evitar multas adicionais.\n\n`
            + `_Obrigado pela sua aten\u00E7\u00E3o.\n\u2014 ASCA Selec\u00E7\u00E3o_`;
    } else {
        msg = `\u{1F4CB} *ASCA Selec\u00E7\u00E3o \u2014 Lembrete de Pagamento*\n\n`
            + `Ol\u00E1 *${firstName}*,\n\n`
            + `Lembramos que o seu *${type}* no valor de *${amount}* vence em *${dueDate}*.\n\n`
            + `Para evitar cobran\u00E7a de multa, efectue o pagamento antes da data de vencimento.\n\n`
            + `_Obrigado pela sua colabora\u00E7\u00E3o.\n\u2014 ASCA Selec\u00E7\u00E3o_`;
    }

    _waPhone    = phone.replace(/\D/g, '');
    if (_waPhone.length === 9) _waPhone = '258' + _waPhone;
    _waOriginal = msg;

    // Populate modal
    document.getElementById('waModalMessage').value = msg;
    const recipLine = document.getElementById('waModalRecipientLine');
    if (recipLine) recipLine.textContent = fullName + (phone ? ' \u2022 ' + phone : '');

    const modal = new bootstrap.Modal(document.getElementById('waPreviewModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    // Send button
    const sendBtn = document.getElementById('waModalSendBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', () => {
            const text = encodeURIComponent(document.getElementById('waModalMessage').value);
            window.open('https://wa.me/' + _waPhone + '?text=' + text, '_blank');
        });
    }

    // Copy button
    const copyBtn = document.getElementById('waModalCopyBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const text = document.getElementById('waModalMessage').value;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    copyBtn.innerHTML = '<i class="bi bi-check me-1"></i>Copiado!';
                    setTimeout(() => { copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copiar'; }, 2000);
                });
            }
        });
    }

    // Reset button
    const resetBtn = document.getElementById('waModalResetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            document.getElementById('waModalMessage').value = _waOriginal;
        });
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// Recent Activity
// ─────────────────────────────────────────────────────────────────────────────
function updateRecentActivity(activities) {
    const container = document.getElementById('recentActivityList');
    if (!activities || activities.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3">Sem actividades recentes</div>';
        return;
    }

    const icons = {
        'login':                 'bi-box-arrow-in-right text-primary',
        'logout':                'bi-box-arrow-right text-secondary',
        'loan_created':          'bi-bank text-info',
        'loan_repaid':           'bi-check-circle text-success',
        'contribution_created':  'bi-cash-coin text-success',
        'member_created':        'bi-person-plus text-primary',
        'cycle_distribution':    'bi-gift text-warning',
        'user_created':          'bi-person-gear text-info',
        'password_reset':        'bi-key text-warning',
    };

    container.innerHTML = activities.map(a => {
        const icon = icons[a.action] || 'bi-circle text-secondary';
        const time = new Date(a.created_at).toLocaleString('pt-MZ', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
        return `<div class="d-flex align-items-start gap-2 mb-2 pb-2 border-bottom">
            <i class="bi ${icon} mt-1 flex-shrink-0"></i>
            <div class="flex-grow-1 min-w-0">
                <div class="small text-truncate">${a.description || a.action}</div>
                <div class="text-muted" style="font-size:.7rem;">${time}</div>
            </div>
        </div>`;
    }).join('');
}

// ─────────────────────────────────────────────────────────────────────────────
// Alerts (persistent — user dismisses manually)
// ─────────────────────────────────────────────────────────────────────────────
function updateAlerts(kpis, lowMovementMembers) {
    const container = document.getElementById('alertsContainer');
    if (!container) return;

    let html = '';

    if (kpis.overdue_loans_count > 0) {
        html += `<div class="alert-card alert-card-danger d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle fs-5 flex-shrink-0"></i>
                <div>
                    <strong>${kpis.overdue_loans_count}</strong> empréstimo(s) em atraso
                    <span class="text-danger fw-semibold ms-1">— ${APP.formatMoney(kpis.overdue_loans_total)}</span>
                </div>
            </div>
            <a href="emprestimos.php" class="btn btn-sm btn-danger" style="white-space:nowrap;">Ver Atrasos</a>
        </div>`;
    }

    if (kpis.pending_joias > 0) {
        html += `<div class="alert-card alert-card-warning d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-gem fs-5 flex-shrink-0"></i>
                <div><strong>${kpis.pending_joias}</strong> membro(s) com jóia pendente</div>
            </div>
            <a href="membros.php" class="btn btn-sm btn-warning text-dark" style="white-space:nowrap;">Ver Membros</a>
        </div>`;
    }

    if (kpis.pending_interest > 0) {
        html += `<div class="alert-card alert-card-warning d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-percent fs-5 flex-shrink-0"></i>
                <div>Juros por cobrar: <strong>${APP.formatMoney(kpis.pending_interest)}</strong></div>
            </div>
            <a href="emprestimos.php" class="btn btn-sm btn-outline-warning" style="white-space:nowrap;">Ver Juros</a>
        </div>`;
    }

    if (lowMovementMembers && lowMovementMembers.length > 0) {
        const names = lowMovementMembers.slice(0, 3).map(m => m.full_name.split(' ')[0]).join(', ');
        const extra = lowMovementMembers.length > 3 ? ` +${lowMovementMembers.length - 3}` : '';
        html += `<div class="alert-card alert-card-warning d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-exclamation fs-5 flex-shrink-0"></i>
                <div>
                    <strong>${lowMovementMembers.length}</strong> membro(s) abaixo do limiar de movimentação
                    <div class="text-muted small">${names}${extra}</div>
                </div>
            </div>
        </div>`;
    }

    container.innerHTML = html;
}

// ─────────────────────────────────────────────────────────────────────────────
// Bootstrap
// ─────────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadDashboard);
