<?php
/**
 * Footer - Sistema Concilio
 * Bootstrap 5 Puro
 */

// Calcular rutas relativas
$script_path = $_SERVER['SCRIPT_NAME'];
$admin_pos = strpos($script_path, '/admin/');
if ($admin_pos !== false) {
    $after_admin = substr($script_path, $admin_pos + 7);
    $depth = substr_count($after_admin, '/');
} else {
    $depth = 0;
}
$base_path = str_repeat('../', $depth);
?>
    </div><!-- /.content-wrapper -->
</main><!-- /.main-content -->

<!-- ========== FOOTER ========== -->
<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-left">
            <strong><i class="fas fa-church text-primary me-1"></i> Iglesia Metodista Libre</strong>
            <span class="d-none d-sm-inline text-muted ms-2">República Dominicana</span>
        </div>
        <div class="footer-right">
            <small class="text-muted">Sistema Concilio v2.0 © <?php echo date('Y'); ?></small>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript personalizado -->
<script src="<?php echo $base_path; ?>../js/app.js"></script>

</body>
</html>
