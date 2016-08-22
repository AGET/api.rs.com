<?php
//require('datos/ConexionBD.php');
//{"nombre":"rest","ap_paterno":"full","ap_materno":"api","telefono":"123","correo":"sas@as.com","usuario":"user","contrase_na":"user","empresa_id":"2"}
class coordenadas
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "coordenadas";
    const ID_COORDENADAS = "coordenadas_id";
    const LONGITUD = "longitud";
    const LATITUD = "latitud";
    const ID_DETALLE= "detalle_id";

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
        }
        else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    /**
     * Crea un nuevo empresa en la base de datos
     */
    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $coordenada = json_decode($cuerpo);

        $resultado = self::crear($coordenada);

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
            if (self::actualizar( $usuario, $peticion[0]) > 0) {
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
    private function crear($datosCoordenada)
    {
        //$coordenadas_id = $datosCoordenada->coordenadas_id;
        $ID_ENLACE = $datosCoordenada->enlace_id;
        if($ID_ENLACE == NULL)
            return self::ESTADO_CREACION_FALLIDA;

        $resultadoInsercionFecha = self::insertarDetalle($ID_ENLACE);

        switch ($resultadoInsercionFecha) {
            case self::ESTADO_CREACION_EXITOSA:
                $longitud = $datosCoordenada->longitud;
                $latitud = $datosCoordenada->latitud;
                try {
                    $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                    // Sentencia INSERT
                    $comando = "INSERT INTO coordenadas ( " .
                    "longitud, " .
                    "latitud, " .
                    "detalle_id )" .
                    " VALUES(?,?,(SELECT MAX(detalle_id) FROM detalle))";

                    // INSERT INTO `coordenadas`( `longitud`, `latitud`, `detalle_id`)
                    //  VALUES (12123.23,123213.21,(SELECT MAX(detalle_id) FROM detalle))

                    $sentencia = $pdo->prepare($comando);

                    $sentencia->bindParam(1, $longitud );
                    $sentencia->bindParam(2, $latitud );

                    $resultado = $sentencia->execute();

                    if ($resultado) {
                        return self::ESTADO_CREACION_EXITOSA;
                    } else {
                        return self::ESTADO_CREACION_FALLIDA;
                    }
                } catch (PDOException $e) {
                    throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
                }
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

        private function insertarDetalle($id_enlace){
            date_default_timezone_set('America/Mexico_City');
            //$FECHA = "'".date('d-m-Y h:i:s', time())."'";
            $FECHA = date('Y-m-d h:i:s', time());
            try {
                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO  detalle ( " .
                "fecha, ". 
                "enlace_id)" .
                " VALUES(?,?)";             
                $sentencia = $pdo->prepare($comando);               
                $sentencia->bindParam(1, $FECHA );
                $sentencia->bindParam(2, $id_enlace );
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
        $coordenada = json_decode($body);

        if(isset($coordenada)){
            $id = $coordenada->coordenadas_id;
            //echo " nombre: ".$usuario->nombre;
            //echo " nombre_id: ".$id;

            // if (self::autenticar($correo, $contrasena)) {
            $coordenadaBD = self::obtenerCoordenada($id);
            

            if ($coordenadaBD != NULL) {
                http_response_code(200);
                $respuesta["coordenadas_id"] = $coordenadaBD["coordenadas_id"];
                $respuesta["longitud"] = $coordenadaBD["longitud"];
                $respuesta["latitud"] = $coordenadaBD["latitud"];
                $respuesta["detalle_id"] = $coordenadaBD["detalle_id"];

                return ["estado" => 1, "coordenadas" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        }else{
            throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                "Especifique el indice ");
        }
    }

    private function listarVarios()
    {
//        $body = file_get_contents('php://input');
//        $usuario = json_decode($body);

        // if (self::autenticar($correo, $contrasena)) {
        $usuarioBD = self::obtenerCoordenada(NULL);

        if ($usuarioBD != NULL) {
            http_response_code(200);

            $arreglo =array();
            while($row = $usuarioBD->fetch()) {
                array_push($arreglo, array(
                    "coordenadas_id" => $row[0],
                    "longitud" => $row[1],
                    "latitud" => $row[2],
                    "detalle_id" => $row[3]
                ));
            }
//            foreach ($arreglo as $keys) {
//                foreach ($keys as $key => $value) {
//                    echo "key: " . $key .  " valor: " . $value . " ----\n";
//                }
//            }
            return ["estado" => 1, "coordenadas" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }


    //private function actualizar($idEmpresa, $empresa, $idContacto)
    private function actualizar( $coordenada, $idCoordenada )
    {
        try {
              $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET "  . self::LONGITUD . "=?," .
                  self::LATITUD . "=?," .
                  self::ID_DETALLE . "=?" .
                " WHERE " . self::ID_COORDENADAS . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $longitud );
            $sentencia->bindParam(2, $latitud );
            $sentencia->bindParam(3, $detalle_id );
            $sentencia->bindParam(4, $idCoordenada );

            $longitud= $coordenada->longitud;
            $latitud= $coordenada->latitud;
            $detalle_id = $coordenada->detalle_id;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    private function eliminar($idCoordenada)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_COORDENADAS . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idCoordenada);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


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


    private function obtenerCoordenada($id = NULL)
    {
        if ($id == NULL) {
            $consulta = "SELECT " .
                self::ID_COORDENADAS. "," .
                self::LONGITUD. "," .
                self::LATITUD. "," .
                self::ID_DETALLE.
                " FROM " . self::NOMBRE_TABLA;
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
            if ($sentencia->execute())
                //return $sentencia->fetch(PDO::FETCH_ASSOC);
                return $sentencia;
            else
                return null;
        } else {
            $consulta = "SELECT " .
                self::ID_COORDENADAS. "," .
                self::LONGITUD. "," .
                self::LATITUD. "," .
                self::ID_DETALLE.
                " FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_COORDENADAS . "=?";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $id);

            if ($sentencia->execute())
                return $sentencia->fetch(PDO::FETCH_ASSOC);
            else
                return null;
        }
    }
}

