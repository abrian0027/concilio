<?php
declare(strict_types=1);

$page_title = "Carga Masiva de Iglesias";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$mensaje = '';
$tipo_mensaje = '';
$iglesias_creadas = 0;
$errores = [];

// Procesar carga masiva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $distrito_id = (int)$_POST['distrito_id'];
    $lista_iglesias = trim($_POST['lista_iglesias']);
    
    if ($distrito_id <= 0) {
        $mensaje = "Debe seleccionar un distrito.";
        $tipo_mensaje = 'danger';
    } elseif (empty($lista_iglesias)) {
        $mensaje = "Debe ingresar la lista de iglesias.";
        $tipo_mensaje = 'danger';
    } else {
        // Parsear la lista (formato: CODIGO,NOMBRE por línea)
        $lineas = explode("\n", $lista_iglesias);
        
        $conexion->begin_transaction();
        
        try {
            $stmt = $conexion->prepare("INSERT INTO iglesias (distrito_id, codigo, nombre, activo) VALUES (?, ?, ?, 1)");
            
            foreach ($lineas as $num_linea => $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;
                
                // Separar por coma o tabulador
                if (strpos($linea, ',') !== false) {
                    $partes = explode(',', $linea, 2);
                } elseif (strpos($linea, "\t") !== false) {
                    $partes = explode("\t", $linea, 2);
                } else {
                    $errores[] = "Línea " . ($num_linea + 1) . ": Formato inválido - '$linea'";
                    continue;
                }
                
                if (count($partes) < 2) {
                    $errores[] = "Línea " . ($num_linea + 1) . ": Faltan datos - '$linea'";
                    continue;
                }
                
                $codigo = strtoupper(trim($partes[0]));
                $nombre = trim($partes[1]);
                
                if (empty($codigo) || empty($nombre)) {
                    $errores[] = "Línea " . ($num_linea + 1) . ": Código o nombre vacío";
                    continue;
                }
                
                // Verificar si el código ya existe
                $check = $conexion->prepare("SELECT id FROM iglesias WHERE codigo = ?");
                $check->bind_param("s", $codigo);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $errores[] = "Línea " . ($num_linea + 1) . ": Código '$codigo' ya existe";
                    $check->close();
                    continue;
                }
                $check->close();
                
                $stmt->bind_param("iss", $distrito_id, $codigo, $nombre);
                if ($stmt->execute()) {
                    $iglesias_creadas++;
                } else {
                    $errores[] = "Línea " . ($num_linea + 1) . ": Error al insertar - " . $stmt->error;
                }
            }
            
            $stmt->close();
            $conexion->commit();
            
            if ($iglesias_creadas > 0) {
                $mensaje = "Se crearon $iglesias_creadas iglesia(s) exitosamente.";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "No se creó ninguna iglesia.";
                $tipo_mensaje = 'warning';
            }
            
        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}

// Obtener conferencias
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-upload"></i> Carga Masiva de Iglesias</h1>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<?php if (!empty($errores)): ?>
<div class="alert alert-warning">
    <strong><i class="fas fa-exclamation-triangle"></i> Errores encontrados:</strong>
    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
        <?php foreach ($errores as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card" style="max-width: 900px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Cargar Lista de Iglesias</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <!-- Conferencia -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-users"></i> Conferencia <span style="color:red;">*</span>
                </label>
                <select name="conferencia_id" id="conferencia_id" class="form-control" required>
                    <option value="">Seleccione una conferencia</option>
                    <?php while ($conf = $conferencias->fetch_assoc()): ?>
                    <option value="<?php echo $conf['id']; ?>">
                        <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Distrito -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-map-marked-alt"></i> Distrito <span style="color:red;">*</span>
                </label>
                <select name="distrito_id" id="distrito_id" class="form-control" required>
                    <option value="">Primero seleccione una conferencia</option>
                </select>
            </div>

            <!-- Lista de iglesias -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-church"></i> Lista de Iglesias <span style="color:red;">*</span>
                </label>
                <textarea name="lista_iglesias" id="lista_iglesias" class="form-control" 
                          rows="12" required
                          placeholder="Formato: CODIGO,NOMBRE (una iglesia por línea)

Ejemplo:
IMLC-201,IML-San Francisco Central
IMLC-202,IML-Castillo
IMLC-203,IML-Cotui"></textarea>
                <small class="text-muted">
                    <strong>Formato:</strong> CODIGO,NOMBRE (separado por coma o tabulador)<br>
                    Una iglesia por línea. Las líneas vacías se ignoran.
                </small>
            </div>

            <!-- Vista previa -->
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-eye"></i> Vista Previa</label>
                <div id="vista_previa" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; min-height: 100px; max-height: 200px; overflow-y: auto;">
                    <span class="text-muted">Ingrese datos para ver la vista previa...</span>
                </div>
                <small class="text-muted">Total: <span id="total_iglesias">0</span> iglesia(s)</small>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Cargar Iglesias
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Ayuda -->
<div class="card mt-3" style="max-width: 900px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-question-circle"></i> Ayuda</span>
    </div>
    <div class="card-body">
        <h6>Formato aceptado:</h6>
        <pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px;">CODIGO,NOMBRE
IMLC-201,IML-San Francisco Central
IMLC-202,IML-Castillo
IMLC-203,IML-Cotui</pre>
        
        <h6 class="mt-3">También puede copiar desde Excel:</h6>
        <p class="text-muted">Si tiene los datos en Excel con columnas CÓDIGO y NOMBRE, simplemente copie y pegue. El sistema acepta datos separados por tabulador.</p>
    </div>
</div>

<script>
// Cargar distritos al cambiar conferencia
document.getElementById('conferencia_id').addEventListener('change', function() {
    const conferenciaId = this.value;
    const distritoSelect = document.getElementById('distrito_id');
    
    distritoSelect.innerHTML = '<option value="">Cargando...</option>';
    distritoSelect.disabled = true;
    
    if (!conferenciaId) {
        distritoSelect.innerHTML = '<option value="">Primero seleccione una conferencia</option>';
        return;
    }
    
    fetch('../distritos_ajax.php?conferencia_id=' + conferenciaId)
        .then(response => response.json())
        .then(distritos => {
            distritoSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
            
            if (distritos.length === 0) {
                distritoSelect.innerHTML = '<option value="">No hay distritos en esta conferencia</option>';
            } else {
                distritos.forEach(distrito => {
                    const option = document.createElement('option');
                    option.value = distrito.id;
                    option.textContent = distrito.codigo + ' - ' + distrito.nombre;
                    distritoSelect.appendChild(option);
                });
                distritoSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            distritoSelect.innerHTML = '<option value="">Error al cargar distritos</option>';
        });
});

// Vista previa al escribir
document.getElementById('lista_iglesias').addEventListener('input', function() {
    const texto = this.value.trim();
    const vistaPrevia = document.getElementById('vista_previa');
    const totalSpan = document.getElementById('total_iglesias');
    
    if (!texto) {
        vistaPrevia.innerHTML = '<span class="text-muted">Ingrese datos para ver la vista previa...</span>';
        totalSpan.textContent = '0';
        return;
    }
    
    const lineas = texto.split('\n').filter(l => l.trim());
    let html = '<table class="table table-sm" style="margin: 0;"><thead><tr><th>Código</th><th>Nombre</th></tr></thead><tbody>';
    let validas = 0;
    
    lineas.forEach((linea, i) => {
        linea = linea.trim();
        if (!linea) return;
        
        let partes;
        if (linea.includes(',')) {
            partes = linea.split(',');
        } else if (linea.includes('\t')) {
            partes = linea.split('\t');
        } else {
            html += `<tr class="table-danger"><td colspan="2">❌ Formato inválido: ${linea}</td></tr>`;
            return;
        }
        
        if (partes.length >= 2) {
            const codigo = partes[0].trim().toUpperCase();
            const nombre = partes[1].trim();
            html += `<tr><td><code>${codigo}</code></td><td>${nombre}</td></tr>`;
            validas++;
        }
    });
    
    html += '</tbody></table>';
    vistaPrevia.innerHTML = html;
    totalSpan.textContent = validas;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>