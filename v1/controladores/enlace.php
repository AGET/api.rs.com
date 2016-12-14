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


    const TP_OBTENER_TELEFONOS_ENLAZADOS = "ObtenerLosTelefonosUsuarioGpsEnlazados";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;


    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        }
        if ($peticion[0] == 'listarTelefonos') {
            return self::listarTelefonos();
        }
        if ($peticion[0] == 'listarEnlaces') {
            return self::listarEnlaces();
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
        $cantidad = self::repetido($gps_id, $usuario_id);

        if ($cantidad["cantidad"] < 1) {
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
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Ya existe el registro", 422);
        }

    }

    public function repetido($codigoId, $codigoUsuario)
    {
        $consulta = "SELECT " .
            "COUNT(enlace_id) as cantidad" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::GPS_ID . "=?" .
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


    private function listarTelefonos()
    {
        $cuerpo = file_get_contents('php://input');
        $enlace = json_decode($cuerpo);

        if (!empty($enlace)) {
            $ID_DEPARTAMENTO = $enlace->departamento_id;

            $enlaceBD = self::obtenerTelefonosEnlazados(self::TP_OBTENER_TELEFONOS_ENLAZADOS, $ID_DEPARTAMENTO);
            if ($enlaceBD != NULL) {
                http_response_code(200);
                $arreglo = array();
                while ($row = $enlaceBD->fetch()) {
                    array_push($arreglo, array(
                        "telefono" => $row[0],
                        "numero" => $row[1]
                    ));
                }
                return ["estado" => 1, "enlace" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce la empresa del gps");
        }
    }


    private function obtenerTelefonosEnlazados($tipoPeticion, $dato)
    {
        switch ($tipoPeticion) {
            case self::TP_OBTENER_TELEFONOS_ENLAZADOS:
                $consulta =
                    "SELECT " .
                    "u.telefono," .
                    "g.numero" .
                    " FROM " . self::NOMBRE_TABLA . " e" .
                    " INNER JOIN gps g ON (e.gps_id = g.gps_id)" .
                    " INNER JOIN usuarios u ON ( e.usuario_id = u.usuario_id)" .
                    " WHERE u.departamento_id=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
                break;
        }
    }


    private function listarEnlaces()
    {
        $cuerpo = file_get_contents('php://input');
        $enlace = json_decode($cuerpo);
        if (!empty($enlace)) {
            if (isset($enlace->gps_id)) {
                $ID_GPS = $enlace->gps_id;

                $enlaceBD = self::listar($ID_GPS);

                if ($enlaceBD != NULL) {
                    http_response_code(200);
                    $arreglo = array();
                    while ($row = $enlaceBD->fetch()) {
                        array_push($arreglo, array(
                            self::ENLACE_ID => $row[0],
                            self::USUARIO_ID => $row[1],
                            self::GPS_ID => $row[2],
                            "nombre" => $row[3],
                            "ap_paterno" => $row[4],
                            "ap_materno" => $row[5]
                        ));
                    }
                    return ["estado" => 1, "enlace" => $arreglo];
                } else {
                    throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Ha ocurrido un error probablemente no se encontro el dato");
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "falta gps_id", 422);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Faltan parametros", 422);
        }
    }


    /**
     * Crea un nuevo enlace en la tabla "enlace"
     * @param mixed $datosEnlace columnas de la busqueda
     * @return int codigo para determinar si la insercion fue exitosa
     */

    private function listar($gps_id)
    {
        try {
            // Sentencia SQL
            $comando = "SELECT e." . self::ENLACE_ID . ", e." . self::USUARIO_ID . ", e." . self::GPS_ID . "u.nombre,u.ap_paterno,u.ap_materno" .
                " FROM " . self::NOMBRE_TABLA . " e" .
                " INNER JOIN usuarios u ON ( e." . self::USUARIO_ID . " = u." . self::USUARIO_ID . " ) " .
                " WHERE e." . self::GPS_ID . " = ?";

            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            $sentencia->bindParam(1, $gps_id);

            if ($sentencia->execute())
                return $sentencia;
            else
                return null;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}
