<?php
//require('datos/ConexionBD.php');
//{"nombre":"rest","ap_paterno":"full","ap_materno":"api","telefono":"123","correo":"sas@as.com","usuario":"user","contrase_na":"user","empresa_id":"2"}
class gps
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "gps";
    const IMEI = "imei";
    const ID_ENLACE = "enlace_id";
    const NUMERO = "numero";

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
        }else if ($peticion[0] == 'listarLibres') {
            return self::listarLibres();
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
        $gps = json_decode($cuerpo);

        $resultado = self::crear($gps);

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
            $gps = json_decode($body);

            //if (self::actualizar($idEmpresa, $empresa, $peticion[0]) > 0) {
            if (self::actualizar($gps, $peticion[0]) > 0) {
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
    private function crear($datosGPS)
    {
        $imei = $datosGPS->imei;
        $enlace_id = $datosGPS->enlace_id;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::IMEI . "," .
                self::ID_ENLACE . ")" .
                " VALUES(?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $imei);
            $sentencia->bindParam(2, $enlace_id);

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
        $gps = json_decode($body);

        if (isset($gps)) {
            $imei = $gps->imei;
            //echo " nombre: ".$usuario->nombre;
            //echo " nombre_id: ".$id;

            // if (self::autenticar($correo, $contrasena)) {
            $gpsBD = self::obtenerUsuario($imei, NULL);

            if ($gpsBD != NULL) {
                http_response_code(200);
                $respuesta["imei"] = $gpsBD["imei"];
                $respuesta["enlace_id"] = $gpsBD["enlace_id"];
                $respuesta["numero"] = $gpsBD["numero"];

                return ["estado" => 1, "gps" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                "Especifique el indice ");
        }
    }


    private function listarVarios()
    {
        $usuarioBD = self::obtenerUsuario(NULL, NULL);

        if ($usuarioBD != NULL) {
            http_response_code(200);

            $arreglo = array();
            while ($row = $usuarioBD->fetch()) {
                array_push($arreglo, array(
                    "imei" => $row[0],
                    "enlace_id" => $row[1],
                    "numero" => $row[2]
                ));
            }
//            foreach ($arreglo as $keys) {
//                foreach ($keys as $key => $value) {
//                    echo "key: " . $key .  " valor: " . $value . " ----\n";
//                }
//            }
            return ["estado" => 1, "gps" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }


    private function listarLibres()
    {
        $gpsBD = self::obtenerUsuario(NULL, TRUE);
        if ($gpsBD != NULL) {
            http_response_code(200);
            $arreglo = array();
            while ($row = $gpsBD->fetch()) {
                array_push($arreglo, array(
                    "imei" => $row[0],
                    "enlace_id" => $row[1],
                    "numero" => $row[2]
                ));
            }
            return ["estado" => 1, "gps" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }


//private function actualizar($idEmpresa, $empresa, $idContacto)
    private
    function actualizar($gps, $IMEI)
    {
        try {
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::ID_ENLACE . "=?" .
                " WHERE " . self::IMEI . "=?";


            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $enlace_id);
            $sentencia->bindParam(2, $IMEI);

            $enlace_id = $gps->enlace_id;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    private
    function eliminar($IMEI)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::IMEI . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $IMEI);

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
    private
    function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private
    function generarClaveApi()
    {
        return md5(microtime() . rand());
    }

    private
    function obtenerEmpresaPorCorreo($correo)
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


    private
    function obtenerUsuario($id = NULL, $enlaces = NULL)
    {
        if ($enlaces) {
            $vacio = " ";
            $consulta = "SELECT " .
                self::IMEI . ", " .
                self::ID_ENLACE . ", " .
                self::NUMERO .
                " FROM " . self::NOMBRE_TABLA .
                //" WHERE " . self::ID_ENLACE . "=?";
                " WHERE " . self::ID_ENLACE . " is null";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
            //$sentencia->bindParam(1, $vacio);
            if ($sentencia->execute()){
                //return $sentencia->fetch(PDO::FETCH_ASSOC);
                return $sentencia;
            } else
                return null;
        } else {
            if ($id == NULL) {
                $consulta = "SELECT " .
                    self::IMEI . "," .
                    self::ID_ENLACE . "," .
                    self::NUMERO .
                    " FROM " . self::NOMBRE_TABLA;
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute())
                    //return $sentencia->fetch(PDO::FETCH_ASSOC);
                    return $sentencia;
                else
                    return null;
            } else {
                $consulta = "SELECT " .
                    self::IMEI . "," .
                    self::ID_ENLACE . "," .
                    self::NUMERO .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::IMEI . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $id);

                if ($sentencia->execute())
                    return $sentencia->fetch(PDO::FETCH_ASSOC);
                else
                    return null;
            }
        }
    }
}

