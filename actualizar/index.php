<?php 
include 'conexion.php';
conectarbd();

$query = "SELECT * FROM tutoria_usuario";
$resultado = mysqli_query($bd, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Lista de Usuarios</h2>
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID Usuario</th>
                    <th>Username</th>
                    <th>Apellido Paterno</th>
                    <th>Apellido Materno</th>
                    <th>Nombres</th>
                    <th>DNI</th>
                    <th>Correo Institucional</th>
                    <th>Correo Personal</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>ID Carrera</th>
                    <th>ID Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td><?= $fila['id_usuario']; ?></td>
                        <td><?= $fila['username']; ?></td>
                        <td><?= $fila['apaterno']; ?></td>
                        <td><?= $fila['amaterno']; ?></td>
                        <td><?= $fila['nombres']; ?></td>
                        <td><?= $fila['dni_pa']; ?></td>
                        <td><?= $fila['cor_inst']; ?></td>
                        <td><?= $fila['cor_per']; ?></td>
                        <td><?= $fila['telefono']; ?></td>
                        <td><?= $fila['estado']; ?></td>
                        <td><?= $fila['id_car']; ?></td>
                        <td><?= $fila['rol_id']; ?></td>
                        <td><a href="editar_usuario.php?id_usuario=<?= $fila['id_usuario']; ?>" class="btn btn-primary btn-sm">Editar</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="agregar_usuario.php" class="btn btn-success mt-3">Agregar Usuario</a>
    </div>
</body>
</html>

<?php
desconectarbd();
?>
