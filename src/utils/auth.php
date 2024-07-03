<?php
session_start();

header("Access-Control-Allow-Origin: * ");
header("Access-Control-Allow-Methods: * ");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

$adminKey = $_ENV['ADMIN_KEY'];


// Recibir y procesar la solicitud del frontend

    $password = $_POST['password'];

    if ($password === 'password') {
        echo json_encode(['success' => true, 'message' => 'Autenticación exitosa']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
}


?>