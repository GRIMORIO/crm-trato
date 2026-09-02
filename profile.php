<?php
/**
 * profile.php — Perfil del Usuario y Configuración de Seguridad
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Obtener datos del usuario actual
$stmt = $pdo->prepare("SELECT username, full_name, email FROM crm_users WHERE id = ?");
$stmt->execute([$_SESSION['crm_user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuario no encontrado.");
}
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    
    <!-- COLUMNA IZQUIERDA: INFORMACIÓN DE PERFIL -->
    <div class="card-section">
        <div class="section-header">
            <h2>👤 Información de Perfil</h2>
        </div>
        
        <div id="profile-msg" style="display: none; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;"></div>
        
        <form id="profile-form" onsubmit="updateProfile(event)">
            <div class="form-group">
                <label for="username">Nombre de Usuario (No modificable)</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
            </div>
            
            <div class="form-group">
                <label for="full_name">Nombre Completo *</label>
                <input type="text" id="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required placeholder="Tu nombre real">
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico (Para recuperación de contraseña)</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="ejemplo@correo.com">
            </div>
            
            <button type="submit" class="btn-submit">Guardar Datos de Perfil</button>
        </form>
    </div>
    
    <!-- COLUMNA DERECHA: CAMBIO DE CONTRASEÑA -->
    <div class="card-section">
        <div class="section-header">
            <h2>🔒 Seguridad (Cambiar Contraseña)</h2>
        </div>
        
        <div id="password-msg" style="display: none; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;"></div>
        
        <form id="password-form" onsubmit="changePassword(event)">
            <div class="form-group">
                <label for="current_password">Contraseña Actual *</label>
                <input type="password" id="current_password" required placeholder="Ingresa tu contraseña actual">
            </div>
            
            <div class="form-group">
                <label for="new_password">Contraseña Nueva * (mínimo 8 caracteres)</label>
                <input type="password" id="new_password" required placeholder="Mínimo 8 caracteres" minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña Nueva *</label>
                <input type="password" id="confirm_password" required placeholder="Repite la contraseña nueva" minlength="8">
            </div>
            
            <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, var(--color-secondary), var(--color-error)); border: none;">Actualizar Contraseña</button>
        </form>
    </div>
    
</div>

<script>
function updateProfile(e) {
    e.preventDefault();
    const fullName = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const msgDiv = document.getElementById('profile-msg');
    
    msgDiv.style.display = 'none';
    
    fetch('api.php?action=update_profile', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            full_name: fullName,
            email: email
        })
    })
    .then(res => res.json())
    .then(data => {
        msgDiv.style.display = 'block';
        if (data.success) {
            msgDiv.style.backgroundColor = 'rgba(16,185,129,0.1)';
            msgDiv.style.border = '1px solid rgba(16,185,129,0.3)';
            msgDiv.style.color = '#a7f3d0';
            msgDiv.innerHTML = '✔️ ' + data.message;
            
            // Actualizar nombre en la barra lateral
            const userTextEl = document.getElementById('sidebar-user-name');
            if (userTextEl) {
                userTextEl.textContent = fullName;
            }
        } else {
            msgDiv.style.backgroundColor = 'rgba(239,68,68,0.1)';
            msgDiv.style.border = '1px solid rgba(239,68,68,0.3)';
            msgDiv.style.color = '#fca5a5';
            msgDiv.innerHTML = '❌ ' + data.error;
        }
    })
    .catch(err => {
        msgDiv.style.display = 'block';
        msgDiv.style.backgroundColor = 'rgba(239,68,68,0.1)';
        msgDiv.style.border = '1px solid rgba(239,68,68,0.3)';
        msgDiv.style.color = '#fca5a5';
        msgDiv.innerHTML = '❌ Error de red al actualizar perfil.';
    });
}

function changePassword(e) {
    e.preventDefault();
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const msgDiv = document.getElementById('password-msg');
    
    msgDiv.style.display = 'none';
    
    if (newPassword !== confirmPassword) {
        msgDiv.style.display = 'block';
        msgDiv.style.backgroundColor = 'rgba(239,68,68,0.1)';
        msgDiv.style.border = '1px solid rgba(239,68,68,0.3)';
        msgDiv.style.color = '#fca5a5';
        msgDiv.innerHTML = '❌ Las nuevas contraseñas no coinciden.';
        return;
    }
    
    if (newPassword.length < 8) {
        msgDiv.style.display = 'block';
        msgDiv.style.backgroundColor = 'rgba(239,68,68,0.1)';
        msgDiv.style.border = '1px solid rgba(239,68,68,0.3)';
        msgDiv.style.color = '#fca5a5';
        msgDiv.innerHTML = '❌ La nueva contraseña debe tener al menos 8 caracteres.';
        return;
    }
    
    fetch('api.php?action=change_password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword
        })
    })
    .then(res => res.json())
    .then(data => {
        msgDiv.style.display = 'block';
        if (data.success) {
            msgDiv.style.backgroundColor = 'rgba(16,185,129,0.1)';
            msgDiv.style.border = '1px solid rgba(16,185,129,0.3)';
            msgDiv.style.color = '#a7f3d0';
            msgDiv.innerHTML = '✔️ ' + data.message;
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        } else {
            msgDiv.style.backgroundColor = 'rgba(239,68,68,0.1)';
            msgDiv.style.border = '1px solid rgba(239,68,68,0.3)';
            msgDiv.style.color = '#fca5a5';
            msgDiv.innerHTML = '❌ ' + data.error;
        }
    })
    .catch(err => {
        msgDiv.style.display = 'block';
        msgDiv.style.backgroundColor = 'rgba(239,68,68,0.1)';
        msgDiv.style.border = '1px solid rgba(239,68,68,0.3)';
        msgDiv.style.color = '#fca5a5';
        msgDiv.innerHTML = '❌ Error de red al cambiar contraseña.';
    });
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
