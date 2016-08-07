<?php

class departamento
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "departamento";
    const ID_DEPARTAMENTO = "departamento_id";
    const NOMBRE = "nombre";
    const TELEFONO = "telefono";
    const CORREO = "correo";
    const DIRECCION = "direccion";
    const EMPRESA_ID = "empresa_id";

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    const LIST_INDIVIDUAL = "uno";
    const LIST_MULTIPLES = "varios";
    const LIST_HABILITADO = "habiltados";
    const LIST_DESHABILITADO = "deshabilitados";
    const LIST_NOMBRE = "nombre";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;


    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'listarUno_Id') {
            return self::listarUnoId();
        } else if ($peticion[0] == 'listarVarios') {
            return self::listarVarios();
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
        $departamento = json_decode($cuerpo);
        if (!empty($departamento )) {
            $resultado = self::crear($departamento );
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
        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $departamento = json_decode($body);

            if (self::actualizar($departamento , $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El departamento a la que intentas acceder no existe", 404);
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
     * Crea un nuevo departamento en la tabla "departamento"
     * @param mixed $datos
     * @return int codigo para determinar si la insercion fue exitosa
     */
    private function crear($datosDepartamento)
    {
        $nombre = $datosDepartamento->nombre;
        $telefono = $datosDepartamento->telefono;
        $correo = $datosDepartamento->correo;
        $direccion = $datosDepartamento->direccion;
        $empresa_id = $datosDepartamento->empresa_id;

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
                self::TELEFONO . "," .
                self::CORREO . "," .
                self::DIRECCION . ",".
                self::EMPRESA_ID . ")" .
                " VALUES(?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $telefono);
            $sentencia->bindParam(3, $correo);
            $sentencia->bindParam(4, $direccion);
            $sentencia->bindParam(5, $empresa_id);

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
        $departamento = json_decode($body);

        if (isset($departamento)) {

            $id = $departamento->departamento_id;

            if ($id != NULL) {

                $departamentoBD = self::obtenerDepartamento(self::LIST_INDIVIDUAL, $id);

                if ($departamentoBD != NULL) {
                    http_response_code(200);
                    $respuesta["departamento_id"] = $departamentoBD["departamento_id"];
                    $respuesta["nombre"] = $departamentoBD["nombre"];
                    $respuesta["telefono"] = $departamentoBD["telefono"];
                    $respuesta["correo"] = $departamentoBD["correo"];
                    $respuesta["direccion"] = $departamentoBD["direccion"];

                    return ["estado" => 1, "departamento" => $respuesta];
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
        $usuarioBD = self::obtenerDepartamento(self::LIST_MULTIPLES, NULL, NULL);

        if ($usuarioBD != NULL) {
            http_response_code(200);

            $arreglo = array();
            while ($row = $usuarioBD->fetch()) {
                array_push($arreglo, array(
                    "departamento_id" => $row[0],
                    "nombre" => $row[1],
                    "telefono" => $row[2],
                    "correo" => $row[3],
                    "direccion" => $row[4]
                ));
            }
            return ["estado" => 1, "departamento" => $arreglo];
        } else {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                "Ha ocurrido un error probablemente no se encontro el dato");
        }
    }



    private function actualizar($departamento, $idDepartamento)
    {
        try {
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::NOMBRE . "=?," .
                self::TELEFONO . "=?," .
                self::CORREO . "=?," .
                self::DIRECCION . "=? " .
                " WHERE " . self::ID_DEPARTAMENTO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $nombre);
            $sentencia->bindParam(2, $telefono);
            $sentencia->bindParam(3, $correo);
            $sentencia->bindParam(4, $direccion);
            $sentencia->bindParam(5, $idDepartamento);

            $nombre = $departamento->nombre;
            $telefono = $departamento->telefono;
            $correo = $departamento->correo;
            $direccion = $departamento->direccion;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function eliminar($idDepartamento)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_DEPARTAMENTO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idDepartamento);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function obtenerDepartamento($tipo, $id = NULL)
    {
        switch ($tipo) {
            case self::LIST_INDIVIDUAL:
                $consulta = "SELECT " .
                    self::ID_DEPARTAMENTO . "," .
                    self::NOMBRE . "," .
                    self::TELEFONO . "," .
                    self::CORREO . ", " .
                    self::DIRECCION .
                    " FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_DEPARTAMENTO . "=?";
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

                $sentencia->bindParam(1, $id);

                if ($sentencia->execute())
                    return $sentencia->fetch(PDO::FETCH_ASSOC);
                else
                    return null;
                break;
            case self::LIST_MULTIPLES:
                $consulta = "SELECT " .
                    self::ID_DEPARTAMENTO . "," .
                    self::NOMBRE . "," .
                    self::TELEFONO . "," .
                    self::CORREO . ", " .
                    self::DIRECCION .
                    " FROM " . self::NOMBRE_TABLA;
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);
                if ($sentencia->execute())
                    //return $sentencia->fetch(PDO::FETCH_ASSOC);
                    return $sentencia;
                else
                    return null;
                break;
        }
    }
}
