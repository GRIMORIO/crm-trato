<?php
/**
 * smtp_mailer.php — Cliente SMTP mínimo (sin dependencias externas) para envío real de correo.
 *
 * Implementación directa por sockets (EHLO/STARTTLS/AUTH LOGIN/MAIL FROM/RCPT TO/DATA) porque
 * este workspace no usa Composer/librerías externas.
 */

function smtp_is_configured() {
    return defined('SMTP_HOST') && !empty(SMTP_HOST)
        && defined('SMTP_USER') && !empty(SMTP_USER)
        && defined('SMTP_PASS') && !empty(SMTP_PASS)
        && defined('SMTP_FROM_EMAIL') && !empty(SMTP_FROM_EMAIL);
}

// Lee una respuesta SMTP completa (multi-línea, formato "250-..." / "250 ...")
function smtp_read_response($socket) {
    $data = '';
    while ($line = fgets($socket, 515)) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_expect_code($response, $expected_code) {
    return substr($response, 0, 3) === (string) $expected_code;
}

/**
 * Envía un correo real por SMTP. Devuelve true/false; en caso de error, $error_out recibe el detalle.
 */
function smtp_send_mail($to, $subject, $body, &$error_out = null) {
    if (!smtp_is_configured()) {
        $error_out = 'SMTP no está configurado en config.php.';
        return false;
    }

    $secure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $from_name = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'TIPS CRM';

    $host = ($secure === 'ssl' ? 'ssl://' : '') . SMTP_HOST;
    $socket = @stream_socket_client($host . ':' . $port, $errno, $errstr, 10);
    if (!$socket) {
        $error_out = "No se pudo conectar a " . SMTP_HOST . ":" . $port . " ({$errstr})";
        return false;
    }

    $response = smtp_read_response($socket);
    if (!smtp_expect_code($response, 220)) {
        $error_out = "Saludo SMTP inesperado: {$response}";
        fclose($socket);
        return false;
    }

    $local_domain = 'localhost';
    fwrite($socket, "EHLO {$local_domain}\r\n");
    smtp_read_response($socket);

    if ($secure === 'tls') {
        fwrite($socket, "STARTTLS\r\n");
        $response = smtp_read_response($socket);
        if (!smtp_expect_code($response, 220)) {
            $error_out = "El servidor rechazó STARTTLS: {$response}";
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $error_out = 'No se pudo iniciar TLS.';
            fclose($socket);
            return false;
        }
        fwrite($socket, "EHLO {$local_domain}\r\n");
        smtp_read_response($socket);
    }

    fwrite($socket, "AUTH LOGIN\r\n");
    smtp_read_response($socket);
    fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
    smtp_read_response($socket);
    fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
    $response = smtp_read_response($socket);
    if (!smtp_expect_code($response, 235)) {
        $error_out = "Autenticación SMTP rechazazada — revisá usuario/contraseña de aplicación en config.php. Detalle: {$response}";
        fclose($socket);
        return false;
    }

    fwrite($socket, "MAIL FROM:<" . SMTP_FROM_EMAIL . ">\r\n");
    $response = smtp_read_response($socket);
    if (!smtp_expect_code($response, 250)) {
        $error_out = "MAIL FROM rechazado: {$response}";
        fclose($socket);
        return false;
    }

    fwrite($socket, "RCPT TO:<{$to}>\r\n");
    $response = smtp_read_response($socket);
    if (!smtp_expect_code($response, 250) && !smtp_expect_code($response, 251)) {
        $error_out = "RCPT TO rechazado (¿destinatario inválido?): {$response}";
        fclose($socket);
        return false;
    }

    fwrite($socket, "DATA\r\n");
    $response = smtp_read_response($socket);
    if (!smtp_expect_code($response, 354)) {
        $error_out = "DATA rechazado: {$response}";
        fclose($socket);
        return false;
    }

    $from_header = $from_name ? ($from_name . ' <' . SMTP_FROM_EMAIL . '>') : SMTP_FROM_EMAIL;
    $headers = "From: {$from_header}\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    // Escapar líneas que empiecen con "." (terminador de DATA en SMTP)
    $escaped_body = str_replace("\n.", "\n..", $body);

    fwrite($socket, $headers . "\r\n" . $escaped_body . "\r\n.\r\n");
    $response = smtp_read_response($socket);
    if (!smtp_expect_code($response, 250)) {
        $error_out = "El servidor no confirmó el envío: {$response}";
        fclose($socket);
        return false;
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}
