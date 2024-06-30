<?php

// Configuración de CORS

//$allowedOrigins = array('http://127.0.0.1:5500', 'https://cac-movies.zeabur.app');

header('Access-Control-Allow-Origin: *'); //acceso a todos

header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
//header("Allow: GET, POST, OPTIONS, PUT, DELETE");
header('content-type: application/json; charset=utf-8');

// Manejar las solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}



// Depuración de datos recibidos para ver en la consola del vsc
file_put_contents('php://stderr', print_r($_POST, true));
file_put_contents('php://stderr', print_r($_FILES, true));

require './src/model/peliculasModel.php';

$PeliculasModel= new peliculasModel();

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
            $errores['error8'] = 'La película debe tener una imágen menor a 2 Mb';
        }

        if (!empty($errores)) {
            $query = http_build_query(array_merge($errores, $_POST));
            echo json_encode($errores);
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

             // Manejar el resultado de la operación para avisar el resultado
        if (isset($resultado) && $resultado[0] === 'success') {
                     
        } else {
            $error = isset($resultado) ? $resultado[1] : 'Error desconocido al guardar la película';                     
        }

        echo json_encode($resultado);
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
            $errores['error8'] = 'La película debe tener una imágen menor a 2 Mb';
        }

        if (!empty($errores)) {
            $query = http_build_query(array_merge($errores, $_POST));
            echo json_encode($errores);
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
                echo json_encode($respuesta);
            exit();

    }
    /* *************************************************************************************************************************** */
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscar']) ){
      
            $buscar = $_GET['buscar'];

            $resultados = $PeliculasModel->buscarPelicula($buscar);
        
            echo json_encode($resultados);
            exit();
    }

    /* ************************************************************************************************************************* */
    // para borrar uso GET porque no hay forma que reconozca el DELETE
        //if ($_SERVER['REQUEST_METHOD'] === 'GET' ){
            if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
                $id = $_GET['id'];


            // Obtener datos del cuerpo de la solicitud
            //parse_str(file_get_contents("php://input"), $_GET);

            // Intenta obtener el ID de la película de los datos enviados en el cuerpo de la solicitud
            //$id = $_GET['id'] ?? null;

            // Si no se encuentra en el cuerpo de la solicitud, intenta obtenerlo de la URL
            //if (is_null($id) || empty(trim($id))) {
             //   $id = $_GET['id'] ?? null;
           // }

            // Verifica si el ID de la película es válido
            if (is_null($id) || empty(trim($id))) {
                $respuesta = ['error' => 'El ID de la película no debe estar vacío'];
            } else {
                // Llama a la función deletePeliculas con el ID obtenido
                $respuesta = $PeliculasModel->deletePeliculas($id);
            }

            // Envía la respuesta en formato JSON
            echo json_encode($respuesta);
        }


?>