<?php

require 'vendor/autoload.php'; // Asegúrate de que la ruta es correcta

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


include './src/controller/peliculasController.php';



?>
