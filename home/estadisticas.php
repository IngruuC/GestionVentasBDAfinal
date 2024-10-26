<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getVentasPorPeriodo($pdo) {
    $sql = "SELECT DATE(fecha_venta) as fecha, SUM(v.cantidad * p.precio) as total
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            GROUP BY DATE(fecha_venta)
            ORDER BY fecha_venta
            LIMIT 30";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getTopProductos($pdo) {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as cantidad
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            GROUP BY p.id
            ORDER BY cantidad DESC
            LIMIT 5";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getComparacionSucursales($pdo) {
    $sql = "SELECT s.nombre, SUM(v.cantidad * p.precio) as total
            FROM sucursales s
            JOIN ventas v ON s.id = v.sucursal_id
            JOIN productos p ON v.producto_id = p.id
            GROUP BY s.id
            ORDER BY total DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductoMasVendido($pdo) {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as total_vendido
            FROM productos p
            JOIN ventas v ON p.id = v.producto_id
            GROUP BY p.id
            ORDER BY total_vendido DESC
            LIMIT 1";
    return $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
}

// Nueva función para tendencias de ventas
function getTendenciaVentas($pdo) {
    $sql = "SELECT 
            DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
            SUM(v.cantidad * p.precio) as total
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
            ORDER BY mes DESC
            LIMIT 2";
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($result) < 2) {
        return ['tendencia' => 'estable', 'porcentaje' => 0];
    }
    
    $mesActual = $result[0]['total'];
    $mesAnterior = $result[1]['total'];
    
    $diferencia = $mesActual - $mesAnterior;
    $porcentaje = ($diferencia / $mesAnterior) * 100;
    
    if ($porcentaje > 0) {
        return ['tendencia' => 'subida', 'porcentaje' => abs($porcentaje)];
    } elseif ($porcentaje < 0) {
        return ['tendencia' => 'bajada', 'porcentaje' => abs($porcentaje)];
    } else {
        return ['tendencia' => 'estable', 'porcentaje' => 0];
    }
}

// Nueva función para ranking de sucursales
function getRankingSucursales($pdo) {
    $sql = "SELECT 
            s.nombre,
            SUM(v.cantidad * p.precio) as total_ventas
            FROM sucursales s
            JOIN ventas v ON s.id = v.sucursal_id
            JOIN productos p ON v.producto_id = p.id
            GROUP BY s.id, s.nombre
            ORDER BY total_ventas DESC
            LIMIT 3";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$ventasPorPeriodo = getVentasPorPeriodo($pdo);
$topProductos = getTopProductos($pdo);
$comparacionSucursales = getComparacionSucursales($pdo);
$productoMasVendido = getProductoMasVendido($pdo);
$totalVentas = array_sum(array_column($ventasPorPeriodo, 'total'));
$tendencia = getTendenciaVentas($pdo);
$rankingSucursales = getRankingSucursales($pdo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .stat-icon {
            font-size: 48px;
            margin-right: 20px;
        }
        .stat-info h3 {
            margin: 0;
            font-size: 24px;
        }
        .stat-info p {
            margin: 5px 0 0;
            font-size: 16px;
            color: #666;
        }
        .product-spotlight {
            background-color: #f0f0f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .product-spotlight img {
            max-width: 200px;
            margin-bottom: 15px;
        }
        .tendencia-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .tendencia-valor {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tendencia-subida {
            color: #2ecc71;
        }
        .tendencia-bajada {
            color: #e74c3c;
        }
        .tendencia-estable {
            color: #f1c40f;
        }
        .ranking-podio {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .ranking-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .medalla {
            font-size: 24px;
            margin-right: 15px;
            min-width: 40px;
            text-align: center;
        }
        .sucursal-info {
            flex-grow: 1;
        }
        .sucursal-nombre {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .sucursal-ventas {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="dashboard.php">Inicio</a>
        <a href="estadisticas.php">Estadísticas</a>
        <a href="inventario.php">Inventario</a>
        <a href="configuracion.php">Configuración</a>
        <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
        <a href="javascript:history.back()">Volver</a>
    </div>
    <div class="main">
        <h1>Estadísticas del Supermercado</h1>
        
        <!-- Nueva card de tendencia -->
        <div class="tendencia-card">
            <h2>Tendencia de Ventas</h2>
            <div class="tendencia-valor <?php echo 'tendencia-' . $tendencia['tendencia']; ?>">
                <?php
                switch($tendencia['tendencia']) {
                    case 'subida':
                        echo '↑ +' . number_format($tendencia['porcentaje'], 1) . '%';
                        break;
                    case 'bajada':
                        echo '↓ -' . number_format($tendencia['porcentaje'], 1) . '%';
                        break;
                    default:
                        echo '→ 0%';
                }
                ?>
            </div>
        </div>

        <!-- Nuevo ranking de sucursales -->
        <div class="ranking-podio">
            <h2>Ranking de Sucursales</h2>
            <?php 
            $medallas = ['🥇', '🥈', '🥉'];
            foreach ($rankingSucursales as $index => $sucursal): ?>
                <div class="ranking-item">
                    <div class="medalla"><?php echo $medallas[$index]; ?></div>
                    <div class="sucursal-info">
                        <div class="sucursal-nombre"><?php echo htmlspecialchars($sucursal['nombre']); ?></div>
                        <div class="sucursal-ventas">
                            Ventas: $<?php echo number_format($sucursal['total_ventas'], 2); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="grid-container">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>Total de Ventas</h3>
                    <p>$<?= number_format($totalVentas, 2) ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3>Promedio Diario</h3>
                    <p>$<?= number_format($totalVentas / count($ventasPorPeriodo), 2) ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Ventas por Período</h2>
            <canvas id="ventasPorPeriodoChart"></canvas>
        </div>
        
        <div class="grid-container">
            <div class="card">
                <h2>Top 5 Productos</h2>
                <canvas id="topProductosChart"></canvas>
            </div>
            
            <div class="product-spotlight">
    <h2>Producto Más Vendido</h2>
    <div style="font-size: 64px; margin: 20px 0;">📦</div>
    <h3><?= htmlspecialchars($productoMasVendido['nombre']) ?></h3>
    <p>Unidades vendidas: <?= $productoMasVendido['total_vendido'] ?></p>
</div>
        </div>
        
        <div class="card">
            <h2>Comparación de Sucursales</h2>
            <canvas id="comparacionSucursalesChart"></canvas>
        </div>
    </div>

    <script>
        // Gráfico de Ventas por Período
        new Chart(document.getElementById('ventasPorPeriodoChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($ventasPorPeriodo, 'fecha')) ?>,
                datasets: [{
                    label: 'Ventas Diarias',
                    data: <?= json_encode(array_column($ventasPorPeriodo, 'total')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Top 5 Productos
        new Chart(document.getElementById('topProductosChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topProductos, 'nombre')) ?>,
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: <?= json_encode(array_column($topProductos, 'cantidad')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Comparación de Sucursales
        new Chart(document.getElementById('comparacionSucursalesChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($comparacionSucursales, 'nombre')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($comparacionSucursales, 'total')) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>