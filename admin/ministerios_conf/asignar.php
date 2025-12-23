<?php
/**
 * Asignar Líder a Ministerio de Conferencia
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
$roles_permitidos = ['super_admin', 'obispo', 'super_conferencia'];
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $roles_permitidos)) {
    header('Location: ../panel_generico.php?error=' . urlencode('Sin permisos'));
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'];
$es_super_admin = ($ROL_NOMBRE === 'super_admin' || $ROL_NOMBRE === 'obispo');

// Obtener parámetros
$conferencia_id = isset($_GET['conferencia']) ? (int)$_GET['conferencia'] : 0;
$ministerio_id = isset($_GET['ministerio']) ? (int)$_GET['ministerio'] : 0;

// Si es super_conferencia, usar su conferencia
if ($ROL_NOMBRE === 'super_conferencia') {
    $conferencia_id = $_SESSION['conferencia_id'] ?? 0;
}

// Validar que exista la conferencia
if ($conferencia_id <= 0) {
    header('Location: index.php?error=' . urlencode('Seleccione una conferencia'));
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM conferencias WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    header('Location: index.php?error=' . urlencode('Conferencia no encontrada'));
    exit;
}

// Obtener ministerio si viene preseleccionado
$ministerio_preseleccionado = null;
if ($ministerio_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM ministerios WHERE id = ? AND activo = 1");
    $stmt->bind_param("i", $ministerio_id);
    $stmt->execute();
    $ministerio_preseleccionado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Obtener todos los ministerios
$ministerios = [];
$result = $conexion->query("SELECT id, nombre FROM ministerios WHERE activo = 1 ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $ministerios[] = $row;
}

// Obtener distritos de la conferencia
$distritos = [];
$stmt = $conexion->prepare("SELECT id, codigo, nombre FROM distritos WHERE conferencia_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $distritos[] = $row;
}
$stmt->close();

// Cargos disponibles
$cargos = ['presidente', 'vicepresidente', 'secretario', 'tesorero', 'vocal'];

$titulo_pagina = "Asignar Líder de Ministerio";
$page_title = $titulo_pagina;
include __DIR__ . '/../includes/header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

<style>
        .card-form { border-top: 4px solid #0d6efd; }
        .info-conferencia { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        #miembro_info { display: none; }
        #miembro_info.show { display: block; }
    </style>

<!-- Content Wrapper -->
<div class="content-wrapper" style="margin-left: 0; padding: 1.5rem;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-user-plus me-2"></i><?php echo $titulo_pagina; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../panel_generico.php">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="index.php?conferencia=<?php echo $conferencia_id; ?>">Líderes Ministerios</a></li>
                                <li class="breadcrumb-item active">Asignar</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Info Conferencia -->
                    <div class="col-12 mb-4">
                        <div class="card info-conferencia">
                            <div class="card-body">
                                <h5><i class="fas fa-globe-americas me-2"></i>Conferencia</h5>
                                <h3 class="mb-0"><?php echo htmlspecialchars($conferencia['codigo'] . ' - ' . $conferencia['nombre']); ?></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <div class="col-lg-8">
                        <div class="card card-form">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-hands-praying me-2"></i>Datos del Líder</h5>
                            </div>
                            <div class="card-body">
                                <form action="guardar.php" method="POST" id="formAsignar">
                                    <input type="hidden" name="conferencia_id" value="<?php echo $conferencia_id; ?>">
                                    
                                    <div class="row g-3">
                                        <!-- Ministerio -->
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-users me-1"></i>Ministerio <span class="text-danger">*</span></label>
                                            <select name="ministerio_id" id="ministerio_id" class="form-select" required>
                                                <option value="">-- Seleccione ministerio --</option>
                                                <?php foreach ($ministerios as $min): ?>
                                                    <option value="<?php echo $min['id']; ?>" <?php echo ($ministerio_id == $min['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($min['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Cargo -->
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-id-badge me-1"></i>Cargo <span class="text-danger">*</span></label>
                                            <select name="cargo" id="cargo" class="form-select" required>
                                                <option value="">-- Seleccione cargo --</option>
                                                <?php foreach ($cargos as $cargo): ?>
                                                    <option value="<?php echo $cargo; ?>"><?php echo ucfirst($cargo); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Distrito -->
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-map-marked-alt me-1"></i>Distrito <span class="text-danger">*</span></label>
                                            <select name="distrito_id" id="distrito_id" class="form-select" required>
                                                <option value="">-- Seleccione distrito --</option>
                                                <?php foreach ($distritos as $dist): ?>
                                                    <option value="<?php echo $dist['id']; ?>">
                                                        <?php echo htmlspecialchars($dist['codigo'] . ' - ' . $dist['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Iglesia -->
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-church me-1"></i>Iglesia <span class="text-danger">*</span></label>
                                            <select name="iglesia_id" id="iglesia_id" class="form-select" required disabled>
                                                <option value="">-- Primero seleccione distrito --</option>
                                            </select>
                                        </div>

                                        <!-- Miembro -->
                                        <div class="col-12">
                                            <label class="form-label"><i class="fas fa-user me-1"></i>Miembro <span class="text-danger">*</span></label>
                                            <select name="miembro_id" id="miembro_id" class="form-select" required disabled>
                                                <option value="">-- Primero seleccione iglesia --</option>
                                            </select>
                                            <small class="text-muted">Buscar por nombre o cédula</small>
                                        </div>

                                        <!-- Info del miembro seleccionado -->
                                        <div class="col-12" id="miembro_info">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6><i class="fas fa-user-check me-2"></i>Miembro Seleccionado</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p class="mb-1"><strong>Cédula:</strong> <span id="info_cedula">-</span></p>
                                                            <p class="mb-1"><strong>Teléfono:</strong> <span id="info_telefono">-</span></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p class="mb-1"><strong>Correo:</strong> <span id="info_correo">-</span></p>
                                                            <p class="mb-0"><strong>Iglesia:</strong> <span id="info_iglesia">-</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fecha inicio -->
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-calendar me-1"></i>Fecha de Inicio <span class="text-danger">*</span></label>
                                            <input type="date" name="fecha_inicio" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>

                                        <!-- Período conferencia -->
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-calendar-alt me-1"></i>Período Conferencia</label>
                                            <input type="text" name="periodo_conferencia" class="form-control" 
                                                   placeholder="Ej: 2024-2027" maxlength="20">
                                        </div>

                                        <!-- Observaciones -->
                                        <div class="col-12">
                                            <label class="form-label"><i class="fas fa-comment me-1"></i>Observaciones</label>
                                            <textarea name="observaciones" class="form-control" rows="2" 
                                                      placeholder="Notas adicionales..."></textarea>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-between">
                                        <a href="index.php?conferencia=<?php echo $conferencia_id; ?>" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Asignar Líder
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información</h6>
                            </div>
                            <div class="card-body">
                                <p class="small">
                                    <strong>Cargos disponibles:</strong>
                                </p>
                                <ul class="small">
                                    <li><strong>Presidente:</strong> Líder principal del ministerio</li>
                                    <li><strong>Vicepresidente:</strong> Segundo al mando</li>
                                    <li><strong>Secretario:</strong> Encargado de actas y comunicaciones</li>
                                    <li><strong>Tesorero:</strong> Encargado de finanzas</li>
                                    <li><strong>Vocal:</strong> Miembro de la directiva</li>
                                </ul>
                                <hr>
                                <p class="small mb-0">
                                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                    Solo puede haber un presidente y un vicepresidente por ministerio en cada conferencia.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

</div><!-- /.content-wrapper -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
        $(document).ready(function() {
            // Cargar iglesias cuando cambie el distrito
            $('#distrito_id').change(function() {
                const distritoId = $(this).val();
                const iglesiaSelect = $('#iglesia_id');
                const miembroSelect = $('#miembro_id');
                
                iglesiaSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
                miembroSelect.html('<option value="">-- Primero seleccione iglesia --</option>').prop('disabled', true);
                $('#miembro_info').removeClass('show');
                
                if (distritoId) {
                    $.ajax({
                        url: '../ajax/ajax_iglesias.php',
                        type: 'GET',
                        data: { distrito_id: distritoId },
                        dataType: 'json',
                        success: function(data) {
                            iglesiaSelect.html('<option value="">-- Seleccione iglesia --</option>');
                            data.forEach(function(iglesia) {
                                iglesiaSelect.append(`<option value="${iglesia.id}">${iglesia.codigo} - ${iglesia.nombre}</option>`);
                            });
                            iglesiaSelect.prop('disabled', false);
                        },
                        error: function() {
                            iglesiaSelect.html('<option value="">Error al cargar</option>');
                        }
                    });
                }
            });

            // Cargar miembros cuando cambie la iglesia
            $('#iglesia_id').change(function() {
                const iglesiaId = $(this).val();
                const miembroSelect = $('#miembro_id');
                
                miembroSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
                $('#miembro_info').removeClass('show');
                
                if (iglesiaId) {
                    $.ajax({
                        url: 'ajax_miembros.php',
                        type: 'GET',
                        data: { iglesia_id: iglesiaId },
                        dataType: 'json',
                        success: function(data) {
                            miembroSelect.html('<option value="">-- Seleccione miembro --</option>');
                            data.forEach(function(m) {
                                miembroSelect.append(`<option value="${m.id}" data-cedula="${m.cedula}" data-telefono="${m.telefono}" data-correo="${m.correo}" data-iglesia="${m.iglesia}">${m.nombre} (${m.cedula})</option>`);
                            });
                            miembroSelect.prop('disabled', false);
                            
                            // Inicializar Select2 para búsqueda
                            miembroSelect.select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Buscar miembro...',
                                allowClear: true
                            });
                        },
                        error: function() {
                            miembroSelect.html('<option value="">Error al cargar</option>');
                        }
                    });
                }
            });

            // Mostrar info del miembro seleccionado
            $(document).on('change', '#miembro_id', function() {
                const selected = $(this).find('option:selected');
                if (selected.val()) {
                    $('#info_cedula').text(selected.data('cedula') || '-');
                    $('#info_telefono').text(selected.data('telefono') || '-');
                    $('#info_correo').text(selected.data('correo') || '-');
                    $('#info_iglesia').text(selected.data('iglesia') || '-');
                    $('#miembro_info').addClass('show');
                } else {
                    $('#miembro_info').removeClass('show');
                }
            });
        });
    </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
