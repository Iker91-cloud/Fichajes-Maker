<?php
session_start();

// Redirigir si no está logueado
if (!isset($_SESSION['username']) || !isset($_SESSION['db_name'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php'; // Asegúrate de que la configuración de la base de datos es correcta

$mensaje = '';

// Comprobar si el usuario quiere fichar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fichar'])) {
    $tipo = $_POST['tipo_fichaje'];
    $nota = $_POST['nota'] ?? null;
    $fechaHora = new DateTime();

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$_SESSION[db_name];charset=utf8", $db_username, $db_password);
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
        // Redireccionamos tras el POST
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $mensaje = "Error al registrar el fichaje: " . $e->getMessage();
    }
}

// Obtener los últimos 5 fichajes
$stmt = $pdo->prepare("SELECT Fecha_Hora_Entrada, Fecha_Hora_Salida, Tipo, Nota FROM RegistrosDeTiempo WHERE Usuario_ID = ? ORDER BY Fecha_Hora_Entrada DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$attendanceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Usuario</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e8e8e8;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }
        .sidebar {
            background-color: #003366;
            color: white;
            width: 200px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sidebar a {
            color: white;
            padding: 10px;
            text-decoration: none;
            text-align: center;
            border: 2px solid white;
            margin: 10px 0;
            width: 100%;
            box-sizing: border-box;
        }
        .sidebar a:hover {
            background-color: #0056b3;
        }
        .main-content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f4f4f4;
        }
        .header {
            width: 100%;
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 24px;
        }
        .header img {
            height: 50px;
        }
        .time-display {
            color: #0056b3;
            font-size: 20px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #f9f9f9;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        label, select, textarea, button {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a> <!-- Use real name instead of username -->
        <a href="/CODIGO/anteriores-fichajes/anteriores-fichajes.php">Ver fichajes anteriores</a>
        <a href="manual-de-uso.php">Manual de uso</a>
    </div>
    <div class="main-content">
        <header>
            <div class="user-name">Bienvenido al Sistema Maker</div>
            <img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon">
        </header>
        <div class="time-display" id="time-display"></div>
        <p><?= $mensaje ?? ''; ?></p>
        <form action="dashboard.php" method="post">
            <label for="tipo_fichaje">Tipo de Fichaje:</label>
            <select id="tipo_fichaje" name="tipo_fichaje">
                <option value="Entrada">Entrada</option>
                <option value="Salida">Salida</option>
            </select>
            <label for="nota">Nota (opcional):</label>
            <textarea id="nota" name="nota"></textarea>
            <button type="submit" name="fichar">Fichar</button>
        </form>
        <h2>Historial reciente de fichajes</h2>
        <table>
            <tr>
                <th>Fecha y Hora de Entrada</th>
                <th>Fecha y Hora de Salida</th>
                <th>Tipo</th>
                <th>Nota</th>
            </tr>
            <?php foreach ($attendanceHistory as $entry): ?>
            <tr>
                <td><?= htmlspecialchars($entry['Fecha_Hora_Entrada']); ?></td>
                <td><?= htmlspecialchars($entry['Fecha_Hora_Salida'] ?? 'En curso'); ?></td>
                <td><?= htmlspecialchars($entry['Tipo']); ?></td>
                <td><?= htmlspecialchars($entry['Nota']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <script>
        function updateTime() {
            const now = new Date();
            document.getElementById('time-display').innerHTML = now.toLocaleTimeString();
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>





