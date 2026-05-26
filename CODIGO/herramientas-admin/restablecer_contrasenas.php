<?php
require_once '../session_config.php';

// Definir las credenciales de la base de datos
$dbhost = $_SESSION['db_host'];
$dbuser = $_SESSION['db_user'];
$dbpass = $_SESSION['db_pass'];
$dbname = $_SESSION['db_name'];

// Crear una nueva instancia de PDO para la conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("No se pudo conectar a la base de datos $dbname: " . $e->getMessage());
}

// Función para generar una contraseña aleatoria
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    return substr(str_shuffle($chars), 0, $length);
}

// Función para enviar un correo electrónico con la nueva contraseña
function sendEmail($to, $username, $password) {
    $subject = 'Restablecimiento de Contraseña - Sistema Maker';
    $message = "Hola $username,\n\nSe ha restablecido tu contraseña. Estas son tus nuevas credenciales:\n\nUsuario: $username\nContraseña: $password\n\nPara acceder directamente, haz clic en el siguiente enlace:\n\nhttps://fichajesmaker.site\n\nSi quieres cambiar la contraseña, en el Manual de uso, dentro de la web, encontrarás cómo hacerlo.";
    $headers = 'From: password@fichajesmaker.site' . "\r\n" .
               'Reply-To: password@fichajesmaker.site' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}

// Manejar la solicitud de restablecimiento de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario'])) {
    $usuario = $_POST['usuario'];

    try {
        // Verificar si el usuario existe
        $stmt = $pdo->prepare("SELECT ID, Nombre, Email FROM Usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $usuarioInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioInfo) {
            // Generar una nueva contraseña
            $nuevaContraseña = generatePassword();

            // Actualizar la contraseña en la base de datos
            $hashContraseña = password_hash($nuevaContraseña, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE Usuarios SET Contraseña = ? WHERE ID = ?");
            $stmt->execute([$hashContraseña, $usuarioInfo['ID']]);

            // Enviar el correo con la nueva contraseña
            sendEmail($usuarioInfo['Email'], $usuarioInfo['Nombre'], $nuevaContraseña);
            $mensaje = "Se ha enviado un correo con la nueva contraseña a " . $usuarioInfo['Email'] . ".";

            // Redirigir después del procesamiento para evitar reenvíos
            header('Location: restablecer_contrasenas.php?mensaje=' . urlencode($mensaje));
            exit;
        } else {
            $mensaje = "No se encontró ningún usuario con ese nombre.";
        }
    } catch (Exception $e) {
        $mensaje = "Error al procesar la solicitud: " . $e->getMessage();
    }
} elseif (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <style>
        /* Estilos generales del cuerpo */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
        }
        /* Estilos de la barra lateral */
        .sidebar {
            height: 100%;
            width: 230px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar a {
            padding: 10px 20px;
            text-align: center;
            font-size: 16px;
            color: white;
            display: block;
            text-decoration: none;
            margin: 5px 0;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .sidebar img {
            width: 60%;
            margin: 0 auto 20px auto;
            display: block;
            border-radius: 50%;
        }
        /* Estilos del contenido principal */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
        }
        header {
            background: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 32px;
        }
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="submit"] {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Barra lateral con enlaces -->
        <a href="../dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="../user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="../manual_uso.php">Manual de uso</a>
        <a href="../fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <?php if (in_array($_SESSION['Rol_ID'], [3, 2])): ?>
            <a href="../control-fichajes/control_fichajes.php">Control fichajes</a>
            <a href="../gestion-usuarios/gestion_usuarios.php">Gestión usuarios</a>
        <?php endif; ?>
        <?php if ($_SESSION['Rol_ID'] == 4): ?>
            <a href="../herramientas-admin/herramientas_admin.php">Herramientas Admin</a>
        <?php endif; ?>
        <a href="../dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Restablecer Contraseña</h1>
        </header>
        <div class="main-content">
            <!-- Mostrar mensaje de confirmación o error -->
            <?php if (isset($mensaje)): ?>
                <p><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>
            <!-- Formulario de restablecimiento de contraseña -->
            <form method="POST" action="">
                <label for="usuario">Nombre de Usuario:</label>
                <input type="text" name="usuario" id="usuario" required>
                <input type="submit" value="Restablecer Contraseña">
            </form>
        </div>
    </div>
</body>
</html>