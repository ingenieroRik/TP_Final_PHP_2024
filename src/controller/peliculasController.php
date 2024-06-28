<?php
session_start();  // Iniciar sesión para manejar mensajes

// Configuración de CORS
$allowed_origins = ['https://cac-movies.zeabur.app'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}

//header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header('content-type: application/json; charset=utf-8');

// Manejo de solicitudes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    http_response_code(200);
    exit();
}



// Depuración de datos recibidos para ver en la consola del vsc
file_put_contents('php://stderr', print_r($_POST, true));
file_put_contents('php://stderr', print_r($_FILES, true));



require './src/model/peliculasModel.php';


$PeliculasModel= new peliculasModel();


    $url_front = $_ENV['URL_FRONT']; 
    

    /* ***************************************************************************************************************************************** */
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['buscar'])) {
       
        $respuesta = (!isset($_GET['id'])) ? $PeliculasModel->getPeliculas() : $PeliculasModel->getPeliculas($_GET['id']);
        echo json_encode($respuesta);
    }
    
    /* ****************************************************************************************************************************************** */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ( !isset($_POST['_method']) || strtoupper($_POST['_method']) !== 'PUT')) {
     
        // para subir el archivo de imagen desde la PC
        if (isset($_FILES['img_url']) && $_FILES['img_url']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['img_url']['tmp_name'];
            $fileName = $_FILES['img_url']['name'];
            $fileSize = $_FILES['img_url']['size'];
            $fileType = $_FILES['img_url']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = array('jpg', 'jpeg', 'png');
            
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = './assets/img/';
                $dest_path = $uploadFileDir . $fileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $message ='Archivo subido correctamente.';
                    $_POST['img_url'] = $dest_path;
                } else {
                    $message = 'Error al mover el archivo subido.';
                    $_POST['img_url'] = '';
                }
            } else {
                $message = 'Tipo de archivo no permitido. Solo archivos JPG, JPEG y PNG son permitidos.';
                $_POST['img_url'] = '';
            }
        } else {
            $message = 'No se subió ningún archivo.';
            $_POST['img_url'] = '';
        }

        // Inicializar una variable de respuesta
        $respuesta = [];

        // Validar los campos de los errores de cada campo de la BD
        $errores = [];

        if (!isset($_POST['titulo']) || is_null($_POST['titulo']) || empty(trim($_POST['titulo'])) || strlen($_POST['titulo']) > 100) {
            $errores['error1'] = 'El titulo de la pelicula no debe estar vacío y no debe de tener más de 80 caracteres';

        } else if (!isset($_POST['genero']) || is_null($_POST['genero']) || empty(trim($_POST['genero'])) || strlen((string)$_POST['genero']) > 50) {
            $errores['error2'] = 'El genero del pelicula no debe estar vacío y no tener más de 50 caracteres';

        } else if (!isset($_POST['calificacion']) || is_null($_POST['calificacion']) || empty(($_POST['calificacion'])) || strlen((string)$_POST['calificacion']) > 100) {
            $errores['error3'] = 'La calificacion de la pelicula no debe estar vacío y no tener más de 100 caracteres';

        } else if (!isset($_POST['descripcion']) || is_null($_POST['descripcion']) || empty(trim($_POST['descripcion'])) || strlen($_POST['descripcion']) > 150) {
            $errores['error4'] = 'La descripción del pelicula no debe estar vacía y no debe de tener más de 150 caracteres';

        } else if (!isset($_POST['anio']) || is_null($_POST['anio']) || empty(($_POST['anio'])) || !is_numeric($_POST['anio']) || strlen((string)$_POST['anio']) > 4) {
            $errores['error5'] = 'El año de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres';

        } else if (!isset($_POST['estrellas']) || is_null($_POST['estrellas']) || empty(($_POST['estrellas'])) || !is_numeric($_POST['estrellas']) || strlen((string)$_POST['estrellas']) > 4) {
            $errores['error6'] = 'las estrellas de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres';

        } else if (!isset($_POST['duracion']) || is_null($_POST['duracion']) || empty(($_POST['duracion'])) || !is_numeric($_POST['duracion']) || strlen((string)$_POST['duracion']) > 4) {
            $errores['error7'] = 'La duración de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres';

        } else if (!isset($_POST['img_url']) || is_null($_POST['img_url']) || empty(($_POST['img_url'])) || strlen((string)$_POST['img_url']) > 256) {
            $errores['error8'] = 'La película debe tener una imágen';
        }

        if (!empty($errores)) {
            $query = http_build_query(array_merge($errores, $_POST));
            header("Location: $url_front/pages/adminpeliculas.php?$query");
            exit();
        }

        // Si todas las validaciones pasan es decir no hay errores
        else {
            // Asumimos que $PeliculasModel->savePeliculas es el método para guardar el pelicula en la base de datos
            $resultado = $PeliculasModel->savePeliculas(
                $_POST['titulo'],
                $_POST['descripcion'],
                $_POST['genero'],                                              
                $_POST['calificacion'], 
                $_POST['anio'],
                $_POST['estrellas'],                                           
                $_POST['duracion'], 
                $_POST['img_url']
            );

             // Manejar el resultado de la operación para avisar el resultado en el frontend
        if (isset($resultado) && $resultado[0] === 'success') {
            $mensaje = $resultado[1];
            $_SESSION['mensaje'] = $mensaje;
            header("Location: $url_front/pages/adminpeliculas.php");
        } else {
            $error = isset($resultado) ? $resultado[1] : 'Error desconocido al guardar la película';
            $_SESSION['error'] = $error;
            header("Location: $url_front/pages/adminpeliculas.php");
        }
        exit();
        
        }
    }   /* ******************************************************************************************************************************************************
           manejamos el PUT como POST, no pude que hacer que funcione como PUT,
           asi que verificamos que venga x POST pero con _method=PUT, arriba
           en le POST verificamos que no sea _method=PUT */
       if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') { 
        parse_str(file_get_contents("php://input"), $_PUT);
        $_PUT= json_decode(file_get_contents('php://input',true));

         // para subir el archivo de imagen desde la PC
         if (isset($_FILES['img_url']) && $_FILES['img_url']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['img_url']['tmp_name'];
            $fileName = $_FILES['img_url']['name'];
            $fileSize = $_FILES['img_url']['size'];
            $fileType = $_FILES['img_url']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = array('jpg', 'jpeg', 'png');
            
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = './assets/img/';
                $dest_path = $uploadFileDir . $fileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $message ='Archivo subido correctamente.';
                    $_POST['img_url'] = $dest_path;
                } else {
                    $message = 'Error al mover el archivo subido.';
                    $_POST['img_url'] = '';
                }
            } else {
                $message = 'Tipo de archivo no permitido. Solo archivos JPG, JPEG y PNG son permitidos.';
                $_POST['img_url'] = '';
            }
        } else {
            $message = 'No se subió ningún archivo.';
            $_POST['img_url'] = '';
        }

        // Inicializar una variable de respuesta
        $respuesta = [];

        // Validar los campos de los errores de cada campo de la BD
        $errores = [];

        if (!isset($_POST['titulo']) || is_null($_POST['titulo']) || empty(trim($_POST['titulo'])) || strlen($_POST['titulo']) > 100) {
            $errores['error1'] = 'El titulo de la pelicula no debe estar vacío y no debe de tener más de 80 caracteres';

        } else if (!isset($_POST['genero']) || is_null($_POST['genero']) || empty(trim($_POST['genero'])) || strlen((string)$_POST['genero']) > 50) {
            $errores['error2'] = 'El genero del pelicula no debe estar vacío y no tener más de 50 caracteres';

        } else if (!isset($_POST['calificacion']) || is_null($_POST['calificacion']) || empty(($_POST['calificacion'])) || strlen((string)$_POST['calificacion']) > 100) {
            $errores['error3'] = 'La calificacion de la pelicula no debe estar vacío y no tener más de 100 caracteres';

        } else if (!isset($_POST['descripcion']) || is_null($_POST['descripcion']) || empty(trim($_POST['descripcion'])) || strlen($_POST['descripcion']) > 150) {
            $errores['error4'] = 'La descripción del pelicula no debe estar vacía y no debe de tener más de 150 caracteres';

        } else if (!isset($_POST['anio']) || is_null($_POST['anio']) || empty(($_POST['anio'])) || !is_numeric($_POST['anio']) || strlen((string)$_POST['anio']) > 4) {
            $errores['error5'] = 'El año de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres';

        } else if (!isset($_POST['estrellas']) || is_null($_POST['estrellas']) || empty(($_POST['estrellas'])) || !is_numeric($_POST['estrellas']) || strlen((string)$_POST['estrellas']) > 4) {
            $errores['error6'] = 'las estrellas de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres';

        } else if (!isset($_POST['duracion']) || is_null($_POST['duracion']) || empty(($_POST['duracion'])) || !is_numeric($_POST['duracion']) || strlen((string)$_POST['duracion']) > 4) {
            $errores['error7'] = 'La duración de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres';

        } else if (!isset($_POST['img_url']) || is_null($_POST['img_url']) || empty(($_POST['img_url'])) || strlen((string)$_POST['img_url']) > 256) {
            $errores['error8'] = 'La película debe tener una imágen';
        }


        if (!empty($errores)) {
            $query = http_build_query(array_merge($errores, $_POST));
            header("Location: $url_front/pages/peliculaEditada.php?$query");
            exit();
        }

         // Si todas las validaciones pasan es decir no hay errores
         else{
            $respuesta = $PeliculasModel->updatePeliculas(
                $_POST['id'], 
                $_POST['titulo'],
                $_POST['descripcion'],
                $_POST['genero'],                                              
                $_POST['calificacion'], 
                $_POST['anio'],
                $_POST['estrellas'],                                           
                $_POST['duracion'], 
                $_POST['img_url']
            );
        }
/*
             // Manejar el resultado de la operación para avisar el resultado en el frontend
             if (isset($resultado) && $resultado[0] === 'success') {
                $mensaje = $resultado[1];
                //$_SESSION['mensaje'] = $mensaje;
                header("Location: http://localhost:8000/pages/listados.html");
            } else {
                $error = isset($resultado) ? $resultado[1] : 'Error desconocido al guardar la película';
                $_SESSION['error'] = $error;
                header("Location: http://localhost:8000/pages/peliculaEditada.php");
            }
                */
                header("Location: $url_front/pages/listados.html");
            exit();
            
            

/*
        if(!isset($_PUT->id) || is_null($_PUT->id) || empty(trim($_PUT->id))){         
       }
        else if(!isset($_PUT->titulo) || is_null($_PUT->titulo) || empty(trim($_PUT->titulo)) || strlen($_PUT->titulo) > 80){
            $respuesta= ['error','El nombre de la pelicula no debe estar vacío y no debe de tener más de 80 caracteres'];
        }
        else if(!isset($_PUT->descripcion) || is_null($_PUT->descripcion) || empty(trim($_PUT->descripcion)) || strlen($_PUT->descripcion) > 150){
            $respuesta= ['error','La descripción del pelicula no debe estar vacía y no debe de tener más de 150 caracteres'];
        }
        else if(!isset($_PUT->genero) || is_null($_PUT->genero) || empty(trim($_PUT->genero)) || !is_numeric($_PUT->genero) || strlen($_PUT->genero) > 20){
            $respuesta= ['error','El precio de la pelicula no debe estar vacío , debe ser de tipo numérico y no tener más de 20 caracteres'];
        }
        // Validar el campo calificacion
        else if(!isset($_PUT->calificacion) || is_null($_PUT->calificacion) || empty(($_PUT->calificacion)) || strlen((string)$_PUT->calificacion) > 100){
            $respuesta = ['error', 'La calificacion de la pelicula no debe estar vacío y no tener más de 100 caracteres'];
        }
         // Validar el campo anio
         else if(!isset($_PUT->anio) || is_null($_PUT->anio) || empty(($_PUT->anio)) || !is_numeric($_PUTanio) || strlen((string)$_PUT->anio) > 4 ){
            $respuesta = ['error', 'El año de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres'];
        }
        // Validar el campo estrellas
        else if(!isset($_PUT->estrellas) || is_null($_PUT->estrellas) || empty(($_PUT->estrellas)) || !is_numeric($_PUT->estrellas) || strlen((string)$_PUT->estrellas) > 4 ){
            $respuesta = ['error', 'las estrellas de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres'];
        }
        // Validar el campo duracion
        else if(!isset($_PUT->duracion) || is_null($_PUT->duracion) || empty(($_PUT->duracion)) || !is_numeric($_PUT->duracion) || strlen((string)$_PUT->duracion) > 4){
            $respuesta = ['error', 'La duración de la pelicula no debe estar vacío, debe ser de tipo numérico y no tener más de 4 caracteres'];
        }
        // Validar el campo img_url
        else if(!isset($_PUT->img_url) || is_null($_PUT->img_url) || empty(($_PUT->img_url)) || !is_numeric($_PUT->img_url) || strlen((string)$_PUT->img_url) > 256){
            $respuesta = ['error', 'La película necesita una imágen'];
        }
        // Si todas las validaciones pasan es decir no hay errores
        else{
            $respuesta = $PeliculasModel->updatePeliculas(
                $_PUT->id, 
                $_PUT->titulo, 
                $_PUT->genero,
                $_PUT->descripcion, 
                $_PUT->calificacion, 
                $_PUT->anio, 
                $_PUT->estrellas,  
                $_PUT->duracion,         
                $_PUT->img_url);
        }
        echo json_encode($respuesta);
        */
    }
    /* *************************************************************************************************************************** */
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar']) ){
      
            $buscar = $_GET['buscar'];

            $resultados = $PeliculasModel->buscarPelicula($buscar);
          
            $peliculas = json_encode($resultados);
            $_SESSION['peliculas'] = $peliculas;
            header("Location: $url_front/pages/peliculaBuscada.php");
            exit();

    }

    /* ************************************************************************************************************************* */
      if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        parse_str(file_get_contents("php://input"), $_DELETE);
        $_DELETE= json_decode(file_get_contents('php://input',true));

        if(!isset($_DELETE->id) || is_null($_DELETE->id) || empty(trim($_DELETE->id))){
            $respuesta= ['error','El ID del pelicula no debe estar vacío'];
        }
        else{
            $respuesta = $PeliculasModel->deletePeliculas($_DELETE->id);
        }
        echo json_encode($respuesta);
    }


?>