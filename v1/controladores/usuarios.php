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
    //const USUARIO = "usuario";
    const CONTRASE_NA = "contrase_na";
    const ID_DEPARTAMENTO = "departamento_id";
    const CLAVE_API = "clave_api";

    const NOMBRE_TABLA_EMPRESA = "empresa_cliente";
    const EMPRESA_ID = "empresa_id";
    const NOMBRE_EMPRESA = "nombre";
    const STATUS_EMPRESA = "status";
    
    const NOMBRE_TABLA_DEPARTAMENTO = "departamento";
    const DEPARTAMENTO_ID = "departamento_id";
    const NOMBRE_DEPARTAMENTO = "nombre";
    

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    const INDIVIDUAL = "uno";
    const MULTIPLES = "varios";
    const USER_EN_DEPARTAMENTO = "de_departamento";
    const GPS_DE_USER = "gps_de_usuarios";
    const COORDENADAS_GPS_DE_USER_FECHA = "coordenadas_gps_de_usuarios_por_fecha";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;

    const ESTADO_LOGIN_CORRECTO = "CORRECTO";
    const ESTADO_LOGIN_ERROR_CLAVE= "CLAVE_INCORRECTA";   
    const ESTADO_LOGIN_ERROR_USUARIO = "USUARIO_INCORRECTO";       


    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::login();
        } else if ($peticion[0] == 'listarUno_Id') {
            return self::listarUnoId();
        } else if ($peticion[0] == 'listarVarios') {
            return self::listarVarios();
        } else if ($peticion[0] == 'listarUsuariosDeDepartamento') {
            return self::listarUsuariosDeDepartamento();
        }  else if ($peticion[0] == 'listarGpsDeUsuario') {
            return self::listarGpsDeUsuario();
        }   else if ($peticion[0] == 'listarCoordenadasDeTodosSusGpsPorFecha') {
            return self::listarCoordenadasDeTodosSusGpsPorFecha();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    /**
     * Crea un nuevo departamento en la base de datos
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
        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $usuario = json_decode($body);
            
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
                    "El usuario que intenta acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Crea un nuevo usuario en la tabla "usuarios"
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
        //$usuario = $datosUsuario->usuario;
        $departamento_id = $datosUsuario->departamento_id;

        $contrase_na = $datosUsuario->contrase_na;
        //$contrasenaEncriptada = self::encriptarContrasena($contrase_na);
        //$contrasenaEncriptada = self::encriptar($contrase_na);


        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::APPATERNO . "," .
                self::APMATERNO . "," .
                self::TELEFONO . "," .
                self::CORREO . "," .
              //  self::USUARIO . "," .
                self::CONTRASE_NA . "," .
                self::ID_DEPARTAMENTO . ")" .
                " VALUES(?,?,?,?,?,?,?,?)";


            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $ap_paterno);
            $sentencia->bindParam(3, $ap_materno);
            $sentencia->bindParam(4, $telefono);
            $sentencia->bindParam(5, $correo);
            //$sentencia->bindParam(6, $usuario);
            $sentencia->bindParam(7, $contrase_na);
            $sentencia->bindParam(8, $departamento_id);

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

        if (isset($usuario)) {
            $id = $usuario->usuario_id;
            //echo " nombre: ".$usuario->nombre;
            //echo " nombre_id: ".$id;

            // if (self::autenticar($correo, $contrasena)) {
            $usuarioBD = self::obtenerUsuario(self::INDIVIDUAL, $id);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["usuario_id"] = $usuarioBD["usuario_id"];
                $respuesta["nombre"] = $usuarioBD["nombre"];
                $respuesta["ap_paterno"] = $usuarioBD["ap_paterno"];
                $respuesta["ap_materno"] = $usuarioBD["ap_materno"];
                $respuesta["telefono"] = $usuarioBD["telefono"];
                $respuesta["correo"] = $usuarioBD["correo"];
               // $respuesta["usuario"] = $usuarioBD["usuario"];
                $respuesta["contrase_na"] = $usuarioBD["contrase_na"];
                $respuesta["departamento_id"] = $usuarioBD["departamento_id"];

                return ["estado" => 1, "usuarios" => $respuesta];
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
//        $body = file_get_contents('php://input');
//        $usuario = json_decode($body);

        $usuarioBD = self::obtenerUsuario(self::MULTIPLES, NULL);

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
                   // "usuario" => $row[6],
                    "contrase_na" => $row[6],
                    "departamento_id" => $row[7]
                ));
            }
//            foreach ($arreglo as $keys) {
//                foreach ($keys as $key => $value) {
//                    echo "key: " . $key .  " valor: " . $value . " ----\n";
//                }
//            }
            return ["estado" => 1, "usuarios" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }

    private function listarUsuariosDeDepartamento()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        if (!empty($usuario)) {
            $ID_DEPARTAMENTO_DE_USUARIOS = $usuario->departamento_id;
            $usuarioBD = self::obtenerUsuario(self::USER_EN_DEPARTAMENTO, $ID_DEPARTAMENTO_DE_USUARIOS);
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
                       // "usuario" => $row[6],
                        "contrase_na" => $row[6],
                        "departamento_id" => $row[7]
                    ));
                }
                return ["estado" => 1, "usuarios" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el usuario");
        }
    }

    private function listarGpsDeUsuario()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        if (!empty($usuario)) {
            $ID_USUARIO = $usuario->usuario_id;
            $usuarioBD = self::obtenerUsuario(self::GPS_DE_USER, $ID_USUARIO);
            if ($usuarioBD != NULL) {
                http_response_code(200);
                $arreglo = array();
                while ($row = $usuarioBD->fetch()) {
                        array_push($arreglo, array(
                            "enlace_id" => $row[0],
                            "usuario_id" => $row[1],
                            "nombre" => $row[2],
                            "gps_id" => $row[3],
                            "imei" => $row[4],
                            "numero" => $row[5],
                            "descripcion" => $row[6],
                            "departamento_id" => $row[7]
                    ));
                }
                return ["estado" => 1, "usuarios" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el departamento");
        }
    }

private function listarCoordenadasDeTodosSusGpsPorFecha()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        if (!empty($usuario)) {
            $ID_USUARIO = $usuario->usuario_id;
            $FECHA_INICIAL = $usuario->fecha_inicial;
            $FECHA_FINAL = $usuario->fecha_final;
            $usuarioBD = self::obtenerCoordenadasUsuario(self::COORDENADAS_GPS_DE_USER_FECHA, $ID_USUARIO, $FECHA_INICIAL, $FECHA_FINAL);
            if ($usuarioBD != NULL) {
                http_response_code(200);
                $arreglo = array();
                while ($row = $usuarioBD->fetch()) {
                        array_push($arreglo, array(
                            "enlace_id" => $row[0],
                            "usuario_id" => $row[1],
                            "nombre" => $row[2],
                            "detalle_id" => $row[3],
                            "fecha" => $row[4],
                            "coordenadas_id" => $row[5],
                            "longitud" => $row[6],
                            "latitud" => $row[7],
                            "gps_id" => $row[8],
                            "imei" => $row[9],
                            "numero" => $row[10],
                            "descripcion" => $row[11]
                    ));
                }
                return ["estado" => 1, "usuarios" => $arreglo];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error probablemente no se encontro el dato");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Se desconoce el enlace");
        }
    }
    
    
    private function actualizar($usuario, $idUsuario)
    {
        try {
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::NOMBRE . "=?," .
                self::APPATERNO . "=?," .
                self::APMATERNO . "=?," .
                self::TELEFONO . "=?," .
                self::CORREO . "=?," .
                //self::USUARIO . "=?," .
                self::CONTRASE_NA . "=?" .
                " WHERE " . self::ID_USUARIO . "=?";


            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $ap_paterno);
            $sentencia->bindParam(3, $ap_materno);
            $sentencia->bindParam(4, $telefono);
            $sentencia->bindParam(5, $correo);
            //$sentencia->bindParam(6, $usuariouser);
            $sentencia->bindParam(7, $contrase_na);
            $sentencia->bindParam(8, $idUsuario);

            $contrase_na = $usuario->contrase_na;
            //$contrasenaEncriptada = self::encriptarContrasena($contrase_na);
            //$contrasenaEncriptada = self::desencriptar($contrase_na);


            $nombre = $usuario->nombre;
            $ap_paterno = $usuario->ap_paterno;
            $ap_materno = $usuario->ap_materno;
            $telefono = $usuario->telefono;
            $correo = $usuario->correo;
            //$usuariouser = $usuario->usuario;


            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function eliminar($idUsuario)
    {
        $cantidad = self::comprobarEnlaces($idUsuario);

        if($cantidad["num_enlaces"] < 1) {
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
        }else{
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "El usuario cuenta con gps", 422);
        }
    }

    private function comprobarEnlaces($dato){
        $consulta = "SELECT " .
            "COUNT(e.usuario_id) as num_enlaces".
            " FROM enlace e".
            " WHERE e." . self::ID_USUARIO . "=?";
        //select count(e.usuario_id) num_enlaces from enlace e where e.usuario_id= 9
        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
        $sentencia->bindParam(1, $dato);
        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }

    private function encriptar($string){
        $key = "kay";
        $result = '';
        for($i=0; $i<strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)+ord($keychar));
            $result.=$char;
        }
        return base64_encode($result);
    }

    private function desencriptar($string) {
        $key = "kay";
        $result = '';
        $string = base64_decode($string);
        for($i=0; $i<strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)-ord($keychar));
            $result.=$char;
        }
        return $result;
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
     */
    private function login()
    {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $empresa = json_decode($body);

        $correo = $empresa->correo;
        $contrasena = $empresa->contrase_na;

        $resultadoLogin = self::autenticar($correo, $contrasena);
        if ($resultadoLogin != self::ESTADO_LOGIN_ERROR_CLAVE && $resultadoLogin != self::ESTADO_LOGIN_ERROR_USUARIO ) {
            $retornar["usuario_id"] = $resultadoLogin["usuario_id"];
            $retornar["nombre"] = $resultadoLogin["nombre"];
            $retornar["ap_paterno"] = $resultadoLogin["ap_paterno"];
            $retornar["ap_materno"] = $resultadoLogin["ap_materno"];
            $retornar["telefono"] = $resultadoLogin["telefono"];
            $retornar["correo"] = $resultadoLogin["correo"];
            //$retornar["usuario"] = $resultadoLogin["usuario"];
            $retornar["contrase_na"] = $resultadoLogin["contrase_na"];

            $infDepartamento = self::obtenerDepartamentoUsuario($resultadoLogin['departamento_id']);

            if ($infDepartamento != NULL) {
                $retornar["departamento_id"] = $infDepartamento["departamento_id"];
                $retornar["nombre_departamento"] = $infDepartamento["nombre"];

                $infEmpresa = self::obtenerEmpresaUsuario($infDepartamento["empresa_id"]);
                //$resultadoLogin["empresa_id"] = $infDepartamento["empresa_id"];
                //$resultadoLogin["status"] = $infDepartamento["status"];
                if($infEmpresa != NULL && $infEmpresa["status"] != "0"){
                    http_response_code(200);
                    $retornar["empresa_id"] = $infEmpresa["empresa_id"];
                    $retornar["nombre_empresa"] = $infEmpresa["nombre"];
                    $retornar["status"] = $infEmpresa["status"];
                    return ["estado" => 1, "usuarios" => $retornar];
                }else{
                    // throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    // "La empresa no esta habilitada");
                    $error["mensaje"] =  "La empresa no esta habilitada";
                    return ["estado" => 5, "usuarios" => $error];
                }
            } else {
              throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error");
            }
        } else if ( $resultadoLogin == self::ESTADO_LOGIN_ERROR_CLAVE ){
            // throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
            //     utf8_encode("Datos incorrectos"));
            $error["mensaje"] =  "Datos incorrectos";
            return ["estado" => 5, "usuarios" => $error];
        } else if ( $resultadoLogin == self::ESTADO_LOGIN_ERROR_USUARIO ){
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Usuario incorrecto"));
        }
    }
    

    private function autenticar($correo, $contrase_na)
    {
        $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=? AND ". self::CONTRASE_NA . "=?";

        try {

            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $correo);
            $sentencia->bindParam(2, $contrase_na);

            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();
                if ($contrase_na == $resultado['contrase_na']) {
                    return $resultado;
                //} else return false;
                } else return self::ESTADO_LOGIN_ERROR_CLAVE;
            } else {
                //return false;
                return self::ESTADO_LOGIN_ERROR_USUARIO;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
    

    /*
    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
        return password_verify($contrasenaPlana, $contrasenaHash);
    }
    */

    

    private function obtenerUsuario($tipo, $dato = NULL)
    {
        switch ($tipo) {
            case self::INDIVIDUAL:
                //uno
                $consulta = "SELECT " .
                    self::ID_USUARIO . "," .
                    self::NOMBRE . "," .
                    self::APPATERNO . "," .
                    self::APMATERNO . ", " .
                    self::TELEFONO . ", " .
                    self::CORREO . ", " .
                    //self::USUARIO . ", " .
                    self::CONTRASE_NA . ", " .
                    self::ID_DEPARTAMENTO .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia->fetch(PDO::FETCH_ASSOC);
                else
                    return null;
                break;
            case self::MULTIPLES:
                //varios
                $consulta = "SELECT " .
                    self::ID_USUARIO . "," .
                    self::NOMBRE . "," .
                    self::APPATERNO . "," .
                    self::APMATERNO . ", " .
                    self::TELEFONO . ", " .
                    self::CORREO . ", " .
                    //self::USUARIO . ", " .
                    self::CONTRASE_NA . ", " .
                    self::ID_DEPARTAMENTO .
                    " FROM " . self::NOMBRE_TABLA;
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
                break;
            case self::USER_EN_DEPARTAMENTO:
                //de departamento
                $consulta = "SELECT " .
                    self::ID_USUARIO . "," .
                    self::NOMBRE . "," .
                    self::APPATERNO . "," .
                    self::APMATERNO . ", " .
                    self::TELEFONO . ", " .
                    self::CORREO . ", " .
                    //self::USUARIO . ", " .
                    self::CONTRASE_NA . ", " .
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

            case self::GPS_DE_USER:
                $consulta = "SELECT " .
                    "e.enlace_id,".
                    "u." . self::ID_USUARIO . "," .
                    "u." . self::NOMBRE . "," .
                    "g.gps_id," .
                    "g.imei," .
                    "g.numero," .
                    "g.descripcion," .
                    "g.departamento_id" .
                    " FROM " . self::NOMBRE_TABLA . " u" .
                    " INNER JOIN enlace e ON (u.usuario_id = e.usuario_id)" .
                    " INNER JOIN gps g ON (g.gps_id = e.gps_id )" .
                    " WHERE u." . self::ID_USUARIO . "=?";

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $dato);

                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;

                break;
        }
    }

    private function obtenerCoordenadasUsuario($tipo, $idUsuario, $fechaInicial = NULL, $fechaFinal = NULL)
    {
        switch ($tipo) {
            case self::COORDENADAS_GPS_DE_USER_FECHA:
                $consulta = "SELECT " .
                "e.enlace_id,".
                " u." . self::ID_USUARIO . "," .
                " u.nombre,".
                " d.detalle_id,".
                " d.fecha,".
                " c.coordenadas_id,".
                " c.longitud,".
                " c.latitud,".
                " g.gps_id,".
                " g.imei,".
                " g.numero,".
                " g.descripcion".
                
                " FROM " . self::NOMBRE_TABLA . " u" .
                " INNER JOIN dbrs.enlace e ON ( u.usuario_id = e.usuario_id ) ".
                " INNER JOIN dbrs.detalle d ON ( e.enlace_id = d.enlace_id )".
                " INNER JOIN dbrs.coordenadas c ON ( d.detalle_id = c.detalle_id ) ".
                " INNER JOIN dbrs.gps g ON ( e.gps_id = g.gps_id ) ".
                
                " WHERE u." . self::ID_USUARIO . "=?" . 
                    " AND " . "d.fecha " . ">=?" .
                    " AND " . "d.fecha " . "<=?" ;

                // SELECT 
                // e.enlace_id, 
                // u.usuario_id, u.nombre,
                // d.detalle_id, d.fecha,
                // c.coordenadas_id, c.longitud, c.latitud, 
                // g.gps_id,g.imei, g.numero, g.descripcion 
                // FROM dbrs.usuarios u 
                // INNER JOIN dbrs.enlace e ON ( u.usuario_id = e.usuario_id ) 
                // INNER JOIN dbrs.detalle d ON ( e.enlace_id = d.enlace_id )
                // INNER JOIN dbrs.coordenadas c ON ( d.detalle_id = c.detalle_id ) 
                // INNER JOIN dbrs.gps g ON ( e.gps_id = g.gps_id ) 
                // Where u.usuario_id = 1 AND d.fecha >= '2016-08-08 00:00:00'
                // AND d.fecha <= '2016-08-08 00:00:00'

                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                $sentencia->bindParam(1, $idUsuario);
                $sentencia->bindParam(2, $fechaInicial);
                $sentencia->bindParam(3, $fechaFinal);
                
                if ($sentencia->execute())
                    return $sentencia;
                else
                    return null;
            break;
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

    
    private function obtenerDepartamentoUsuario($idUser)
    {
        $comando = "SELECT " . self::ID_DEPARTAMENTO .', '. selF::NOMBRE_DEPARTAMENTO.', '.self::EMPRESA_ID .
         " FROM " . self::NOMBRE_TABLA_DEPARTAMENTO .
            " WHERE " . self::ID_DEPARTAMENTO . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idUser);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado;
            // return $sentencia->fetch(PDO::FETCH_ASSOC);;
        } else
            return null;
    }

    private function obtenerEmpresaUsuario($idEmpresa)
    {
        $comando = "SELECT " . self::EMPRESA_ID .", ".self::NOMBRE_EMPRESA .", ".self::STATUS_EMPRESA.
            " FROM " . self::NOMBRE_TABLA_EMPRESA .
            " WHERE " . self::EMPRESA_ID . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $idEmpresa);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado;
        } else
            return null;
    }
    
}