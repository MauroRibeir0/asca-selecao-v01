    </div><!-- /.container-fluid -->
</main><!-- /.main-content -->
</div><!-- /.wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- App JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>

<?php if (isset($pageScript)): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $pageScript ?>?v=<?= filemtime(__DIR__ . '/../assets/js/' . $pageScript) ?>"></script>
<?php endif; ?>

<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
    .then(function(reg) { console.log('Service worker registered.', reg); })
    .catch(function(err) { console.error('Service worker registration failed:', err); });
}
window.OneSignal = window.OneSignal || [];
OneSignal.push(function() {
  OneSignal.init({
    appId: "4e86da52-696c-4cd7-8545-a5e755c88162"
  });
});
</script>
</body>
</html>
