<?php
require_once __DIR__ . '/../Config/Connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$connection = new Connection();
$pdo = $connection->connect();

function generarInforme($pdo, $tipo, $fechaInicio, $fechaFin) {
   
}

$sql = "SELECT * FROM informes_programados WHERE proxima_ejecucion <= NOW()";
$informesProgramados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($informesProgramados as $informe) {
    $fechaFin = new DateTime();
    $fechaInicio = clone $fechaFin;

    switch ($informe['frecuencia']) {
        case 'diario':
            $fechaInicio->modify('-1 day');
            break;
        case 'semanal':
            $fechaInicio->modify('-1 week');
            break;
        case 'mensual':
            $fechaInicio->modify('-1 month');
            break;
    }

    $nombreArchivo = generarInforme($pdo, $informe['tipo'], $fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d'));

    // Enviar el informe por correo
    $mail = new PHPMailer(true);
    try {
        // Configurar el servidor de correo
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_email@example.com'; 
        $mail->Password   = 'tu_contraseña';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('from@example.com', 'Sistema de Informes');
        $mail->addAddress($informe['email']);
        $mail->addAttachment(__DIR__ . "/../informes/$nombreArchivo");

        $mail->isHTML(true);
        $mail->Subject = 'Informe Programado: ' . $informe['tipo'];
        $mail->Body    = 'Adjunto encontrará el informe programado.';

        $mail->send();
        echo "Informe enviado a " . $informe['email'] . "<br>";

        // Actualizar la próxima ejecución
        $proximaEjecucion = new DateTime();
        switch ($informe['frecuencia']) {
            case 'diario':
                $proximaEjecucion->modify('+1 day');
                break;
            case 'semanal':
                $proximaEjecucion->modify('+1 week');
                break;
            case 'mensual':
                $proximaEjecucion->modify('+1 month');
                break;
        }

        $sqlUpdate = "UPDATE informes_programados 
                      SET ultima_ejecucion = NOW(), 
                          proxima_ejecucion = :proxima 
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'proxima' => $proximaEjecucion->format('Y-m-d H:i:s'),
            'id' => $informe['id']
        ]);

    } catch (Exception $e) {
        echo "Error al enviar el informe: {$mail->ErrorInfo}<br>";
    }
}