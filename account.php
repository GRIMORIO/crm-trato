<?php
/**
 * account.php — Ficha 360 de una empresa B2B.
 *
 * Timeline del embudo por negocio (días por fase hasta la conversión), resumen del
 * negocio, panel de actividades y log de contacto. Se abre desde el nombre de la
 * empresa en accounts.php (?id=<account_id>, opcional &deal=<deal_id>).
 * Los datos se cargan con api.php?action=get_account_detail.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$account_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$preselect_deal = isset($_GET['deal']) ? intval($_GET['deal']) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<a href="accounts.php" style="display:inline-flex; align-items:center; gap:0.35rem; color:var(--text-muted); text-decoration:none; font-size:0.85rem; margin-bottom:1rem;">
    <i data-lucide="arrow-left" style="width:14px; height:14px;"></i> Volver al Directorio de Empresas
</a>

<div id="acct-loading" style="text-align:center; padding:3rem; color:var(--text-muted);">
    <i data-lucide="loader" style="width:26px; height:26px;"></i>
    <p style="margin-top:0.75rem;">Cargando ficha de la empresa…</p>
</div>

<div id="acct-error" style="display:none; text-align:center; padding:3rem;"></div>

<div id="acct-content" style="display:none;">
    <!-- Cabecera + selector de negocio + timeline -->
    <div class="card-section">
        <div class="acct-head">
            <div>
                <h2 id="acct-name"></h2>
                <div class="acct-meta" id="acct-meta"></div>
            </div>
            <div id="deal-switch-wrap" style="min-width:240px; display:none;">
                <label style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.25rem;">Negocio</label>
                <select id="deal-switch" onchange="selectDeal(this.value)"></select>
            </div>
        </div>

        <div id="timeline-wrap">
            <div class="deal-timeline" id="deal-timeline"></div>
            <div class="deal-condition" id="deal-condition"></div>
        </div>
        <div id="no-deals" style="display:none; padding:1.5rem; text-align:center; color:var(--text-muted);">
            Esta empresa todavía no tiene negocios en el embudo.
        </div>
    </div>

    <!-- Dos columnas: resumen (izq) / actividades (der) -->
    <div class="account-detail-grid">
        <!-- IZQUIERDA -->
        <div class="card-section" style="margin-bottom:0;">
            <div class="section-header"><h2>Resumen del negocio</h2></div>
            <div id="summary-deal"></div>

            <div class="acct-subsection" style="margin-top:1.5rem;">
                <h4><i data-lucide="user" style="width:15px;height:15px;"></i> Contacto decisor</h4>
                <div id="summary-contact"></div>
            </div>

            <div class="acct-subsection">
                <h4><i data-lucide="building-2" style="width:15px;height:15px;"></i> Datos de la empresa</h4>
                <div id="summary-account"></div>
            </div>
        </div>

        <!-- DERECHA -->
        <div class="card-section" style="margin-bottom:0;">
            <div class="section-header">
                <h2>Actividades</h2>
                <div style="display:flex; gap:0.4rem;">
                    <a id="link-email" class="btn-action btn-sm btn-secondary" style="text-decoration:none;">
                        <i data-lucide="mail" style="width:13px;height:13px;"></i> Correo
                    </a>
                    <a id="link-wa" class="btn-action btn-sm btn-secondary" target="_blank" style="text-decoration:none;">
                        <i data-lucide="message-square" style="width:13px;height:13px;"></i> WhatsApp
                    </a>
                </div>
            </div>

            <!-- Notas internas -->
            <div class="acct-subsection">
                <h4><i data-lucide="sticky-note" style="width:15px;height:15px;"></i> Notas internas</h4>
                <div id="notes-list"></div>
                <div id="note-add" style="margin-top:0.75rem; display:none;">
                    <textarea id="note-text" rows="2" placeholder="Escribe una nota comercial…"></textarea>
                    <button class="btn-action btn-sm" style="margin-top:0.4rem;" onclick="addNote()">Guardar nota</button>
                </div>
            </div>

            <!-- Agenda de actividades -->
            <div class="acct-subsection">
                <h4 style="justify-content:space-between;">
                    <span style="display:flex;align-items:center;gap:0.4rem;">
                        <i data-lucide="calendar-check" style="width:15px;height:15px;"></i> Agenda
                    </span>
                    <button id="btn-new-activity" class="btn-action btn-sm btn-secondary" onclick="openActivityModal()" style="display:none;">+ Actividad</button>
                </h4>
                <div id="activities-list"></div>
            </div>

            <!-- Documentos -->
            <div class="acct-subsection">
                <h4><i data-lucide="folder" style="width:15px;height:15px;"></i> Documentos / archivos para el cliente</h4>
                <form action="api.php?action=upload_account_document" method="POST" enctype="multipart/form-data"
                      style="background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:8px; padding:0.85rem; margin-bottom:0.85rem; display:grid; grid-template-columns:1fr auto; gap:0.5rem; align-items:end;">
                    <input type="hidden" name="account_id" id="doc-account-id">
                    <input type="hidden" name="redirect_uri" id="doc-redirect">
                    <div>
                        <input type="file" name="document_file" required style="font-size:0.78rem; width:100%; margin-bottom:0.4rem;">
                        <select name="category" style="font-size:0.78rem; padding:0.3rem 0.4rem;">
                            <option value="Contrato B2B">Contrato B2B</option>
                            <option value="Cotización">Cotización</option>
                            <option value="Ficha Técnica">Ficha Técnica</option>
                            <option value="Factura">Factura</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-action btn-sm">Subir</button>
                </form>
                <div id="docs-list"></div>
            </div>

            <!-- Facturas -->
            <div class="acct-subsection" style="margin-bottom:0;">
                <h4><i data-lucide="receipt" style="width:15px;height:15px;"></i> Facturas</h4>
                <div id="invoices-list"></div>
            </div>
        </div>
    </div>

    <!-- Log / historial de contacto -->
    <div class="card-section">
        <div class="section-header"><h2>Historial de contacto</h2></div>
        <div class="contact-log" id="contact-log"></div>
    </div>
</div>

<!-- Modal: Nueva actividad -->
<div class="modal-overlay" id="activity-modal">
    <div class="modal-card">
        <div class="modal-title">
            <span>Nueva actividad</span>
            <button class="modal-close" onclick="closeActivityModal()">&times;</button>
        </div>
        <form action="api.php?action=create_activity" method="POST">
            <input type="hidden" name="deal_id" id="act-deal-id">
            <input type="hidden" name="redirect_uri" id="act-redirect">
            <div class="grid-form" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="act-type">Tipo</label>
                    <select id="act-type" name="type">
                        <option value="Call">Llamada</option>
                        <option value="Email">Correo</option>
                        <option value="Meeting">Reunión</option>
                        <option value="Task">Tarea</option>
                        <option value="Technical_Visit">Visita Técnica</option>
                        <option value="Demo">Demostración</option>
                        <option value="Samples">Muestras</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="act-due">Fecha límite *</label>
                    <input type="datetime-local" id="act-due" name="due_date" required>
                </div>
            </div>
            <div class="form-group">
                <label for="act-subject">Asunto *</label>
                <input type="text" id="act-subject" name="subject" required placeholder="Ej. Llamar para coordinar demo">
            </div>
            <div class="form-group">
                <label for="act-desc">Detalle</label>
                <textarea id="act-desc" name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn-submit">Crear actividad</button>
        </form>
    </div>
</div>

<script>
    const ACCOUNT_ID = <?php echo (int) $account_id; ?>;
    const PRESELECT_DEAL = <?php echo (int) $preselect_deal; ?>;
    let DATA = null;
    let activeDealId = null;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const parseDT = (s) => s ? new Date(String(s).replace(' ', 'T')) : null;
    const toDate = (v) => (v instanceof Date) ? v : parseDT(v);
    const fmtDate = (s) => { const d = toDate(s); return d && !isNaN(d) ? d.toLocaleDateString('es-CR', { dateStyle: 'medium' }) : '—'; };
    const fmtDateTime = (s) => { const d = toDate(s); return d && !isNaN(d) ? d.toLocaleString('es-CR', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; };
    const money = (v) => parseFloat(v || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    const daysBetween = (a, b) => Math.max(0, Math.round((b - a) / 86400000));
    const dayLabel = (n) => n === 0 ? '<1 día' : (n === 1 ? '1 día' : n + ' días');

    // PDO devuelve todo como texto — normalizamos los IDs numéricos para poder comparar con ===.
    function normalize(d) {
        (d.deals || []).forEach(x => {
            x.id = +x.id; x.stage_id = +x.stage_id;
            x.stage_position = +x.stage_position; x.pipeline_id = +x.pipeline_id;
        });
        (d.stage_history || []).forEach(h => { h.deal_id = +h.deal_id; h.stage_id = +h.stage_id; h.position = +h.position; });
        (d.notes || []).forEach(n => n.deal_id = +n.deal_id);
        (d.emails || []).forEach(e => e.deal_id = +e.deal_id);
        (d.activities || []).forEach(a => { a.deal_id = +a.deal_id; a.id = +a.id; });
        Object.keys(d.pipeline_stages || {}).forEach(k => {
            d.pipeline_stages[k].forEach(s => { s.id = +s.id; s.position = +s.position; });
        });
        return d;
    }

    function load() {
        fetch('api.php?action=get_account_detail&account_id=' + ACCOUNT_ID)
            .then(r => r.json())
            .then(d => {
                if (!d.success) { showError(d.error || 'No se pudo cargar la ficha.'); return; }
                DATA = normalize(d);
                document.getElementById('acct-loading').style.display = 'none';
                document.getElementById('acct-content').style.display = 'block';
                renderHeader();
                buildDealSwitch();
                const first = DATA.deals.find(x => x.id == PRESELECT_DEAL) || DATA.deals[0];
                selectDeal(first ? first.id : null);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(err => { console.error(err); showError('Error de conexión al cargar la ficha.'); });
    }

    function showError(msg) {
        document.getElementById('acct-loading').style.display = 'none';
        const el = document.getElementById('acct-error');
        el.style.display = 'block';
        el.innerHTML = `<p style="color:var(--color-error); font-size:0.95rem;">${esc(msg)}</p>
            <a href="accounts.php" class="btn-action btn-sm" style="margin-top:1rem; text-decoration:none;">Volver al Directorio</a>`;
    }

    function renderHeader() {
        const a = DATA.account;
        document.getElementById('acct-name').textContent = a.name;
        const bits = [];
        if (a.industry) bits.push(esc(a.industry));
        if (a.city) bits.push(esc(a.city));
        if (a.assigned_agent) bits.push('Asesor: ' + esc(a.assigned_agent));
        document.getElementById('acct-meta').innerHTML = bits.join(' &nbsp;·&nbsp; ');
        document.getElementById('doc-account-id').value = a.id;
        document.getElementById('doc-redirect').value = 'account.php?id=' + a.id;
    }

    function buildDealSwitch() {
        const wrap = document.getElementById('deal-switch-wrap');
        const sel = document.getElementById('deal-switch');
        if (!DATA.deals.length) { wrap.style.display = 'none'; return; }
        sel.innerHTML = DATA.deals.map(d => {
            const st = d.status === 'Won' ? 'Ganado' : (d.status === 'Lost' ? 'Perdido' : 'Abierto');
            return `<option value="${d.id}">${esc(d.title)} — ${esc(d.pipeline_name)} (${st})</option>`;
        }).join('');
        wrap.style.display = DATA.deals.length > 1 ? 'block' : 'none';
    }

    function selectDeal(dealId) {
        if (!dealId) {
            document.getElementById('timeline-wrap').style.display = 'none';
            document.getElementById('no-deals').style.display = 'block';
            document.getElementById('note-add').style.display = 'none';
            document.getElementById('btn-new-activity').style.display = 'none';
            renderSummaryNoDeal();
            renderActivities(null);
            renderNotes(null);
            renderDocs();
            renderInvoices();
            renderLog(null);
            return;
        }
        activeDealId = parseInt(dealId);
        document.getElementById('deal-switch').value = String(activeDealId);
        document.getElementById('timeline-wrap').style.display = 'block';
        document.getElementById('no-deals').style.display = 'none';
        document.getElementById('note-add').style.display = 'block';
        document.getElementById('btn-new-activity').style.display = 'inline-block';

        const deal = DATA.deals.find(d => d.id === activeDealId);
        document.getElementById('act-deal-id').value = activeDealId;
        document.getElementById('act-redirect').value = 'account.php?id=' + ACCOUNT_ID + '&deal=' + activeDealId;
        document.getElementById('doc-redirect').value = 'account.php?id=' + ACCOUNT_ID + '&deal=' + activeDealId;
        document.getElementById('link-email').href = 'email_inbox.php?deal_id=' + activeDealId;
        setWhatsappLink(deal);

        renderTimeline(deal);
        renderSummary(deal);
        renderNotes(deal);
        renderActivities(deal);
        renderDocs();
        renderInvoices();
        renderLog(deal);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function setWhatsappLink(deal) {
        const link = document.getElementById('link-wa');
        const raw = deal.contact_phone || DATA.account.phone || '';
        const clean = raw.replace(/[-\s]/g, '');
        if (!clean) { link.style.opacity = '0.45'; link.style.pointerEvents = 'none'; link.removeAttribute('href'); return; }
        const phone = clean.length === 8 ? '506' + clean : clean;
        link.style.opacity = '1'; link.style.pointerEvents = 'auto';
        link.href = `https://wa.me/${phone}?text=` + encodeURIComponent('Hola ' + (deal.first_name || '') + ', le saludo de TIPS S.A.');
    }

    // ---- Timeline del embudo ----
    function stageDurations(deal) {
        // Devuelve {stageId: totalMs, firstEntry: Date, lastEntry: Date} a partir del historial.
        let hist = (DATA.stage_history || []).filter(h => h.deal_id === deal.id)
            .sort((x, y) => parseDT(x.entered_at) - parseDT(y.entered_at));
        if (!hist.length) {
            hist = [{ stage_id: deal.stage_id, entered_at: deal.created_at, stage_name: deal.stage_name, position: deal.stage_position }];
        }
        const end = deal.closed_at ? parseDT(deal.closed_at) : new Date();
        const durations = {};
        for (let i = 0; i < hist.length; i++) {
            const from = parseDT(hist[i].entered_at);
            const to = (i + 1 < hist.length) ? parseDT(hist[i + 1].entered_at) : end;
            const ms = Math.max(0, to - from);
            durations[hist[i].stage_id] = (durations[hist[i].stage_id] || 0) + ms;
        }
        return { durations, firstEntry: parseDT(hist[0].entered_at), end };
    }

    function renderTimeline(deal) {
        const stages = (DATA.pipeline_stages[deal.pipeline_id] || []).slice()
            .sort((a, b) => a.position - b.position);
        const { durations, firstEntry, end } = stageDurations(deal);
        const curPos = parseInt(deal.stage_position);
        const isWon = deal.status === 'Won';
        const isLost = deal.status === 'Lost';

        const nodes = stages.map(s => {
            const pos = parseInt(s.position);
            const ms = durations[s.id] || 0;
            const d = Math.round(ms / 86400000);
            let cls, badge;
            if (pos < curPos) {
                cls = 'done';
                badge = `<span class="timeline-day-badge">${dayLabel(d)}</span>`;
            } else if (pos === curPos) {
                if (isWon) { cls = 'done'; badge = `<span class="timeline-day-badge">${dayLabel(d)}</span>`; }
                else if (isLost) { cls = 'lost'; badge = `<span class="timeline-day-badge">${dayLabel(d)}</span>`; }
                else { cls = 'current'; badge = `<span class="timeline-day-badge">${dayLabel(d)} · en curso</span>`; }
            } else {
                cls = 'pending';
                badge = `<span class="timeline-day-badge" style="opacity:.5;">—</span>`;
            }
            const mark = cls === 'done' ? '✓' : (cls === 'lost' ? '✕' : pos);
            return `<div class="timeline-node ${cls}">
                <div class="timeline-dot">${mark}</div>
                <div class="timeline-stage-name">${esc(s.name)}</div>
                ${badge}
            </div>`;
        }).join('');
        document.getElementById('deal-timeline').innerHTML = nodes || '<p style="color:var(--text-muted);">Este embudo no tiene fases configuradas.</p>';

        const totalDays = daysBetween(firstEntry, end);
        let condBadge;
        if (isWon) condBadge = '<span class="badge badge-won">Ganado</span>';
        else if (isLost) condBadge = '<span class="badge badge-lost">Perdido</span>';
        else condBadge = '<span class="badge badge-open">Abierto</span>';

        const closedTxt = deal.closed_at
            ? `<span>Cerrado: <strong>${fmtDate(deal.closed_at)}</strong></span>`
            : `<span>Cierre estimado: <strong>${fmtDate(deal.close_date)}</strong></span>`;

        document.getElementById('deal-condition').innerHTML = `
            ${condBadge}
            <span>Ingreso al embudo: <strong>${fmtDate(DATA.stage_history.filter(h=>h.deal_id===deal.id)[0]?.entered_at || deal.created_at)}</strong></span>
            <span>${isWon || isLost ? 'Duración total' : 'En el embudo'}: <strong>${dayLabel(totalDays)}</strong></span>
            ${closedTxt}
        `;
    }

    // ---- Resumen ----
    function row(label, value) {
        return `<div class="summary-row"><span class="label">${label}</span><span class="value">${value}</span></div>`;
    }

    function renderSummary(deal) {
        const st = deal.status === 'Won' ? 'Ganado' : (deal.status === 'Lost' ? 'Perdido' : 'Abierto');
        document.getElementById('summary-deal').innerHTML =
            row('Negocio', esc(deal.title)) +
            row('Valor', money(deal.value)) +
            row('Estado', st) +
            row('Fase actual', esc(deal.stage_name) + ' · ' + esc(deal.pipeline_name)) +
            row('Fecha posible cierre', fmtDate(deal.close_date)) +
            (deal.closed_at ? row('Cerrado el', fmtDate(deal.closed_at)) : '') +
            row('Asesor asignado', esc(deal.assigned_agent || '—'));

        if (deal.first_name) {
            document.getElementById('summary-contact').innerHTML =
                row('Nombre', esc(deal.first_name + ' ' + deal.last_name)) +
                row('Cargo', esc(deal.job_title || '—')) +
                row('Teléfono', esc(deal.contact_phone || '—')) +
                row('Correo', esc(deal.contact_email || '—'));
        } else {
            const c = DATA.contacts[0];
            document.getElementById('summary-contact').innerHTML = c
                ? row('Nombre', esc(c.first_name + ' ' + c.last_name)) + row('Cargo', esc(c.job_title || '—')) + row('Teléfono', esc(c.phone || '—')) + row('Correo', esc(c.email || '—'))
                : '<p style="font-size:0.8rem; color:var(--text-dark);">Sin contacto asociado.</p>';
        }
        renderAccountBox();
    }

    function renderSummaryNoDeal() {
        document.getElementById('summary-deal').innerHTML = '<p style="font-size:0.85rem; color:var(--text-dark);">Sin negocios en el embudo para esta empresa.</p>';
        const c = DATA.contacts[0];
        document.getElementById('summary-contact').innerHTML = c
            ? row('Nombre', esc(c.first_name + ' ' + c.last_name)) + row('Cargo', esc(c.job_title || '—')) + row('Teléfono', esc(c.phone || '—')) + row('Correo', esc(c.email || '—'))
            : '<p style="font-size:0.8rem; color:var(--text-dark);">Sin contacto asociado.</p>';
        renderAccountBox();
    }

    function renderAccountBox() {
        const a = DATA.account;
        const avail = (parseFloat(a.credit_limit || 0) - parseFloat(a.credit_balance || 0));
        document.getElementById('summary-account').innerHTML =
            row('Sector', esc(a.industry || '—')) +
            row('Ciudad', esc(a.city || '—')) +
            row('Dirección', esc(a.address || '—')) +
            row('Condición de pago', esc(a.credit_terms || 'Contado')) +
            row('Límite de crédito', money(a.credit_limit)) +
            row('Saldo deudor', money(a.credit_balance)) +
            row('Crédito disponible', money(avail));
    }

    // ---- Notas ----
    function renderNotes(deal) {
        const el = document.getElementById('notes-list');
        const notes = deal ? DATA.notes.filter(n => n.deal_id === deal.id) : [];
        if (!notes.length) { el.innerHTML = '<p style="font-size:0.8rem; color:var(--text-dark);">Sin notas registradas.</p>'; return; }
        el.innerHTML = notes.map(n => `
            <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:6px; padding:0.6rem 0.75rem; margin-bottom:0.5rem; font-size:0.82rem;">
                <div style="white-space:pre-wrap; color:var(--text-main);">${esc(n.content)}</div>
                <div style="text-align:right; font-size:0.7rem; color:var(--text-dark); margin-top:0.35rem;">${fmtDateTime(n.created_at)}</div>
            </div>`).join('');
    }

    function addNote() {
        const t = document.getElementById('note-text').value.trim();
        if (!t || !activeDealId) return;
        fetch('api.php?action=add_deal_note', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ deal_id: activeDealId, content: t })
        }).then(r => r.json()).then(d => {
            if (d.success) { document.getElementById('note-text').value = ''; reloadKeepDeal(); }
            else alert('Error: ' + (d.error || 'no se pudo guardar la nota'));
        });
    }

    // ---- Actividades ----
    function actTypeLabel(t) {
        return { Call: 'Llamada', Email: 'Correo', Meeting: 'Reunión', Task: 'Tarea', Technical_Visit: 'Visita Técnica', Demo: 'Demostración', Samples: 'Muestras' }[t] || t;
    }

    function renderActivities(deal) {
        const el = document.getElementById('activities-list');
        const acts = deal ? DATA.activities.filter(a => a.deal_id === deal.id) : [];
        if (!acts.length) { el.innerHTML = '<p style="font-size:0.8rem; color:var(--text-dark);">Sin actividades para este negocio.</p>'; return; }
        const pend = acts.filter(a => a.status === 'Pending').sort((a, b) => parseDT(a.due_date) - parseDT(b.due_date));
        const done = acts.filter(a => a.status === 'Completed').sort((a, b) => parseDT(b.due_date) - parseDT(a.due_date));
        let html = '';
        pend.forEach(a => {
            html += `<div style="display:flex; gap:0.6rem; align-items:flex-start; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:6px; padding:0.6rem 0.75rem; margin-bottom:0.5rem;">
                <span class="act-icon act-${a.type}" style="flex-shrink:0;"><i data-lucide="clock" style="width:13px;height:13px;"></i></span>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:0.82rem; font-weight:600; color:var(--text-main);">${esc(a.subject)}</div>
                    <div style="font-size:0.72rem; color:var(--text-dark);">${actTypeLabel(a.type)} · vence ${fmtDateTime(a.due_date)}</div>
                </div>
                <button class="btn-action btn-sm btn-secondary" onclick="completeActivity(${a.id})" style="flex-shrink:0;">Completar</button>
            </div>`;
        });
        done.forEach(a => {
            html += `<div style="display:flex; gap:0.6rem; align-items:flex-start; opacity:0.7; padding:0.5rem 0.75rem; margin-bottom:0.35rem;">
                <span class="act-icon act-${a.type}" style="flex-shrink:0;"><i data-lucide="check" style="width:13px;height:13px;"></i></span>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:0.8rem; color:var(--text-main); text-decoration:line-through;">${esc(a.subject)}</div>
                    <div style="font-size:0.7rem; color:var(--text-dark);">${actTypeLabel(a.type)} · ${fmtDateTime(a.due_date)}</div>
                </div>
            </div>`;
        });
        el.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function completeActivity(id) {
        fetch('api.php?action=complete_activity', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ activity_id: id })
        }).then(r => r.json()).then(d => {
            if (d.success) reloadKeepDeal();
            else alert('Error: ' + (d.error || 'no se pudo completar'));
        });
    }

    // ---- Documentos / Facturas (nivel cuenta) ----
    function renderDocs() {
        const el = document.getElementById('docs-list');
        if (!DATA.documents.length) { el.innerHTML = '<p style="font-size:0.8rem; color:var(--text-dark);">Sin documentos en el expediente.</p>'; return; }
        el.innerHTML = DATA.documents.map(doc => `
            <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.015); border:1px solid rgba(255,255,255,0.05); border-radius:6px; padding:0.55rem 0.75rem; margin-bottom:0.4rem;">
                <div style="min-width:0;">
                    <div style="font-size:0.8rem; font-weight:600; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${esc(doc.filename)}</div>
                    <div style="font-size:0.68rem; color:var(--text-dark);">${esc(doc.category)} · ${fmtDate(doc.uploaded_at)}</div>
                </div>
                <a href="uploads/${encodeURIComponent(doc.filename)}" target="_blank" class="btn-action btn-sm btn-secondary" style="text-decoration:none; flex-shrink:0;">Ver</a>
            </div>`).join('');
    }

    function renderInvoices() {
        const el = document.getElementById('invoices-list');
        if (!DATA.invoices.length) { el.innerHTML = '<p style="font-size:0.8rem; color:var(--text-dark);">Sin facturas registradas.</p>'; return; }
        el.innerHTML = `<table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
            <thead><tr style="color:var(--text-dark); text-align:left;">
                <th style="padding:0.3rem 0;">Nº</th><th>Vence</th><th style="text-align:right;">Monto</th><th style="text-align:right;">Estado</th>
            </tr></thead><tbody>
            ${DATA.invoices.map(inv => `<tr style="border-top:1px solid rgba(255,255,255,0.04);">
                <td style="padding:0.4rem 0; color:var(--text-main);">${esc(inv.invoice_number)}</td>
                <td style="color:var(--text-muted);">${fmtDate(inv.due_date)}</td>
                <td style="text-align:right; color:var(--text-main);">${money(inv.amount)}</td>
                <td style="text-align:right;">${inv.status === 'Paid'
                    ? '<span class="badge badge-completed">Cobrado</span>'
                    : '<span class="badge badge-pending">Pendiente</span>'}</td>
            </tr>`).join('')}
            </tbody></table>`;
    }

    // ---- Log de contacto ----
    function renderLog(deal) {
        const items = [];
        if (deal) {
            DATA.stage_history.filter(h => h.deal_id === deal.id).forEach(h => items.push({
                date: parseDT(h.entered_at), icon: 'git-commit', title: 'Pasó a fase: ' + h.stage_name,
                meta: (h.changed_by ? 'Por ' + h.changed_by : '') , text: ''
            }));
            DATA.notes.filter(n => n.deal_id === deal.id).forEach(n => items.push({
                date: parseDT(n.created_at), icon: 'sticky-note', title: 'Nota interna', meta: deal.title, text: n.content
            }));
            DATA.emails.filter(e => e.deal_id === deal.id).forEach(e => items.push({
                date: parseDT(e.sent_date), icon: 'mail',
                title: (e.direction === 'Sent' ? 'Correo enviado' : 'Correo recibido') + ': ' + e.subject,
                meta: `De ${e.sender} → ${e.recipient}`, text: e.body
            }));
            DATA.activities.filter(a => a.deal_id === deal.id && a.status === 'Completed').forEach(a => items.push({
                date: parseDT(a.due_date), icon: 'check-circle', title: 'Actividad completada: ' + a.subject,
                meta: actTypeLabel(a.type), text: a.description || ''
            }));
        }
        DATA.invoices.forEach(inv => items.push({
            date: parseDT(inv.created_at || inv.due_date), icon: 'receipt',
            title: 'Factura ' + inv.invoice_number + ' (' + money(inv.amount) + ')',
            meta: inv.status === 'Paid' ? 'Cobrada' : 'Pendiente', text: ''
        }));
        DATA.documents.forEach(doc => items.push({
            date: parseDT(doc.uploaded_at), icon: 'folder', title: 'Documento: ' + doc.filename, meta: doc.category, text: ''
        }));

        items.sort((a, b) => (b.date || 0) - (a.date || 0));
        const el = document.getElementById('contact-log');
        if (!items.length) { el.innerHTML = '<p style="color:var(--text-muted);">Sin registros de contacto todavía.</p>'; return; }
        el.innerHTML = items.map(it => `
            <div class="contact-log-item">
                <span class="cl-icon"><i data-lucide="${it.icon}" style="width:14px;height:14px;"></i></span>
                <div class="cl-body">
                    <div class="cl-title">${esc(it.title)}</div>
                    <div class="cl-meta">${fmtDateTime(it.date)}${it.meta ? ' · ' + esc(it.meta) : ''}</div>
                    ${it.text ? `<div class="cl-text">${esc(it.text)}</div>` : ''}
                </div>
            </div>`).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function reloadKeepDeal() {
        const keep = activeDealId;
        fetch('api.php?action=get_account_detail&account_id=' + ACCOUNT_ID)
            .then(r => r.json()).then(d => {
                if (!d.success) return;
                DATA = normalize(d);
                buildDealSwitch();
                const still = DATA.deals.find(x => x.id === keep);
                selectDeal(still ? still.id : (DATA.deals[0] ? DATA.deals[0].id : null));
            });
    }

    // ---- Modal actividad ----
    const activityModal = document.getElementById('activity-modal');
    function openActivityModal() {
        if (!activeDealId) return;
        document.getElementById('act-deal-id').value = activeDealId;
        activityModal.style.display = 'flex';
        setTimeout(() => activityModal.classList.add('active'), 10);
    }
    function closeActivityModal() {
        activityModal.classList.remove('active');
        setTimeout(() => activityModal.style.display = 'none', 250);
    }
    window.addEventListener('click', e => { if (e.target === activityModal) closeActivityModal(); });

    load();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
