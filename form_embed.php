<?php
/**
 * form_embed.php — Formulario de Captación de Leads incrustable para Sitios Web
 */

require_once __DIR__ . '/config.php';

$form_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Obtener detalles del formulario
$stmt = $pdo->prepare("SELECT * FROM web_forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

if (!$form) {
    die("Formulario no encontrado.");
}

$fields = json_decode($form['fields'], true) ?: ['name', 'email'];
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $company = isset($_POST['company']) ? trim($_POST['company']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        if (empty($name) || empty($email)) {
            throw new Exception("El nombre y el correo son campos obligatorios.");
        }
        
        // Separar nombre y apellido
        $parts = explode(' ', $name, 2);
        $first_name = $parts[0];
        $last_name = isset($parts[1]) ? $parts[1] : '';
        
        // 1. Crear Cuenta Comercial si se provee empresa
        $account_id = null;
        if (in_array('company', $fields) && !empty($company)) {
            $stmt_acc = $pdo->prepare("INSERT INTO `accounts` (`name`, `industry`, `phone`, `email`, `city`) VALUES (?, 'Pastelería', ?, ?, 'San José')");
            $stmt_acc->execute([$company, $phone, $email]);
            $account_id = $pdo->lastInsertId();
        }
        
        // 2. Crear Persona de Contacto
        $stmt_con = $pdo->prepare("INSERT INTO `contacts` (`account_id`, `first_name`, `last_name`, `job_title`, `phone`, `email`) VALUES (?, ?, ?, 'Web Lead', ?, ?)");
        $stmt_con->execute([$account_id, $first_name, $last_name, $phone, $email]);
        $contact_id = $pdo->lastInsertId();
        
        // 3. Crear Oportunidad de Venta (Deal) en Etapa 1 ("Contacto Inicial")
        $deal_title = "Lead Web: " . ($company ?: $name) . " — " . $form['title'];
        $deal_value = 0.00; // Valor por defecto
        $stage_id = 1; // Contacto Inicial
        $close_date = date('Y-m-d', strtotime('+30 days'));
        
        $stmt_deal = $pdo->prepare("INSERT INTO `deals` (`title`, `value`, `stage_id`, `account_id`, `contact_id`, `status`, `close_date`) VALUES (?, ?, ?, ?, ?, 'Open', ?)");
        $stmt_deal->execute([$deal_title, $deal_value, $stage_id, $account_id, $contact_id, $close_date]);
        $deal_id = $pdo->lastInsertId();
        
        // 4. Si se incluyó un mensaje, agregarlo como nota en la oportunidad
        if (!empty($message)) {
            $stmt_note = $pdo->prepare("INSERT INTO `notes` (`deal_id`, `content`) VALUES (?, ?)");
            $stmt_note->execute([$deal_id, "Mensaje enviado desde el formulario web:\n\"" . $message . "\""]);
        }
        
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?></title>
    <!-- Google Font Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-embed: #0c1020;
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --color-primary: #06b6d4;
            --color-secondary: #a855f7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-embed);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            padding: 1.5rem;
            overflow-x: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-card {
            width: 100%;
            max-width: 450px;
            background: rgba(18, 24, 48, 0.55);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        h2 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #fff;
            border-bottom: 1px dashed var(--border-color);
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.35rem;
            color: var(--text-main);
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            background-color: rgba(7, 10, 19, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.55rem 0.75rem;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            transition: 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 8px rgba(6, 182, 212, 0.15);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.65rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.2);
            transition: 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(6, 182, 212, 0.3);
        }

        .alert {
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
    </style>
</head>
<body>

<div class="form-card">
    <h2><?php echo htmlspecialchars($form['title']); ?></h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            🎉 ¡Solicitud recibida! Un asesor de TIPS B2B se comunicará contigo pronto.
        </div>
    <?php else: ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="name">Nombre y Apellidos *</label>
                <input type="text" id="name" name="name" placeholder="Ej. Mariana López" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico *</label>
                <input type="email" id="email" name="email" placeholder="mariana@correo.com" required>
            </div>
            
            <?php if (in_array('phone', $fields)): ?>
                <div class="form-group">
                    <label for="phone">Teléfono / WhatsApp</label>
                    <input type="text" id="phone" name="phone" placeholder="Ej. 8888-8888">
                </div>
            <?php endif; ?>

            <?php if (in_array('company', $fields)): ?>
                <div class="form-group">
                    <label for="company">Nombre de tu Negocio / Empresa</label>
                    <input type="text" id="company" name="company" placeholder="Ej. Repostería El Dulce Capricho">
                </div>
            <?php endif; ?>

            <?php if (in_array('message', $fields)): ?>
                <div class="form-group">
                    <label for="message">Mensaje / Detalle de tu Solicitud</label>
                    <textarea id="message" name="message" rows="3" placeholder="¿En qué productos o equipos estás interesado...?"></textarea>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-submit">Enviar Solicitud</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
