<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Agregar Usuario</h2>
        <form action="procesar_agregar_usuario.php" method="POST" class="bg-white p-4 shadow-sm rounded">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="apaterno">Apellido Paterno</label>
                <input type="text" class="form-control" id="apaterno" name="apaterno" required>
            </div>
            <div class="form-group">
                <label for="amaterno">Apellido Materno</label>
                <input type="text" class="form-control" id="amaterno" name="amaterno" required>
            </div>
            <div class="form-group">
                <label for="nombres">Nombres</label>
                <input type="text" class="form-control" id="nombres" name="nombres" required>
            </div>
            <div class="form-group">
                <label for="dni_pa">DNI</label>
                <input type="text" class="form-control" id="dni_pa" name="dni_pa" required>
            </div>
            <div class="form-group">
                <label for="cor_inst">Correo Institucional</label>
                <input type="email" class="form-control" id="cor_inst" name="cor_inst" required>
            </div>
            <div class="form-group">
                <label for="cor_per">Correo Personal</label>
                <input type="email" class="form-control" id="cor_per" name="cor_per">
            </div>
            <div class="form-group">
                <label for="clave">Clave</label>
                <input type="password" class="form-control" id="clave" name="clave" required>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono">
            </div>
            <div class="form-group">
                <label for="estado">Estado</label>
                <select class="form-control" id="estado" name="estado" required>
                    <option value="ACTIVO">ACTIVO</option>
                    <option value="INACTIVO">INACTIVO</option>
                </select>
            </div>
            <div class="form-group">
                <label for="rol_id">Rol</label>
                <select class="form-control" id="rol_id" name="rol_id" required>
                    <option value="1">Administrador</option>
                    <option value="2">Docente</option>
                    <option value="3">Alumno</option>
                    <option value="4">Coordinador</option>
                    <option value="5">Apoyo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="listar_usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
