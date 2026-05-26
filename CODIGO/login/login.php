<?php
require_once '../session_config.php'; // Incluir el archivo de configuración de la sesión
require_once 'db_config.php'; // Incluir el archivo de configuración de la base de datos

date_default_timezone_set('Europe/Madrid'); // Ajustar la zona horaria a Madrid
$fechaActual = date('d/m/Y'); // Obtener la fecha actual en formato día/mes/año

$error = '';

// Función para verificar el token de hCaptcha
function verificarHCaptcha($token) {
    $secretKey = 'ES_591281b67c2c462381527d5cd87ac651'; // Llave secreta de hCaptcha
    $url = 'https://hcaptcha.com/siteverify'; // URL de verificación de hCaptcha
    $data = [
        'secret' => $secretKey,
        'response' => $token
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options); // Crear el contexto de la solicitud
    $response = file_get_contents($url, false, $context); // Enviar la solicitud
    $result = json_decode($response, true); // Decodificar la respuesta JSON

    return $result['success']; // Devolver el resultado de la verificación
}

// Manejar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $usuario = $_POST['usuario']; // Obtener el nombre de usuario del formulario
    $password = $_POST['password']; // Obtener la contraseña del formulario
    $hcaptchaToken = $_POST['h-captcha-response']; // Obtener el token de hCaptcha del formulario

    // Verificar el token de hCaptcha
    if (verificarHCaptcha($hcaptchaToken)) {
        $authenticated = false; // Variable para verificar la autenticación

        // Recorrer cada base de datos definida en db_config.php
        foreach ($databases as $database) {
            $dsn = "mysql:host={$database['Dservername']};dbname={$database['Ddbname']};charset=utf8";
            try {
                // Crear una nueva conexión PDO
                $pdo = new PDO($dsn, $database['Dusername'], $database['Dpassword']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Preparar y ejecutar la consulta para obtener el usuario
                $stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verificar la contraseña y establecer las variables de sesión si es correcta
                if ($user && password_verify($password, $user['Contrasena'])) {
                    $_SESSION['user_id'] = $user['ID'];
                    $_SESSION['username'] = $user['usuario'];
                    $_SESSION['nombre'] = $user['Nombre'];
                    $_SESSION['db_name'] = $database['Ddbname'];
                    $_SESSION['db_user'] = $database['Dusername'];
                    $_SESSION['db_pass'] = $database['Dpassword'];
                    $_SESSION['db_host'] = $database['Dservername'];
                    $_SESSION['Rol_ID'] = $user['Rol_ID'];
                    if (isset($user['Departamento_ID'])) {
                        $_SESSION['Department_ID'] = $user['Departamento_ID'];
                    }
                    $authenticated = true;
                    break;
                }
            } catch (PDOException $e) {
                echo "Error en la base de datos: " . $e->getMessage();
            }
        }

        // Redirigir al dashboard si la autenticación es correcta
        if ($authenticated) {
            header("Location: ../dashboard.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Error en la verificación de hCaptcha.";
    }
} else {
    $error = "Por favor, inicie sesión para continuar.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif; /* Fuente principal */
            background: linear-gradient(to right, #4b79a1, #283e51); /* Fondo con degradado */
            color: #ffffff; /* Color de texto */
            display: flex; /* Flexbox para centrar el contenido */
            justify-content: center; /* Centrar horizontalmente */
            align-items: center; /* Centrar verticalmente */
            height: 100vh; /* Altura de la ventana */
            margin: 0; /* Sin márgenes */
        }
        .login-container {
            width: 320px; /* Ancho del contenedor */
            padding: 20px; /* Relleno interior */
            background-color: #fff; /* Fondo blanco */
            color: #000; /* Color de texto negro */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Sombra */
            border-radius: 8px; /* Bordes redondeados */
            text-align: center; /* Centrar texto */
        }
        .login-container img {
            width: 100px; /* Ancho de la imagen */
            margin-bottom: 10px; /* Margen inferior */
        }
        .login-container h2 {
            margin-bottom: 20px; /* Margen inferior */
            color: #333; /* Color de texto */
        }
        .login-container .date-time {
            font-size: 14px; /* Tamaño de fuente */
            color: #0056b3; /* Azul */
            margin-bottom: 20px; /* Margen inferior */
        }
        form {
            display: flex; /* Flexbox para centrar el contenido */
            flex-direction: column; /* Dirección vertical */
            align-items: center; /* Centrar contenido */
        }
        .form-group {
            margin-bottom: 15px; /* Margen inferior */
            text-align: left; /* Alinear texto a la izquierda */
            width: 100%; /* Ancho completo */
        }
        label {
            margin-bottom: 5px; /* Margen inferior */
            font-weight: bold; /* Texto en negrita */
            color: #333; /* Color de texto */
        }
        input[type="text"],
        input[type="password"] {
            padding: 10px; /* Relleno interior */
            border: 1px solid #ccc; /* Borde */
            border-radius: 4px; /* Bordes redondeados */
            width: calc(100% - 22px); /* Ancho menos el relleno */
        }
        button {
            padding: 10px; /* Relleno interior */
            background-color: #0056b3; /* Fondo azul */
            color: white; /* Texto blanco */
            border: none; /* Sin borde */
            border-radius: 4px; /* Bordes redondeados */
            cursor: pointer; /* Cursor de mano */
            width: 100%; /* Ancho completo */
        }
        button:hover {
            background-color: #004494; /* Fondo azul oscuro al pasar el ratón */
        }
        p.error {
            color: red; /* Color de texto rojo para errores */
        }
        .h-captcha {
            margin-bottom: 15px; /* Margen inferior */
        }
    </style>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script> <!-- Script de hCaptcha -->
    <script>
        function updateTime() {
            const now = new Date();
            const formattedTime = now.toLocaleTimeString();
            document.getElementById('currentTime').textContent = formattedTime;
        }
        setInterval(updateTime, 1000); // Actualizar cada segundo
        window.onload = updateTime; // Actualizar al cargar la página
    </script>
</head>
<body>
    <div class="login-container">
        <img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"> <!-- Logo -->
        <div class="date-time">
            <div>Fecha: <?= $fechaActual ?></div> <!-- Fecha actual -->
            <div>Hora: <span id="currentTime"></span></div> <!-- Hora actual -->
        </div>
        <h2>Iniciar Sesión</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p> <!-- Mensaje de error -->
        <?php endif; ?>
        <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required> <!-- Campo de usuario -->
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required> <!-- Campo de contraseña -->
            </div>
            <div class="h-captcha" data-sitekey="01cc7948-aa9a-4e96-9cd0-16e860fccef8"></div> <!-- hCaptcha -->
            <button type="submit" name="login">Entrar</button> <!-- Botón de envío -->
        </form>
    </div>
</body>
</html>