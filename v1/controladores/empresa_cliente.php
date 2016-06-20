<?php

require('datos/ConexionBD.php');

//{"nombre":"nombresito","telefono":"1234","correo":"email@gmail.com","status":"1"}
class empresa_cliente
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "empresa_cliente";
    const ID_EMPRESA = "empresa_id";
    const NOMBRE = "nombre";
    const TELEFONO = "telefono";
    const CORREO = "correo";
    const STATUS = "status";

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
            //   return self::loguear();
        } else if ($peticion[0] == 'listarUno_Id') {
            return self::listarUnoId();
        } else if ($peticion[0] == 'listarVarios') {
            return self::listarVarios();
        } else if ($peticion[0] == 'listarPorNombre') {
            return self::listarPorNombre();
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
        $empresa = json_decode($cuerpo);
        if (!empty($empresa)) {
            $resultado = self::crear($empresa);
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


    public static function put($peticion)
    {
        //$idEmpresa = empresa_cliente::autorizar();

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $empresa = json_decode($body);

            //if (self::actualizar($idEmpresa, $empresa, $peticion[0]) > 0) {
            if (self::actualizar($empresa, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "La empresa a la que intentas acceder no existe", 404);
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
    private function crear($datosEmpresa)
    {
        $nombre = $datosEmpresa->nombre;
        $telefono = $datosEmpresa->telefono;
        $correo = $datosEmpresa->correo;
        $status = $datosEmpresa->status;

        //$contrasena = $datosEmpresa->contrasena;
        //$contrasenaEncriptada = self::encriptarContrasena($contrasena);

        //$clave_api = self::generarClaveApi();

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::TELEFONO . "," .
                self::CORREO . "," .
                self::STATUS . ")" .
                " VALUES(?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $telefono);
            $sentencia->bindParam(3, $correo);
            $sentencia->bindParam(4, $status);

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
        $empresa_cliente = json_decode($body);

        if (isset($empresa_cliente)) {

            $id = $empresa_cliente->empresa_id;

            if ($id != NULL) {

                $usuarioBD = self::obtenerEmpresa($id, NULL);

                if ($usuarioBD != NULL) {
                    http_response_code(200);
                    $respuesta["empresa_id"] = $usuarioBD["empresa_id"];
                    $respuesta["nombre"] = $usuarioBD["nombre"];
                    $respuesta["telefono"] = $usuarioBD["telefono"];
                    $respuesta["correo"] = $usuarioBD["correo"];
                    $respuesta["status"] = $usuarioBD["status"];

                    return ["estado" => 1, "empresa_cliente" => $respuesta];
                } else {
                    throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                        "Ha ocurrido un error probablemente no se encontro el dato");
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "Especifique el indice ");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                "Nomingreso el indice");
        }

    }


    private function listarPorNombre()
    {
        $body = file_get_contents('php://input');
        $empresa_cliente = json_decode($body);

        if (isset($empresa_cliente)) {

            $nombre = $empresa_cliente->nombre;

            if ($nombre != NULL) {

                $usuarioBD = self::obtenerEmpresa(NULL, $nombre);

                if ($usuarioBD != NULL) {
                    http_response_code(200);
                    $respuesta["empresa_id"] = $usuarioBD["empresa_id"];
                    $respuesta["nombre"] = $usuarioBD["nombre"];
                    $respuesta["telefono"] = $usuarioBD["telefono"];
                    $respuesta["correo"] = $usuarioBD["correo"];
                    $respuesta["status"] = $usuarioBD["status"];

                    return ["estado" => 1, "empresa_cliente" => $respuesta];
                } else {
                    throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                        "Ha ocurrido un error probablemente no se encontro el dato");
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "Especifique el indice ");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                "Nomingreso el indice");
        }

    }


    private function listarVarios()
    {
        $usuarioBD = self::obtenerEmpresa(NULL, NULL);

        if ($usuarioBD != NULL) {
            http_response_code(200);

            $arreglo = array();
            while ($row = $usuarioBD->fetch()) {
                array_push($arreglo, array(
                    "empresa_id" => $row[0],
                    "nombre" => $row[1],
                    "telefono" => $row[2],
                    "correo" => $row[3],
                    "status" => $row[4]
                ));
            }
//            foreach ($arreglo as $keys) {
//                foreach ($keys as $key => $value) {
//                    echo "key: " . $key .  " valor: " . $value . " ----\n";
//                }
//            }
            return ["estado" => 1, "empresa_cliente" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }

    //private function actualizar($idEmpresa, $empresa, $idContacto)
    private function actualizar($empresa, $idEmpresa)
    {
        try {
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::NOMBRE . "=?," .
                self::TELEFONO . "=?," .
                self::CORREO . "=?," .
                self::STATUS . "=? " .
                " WHERE " . self::ID_EMPRESA . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $telefono);
            $sentencia->bindParam(3, $correo);
            $sentencia->bindParam(4, $status);
            $sentencia->bindParam(5, $idEmpresa);

            if(empty($nombre = $empresa->nombre));
            $telefono = $empresa->telefono;
            $correo = $empresa->correo;
            $status = $empresa->status;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function eliminar($idEmpresa)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_EMPRESA . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idEmpresa);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    private function obtenerEmpresa($id = NULL, $nombre = NULL)
    {
        if ($nombre == NULL) {


            if ($id == NULL) {
                $consulta = "SELECT " .
                    self::ID_EMPRESA . "," .
                    self::NOMBRE . "," .
                    self::TELEFONO . "," .
                    self::CORREO . ", " .
                    self::STATUS .
                    " FROM " . self::NOMBRE_TABLA;
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute())
                    //return $sentencia->fetch(PDO::FETCH_ASSOC);
                    return $sentencia;
                else
                    return null;
            } else {
                $consulta = "SELECT " .
                    self::ID_EMPRESA . "," .
                    self::NOMBRE . "," .
                    self::TELEFONO . "," .
                    self::CORREO . ", " .
                    self::STATUS .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_EMPRESA . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $id);

                if ($sentencia->execute())
                    return $sentencia->fetch(PDO::FETCH_ASSOC);
                else
                    return null;
            }
        } else {
            $consulta = "SELECT " .
                self::ID_EMPRESA . "," .
                self::NOMBRE . "," .
                self::TELEFONO . "," .
                self::CORREO . ", " .
                self::STATUS .
                " FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::NOMBRE . "=?";
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombre);

            if ($sentencia->execute())
                return $sentencia->fetch(PDO::FETCH_ASSOC);
            else
                return null;
        }
    }
}
