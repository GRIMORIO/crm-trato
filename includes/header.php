<?php
/**
 * header.php — Cabecera global y navegación lateral del CRM B2B TIPS
 */

$current_page = basename($_SERVER['PHP_SELF']);

// Título por página — se usa tanto en <title> (pestaña del navegador) como en el <h1> de la barra superior.
$page_titles = [
    'panel.php'        => 'Panel de Ventas',
    'pipeline.php'     => 'Pipeline de Ventas',
    'accounts.php'     => 'Directorio de Empresas y Contactos',
    'account.php'      => 'Ficha de Empresa',
    'activities.php'   => 'Planificador de Actividades',
    'reports.php'      => 'Informes y Rendimiento de Ventas',
    'analytics.php'    => 'Analítica del Pipeline — Conversión y Velocidad',
    'settings.php'     => 'Configuración',
    'users.php'        => 'Usuarios',
    'form_builder.php' => 'Constructor de Formularios de Leads',
    'chat_console.php' => 'Consola de Chats en Vivo',
    'email_inbox.php'  => 'Bandeja de Correo',
    'docs.php'         => 'Documentación y SOP Comercial',
    'profile.php'      => 'Mi Perfil',
];
$page_title = $page_titles[$current_page] ?? 'Panel de Ventas';

require_once __DIR__ . '/branding.php';
$brand = get_brand_settings($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title><?php echo htmlspecialchars($page_title); ?> · <?php echo htmlspecialchars($brand['name']); ?></title>
    <meta name="description" content="Herramienta interna de gestión comercial B2B de TIPS. Acceso restringido a usuarios autorizados.">
    <link rel="icon" type="image/svg+xml" href="assets/image/favicon.svg">
    <link rel="alternate icon" type="image/png" href="assets/image/log_azul.png">
    <link rel="apple-touch-icon" href="assets/image/log_azul.png">
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <!-- Chart.js (para reportes) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Rutas de logo personalizadas por tema (Configuración → Marca), con los originales de TIPS como fallback.
        const BRAND_LOGO_DARK = <?php echo json_encode($brand['logo_dark']); ?>;
        const BRAND_LOGO_LIGHT = <?php echo json_encode($brand['logo_light']); ?>;

        // Aplicar tema guardado inmediatamente para evitar flashes
        const savedTheme = localStorage.getItem('crm-theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);

        window.addEventListener('DOMContentLoaded', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                icon.setAttribute('data-lucide', currentTheme === 'dark' ? 'sun' : 'moon');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            const logo = document.querySelector('.sidebar-brand img');
            if (logo) {
                logo.src = (currentTheme === 'light') ? BRAND_LOGO_LIGHT : BRAND_LOGO_DARK;
            }
        });

        function toggleCRMTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('crm-theme', newTheme);

            const icon = document.getElementById('theme-toggle-icon');
            if (icon) {
                icon.setAttribute('data-lucide', newTheme === 'dark' ? 'sun' : 'moon');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            const logo = document.querySelector('.sidebar-brand img');
            if (logo) {
                logo.src = (newTheme === 'light') ? BRAND_LOGO_LIGHT : BRAND_LOGO_DARK;
            }
        }
    </script>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside>
        <div class="sidebar-brand">
            <a href="panel.php" style="display: flex; align-items: center; gap: 0.6rem; width: 100%; justify-content: center;">
                <img src="<?php echo htmlspecialchars($brand['logo_dark']); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" style="max-height: 40px; flex-shrink: 0; object-fit: contain;">
                <span style="color: var(--text-main); font-size: 1.1rem; font-weight: 700; letter-spacing: 0.02em; white-space: nowrap;"><?php echo htmlspecialchars($brand['name']); ?></span>
            </a>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="panel.php" class="sidebar-link <?php echo $current_page == 'panel.php' ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="pipeline.php" class="sidebar-link <?php echo $current_page == 'pipeline.php' ? 'active' : ''; ?>">
                    <i data-lucide="kanban-square"></i>
                    <span>Pipeline Ventas</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="accounts.php" class="sidebar-link <?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>">
                    <i data-lucide="building-2"></i>
                    <span>Directorio B2B</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="activities.php" class="sidebar-link <?php echo $current_page == 'activities.php' ? 'active' : ''; ?>">
                    <i data-lucide="calendar-check"></i>
                    <span>Actividades</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i data-lucide="bar-chart-3"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <?php if (function_exists('is_admin') && is_admin()): ?>
            <li class="sidebar-item">
                <a href="analytics.php" class="sidebar-link <?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
                    <i data-lucide="git-branch"></i>
                    <span>Analítica del Pipeline</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-item">
                <a href="form_builder.php" class="sidebar-link <?php echo $current_page == 'form_builder.php' ? 'active' : ''; ?>">
                    <i data-lucide="form-input"></i>
                    <span>Formularios B2B</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="chat_console.php" class="sidebar-link <?php echo $current_page == 'chat_console.php' ? 'active' : ''; ?>">
                    <i data-lucide="message-square"></i>
                    <span>Consola Chat</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="email_inbox.php" class="sidebar-link <?php echo $current_page == 'email_inbox.php' ? 'active' : ''; ?>">
                    <i data-lucide="mail"></i>
                    <span>Bandeja Correo</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="docs.php" class="sidebar-link <?php echo $current_page == 'docs.php' ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Documentación</span>
                </a>
            </li>
            <?php if (function_exists('is_admin') && is_admin()): ?>
            <li class="sidebar-item">
                <a href="users.php" class="sidebar-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-item">
                <a href="settings.php" class="sidebar-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <p style="color:var(--text-main); font-weight:600;">
                    👤 <a href="profile.php" style="color:inherit; text-decoration:none;" title="Ver Perfil y Seguridad"><span id="sidebar-user-name"><?php echo htmlspecialchars(current_user_name()); ?></span></a>
                </p>
                <p>
                    <a href="profile.php" style="color:var(--color-primary); text-decoration:none; font-size:0.8rem; margin-right:0.5rem;">Mi Perfil</a> | 
                    <a href="logout.php" style="color:var(--color-error); text-decoration:none; font-size:0.8rem;">Salir</a>
                </p>
            <?php endif; ?>
            <p style="margin-top:0.5rem;"><?php echo htmlspecialchars($brand['name']); ?> v1.0</p>
            <p>© 2026 E-commerce Dept</p>
        </div>
    </aside>
    
    <!-- Overlay para cerrar sidebar en móvil -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebarMenu()"></div>

    <!-- Main Content Area -->
    <main>
        <div class="top-bar">
            <button id="sidebar-toggle" onclick="toggleSidebarMenu()" style="display: none; background: none; border: none; color: #fff; cursor: pointer; padding: 6px; margin-right: 0.75rem;" title="Mostrar Menú">
                <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
            </button>
            <div class="page-title" style="display: flex; align-items: center;">
                <?php
                $title = $page_title;

                if ($current_page == 'pipeline.php') {
                    global $pdo;
                    try {
                        $pipelines = $pdo->query("SELECT * FROM pipelines ORDER BY id")->fetchAll();
                        $active_pipeline_id = isset($_GET['pipeline_id']) ? intval($_GET['pipeline_id']) : ($pipelines[0]['id'] ?? 1);
                    } catch (Exception $e) {
                        $pipelines = [['id' => 1, 'name' => 'Pipeline de Ventas Principal']];
                        $active_pipeline_id = 1;
                    }
                    ?>
                    <h1 style="display:flex; align-items:center; gap:0.5rem;">
                        Pipeline: 
                        <select id="active-pipeline-selector" onchange="switchPipeline(this.value)" style="background:none; border:none; color:var(--color-primary); font-size:1.25rem; font-weight:700; cursor:pointer; padding:0; width:auto; display:inline-block; vertical-align:middle; outline:none; max-width:250px;">
                            <?php foreach ($pipelines as $pipe): ?>
                                <option value="<?php echo $pipe['id']; ?>" <?php echo $pipe['id'] == $active_pipeline_id ? 'selected' : ''; ?> style="background:var(--bg-secondary); color:#fff; font-size:1rem;">
                                    <?php echo htmlspecialchars($pipe['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </h1>
                    <?php
                } else {
                    ?>
                    <h1><?php echo $title; ?></h1>
                    <?php
                }
                ?>
            </div>
            
            <div class="top-bar-actions">
                <?php if ($current_page == 'pipeline.php'): ?>
                    <button class="btn-action btn-secondary" onclick="openCustomFieldsModal()" style="margin-right: 0.5rem; background:rgba(245,158,11,0.15); border-color:var(--color-warning); color: var(--color-warning);">
                        <i data-lucide="settings-2" style="color:var(--color-warning); display:inline-block; vertical-align:middle; margin-right:4px;"></i> Configurar Campos
                    </button>
                    <button class="btn-action btn-secondary" onclick="openPipelineModal()" style="margin-right: 0.5rem; background:rgba(var(--color-primary-rgb),0.15); border-color:var(--color-primary); color: var(--color-primary);">
                        <i data-lucide="plus-square" style="color:var(--color-primary); display:inline-block; vertical-align:middle; margin-right:4px;"></i> Nuevo Pipeline
                    </button>
                    <?php if (count($pipelines) > 1): ?>
                    <button class="btn-action btn-secondary" onclick="deletePipeline(<?php echo $active_pipeline_id; ?>)" style="margin-right: 0.5rem; background:rgba(239,68,68,0.12); border-color:var(--color-error); color: var(--color-error);">
                        <i data-lucide="trash-2" style="color:var(--color-error); display:inline-block; vertical-align:middle; margin-right:4px;"></i> Suprimir Pipeline
                    </button>
                    <?php endif; ?>
                    <button class="btn-action btn-secondary" onclick="openStageModal()" style="margin-right: 0.5rem; background:rgba(var(--color-secondary-rgb),0.15); border-color:var(--color-secondary); color: var(--color-secondary);">
                        <i data-lucide="plus-circle" style="color:var(--color-secondary); display:inline-block; vertical-align:middle; margin-right:4px;"></i> Nueva Etapa
                    </button>
                <?php endif; ?>
                <button id="theme-toggle-btn" class="btn-action btn-secondary" onclick="toggleCRMTheme()" style="margin-right: 0.5rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center; height: 38px; width: 38px;" title="Alternar Tema Claro/Oscuro">
                    <i id="theme-toggle-icon" data-lucide="sun" style="width: 18px; height: 18px;"></i>
                </button>
                <button class="btn-action" onclick="openGlobalDealModal()">
                    <i data-lucide="plus-circle"></i> Nueva Oportunidad
                </button>
            </div>
        </div>
        
        <div class="content-body">
