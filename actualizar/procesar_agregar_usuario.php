<?php
include 'conexion.php';
conectarbd();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $apaterno = $_POST['apaterno'];
    $amaterno = $_POST['amaterno'];
    $nombres = $_POST['nombres'];
    $dni_pa = $_POST['dni_pa'];
    $cor_inst = $_POST['cor_inst'];
    $cor_per = $_POST['cor_per'];
    $clave = $_POST['clave'];
    $telefono = $_POST['telefono'];
    $estado = $_POST['estado'];
    $rol_id = $_POST['rol_id'];

    $query = "INSERT INTO tutoria_usuario (username, apaterno, amaterno, nombres, dni_pa, cor_inst, cor_per, clave, telefono, estado, rol_id) 
              VALUES ('$username', '$apaterno', '$amaterno', '$nombres', '$dni_pa', '$cor_inst', '$cor_per', '$clave', '$telefono', '$estado', '$rol_id')";

    if (mysqli_query($bd, $query)) {
        header('Location: index.php');
    } else {
        echo "Error: " . mysqli_error($bd);
    }
}

desconectarbd();
?>
