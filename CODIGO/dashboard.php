<?php
require_once 'session_config.php';

// Establecer la zona horaria
date_default_timezone_set('Europe/Madrid');

// Verificar si el usuario está logueado
if (!isset($_SESSION['username']) || !isset($_SESSION['db_name']) || !isset($_SESSION['db_user']) || !isset($_SESSION['db_pass']) || !isset($_SESSION['db_host'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';

// Define las credenciales de la base de datos
$dbhost = $_SESSION['db_host'];
$dbuser = $_SESSION['db_user'];
$dbpass = $_SESSION['db_pass'];
$dbname = $_SESSION['db_name'];

// Crea una nueva instancia de PDO
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
} catch (PDOException $e) {
    die("No se pudo conectar a la base de datos $dbname: " . $e->getMessage());
}

// Comprobación y registro de fichaje
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fichar'])) {
    $tipo = $_POST['tipo_fichaje'];
    $nota = $_POST['nota'] ?? null;
    $fechaHora = new DateTime();

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($tipo == 'Entrada') {
            $stmt = $pdo->prepare("INSERT INTO RegistrosDeTiempo (Usuario_ID, Fecha_Hora_Entrada, Tipo, Nota) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $fechaHora->format('Y-m-d H:i:s'), $tipo, $nota]);
        } else {
            $stmt = $pdo->prepare("SELECT Fecha_Hora_Entrada FROM RegistrosDeTiempo WHERE Usuario_ID = ? AND Fecha_Hora_Salida IS NULL AND Tipo = 'Entrada' ORDER BY Fecha_Hora_Entrada DESC LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $lastEntry = $stmt->fetch();

            if ($lastEntry) {
                $stmt = $pdo->prepare("INSERT INTO RegistrosDeTiempo (Usuario_ID, Fecha_Hora_Entrada, Fecha_Hora_Salida, Tipo, Nota) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $lastEntry['Fecha_Hora_Entrada'], $fechaHora->format('Y-m-d H:i:s'), 'Salida', $nota]);
            } else {
                throw new Exception("No se encontró un registro de entrada válido para asociar la salida.");
            }
        }
        $mensaje = "Fichaje de $tipo registrado con éxito.";
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $mensaje = "Error al registrar el fichaje: " . $e->getMessage();
    }
}

// Obtener los últimos fichajes
$stmt = $pdo->prepare("SELECT Fecha_Hora_Entrada, Fecha_Hora_Salida, Tipo, Nota FROM RegistrosDeTiempo WHERE Usuario_ID = ? ORDER BY Fecha_Hora_Entrada DESC LIMIT 4");
$stmt->execute([$_SESSION['user_id']]);
$attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        /* Estilos generales del cuerpo */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
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
            display: flex;
            flex-direction: column;
        }

        /* Estilos de los enlaces de la barra lateral */
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

        .sidebar .links {
            flex-grow: 1;
        }

        /* Estilos del contenido principal */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
            overflow-y: auto;
        }

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

        .current-datetime {
            font-size: 18px;
            color: #0056b3;
            text-align: center;
            margin-top: 5px;
        }

        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        form {
            margin: 10px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        form label {
            display: block;
            margin: 5px 0;
        }

        form select, form input[type="text"], form input[type="submit"] {
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        form select {
            width: 50%;
        }

        form input[type="text"] {
            width: 70%;
            color: #888;
            border-color: blue;
        }

        form input[type="submit"] {
            width: 90%;
            background-color: #0056b3;
            color: white;
            border: none;
        }

        form input[type="submit"]:hover {
            background-color: #004494;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .entrada {
            color: green;
        }

        .salida {
            color: red;
        }

        #tipo_fichaje {
            border-color: green;
        }

        #nota:focus {
            color: black;
        }
    </style>
    <script>
        // Actualizar la hora actual cada segundo
        function updateTime() {
            const now = new Date();
            const time = now.toLocaleTimeString('es-ES', { hour12: false });
            const date = now.toLocaleDateString('es-ES');
            document.getElementById('datetime').textContent = `Hora actual: ${time} | Fecha actual: ${date}`;
        }
        setInterval(updateTime, 1000);

        // Actualizar el color del borde del campo según el tipo de fichaje
        function updateBorderColor() {
            const tipoFichaje = document.getElementById('tipo_fichaje');
            if (tipoFichaje.value === 'Entrada') {
                tipoFichaje.style.borderColor = 'green';
            } else {
                tipoFichaje.style.borderColor = 'red';
            }
        }

        window.onload = function() {
            updateBorderColor();
        }

        // Limpiar el placeholder del campo de nota
        function clearPlaceholder(input) {
            input.placeholder = '';
            input.style.color = 'black';
        }
    </script>
</head>
<body onload="updateTime()">
    <div class="sidebar">
        <a href="/CODIGO/dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="manual_uso.php">Manual de uso</a>
        <a href="/CODIGO/fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <?php if (in_array($_SESSION['Rol_ID'], [3, 2])): ?>
            <a href="/CODIGO/control-fichajes/control_fichajes.php">Control fichajes</a>
            <a href="/CODIGO/gestion-usuarios/gestion_usuarios.php">Gestión usuarios</a>
        <?php endif; ?>
        <?php if ($_SESSION['Rol_ID'] == 4): ?>
            <a href="/CODIGO/herramientas-admin/herramientas_admin.php">Herramientas Admin</a>
        <?php endif; ?>
        <a href="/CODIGO/dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Bienvenido al Sistema Maker</h1>
            <div class="current-datetime" id="datetime"></div>
        </header>
        <div class="main-content">
            <h2>¡Hola! <?= htmlspecialchars($_SESSION['nombre']); ?></h2>
            <p><?= $mensaje ?? ''; ?></p>
            <form method="POST" action="">
                <label for="tipo_fichaje">Tipo de Fichaje:</label>
                <select name="tipo_fichaje" id="tipo_fichaje" onchange="updateBorderColor()">
                    <option value="Entrada" selected>Entrada</option>
                    <option value="Salida">Salida</option>
                </select>
                <br>
                <input type="text" name="nota" id="nota" placeholder="Nota (opcional)" onclick="clearPlaceholder(this)">
                <br>
                <input type="submit" name="fichar" value="Fichar">
            </form>
            <h3>Fichajes recientes:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Fecha/Hora Entrada</th>
                        <th>Fecha/Hora Salida</th>
                        <th>Tipo</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceHistory as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['Fecha_Hora_Entrada']); ?></td>
                            <td><?= htmlspecialchars($entry['Fecha_Hora_Salida']) ?? 'En curso'; ?></td>
                            <td class="<?= strtolower(htmlspecialchars($entry['Tipo'])); ?>">
                                <?= htmlspecialchars($entry['Tipo']); ?>
                            </td>
                            <td><?= htmlspecialchars($entry['Nota']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


