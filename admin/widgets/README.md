# Widgets del Dashboard - Documentación

Esta carpeta contiene los **componentes reutilizables** (widgets) del sistema de dashboard unificado.

---

## 📦 Widgets Disponibles

### 1. **stat_card.php** - Tarjetas Estadísticas

**Función:** `renderStatCard($config, $conexion, $session_data)`

**Descripción:** Renderiza una tarjeta pequeña (small-box) con un valor estadístico obtenido de la base de datos.

**Configuración:**
```php
[
    'type' => 'stat_card',
    'title' => 'Total Miembros',           // Texto descriptivo
    'icon' => 'fa-users',                  // Ícono FontAwesome
    'color' => 'bg-info',                  // Color AdminLTE (bg-info, bg-success, etc.)
    'query' => 'SELECT COUNT(*) FROM...', // Consulta SQL
    'params' => ['iglesia_id'],            // Parámetros desde sesión
    'format' => 'currency',                // Opcional: 'currency' para formato moneda
    'col' => 'col-lg-3 col-md-6'          // Clases de columna Bootstrap
]
```

**Parámetros Soportados:**
- `iglesia_id` - ID de la iglesia del usuario
- `distrito_id` - ID del distrito del usuario
- `conferencia_id` - ID de la conferencia del usuario
- `usuario_id` - ID del usuario actual

**Formatos Soportados:**
- **Sin formato:** Número entero (ej: 1234)
- **`currency`:** Formato bolivianos (ej: Bs. 1.234,56)

**Ejemplo Visual:**
```
┌─────────────────┐
│ 👥 MIEMBROS     │
│                 │
│      245        │
│  Total Activos  │
└─────────────────┘
```

---

### 2. **quick_access.php** - Accesos Rápidos

**Función:** `renderQuickAccess($config)`

**Descripción:** Renderiza una cuadrícula de botones de acceso rápido a diferentes secciones del sistema.

**Configuración:**
```php
[
    'type' => 'quick_access',
    'title' => 'Accesos Rápidos',
    'links' => [
        [
            'url' => 'miembros/index.php',
            'text' => 'Miembros',
            'icon' => 'fa-users',
            'color' => 'btn-primary'
        ],
        // ... más links
    ],
    'col' => 'col-md-12'
]
```

**Colores Disponibles:**
- `btn-primary` - Azul
- `btn-success` - Verde
- `btn-info` - Cyan
- `btn-warning` - Naranja
- `btn-danger` - Rojo
- `btn-secondary` - Gris

**Ejemplo Visual:**
```
┌─────────────────────────────────────────┐
│  🚀 ACCESOS RÁPIDOS                     │
├─────────────────────────────────────────┤
│  [👥 Miembros]    [⛪ Iglesias]         │
│  [📊 Reportes]    [⚙️ Configuración]    │
└─────────────────────────────────────────┘
```

---

### 3. **stat_ministerio.php** - Estadísticas de Ministerio

**Función:** `renderStatMinisterio($config, $conexion, $session_data)`

**Descripción:** Widget especial para **líderes de ministerio** que muestra estadísticas completas del ministerio incluyendo cobertura en conferencias con barra de progreso.

**Configuración:**
```php
[
    'type' => 'stat_ministerio',
    'col' => 'col-md-12'
]
```

**Datos que Muestra:**
- Nombre del ministerio
- Total de miembros
- Miembros activos
- Porcentaje de cobertura en conferencias
- Barra de progreso visual
- Botón de acceso a detalles

**Consultas Internas:**
1. Obtiene el ministerio del líder por cédula
2. Cuenta total de miembros del ministerio
3. Cuenta miembros activos
4. Calcula cobertura: (conferencias con ministerio / total conferencias) * 100

**Ejemplo Visual:**
```
┌─────────────────────────────────────────┐
│  📊 MINISTERIO JUVENIL                  │
├─────────────────────────────────────────┤
│  👥 Miembros: 156 (142 activos)         │
│                                         │
│  🗺️ Cobertura en Conferencias:         │
│  ████████████████░░░░░░  65.5%         │
│                                         │
│  [📋 Ver Detalles Completos]            │
└─────────────────────────────────────────┘
```

---

## 🔧 Crear un Nuevo Widget

### Paso 1: Crear el Archivo

Crea un nuevo archivo en `admin/widgets/`:

```php
<?php
/**
 * Widget: Mi Nuevo Widget
 * Descripción: Lo que hace este widget
 */

function renderMiNuevoWidget($config, $conexion, $session_data) {
    // Extraer configuración
    $title = $config['title'] ?? 'Sin título';
    $col = $config['col'] ?? 'col-md-12';
    
    // Tu lógica aquí
    // ...
    
    // Renderizar HTML
    ?>
    <div class="<?php echo $col; ?>">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $title; ?></h3>
            </div>
            <div class="card-body">
                <!-- Tu contenido -->
            </div>
        </div>
    </div>
    <?php
}
?>
```

### Paso 2: Agregar a dashboard.php

En `admin/dashboard.php` línea ~80, agregar el case:

```php
case 'mi_nuevo_widget':
    require_once __DIR__ . '/widgets/mi_nuevo_widget.php';
    renderMiNuevoWidget($widget, $conexion, $session_data);
    break;
```

### Paso 3: Agregar a Configuración

En `admin/config/dashboard_config.php`, agregar el widget al rol deseado:

```php
'super_admin' => [
    // ...
    'widgets' => [
        // ... otros widgets
        [
            'type' => 'mi_nuevo_widget',
            'title' => 'Mi Widget Personalizado',
            'col' => 'col-md-6',
            // ... más config según necesites
        ]
    ]
]
```

---

## 🎨 Guía de Estilos

### Colores AdminLTE v4

```css
/* Backgrounds para small-box */
.bg-primary   /* Azul #007bff */
.bg-success   /* Verde #28a745 */
.bg-info      /* Cyan #17a2b8 */
.bg-warning   /* Naranja #fd7e14 */
.bg-danger    /* Rojo #dc3545 */
.bg-secondary /* Gris #6c757d */
.bg-dark      /* Negro #343a40 */

/* Backgrounds con gradiente */
.bg-gradient-primary
.bg-gradient-success
.bg-gradient-info
```

### Estructura de Card

```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-icon me-2"></i>Título
        </h3>
    </div>
    <div class="card-body">
        <!-- Contenido -->
    </div>
    <div class="card-footer">
        <!-- Footer opcional -->
    </div>
</div>
```

### Estructura de Small-Box

```html
<div class="small-box bg-info">
    <div class="inner">
        <h3>150</h3>
        <p>Descripción</p>
    </div>
    <div class="icon">
        <i class="fas fa-shopping-cart"></i>
    </div>
</div>
```

---

## 🔒 Seguridad en Widgets

### Siempre Usar Prepared Statements

```php
// ❌ MAL - Vulnerable a SQL Injection
$query = "SELECT * FROM tabla WHERE id = " . $session_data['usuario_id'];

// ✅ BIEN - Prepared Statement
$stmt = $conexion->prepare("SELECT * FROM tabla WHERE id = ?");
$stmt->bind_param("i", $session_data['usuario_id']);
$stmt->execute();
```

### Escapar Salida HTML

```php
// ❌ MAL - XSS Vulnerability
echo "<p>" . $nombre . "</p>";

// ✅ BIEN - Escapado
echo "<p>" . htmlspecialchars($nombre) . "</p>";
```

### Validar Permisos

```php
// Verificar que el usuario tiene acceso a los datos
if ($session_data['iglesia_id'] != $iglesia_solicitada) {
    echo "Sin permisos";
    return;
}
```

---

## 📊 Performance

### Optimización de Consultas

```php
// ❌ MAL - Múltiples consultas
$total = getTotal($conexion);
$activos = getActivos($conexion);
$inactivos = getInactivos($conexion);

// ✅ BIEN - Una consulta
$query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
    FROM tabla
    WHERE iglesia_id = ?
";
```

### Caché de Resultados (Opcional)

```php
// Para widgets que no cambian frecuentemente
$cache_key = "stats_iglesia_" . $session_data['iglesia_id'];
$cache_time = 300; // 5 minutos

if (isset($_SESSION[$cache_key]) && 
    $_SESSION[$cache_key]['time'] > time() - $cache_time) {
    $stats = $_SESSION[$cache_key]['data'];
} else {
    // Consultar DB
    $stats = consultarEstadisticas($conexion);
    $_SESSION[$cache_key] = [
        'data' => $stats,
        'time' => time()
    ];
}
```

---

## 🧪 Testing de Widgets

### Checklist de Pruebas

- [ ] Widget se renderiza correctamente
- [ ] Consultas SQL funcionan sin errores
- [ ] Parámetros de sesión se usan correctamente
- [ ] No hay warnings/notices de PHP
- [ ] Responsive (mobile, tablet, desktop)
- [ ] Iconos cargan correctamente
- [ ] Colores aplicados correctamente
- [ ] Links funcionan correctamente
- [ ] Sin vulnerabilidades XSS/SQLi

### Debugging

```php
// Agregar al inicio del widget para debug:
if ($_SESSION['rol_nombre'] == 'super_admin') {
    echo "<pre>";
    var_dump($config);
    var_dump($session_data);
    echo "</pre>";
}
```

---

## 📚 Recursos Adicionales

- [AdminLTE v4 Docs](https://adminlte.io/docs/4.0/)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)
- [FontAwesome Icons](https://fontawesome.com/icons)
- [PHP Prepared Statements](https://www.php.net/manual/es/mysqli.quickstart.prepared-statements.php)

---

**Última actualización:** 2025-01-15  
**Mantenedor:** Sistema Concilio Team
