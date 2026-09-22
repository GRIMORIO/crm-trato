<?php
/**
 * index.php — Landing pública de CRM Trato (crmtrato.com).
 * El dashboard autenticado vive ahora en panel.php.
 */
require_once __DIR__ . '/includes/auth.php';
$logged_in = is_logged_in();
$cta_href  = $logged_in ? 'panel.php' : 'login.php';
$cta_label = $logged_in ? 'Ir al panel' : 'Iniciar sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Trato — CRM B2B para equipos de ventas</title>
    <meta name="description" content="Pipeline de ventas visual, seguimiento de actividades, prospección B2B y forecast en una sola herramienta. CRM Trato.">
    <link rel="icon" type="image/svg+xml" href="assets/image/favicon.svg">
    <link rel="alternate icon" type="image/png" href="assets/image/log_azul.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #070a13;
            --surface: rgba(18, 24, 48, 0.55);
            --border: rgba(255, 255, 255, 0.09);
            --text: #f1f5f9;
            --muted: #94a3b8;
            --cyan: #06b6d4;
            --purple: #a855f7;
        }
        body {
            background-color: var(--bg);
            background-image:
                radial-gradient(at 0% 0%, rgba(6, 182, 212, 0.10) 0px, transparent 45%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.10) 0px, transparent 45%);
            background-attachment: fixed;
            color: var(--text);
            font-family: 'Outfit', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 0 1.5rem; }

        /* Header */
        header { padding: 1.5rem 0; }
        .nav { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .brand { display: flex; align-items: center; gap: 0.65rem; font-weight: 700; font-size: 1.15rem; letter-spacing: 0.01em; }
        .brand .mark { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.7rem 1.4rem; border-radius: 8px; font-weight: 600; font-size: 0.95rem;
            text-decoration: none; border: 1px solid transparent; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-primary { background: linear-gradient(135deg, var(--cyan), var(--purple)); color: #fff; box-shadow: 0 6px 20px rgba(6, 182, 212, 0.25); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(6, 182, 212, 0.35); }
        .btn-ghost { background: transparent; color: var(--text); border-color: var(--border); }
        .btn-ghost:hover { border-color: var(--cyan); color: #fff; }

        /* Hero */
        .hero { padding: 5rem 0 4rem; text-align: center; }
        .hero .eyebrow {
            display: inline-block; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--cyan); margin-bottom: 1.25rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 800; line-height: 1.15;
            max-width: 18ch; margin: 0 auto 1.25rem;
            background: linear-gradient(120deg, #fff 30%, #c4b5fd); -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .hero p.sub { font-size: clamp(1rem, 2.2vw, 1.2rem); color: var(--muted); max-width: 52ch; margin: 0 auto 2rem; }
        .hero .actions { display: flex; gap: 0.9rem; justify-content: center; flex-wrap: wrap; }

        /* Features */
        section.features { padding: 3rem 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; }
        .card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            padding: 1.6rem; backdrop-filter: blur(14px);
        }
        .card .ico {
            width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center;
            background: rgba(6, 182, 212, 0.12); color: var(--cyan); font-size: 1.25rem; margin-bottom: 1rem;
        }
        .card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 0.4rem; }
        .card p { font-size: 0.92rem; color: var(--muted); }

        /* Steps */
        section.steps { padding: 3rem 0 4rem; }
        section.steps h2 { text-align: center; font-size: clamp(1.5rem, 3.5vw, 2rem); font-weight: 800; margin-bottom: 2.5rem; }
        .steps-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .step { text-align: center; }
        .step .n {
            width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 0.85rem;
            font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--cyan), var(--purple));
        }
        .step p { color: var(--muted); font-size: 0.92rem; }
        .step strong { display: block; color: var(--text); font-size: 1rem; margin-bottom: 0.25rem; font-weight: 600; }

        /* Footer */
        footer { border-top: 1px solid var(--border); padding: 2rem 0; margin-top: 2rem; }
        .foot { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; font-size: 0.85rem; color: var(--muted); }
        .foot a { color: var(--muted); text-decoration: none; }
        .foot a:hover { color: var(--cyan); }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <nav class="nav">
                <div class="brand">
                    <svg class="mark" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs><linearGradient id="m" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                            <stop offset="0" stop-color="#06b6d4"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs>
                        <rect width="32" height="32" rx="7" fill="url(#m)"/>
                        <rect x="7" y="8" width="18" height="4.6" rx="1.6" fill="#fff"/>
                        <rect x="13.7" y="8" width="4.6" height="16" rx="1.6" fill="#fff"/>
                    </svg>
                    CRM Trato
                </div>
                <a class="btn btn-ghost" href="<?php echo $cta_href; ?>"><?php echo $cta_label; ?></a>
            </nav>
        </header>

        <section class="hero">
            <span class="eyebrow">CRM B2B</span>
            <h1>El CRM donde tu pipeline por fin se ordena</h1>
            <p class="sub">Pipeline visual, seguimiento de actividades, prospección y forecast en una sola herramienta. Sin hojas de cálculo, sin tratos que se caen entre las grietas.</p>
            <div class="actions">
                <a class="btn btn-primary" href="<?php echo $cta_href; ?>"><?php echo $cta_label; ?></a>
                <a class="btn btn-ghost" href="mailto:hola@crmtrato.com?subject=Solicitud%20de%20acceso%20a%20CRM%20Trato">Solicitar acceso</a>
            </div>
        </section>

        <section class="features">
            <div class="grid">
                <div class="card">
                    <div class="ico">📊</div>
                    <h3>Pipeline de ventas</h3>
                    <p>Arrastra oportunidades entre etapas. Valor ponderado por probabilidad de cierre y varios pipelines en paralelo.</p>
                </div>
                <div class="card">
                    <div class="ico">📅</div>
                    <h3>Actividades y seguimiento</h3>
                    <p>Planifica llamadas, reuniones y tareas con recordatorios. Cada oportunidad siempre tiene un próximo paso.</p>
                </div>
                <div class="card">
                    <div class="ico">🔍</div>
                    <h3>Prospectador B2B</h3>
                    <p>Encuentra empresas y contactos nuevos y cárgalos al directorio del CRM en un clic.</p>
                </div>
                <div class="card">
                    <div class="ico">📈</div>
                    <h3>Reportes y forecast</h3>
                    <p>Meta mensual, avance del equipo y proyección de cierre ponderada, en tiempo real.</p>
                </div>
            </div>
        </section>

        <section class="steps">
            <h2>Empezar toma un minuto</h2>
            <div class="steps-row">
                <div class="step">
                    <div class="n">1</div>
                    <strong>Solicita tu cuenta</strong>
                    <p>Escríbenos a hola@crmtrato.com y te damos de alta.</p>
                </div>
                <div class="step">
                    <div class="n">2</div>
                    <strong>Recibes el acceso</strong>
                    <p>Te llega un correo con un enlace seguro para entrar.</p>
                </div>
                <div class="step">
                    <div class="n">3</div>
                    <strong>Defines tu contraseña</strong>
                    <p>Eliges tu clave y empiezas a mover tu pipeline.</p>
                </div>
            </div>
        </section>

        <footer>
            <div class="foot">
                <span>© <?php echo date('Y'); ?> CRM Trato · crmtrato.com</span>
                <span><a href="<?php echo $cta_href; ?>"><?php echo $cta_label; ?></a> · Hecho por <a href="https://napoleoncontreras.com" target="_blank" rel="noopener">napoleoncontreras.com</a></span>
            </div>
        </footer>
    </div>
</body>
</html>
