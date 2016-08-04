<?php

require 'controladores/empresa_cliente.php';
require 'controladores/departamento.php';
require 'controladores/usuarios.php';
require 'controladores/coordenadas.php';
require 'controladores/gps.php';
require 'controladores/enlace.php';
require 'controladores/contactos.php';
require 'vistas/VistaXML.php';
require 'vistas/VistaJson.php';
require 'utilidades/ExcepcionApi.php';

// Constantes de estado
const ESTADO_URL_INCORRECTA = 2;
const ESTADO_EXISTENCIA_RECURSO = 3;
const ESTADO_METODO_NO_PERMITIDO = 4;

// Preparar manejo de excepciones
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'json';


//http://localhost/api.peopleapp.com/v1/usuarios/registro
//{"nombre":"nombresito","contrasena":"123","correo":"email@gmail.com"}

switch ($formato) {
    case 'xml':
        $vista = new VistaXML();
        break;
    case 'json':
    default:
        $vista = new VistaJson();
}

set_exception_handler(function ($exception) use ($vista) {
    $cuerpo = array(
        "estado" => $exception->estado,
        "mensaje" => $exception->getMessage()
    );
    if ($exception->getCode()) {
        $vista->estado = $exception->getCode();
    } else {
        $vista->estado = 500;
    }

    $vista->imprimir($cuerpo);
}
);

// Extraer segmento de la url
if (isset($_GET['PATH_INFO']))
    $peticion = explode('/', $_GET['PATH_INFO']);
else
    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, utf8_encode("No se reconoce la peticion"));

// Obtener recurso
$recurso = array_shift($peticion);
$recursos_existentes = array('contactos', 'empresa_cliente','departamento','usuarios','coordenadas','gps','enlace');

// Comprobar si existe el recurso
if (!in_array($recurso, $recursos_existentes)) {
    throw new ExcepcionApi(ESTADO_EXISTENCIA_RECURSO,
        "No se reconoce el recurso al que intentas acceder");
}

$metodo = strtolower($_SERVER['REQUEST_METHOD']);
// echo $metodo;
// Filtrar metodo
switch ($metodo) {
    case 'get':
        //$vista->imprimir(contactos::get($peticion));
        //break;
    case 'post':
        //$vista->imprimir(usuarios::post($peticion));
        //break;
    case 'put':
        //break;
    case 'delete':
        if (method_exists($recurso, $metodo)) {
            $respuesta = call_user_func(array($recurso, $metodo), $peticion);
            $vista->imprimir($respuesta);
            break;
        }
    default:
        // Metodo no aceptado
        $vista->estado = 405;
        $cuerpo = [
            "estado" => ESTADO_METODO_NO_PERMITIDO,
            "mensaje" => utf8_encode("Metodo no permitido")
        ];
        $vista->imprimir($cuerpo);

}


