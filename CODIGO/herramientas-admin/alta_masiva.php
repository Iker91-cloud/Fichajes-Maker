<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['db_name']) || $_SESSION['Rol_ID'] != 4) {
    header('Location: ../login.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $generatePasswords = isset($_POST['generate_passwords']);

    if (($handle = fopen($file, 'r')) !== FALSE) {
        $row = 1;
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if ($row == 1) {
                // Omite la primera fila (encabezados)
                $row++;
                continue;
            }

            $username = isset($data[0]) ? trim($data[0]) : null;
            $nombre = isset($data[1]) ? trim($data[1]) : null;
            $email = isset($data[2]) ? trim($data[2]) : null;
            $rolNombre = isset($data[3]) ? trim($data[3]) : null;
            $departamentoNombre = isset($data[4]) ? trim($data[4]) : null;

            if ($generatePasswords) {
                $password = generatePassword();
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            } else {
                $password = isset($data[5]) ? trim($data[5]) : null;
                $hashedPassword = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
            }

            if (!$username || !$nombre || !$email || !$rolNombre || !$departamentoNombre || (!$generatePasswords && !$password)) {
                $mensaje .= "Error en la fila $row: Datos incompletos.<br>";
                $row++;
                continue;
            }

            try {
                $pdo = new PDO("mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']}", $_SESSION['db_user'], $_SESSION['db_pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Obtener el ID del rol
                $stmt = $pdo->prepare("SELECT Rol_ID FROM Roles WHERE Nombre = ?");
                $stmt->execute([$rolNombre]);
                $rol = $stmt->fetchColumn();

                // Obtener el ID del departamento
                $stmt = $pdo->prepare("SELECT Departamento_ID FROM Departamentos WHERE Nombre = ?");
                $stmt->execute([$departamentoNombre]);
                $departamento = $stmt->fetchColumn();

                if (!$rol || !$departamento) {
                    $mensaje .= "Error en la fila $row: Rol o Departamento no encontrado.<br>";
                    $row++;
                    continue;
                }

                $stmt = $pdo->prepare("INSERT INTO Usuarios (usuario, Nombre, Email, Contraseña, Rol_ID, Departamento_ID) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $nombre, $email, $hashedPassword, $rol, $departamento]);

                if ($generatePasswords) {
                    sendEmail($email, $username, $password);
                }
            } catch (Exception $e) {
                $mensaje .= "Error al procesar la fila $row: " . $e->getMessage() . "<br>";
            }
            $row++;
        }
        fclose($handle);
        if ($mensaje === '') {
            $mensaje = 'Usuarios creados y procesados exitosamente.';
        }
    } else {
        $mensaje = 'Error al abrir el archivo.';
    }
}

function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    return substr(str_shuffle($chars), 0, $length);
}

function sendEmail($to, $username, $password) {
    $subject = 'Bienvenido al Sistema Maker';
    $message = "Bienvenido al Sistema Maker.\n\nTe han dado de alta. Estas son tus credenciales:\n\nUsuario: $username\nContraseña: $password\n\nPara acceder directamente, haz clic en el siguiente enlace:\n\nhttps://fichajesmaker.site\n\nSi quieres cambiar la contraseña, en el Manual de uso, dentro de la web, encontrarás cómo hacerlo.";
    $headers = 'From: password@fichajesmaker.site' . "\r\n" .
               'Reply-To: password@fichajesmaker.site' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}

// Función para descargar plantilla
if (isset($_GET['download_template'])) {
    $templateType = $_GET['download_template'];

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="plantilla_usuarios.csv"');

    $output = fopen('php://output', 'w');
    if ($templateType == 'contrasena') {
        fputcsv($output, ['Usuario', 'Nombre', 'Email', 'Rol', 'Departamento', 'Contraseña']);
        fputcsv($output, ['jdoe', 'John Doe', 'john.doe@example.com', 'Admin', 'IT', 'Password123']);
        fputcsv($output, ['jsmith', 'Jane Smith', 'jane.smith@example.com', 'User', 'HR', 'Password456']);
    } else {
        fputcsv($output, ['Usuario', 'Nombre', 'Email', 'Rol', 'Departamento']);
        fputcsv($output, ['jdoe', 'John Doe', 'john.doe@example.com', 'Admin', 'IT']);
        fputcsv($output, ['jsmith', 'Jane Smith', 'jane.smith@example.com', 'User', 'HR']);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alta Masiva de Usuarios</title>
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
        input[type="file"],
        input[type="submit"],
        input[type="checkbox"] {
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
        .mensaje {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
        .template-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .template-buttons a {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .template-buttons a:hover {
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
            <h1>Alta Masiva de Usuarios</h1>
        </header>
        <div class="main-content">
            <?php if ($mensaje): ?>
                <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>
            <div class="template-buttons">
                <a href="?download_template=contrasena">Descargar plantilla con contraseña</a>
                <a href="?download_template=sin_contrasena">Descargar plantilla sin contraseña</a>
            </div>
            <form method="POST" enctype="multipart/form-data" action="">
                <label for="csv_file">Seleccionar archivo CSV:</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                <label>
                    <input type="checkbox" name="generate_passwords" id="generate_passwords">
                    Generar contraseñas aleatorias y enviar por correo
                </label>
                <input type="submit" value="Subir y Procesar">
            </form>
        </div>
    </div>
</body>
</html>

