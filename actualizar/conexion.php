<?php
// Definir los detalles de la conexión a la base de datos
$host = "localhost";
$usuario = "usr_sivireno";
$contrasena = "S1v1r3n0@";
$base_datos = "dbsivireno";

// Función para conectar a la base de datos
function conectarbd()
{
    global $bd, $host, $usuario, $contrasena, $base_datos;

    // Crear la conexión
    $bd = mysqli_connect($host, $usuario, $contrasena, $base_datos);

    // Establecer el conjunto de caracteres a UTF-8
    mysqli_set_charset($bd, 'utf8');

    // Comprobar si la conexión fue exitosa
    if (!$bd) {
        die("Error de conexión: " . mysqli_connect_error());
    }
}

// Función para desconectar la base de datos
function desconectarbd()
{
    global $bd;
    mysqli_close($bd);
}

// Conectar a la base de datos al incluir este archivo
conectarbd();
?>
