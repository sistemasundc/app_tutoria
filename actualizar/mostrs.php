<?php
function conectarbd()
{
    global $bd;
    $bd = mysqli_connect("localhost", "usr_sivireno", "S1v1r3n0@", "dbsivireno");
    mysqli_set_charset($bd, 'utf8');
    
    if (!$bd) {
        die("Error de conexión: " . mysqli_connect_error());
    }
}

function desconectarbd()
{
    global $bd;    
    mysqli_close($bd);
}

// Conectar a la base de datos
conectarbd();

// Realizar la consulta para obtener los datos de la tabla "usuario"
$query = "SELECT * FROM tutoria_docente_asignado";
$resultado = mysqli_query($bd, $query);

// Comprobar si la consulta fue exitosa
if ($resultado) {
    // Imprimir los datos en una tabla HTML
    echo "<table border='1'>
            <tr>
                <th>ID Usuario</th>
                <th>Username</th>
                <th>Apellido Paterno</th>
                <th>Apellido Materno</th>
                <th>Nombres</th>
                <th>DNI</th>
                <th>Correo Institucional</th>
                <th>Correo Personal</th>
                <th>Clave</th>
                <th>Foto</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th>Seleccionado</th>
                <th>ID Carrera</th>
                <th>ID Rol</th>
            </tr>";

    // Recorrer los resultados y mostrar cada fila en la tabla
    while ($fila = mysqli_fetch_assoc($resultado)) {
        echo "<tr>";
        echo "<td>" . $fila['id_docente_tutor'] . "</td>";
        echo "<td>" . $fila['id_doce'] . "</td>";
        echo "<td>" . $fila['id_semestre'] . "</td>";
        echo "<td>" . $fila['amaterno'] . "</td>";
        echo "<td>" . $fila['id_docente_tutor'] . "</td>";
        echo "<td>" . $fila['dni_pa'] . "</td>";
        echo "<td>" . $fila['cor_inst'] . "</td>";
        echo "<td>" . $fila['cor_per'] . "</td>";
        echo "<td>" . $fila['clave'] . "</td>";
        echo "<td>" . $fila['foto'] . "</td>";
        echo "<td>" . $fila['telefono'] . "</td>";
        echo "<td>" . $fila['estado'] . "</td>";
        echo "<td>" . $fila['seleccionado'] . "</td>";
        echo "<td>" . $fila['id_car'] . "</td>";
        echo "<td>" . $fila['rol_id'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "Error al realizar la consulta: " . mysqli_error($bd);
}

// Desconectar de la base de datos
desconectarbd();
?>
