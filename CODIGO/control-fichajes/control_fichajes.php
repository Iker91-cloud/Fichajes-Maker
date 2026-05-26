<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['db_name']) || !in_array($_SESSION['Rol_ID'], [2, 3])) {
    header('Location: ../login.php');
    exit;
}

$db_host = $_SESSION['db_host'];
$db_username = $_SESSION['db_user'];
$db_password = $_SESSION['db_pass'];
$database = $_SESSION['db_name'];
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['Rol_ID'];
$department_id = isset($_SESSION['Department_ID']) ? $_SESSION['Department_ID'] : null;

$limit = 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$database;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Construir consulta para buscar usuarios
    $user_sql = "SELECT ID, Nombre, usuario FROM Usuarios WHERE (Nombre LIKE :search OR usuario LIKE :search)";
    if ($role_id == 2) { // Si es Jefe de Departamento
        $user_sql .= " AND Departamento_ID = :department_id";
    }
    $user_sql .= " LIMIT :limit OFFSET :offset";

    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $user_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $user_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if ($role_id == 2) {
        $user_stmt->bindValue(':department_id', $department_id, PDO::PARAM_INT);
    }
    $user_stmt->execute();
    $usuarios = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construir consulta para buscar fichajes si se selecciona un usuario
    if (isset($_GET['user_id'])) {
        $selected_user_id = intval($_GET['user_id']);
        $sql = "SELECT Fecha_Hora_Entrada, Fecha_Hora_Salida, Tipo, Nota FROM RegistrosDeTiempo WHERE Usuario_ID = :user_id";
        $params = ['user_id' => $selected_user_id];

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

        // Obtener el nombre del usuario seleccionado
        $user_stmt = $pdo->prepare("SELECT Nombre FROM Usuarios WHERE ID = :user_id");
        $user_stmt->execute(['user_id' => $selected_user_id]);
        $selected_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $selected_user_name = $selected_user['Nombre'] ?? '';
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
    <title>Control de Fichajes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
        }
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
            overflow-y: auto;
            max-height: 80vh;
        }
        .filters {
            display: none;
            gap: 10px;
            margin-bottom: 20px;
            align-items: flex-end;
        }
        .filters label {
            margin-right: 10px;
        }
        .filters select, .filters input[type="date"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .table-container {
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #0056b3;
            color: white;
        }
        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        tbody tr:nth-child(even) {
            background-color: #fff;
        }
        .back-link {
            color: #0056b3;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: white;
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .back-link:hover {
            background-color: #e7e7e7;
        }
        img.icon {
            height: 60px;
        }
        .title {
            margin-bottom: 20px;
            font-size: 22px;
            color: #0056b3;
            text-align: center;
        }
        .buttons-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .buttons-container form,
        .buttons-container button {
            margin: 0;
        }
        .download-buttons {
            display: flex;
            gap: 10px;
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
        .load-more {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        .load-more button {
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .load-more button:hover {
            background-color: #004494;
        }
        .entrada {
            color: green;
        }
        .salida {
            color: red;
        }
        .search-container {
            display: flex;
            align-items: center;
        }
        .search-container input[type="text"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }
        .search-container button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .search-container button:hover {
            background-color: #0056b3;
        }
        .back-to-control {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            display: block;
            margin: 20px auto;
            width: fit-content;
        }
        .back-to-control:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        // Función para mostrar u ocultar los filtros de búsqueda
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

            fetch(`control_fichajes.php?${params.toString()}`)
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const newRows = doc.querySelectorAll('tbody tr');
                    const tbody = document.querySelector('tbody');
                    newRows.forEach(row => {
                        tbody.appendChild(row);
                    });
                    document.querySelector('.load-more button').dataset.offset = parseInt(offset) + 20;

                    if (newRows.length < 20) {
                        document.querySelector('.load-more').remove();
                    }
                });
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <a href="../dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="../manual_uso.php">Manual de uso</a>
        <a href="/CODIGO/fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <?php if (in_array($_SESSION['Rol_ID'], [3, 2])): ?>
            <a href="/CODIGO/control-fichajes/control_fichajes.php">Control fichajes</a>
            <a href="/CODIGO/gestion-usuarios/gestion_usuarios.php">Gestión usuarios</a>
        <?php endif; ?>
        <a href="../dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Control de Fichajes</h1>
        </header>
        <div class="main-content">
            <!-- Formulario de búsqueda de usuarios -->
            <form method="GET" action="control_fichajes.php">
                <div class="search-container">
                    <input type="text" name="search" placeholder="Nombre o Usuario" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Buscar</button>
                </div>
            </form>
            <?php if (isset($selected_user_id)): ?>
                <!-- Controles adicionales para un usuario específico -->
                <div class="buttons-container">
                    <button onclick="toggleFilters()">Añadir Filtros</button>
                    <form method="GET" action="control_descargar.php" class="download-buttons">
                        <input type="hidden" name="user_id" value="<?= $selected_user_id ?>">
                        <input type="hidden" name="tipo" value="<?= htmlspecialchars($_GET['tipo'] ?? '') ?>">
                        <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                        <input type="hidden" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                        <button type="submit" name="format" value="csv">Descargar CSV</button>
                        <button type="submit" name="format" value="excel">Descargar Excel</button>
                        <button type="submit" name="format" value="pdf">Descargar PDF</button>
                    </form>

                </div>
                <!-- Filtros de búsqueda -->
                <form class="filters" method="GET" action="control_fichajes.php">
                    <input type="hidden" name="user_id" value="<?= $selected_user_id ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
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
                <div class="title">Fichajes de <?= htmlspecialchars($selected_user_name) ?>:</div>
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
                <a href="control_fichajes.php" class="back-to-control">Volver a control fichajes</a>
            <?php else: ?>
                <div class="title">Todos los usuarios:</div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['Nombre']) ?></td>
                                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                    <td><a href="control_fichajes.php?user_id=<?= $usuario['ID'] ?>&search=<?= urlencode($search) ?>">Ver Fichajes</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($usuarios) == $limit): ?>
                        <div class="load-more">
                            <button type="button" data-offset="<?= $offset + $limit ?>" onclick="loadMore()">Cargar Más</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
