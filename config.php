<?php
// config.php
$db_host = 'localhost';
$db_name = $_SESSION['db_name'];  // Base de datos identificada en el login
$db_username = $_SESSION['db_user']; // Usuario específico de la BD identificado en el login
$db_password = 'Fichajesmaker123'; // Tu nueva contraseña de Hostinger

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error conectando a la base de datos: " . $e->getMessage());
}
?>
