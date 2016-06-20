<?php

//{"nombre":"nombresito","telefono":"1234","correo":"email@gmail.com","status":"1"}
class enlace
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "enlace";
    const ENLACE_ID = "enlace_id";
    const USUARIO_ID = "usuario_id";
    const GPS_ID = "gps_id";
    //const GPS_IMEI = "gps_imei";

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
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    public static function delete($peticion)
    {
        if (!empty($peticion[0])) {
            if (self::eliminar($peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El enlace al que intenta acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Crea un nuevo enlace en la base de datos
     */
    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $enlace = json_decode($cuerpo);
        if (!empty($enlace)) {
            $resultado = self::crear($enlace);
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
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Faltan parametros", 422);
        }
    }




    /**
     * Crea un nuevo enlace en la tabla "enlace"
     * @param mixed $datosEnlace columnas del registro
     * @return int codigo para determinar si la insercion fue exitosa
     */
    private function crear($datosEnlace)
    {
        $usuario_id = $datosEnlace->usuario_id;
        $gps_id = $datosEnlace->gps_id;
        $cantidad = self::repetido($gps_id,$usuario_id);

        if($cantidad["cantidad"] < 1) {
            try {
                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::USUARIO_ID . "," .
                    self::GPS_ID . ")" .
                    " VALUES(?,?)";

                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $usuario_id);
                $sentencia->bindParam(2, $gps_id);

                $resultado = $sentencia->execute();

                if ($resultado) {
                    return self::ESTADO_CREACION_EXITOSA;
                } else {
                    return self::ESTADO_CREACION_FALLIDA;
                }
            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        }else{
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Ya existe el registro", 422);
        }

    }

    public function repetido($codigoId, $codigoUsuario){
        $consulta = "SELECT " .
            "COUNT(enlace_id) as cantidad".
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::GPS_ID . "=?".
            " AND usuario_id =?";
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        $sentencia->bindParam(1, $codigoId);
        $sentencia->bindParam(2, $codigoUsuario);
        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }


    private function eliminar($idEnlace)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ENLACE_ID . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idEnlace);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}
