<?php

/**
 * Clase base para la representacion de las vistas
 */
abstract class VistaApi{

    // Codigo de error
    public $estado;

    public abstract function imprimir($cuerpo);
}