<?php
/**
 * logout.php — Cierra la sesión y vuelve al login
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

do_logout();
header('Location: login.php');
exit;
