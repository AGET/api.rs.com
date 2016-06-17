<?php
//require('datos/ConexionBD.php');
//{"nombre":"rest","ap_paterno":"full","ap_materno":"api","telefono":"123","correo":"sas@as.com","usuario":"user","contrase_na":"user","empresa_id":"2"}
class usuarios
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "usuarios";
    const ID_USUARIO = "usuario_id";
    const NOMBRE = "nombre";
    const APPATERNO = "ap_paterno";
    const APMATERNO = "ap_materno";
    const TELEFONO = "telefono";
    const CORREO = "correo";
    const USUARIO = "usuario";
    const CONTRASE_NA = "contrase_na";
    const ID_EMPRESA = "empresa_id";
    const CLAVE_API = "clave_api";

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;


    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;


    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            //return self::loguear();
        } else if ($peticion[0] == 'listarUno_Id') {
            return self::listarUnoId();
        } else if ($peticion[0] == 'listarVarios') {
            return self::listarVarios();
        } else if ($peticion[0] == 'listarUsuariosDeEmpresa') {
            return self::listarUsuariosDeEmpresa();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    /**
     * Crea un nuevo empresa en la base de datos
     */
    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $resultado = self::crear($usuario);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("Registro con exito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    public static function put($peticion)
    {
        //$idEmpresa = empresa_cliente::autorizar();

        //$peticion[0] : es lo indicado e la direccion :http://localhost/api.rs.com/v1/usuarios/2 = 2
        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $usuario = json_decode($body);

            //if (self::actualizar($idEmpresa, $empresa, $peticion[0]) > 0) {
            if (self::actualizar($usuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El usuario al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    public static function delete($peticion)
    {
        //$idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            if (self::eliminar($peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "La empresa a la que intenta acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Crea una nueva empresa en la tabla "empresa"
     * @param mixed $datosUsuario columnas del registro
     * @return int codigo para determinar si la insercion fue exitosa
     */
    private function crear($datosUsuario)
    {
        $nombre = $datosUsuario->nombre;
        $ap_paterno = $datosUsuario->ap_paterno;
        $ap_materno = $datosUsuario->ap_materno;
        $telefono = $datosUsuario->telefono;
        $correo = $datosUsuario->correo;
        $usuario = $datosUsuario->usuario;
        $empresa_id = $datosUsuario->empresa_id;

        $contrase_na = $datosUsuario->contrase_na;
        $contrasenaEncriptada = self::encriptarContrasena($contrase_na);

        $clave_api = self::generarClaveApi();

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::APPATERNO . "," .
                self::APMATERNO . "," .
                self::TELEFONO . "," .
                self::CORREO . "," .
                self::USUARIO . "," .
                self::CONTRASE_NA . "," .
                self::ID_EMPRESA . "," .
                self::CLAVE_API . ")" .
                " VALUES(?,?,?,?,?,?,?,?,?)";


            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $ap_paterno);
            $sentencia->bindParam(3, $ap_materno);
            $sentencia->bindParam(4, $telefono);
            $sentencia->bindParam(5, $correo);
            $sentencia->bindParam(6, $usuario);
            $sentencia->bindParam(7, $contrasenaEncriptada);
            $sentencia->bindParam(8, $empresa_id);
            $sentencia->bindParam(9, $clave_api);

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    }


    private function listarUnoId()
    {
        $body = file_get_contents('php://input');
        $usuario = json_decode($body);

        if (isset($empresa_cliente)) {
            $id = $usuario->usuario_id;
            //echo " nombre: ".$usuario->nombre;
            //echo " nombre_id: ".$id;

            // if (self::autenticar($correo, $contrasena)) {
            $usuarioBD = self::obtenerUsuario($id);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["usuario_id"] = $usuarioBD["usuario_id"];
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["ap_paterno"] = $usuarioBD["ap_paterno"];
                $respuesta["ap_materno"] = $usuarioBD["ap_materno"];
                $respuesta["telefono"] = $usuarioBD["telefono"];
                $respuesta["correo"] = $usuarioBD["correo"];
                $respuesta["usuario"] = $usuarioBD["usuario"];
                $respuesta["contrase_na"] = $usuarioBD["contrase_na"];
                $respuesta["empresa_id"] = $usuarioBD["empresa_id"];

                return ["estado" => 1, "usuario" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                "Especifique el indice ");
        }


//        } else {
//            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
//                utf8_encode("Correo o contrase_na invalidos"));
//        }


        /*
        return
            [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Registro con exitookokok!")
            ];
        */
    }

    private function listarVarios()
    {
//        $body = file_get_contents('php://input');
//        $usuario = json_decode($body);

        // if (self::autenticar($correo, $contrasena)) {
        $usuarioBD = self::obtenerUsuario(NULL);

        if ($usuarioBD != NULL) {
            http_response_code(200);
//            $respuesta["usuario_id"] = $usuarioBD["usuario_id"];
//            $respuesta["nombre"] = $usuarioBD["nombre"];
//            $respuesta["ap_paterno"] = $usuarioBD["ap_paterno"];
//            $respuesta["ap_materno"] = $usuarioBD["ap_materno"];
//            $respuesta["telefono"] = $usuarioBD["telefono"];
//            $respuesta["correo"] = $usuarioBD["correo"];
//            $respuesta["usuario"] = $usuarioBD["usuario"];
//            $respuesta["contrase_na"] = $usuarioBD["contrase_na"];
//            $respuesta["empresa_id"] = $usuarioBD["empresa_id"];

            $arreglo = array();
            while ($row = $usuarioBD->fetch()) {
                array_push($arreglo, array(
                    "usuario_id" => $row[0],
                    "nombre" => $row[1],
                    "ap_paterno" => $row[2],
                    "ap_materno" => $row[3],
                    "telefono" => $row[4],
                    "correo" => $row[5],
                    "usuario" => $row[6],
                    "contrase_na" => $row[7],
                    "empresa_id" => $row[8]
                ));
            }
//            foreach ($arreglo as $keys) {
//                foreach ($keys as $key => $value) {
//                    echo "key: " . $key .  " valor: " . $value . " ----\n";
//                }
//            }
            return ["estado" => 1, "usuario" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
//        } else {
//            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
//                utf8_encode("Correo o contrase_na invalidos"));
//        }


        /*
        return
            [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Registro con exitookokok!")
            ];
        */
    }

    private function listarUsuariosDeEmpresa(){
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        if(!empty($usuario)) {
            $ID_EMPRESA_DE_USUARIOS = $usuario->empresa_id;
            echo "nUESTRO ID ES: " . $ID_EMPRESA_DE_USUARIOS . "--";
            $usuarioBD = self::obtenerUsuario(NULL, $ID_EMPRESA_DE_USUARIOS);
            if ($usuarioBD != NULL) {
                http_response_code(200);
                $arreglo = array();
                while ($row = $usuarioBD->fetch()) {
                    array_push($arreglo, array(
                        "usuario_id" => $row[0],
                        "nombre" => $row[1],
                        "ap_paterno" => $row[2],
                        "ap_materno" => $row[3],
                        "telefono" => $row[4],
                        "correo" => $row[5],
                        "usuario" => $row[6],
                        "contrase_na" => $row[7],
                        "empresa_id" => $row[8]
                    ));
                }
                return ["estado" => 1, "usuario" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        }else{
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce la empresa");
        }
    }


    //private function actualizar($idEmpresa, $empresa, $idContacto)
    private function actualizar($usuario, $idUsuario)
    {
        try {
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::NOMBRE . "=?," .
                self::APPATERNO . "=?," .
                self::APMATERNO . "=?," .
                self::TELEFONO . "=?," .
                self::CORREO . "=?," .
                self::USUARIO . "=?," .
                self::CONTRASE_NA . "=?" .
                " WHERE " . self::ID_USUARIO . "=?";


            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $ap_paterno);
            $sentencia->bindParam(3, $ap_materno);
            $sentencia->bindParam(4, $telefono);
            $sentencia->bindParam(5, $correo);
            $sentencia->bindParam(6, $usuariouser);
            $sentencia->bindParam(7, $contrasenaEncriptada);
            $sentencia->bindParam(8, $idUsuario);

            $contrase_na = $usuario->contrase_na;
            $contrasenaEncriptada = self::encriptarContrasena($contrase_na);


            $nombre = $usuario->nombre;
            $ap_paterno = $usuario->ap_paterno;
            $ap_materno = $usuario->ap_materno;
            $telefono = $usuario->telefono;
            $correo = $usuario->correo;
            $usuariouser = $usuario->usuario;


            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    private function eliminar($idUsuario)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Protege la contrase_na con un algoritmo de encriptado
     * @param $contrasenaPlana
     * @return bool|null|string
     */
    private function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private function generarClaveApi()
    {
        return md5(microtime() . rand());
    }

    /*
     *Loguear
    private function loguear()
    {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $empresa = json_decode($body);

        $correo = $empresa->correo;
        $contrasena = $empresa->contrasena;


        if (self::autenticar($correo, $contrasena)) {
            $usuarioBD = self::obtenerEmpresaPorCorreo($correo);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["correo"] = $usuarioBD["correo"];
                $respuesta["claveApi"] = $usuarioBD["claveApi"];
                return ["estado" => 1, "usuario" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Correo o contrase_na invalidos"));
        }
    }
    */


    /*
    private function autenticar($correo, $contrasena)
    {
        $comando = "SELECT contrasena FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";

        try {

            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $correo);

            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();

                if (self::validarContrasena($contrasena, $resultado['contrasena'])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
    */

    /*
    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
        return password_verify($contrasenaPlana, $contrasenaHash);
    }
    */


    private function obtenerEmpresaPorCorreo($correo)
    {
        $comando = "SELECT " .
            self::NOMBRE . "," .
            self::CONTRASENA . "," .
            self::CORREO . "," .
            self::CLAVE_API .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $correo);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }


    private function obtenerUsuario($id = NULL, $id_empresa = NULL)
    {
        if ($id_empresa == NULL) {
            if ($id == NULL) {
                $consulta = "SELECT " .
                    self::ID_USUARIO . "," .
                    self::NOMBRE . "," .
                    self::APPATERNO . "," .
                    self::APMATERNO . ", " .
                    self::TELEFONO . ", " .
                    self::CORREO . ", " .
                    self::USUARIO . ", " .
                    self::CONTRASE_NA . ", " .
                    self::ID_EMPRESA .
                    " FROM " . self::NOMBRE_TABLA;
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute())
                    //return $sentencia->fetch(PDO::FETCH_ASSOC);
                    return $sentencia;
                else
                    return null;
            } else {
                $consulta = "SELECT " .
                    self::ID_USUARIO . "," .
                    self::NOMBRE . "," .
                    self::APPATERNO . "," .
                    self::APMATERNO . ", " .
                    self::TELEFONO . ", " .
                    self::CORREO . ", " .
                    self::USUARIO . ", " .
                    self::CONTRASE_NA . ", " .
                    self::ID_EMPRESA .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $id);

                if ($sentencia->execute())
                    return $sentencia->fetch(PDO::FETCH_ASSOC);
                else
                    return null;
            }
        } else {
            $consulta = "SELECT " .
                self::ID_USUARIO . "," .
                self::NOMBRE . "," .
                self::APPATERNO . "," .
                self::APMATERNO . ", " .
                self::TELEFONO . ", " .
                self::CORREO . ", " .
                self::USUARIO . ", " .
                self::CONTRASE_NA . ", " .
                self::ID_EMPRESA .
                " FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_EMPRESA . "=?";
            /*SELECT g.descripcion, ec.nombre
                FROM dbrs.gps g
	        INNER JOIN dbrs.empresa_cliente ec ON ( g.empresa_id = ec.empresa_id  )
            WHERE ec.empresa_id = X*/
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
echo "Consulta: ".$consulta;
            $sentencia->bindParam(1, $id_empresa);

            if ($sentencia->execute())
                return $sentencia;
            else
                return null;
        }
    }



    /**
     * Otorga los permisos a un usuario para que acceda a los recursos
     * @return null o el id del usuario autorizado
     * @throws Exception
     */
    /*
    public static function autorizar()
    {
        $cabeceras = apache_request_headers();
//        $cabeceras =getallheaders();
//        echo count(apache_request_headers());
//        echo count(getallheaders());
//
//        foreach ($cabeceras as $key => $value) {
//            echo ' '. $key . ' = ' . $value . ' ';
//        }

        //if (isset($cabeceras["Authorization"])) {
        if (isset($cabeceras["authorization"])) {

            //$claveApi = $cabeceras["Authorization"];
            $claveApi = $cabeceras["authorization"];

            if (usuarios::validarClaveApi($claveApi)) {
                return usuarios::obtenerIdEmpresa($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticacion noo"));
        }
    }
*/

    /**
     * Comprueba la existencia de la clave para la api
     * @param $claveApi
     * @return bool true si existe o false en caso contrario
     */

    /*
    private function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID_EMPRESA . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }
*/

    /**
     * Obtiene el valor de la columna "idUsuario" basado en la clave de api
     * @param $claveApi
     * @return null si este no fue encontrado
     */

    /*
    private function obtenerIdEmpresa($claveApi)
    {
        $comando = "SELECT " . self::ID_EMPRESA .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['empresa_id'];
        } else
            return null;
    }
    */
}

