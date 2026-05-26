<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Prueba de conexión y Login</h3>";

try {
    // Intentamos conectar solo a la BD de Maker
    $pdo = new PDO("mysql:host=localhost;dbname=u496635941_fi_maker;charset=utf8", "u496635941_webuser", "Fichajesmaker123");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>1. Conexión a BD: ÉXITO</p>";

    // Intentamos buscar al usuario
    $stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE usuario = ?");
    $stmt->execute(['rrhh_maker']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "<p style='color:green;'>2. Usuario 'rrhh_maker' encontrado: ÉXITO</p>";
        
        // Verificamos la contraseña
        if (password_verify('123456', $user['Contrasena'])) {
            echo "<p style='color:green;'>3. Contraseña: ÉXITO. ¡El login funciona a nivel de código base!</p>";
        } else {
            echo "<p style='color:red;'>3. FALLO: La contraseña '123456' no cuadra con el hash guardado en phpMyAdmin.</p>";
        }
    } else {
        echo "<p style='color:red;'>2. FALLO: El usuario 'rrhh_maker' no existe en la tabla. (Ojo: revisa si la tabla se llama 'usuarios' en minúscula y cambia el código).</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red;'>ERROR CRÍTICO: " . $e->getMessage() . "</p>";
}
?>