<?php
session_start();
require 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];


    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token_confirmacion = ? AND activo = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {

        $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1, token_confirmacion = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        $_SESSION['message'] = "¡Cuenta activada con éxito! Ya puedes iniciar sesión.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "El token de activación no es válido o ya ha caducado.";
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>