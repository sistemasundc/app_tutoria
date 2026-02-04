<?php
include 'conexion.php';
conectarbd();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_usuario = $_POST['id_usuario'];
    $username = $_POST['username'];
    $apaterno = $_POST['apaterno'];
    $amaterno = $_POST['amaterno'];
    $nombres = $_POST['nombres'];
    $dni_pa = $_POST['dni_pa'];
    $cor_inst = $_POST['cor_inst'];
    $cor_per = $_POST['cor_per'];
    $clave = $_POST['clave'];
    $telefono = $_POST['telefono'];
    $rol_id = $_POST['rol_id'];

    // Mostrar los valores recibidos para depuración
    echo "Datos recibidos para la actualización:<br>";
    echo "ID Usuario: $id_usuario<br>";
    echo "Username: $username<br>";
    echo "Apellido Paterno: $apaterno<br>";
    echo "Apellido Materno: $amaterno<br>";
    echo "Nombres: $nombres<br>";
    echo "DNI: $dni_pa<br>";
    echo "Correo Institucional: $cor_inst<br>";
    echo "Correo Personal: $cor_per<br>";
    echo "Clave: $clave<br>";
    echo "Teléfono: $telefono<br>";
    echo "Rol ID: $rol_id<br>";

    $query = "UPDATE tutoria_usuario 
              SET username = '$username', apaterno = '$apaterno', amaterno = '$amaterno', nombres = '$nombres', 
                  dni_pa = '$dni_pa', cor_inst = '$cor_inst', cor_per = '$cor_per', clave = '$clave', telefono = '$telefono', 
                  id_car = '4' , 
                  rol_id = '$rol_id' 
              WHERE id_usuario = '$id_usuario'";

    // Mostrar la consulta SQL para depuración
    echo "<br>Consulta SQL ejecutada:<br>$query<br>";

    // Ejecutar la consulta e imprimir el resultado
    if (mysqli_query($bd, $query)) {
        echo "Registro actualizado correctamente.";
        header('Location: index.php');
    } else {
        echo "Error al actualizar el registro: " . mysqli_error($bd);
    }
}

desconectarbd();
?>
