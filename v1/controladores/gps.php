<?php
//require('datos/ConexionBD.php');
//{"nombre":"rest","ap_paterno":"full","ap_materno":"api","telefono":"123","correo":"sas@as.com","usuario":"user","contrase_na":"user","empresa_id":"2"}
class gps
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "gps";
    const GPS_ID = "gps_id";
    const IMEI = "imei";
    const NUMERO = "numero";
    const DESCRIPCION = "descripcion";
    const AUTORASTREO = "autorastreo";
    const ID_DEPARTAMENTO = "departamento_id";

    const TP_INDIVIDUAL = "uno";
    const TP_TODOS = "todos";
    const TP_ENLAZADOS = "enlace";
    const TP_LIBRES = "libres";
    const TP_ENLACES_DISPONIBLES = "enlaces";
    const TP_USUARIOS_ENLAZADOS = "user_enlazados";

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
        } else if ($peticion[0] == 'listarLibres') {
            return self::listarLibres();
        } else if ($peticion[0] == 'listarGpsDeDepartamento') {
            return self::listarGpsDeDepartamento();
        } else if ($peticion[0] == 'listarGpsDeDepartamentoDisponiblesAEnlace') {
            return self::listarGpsDeDepartamentoDisponiblesAEnlace();
        } else if ($peticion[0] == 'listarGpsUsuarioEnlazados') {
            return self::listarGpsUsuarioEnlazados();
        } else if ($peticion[0] == 'sustituirGps') {
            return self::sustituirGps();
        } else if ($peticion[0] == 'listarGpsDeDepartamentoAEnlazarUsuario') {
            return self::listarGpsDeDepartamentoAEnlazarUsuario();
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
        //$peticion[0] : es lo indicado e la direccion :http://localhost/api.rs.com/v1/usuarios/2 = 2
        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $gps = json_decode($body);

            if (self::actualizar($gps, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El gps al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
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
                    "El gps al que intenta acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Crea un nuevo gps en la tabla "gps"
     * @param mixed $datosGps columnas del registro
     * @return int codigo para determinar si la insercion fue exitosa
     */
    private function crear($datosGPS)
    {
        $imei = $datosGPS->imei;
        $numero = $datosGPS->numero;
        $descripcion = $datosGPS->descripcion;
        $departamento_id = $datosGPS->departamento_id;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::IMEI . "," .
                self::NUMERO . "," .
                self::DESCRIPCION . "," .
                self::ID_DEPARTAMENTO . ")" .
                " VALUES(?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $imei);
            $sentencia->bindParam(2, $numero);
            $sentencia->bindParam(3, $descripcion);
            $sentencia->bindParam(4, $departamento_id);

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
            $gps_id = $gps->gps_id;

            $gpsBD = self::obtenerGps(self::TP_INDIVIDUAL, $gps_id);

            if ($gpsBD != NULL) {
                http_response_code(200);
                $respuesta["gps_id"] = $gpsBD["gps_id"];
                $respuesta["imei"] = $gpsBD["imei"];
                $respuesta["numero"] = $gpsBD["numero"];
                $respuesta["descripcion"] = $gpsBD["descripcion"];
                $respuesta["autorastreo"] = $gpsBD["autorastreo"];
                $respuesta["departamento_id"] = $gpsBD["departamento_id"];

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
        $usuarioBD = self::obtenerGps(self::TP_TODOS);

        if ($usuarioBD != NULL) {
            http_response_code(200);

            $arreglo = array();
            while ($row = $usuarioBD->fetch()) {
                array_push($arreglo, array(
                    "gps_id" => $row[0],
                    "imei" => $row[1],
                    "numero" => $row[2],
                    "descripcion" => $row[3],
                    "departamento_id" => $row[4]
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

    private function listarGpsDeDepartamento()
    {
        $cuerpo = file_get_contents('php://input');
        $gps = json_decode($cuerpo);

        if (!empty($gps)) {
            $ID_DEPARTAMENTO_DE_GPS = $gps->departamento_id;

            $gpsBD = self::obtenerGps(self::TP_ENLAZADOS, $ID_DEPARTAMENTO_DE_GPS);
            if ($gpsBD != NULL) {
                http_response_code(200);
                $arreglo = array();
                while ($row = $gpsBD->fetch()) {
                    array_push($arreglo, array(
                        "gps_id" => $row[0],
                        "imei" => $row[1],
                        "numero" => $row[2],
                        "descripcion" => $row[3],
                        "autorastreo" => $row[4],
                        "departamento_id" => $row[5]
                    ));
                }
                return ["estado" => 1, "gps" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el departamento del gps");
        }
    }

    private function listarGpsDeDepartamentoAEnlazarUsuario()
    {
        $cuerpo = file_get_contents('php://input');
        $gps = json_decode($cuerpo);

        if (!empty($gps)) {
            $ID_DEPARTAMENTO_DE_GPS = $gps->departamento_id;

            $USUARIO_ID = $gps->usuario_id;

            $gpsBD_departamento = self::obtenerGps(self::TP_ENLAZADOS, $ID_DEPARTAMENTO_DE_GPS);

            $gpsBD_enlaces = self::obtenerEnlaces($USUARIO_ID);

            if ($gpsBD_departamento != NULL) {
                http_response_code(200);
                $arregloDepartamento = array();
                while ($row = $gpsBD_departamento->fetch()) {
                    array_push($arregloDepartamento, array(
                        "gps_id" => $row[0],
                        "imei" => $row[1],
                        "numero" => $row[2],
                        "descripcion" => $row[3],
                        "autorastreo" => $row[4],
                        "departamento_id" => $row[5]
                    ));
                }
                if ($gpsBD_departamento != NULL) {
                    $arregloEnlace = array();
                    $arregloAretornar = array();
                    while ($row = $gpsBD_enlaces->fetch()) {
                        array_push($arregloEnlace, array(
                            "enlace_id" => $row[0],
                            "usuario_id" => $row[1],
                            "gps_id" => $row[2]
                        ));
                    }
                    $arreglo = $arregloDepartamento;
                    $repetido = 0;
                    $contadordepa = 0;
                    foreach ($arregloDepartamento as $dep) {
                        $contadorenlace = 0;
                        foreach ($arregloEnlace as $enlace) {
                            if ($enlace["gps_id"] == $dep["gps_id"]) {
                                unset($arregloDepartamento[$contadordepa]);
                            }
                            $contadorenlace++;
                        }
                        $contadordepa++;
                    }
                    $arregloDepartamento = array_values($arregloDepartamento);
                    return ["estado" => 1, "gps" => $arregloDepartamento];
                } else {
                    return ["estado" => 1, "gps" => $arregloDepartamento];
                }

            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el departmaneto del gps");
        }
    }

    private function listarGpsDeDepartamentoDisponiblesAEnlace()
    {
        /*
         * SELECT g.imei, g.numero, g.descripcion, e.enlace_id, e.usuario_id,count(g.imei) as cantidadEnlaces FROM dbrs.gps g left JOIN dbrs.enlace e ON ( g.imei = e.gps_imei  ) WHERE g.empresa_id = 1 GROUP BY g.imei having cantidadEnlaces < 6
         * */
        $cuerpo = file_get_contents('php://input');
        $gps = json_decode($cuerpo);

        if (!empty($gps)) {
            $ID_DEPARTAMENTO_DE_GPS = $gps->departamento_id;

            $USUARIO_ID = $gps->usuario_id;

            $gpsBD_departamento = self::obtenerGps(self::TP_ENLACES_DISPONIBLES, $ID_DEPARTAMENTO_DE_GPS);

            $gpsBD_enlaces = self::obtenerEnlaces($USUARIO_ID);


            if ($gpsBD_departamento != NULL) {
                http_response_code(200);
                $arreglo = array();
                $arregloDepartamento = array();
                while ($row = $gpsBD_departamento->fetch()) {
                    array_push($arregloDepartamento, array(
                        "gps_id" => $row[0],
                        "imei" => $row[1],
                        "numero" => $row[2],
                        "descripcion" => $row[3],
                        "autorastreo" => $row[4],
                        "departamento_id" => $row[5],
                        "enlace_id" => $row[6],
                        "usuario_id" => $row[7],
                        "cantidadEnlaces" => $row[8]
                    ));
                }
                if ($gpsBD_departamento != NULL) {
                    $arregloEnlace = array();
                    $arregloAretornar = array();
                    while ($row = $gpsBD_enlaces->fetch()) {
                        array_push($arregloEnlace, array(
                            "enlace_id" => $row[0],
                            "usuario_id" => $row[1],
                            "gps_id" => $row[2]
                        ));
                    }
                    $arreglo = $arregloDepartamento;
                    $repetido = 0;
                    $contadordepa = 0;
                    foreach ($arregloDepartamento as $dep) {
                        $contadorenlace = 0;
                        foreach ($arregloEnlace as $enlace) {
                            if ($enlace["gps_id"] == $dep["gps_id"]) {
                                unset($arregloDepartamento[$contadordepa]);
                            }
                            $contadorenlace++;
                        }
                        $contadordepa++;
                    }
                    $arregloDepartamento = array_values($arregloDepartamento);
                    return ["estado" => 1, "gps" => $arregloDepartamento];
                } else {
                    return ["estado" => 1, "gps" => $arregloDepartamento];
                }

            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el departamento del gps");
        }
    }

    private function listarGpsUsuarioEnlazados()
    {
        /*
         * SELECT g.imei, g.numero, g.descripcion, e.enlace_id, e.usuario_id,count(g.imei) as cantidadEnlaces FROM dbrs.gps g left JOIN dbrs.enlace e ON ( g.imei = e.gps_imei  ) WHERE g.empresa_id = 1 GROUP BY g.imei having cantidadEnlaces < 6
         * */
        $cuerpo = file_get_contents('php://input');
        $gps = json_decode($cuerpo);

        if (!empty($gps)) {
            $GPS_ID = $gps->gps_id;

            $gpsBD = self::obtenerGps(self::TP_USUARIOS_ENLAZADOS, $GPS_ID);
            if ($gpsBD != NULL) {
                http_response_code(200);
                $arreglo = array();
                echo "mirame: " . $gpsBD->columnCount();

                while ($row = $gpsBD->fetch()) {
                    array_push($arreglo, array(
                        "enlace_id" => $row[0],
                        "enlaceUsuario" => $row[1],
                        "usuario_id" => $row[2],
                        "nombre" => $row[3],
                        "ap_paterno" => $row[4],
                        "ap_materno" => $row[5],
                        "telefono" => $row[6],
                        "correo" => $row[7],
                        "usuario" => $row[8],
                        "empresa_id" => $row[9],
                        "cantidadEnlaces" => $row[10]
                    ));
                }
                return ["estado" => 1, "gps" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el departamento del gps");
        }
    }

    private function listarLibres()
    {
        $gpsBD = self::obtenerGps(self::TP_LIBRES);
        if ($gpsBD != NULL) {
            http_response_code(200);
            $arreglo = array();
            while ($row = $gpsBD->fetch()) {
                array_push($arreglo, array(
                    "gps_id" => $row[0],
                    "imei" => $row[1],
                    "numero" => $row[2],
                    "descripcion" => $row[3],
                    "autorastreo" => $row[4],
                    "departamento_id" => $row[5]
                ));
            }
            return ["estado" => 1, "gps" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }

    private function sustituirGps()
    {
        $cuerpo = file_get_contents('php://input');
        $gps = json_decode($cuerpo);

        if (!empty($gps)) {

            //$empresa_id = $gps->empresa_id;

            $gps_id_anterior = $gps->gps_id_anterior;

            $gps_id_nuevo = $gps->gps_id_nuevo;

            /*
            $imei__nuevo = $gps->imei_nuevo;
            $numero__nuevo = $gps->numero_nuevo;
            $descripcion_nuevo = $gps->descripcion_nuevo;*/
            if (self::actualizar($gps, $gps_id_nuevo) > 0) {

            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "No se realizo la actualizacion del gps");
            }


            $cantidadEnlaces = self::verificareEnlaces($gps_id_anterior);
            if ($cantidadEnlaces > 0) {
                echo "con enlaces";

                //paso 4
                if (self::sustituirEnlaces($gps_id_nuevo, $gps_id_anterior) > 0) {

                } else {
                    throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                        "No se intercambiaaron los datos de enlaces");
                }
                // paso 5
                if (self::sustituirDetalles($gps_id_nuevo, $gps_id_anterior) > 0) {

                } else {
                    throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                        "No se intercambiaaron los datos de detalle");
                }
            } else {
                echo "sin enlaces";
            }
            //paso 6
            if (self::eliminarDelDepartamento($gps_id_anterior) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Exito en el cambio"
                ];

            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "No se intercambiaaron los datos de detalle");
            }
        }
    }

    private function verificareEnlaces($dato)
    {
        $consulta = "SELECT " .
            "g." . self::GPS_ID . ", " .
            "g." . self::IMEI . ", " .
            "g." . self::NUMERO . ", " .
            "g." . self::DESCRIPCION . ", " .
            "g." . self::ID_DEPARTAMENTO .
            " FROM " . self::NOMBRE_TABLA . " g" .
            " INNER JOIN enlace e ON ( g.gps_id = e.gps_id  )" .
            " WHERE g." . self::GPS_ID . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        $sentencia->bindParam(1, $dato);

        if ($sentencia->execute())
            return $sentencia->rowCount();
        else
            return null;
    }

    private function sustituirEnlaces($gps_nuevo, $gps_anterior)
    {
        $consulta = "UPDATE enlace " .
            " SET " . self::GPS_ID . "=?" .
            " WHERE " . self::GPS_ID . "=?";

        // Preparar la sentencia
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        $sentencia->bindParam(1, $gps_nuevo);
        $sentencia->bindParam(2, $gps_anterior);
        // Ejecutar la sentencia
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function sustituirDetalles($gps_nuevo, $gps_anterior)
    {
        $consulta = "UPDATE detalle " .
            " SET " . self::GPS_ID . "=?" .
            " WHERE " . self::GPS_ID . "=?";

        // Preparar la sentencia
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        $sentencia->bindParam(1, $gps_nuevo);
        $sentencia->bindParam(2, $gps_anterior);
        // Ejecutar la sentencia
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    private function eliminarDelDepartamento($gps_id_anterior)
    {
        try {
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::ID_DEPARTAMENTO . "=NULL" .
                " WHERE " . self::GPS_ID . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $gps_id_anterior);


            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    function actualizar($gps, $gps_id)
    {
        try {
            if (empty($gps->numero) && empty($gps->descripcion) && empty($gps->departamento_id)) {
//Actualiza solo el IMEI
                $IMEI = $gps->imei;
                $consulta = "UPDATE " . self::NOMBRE_TABLA .
                    " SET " . self::IMEI . "=?" .
                    " WHERE " . self::GPS_ID . "=?";

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $IMEI);
                $sentencia->bindParam(2, $gps_id);

            } else if (empty($gps->imei) && empty($gps->descripcion) && empty($gps->departamento_id)) {
//Actualiza solo el numero
                $numero = $gps->numero;
                $consulta = "UPDATE " . self::NOMBRE_TABLA .
                    " SET " . self::NUMERO . "=?" .
                    " WHERE " . self::GPS_ID . "=?";

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $numero);
                $sentencia->bindParam(2, $gps_id);

            } else if (empty($gps->imei) && empty($gps->numero) && empty($gps->departamento_id)) {
//Actualiza solo la descripcion
                $descripcion = $gps->descripcion;
                $consulta = "UPDATE " . self::NOMBRE_TABLA .
                    " SET " . self::DESCRIPCION . "=?" .
                    " WHERE " . self::GPS_ID . "=?";

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $descripcion);
                $sentencia->bindParam(2, $gps_id);

            } else if (empty($gps->imei) && empty($gps->numero) && empty($gps->descripcion)) {
//Actualiza solo el id del departamento
                $departamento_id = $gps->departamento_id;
                $consulta = "UPDATE " . self::NOMBRE_TABLA .
                    " SET " . self::ID_DEPARTAMENTO . "=?" .
                    " WHERE " . self::GPS_ID . "=?";

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $departamento_id);
                $sentencia->bindParam(2, $gps_id);

            } else {
                //Actualiza todo
                $consulta = "UPDATE " . self::NOMBRE_TABLA .
                    " SET " . self::IMEI . "=?," .
                    self::NUMERO . "=?," .
                    self::DESCRIPCION . "=?," .
                    self::ID_DEPARTAMENTO . "=?" .
                    " WHERE " . self::GPS_ID . "=?";

                // Preparar la sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $IMEI);
                $sentencia->bindParam(2, $numero);
                $sentencia->bindParam(3, $descripcion);
                $sentencia->bindParam(4, $departamento_id);
                $sentencia->bindParam(5, $gps_id);
            }

            // Ejecutar la sentencia
            $sentencia->execute();
            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private
    function eliminar($gps_id)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::GPS_ID . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $gps_id);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function obtenerGps($tipoPeticion, $dato = NULL)
    {
        switch ($tipoPeticion) {
            case self::TP_INDIVIDUAL:
                $consulta = "SELECT " .
                    self::GPS_ID . "," .
                    self::IMEI . "," .
                    self::NUMERO . "," .
                    self::DESCRIPCION . "," .
                    self::AUTORASTREO . "," .
                    self::ID_DEPARTAMENTO .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::GPS_ID . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia->fetch(PDO::FETCH_ASSOC);
                else
                    return null;
                break;
            case self::TP_LIBRES:
                $consulta = "SELECT " .
                    self::GPS_ID . ", " .
                    self::IMEI . ", " .
                    self::NUMERO . ", " .
                    self::DESCRIPCION . ", " .
                    self::AUTORASTREO . ", " .
                    self::ID_DEPARTAMENTO .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_DEPARTAMENTO . " is null or ''";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute()) {
                    return $sentencia;
                } else
                    return null;
                break;
            case self::TP_TODOS:
                $consulta = "SELECT " .
                    self::GPS_ID . ", " .
                    self::IMEI . "," .
                    self::NUMERO . "," .
                    self::DESCRIPCION . "," .
                    self::AUTORASTREO . ", " .
                    self::ID_DEPARTAMENTO .
                    " FROM " . self::NOMBRE_TABLA;
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
                break;
            case self::TP_ENLAZADOS:
                $consulta = "SELECT " .
                    self::GPS_ID . ", " .
                    self::IMEI . "," .
                    self::NUMERO . "," .
                    self::DESCRIPCION . "," .
                    self::AUTORASTREO . ", " .
                    self::ID_DEPARTAMENTO .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_DEPARTAMENTO . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
                break;
            case self::TP_ENLACES_DISPONIBLES:
                $consulta =
                    "SELECT " .
                    "g." . self::GPS_ID . ", " .
                    "g." . self::IMEI . "," .
                    "g." . self::NUMERO . "," .
                    "g." . self::DESCRIPCION . "," .
                    "g." . self::AUTORASTREO . ", " .
                    "g." . self::ID_DEPARTAMENTO . ", " .
                    "e.enlace_id," .
                    "e.usuario_id," .
                    "count(g.gps_id) as cantidadEnlaces" .
                    " FROM " . self::NOMBRE_TABLA . " g" .
                    " LEFT JOIN enlace e ON (g.gps_id = e.gps_id)" .
                    " WHERE g." . self::ID_DEPARTAMENTO . "=?" .
                    " GROUP BY g.gps_id" .
                    " HAVING cantidadEnlaces < 6 ";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
                break;
            case self::TP_USUARIOS_ENLAZADOS:
                $consulta =
                    "SELECT " .
                    "e.enlace_id," .
                    "e.usuario_id as enlaceUsuario," .
                    "u.usuario_id," .
                    "u.nombre," .
                    "u.ap_paterno," .
                    "u.ap_materno," .
                    "u.telefono," .
                    "u.correo," .
                    "u.usuario," .
                    "u.empresa_id," .
                    "count(g.gps_id) as cantidadEnlaces" .
                    " FROM " . self::NOMBRE_TABLA . " g" .
                    " LEFT JOIN enlace e ON (g.gps_id = e.gps_id)" .
                    " LEFT JOIN usuarios u ON ( e.usuario_id = u.usuario_id)" .
                    " WHERE g." . self::GPS_ID . "=?" .
                    " GROUP BY e.usuario_id";

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
                break;
        }
    }

    private function obtenerEnlaces($idUsuario)
    {
        $consulta =
            "SELECT " .
            "enlace_id, " .
            "usuario_id, " .
            "gps_id" .
            " FROM " . "enlace" .
            " WHERE " . "usuario_id" . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        $sentencia->bindParam(1, $idUsuario);
        if ($sentencia->execute())
            return $sentencia;
        else
            return null;
    }
}
