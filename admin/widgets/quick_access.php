<?php
/**
 * Widget: Accesos Rápidos
 * Muestra botones de acceso rápido a diferentes secciones
 */

function renderQuickAccess($config) {
    $title = $config['title'] ?? 'Accesos Rápidos';
    $icon = $config['icon'] ?? 'fa-bolt';
    $links = $config['links'] ?? [];
    $col = $config['col'] ?? 'col-md-12';
    ?>
    
    <div class="<?php echo $col; ?>">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas <?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($title); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($links as $link): ?>
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" class="btn btn-outline-<?php echo $link['color'] ?? 'primary'; ?> w-100 d-flex flex-column align-items-center py-3">
                                <i class="fas <?php echo $link['icon'] ?? 'fa-link'; ?> fa-2x mb-2"></i>
                                <span class="small text-center"><?php echo htmlspecialchars($link['label']); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
}
