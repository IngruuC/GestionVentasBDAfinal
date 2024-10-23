<?php
session_start();

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../Config/Connection.php';
$connection = new Connection();
$pdo = $connection->connect();

// Función para obtener el nombre del rol
function getRoleName($pdo, $role_id) {
    $stmt = $pdo->prepare("SELECT nombre FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    return $role ? $role['nombre'] : 'Desconocido';
}

$role_name = getRoleName($pdo, $_SESSION['role_id']);

// Función para obtener ventas por empresa
function getVentasPorEmpresa($pdo) {
    $sql = "SELECT e.id, e.nombre, e.estado, SUM(v.cantidad * p.precio) as total_ventas
            FROM empresas e
            LEFT JOIN sucursales s ON e.id = s.empresa_id
            LEFT JOIN ventas v ON s.id = v.sucursal_id
            LEFT JOIN productos p ON v.producto_id = p.id
            GROUP BY e.id
            ORDER BY total_ventas DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener ventas por sucursal de una empresa
function getVentasPorSucursal($pdo, $empresa_id) {
    $sql = "SELECT s.id, s.nombre, SUM(v.cantidad * p.precio) as total_ventas
            FROM sucursales s
            LEFT JOIN ventas v ON s.id = v.sucursal_id
            LEFT JOIN productos p ON v.producto_id = p.id
            WHERE s.empresa_id = :empresa_id
            GROUP BY s.id
            ORDER BY total_ventas DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['empresa_id' => $empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener ventas por producto de una sucursal
function getVentasPorProducto($pdo, $sucursal_id) {
    $sql = "SELECT p.id, p.nombre, SUM(v.cantidad) as cantidad_vendida, SUM(v.cantidad * p.precio) as total_ventas
            FROM productos p
            LEFT JOIN ventas v ON p.id = v.producto_id
            WHERE v.sucursal_id = :sucursal_id
            GROUP BY p.id
            ORDER BY total_ventas DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['sucursal_id' => $sucursal_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener ventas por cliente
function getVentasPorCliente($pdo) {
    $sql = "SELECT c.id, c.nombre, SUM(v.cantidad * p.precio) as total_ventas
            FROM clientes c
            LEFT JOIN ventas v ON c.id = v.cliente_id
            LEFT JOIN productos p ON v.producto_id = p.id
            GROUP BY c.id
            ORDER BY total_ventas DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$nivel = isset($_GET['nivel']) ? $_GET['nivel'] : 'empresas';
$empresa_id = isset($_GET['empresa_id']) ? $_GET['empresa_id'] : null;
$sucursal_id = isset($_GET['sucursal_id']) ? $_GET['sucursal_id'] : null;

// Determinar qué contenido mostrar basado en la opción seleccionada
$selectedOption = isset($_GET['option']) ? $_GET['option'] : 'inicio';

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .empresa-activa { color: green; }
        .empresa-inactiva { color: red; }
        .card { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="sidebar">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Inicio</a>
    <a href="estadisticas.php">Estadísticas</a>
    <a href="informes.php">Informes</a>
    <a href="configuracion.php">Configuración</a>
    <a href="inventario.php">Gestión de Inventario</a>
    <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
    </div>
    <div class="main">
        <h1>Dashboard - <?php echo htmlspecialchars($role_name); ?></h1>
        
        <?php if ($selectedOption === 'inicio'): ?>
            <?php if ($nivel == 'empresas'): ?>
                <div class="card">
                    <h2>Ventas por Empresa</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Estado</th>
                                <th>Total Ventas</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getVentasPorEmpresa($pdo) as $empresa): ?>
                                <tr>
                                    <td><?= htmlspecialchars($empresa['nombre']) ?></td>
                                    <td class="<?= $empresa['estado'] == 'activo' ? 'empresa-activa' : 'empresa-inactiva' ?>">
                                        <?= ucfirst($empresa['estado']) ?>
                                    </td>
                                    <td>$<?= number_format($empresa['total_ventas'], 2) ?></td>
                                    <td>
                                        <a href="?nivel=sucursales&empresa_id=<?= $empresa['id'] ?>">Ver Sucursales</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($nivel == 'sucursales' && $empresa_id): ?>
                <div class="card">
                    <h2>Ventas por Sucursal</h2>
                    <a href="?nivel=empresas">Volver a Empresas</a>
                    <table>
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Total Ventas</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getVentasPorSucursal($pdo, $empresa_id) as $sucursal): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sucursal['nombre']) ?></td>
                                    <td>$<?= number_format($sucursal['total_ventas'], 2) ?></td>
                                    <td>
                                        <a href="?nivel=productos&sucursal_id=<?= $sucursal['id'] ?>">Ver Productos</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($nivel == 'productos' && $sucursal_id): ?>
                <div class="card">
                    <h2>Ventas por Producto</h2>
                    <a href="?nivel=empresas">Volver a Empresas</a>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad Vendida</th>
                                <th>Total Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getVentasPorProducto($pdo, $sucursal_id) as $producto): ?>
                                <tr>
                                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                    <td><?= $producto['cantidad_vendida'] ?></td>
                                    <td>$<?= number_format($producto['total_ventas'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Ventas por Cliente</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Total Ventas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (getVentasPorCliente($pdo) as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                                <td>$<?= number_format($cliente['total_ventas'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <canvas id="ventasChart" width="400" height="200"></canvas>
            </div>

            <?php if ($_SESSION['role_id'] == 1): ?>
    <div class="card">
        <h2>Ejecutar Informes Programados</h2>
        <p>Haz clic en el botón para ejecutar manualmente los informes programados.</p>
        <a href="ejecutar_informes.php" class="btn">Ejecutar Informes</a>
    </div>
<?php endif; ?>

        <?php elseif ($selectedOption === 'estadisticas'): ?>
            <div class="card">
                <h2>Estadísticas</h2>
                <p>Aquí puedes mostrar gráficos y estadísticas detalladas.</p>
            </div>
        <?php elseif ($selectedOption === 'informes'): ?>
            <div class="card">
                <h2>Informes</h2>
                <p>Sección para generar y ver informes.</p>
            </div>
        <?php elseif ($selectedOption === 'configuracion'): ?>
            <div class="card">
                <h2>Configuración</h2>
                <p>Opciones de configuración del sistema.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        var ctx = document.getElementById('ventasChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    if ($nivel == 'empresas') {
                        echo implode(',', array_map(function($e) { return "'".$e['nombre']."'"; }, getVentasPorEmpresa($pdo)));
                    } elseif ($nivel == 'sucursales') {
                        echo implode(',', array_map(function($s) { return "'".$s['nombre']."'"; }, getVentasPorSucursal($pdo, $empresa_id)));
                    } elseif ($nivel == 'productos') {
                        echo implode(',', array_map(function($p) { return "'".$p['nombre']."'"; }, getVentasPorProducto($pdo, $sucursal_id)));
                    }
                ?>],
                datasets: [{
                    label: 'Total Ventas',
                    data: [<?php 
                        if ($nivel == 'empresas') {
                            echo implode(',', array_map(function($e) { return $e['total_ventas']; }, getVentasPorEmpresa($pdo)));
                        } elseif ($nivel == 'sucursales') {
                            echo implode(',', array_map(function($s) { return $s['total_ventas']; }, getVentasPorSucursal($pdo, $empresa_id)));
                        } elseif ($nivel == 'productos') {
                            echo implode(',', array_map(function($p) { return $p['total_ventas']; }, getVentasPorProducto($pdo, $sucursal_id)));
                        }
                    ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>