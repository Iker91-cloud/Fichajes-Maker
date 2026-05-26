<?php
date_default_timezone_set('Europe/Madrid');

// 1. EN AZURE NO CONFIGURAMOS 'session.save_path' MANUALMENTE.
// Al quitar las líneas de $tmp_path, obligamos a PHP a usar la carpeta nativa '/tmp'
// del contenedor de Azure, evitando fallos de bloqueo de archivos en red.

// 2. DETECCIÓN CORRECTA DE HTTPS DETRÁS DEL BALANCEADOR DE AZURE
// Comprobamos tanto la variable nativa como la cabecera que inyecta el proxy de Azure
$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Establecer las configuraciones de las cookies de sesión antes de iniciarla
ini_set('session.gc_maxlifetime', 3600); // 1 hora de duración de la sesión
ini_set('session.cookie_lifetime', 0);   // Se destruye al cerrar el navegador
ini_set('session.cookie_secure', $is_https ? 1 : 0); // Ajustado para el balanceador de Azure
ini_set('session.cookie_httponly', 1);   // No accesible por JavaScript
ini_set('session.use_strict_mode', 1);   // Modo estricto anti-fijación
ini_set('session.cookie_samesite', 'Lax'); // Protección CSRF

// Iniciar la sesión si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destruir la sesión si el usuario ha salido
if (isset($_GET['logout'])) {
    session_unset();   // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión en el servidor

    header('Location: login.php'); // Redirigir al usuario a la página de inicio de sesión
    exit;
}
?>