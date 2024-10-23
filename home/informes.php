<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getInformesRecientes($pdo) {
    // Tablas
    $stmt = $pdo->query("DESCRIBE informes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $selectColumns = [];
    if (in_array('id', $columns)) $selectColumns[] = 'id';
    if (in_array('nombre', $columns)) $selectColumns[] = 'nombre';
    if (in_array('fecha', $columns)) $selectColumns[] = 'fecha';
    if (in_array('fecha_creacion', $columns)) $selectColumns[] = 'fecha_creacion';  // Alternativa común para 'fecha'
    if (in_array('tipo', $columns)) $selectColumns[] = 'tipo';

    if (empty($selectColumns)) {
        throw new Exception("No se encontraron columnas válidas en la tabla informes");
    }

    $query = "SELECT " . implode(', ', $selectColumns) . " FROM informes ORDER BY ";
    
    // Ordenamos por fecha si existe, si no, por id
    $query .= in_array('fecha', $columns) ? 'fecha' : (in_array('fecha_creacion', $columns) ? 'fecha_creacion' : 'id');
    $query .= " DESC LIMIT 5";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generarInforme($pdo, $tipoInforme, $fechaInicio, $fechaFin) {
    switch ($tipoInforme) {
        case 'ventas_diarias':
            $query = "SELECT fecha, SUM(monto) as total_ventas 
                      FROM ventas 
                      WHERE fecha BETWEEN :fechaInicio AND :fechaFin 
                      GROUP BY fecha";
            break;
        case 'inventario':
            $query = "SELECT p.nombre, i.cantidad, i.fecha_actualizacion 
                      FROM inventario i 
                      JOIN productos p ON i.producto_id = p.id 
                      WHERE i.fecha_actualizacion BETWEEN :fechaInicio AND :fechaFin";
            break;
        case 'clientes':
            $query = "SELECT c.nombre, COUNT(v.id) as total_compras, SUM(v.monto) as monto_total 
                      FROM clientes c 
                      LEFT JOIN ventas v ON c.id = v.cliente_id 
                      WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin 
                      GROUP BY c.id";
            break;
        case 'productos':
            $query = "SELECT p.nombre, SUM(v.cantidad) as unidades_vendidas, SUM(v.monto) as ingresos_totales 
                      FROM productos p 
                      JOIN ventas_detalle v ON p.id = v.producto_id 
                      JOIN ventas vn ON v.venta_id = vn.id 
                      WHERE vn.fecha BETWEEN :fechaInicio AND :fechaFin 
                      GROUP BY p.id";
            break;
        default:
            throw new Exception("Tipo de informe no válido");
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([':fechaInicio' => $fechaInicio, ':fechaFin' => $fechaFin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar nombre del informe
    $nombreInforme = ucfirst(str_replace('_', ' ', $tipoInforme)) . " - " . $fechaInicio . " a " . $fechaFin;

    // Guardar el informe en la base de datos
    $queryGuardar = "INSERT INTO informes (nombre, tipo, fecha, datos) VALUES (:nombre, :tipo, NOW(), :datos)";
    $stmtGuardar = $pdo->prepare($queryGuardar);
    $stmtGuardar->execute([
        ':nombre' => $nombreInforme,
        ':tipo' => $tipoInforme,
        ':datos' => json_encode($resultados)
    ]);

    return $nombreInforme;
}

$mensajeExito = '';
$mensajeError = '';

try {
    $informesRecientes = getInformesRecientes($pdo);
} catch (Exception $e) {
    $mensajeError = "Error al obtener informes recientes: " . $e->getMessage();
    $informesRecientes = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoInforme = $_POST['tipo_informe'];
    $fechaInicio = $_POST['fecha_inicio'];
    $fechaFin = $_POST['fecha_fin'];
    
    try {
        $nombreInforme = generarInforme($pdo, $tipoInforme, $fechaInicio, $fechaFin);
        $mensajeExito = "Informe '$nombreInforme' generado exitosamente.";
        $informesRecientes = getInformesRecientes($pdo); // Lista de informes clientes Act.
    } catch (Exception $e) {
        $mensajeError = "Error al generar el informe: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .informe-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .informe-icon {
            font-size: 24px;
            margin-right: 15px;
        }
        .informe-info {
            flex-grow: 1;
        }
        .informe-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="dashboard.php">Inicio</a>
        <a href="estadisticas.php">Estadísticas</a>
        <a href="informes.php">Informes</a>
        <a href="inventario.php">Inventario</a>
        <a href="configuracion.php">Configuración</a>
        <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
        <a href="javascript:history.back()">Volver</a>
    </div>
    <div class="main">
        <h1>Generación de Informes</h1>
        
        <div class="grid-container">
            <div class="card">
                <h2>Generar Nuevo Informe</h2>
                <form action="" method="POST">
                    <select name="tipo_informe" required>
                        <option value="ventas_diarias">Ventas Diarias</option>
                        <option value="inventario">Estado del Inventario</option>
                        <option value="clientes">Actividad de Clientes</option>
                        <option value="productos">Rendimiento de Productos</option>
                    </select>
                    <input type="date" name="fecha_inicio" required>
                    <input type="date" name="fecha_fin" required>
                    <button type="submit">Generar Informe</button>
                </form>
                <?php if (!empty($mensajeExito)): ?>
                    <p style="color: green;"><?= htmlspecialchars($mensajeExito) ?></p>
                <?php endif; ?>
                <?php if (!empty($mensajeError)): ?>
                    <p style="color: red;"><?= htmlspecialchars($mensajeError) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Informes Recientes</h2>
                <?php if (empty($informesRecientes)): ?>
                    <p>No hay informes recientes disponibles.</p>
                <?php else: ?>
                    <?php foreach ($informesRecientes as $informe): ?>
                        <div class="informe-card">
                            <div class="informe-icon">
                                <?php
                                $tipo = isset($informe['tipo']) ? $informe['tipo'] : 'desconocido';
                                switch($tipo) {
                                    case 'ventas_diarias':
                                        echo '📊';
                                        break;
                                    case 'inventario':
                                        echo '📦';
                                        break;
                                    case 'clientes':
                                        echo '👥';
                                        break;
                                    case 'productos':
                                        echo '🛍️';
                                        break;
                                    default:
                                        echo '📄';
                                }
                                ?>
                            </div>
                            <div class="informe-info">
                                <h3><?= isset($informe['nombre']) ? htmlspecialchars($informe['nombre']) : 'Informe sin nombre' ?></h3>
                                <p>Generado el: <?= isset($informe['fecha']) ? htmlspecialchars($informe['fecha']) : (isset($informe['fecha_creacion']) ? htmlspecialchars($informe['fecha_creacion']) : 'Fecha desconocida') ?></p>
                            </div>
                            <div class="informe-actions">
                                <?php if (isset($informe['id'])): ?>
                                    <a href="descargar_informe.php?id=<?= $informe['id'] ?>" class="btn">Descargar</a>
                                    <a href="ver_informe.php?id=<?= $informe['id'] ?>" class="btn">Ver</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>