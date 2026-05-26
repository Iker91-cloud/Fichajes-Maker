<?php
// Importar las clases de PHPMailer al principio del script
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['Rol_ID']) || !in_array($_SESSION['Rol_ID'], [3, 2])) {
    header('Location: ../login.php');
    exit;
}

// Configurar PDO para la conexión a la base de datos
$db_host = $_SESSION['db_host'];
$db_username = $_SESSION['db_user'];
$db_password = $_SESSION['db_pass'];
$database = $_SESSION['db_name'];

try {
    // Establecer la conexión con la base de datos
    $pdo = new PDO("mysql:host=$db_host;dbname=$database;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener lista de roles y departamentos
$roles_stmt = $pdo->query("SELECT Rol_ID, Nombre FROM Roles");
$roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);

$dept_stmt = $pdo->query("SELECT Departamento_ID, Nombre FROM Departamentos");
$departamentos = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para generar una contraseña aleatoria
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    return substr(str_shuffle($chars), 0, $length);
}

// Función para enviar un correo electrónico al nuevo usuario usando PHPMailer
function sendEmail($to, $username, $password) {
    // Cargar los archivos de PHPMailer desde la raíz
    require_once $_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/Exception.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/PHPMailer.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/PHPMailer/SMTP.php';

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'password@fichajesmaker.site'; 
        $mail->Password   = 'Mrw852456.';      
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Remitente y Destinatario
        $mail->setFrom('password@fichajesmaker.site', 'Sistema Maker'); 
        $mail->addAddress($to);

        // Contenido del correo 
        $mail->isHTML(false); 
        $mail->Subject = 'Bienvenido al Sistema Maker';
        $mail->Body    = "Bienvenido al Sistema Maker.\n\nTe han dado de alta. Estas son tus credenciales:\n\nUsuario: $username\nContraseña: $password\n\nPara acceder directamente, haz clic en el siguiente enlace:\n\nhttps://fichajesmaker.site\n\nSi quieres cambiar la contraseña, en el Manual de uso, dentro de la web, encontrarás cómo hacerlo.";

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Error al enviar el correo con PHPMailer: {$mail->ErrorInfo}");
    }
}

// Manejar el formulario de envío para añadir un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $email = $_POST['email'];
    $password = generatePassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $rol_id = $_POST['rol'];
    $departamento_id = $_POST['departamento'];

    try {
        // Insertar el nuevo usuario en la base de datos
        $stmt = $pdo->prepare("INSERT INTO Usuarios (Nombre, usuario, Email, Contrasena, Rol_ID, Departamento_ID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $usuario, $email, $hashedPassword, $rol_id, $departamento_id]);
        
        // Enviar el correo electrónico con las credenciales
        sendEmail($email, $usuario, $password); 
        
        $mensaje = "Usuario añadido con éxito y la contraseña ha sido enviada por correo.";
    } catch (Exception $e) {
        $mensaje = "Error al añadir el usuario: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Usuario</title>
    <style>
        /* Estilos básicos para el cuerpo de la página */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        /* Estilos para la barra lateral */
        .sidebar {
            height: 100%;
            width: 230px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        /* Estilos para los enlaces de la barra lateral */
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
        /* Estilos para el contenido principal */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
            overflow-y: auto;
        }
        /* Estilos para el encabezado */
        header {
            background: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
            position: relative;
        }
        header h1 {
            margin: 0;
            font-size: 32px;
        }
        /* Estilos para el contenido principal */
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        /* Estilos para el formulario */
        form {
            display: flex;
            flex-direction: column;
        }
        form label {
            margin: 5px 0;
        }
        form input, form select {
            padding: 8px;
            margin: 5px 0;
            width: 100%;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        /* Estilos para el botón de envío */
        form input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        form input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        /* Estilos para el mensaje de confirmación */
        .message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        /* Estilos para el enlace de regreso */
        .back-link {
            color: #0056b3;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            background-color: white;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            text-align: center;
            margin-top: 20px;
        }
        .back-link:hover {
            background-color: #e7e7e7;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="../dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="../user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="../manual_uso.php">Manual de uso</a>
        <a href="../fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <a href="../control-fichajes/control_fichajes.php">Control fichajes</a>
        <a href="gestion_usuarios.php">Gestión usuarios</a>
        <a href="../dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Añadir Usuario</h1>
        </header>
        <div class="main-content">
            <?php if (isset($mensaje)): ?>
                <div class="message"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" id="nombre" autocomplete="off" required>

                <label for="usuario">Usuario:</label>
                <input type="text" name="usuario" id="usuario" autocomplete="off" required>

                <label for="email">Email:</label>
                <input type="email" name="email" id="email" autocomplete="off" required>

                <label for="rol">Rol:</label>
                <select name="rol" id="rol" required>
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= htmlspecialchars($rol['Rol_ID']) ?>"><?= htmlspecialchars($rol['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="departamento">Departamento:</label>
                <select name="departamento" id="departamento" required>
                    <option value="">Seleccione un departamento</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['Departamento_ID']) ?>"><?= htmlspecialchars($dept['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="submit" value="Añadir Usuario">
            </form>
            <a class="back-link" href="gestion_usuarios.php">Volver a Gestión de Usuarios</a>
        </div>
    </div>
</body>
</html>