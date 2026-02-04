<?php
include 'conexion.php';
conectarbd();

// Verificamos si el parámetro 'id_usuario' está presente en la URL
if (isset($_GET['id_usuario'])) {
    $id_usuario = $_GET['id_usuario'];

    // Consultar los datos del usuario para mostrarlos en el formulario de edición
    $query = "SELECT * FROM tutoria_usuario WHERE id_usuario = '$id_usuario'";
    $resultado = mysqli_query($bd, $query);

    if ($resultado) {
        if (mysqli_num_rows($resultado) > 0) {
            $usuario = mysqli_fetch_assoc($resultado);
        } else {
            echo "Error: No se encontró un usuario con ese ID.";
            exit;
        }
    } else {
        echo "Error al ejecutar la consulta: " . mysqli_error($bd);
        exit;
    }
} else {
    echo "Error: ID de usuario no proporcionado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Editar Usuario</h2>
    <form action="procesar_editar_usuario.php" method="POST">
        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo $usuario['username']; ?>" required>
        </div>

        <div class="form-group">
            <label for="apaterno">Apellido Paterno:</label>
            <input type="text" class="form-control" id="apaterno" name="apaterno" value="<?php echo $usuario['apaterno']; ?>" required>
        </div>

        <div class="form-group">
            <label for="amaterno">Apellido Materno:</label>
            <input type="text" class="form-control" id="amaterno" name="amaterno" value="<?php echo $usuario['amaterno']; ?>" required>
        </div>

        <div class="form-group">
            <label for="nombres">Nombres:</label>
            <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo $usuario['nombres']; ?>" required>
        </div>

        <div class="form-group">
            <label for="dni_pa">DNI:</label>
            <input type="text" class="form-control" id="dni_pa" name="dni_pa" value="<?php echo $usuario['dni_pa']; ?>" required>
        </div>

        <div class="form-group">
            <label for="cor_inst">Correo Institucional:</label>
            <input type="email" class="form-control" id="cor_inst" name="cor_inst" value="<?php echo $usuario['cor_inst']; ?>" required>
        </div>

        <div class="form-group">
            <label for="cor_per">Correo Personal:</label>
            <input type="email" class="form-control" id="cor_per" name="cor_per" value="<?php echo $usuario['cor_per']; ?>" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $usuario['telefono']; ?>" required>
        </div>
        <div class="form-group">
            <label for="clave">clave:</label>
            <select class="form-control" id="clave" name="clave" required>
                <option value="$2y$10$z59petq59vhlIMNS/F.WXuOsgpIyGhMlaJb2SRpZJggA5upPwEEpS">123</option>
               
            </select>
        </div>

        <div class="form-group">
            <label for="rol_id">Rol:</label>
            <select class="form-control" id="rol_id" name="rol_id" required>
                <option value="1" <?php echo ($usuario['rol_id'] == 1) ? 'selected' : ''; ?>>Administrador</option>
                <option value="2" <?php echo ($usuario['rol_id'] == 2) ? 'selected' : ''; ?>>Docente</option>
                <option value="3" <?php echo ($usuario['rol_id'] == 3) ? 'selected' : ''; ?>>Alumno</option>
                <option value="4" <?php echo ($usuario['rol_id'] == 4) ? 'selected' : ''; ?>>Coordinador</option>
                <option value="5" <?php echo ($usuario['rol_id'] == 5) ? 'selected' : ''; ?>>Apoyo</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
