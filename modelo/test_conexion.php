<?php
require_once "modelo_conexion.php";

$c = new conexion();
$c->conectar();

echo "? Conexión exitosa";