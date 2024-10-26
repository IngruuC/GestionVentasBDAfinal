<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 2) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getProductosInventario($pdo) {
    $sql = "SELECT id, nombre, stock, precio FROM productos ORDER BY stock ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductosBajoStock($pdo) {
    $sql = "SELECT id, nombre, stock FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 5";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$productos = getProductosInventario($pdo);
$productosBajoStock = getProductosBajoStock($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar_stock':
                $id = $_POST['id'];
                $cantidad = $_POST['cantidad'];

                // Registrar la entrada en entradas_productos
                $sql = "INSERT INTO entradas_productos (producto_id, cantidad, fecha_entrada) VALUES (?, ?, CURRENT_DATE())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id, $cantidad]);

                // Actualizar el stock
                $sql = "UPDATE productos SET stock = stock + ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cantidad, $id]);

                $mensaje = "Stock actualizado correctamente.";
                break;
        }

        $productos = getProductosInventario($pdo);
        $productosBajoStock = getProductosBajoStock($pdo);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Stock</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .producto-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .producto-info {
            flex-grow: 1;
        }
        .stock-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            display: inline-block;
        }
        .stock-bajo { background-color: #e74c3c; }
        .stock-medio { background-color: #f39c12; }
        .stock-alto { background-color: #2ecc71; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="dashboardProveedor.php">Inicio</a>
        <a href="entregas.php">Gestión de Entregas</a>
        <a href="stock.php">Control de Stock</a>
        <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
    </div>

    <div class="main">
        <h1>Control de Stock</h1>

        <?php if (isset($mensaje)): ?>
            <p style="color: green;"><?= $mensaje ?></p>
        <?php endif; ?>

        <div class="grid-container">
            <div class="card">
                <h2>🚨 Productos con Bajo Stock</h2>
                <?php foreach ($productosBajoStock as $producto): ?>
                    <div class="producto-card">
                        <div class="stock-indicator stock-bajo"></div>
                        <div class="producto-info">
                            <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                            <p>Stock actual: <?= $producto['stock'] ?></p>
                        </div>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="accion" value="agregar_stock">
                            <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                            <input type="number" name="cantidad" placeholder="Cantidad" required min="1">
                            <button type="submit">Reponer Stock</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <h2>📊 Resumen de Inventario</h2>
                <canvas id="inventarioChart"></canvas>
            </div>
        </div>

        <div class="card">
            <h2>📦 Inventario Completo</h2>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td>
                                <div class="stock-indicator <?= 
                                    $producto['stock'] < 10 ? 'stock-bajo' : 
                                    ($producto['stock'] < 30 ? 'stock-medio' : 'stock-alto') 
                                ?>"></div>
                                <?= htmlspecialchars($producto['nombre']) ?>
                            </td>
                            <td><?= $producto['stock'] ?></td>
                            <td>$<?= number_format($producto['precio'], 2) ?></td>
                            <td>
                                <form action="" method="POST" style="display: inline;">
                                    <input type="hidden" name="accion" value="agregar_stock">
                                    <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                                    <input type="number" name="cantidad" placeholder="Cantidad" required min="1">
                                    <button type="submit">Agregar Stock</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Gráfico de inventario
        var ctx = document.getElementById('inventarioChart').getContext('2d');
        var inventarioChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($productos, 'nombre')) ?>,
                datasets: [{
                    label: 'Stock Actual',
                    data: <?= json_encode(array_column($productos, 'stock')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
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