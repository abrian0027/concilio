<?php
declare(strict_types=1);

$page_title = "Gestión de Iglesias";
require_once __DIR__ . '/../includes/header.php';

// Super Admin o Superintendente de Conferencia
if (!in_array($ROL_NOMBRE, ['super_admin', 'super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Filtrar por conferencia si es superintendente
$filtro_conferencia = null;
$es_super_conferencia = ($ROL_NOMBRE === 'super_conferencia');
if ($es_super_conferencia && isset($_SESSION['conferencia_id'])) {
    $filtro_conferencia = (int)$_SESSION['conferencia_id'];
}

// Obtener todas las iglesias con conferencia y distrito
try {
    $sql = "SELECT i.*, 
                   d.nombre AS distrito_nombre, d.codigo AS distrito_codigo,
                   c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
            FROM iglesias i
            INNER JOIN distritos d ON i.distrito_id = d.id
            INNER JOIN conferencias c ON d.conferencia_id = c.id";
    
    if ($filtro_conferencia) {
        $sql .= " WHERE d.conferencia_id = " . $filtro_conferencia;
    }
    
    $sql .= " ORDER BY c.nombre, d.nombre, i.codigo";
    $resultado = $conexion->query($sql);
} catch (Exception $e) {
    error_log("Error al obtener iglesias: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error al cargar las iglesias.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-church"></i> Gestión de Iglesias</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        $success = htmlspecialchars($_GET['success']);
        if ($success === 'creada') echo "Iglesia creada exitosamente.";
        if ($success === 'editada') echo "Iglesia actualizada exitosamente.";
        if ($success === 'eliminada') echo "Iglesia eliminada exitosamente.";
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <span class="card-title mb-2 mb-md-0"><i class="fas fa-list"></i> Listado de Iglesias</span>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" id="buscador-iglesias" class="form-control form-control-sm" placeholder="Buscar iglesia..." style="min-width:180px;max-width:220px;">
                <a href="crear.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Iglesia
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Conferencia</th>
                            <th>Distrito</th>
                            <th>Código Iglesia</th>
                            <th>Nombre Iglesia</th>
                            <th>Pastor</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($igl = $resultado->fetch_assoc()): ?>
                            <tr class="fila-iglesia">
                                <td data-label="Conferencia">
                                    <strong style="color: #2c5aa0; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($igl['conferencia_codigo']); ?>
                                    </strong>
                                    <br>
                                    <small style="color: #7f8c8d;">
                                        <?php echo htmlspecialchars($igl['conferencia_nombre']); ?>
                                    </small>
                                </td>
                                <td data-label="Distrito">
                                    <strong style="color: #27ae60; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($igl['distrito_codigo']); ?>
                                    </strong>
                                    <br>
                                    <small style="color: #7f8c8d;">
                                        <?php echo htmlspecialchars($igl['distrito_nombre']); ?>
                                    </small>
                                </td>
                                <td data-label="Código Iglesia">
                                    <strong><?php echo htmlspecialchars($igl['codigo']); ?></strong>
                                </td>
                                <td data-label="Nombre">
                                    <?php echo htmlspecialchars($igl['nombre']); ?>
                                </td>
                                <td data-label="Pastor">
                                    <?php echo htmlspecialchars($igl['pastor_nombre'] ?? '-'); ?>
                                </td>
                                <td data-label="Dirección">
                                    <?php echo htmlspecialchars($igl['direccion'] ?? '-'); ?>
                                </td>
                                <td data-label="Teléfono">
                                    <?php echo htmlspecialchars($igl['telefono'] ?? '-'); ?>
                                </td>
                                <td data-label="Estado">
                                    <?php if ($igl['activo'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check-circle"></i> Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                            <i class="fas fa-times-circle"></i> Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <button type="button" 
                                            class="btn btn-sm btn-info btn-resumen w-100 mb-1"
                                            data-id="<?php echo $igl['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($igl['nombre']); ?>"
                                            title="Ver Resumen">
                                        <i class="fas fa-chart-pie"></i> Resumen
                                    </button>
                                    <?php if ($ROL_NOMBRE === 'super_admin'): ?>
                                    <a href="editar.php?id=<?php echo $igl['id']; ?>" 
                                       class="btn btn-sm btn-warning w-100 mb-1"
                                       title="Editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $igl['id']; ?>" 
                                       class="btn btn-sm btn-danger w-100"
                                       title="Eliminar"
                                       onclick="return confirm('¿Está seguro de eliminar esta iglesia?');">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No hay iglesias registradas. 
                <a href="crear.php">Crear la primera iglesia</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Resumen de Iglesia -->
<div class="modal fade" id="modalResumen" tabindex="-1" aria-labelledby="modalResumenLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #2c5aa0 0%, #1a3d6e 100%); color: white;">
                <h5 class="modal-title" id="modalResumenLabel">
                    <i class="fas fa-church me-2"></i>
                    <span id="modal-iglesia-nombre">Resumen de Iglesia</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando información...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos del Modal Resumen */
.resumen-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}
.pastor-card {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
}
.pastor-foto {
    width: 90px;
    height: 120px;
    border-radius: 50px / 30px;
    object-fit: cover;
    border: 3px solid #2c5aa0;
}
.pastor-foto-placeholder {
    width: 90px;
    height: 120px;
    border-radius: 50px / 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
}
.pastor-info h6 {
    margin: 0 0 5px 0;
    font-weight: 600;
    color: #2c5aa0;
}
.pastor-info small {
    color: #6c757d;
}

/* Estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.stat-box {
    text-align: center;
    padding: 12px 8px;
    border-radius: 10px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}
.stat-box.total {
    background: linear-gradient(135deg, #2c5aa0 0%, #1a3d6e 100%);
    color: white;
}
.stat-box .number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}
.stat-box .label {
    font-size: 0.75rem;
    margin-top: 5px;
    opacity: 0.8;
}
.stat-box.success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
.stat-box.warning { background: #fff3cd; color: #856404; border-color: #ffeeba; }
.stat-box.info { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

/* Ministerios */
.ministerios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.ministerio-badge {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    border-radius: 8px;
    background: #e9ecef;
    font-size: 0.85rem;
}
.ministerio-badge .nombre {
    font-weight: 500;
}
.ministerio-badge .cantidad {
    background: #2c5aa0;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Junta */
.junta-table {
    width: 100%;
    font-size: 0.9rem;
}
.junta-table th {
    background: #f8f9fa;
    padding: 8px 12px;
    font-weight: 600;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
}
.junta-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #e9ecef;
}
.junta-table tr:hover {
    background: #f8f9fa;
}

/* Secciones */
.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-title i {
    color: #2c5aa0;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .ministerios-grid {
        grid-template-columns: 1fr;
    }
    .pastor-card {
        flex-direction: column;
        text-align: center;
        padding: 10px;
    }
    .stat-box .number { font-size: 1.1rem; }
    .stat-box .label { font-size: 0.7rem; }
    .modal-dialog {
        max-width: 98vw;
        margin: 0.5rem auto;
    }
    .modal-content {
        border-radius: 10px;
        padding: 0.5rem;
    }
    .junta-table, .junta-table th, .junta-table td {
        font-size: 0.85rem;
    }
    .btn-resumen, .btn-warning, .btn-danger {
        font-size: 0.95rem;
        padding: 0.5rem 0.7rem;
    }
}
@media (max-width: 480px) {
    .modal-content {
        padding: 0.2rem;
    }
    .stats-grid, .ministerios-grid {
        grid-template-columns: 1fr;
    }
    .junta-table, .junta-table th, .junta-table td {
        font-size: 0.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Buscador de iglesias ---
    const buscador = document.getElementById('buscador-iglesias');
    if (buscador) {
        buscador.addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            document.querySelectorAll('.fila-iglesia').forEach(function(fila) {
                const contenido = fila.textContent.toLowerCase();
                fila.style.display = contenido.includes(texto) ? '' : 'none';
            });
        });
    }
    // Delegación de eventos para botones de resumen
    document.querySelectorAll('.btn-resumen').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            // Abrir modal
            const modalEl = document.getElementById('modalResumen');
            const modal = new bootstrap.Modal(modalEl);
            document.getElementById('modal-iglesia-nombre').textContent = nombre;
            document.getElementById('modal-body-content').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando información...</p>
                </div>
            `;
            modal.show();
            // Cargar datos via AJAX
            fetch('ajax_resumen.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderResumen(data);
                    } else {
                        document.getElementById('modal-body-content').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.error || 'Error al cargar'}
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    document.getElementById('modal-body-content').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Error de conexión
                        </div>
                    `;
                });
        });
    });
    
    function renderResumen(data) {
        const igl = data.iglesia;
        const pastor = data.pastor;
        const stats = data.estadisticas;
        const ministerios = data.ministerios;
        const junta = data.junta;
        
        // Foto del pastor
        let fotoHtml = '';
        if (pastor.foto && pastor.foto !== null && pastor.foto !== '') {
            // Validar si la ruta es relativa y no contiene 'http'
            let fotoSrc = pastor.foto;
            if (!fotoSrc.startsWith('http') && !fotoSrc.startsWith('/')) {
                fotoSrc = fotoSrc.replace('..', ''); // Eliminar los .. si existen
                fotoSrc = 'uploads/miembros/' + fotoSrc.replace('uploads/miembros/', '');
            }
            fotoHtml = `<img src="${fotoSrc}" alt="Pastor" class="pastor-foto">`;
        } else {
            fotoHtml = `<div class="pastor-foto-placeholder"><i class="fas fa-user"></i></div>`;
        }
        
        // Ministerios HTML
        let ministeriosHtml = '';
        ministerios.forEach(m => {
            ministeriosHtml += `
                <div class="ministerio-badge">
                    <span class="nombre">${m.nombre}</span>
                    <span class="cantidad">${m.cantidad}</span>
                </div>
            `;
        });
        
        // Junta HTML
        let juntaHtml = '';
        if (junta.length > 0) {
            juntaHtml = `
                <table class="junta-table">
                    <thead>
                        <tr>
                            <th>Cargo</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            junta.forEach(j => {
                juntaHtml += `
                    <tr>
                        <td><strong>${j.cargo}</strong></td>
                        <td>${j.nombre}</td>
                        <td>${j.telefono}</td>
                    </tr>
                `;
            });
            juntaHtml += '</tbody></table>';
        } else {
            juntaHtml = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> No hay junta administrativa registrada</div>';
        }
        
        // Construir contenido completo
        const html = `
            <!-- Encabezado -->
            <div class="resumen-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1" style="color: #2c5aa0;">
                            <i class="fas fa-church me-2"></i>${igl.codigo} - ${igl.nombre}
                        </h5>
                        <small class="text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> ${igl.direccion}
                        </small>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <span class="badge ${igl.activo ? 'bg-success' : 'bg-danger'} px-3 py-2">
                            ${igl.activo ? 'Iglesia Activa' : 'Iglesia Inactiva'}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Pastor -->
            <div class="section-title">
                <i class="fas fa-user-tie"></i> Pastor Asignado
            </div>
            <div class="pastor-card">
                ${fotoHtml}
                <div class="pastor-info">
                    <h6>${pastor.nombre}</h6>
                    <small><i class="fas fa-phone me-1"></i> ${pastor.telefono}</small>
                </div>
            </div>
            
            <!-- Estadísticas de Membresía -->
            <div class="section-title">
                <i class="fas fa-users"></i> Estadísticas de Membresía
            </div>
            <div class="stats-grid">
                <div class="stat-box total">
                    <div class="number">${stats.total_miembros}</div>
                    <div class="label">Total Miembros</div>
                </div>
                <div class="stat-box success">
                    <div class="number">${stats.en_plena}</div>
                    <div class="label">En Plena</div>
                </div>
                <div class="stat-box warning">
                    <div class="number">${stats.en_preparacion}</div>
                    <div class="label">Preparación</div>
                </div>
                <div class="stat-box info">
                    <div class="number">${stats.menores}</div>
                    <div class="label">Menores</div>
                </div>
                <div class="stat-box" style="background: #e3f2fd; color: #1565c0;">
                    <div class="number">${stats.hombres}</div>
                    <div class="label">Hombres</div>
                </div>
                <div class="stat-box" style="background: #fce4ec; color: #c2185b;">
                    <div class="number">${stats.mujeres}</div>
                    <div class="label">Mujeres</div>
                </div>
            </div>
            
            <!-- Ministerios -->
            <div class="section-title">
                <i class="fas fa-hands-helping"></i> Miembros por Ministerio
            </div>
            <div class="ministerios-grid">
                ${ministeriosHtml}
            </div>
            
            <!-- Junta Administrativa -->
            <div class="section-title">
                <i class="fas fa-sitemap"></i> Junta Administrativa Local
            </div>
            ${juntaHtml}
        `;
        
        document.getElementById('modal-body-content').innerHTML = html;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>