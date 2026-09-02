<?php
/**
 * accounts.php — Directorio B2B de Cuentas y Contactos para TIPS CRM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Obtener cuentas y contactos filtrados por grupo de visibilidad
$restriction = get_visibility_restriction();

if ($restriction !== '') {
    $stmt_acc = $pdo->prepare("SELECT * FROM accounts WHERE assigned_agent = ? ORDER BY name ASC");
    $stmt_acc->execute([$restriction]);
    $accounts = $stmt_acc->fetchAll();

    $stmt_con = $pdo->prepare("
        SELECT c.*, a.name AS account_name 
        FROM contacts c
        JOIN accounts a ON c.account_id = a.id
        WHERE a.assigned_agent = ?
        ORDER BY c.first_name ASC
    ");
    $stmt_con->execute([$restriction]);
    $contacts = $stmt_con->fetchAll();
} else {
    $accounts = $pdo->query("SELECT * FROM accounts ORDER BY name ASC")->fetchAll();
    $contacts = $pdo->query("
        SELECT c.*, a.name AS account_name 
        FROM contacts c
        LEFT JOIN accounts a ON c.account_id = a.id
        ORDER BY c.first_name ASC
    ")->fetchAll();
}

$whatsapp_templates = $pdo->query("SELECT * FROM whatsapp_templates")->fetchAll();
$industries = $pdo->query("SELECT * FROM industries ORDER BY name ASC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
    
    <!-- PANEL DE CUENTAS COMERCIALES (EMPRESAS) -->
    <div class="card-section">
        <div class="section-header">
            <h2>Cuentas Comerciales B2B (Empresas)</h2>
            <div style="display:flex; gap:0.5rem;">
                <button class="btn-action btn-sm" onclick="openAccountModal()">
                    <i data-lucide="plus-circle" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                    Agregar Empresa
                </button>
                <button class="btn-action btn-secondary btn-sm" onclick="openIndustryModal()" style="background:rgba(168,85,247,0.15); border-color:var(--color-secondary); color:var(--color-secondary);">
                    <i data-lucide="plus-circle" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                    Nuevo Sector
                </button>
            </div>
        </div>
        
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nombre de la Empresa</th>
                        <th>Sector / Tipo</th>
                        <th>Teléfono</th>
                        <th>Correo Electrónico</th>
                        <th>Ciudad</th>
                        <th>Dirección física</th>
                        <th style="width: 120px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    <a class="acct-link" href="account.php?id=<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['name']); ?></a>
                                </td>
                                <td><span class="badge" style="background-color:rgba(168,85,247,0.1); color:var(--color-secondary);"><?php echo htmlspecialchars($acc['industry']); ?></span></td>
                                <td><?php echo htmlspecialchars($acc['phone']); ?></td>
                                <td><?php echo htmlspecialchars($acc['email']); ?></td>
                                <td><?php echo htmlspecialchars($acc['city']); ?></td>
                                <td><?php echo htmlspecialchars($acc['address']); ?></td>
                                <td style="text-align: center;">
                                    <a class="btn-action btn-sm" href="account.php?id=<?php echo $acc['id']; ?>" style="background:rgba(6,182,212,0.15); border-color:var(--color-primary); color:var(--color-primary); padding:4px 8px; text-decoration:none;">
                                        <i data-lucide="history" style="width:13px; height:13px; display:inline-block; vertical-align:middle; margin-right:2px;"></i>
                                        Ver ficha
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">No hay empresas B2B registradas todavía.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PANEL DE CONTACTOS -->
    <div class="card-section">
        <div class="section-header">
            <h2>Personas de Contacto (Decisores Clave)</h2>
            <button class="btn-action btn-sm" onclick="openContactModal()">
                <i data-lucide="plus-circle" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                Agregar Contacto
            </button>
        </div>
        
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Empresa Asociada</th>
                        <th>Puesto / Cargo</th>
                        <th>Teléfono Directo</th>
                        <th>Correo Electrónico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($contacts) > 0): ?>
                        <?php foreach ($contacts as $con): ?>
                            <tr>
                                <td style="font-weight: 600; color: #fff;">
                                    <?php echo htmlspecialchars($con['first_name'] . ' ' . $con['last_name']); ?>
                                </td>
                                <td>
                                    <i data-lucide="building" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                                    <?php
                                    if ($con['account_name']) {
                                        echo '<a class="acct-link" href="account.php?id=' . (int) $con['account_id'] . '">' . htmlspecialchars($con['account_name']) . '</a>';
                                    } else {
                                        echo '<span style="color:var(--text-dark);">Sin asociar</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($con['job_title'] ?: 'N/D'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($con['phone']); ?>
                                    <a href="javascript:void(0)" onclick="openWhatsappTemplates('<?php echo $con['phone']; ?>', '<?php echo addslashes($con['first_name'] . ' ' . $con['last_name']); ?>')" title="Enviar WhatsApp con plantilla" style="color:var(--color-success); margin-left:6px;">
                                        <i data-lucide="message-square" style="width:14px; height:14px; display:inline-block; vertical-align:middle;"></i>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($con['email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">No hay contactos clave registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Nueva Cuenta/Empresa -->
<div class="modal-overlay" id="account-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Nueva Cuenta B2B</span>
            <button class="modal-close" onclick="closeAccountModal()">&times;</button>
        </div>
        <form action="api.php?action=create_account" method="POST">
            <div class="form-group">
                <label for="acc_name">Nombre de la Empresa *</label>
                <input type="text" id="acc_name" name="name" placeholder="Ej. Panadería El Maná" required>
            </div>
            <div class="grid-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="acc_industry">Industria / Sector</label>
                    <select id="acc_industry" name="industry" required>
                        <?php foreach ($industries as $ind): ?>
                            <option value="<?php echo htmlspecialchars($ind['name']); ?>"><?php echo htmlspecialchars($ind['name']); ?></option>
                        <?php endforeach; ?>
                        <option value="Otro">Otro Sector</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="acc_phone">Teléfono de Oficina</label>
                    <input type="text" id="acc_phone" name="phone" placeholder="2200-0000">
                </div>
            </div>
            <div class="form-group">
                <label for="acc_email">Correo General</label>
                <input type="email" id="acc_email" name="email" placeholder="contacto@empresa.com">
            </div>
            <div class="grid-form" style="display:grid; grid-template-columns: 1fr 1.5fr; gap:1rem;">
                <div class="form-group">
                    <label for="acc_city">Provincia / Ciudad</label>
                    <input type="text" id="acc_city" name="city" placeholder="San José">
                </div>
                <div class="form-group">
                    <label for="acc_address">Dirección de Entrega / Despacho</label>
                    <input type="text" id="acc_address" name="address" placeholder="Ej. 300m norte de la estación...">
                </div>
            </div>
            <button type="submit" class="btn-submit">Guardar Empresa</button>
        </form>
    </div>
</div>

<!-- Modal: Nuevo Contacto -->
<div class="modal-overlay" id="contact-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Nuevo Contacto Decisor</span>
            <button class="modal-close" onclick="closeContactModal()">&times;</button>
        </div>
        <form action="api.php?action=create_contact" method="POST">
            <div class="grid-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="con_first">Nombre *</label>
                    <input type="text" id="con_first" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="con_last">Apellido *</label>
                    <input type="text" id="con_last" name="last_name" required>
                </div>
            </div>
            <div class="form-group">
                <label for="con_account">Empresa Vinculada</label>
                <select id="con_account" name="account_id">
                    <option value="">-- Sin Vincular --</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="con_job">Cargo / Puesto</label>
                <input type="text" id="con_job" name="job_title" placeholder="Ej. Encargado de Compras, Pastelero Ejecutivo">
            </div>
            <div class="grid-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="con_phone">Móvil / WhatsApp</label>
                    <input type="text" id="con_phone" name="phone" placeholder="8888-8888">
                </div>
                <div class="form-group">
                    <label for="con_email">Correo de Contacto</label>
                    <input type="email" id="con_email" name="email" placeholder="nombre@correo.com">
                </div>
            </div>
            <button type="submit" class="btn-submit">Guardar Contacto</button>
        </form>
    </div>
</div>

<!-- Modal: Plantillas WhatsApp -->
<div class="modal-overlay" id="whatsapp-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Enviar Mensaje por WhatsApp</span>
            <button class="modal-close" onclick="closeWhatsappModal()">&times;</button>
        </div>
        <div class="form-group">
            <label>Destinatario:</label>
            <input type="text" id="wa-dest-name" readonly style="background:rgba(0,0,0,0.2);">
            <input type="hidden" id="wa-dest-phone">
        </div>
        <div class="form-group">
            <label for="wa-template-select">Seleccionar Plantilla B2B:</label>
            <select id="wa-template-select" onchange="onTemplateChange()">
                <option value="">-- Personalizado (Escribir en WhatsApp) --</option>
                <?php foreach ($whatsapp_templates as $tpl): ?>
                    <option value="<?php echo htmlspecialchars($tpl['template_text']); ?>"><?php echo htmlspecialchars($tpl['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="wa-preview">Vista Previa del Texto:</label>
            <textarea id="wa-preview" rows="4" placeholder="Escribe tu mensaje..."></textarea>
        </div>
        <button class="btn-submit" onclick="sendWhatsapp()">Abrir en WhatsApp</button>
    </div>
</div>

<!-- Modal: Nuevo Sector Industrial -->
<div class="modal-overlay" id="industry-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Agregar Nuevo Sector Industrial</span>
            <button class="modal-close" onclick="closeIndustryModal()">&times;</button>
        </div>
        <form action="api.php?action=create_industry" method="POST">
            <div class="form-group">
                <label for="ind_name">Nombre del Sector *</label>
                <input type="text" id="ind_name" name="name" placeholder="Ej. Distribuidora B2B, Heladería..." required>
            </div>
            <div class="form-group">
                <label for="ind_points">Puntos para Lead Scoring (1 - 100) *</label>
                <input type="number" id="ind_points" name="scoring_points" value="25" min="1" max="100" required>
            </div>
            <button type="submit" class="btn-submit">Crear Sector</button>
        </form>
    </div>
</div>

<script>
    const accountModal = document.getElementById('account-modal');
    const contactModal = document.getElementById('contact-modal');
    const whatsappModal = document.getElementById('whatsapp-modal');
    let currentContactName = '';

    // Cuentas Modales
    function openAccountModal() {
        accountModal.style.display = 'flex';
        setTimeout(() => accountModal.classList.add('active'), 10);
    }
    function closeAccountModal() {
        accountModal.classList.remove('active');
        setTimeout(() => accountModal.style.display = 'none', 250);
    }

    // Contactos Modales
    function openContactModal() {
        contactModal.style.display = 'flex';
        setTimeout(() => contactModal.classList.add('active'), 10);
    }
    function closeContactModal() {
        contactModal.classList.remove('active');
        setTimeout(() => contactModal.style.display = 'none', 250);
    }

    // WhatsApp Modal
    function openWhatsappTemplates(phone, name) {
        currentContactName = name;
        document.getElementById('wa-dest-name').value = name + ' (' + phone + ')';
        document.getElementById('wa-dest-phone').value = phone;
        document.getElementById('wa-template-select').value = '';
        document.getElementById('wa-preview').value = '';
        
        whatsappModal.style.display = 'flex';
        setTimeout(() => whatsappModal.classList.add('active'), 10);
    }

    function closeWhatsappModal() {
        whatsappModal.classList.remove('active');
        setTimeout(() => whatsappModal.style.display = 'none', 250);
    }

    function onTemplateChange() {
        const select = document.getElementById('wa-template-select');
        let text = select.value;
        
        // Reemplazar marcadores dinámicos
        text = text.replace(/\{\{name\}\}/g, currentContactName);
        text = text.replace(/\{\{deal_title\}\}/g, 'Colorantes Enco');
        text = text.replace(/\{\{value\}\}/g, '$1,500.00');
        
        document.getElementById('wa-preview').value = text;
    }

    function sendWhatsapp() {
        const phone = document.getElementById('wa-dest-phone').value;
        const text = encodeURIComponent(document.getElementById('wa-preview').value);
        const cleanPhone = phone.replace(/-|\s/g, "");
        
        let finalPhone = cleanPhone;
        if (cleanPhone.length === 8) {
            finalPhone = '506' + cleanPhone;
        }
        
        const url = `https://wa.me/${finalPhone}?text=${text}`;
        window.open(url, '_blank');
        closeWhatsappModal();
    }

    // Modal de Nuevo Sector
    const industryModal = document.getElementById('industry-modal');

    function openIndustryModal() {
        industryModal.style.display = 'flex';
        setTimeout(() => industryModal.classList.add('active'), 10);
    }
    function closeIndustryModal() {
        industryModal.classList.remove('active');
        setTimeout(() => industryModal.style.display = 'none', 250);
    }

    // Cerrar al hacer click afuera
    window.addEventListener('click', (e) => {
        if (e.target === accountModal) closeAccountModal();
        if (e.target === contactModal) closeContactModal();
        if (e.target === whatsappModal) closeWhatsappModal();
        if (e.target === industryModal) closeIndustryModal();
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
