<?php
/**
 * form_builder.php — Panel de Control de Formularios Incrustables de Captación de Leads
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Obtener formularios configurados en la DB
$forms = $pdo->query("SELECT * FROM web_forms ORDER BY created_at DESC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    
    <div class="card-section">
        <div class="section-header">
            <h2>Formularios de Captación Activos</h2>
            <button class="btn-action btn-sm" onclick="openNewFormModal()">
                <i data-lucide="plus-circle" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                Crear Formulario
            </button>
        </div>
        
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem; max-width:850px;">
            Diseña formularios de contacto comerciales. Copia el código iframe proporcionado y colócalo en tu sitio web de TIPS o landing pages para que los prospectos ingresen directamente a tu flujo del CRM como leads calificados en la primera etapa.
        </p>

        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Formulario</th>
                        <th>Campos Activos</th>
                        <th>Fecha Creación</th>
                        <th>Código Incrustable (Iframe)</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $f): 
                        $fields_array = json_decode($f['fields'], true) ?: [];
                        $fields_badge = implode(', ', array_map(function($fld) {
                            return "<span class='badge' style='background:rgba(6,182,212,0.08); color:var(--color-primary); font-size:0.7rem; margin-right:2px;'>$fld</span>";
                        }, $fields_array));
                        
                        // Generar el código iframe exacto
                        $embed_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/form_embed.php?id=" . $f['id'];
                        $iframe_code = '<iframe src="' . $embed_url . '" width="100%" height="450px" frameborder="0" style="border:1px solid rgba(255,255,255,0.08); border-radius:8px;"></iframe>';
                        ?>
                        <tr>
                            <td>#<?php echo $f['id']; ?></td>
                            <td style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($f['title']); ?></td>
                            <td><?php echo $fields_badge; ?></td>
                            <td><?php echo date('d M Y', strtotime($f['created_at'])); ?></td>
                            <td>
                                <input type="text" readonly value="<?php echo htmlspecialchars($iframe_code); ?>" 
                                       id="iframe-code-<?php echo $f['id']; ?>"
                                       style="width: 250px; font-family: monospace; font-size: 0.75rem; background:rgba(0,0,0,0.3); border:1px solid var(--border-color); padding: 4px; border-radius: 4px;">
                                <button class="btn-secondary btn-sm" onclick="copyEmbed(<?php echo $f['id']; ?>)" style="padding:2px 6px !important; border-radius:4px;">Copiar</button>
                            </td>
                            <td class="text-right">
                                <a href="form_embed.php?id=<?php echo $f['id']; ?>" target="_blank" class="btn-action btn-sm" style="text-decoration:none; display:inline-block;">
                                    <i data-lucide="external-link" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:2px;"></i> Ver Formulario
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Nuevo Formulario -->
<div class="modal-overlay" id="new-form-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Crear Formulario de Leads</span>
            <button class="modal-close" onclick="closeNewFormModal()">&times;</button>
        </div>
        <form action="form_builder.php" method="POST">
            <div class="form-group">
                <label for="form_title">Nombre del Formulario *</label>
                <input type="text" id="form_title" name="title" placeholder="Ej. Formulario Landing Page KitchenAid" required>
            </div>
            
            <div class="form-group">
                <label>Seleccionar campos que se mostrarán en la Web:</label>
                <div style="background:rgba(0,0,0,0.2); padding:1rem; border-radius:8px; border:1px solid var(--border-color); display:flex; flex-direction:column; gap:0.5rem;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                        <input type="checkbox" checked disabled>
                        <span>Nombre y Apellido (Obligatorio)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                        <input type="checkbox" checked disabled>
                        <span>Correo Electrónico (Obligatorio)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                        <input type="checkbox" name="fields[]" value="phone" checked>
                        <span>Número de Teléfono / WhatsApp</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                        <input type="checkbox" name="fields[]" value="company" checked>
                        <span>Nombre de la Empresa (B2B)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                        <input type="checkbox" name="fields[]" value="message" checked>
                        <span>Mensaje / Solicitud de cotización</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Crear Formulario</button>
        </form>
    </div>
</div>

<?php
// Procesar guardado de nuevo formulario si se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $selected_fields = isset($_POST['fields']) ? $_POST['fields'] : [];
    
    // Forzar campos básicos
    array_unshift($selected_fields, 'email');
    array_unshift($selected_fields, 'name');
    
    $fields_json = json_encode($selected_fields);
    
    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO `web_forms` (`title`, `fields`) VALUES (?, ?)");
        $stmt->execute([$title, $fields_json]);
        
        // Refrescar página para listar
        echo "<script>window.location = 'form_builder.php';</script>";
        exit;
    }
}
?>

<script>
    const newFormModal = document.getElementById('new-form-modal');

    function openNewFormModal() {
        newFormModal.style.display = 'flex';
        setTimeout(() => newFormModal.classList.add('active'), 10);
    }
    
    function closeNewFormModal() {
        newFormModal.classList.remove('active');
        setTimeout(() => newFormModal.style.display = 'none', 250);
    }

    window.addEventListener('click', (e) => {
        if (e.target === newFormModal) closeNewFormModal();
    });

    function copyEmbed(id) {
        const copyText = document.getElementById("iframe-code-" + id);
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Para móviles
        document.execCommand("copy");
        alert("¡Código iframe copiado al portapapeles!");
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
