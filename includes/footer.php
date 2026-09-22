<?php
/**
 * footer.php — Pie de página global y modal de creación rápida para TIPS CRM
 */

// Cargar cuentas, contactos y etapas para el modal global de nuevo deal
global $pdo;
$stages_opt = [];
$accounts_opt = [];
$contacts_opt = [];

if (isset($pdo)) {
    try {
        $stages_opt = $pdo->query("SELECT * FROM stages ORDER BY position")->fetchAll();
        $accounts_opt = $pdo->query("SELECT id, name FROM accounts ORDER BY name")->fetchAll();
        $contacts_opt = $pdo->query("SELECT id, first_name, last_name FROM contacts ORDER BY first_name")->fetchAll();
    } catch (Exception $e) {
        // Fallback silencioso
    }
}
?>
        </div> <!-- Fin de content-body -->
    </main>

    <!-- Modal Global: Nueva Oportunidad -->
    <div class="modal-overlay" id="global-deal-modal">
        <div class="modal-card">
            <div class="modal-title">
                <span>Nueva Oportunidad de Venta</span>
                <button class="modal-close" onclick="closeGlobalDealModal()">&times;</button>
            </div>
            
            <form action="api.php?action=create_deal" method="POST">
                <!-- Retener la URL de origen para redireccionar después -->
                <input type="hidden" name="redirect_uri" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">

                <div class="form-group">
                    <label for="deal_title">Título de la Oportunidad *</label>
                    <input type="text" id="deal_title" name="title" placeholder="Ej. Lote Colorantes Enco Gel - Doña Rebeca" required>
                </div>

                <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="deal_value">Monto Estimado ($) *</label>
                        <input type="number" id="deal_value" name="value" step="0.01" min="0" placeholder="1500.00" required>
                    </div>
                    <div class="form-group">
                        <label for="deal_stage">Etapa del Pipeline *</label>
                        <select id="deal_stage" name="stage_id" required>
                            <?php 
                            $all_pipelines = $pdo->query("SELECT * FROM pipelines ORDER BY id")->fetchAll();
                            foreach ($all_pipelines as $pipe):
                                $pipe_stages = $pdo->prepare("SELECT * FROM stages WHERE pipeline_id = ? ORDER BY position");
                                $pipe_stages->execute([$pipe['id']]);
                                $stages_list = $pipe_stages->fetchAll();
                                if (!empty($stages_list)):
                                ?>
                                    <optgroup label="<?php echo htmlspecialchars($pipe['name']); ?>">
                                        <?php foreach ($stages_list as $stg): ?>
                                            <option value="<?php echo $stg['id']; ?>"><?php echo htmlspecialchars($stg['name']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="deal_account">Cuenta Comercial (Empresa B2B)</label>
                    <select id="deal_account" name="account_id">
                        <option value="">-- Seleccionar Empresa --</option>
                        <?php foreach ($accounts_opt as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="deal_contact">Persona de Contacto</label>
                    <select id="deal_contact" name="contact_id">
                        <option value="">-- Seleccionar Contacto --</option>
                        <?php foreach ($contacts_opt as $con): ?>
                            <option value="<?php echo $con['id']; ?>"><?php echo htmlspecialchars($con['first_name'] . ' ' . $con['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="deal_close_date">Fecha Estimada de Cierre</label>
                    <input type="date" id="deal_close_date" name="close_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>

                <button type="submit" class="btn-submit">Crear Oportunidad</button>
            </form>
        </div>
    </div>

    <script>
        // Inicializar iconos de Lucide
        lucide.createIcons();

        // Control del Modal Global
        const globalDealModal = document.getElementById('global-deal-modal');

        function openGlobalDealModal() {
            if (globalDealModal) {
                globalDealModal.style.display = 'flex';
                setTimeout(() => globalDealModal.classList.add('active'), 10);
            }
        }

        function closeGlobalDealModal() {
            if (globalDealModal) {
                globalDealModal.classList.remove('active');
                setTimeout(() => globalDealModal.style.display = 'none', 250);
            }
        }

        // Cerrar al hacer clic fuera del modal card
        window.addEventListener('click', (e) => {
            if (e.target === globalDealModal) {
                closeGlobalDealModal();
            }
        });

        // Control de Menú Sidebar en Móvil
        function toggleSidebarMenu() {
            const aside = document.querySelector('aside');
            const overlay = document.getElementById('sidebar-overlay');
            if (aside && overlay) {
                aside.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
    </script>
</body>
</html>
