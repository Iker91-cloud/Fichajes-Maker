<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['db_name'])) {
    header('Location: ../login.php');
    exit;
}

// Variables de configuración de la base de datos
$db_host = $_SESSION['db_host'];
$db_username = $_SESSION['db_user'];
$db_password = $_SESSION['db_pass'];
$database = $_SESSION['db_name'];

$limit = 20; // Límite de resultados por página
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0; // Desplazamiento para paginación

try {
    // Conectar a la base de datos usando PDO
    $pdo = new PDO("mysql:host=$db_host;dbname=$database;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];
    // Consulta SQL para obtener los fichajes del usuario logueado
    $sql = "SELECT Fecha_Hora_Entrada, Fecha_Hora_Salida, Tipo, Nota FROM RegistrosDeTiempo WHERE Usuario_ID = :user_id";
    $params = ['user_id' => $user_id];

    // Aplicar filtros si están establecidos
    if (isset($_GET['tipo']) && $_GET['tipo'] != '') {
        $sql .= " AND Tipo = :tipo";
        $params['tipo'] = $_GET['tipo'];
    }

    if (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] != '' && isset($_GET['fecha_fin']) && $_GET['fecha_fin'] != '') {
        $sql .= " AND (DATE(Fecha_Hora_Entrada) BETWEEN :fecha_inicio AND :fecha_fin OR DATE(Fecha_Hora_Salida) BETWEEN :fecha_inicio AND :fecha_fin)";
        $params['fecha_inicio'] = $_GET['fecha_inicio'];
        $params['fecha_fin'] = $_GET['fecha_fin'];
    } elseif (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] != '') {
        $sql .= " AND (DATE(Fecha_Hora_Entrada) >= :fecha_inicio OR DATE(Fecha_Hora_Salida) >= :fecha_inicio)";
        $params['fecha_inicio'] = $_GET['fecha_inicio'];
    } elseif (isset($_GET['fecha_fin']) && $_GET['fecha_fin'] != '') {
        $sql .= " AND (DATE(Fecha_Hora_Entrada) <= :fecha_fin OR DATE(Fecha_Hora_Salida) <= :fecha_fin)";
        $params['fecha_fin'] = $_GET['fecha_fin'];
    }

    $sql .= " ORDER BY Fecha_Hora_Entrada DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$value) {
        $stmt->bindParam(":$key", $value);
    }
    $stmt->execute();
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode($fichajes);
        exit;
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichajes Anteriores</title>
    <style>
        /* Estilos básicos para el cuerpo de la página */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
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
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        /* Estilos para los filtros */
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: flex-end;
        }
        .filters label {
            margin-right: 10px;
        }
        .filters select, .filters input[type="date"], .filters input[type="submit"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        /* Estilos para los botones de descarga */
        .buttons-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .buttons-container form, .buttons-container button {
            margin: 0;
        }
        .download-buttons button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .download-buttons button:hover {
            background-color: #0056b3;
        }
        /* Estilos para la tabla de fichajes */
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
        /* Estilos para el botón de cargar más */
        .load-more {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        .load-more button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .load-more button:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        // Función para mostrar u ocultar los filtros
        function toggleFilters() {
            const filters = document.querySelector('.filters');
            if (filters.style.display === 'none' || filters.style.display === '') {
                filters.style.display = 'flex';
            } else {
                filters.style.display = 'none';
            }
        }

        // Función para cargar más resultados al hacer clic en el botón "Cargar Más"
        function loadMore() {
            const offset = document.querySelector('.load-more button').dataset.offset;
            const params = new URLSearchParams(window.location.search);
            params.set('offset', offset);
            params.set('ajax', '1');
            fetch(`fichajes_anteriores.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const tbody = document.querySelector('tbody');
                        data.forEach(fichaje => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${fichaje.Fecha_Hora_Entrada}</td>
                                <td>${fichaje.Fecha_Hora_Salida || 'N/A'}</td>
                                <td class="${fichaje.Tipo.toLowerCase()}">${fichaje.Tipo}</td>
                                <td>${fichaje.Nota}</td>
                            `;
                            tbody.appendChild(row);
                        });
                        const newOffset = parseInt(offset) + 20;
                        document.querySelector('.load-more button').dataset.offset = newOffset;
                        if (data.length < 20) {
                            document.querySelector('.load-more').remove();
                        }
                    } else {
                        document.querySelector('.load-more').remove();
                    }
                });
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <a href="/CODIGO/dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="/CODIGO/manual_uso.php">Manual de uso</a>
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
            <h1>Fichajes Anteriores</h1>
            <div class="current-datetime" id="datetime"></div>
        </header>
        <div class="main-content">
            <div class="buttons-container">
                <button onclick="toggleFilters()">Añadir Filtros</button>
                <form method="GET" action="descargar_fichajes.php" class="download-buttons">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($_GET['tipo'] ?? '') ?>">
                    <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                    <input type="hidden" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                    <button type="submit" name="format" value="csv">Descargar CSV</button>
                    <button type="submit" name="format" value="excel">Descargar Excel</button>
                    <button type="submit" name="format" value="pdf">Descargar PDF</button>
                </form>
            </div>
            <form class="filters" method="GET" action="fichajes_anteriores.php">
                <label for="tipo">Tipo de Fichaje:</label>
                <select name="tipo" id="tipo">
                    <option value="">Todos</option>
                    <option value="Entrada" <?= isset($_GET['tipo']) && $_GET['tipo'] == 'Entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="Salida" <?= isset($_GET['tipo']) && $_GET['tipo'] == 'Salida' ? 'selected' : '' ?>>Salida</option>
                </select>
                <label for="fecha_inicio">Fecha Inicio:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                <label for="fecha_fin">Fecha Fin:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                <input type="submit" value="Filtrar">
            </form>
            <div class="title">Tus anteriores fichajes:</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha y Hora de Entrada</th>
                            <th>Fecha y Hora de Salida</th>
                            <th>Tipo</th>
                            <th>Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichajes as $fichaje): ?>
                            <tr>
                                <td><?= htmlspecialchars($fichaje['Fecha_Hora_Entrada']) ?></td>
                                <td><?= htmlspecialchars($fichaje['Fecha_Hora_Salida']) ?: 'N/A' ?></td>
                                <td class="<?= strtolower(htmlspecialchars($fichaje['Tipo'])); ?>">
                                    <?= htmlspecialchars($fichaje['Tipo']) ?>
                                </td>
                                <td><?= htmlspecialchars($fichaje['Nota']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($fichajes) == $limit): ?>
                    <div class="load-more">
                        <button type="button" data-offset="<?= $offset + $limit ?>" onclick="loadMore()">Cargar Más</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
