<?php
/**
 * Footer - Sistema Concilio
 * Bootstrap 5 Puro - Mobile First
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
    </div><!-- /.container-fluid -->
</main><!-- /.main-content -->

<!-- ========== FOOTER ========== -->
<footer class="main-footer">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <strong><i class="fas fa-church text-primary me-1"></i> Iglesia Metodista Libre</strong>
                <span class="text-muted ms-2 d-none d-sm-inline">República Dominicana</span>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <small class="text-muted">Sistema Concilio v2.0 © <?php echo date('Y'); ?></small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle JS (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript personalizado -->
<script src="<?php echo $base_path; ?>../js/app.js"></script>

</body>
</html>
