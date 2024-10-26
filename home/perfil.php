<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

// Obtener datos del usuario
function getDatosUsuario($pdo, $user_id) {
    $sql = "SELECT u.*, c.nombre as nombre_cliente, c.email, c.telefono 
            FROM usuarios u 
            LEFT JOIN clientes c ON c.usuario_id = u.id
            WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$usuario = getDatosUsuario($pdo, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar_perfil'])) {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'];
        
        // Verificar si ya existe el cliente
        $sql = "SELECT id FROM clientes WHERE usuario_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            // Actualizar cliente existente
            $sql = "UPDATE clientes SET nombre = ?, email = ?, telefono = ? WHERE usuario_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $telefono, $_SESSION['user_id']]);
        } else {
            // Crear nuevo cliente
            $sql = "INSERT INTO clientes (usuario_id, nombre, email, telefono) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $nombre, $email, $telefono]);
        }

        $mensaje = "Perfil actualizado correctamente.";
        $usuario = getDatosUsuario($pdo, $_SESSION['user_id']);
    }

    if (isset($_POST['cambiar_password'])) {
        $password_actual = $_POST['password_actual'];
        $password_nuevo = $_POST['password_nuevo'];
        $password_confirmar = $_POST['password_confirmar'];

        // Verificar contraseña actual
        if (password_verify($password_actual, $usuario['password'])) {
            if ($password_nuevo === $password_confirmar) {
                $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$password_hash, $_SESSION['user_id']]);
                $mensaje = "Contraseña actualizada correctamente.";
            } else {
                $error = "Las contraseñas nuevas no coinciden.";
            }
        } else {
            $error = "La contraseña actual es incorrecta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            background-color: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            margin: 0 auto 20px;
        }

        button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #2980b9;
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
        <h1>Mi Perfil</h1>

        <?php if (isset($mensaje)): ?>
            <div class="alert alert-success"><?= $mensaje ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="avatar">
                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
            </div>
            <h2><?= htmlspecialchars($_SESSION['username']) ?></h2>
            
            <form action="" method="POST">
                <input type="hidden" name="actualizar_perfil" value="1">
                
                <div class="form-group">
                    <label>Nombre completo:</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre_cliente'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                </div>

                <button type="submit">Actualizar Perfil</button>
            </form>
        </div>

        <div class="profile-card">
            <h2>Cambiar Contraseña</h2>
            <form action="" method="POST">
                <input type="hidden" name="cambiar_password" value="1">
                
                <div class="form-group">
                    <label>Contraseña actual:</label>
                    <input type="password" name="password_actual" required>
                </div>

                <div class="form-group">
                    <label>Nueva contraseña:</label>
                    <input type="password" name="password_nuevo" required>
                </div>

                <div class="form-group">
                    <label>Confirmar nueva contraseña:</label>
                    <input type="password" name="password_confirmar" required>
                </div>

                <button type="submit">Cambiar Contraseña</button>
            </form>
        </div>
    </div>
</body>
</html>