<?php
/**
 * email_inbox.php — Bandeja de Entrada de Correos Integrada (TIPS CRM)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

$success_msg = '';
$error_msg = '';

// Procesar envío de correo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    try {
        $recipient = trim($_POST['recipient']);
        $subject = trim($_POST['subject']);
        $body = trim($_POST['body']);
        $deal_id = !empty($_POST['deal_id']) ? intval($_POST['deal_id']) : null;
        
        if (empty($recipient) || empty($subject) || empty($body)) {
            throw new Exception("Todos los campos son obligatorios.");
        }
        
        // Registrar en la base de datos como correo saliente (Sent)
        $stmt = $pdo->prepare("INSERT INTO `emails` (`deal_id`, `direction`, `sender`, `recipient`, `subject`, `body`) VALUES (?, 'Sent', 'asesor@tipscr.com', ?, ?, ?)");
        $stmt->execute([$deal_id, $recipient, $subject, $body]);
        
        // Simular respuesta automática del cliente después de enviar (para fines de demostración interactiva)
        // Esto le da una sensación viva de "recibir correos"
        $auto_reply_subject = "Re: " . $subject;
        $auto_reply_body = "Hola, he recibido tu correo. Estaré revisando la cotización técnica hoy por la tarde con mi socio y te aviso. ¡Saludos!";
        
        $stmt_reply = $pdo->prepare("INSERT INTO `emails` (`deal_id`, `direction`, `sender`, `recipient`, `subject`, `body`) VALUES (?, 'Received', ?, 'asesor@tipscr.com', ?, ?)");
        $stmt_reply->execute([$deal_id, $recipient, $auto_reply_subject, $auto_reply_body]);
        
        $success_msg = "¡Correo enviado con éxito! (Simulando SMTP). Se ha recibido una respuesta automática en tu bandeja.";
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Cargar todos los correos registrados
$emails = $pdo->query("
    SELECT e.*, d.title AS deal_title 
    FROM emails e
    LEFT JOIN deals d ON e.deal_id = d.id
    ORDER BY e.sent_date DESC
")->fetchAll();

// Cargar deals abiertos con detalles de contacto para asociar al redactar correo
$open_deals = $pdo->query("
    SELECT d.id, d.title, d.value, 
           c.first_name, c.last_name, c.email AS contact_email,
           a.name AS account_name
    FROM deals d
    LEFT JOIN contacts c ON d.contact_id = c.id
    LEFT JOIN accounts a ON d.account_id = a.id
    WHERE d.status = 'Open' 
    ORDER BY d.title ASC
")->fetchAll();

// Cargar plantillas de correo B2B
$email_templates = $pdo->query("SELECT * FROM email_templates ORDER BY id ASC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 2rem;">
    
    <!-- COLUMNA IZQUIERDA: REDACTAR CORREO -->
    <div class="card-section">
        <div class="section-header">
            <h2>Redactar Correo B2B</h2>
        </div>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="background-color:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#a7f3d0; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
                ✔️ <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error" style="background-color:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
                ❌ <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <input type="hidden" name="send_email" value="1">
            
            <div class="form-group">
                <label for="mail_deal">Vincular a Oportunidad (Deal)</label>
                <select id="mail_deal" name="deal_id">
                    <option value="">-- Sin Vincular (General) --</option>
                    <?php foreach ($open_deals as $d): 
                        $selected = (isset($_GET['deal_id']) && intval($_GET['deal_id']) === intval($d['id'])) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $selected; ?>
                                data-contact="<?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>"
                                data-email="<?php echo htmlspecialchars($d['contact_email'] ?? ''); ?>"
                                data-company="<?php echo htmlspecialchars($d['account_name'] ?? ''); ?>"
                                data-value="<?php echo htmlspecialchars($d['value']); ?>">
                            <?php echo htmlspecialchars($d['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="email-template-select">Plantilla de Correo B2B (Respuesta Rápida)</label>
                <select id="email-template-select" onchange="applyEmailTemplate()">
                    <option value="">-- Redactar en Blanco --</option>
                    <?php foreach ($email_templates as $tpl): ?>
                        <option value="<?php echo htmlspecialchars($tpl['id']); ?>"
                                data-subject="<?php echo htmlspecialchars($tpl['subject']); ?>"
                                data-body="<?php echo htmlspecialchars($tpl['body']); ?>">
                            <?php echo htmlspecialchars($tpl['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="mail_recipient">Destinatario (Email del cliente) *</label>
                <input type="email" id="mail_recipient" name="recipient" placeholder="cliente@pasteleria.com" required>
            </div>
            
            <div class="form-group">
                <label for="mail_subject">Asunto *</label>
                <input type="text" id="mail_subject" name="subject" placeholder="Ej. Cotización Lote Colorantes Enco / Batidora KitchenAid" required>
            </div>
            
            <div class="form-group">
                <label for="mail_body">Cuerpo del Correo *</label>
                <textarea id="mail_body" name="body" rows="8" placeholder="Estimado cliente, adjunto los detalles acordados..." required></textarea>
            </div>
            
            <button type="submit" class="btn-submit">
                <i data-lucide="send" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                Enviar Correo
            </button>
        </form>
    </div>
    
    <!-- COLUMNA DERECHA: HISTORIAL DE CORREOS -->
    <div class="card-section">
        <div class="section-header">
            <h2>Historial de Comunicaciones por Correo</h2>
        </div>
        
        <div style="display:flex; flex-direction:column; gap:1rem; max-height:600px; overflow-y:auto; padding-right:0.5rem;">
            <?php if (count($emails) > 0): ?>
                <?php foreach ($emails as $email): 
                    $is_sent = $email['direction'] === 'Sent';
                    $bg_color = $is_sent ? 'rgba(6,182,212,0.03)' : 'rgba(168,85,247,0.03)';
                    $border_color = $is_sent ? 'rgba(6,182,212,0.15)' : 'rgba(168,85,247,0.15)';
                    $icon = $is_sent ? 'send' : 'mail-question';
                    ?>
                    <div style="background:<?php echo $bg_color; ?>; border:1px solid <?php echo $border_color; ?>; border-radius:10px; padding:1.25rem;">
                        <div class="flex-between" style="margin-bottom:0.5rem;">
                            <span style="font-size:0.75rem; color:var(--text-muted); display:flex; align-items:center; gap:0.25rem;">
                                <i data-lucide="<?php echo $icon; ?>" style="width:12px; height:12px; color:var(--color-primary);"></i>
                                <?php echo $is_sent ? 'Enviado por Ti' : 'Recibido del Cliente'; ?>
                            </span>
                            <span style="font-size:0.7rem; color:var(--text-dark);"><?php echo date('d M, h:i a', strtotime($email['sent_date'])); ?></span>
                        </div>
                        
                        <h4 style="font-size:0.9rem; color:#fff; font-weight:600; margin-bottom:0.25rem;"><?php echo htmlspecialchars($email['subject']); ?></h4>
                        
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.5rem;">
                            <div>De: <?php echo htmlspecialchars($email['sender']); ?></div>
                            <div>Para: <?php echo htmlspecialchars($email['recipient']); ?></div>
                            <?php if ($email['deal_title']): ?>
                                <div style="color:var(--color-primary); font-size:0.75rem; margin-top:0.25rem;">
                                    📌 Vinculado a: <?php echo htmlspecialchars($email['deal_title']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <p style="font-size:0.85rem; color:#cbd5e1; background:rgba(0,0,0,0.2); padding:0.75rem; border-radius:6px; border:1px solid rgba(255,255,255,0.02); white-space:pre-line;"><?php echo htmlspecialchars($email['body']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; padding:3rem; color:var(--text-muted);">No hay correos registrados en esta oportunidad.</p>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<script>
    const mailDealSel = document.getElementById('mail_deal');
    const mailRecipientInput = document.getElementById('mail_recipient');
    const emailTemplateSel = document.getElementById('email-template-select');
    const mailSubjectInput = document.getElementById('mail_subject');
    const mailBodyTextarea = document.getElementById('mail_body');

    // Auto completar destinatario cuando cambia la oportunidad y refrescar plantilla
    mailDealSel.addEventListener('change', () => {
        const selectedOpt = mailDealSel.options[mailDealSel.selectedIndex];
        if (selectedOpt && selectedOpt.value !== "") {
            const email = selectedOpt.getAttribute('data-email');
            if (email) {
                mailRecipientInput.value = email;
            }
        }
        applyEmailTemplate();
    });

    function applyEmailTemplate() {
        const tplOpt = emailTemplateSel.options[emailTemplateSel.selectedIndex];
        if (!tplOpt || tplOpt.value === "") {
            mailSubjectInput.value = "";
            mailBodyTextarea.value = "";
            return;
        }
        
        let subject = tplOpt.getAttribute('data-subject') || '';
        let body = tplOpt.getAttribute('data-body') || '';
        
        // Obtener contexto de la oportunidad seleccionada
        const dealOpt = mailDealSel.options[mailDealSel.selectedIndex];
        let contactName = "Cliente";
        let companyName = "tu empresa";
        let dealValue = "0.00";
        
        if (dealOpt && dealOpt.value !== "") {
            contactName = dealOpt.getAttribute('data-contact') || "Cliente";
            companyName = dealOpt.getAttribute('data-company') || "tu empresa";
            dealValue = "$" + parseFloat(dealOpt.getAttribute('data-value') || "0").toFixed(2);
        }
        
        // Reemplazar etiquetas/placeholders dinámicos
        subject = subject.replace(/\{\{contact_name\}\}/g, contactName)
                         .replace(/\{\{company_name\}\}/g, companyName)
                         .replace(/\{\{deal_value\}\}/g, dealValue);
                         
        body = body.replace(/\{\{contact_name\}\}/g, contactName)
                   .replace(/\{\{company_name\}\}/g, companyName)
                   .replace(/\{\{deal_value\}\}/g, dealValue);
                   
        mailSubjectInput.value = subject;
        mailBodyTextarea.value = body;
    }

    // Auto-disparar cambio si viene pre-seleccionado de la Ficha 360
    if (mailDealSel.value !== "") {
        mailDealSel.dispatchEvent(new Event('change'));
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
