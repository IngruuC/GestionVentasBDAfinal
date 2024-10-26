<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 2) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getEntregasProductos($pdo) {
    $sql = "SELECT ep.id, p.nombre, ep.cantidad, ep.fecha_entrada 
            FROM entradas_productos ep
            JOIN productos p ON ep.producto_id = p.id
            ORDER BY ep.fecha_entrada DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductos($pdo) {
    $sql = "SELECT id, nombre FROM productos ORDER BY nombre";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$entregas = getEntregasProductos($pdo);
$productos = getProductos($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'registrar_entrada':
                $producto_id = $_POST['producto_id'];
                $cantidad = $_POST['cantidad'];
                $fecha_entrada = $_POST['fecha_entrada'];

                $sql = "INSERT INTO entradas_productos (producto_id, cantidad, fecha_entrada) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$producto_id, $cantidad, $fecha_entrada]);

                $sql = "UPDATE productos SET stock = stock + ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cantidad, $producto_id]);

                $mensaje = "Entrega registrada correctamente.";
                break;
        }
        // Actualizar lista de entregas
        $entregas = getEntregasProductos($pdo);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Entregas</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .entrega-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .entrega-form form {
            display: grid;
            gap: 15px;
            max-width: 500px;
        }
        
        .entrega-form select,
        .entrega-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .entrega-form button {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .entregas-lista {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .entrega-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .badge-success {
            background: #2ecc71;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
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
        <h1>Gestión de Entregas</h1>

        <?php if (isset($mensaje)): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <div class="entrega-form">
            <h2>📦 Registrar Nueva Entrega</h2>
            <form action="" method="POST">
                <input type="hidden" name="accion" value="registrar_entrada">
                
                <div>
                    <label for="producto_id">Producto:</label>
                    <select name="producto_id" id="producto_id" required>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= $producto['id'] ?>"><?= htmlspecialchars($producto['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="cantidad">Cantidad:</label>
                    <input type="number" name="cantidad" id="cantidad" required min="1">
                </div>

                <div>
                    <label for="fecha_entrada">Fecha de Entrega:</label>
                    <input type="date" name="fecha_entrada" id="fecha_entrada" required value="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit">Registrar Entrega</button>
            </form>
        </div>

        <div class="entregas-lista">
            <h2>📋 Historial de Entregas</h2>
            <?php foreach ($entregas as $entrega): ?>
                <div class="entrega-item">
                    <div>
                        <strong><?= htmlspecialchars($entrega['nombre']) ?></strong>
                        <br>
                        Cantidad: <?= $entrega['cantidad'] ?> unidades
                    </div>
                    <div>
                        <span class="badge-success">
                            Entregado: <?= date('d/m/Y', strtotime($entrega['fecha_entrada'])) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>