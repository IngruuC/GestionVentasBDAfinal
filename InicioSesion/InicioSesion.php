<?php
require_once '../config/Connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $connection = new Connection();
        $pdo = $connection->connect();

        $sql = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error_message'] = "El usuario no existe.";
        } elseif (!password_verify($password, $user['password'])) {
            $_SESSION['error_message'] = "La contraseña es incorrecta.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];

            // Redirigir según el rol
            switch ($user['role_id']) {
                case 1: // Administrador
                    header('Location: ../Home/dashboard.php');
                    break;
                case 2: // Proveedor
                    header('Location: ../Home/dashboardProveedor.php');
                    break;
                case 3: // Usuario/Cliente
                    header('Location: ../Home/dashboardUser.php');
                    break;
                default:
                    $_SESSION['error_message'] = "Rol no reconocido";
                    header('Location: ../index.php');
                    break;
            }
            exit();
        }
    } catch (\Throwable $th) {
        $_SESSION['error_message'] = "Error en la conexión: " . $th->getMessage();
    }
}

header('Location: ../index.php');
exit();