<?php






// para la autenticacion del administrador del front. en .env esta la clave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'auth') {
    include 'src/utils/auth.php';
    exit;
}
include './src/controller/peliculasController.php';

?>
