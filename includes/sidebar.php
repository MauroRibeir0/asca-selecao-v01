<?php
/**
 * ASCA Selecção - Sidebar Navigation
 */
$isMember = hasRole(ROLE_MEMBER);
?>
<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-inner">

        <?php if ($isMember): ?>
        <!-- ══════ MEMBER PORTAL ══════ -->
        <div class="sidebar-section-title">Meu Espaço</div>
        <ul class="sidebar-nav">
            <li class="<?= $currentPage === 'meu_resumo' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/member/meu_resumo.php">
                    <span class="nav-icon"><i class="bi bi-house-door"></i></span>
                    <span>Meu Resumo</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'minhas_contribuicoes' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/member/minhas_contribuicoes.php">
                    <span class="nav-icon"><i class="bi bi-cash-coin"></i></span>
                    <span>Minhas Contribuições</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'meus_emprestimos' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/member/meus_emprestimos.php">
                    <span class="nav-icon"><i class="bi bi-bank"></i></span>
                    <span>Meus Empréstimos</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'meu_extracto' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/member/meu_extracto.php">
                    <span class="nav-icon"><i class="bi bi-file-earmark-text"></i></span>
                    <span>Meu Extracto</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'meu_perfil' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/member/meu_perfil.php">
                    <span class="nav-icon"><i class="bi bi-person-gear"></i></span>
                    <span>Meu Perfil</span>
                </a>
            </li>
        </ul>

        <?php else: ?>
        <!-- ══════ ADMIN / USER ══════ -->
        <div class="sidebar-section-title">Menu Principal</div>
        <ul class="sidebar-nav">
            <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/dashboard.php">
                    <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'membros' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/membros.php">
                    <span class="nav-icon"><i class="bi bi-people"></i></span>
                    <span>Membros</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'contribuicoes' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/contribuicoes.php">
                    <span class="nav-icon"><i class="bi bi-cash-coin"></i></span>
                    <span>Contribuições</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'emprestimos' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/emprestimos.php">
                    <span class="nav-icon"><i class="bi bi-bank"></i></span>
                    <span>Empréstimos</span>
                </a>
            </li>
            <li class="<?= $currentPage === 'relatorios' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/relatorios.php">
                    <span class="nav-icon"><i class="bi bi-file-earmark-bar-graph"></i></span>
                    <span>Relatórios</span>
                </a>
            </li>
        </ul>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section-title">Administração</div>
        <ul class="sidebar-nav">
            <li class="<?= $currentPage === 'definicoes' ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/pages/definicoes.php">
                    <span class="nav-icon"><i class="bi bi-gear"></i></span>
                    <span>Definições</span>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Sidebar footer -->
    <div class="sidebar-footer">
        <small class="text-muted" style="font-size:.7rem;">v<?= APP_VERSION ?></small>
    </div>
</nav>

<!-- Sidebar backdrop (mobile) -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Main Content Wrapper -->
<main class="main-content" id="mainContent">
    <div class="container-fluid py-4">
        <?= renderFlashMessages() ?>
