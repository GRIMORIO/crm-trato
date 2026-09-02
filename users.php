<?php
/**
 * users.php — Gestión de usuarios del CRM (alta por invitación, rol, baja). Solo admin.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$me = (int) $_SESSION['crm_user_id'];
$users = $pdo->query("SELECT id, username, full_name, email, role, created_at FROM crm_users ORDER BY role = 'admin' DESC, username")->fetchAll(PDO::FETCH_ASSOC);

// ¿tiene invitación pendiente? (token vigente y contraseña aún placeholder no la sabemos, pero el token vigente indica que no ha entrado)
$pending = [];
foreach ($pdo->query("SELECT username FROM crm_password_resets WHERE expires_at > NOW()")->fetchAll(PDO::FETCH_COLUMN) as $pu) {
    $pending[$pu] = true;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.users-wrap { display: grid; gap: 2rem; max-width: 960px; }
.card-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.75rem; backdrop-filter: var(--glass-blur); }
.card-section h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.35rem; }
.card-section .hint { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem; }
.invite-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.users-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.users-table th, .users-table td { text-align: left; padding: 0.75rem 0.6rem; border-bottom: 1px solid var(--border-color); }
.users-table th { color: var(--text-muted); font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
.badge { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; }
.badge-admin { background: rgba(168,85,247,0.15); color: #c4b5fd; }
.badge-agent { background: rgba(148,163,184,0.15); color: var(--text-muted); }
.badge-pending { background: rgba(245,158,11,0.15); color: #fcd34d; margin-left: 0.4rem; }
.row-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.row-actions button { background: none; border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; padding: 0.3rem 0.6rem; font-size: 0.78rem; cursor: pointer; transition: var(--transition); }
.row-actions button:hover { border-color: var(--color-primary); }
.row-actions button.danger:hover { border-color: var(--color-error); color: var(--color-error); }
#invite-msg, #table-msg { display: none; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.85rem; word-break: break-word; }
.table-scroll { overflow-x: auto; }
</style>

<div class="users-wrap">

    <div class="card-section">
        <h2>➕ Invitar usuario</h2>
        <p class="hint">Se crea la cuenta y se le envía un correo con un enlace (48 h) para que defina su contraseña. El correo saliente usa la configuración de <a href="settings.php" style="color:var(--color-primary);">Configuración → Correo (SMTP)</a>.</p>
        <div id="invite-msg"></div>
        <form id="invite-form" onsubmit="inviteUser(event)">
            <div class="invite-grid">
                <div class="form-group">
                    <label for="inv_email">Correo *</label>
                    <input type="email" id="inv_email" required placeholder="persona@empresa.com" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="inv_name">Nombre completo *</label>
                    <input type="text" id="inv_name" required placeholder="Nombre y apellido">
                </div>
                <div class="form-group">
                    <label for="inv_user">Usuario (opcional)</label>
                    <input type="text" id="inv_user" placeholder="se deriva del correo" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="inv_role">Rol *</label>
                    <select id="inv_role">
                        <option value="agent">Agente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-submit" style="margin-top:0.5rem;">Crear y enviar invitación</button>
        </form>
    </div>

    <div class="card-section">
        <h2>👥 Usuarios (<?php echo count($users); ?>)</h2>
        <p class="hint">El rol <strong>Administrador</strong> puede gestionar usuarios y configuración. <strong>Agente</strong> solo ve sus oportunidades asignadas.</p>
        <div id="table-msg"></div>
        <div class="table-scroll">
        <table class="users-table">
            <thead>
                <tr><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Alta</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr data-user-id="<?php echo $u['id']; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                        <?php if ($u['id'] === $me): ?><span class="badge badge-agent">tú</span><?php endif; ?>
                        <?php if (isset($pending[$u['username']])): ?><span class="badge badge-pending">invitación pendiente</span><?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($u['full_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($u['email'] ?? '—'); ?></td>
                    <td><span class="badge badge-<?php echo $u['role'] === 'admin' ? 'admin' : 'agent'; ?>"><?php echo $u['role'] === 'admin' ? 'Admin' : 'Agente'; ?></span></td>
                    <td style="color:var(--text-muted); white-space:nowrap;"><?php echo htmlspecialchars(substr($u['created_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <?php if ($u['id'] !== $me): ?>
                        <div class="row-actions">
                            <?php if (!empty($u['email'])): ?>
                                <button type="button" onclick="resendInvite(<?php echo $u['id']; ?>)">Reenviar acceso</button>
                            <?php endif; ?>
                            <?php if ($u['role'] === 'admin'): ?>
                                <button type="button" onclick="changeRole(<?php echo $u['id']; ?>, 'agent')">Hacer agente</button>
                            <?php else: ?>
                                <button type="button" onclick="changeRole(<?php echo $u['id']; ?>, 'admin')">Hacer admin</button>
                            <?php endif; ?>
                            <button type="button" class="danger" onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')">Eliminar</button>
                        </div>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-size:0.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<script>
function showMsg(id, ok, text) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.style.backgroundColor = ok ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
    el.style.border = '1px solid ' + (ok ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)');
    el.style.color = ok ? '#a7f3d0' : '#fca5a5';
    el.innerHTML = (ok ? '✔️ ' : '❌ ') + text;
}

function apiPost(action, payload) {
    return fetch('api.php?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {})
    }).then(r => r.json());
}

function inviteUser(e) {
    e.preventDefault();
    const payload = {
        email: document.getElementById('inv_email').value.trim(),
        full_name: document.getElementById('inv_name').value.trim(),
        username: document.getElementById('inv_user').value.trim(),
        role: document.getElementById('inv_role').value
    };
    apiPost('invite_user', payload).then(d => {
        if (d.success) {
            let extra = d.mail_sent ? '' : '<br><span style="opacity:.85">Enlace: <a href="' + d.invite_link + '" style="color:#a7f3d0">' + d.invite_link + '</a></span>';
            showMsg('invite-msg', true, d.message + extra);
            setTimeout(() => location.reload(), d.mail_sent ? 1400 : 6000);
        } else {
            showMsg('invite-msg', false, d.error);
        }
    }).catch(() => showMsg('invite-msg', false, 'Error de red.'));
}

function resendInvite(id) {
    apiPost('resend_invite', { user_id: id }).then(d => {
        if (d.success) {
            let extra = d.mail_sent ? '' : '<br>Enlace: <a href="' + d.invite_link + '" style="color:#a7f3d0">' + d.invite_link + '</a>';
            showMsg('table-msg', d.mail_sent, d.message + extra);
        } else showMsg('table-msg', false, d.error);
    }).catch(() => showMsg('table-msg', false, 'Error de red.'));
}

function changeRole(id, role) {
    apiPost('update_user_role', { user_id: id, role: role }).then(d => {
        if (d.success) { showMsg('table-msg', true, d.message); setTimeout(() => location.reload(), 800); }
        else showMsg('table-msg', false, d.error);
    }).catch(() => showMsg('table-msg', false, 'Error de red.'));
}

function deleteUser(id, name) {
    if (!confirm('¿Eliminar al usuario "' + name + '"? Esta acción no se puede deshacer.')) return;
    apiPost('delete_user', { user_id: id }).then(d => {
        if (d.success) { showMsg('table-msg', true, d.message); setTimeout(() => location.reload(), 800); }
        else showMsg('table-msg', false, d.error);
    }).catch(() => showMsg('table-msg', false, 'Error de red.'));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
