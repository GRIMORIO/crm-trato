# CRM Trato

CRM comercial B2B liviano, escrito en **PHP + MySQL sin framework ni build**. Gestiona
cuentas de empresa, contactos, un pipeline de ventas multi-embudo, actividades/agenda,
formularios embebibles, chat en vivo y analítica del embudo.

Cada página es un archivo PHP servido por Apache; el front usa HTML + CSS + JS vanilla
(Chart.js por CDN en la analítica). No hay Composer, npm ni pasos de compilación.

## Requisitos

- PHP 8.0+ con PDO MySQL
- MySQL / MariaDB
- Apache con `mod_rewrite` y `mod_headers` (para el `.htaccess` incluido)

En local funciona tal cual sobre XAMPP / Laragon / MAMP.

## Instalación

```bash
git clone https://github.com/<usuario>/crm-trato.git
cd crm-trato
cp config.example.php config.php     # y editá las credenciales
```

Luego, con el sitio servido por Apache, abrí una vez:

```
http://localhost/crm-trato/setup.php
```

`setup.php` crea todas las tablas y siembra datos de demostración (usuarios de prueba,
cuentas, deals). La contraseña temporal común de los usuarios sembrados se muestra en
`docs.php`. **Cambiala antes de exponer el sitio.**

Después de instalar, entrá por `login.php`. El panel autenticado es `panel.php`;
`index.php` es una landing pública.

### Migraciones

Los scripts `update_*.php` son migraciones aditivas de un solo uso (una por feature).
Se corren manualmente vía navegador contra la base y **no deberían quedar accesibles en
producción** — stubbealos a HTTP 410 o borralos tras usarlos, igual que `setup.php`.

## Configuración

Todo lo sensible vive en `config.php` (ignorado por git). A partir de ahí, la mayoría de
los ajustes operativos (SMTP, claves de API de integraciones, metas de ventas) se editan
desde **Configuración** dentro del CRM y se guardan en la tabla `crm_settings`.

> Las claves en `crm_settings` (`smtp_pass`, `openai_api_key`, etc.) se guardan en texto
> plano. Si vas a producción con datos reales, evaluá cifrado en reposo.

## Estructura

| Área | Archivos |
|---|---|
| Auth / cuenta | `login.php` · `logout.php` · `recover_password.php` · `profile.php` · `includes/auth.php` |
| Dashboard | `panel.php` · `index.php` (landing pública) |
| Pipeline | `pipeline.php` (kanban multi-embudo, drag & drop) |
| Empresas | `accounts.php` · `account.php` (ficha con timeline de embudo) |
| Actividades | `activities.php` · `ical.php` (feed iCal) |
| Analítica | `analytics.php` · `includes/analytics.php` |
| Reportes | `reports.php` |
| Formularios | `form_builder.php` · `form_embed.php` (embebible) |
| Chat en vivo | `widget_chat.php` · `widget_chat.js` · `chat_console.php` · `api_chat.php` |
| Correo | `email_inbox.php` · `includes/smtp_mailer.php` (SMTP por sockets, sin dependencias) |
| Admin | `settings.php` · `users.php` |
| API interna | `api.php` |
| Instalación | `setup.php` · `update_*.php` |
| Deploy | `deploy_tus.sh` (subida archivo por archivo vía TUS de Hostinger) |

## Seguridad

- `.htaccess`: `X-Robots-Tag: noindex` en todo el sitio + bloqueo HTTP directo de
  `config.php` + `robots.txt` con `Disallow: /`. Es una herramienta interna, no un sitio
  para indexar.
- Control de acceso por rol: `admin` ve todo; `agent` solo ve cuentas/deals donde es el
  `assigned_agent`.
- No subas `config.php`, `config.production.php`, `setup.php` ni `update_*.php` a un
  servidor público.

## Licencia

MIT — ver [LICENSE](LICENSE).
