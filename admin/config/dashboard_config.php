<?php
/**
 * Configuración de Dashboard por Roles
 * Define qué widgets se muestran para cada rol y su disposición
 * Sistema Concilio - AdminLTE
 */

return [
    'super_admin' => [
        'title' => 'Panel Super Administrador',
        'icon' => 'fa-user-shield',
        'welcome_message' => 'Gestión completa del sistema',
        'widgets' => [
            // Fila 1: Estadísticas principales
            [
                'type' => 'stat_card',
                'title' => 'Total Miembros',
                'icon' => 'fa-users',
                'color' => 'info',
                'query' => "SELECT COUNT(*) as total FROM miembros WHERE estado = 'activo'",
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Total Iglesias',
                'icon' => 'fa-church',
                'color' => 'success',
                'query' => "SELECT COUNT(*) as total FROM iglesias WHERE activo = 1",
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Total Distritos',
                'icon' => 'fa-map-marked-alt',
                'color' => 'warning',
                'query' => "SELECT COUNT(*) as total FROM distritos WHERE activo = 1",
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Total Conferencias',
                'icon' => 'fa-globe-americas',
                'color' => 'danger',
                'query' => "SELECT COUNT(*) as total FROM conferencias WHERE activo = 1",
                'col' => 'col-lg-3 col-md-6'
            ],
            
            // Fila 2: Accesos rápidos
            [
                'type' => 'quick_access',
                'title' => 'Accesos Rápidos',
                'icon' => 'fa-bolt',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'conferencias/', 'label' => 'Gestionar Conferencias', 'icon' => 'fa-globe', 'color' => 'primary'],
                    ['url' => 'distritos/', 'label' => 'Gestionar Distritos', 'icon' => 'fa-map-marked-alt', 'color' => 'success'],
                    ['url' => 'iglesias/', 'label' => 'Gestionar Iglesias', 'icon' => 'fa-church', 'color' => 'info'],
                    ['url' => 'miembros/', 'label' => 'Gestionar Miembros', 'icon' => 'fa-users', 'color' => 'warning'],
                    ['url' => 'usuarios/', 'label' => 'Usuarios del Sistema', 'icon' => 'fa-users-cog', 'color' => 'danger'],
                    ['url' => 'ministerios_conf/', 'label' => 'Líderes de Ministerios', 'icon' => 'fa-hands-praying', 'color' => 'secondary'],
                ]
            ],
        ]
    ],
    
    'obispo' => [
        'title' => 'Panel del Obispo',
        'icon' => 'fa-church',
        'welcome_message' => 'Supervisión de Conferencias',
        'widgets' => [
            [
                'type' => 'stat_card',
                'title' => 'Total Conferencias',
                'icon' => 'fa-globe-americas',
                'color' => 'primary',
                'query' => "SELECT COUNT(*) as total FROM conferencias WHERE activo = 1",
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Total Distritos',
                'icon' => 'fa-map-marked-alt',
                'color' => 'success',
                'query' => "SELECT COUNT(*) as total FROM distritos WHERE activo = 1",
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Total Miembros',
                'icon' => 'fa-users',
                'color' => 'info',
                'query' => "SELECT COUNT(*) as total FROM miembros WHERE estado = 'activo'",
                'col' => 'col-lg-4 col-md-12'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión Episcopal',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'conferencias/', 'label' => 'Conferencias', 'icon' => 'fa-globe', 'color' => 'primary'],
                    ['url' => 'distritos/', 'label' => 'Distritos', 'icon' => 'fa-map-marked-alt', 'color' => 'success'],
                    ['url' => 'iglesias/', 'label' => 'Iglesias', 'icon' => 'fa-church', 'color' => 'info'],
                    ['url' => 'miembros/', 'label' => 'Miembros', 'icon' => 'fa-users', 'color' => 'warning'],
                ]
            ],
        ]
    ],
    
    'super_conferencia' => [
        'title' => 'Panel del Superintendente',
        'icon' => 'fa-users-cog',
        'welcome_message' => 'Gestión de Conferencia',
        'widgets' => [
            [
                'type' => 'stat_card',
                'title' => 'Distritos',
                'icon' => 'fa-map-marked-alt',
                'color' => 'primary',
                'query' => "SELECT COUNT(*) as total FROM distritos WHERE conferencia_id = ? AND activo = 1",
                'params' => ['conferencia_id'],
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Iglesias',
                'icon' => 'fa-church',
                'color' => 'success',
                'query' => "SELECT COUNT(*) as total FROM iglesias i INNER JOIN distritos d ON i.distrito_id = d.id WHERE d.conferencia_id = ? AND i.activo = 1",
                'params' => ['conferencia_id'],
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Miembros',
                'icon' => 'fa-users',
                'color' => 'info',
                'query' => "SELECT COUNT(*) as total FROM miembros m INNER JOIN iglesias i ON m.iglesia_id = i.id INNER JOIN distritos d ON i.distrito_id = d.id WHERE d.conferencia_id = ? AND m.estado = 'activo'",
                'params' => ['conferencia_id'],
                'col' => 'col-lg-4 col-md-12'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión de Conferencia',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'distritos/', 'label' => 'Gestionar Distritos', 'icon' => 'fa-map-marked-alt', 'color' => 'primary'],
                    ['url' => 'iglesias/', 'label' => 'Ver Iglesias', 'icon' => 'fa-church', 'color' => 'success'],
                    ['url' => 'miembros/', 'label' => 'Ver Miembros', 'icon' => 'fa-users', 'color' => 'info'],
                    ['url' => 'ministerios_conf/', 'label' => 'Líderes de Ministerios', 'icon' => 'fa-hands-praying', 'color' => 'warning'],
                ]
            ],
        ]
    ],
    
    'super_distrito' => [
        'title' => 'Panel del Supervisor de Distrito',
        'icon' => 'fa-map-marked-alt',
        'welcome_message' => 'Supervisión del Distrito',
        'widgets' => [
            [
                'type' => 'stat_card',
                'title' => 'Iglesias del Distrito',
                'icon' => 'fa-church',
                'color' => 'primary',
                'query' => "SELECT COUNT(*) as total FROM iglesias WHERE distrito_id = ? AND activo = 1",
                'params' => ['distrito_id'],
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Miembros del Distrito',
                'icon' => 'fa-users',
                'color' => 'success',
                'query' => "SELECT COUNT(*) as total FROM miembros m INNER JOIN iglesias i ON m.iglesia_id = i.id WHERE i.distrito_id = ? AND m.estado = 'activo'",
                'params' => ['distrito_id'],
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Pastores',
                'icon' => 'fa-pray',
                'color' => 'info',
                'query' => "SELECT COUNT(DISTINCT u.id) as total FROM usuarios u INNER JOIN iglesias i ON u.iglesia_id = i.id INNER JOIN roles r ON u.rol_id = r.id WHERE i.distrito_id = ? AND r.nombre = 'pastor' AND u.activo = 1",
                'params' => ['distrito_id'],
                'col' => 'col-lg-4 col-md-12'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión del Distrito',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'distritos/mis_iglesias.php', 'label' => 'Mis Iglesias', 'icon' => 'fa-church', 'color' => 'primary'],
                    ['url' => 'miembros/', 'label' => 'Ver Miembros', 'icon' => 'fa-users', 'color' => 'success'],
                    ['url' => 'distritos/reportes.php', 'label' => 'Reportes del Distrito', 'icon' => 'fa-chart-bar', 'color' => 'info'],
                ]
            ],
        ]
    ],
    
    'pastor' => [
        'title' => 'Panel del Pastor',
        'icon' => 'fa-pray',
        'welcome_message' => 'Gestión de la Iglesia',
        'widgets' => [
            [
                'type' => 'stat_card',
                'title' => 'Miembros',
                'icon' => 'fa-users',
                'color' => 'primary',
                'query' => "SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND estado = 'activo'",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Familias',
                'icon' => 'fa-home',
                'color' => 'success',
                'query' => "SELECT COUNT(DISTINCT familia_codigo) as total FROM miembros WHERE iglesia_id = ? AND familia_codigo IS NOT NULL AND estado = 'activo'",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Ministerios',
                'icon' => 'fa-hands-praying',
                'color' => 'info',
                'query' => "SELECT COUNT(DISTINCT ministerio_id) as total FROM miembros WHERE iglesia_id = ? AND ministerio_id IS NOT NULL AND estado = 'activo'",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Usuarios',
                'icon' => 'fa-user-cog',
                'color' => 'warning',
                'query' => "SELECT COUNT(*) as total FROM usuarios WHERE iglesia_id = ? AND activo = 1",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-3 col-md-6'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión Pastoral',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'miembros/', 'label' => 'Gestionar Miembros', 'icon' => 'fa-users', 'color' => 'primary'],
                    ['url' => 'miembros/crear.php', 'label' => 'Registrar Miembro', 'icon' => 'fa-user-plus', 'color' => 'success'],
                    ['url' => 'usuarios_iglesia/', 'label' => 'Usuarios de la Iglesia', 'icon' => 'fa-users-cog', 'color' => 'info'],
                    ['url' => 'finanzas/', 'label' => 'Finanzas', 'icon' => 'fa-money-bill-wave', 'color' => 'warning'],
                ]
            ],
        ]
    ],
    
    'secretaria' => [
        'title' => 'Panel de Secretaría',
        'icon' => 'fa-clipboard-list',
        'welcome_message' => 'Gestión de Registros',
        'widgets' => [
            [
                'type' => 'stat_card',
                'title' => 'Miembros Activos',
                'icon' => 'fa-users',
                'color' => 'primary',
                'query' => "SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND estado = 'activo'",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Familias',
                'icon' => 'fa-home',
                'color' => 'success',
                'query' => "SELECT COUNT(DISTINCT familia_codigo) as total FROM miembros WHERE iglesia_id = ? AND familia_codigo IS NOT NULL AND estado = 'activo'",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Registros este Mes',
                'icon' => 'fa-calendar-plus',
                'color' => 'info',
                'query' => "SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())",
                'params' => ['iglesia_id'],
                'col' => 'col-lg-4 col-md-12'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión de Registros',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'miembros/', 'label' => 'Ver Miembros', 'icon' => 'fa-users', 'color' => 'primary'],
                    ['url' => 'miembros/crear.php', 'label' => 'Registrar Miembro', 'icon' => 'fa-user-plus', 'color' => 'success'],
                    ['url' => 'miembros/carga_masiva.php', 'label' => 'Carga Masiva', 'icon' => 'fa-file-upload', 'color' => 'info'],
                ]
            ],
        ]
    ],
    
    'tesorero' => [
        'title' => 'Panel de Tesorería',
        'icon' => 'fa-money-bill-wave',
        'welcome_message' => 'Gestión Financiera',
        'widgets' => [
            [
                'type' => 'stat_card',
                'title' => 'Ingresos del Mes',
                'icon' => 'fa-arrow-up',
                'color' => 'success',
                'query' => "SELECT COALESCE(SUM(monto), 0) as total FROM finanzas_entradas WHERE iglesia_id = ? AND MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())",
                'params' => ['iglesia_id'],
                'format' => 'currency',
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Egresos del Mes',
                'icon' => 'fa-arrow-down',
                'color' => 'danger',
                'query' => "SELECT COALESCE(SUM(monto), 0) as total FROM finanzas_salidas WHERE iglesia_id = ? AND MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())",
                'params' => ['iglesia_id'],
                'format' => 'currency',
                'col' => 'col-lg-4 col-md-6'
            ],
            [
                'type' => 'stat_card',
                'title' => 'Balance',
                'icon' => 'fa-balance-scale',
                'color' => 'info',
                'query' => "SELECT (COALESCE((SELECT SUM(monto) FROM finanzas_entradas WHERE iglesia_id = ?), 0) - COALESCE((SELECT SUM(monto) FROM finanzas_salidas WHERE iglesia_id = ?), 0)) as total",
                'params' => ['iglesia_id', 'iglesia_id'],
                'format' => 'currency',
                'col' => 'col-lg-4 col-md-12'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión Financiera',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'finanzas/entradas.php', 'label' => 'Registrar Ingreso', 'icon' => 'fa-plus-circle', 'color' => 'success'],
                    ['url' => 'finanzas/salidas.php', 'label' => 'Registrar Egreso', 'icon' => 'fa-minus-circle', 'color' => 'danger'],
                    ['url' => 'finanzas/reportes.php', 'label' => 'Reportes Financieros', 'icon' => 'fa-chart-line', 'color' => 'info'],
                    ['url' => 'finanzas/cierre_mes.php', 'label' => 'Cierre de Mes', 'icon' => 'fa-calendar-check', 'color' => 'warning'],
                ]
            ],
        ]
    ],
    
    'lider_ministerio' => [
        'title' => 'Panel del Líder de Ministerio',
        'icon' => 'fa-hands-praying',
        'welcome_message' => 'Gestión del Ministerio',
        'widgets' => [
            [
                'type' => 'stat_ministerio',
                'col' => 'col-md-12'
            ],
            [
                'type' => 'quick_access',
                'title' => 'Gestión del Ministerio',
                'icon' => 'fa-tasks',
                'col' => 'col-md-12',
                'links' => [
                    ['url' => 'ministerios_conf/miembros_ministerio.php', 'label' => 'Ver Miembros del Ministerio', 'icon' => 'fa-user-friends', 'color' => 'primary'],
                    ['url' => 'ministerios_conf/', 'label' => 'Líderes por Iglesia', 'icon' => 'fa-users', 'color' => 'success'],
                ]
            ],
        ]
    ],
];
