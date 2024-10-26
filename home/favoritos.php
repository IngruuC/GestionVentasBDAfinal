<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

// Manejar agregado/eliminación de favoritos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_favorito'])) {
        $producto_id = $_POST['producto_id'];
        $sql = "INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $producto_id]);
        $mensaje = "Producto agregado a favoritos.";
    }
    
    if (isset($_POST['eliminar_favorito'])) {
        $producto_id = $_POST['producto_id'];
        $sql = "DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $producto_id]);
        $mensaje = "Producto eliminado de favoritos.";
    }
}

// Obtener favoritos del usuario
function getProductosFavoritos($pdo, $usuario_id) {
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock
            FROM productos p
            JOIN favoritos f ON p.id = f.producto_id
            WHERE f.usuario_id = ?
            ORDER BY f.fecha_agregado DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener productos no favoritos
function getProductosNoFavoritos($pdo, $usuario_id) {
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock
            FROM productos p
            WHERE p.id NOT IN (
                SELECT producto_id 
                FROM favoritos 
                WHERE usuario_id = ?
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$favoritos = getProductosFavoritos($pdo, $_SESSION['user_id']);
$otros_productos = getProductosNoFavoritos($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .producto-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .producto-info {
            flex-grow: 1;
        }
        
        .producto-precio {
            font-size: 1.2em;
            color: #2ecc71;
            font-weight: bold;
        }
        
        .stock-badge {
            background: #f1c40f;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .actions button {
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            margin-left: 10px;
        }
        
        .btn-remove {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-add {
            background-color: #2ecc71;
            color: white;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Mi Cuenta</h2>
        <a href="dashboardUser.php">Inicio</a>
        <a href="perfil.php">Mi Perfil</a>
        <a href="compras.php">Mis Compras</a>
        <a href="favoritos.php">Favoritos</a>
        <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
    </div>

    <div class="main">
        <h1>Mis Favoritos</h1>

        <?php if (isset($mensaje)): ?>
            <div class="message">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Productos Favoritos -->
        <h2 class="section-title">❤️ Mis Productos Favoritos</h2>
        <?php if (empty($favoritos)): ?>
            <div class="producto-card">
                <p>No tienes productos favoritos aún.</p>
            </div>
        <?php else: ?>
            <?php foreach ($favoritos as $producto): ?>
                <div class="producto-card">
                    <div class="producto-info">
                        <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <div class="producto-precio">
                            $<?= number_format($producto['precio'], 2) ?>
                            <span class="stock-badge">Stock: <?= $producto['stock'] ?></span>
                        </div>
                    </div>
                    <div class="actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                            <button type="submit" name="eliminar_favorito" class="btn-remove">
                                Eliminar de favoritos ❌
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Otros Productos -->
        <h2 class="section-title">🛍️ Otros Productos</h2>
        <?php foreach ($otros_productos as $producto): ?>
            <div class="producto-card">
                <div class="producto-info">
                    <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <div class="producto-precio">
                        $<?= number_format($producto['precio'], 2) ?>
                        <span class="stock-badge">Stock: <?= $producto['stock'] ?></span>
                    </div>
                </div>
                <div class="actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                        <button type="submit" name="agregar_favorito" class="btn-add">
                            Agregar a favoritos ❤️
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>