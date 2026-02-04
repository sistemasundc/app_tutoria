<?php
session_start();
date_default_timezone_set('America/Lima');
if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'VICEPRESIDENCIA ACADEMICA') {
    die("Acceso denegado.");
}

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

$sql = "
SELECT 
    rd.id_plan_tutoria,
    tp.id_cargalectiva,
    cl.ciclo,
    rd.fecha_revision,
    tpc.fecha_envio
FROM tutoria_revision_director rd
JOIN tutoria_plan2 tp ON rd.id_plan_tutoria = tp.id_plan_tutoria
JOIN carga_lectiva cl ON tp.id_cargalectiva = cl.id_cargalectiva
LEFT JOIN tutoria_plan_compartido tpc ON tpc.id_plan_tutoria = tp.id_plan_tutoria
WHERE rd.estado_revision = 'CONFORME'
";
$resultado = $conexion->conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Planes de Tutoría Conformes</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fc;
        }
        .card-custom {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 50px auto;
        }
        table {
            width: 100%;
            text-align: center;
        }
        h3 {
        margin-bottom: 20px;
        color: #2c3e50;
        }
        th, td {
            vertical-align: middle;
        }
        h3 {
        margin-bottom: 20px;
        color: #2c3e50;
        font-weight: bold;
        }
        th {
        background-color: #154360;
        color: white;
        text-align: center;
        }
    </style>
</head>
<<body>
    <div class="card-custom">
        <h3 class="text-center mb-4">PLANES DE TUTORÍA CONFORMES</h3>
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Ciclo</th>
                    <th>Fecha de Envio</th>
                    <th>Acción</th>
                    <th>Fecha de Conformidad</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['ciclo']) ?></td>
                    <td> <?= $row['fecha_envio'] ?? '-----' ?></td>
                    <td>
                        <form onsubmit="return abrirVentanaPopup(this);" method="GET" action="https://tutoria.undc.edu.pe/vista/tutor_aula/vista_prev_plan_tutoria.php">
                            <input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
                            <input type="hidden" name="id_plan" value="<?= $row['id_plan_tutoria'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Ver plan</button>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($row['fecha_revision']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

<script>
  function abrirVentanaPopup(form) {
    const ancho = 900;
    const alto = 700;
    const izquierda = (screen.width - ancho) / 2;
    const arriba = (screen.height - alto) / 2;

    const url = new URL(form.action);
    const params = new URLSearchParams(new FormData(form));
    url.search = params.toString();

    window.open(
      url.toString(),
      'VistaPrevTutoria',
      `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no`
    );

    return false; // Prevenir el envío tradicional del formulario
  }
</script>
</html>
