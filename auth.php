<?php
session_start();
require 'db.php';
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'vaca272000@gmail.com');
define('SMTP_PASS', 'gewn lixa epiz wesc');

function enviarCorreoConfirmacion($email, $nombre, $token)
{
    $mail = new PHPMailer(true);
    try {

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';


        $mail->SMTPDebug = 2;
        $_SESSION['smtp_debug'] = "";
        $mail->Debugoutput = function ($str, $level) {
            $_SESSION['smtp_debug'] .= "$level: $str\n";
        };


        $mail->setFrom(SMTP_USER, 'Tutorias Online');
        $mail->addAddress($email, $nombre);


        $mail->isHTML(true);
        $mail->Subject = 'Confirma tu cuenta - Tutorias Online';

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $currentPath = dirname($_SERVER['PHP_SELF']);
        $currentPath = ($currentPath == DIRECTORY_SEPARATOR || $currentPath == '/') ? "" : $currentPath;
        $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $currentPath . "/verify.php?token=" . $token;

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f7f6;'>
                <h2 style='color: #3553ff;'>¡Bienvenido a Tutorias Online!</h2>
                <p>Hola $nombre, gracias por registrarte. Para activar tu cuenta, haz clic en el siguiente botón:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$url' style='display: inline-block; padding: 12px 25px; background-color: #3553ff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Activar mi cuenta</a>
                </div>
                <p>Si no puedes ver el botón, copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; color: #666;'>$url</p>
                <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;'>
                <p style='font-size: 0.8rem; color: #999;'>Si no solicitaste esta cuenta, puedes ignorar este correo.</p>
            </div>";

        $mail->send();
        unset($_SESSION['smtp_debug']);
        return true;
    } catch (PHPMailerException $e) {
        $_SESSION['error_detalle'] = $mail->ErrorInfo;
        return false;
    }
}


if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, token_confirmacion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password, $token]);

        if (enviarCorreoConfirmacion($email, $nombre, $token)) {
            $_SESSION['message'] = "Registro exitoso. Revisa tu correo para activar tu cuenta.";
        } else {
            $error_msg = "Usuario registrado, pero hubo un error al enviar el correo de confirmación.";
            if (isset($_SESSION['error_detalle'])) {
                $error_msg .= " Detalles: " . $_SESSION['error_detalle'];
                unset($_SESSION['error_detalle']);
            }
            if (isset($_SESSION['smtp_debug']) && !empty($_SESSION['smtp_debug'])) {
                $error_msg .= " | Log: " . substr(strip_tags($_SESSION['smtp_debug']), -200);
                unset($_SESSION['smtp_debug']);
            }
            $_SESSION['error'] = $error_msg;
        }
    } catch (\Exception $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
    }
    header("Location: register.php");
    exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['activo'] == 1) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Tu cuenta aún no ha sido activada. Revisa tu correo.";
        }
    } else {
        $_SESSION['error'] = "Correo o contraseña incorrectos.";
    }
    header("Location: login.php");
    exit();
}
?>