<?php
/**
 * docs.php — Página de Documentación de Procesos y SOP del CRM TIPS
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">

    <!-- INTRODUCCIÓN Y ARQUITECTURA -->
    <div class="card-section">
        <div class="section-header">
            <h2>1. Introducción al CRM y Arquitectura del Sistema</h2>
        </div>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1rem;">
            El **TIPS CRM B2B** es una plataforma comercial personalizada diseñada para consolidar la información de clientes corporativos (panaderías, pastelerías, hoteles, restaurantes y cafeterías de Costa Rica).
        </p>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; margin-top:1.5rem; font-size:0.9rem;">
            <div>
                <h4 style="color:#fff; margin-bottom:0.5rem; font-weight:600;">Stack Tecnológico Local</h4>
                <ul style="margin-left:1.5rem; color:var(--text-muted); display:flex; flex-direction:column; gap:0.25rem;">
                    <li><strong>Backend:</strong> PHP 7.4+ corriendo sobre XAMPP. Conexiones seguras PDO.</li>
                    <li><strong>Base de Datos:</strong> MySQL / MariaDB (Nombre: <code>tips_crm</code>).</li>
                    <li><strong>Frontend:</strong> HTML5, CSS3 vanilla (estilo cian/púrpura oscuro), JavaScript Vanilla.</li>
                    <li><strong>Librerías:</strong> Lucide Icons (iconografía), Chart.js (gráficos analíticos).</li>
                </ul>
            </div>
            <div>
                <h4 style="color:#fff; margin-bottom:0.5rem; font-weight:600;">Esquema de Base de Datos (MySQL)</h4>
                <ul style="margin-left:1.5rem; color:var(--text-muted); display:flex; flex-direction:column; gap:0.25rem;">
                    <li><code>accounts</code>: Empresas y cuentas comerciales B2B.</li>
                    <li><code>contacts</code>: Personas físicas decisoras vinculadas a empresas.</li>
                    <li><code>deals</code>: Oportunidades comerciales con montos y estados.</li>
                    <li><code>stages</code>: Etapas del embudo visual.</li>
                    <li><code>activities</code>: Tareas programadas (Llamadas, Reuniones, Correos).</li>
                    <li><code>web_forms</code>, <code>live_chats</code>, <code>chat_messages</code>: Módulo de Leads y Chat.</li>
                    <li><code>emails</code>: Historial de correspondencia SMTP/IMAP simulada.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- CALIFICACIÓN BANT Y MEDDIC (SOP) -->
    <div class="card-section">
        <div class="section-header">
            <h2>2. Procesos SOP: Marcos de Calificación de Ventas BANT y MEDDIC</h2>
        </div>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem;">
            TIPS CRM admite dos metodologías líderes mundiales de calificación comercial configurables desde el panel de control. Esto permite adaptar el proceso de venta según el volumen o complejidad de la negociación.
        </p>

        <!-- Pestañas informativas o columnas -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; margin-bottom:1.5rem;">
            <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); padding:1.25rem; border-radius:10px;">
                <h3 style="color:#06b6d4; font-size:1.1rem; margin-bottom:0.75rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
                    <span style="background:rgba(6,182,212,0.15); padding:0.2rem 0.5rem; border-radius:4px; font-weight:800;">BANT</span>
                    Marco Clásico (Transaccional)
                </h3>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
                    Recomendado para ventas de insumos recurrentes o equipamiento estándar. Evalúa rápidamente la viabilidad comercial:
                </p>
                <ul style="margin-left:1.25rem; color:var(--text-muted); font-size:0.85rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <li><strong>B — Budget (Presupuesto):</strong> ¿El cliente tiene fondos para comprar?</li>
                    <li><strong>A — Authority (Autoridad):</strong> ¿Hablamos con quien toma la decisión?</li>
                    <li><strong>N — Need (Necesidad):</strong> ¿Tienen un dolor claro en su cocina/pastelería?</li>
                    <li><strong>T — Timeline (Plazo):</strong> ¿Comprará a corto plazo (ej: < 30 días)?</li>
                </ul>
            </div>

            <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); padding:1.25rem; border-radius:10px;">
                <h3 style="color:#a855f7; font-size:1.1rem; margin-bottom:0.75rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
                    <span style="background:rgba(168,85,247,0.15); padding:0.2rem 0.5rem; border-radius:4px; font-weight:800;">MEDDIC</span>
                    Marco Complejo (B2B Corporativo)
                </h3>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1rem;">
                    Recomendado para proyectos de equipamiento pesado o licitaciones de franquicias. Se enfoca en mitigar riesgos:
                </p>
                <ul style="margin-left:1.25rem; color:var(--text-muted); font-size:0.85rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <li><strong>M — Metrics (Métricas):</strong> Beneficio económico cuantificable.</li>
                    <li><strong>E — Economic Buyer (Comprador Económico):</strong> Dueño o decisor financiero.</li>
                    <li><strong>D — Decision Criteria (Criterios de Decisión):</strong> ¿Qué evalúan? (Precio, calidad, etc.)</li>
                    <li><strong>D — Decision Process (Proceso de Decisión):</strong> Pasos y aprobaciones internas.</li>
                    <li><strong>I — Identify Pain (Identificar Dolor):</strong> Problema urgente en su operación.</li>
                    <li><strong>C — Champion (Campeón):</strong> Aliado interno que promueve a TIPS.</li>
                </ul>
            </div>
        </div>

        <div style="background:rgba(6, 182, 212, 0.05); border:1px solid rgba(6, 182, 212, 0.15); padding:1rem; border-radius:8px; font-size:0.875rem; color:var(--text-main);">
            <strong>📌 Operación del Sistema:</strong> 
            El administrador puede alternar el método activo en <a href="settings.php">Configuración > Lead Scoring</a>. Esto cambia automáticamente los indicadores en el <a href="pipeline.php">Embudo de Ventas</a> (letras interactivas sobre cada tarjeta de trato), actualiza los pesos aplicados al Lead Scoring global y adapta el Sales Coach de Brian Tracy en tiempo real.
        </div>
    </div>
    </div>

    <!-- FORMULARIOS DE CAPTURA Y CHAT EN VIVO -->
    <div class="card-section">
        <div class="section-header">
            <h2>3. Integraciones de Generación de Leads</h2>
        </div>
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; font-size:0.9rem;">
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">A. Formularios Web Incrustables</h3>
                <p style="color:var(--text-muted); margin-bottom:1rem;">
                    El <a href="form_builder.php">Constructor de Formularios</a> te permite generar formularios rápidos para tus landing pages de TIPS. Copiando el código de iframe generado, los prospectos alimentan de inmediato el embudo.
                </p>
                <div style="background:rgba(0,0,0,0.3); border:1px solid var(--border-color); padding:0.75rem; border-radius:6px; font-family:monospace; font-size:0.75rem; color:var(--color-primary); overflow-x:auto;">
                    &lt;iframe src="http://localhost/proyectos/crm-tips/form_embed.php?id=1" width="100%" height="450px" frameborder="0"&gt;&lt;/iframe&gt;
                </div>
            </div>
            
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">B. Chat en Vivo con Inteligencia Artificial</h3>
                <p style="color:var(--text-muted); margin-bottom:1rem;">
                    El chat en vivo incrustable permite interactuar directamente con el lead. Si no hay asesores atendiendo la <a href="chat_console.php">Consola de Chat</a>, un bot con Inteligencia Artificial (OpenAI GPT-4o-mini) asume la conversación, responde sobre los colorantes Enco o batidoras KitchenAid y le solicita el teléfono/correo al cliente para guardarlo.
                </p>
                <p style="color:var(--text-muted); font-size:0.8rem;">
                    Para cargar el chat flotante en cualquier sitio web público de TIPS, añade este script al final del HTML de tu página:
                </p>
                <div style="background:rgba(0,0,0,0.3); border:1px solid var(--border-color); padding:0.75rem; border-radius:6px; font-family:monospace; font-size:0.75rem; color:var(--color-secondary); overflow-x:auto; margin-top:0.5rem;">
                    &lt;script src="http://localhost/proyectos/crm-tips/widget_chat.js"&gt;&lt;/script&gt;
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULOS DE CORREO Y WHATSAPP -->
    <div class="card-section">
        <div class="section-header">
            <h2>4. Canales de Comunicación Directa (Correo & WhatsApp)</h2>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; font-size:0.9rem; color:var(--text-muted);">
            <div>
                <h4 style="color:#fff; font-weight:600; margin-bottom:0.5rem;">Bandeja de Correo Integrada</h4>
                <p>
                    Desde el módulo de <a href="email_inbox.php">Bandeja Correo</a> puedes redactar correos salientes y asociarlos de forma directa a la oportunidad de venta (Deal). El sistema guarda todo el historial de interacciones con el cliente.
                </p>
            </div>
            <div>
                <h4 style="color:#fff; font-weight:600; margin-bottom:0.5rem;">Plantillas de WhatsApp en Directorio</h4>
                <p>
                    En el <a href="accounts.php">Directorio B2B</a>, al presionar el icono de mensaje al lado de cualquier teléfono, el sistema despliega un menú con plantillas pre-configuradas (ej. Contacto Inicial, envío de cotización) y te permite abrir la conversación de WhatsApp con los datos del cliente auto-rellenados.
                </p>
            </div>
        </div>
    </div>

    <!-- PERMISOS JERÁRQUICOS Y GRUPOS DE VISIBILIDAD -->
    <div class="card-section">
        <div class="section-header">
            <h2>5. Permisos Jerárquicos y Grupos de Visibilidad (Equipos)</h2>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; font-size:0.9rem; color:var(--text-muted);">
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">Control de Acceso Basado en Roles (RBAC)</h3>
                <p>
                    TIPS CRM implementa políticas de seguridad jerárquica para proteger las cuentas comerciales y los tratos corporativos en producción:
                </p>
                <ul style="list-style-type: disc; padding-left: 1.2rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <li><strong>Rol de Administrador (Admin)</strong>: Acceso total e irrestricto. Puede visualizar, editar y eliminar cualquier oportunidad comercial, cuenta o contacto de todas las zonas de Costa Rica.</li>
                    <li><strong>Rol de Asesor (Agent)</strong>: Visibilidad estrictamente restringida. Solo tienen acceso a las cuentas y tratos comerciales que tienen asignados como su <code>assigned_agent</code>.</li>
                </ul>
            </div>
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">Regiones y Cuentas Sembradas</h3>
                <p>
                    El sistema está configurado y sembrado con los siguientes usuarios de acceso (contraseña temporal común: <code>tips2026</code>):
                </p>
                <ul style="list-style-type: circle; padding-left: 1.2rem; margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <li><strong>napoleon</strong> (Rol: <code>admin</code>) — Meta: $30,000 global.</li>
                    <li><strong>andres</strong> (Rol: <code>agent</code>) — Asignado a la zona: <em>Andrés Herrera (GAM Norte)</em>.</li>
                    <li><strong>carlos</strong> (Rol: <code>agent</code>) — Asignado a la zona: <em>Carlos Mendoza (Zona Costa)</em>.</li>
                    <li><strong>sofia</strong> (Rol: <code>agent</code>) — Asignado a la zona: <em>Sofía Castro (GAM Oriente)</em>.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- FUNNEL DE MARKETING Y VENTAS -->
    <div class="card-section">
        <div class="section-header">
            <h2>6. El Funnel de Marketing y Ventas — Ciclo de Vida de un Lead</h2>
        </div>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem;">
            Todo cliente potencial de TIPS (panadería, pastelería, hotel, restaurante o cafetería) recorre las mismas etapas de madurez comercial, de menos a más comprometido. Cada salto tiene un <strong>criterio de entrada verificable</strong> y un <strong>dueño claro</strong> — marketing genera y filtra la demanda, el asesor de zona la acepta y la califica. Esto evita dos problemas típicos: mandar cotizaciones a quien todavía no decide, y descartar clientes que solo necesitaban seguimiento.
        </p>

        <!-- FLUJO VISUAL -->
        <div style="display:flex; flex-wrap:wrap; align-items:stretch; gap:0.5rem; margin-bottom:1.75rem;">
            <div style="flex:1 1 130px; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:8px; padding:0.75rem; text-align:center;">
                <span style="display:block; color:var(--color-secondary); font-weight:800; font-size:0.8rem; letter-spacing:0.03em;">LEAD</span>
                <span style="font-size:0.72rem; color:var(--text-muted);">Contacto capturado</span>
            </div>
            <div style="align-self:center; color:var(--text-muted);">&rarr;</div>
            <div style="flex:1 1 130px; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:8px; padding:0.75rem; text-align:center;">
                <span style="display:block; color:var(--color-secondary); font-weight:800; font-size:0.8rem; letter-spacing:0.03em;">MQL</span>
                <span style="font-size:0.72rem; color:var(--text-muted);">Marketing lo aprueba</span>
            </div>
            <div style="align-self:center; color:var(--text-muted);">&rarr;</div>
            <div style="flex:1 1 130px; background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:8px; padding:0.75rem; text-align:center;">
                <span style="display:block; color:var(--color-secondary); font-weight:800; font-size:0.8rem; letter-spacing:0.03em;">SAL</span>
                <span style="font-size:0.72rem; color:var(--text-muted);">El asesor lo acepta</span>
            </div>
            <div style="align-self:center; color:var(--text-muted);">&rarr;</div>
            <div style="flex:1 1 130px; background:rgba(6,182,212,0.08); border:1px solid rgba(6,182,212,0.25); border-radius:8px; padding:0.75rem; text-align:center;">
                <span style="display:block; color:var(--color-primary); font-weight:800; font-size:0.8rem; letter-spacing:0.03em;">SQL</span>
                <span style="font-size:0.72rem; color:var(--text-muted);">Oportunidad real (BANT/MEDDIC)</span>
            </div>
            <div style="align-self:center; color:var(--text-muted);">&rarr;</div>
            <div style="flex:1 1 130px; background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:0.75rem; text-align:center;">
                <span style="display:block; color:#10b981; font-weight:800; font-size:0.8rem; letter-spacing:0.03em;">CERRADO</span>
                <span style="font-size:0.72rem; color:var(--text-muted);">Ganado / Perdido</span>
            </div>
        </div>

        <!-- DEFINICIONES DEL FUNNEL -->
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <div style="border-left:3px solid var(--color-secondary); padding-left:1rem;">
                <strong style="color:#fff;">Lead (Prospecto)</strong>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">
                    Cualquier persona o negocio del que se capturaron datos de contacto, sin filtrar todavía. Puede venir de un formulario web, del chat con IA de la tienda, de una feria del sector, de un referido o de una base de datos de outbound del asesor.
                    <br><em style="color:var(--text-muted);">Criterio de entrada:</em> hay un nombre de negocio y una forma de contactarlo.
                    <br><em style="color:var(--text-muted);">Dueño:</em> Marketing / punto de entrada. &nbsp;<em style="color:var(--text-muted);">En el CRM:</em> registros de <code>web_forms</code> / <code>live_chats</code>, o un deal en la primera etapa del embudo (<em>Contacto Inicial</em>).
                </p>
            </div>
            <div style="border-left:3px solid var(--color-secondary); padding-left:1rem;">
                <strong style="color:#fff;">MQL — Marketing Qualified Lead</strong>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">
                    Lead que marketing considera listo para que un asesor lo trabaje, por dos razones a la vez: <strong>(a)</strong> encaja con el perfil de cliente objetivo de TIPS (tipo de negocio, volumen de compra estimado, zona atendible) y <strong>(b)</strong> mostró una señal de intención real — pidió precios, consultó por un producto (ej. colorantes, batidoras, moldes), descargó un catálogo o dejó su contacto en el chat. Un negocio que encaja pero nunca preguntó nada <u>no</u> es MQL; un negocio muy interesado fuera del perfil o de zona tampoco.
                    <br><em style="color:var(--text-muted);">Criterio de entrada:</em> encaja con el perfil objetivo + hay una acción de intención registrada.
                    <br><em style="color:var(--text-muted);">Dueño:</em> Marketing, hasta que el asesor lo acepte. &nbsp;<em style="color:var(--text-muted);">En el CRM:</em> deal con la fuente del lead marcada y una nota de por qué encaja, listo para asignar a un asesor de zona.
                </p>
            </div>
            <div style="border-left:3px solid var(--color-secondary); padding-left:1rem;">
                <strong style="color:#fff;">SAL — Sales Accepted Lead (Lead Aceptado por Ventas)</strong>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">
                    El MQL que el <strong>asesor de zona revisó y aceptó formalmente</strong> como digno de invertir tiempo (visita, llamada, muestra). Es el apretón de manos del acuerdo (SLA) entre marketing y el equipo comercial: si el lead no cumple los criterios pactados — no es la persona que compra, no hay volumen, está fuera de zona atendible — el asesor lo <strong>devuelve a marketing con un motivo</strong>, no lo deja sin gestionar. Esta etapa es la que corta la discusión "marketing me pasa contactos que no sirven" / "el asesor no trabaja lo que le asigno".
                    <br><em style="color:var(--text-muted);">Criterio de entrada:</em> el asesor confirmó que vale el contacto y agendó el primer paso (llamada, visita o envío de muestra).
                    <br><em style="color:var(--text-muted);">Dueño:</em> Asesor de zona (<code>assigned_agent</code>). &nbsp;<em style="color:var(--text-muted);">En el CRM:</em> deal asignado y movido a <em>Calificación BANT</em> / <em>Demostración</em> con la actividad de contacto agendada.
                </p>
            </div>
            <div style="border-left:3px solid var(--color-primary); padding-left:1rem;">
                <strong style="color:#fff;">SQL — Sales Qualified Lead (Oportunidad)</strong>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">
                    Tras el primer contacto, el asesor calificó el lead con el marco activo — <strong>BANT</strong> (compra recurrente / equipo estándar) o <strong>MEDDIC</strong> (equipamiento pesado / licitación) — y lo convirtió en una <strong>oportunidad real</strong>: hay presupuesto, la persona que decide en la mesa, un dolor concreto en su cocina/operación y una fecha de compra. Recién aquí se arma la cotización formal.
                    <br><em style="color:var(--text-muted);">Criterio de entrada:</em> las letras del marco activo confirmadas (visibles en la tarjeta del embudo) — no antes.
                    <br><em style="color:var(--text-muted);">Dueño:</em> Asesor de zona. &nbsp;<em style="color:var(--text-muted);">En el CRM:</em> deal con el Lead Scoring alto y la insignia de calificación; avanza por <em>Propuesta Comercial</em> y <em>Negociación y Cierre</em>.
                </p>
            </div>
            <div style="border-left:3px solid #10b981; padding-left:1rem;">
                <strong style="color:#fff;">Cerrado - Ganado</strong>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">
                    La oportunidad se cerró: orden de compra firmada o pedido confirmado. Registrar siempre qué se vendió y por cuánto. Aquí arranca la relación de cuenta: reposición, cross-sell de otras líneas, y el asesor como contacto de servicio.
                    <br><em style="color:var(--text-muted);">En el CRM:</em> es el campo <code>status = 'Won'</code> del deal, <u>no</u> una etapa del embudo.
                </p>
            </div>
            <div style="border-left:3px solid #ef4444; padding-left:1rem;">
                <strong style="color:#fff;">Cerrado - Perdido</strong>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">
                    La oportunidad no se concretó: eligió a otro proveedor, el precio no cerró, se pospuso la compra sin fecha nueva, o se enfrió sin respuesta. <strong>Registrar el motivo siempre.</strong> Revisar los motivos acumulados cada mes dice si el problema es de precio, de catálogo, de tiempos de entrega o de zona.
                    <br><em style="color:var(--text-muted);">En el CRM:</em> <code>status = 'Lost'</code> + motivo. La oportunidad <strong>nunca se borra</strong>: se archiva perdida.
                </p>
            </div>
        </div>

        <div style="background:rgba(6, 182, 212, 0.05); border:1px solid rgba(6, 182, 212, 0.15); padding:1rem; border-radius:8px; font-size:0.85rem; margin-top:1.5rem;">
            <strong>📌 Perdido ≠ Descalificado.</strong> Un lead que <strong>nunca llegó a SQL</strong> porque hoy no tiene presupuesto o no es temporada de compra no es una oportunidad perdida — es un <strong>descalificado / seguimiento</strong>: sale del embudo activo pero se guarda para retomarlo (arranca temporada alta, abre un local nuevo, cambia el encargado de compras). "Cerrado - Perdido" se reserva para oportunidades que <em>sí</em> estaban calificadas y se cayeron; mezclarlas distorsiona la tasa de cierre de cada asesor.
        </div>
    </div>

    <!-- DICCIONARIO B2B Y ABREVIACIONES -->
    <div class="card-section">
        <div class="section-header">
            <h2>7. Glosario de Ventas B2B e Índice de Abreviaciones</h2>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:2rem; font-size:0.9rem;">
            <!-- DICCIONARIO ALFABÉTICO -->
            <div>
                <h3 style="color:#fff; font-size:1.05rem; margin-bottom:1rem; font-weight:600; border-bottom:1px dashed var(--border-color); padding-bottom:0.25rem;">Diccionario de Ventas B2B</h3>
                <div style="display:flex; flex-direction:column; gap:0.75rem; max-height:460px; overflow-y:auto; padding-right:0.5rem;">
                    <div>
                        <strong style="color:var(--color-primary);">A — Account (Cuenta B2B)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Registro de una empresa o cliente corporativo (ej. un hotel o una cadena de panaderías) con el que se hace negocio, a diferencia de una persona individual.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">B — BANT (Filtro de Calificación)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Marco clásico para calificar prospectos validando cuatro parámetros: Presupuesto (Budget), Autoridad (Authority), Necesidad (Need) y Plazo (Timeline). Recomendado para compra recurrente o equipo estándar. Es el criterio que convierte un SAL en SQL.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">C — Cerrado - Ganado / Cerrado - Perdido</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Estados finales de una oportunidad. En el CRM son el campo <code>status</code> del deal (<code>Won</code> / <code>Lost</code>), no etapas del embudo. "Perdido" exige un motivo y nunca implica borrar el deal; "Ganado" abre la gestión de cuenta.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">C — Comité de compras (Buying Committee)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">El conjunto de personas que influye en una decisión de compra, no solo quien firma la orden. En TIPS suele incluir al encargado de compras, al chef o jefe de cocina que pide el producto y al dueño/gerente que aprueba el monto. En equipamiento pesado o licitaciones el comité es más grande — ahí aplica MEDDIC (Economic Buyer + Champion). Mapear quién opina antes de mandar la cotización evita sorpresas de último minuto.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">C — Contact (Contacto B2B)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Persona decisora o influenciadora que trabaja para una Cuenta B2B (ej. el Chef Pastelero, el encargado de compras o el Gerente General).</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">D — Deal (Negocio / Oportunidad)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Una oportunidad de venta abierta en el CRM, con valor monetario y fecha de cierre estimada. Puede vivir en cualquiera de los embudos configurados.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">E — Evaluación de opciones</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Segunda fase del <strong>proceso de compra del cliente</strong>: ya reconoció que necesita el producto y ahora compara proveedores, marcas y precios (TIPS vs. otro distribuidor, importar directo, seguir con lo que ya usa). La jugada del asesor acá es la demostración o el envío de muestra, no solo pasar lista de precios.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">F — Forecast (Pronóstico de ventas)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Proyección de qué oportunidades va a cerrar cada asesor, cuándo y por cuánto — la base de la meta mensual por zona. En el CRM se apoya en el <strong>pronóstico ponderado</strong> del pipeline (valor × probabilidad de la etapa). Un deal no debe quedarse con fecha de cierre vencida sin reclasificarlo: es la causa #1 de que un pronóstico pierda credibilidad.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">L — Lead (Prospecto)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Persona o negocio del cual se capturaron datos de contacto pero que aún no se filtró ni calificó. Primer estado del funnel.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">M — MEDDIC (Calificación Corporativa)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Marco de calificación para ventas de alto valor y complejidad: Metrics (beneficio cuantificable), Economic Buyer (decisor financiero), Decision Criteria/Process, Identify Pain y Champion (aliado interno). Recomendado para equipamiento pesado o licitaciones.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">M — MQL (Marketing Qualified Lead)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Lead que marketing aprueba para pasar a un asesor porque encaja con el perfil objetivo <em>y</em> mostró una señal de intención real. Sigue siendo responsabilidad de marketing hasta que el asesor lo acepte (SAL).</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">N — Negociación</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Fase final antes del cierre: el cliente respondió con una objeción de precio, pidió ajustar cantidades/condiciones o comparó con una contraoferta. Es una etapa del embudo (<em>Negociación y Cierre</em>). Riesgo si se estanca ahí sin una fecha concreta de decisión.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">P — Pipeline (Embudo de Ventas)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">El flujo visual en el que se ordenan y gestionan las oportunidades a través de sus etapas. TIPS CRM admite varios embudos en paralelo (ej. insumos vs. equipamiento).</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">R — Reconocimiento de necesidades</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Primera fase del proceso de compra del cliente: se da cuenta de que necesita reponer un insumo, cambiar un equipo que falla o abastecer un local nuevo. La prospección y el contenido de la parte alta del embudo existen para llegar justo en ese momento. Se corresponde con el paso de <strong>Lead</strong> a <strong>MQL</strong>.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">R — Resolución de dudas</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Fase en la que el cliente, ya casi decidido, saca a la mesa los riesgos ("¿tienen stock siempre?", "¿el repuesto llega rápido?", "¿la garantía cómo funciona?"). El asesor las responde antes de que frenen la compra — idealmente ya anticipadas en la propuesta. Coincide con las etapas <strong>Propuesta Comercial</strong> → <strong>Negociación y Cierre</strong>.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">S — SAL (Sales Accepted Lead)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">MQL que el asesor de zona revisó y aceptó formalmente como válido para trabajar. Es el contrato de servicio (SLA) marketing&rarr;ventas: si no cumple los criterios acordados, el asesor lo devuelve con un motivo en vez de dejarlo sin gestión.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">S — SQL (Sales Qualified Lead)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">Lead que el asesor calificó con BANT o MEDDIC y convirtió en oportunidad real, con presupuesto, decisor, necesidad y plazo confirmados. Recién aquí se arma la cotización formal.</p>
                    </div>
                    <div>
                        <strong style="color:var(--color-primary);">S — SLA (Acuerdo de Nivel de Servicio)</strong>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.25rem;">El acuerdo explícito entre marketing y el equipo comercial sobre qué es un MQL válido, en cuánto tiempo el asesor lo acepta o rechaza, y qué pasa con los rechazados. Es lo que hace real la etapa SAL.</p>
                    </div>
                </div>
            </div>

            <!-- ÍNDICE DE ABREVIACIONES -->
            <div style="background:rgba(255,255,255,0.01); border:1px solid var(--border-color); padding:1.25rem; border-radius:10px;">
                <h3 style="color:#fff; font-size:1.05rem; margin-bottom:1rem; font-weight:600; border-bottom:1px dashed var(--border-color); padding-bottom:0.25rem;">Índice de Abreviaciones</h3>
                <ul style="list-style:none; display:flex; flex-direction:column; gap:0.75rem; font-size:0.85rem;">
                    <li><strong style="color:var(--color-secondary);">B2B</strong> — Business to Business (Negocios entre empresas).</li>
                    <li><strong style="color:var(--color-secondary);">CRM</strong> — Customer Relationship Management (Gestor de Relación con Clientes).</li>
                    <li><strong style="color:var(--color-secondary);">HORECA</strong> — Hoteles, Restaurantes y Catering (Sector de servicios de comida).</li>
                    <li><strong style="color:var(--color-secondary);">MQL</strong> — Marketing Qualified Lead (Lead calificado por marketing).</li>
                    <li><strong style="color:var(--color-secondary);">SAL</strong> — Sales Accepted Lead (Lead aceptado por ventas).</li>
                    <li><strong style="color:var(--color-secondary);">SQL</strong> — Sales Qualified Lead (Lead calificado por ventas / oportunidad).</li>
                    <li><strong style="color:var(--color-secondary);">SLA</strong> — Service Level Agreement (Acuerdo marketing&ndash;ventas).</li>
                    <li><strong style="color:var(--color-secondary);">SOP</strong> — Standard Operating Procedure (Procedimiento Operativo Estándar).</li>
                    <li><strong style="color:var(--color-secondary);">BANT</strong> — Budget, Authority, Need, Timeline (Filtro de calificación).</li>
                    <li><strong style="color:var(--color-secondary);">MEDDIC</strong> — Metrics, Economic Buyer, Decision Criteria/Process, Pain, Champion (Calificación corporativa).</li>
                    <li><strong style="color:var(--color-secondary);">ROI</strong> — Return On Investment (Retorno sobre la Inversión).</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ANALÍTICA DEL EMBUDO -->
    <div class="card-section">
        <div class="section-header">
            <h2>8. Analítica del Embudo — Conversión, Volumen y Velocidad</h2>
        </div>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1rem;">
            <a href="analytics.php">Analítica del Embudo</a> (solo administradores) mide cómo se mueven los negocios por el proceso de ventas (§6). Se alimenta del historial de fases (<code>deal_stage_history</code>) que se registra en cada cambio de etapa del <a href="pipeline.php">Embudo</a> — no hay nada que configurar. Filtros: rango de fechas y <strong>embudo</strong> (funciona por separado para cada uno de los embudos configurados).
        </p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; font-size:0.9rem;">
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">1. Volumen por etapa</h3>
                <p style="color:var(--text-muted);">Cuántos negocios <strong>entraron</strong> a cada fase en el período (flujo) frente a cuántos están abiertos ahí hoy. Más oportunidades/cuentas creadas y ganados/perdidos del período.</p>
            </div>
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">2. Conversión de cohorte</h3>
                <p style="color:var(--text-muted);">Del grupo de negocios <strong>creados</strong> en el rango, qué porcentaje alcanzó cada etapa posterior. La <strong>etapa con mayor fuga</strong> se resalta en rojo.</p>
            </div>
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">3. Tiempo entre etapas</h3>
                <p style="color:var(--text-muted);">Días media y mediana en cada fase, ciclo de venta (Ganados y Perdidos por separado), tiempo hasta el primer contacto, y tabla de <strong>oportunidades estancadas</strong> (abiertas sin moverse más de máx. 21 días o 1.5× la mediana de su etapa).</p>
            </div>
            <div>
                <h3 style="color:#fff; font-size:1rem; margin-bottom:0.5rem; font-weight:600;">4. Velocidad del pipeline</h3>
                <p style="color:var(--text-muted);">(Oportunidades calificadas × valor medio × win rate) ÷ ciclo medio en días — cuánto valor produce el embudo por día.</p>
            </div>
        </div>
        <div style="background:rgba(6, 182, 212, 0.05); border:1px solid rgba(6, 182, 212, 0.15); padding:1rem; border-radius:8px; font-size:0.85rem; margin-top:1.25rem;">
            <strong>📌 Lectura fina:</strong> para negocios anteriores a que se activara <code>deal_stage_history</code> los días por fase son una estimación (backfill). El "alcanzó la etapa N" usa la posición <em>actual</em> de las etapas. La vista por zona/asesor llega en una fase posterior — hoy es solo para administradores y muestra todo el embudo.
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
