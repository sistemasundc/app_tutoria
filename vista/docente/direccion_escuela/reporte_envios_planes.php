<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO','DIRECCION DE ESCUELA','COMITÉ - SUPERVISIÓN'])) {
    die('Acceso no autorizado');
}

$id_car = $_SESSION['S_SCHOOL'] ?? null;
$id_semestre = $_SESSION['S_SEMESTRE'] ?? null;
$nombre_semestre = $_SESSION['S_NOMSEMESTRE'] ?? '2025-2';

$nombre_carrera = '……………………………';
if ($id_car) {
    $sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
    $stmtCarrera = $conexion->conexion->prepare($sqlCarrera);
    $stmtCarrera->bind_param("i", $id_car);
    $stmtCarrera->execute();
    $resCarrera = $stmtCarrera->get_result();
    if ($rowCar = $resCarrera->fetch_assoc()) {
        $nombre_carrera = strtoupper($rowCar['nom_car']);
    }
}

if (!$id_car || !$id_semestre) {
    die(" Error: Sesión sin carrera o semestre.");
}

// Obtener lista de docentes
// === LISTA DE TUTORES DE AULA DEL SEMESTRE/CARRERA ===
//   - tda: quién es tutor de aula (id_rol=6)
//   - tat: cargas donde ese docente está asignado como TUTORÍA DE AULA (tipo_asignacion_id=1)
//   - cl : datos de la carga (ciclo, etc.)
$sql_tutores = "
SELECT 
    d.id_doce,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.email_doce,
    GROUP_CONCAT(DISTINCT cl.ciclo ORDER BY cl.ciclo SEPARATOR ', ') AS ciclo
FROM tutoria_docente_asignado tda
JOIN docente d
  ON d.id_doce = tda.id_doce
JOIN tutoria_asignacion_tutoria tat
  ON tat.id_docente  = tda.id_doce
 AND tat.id_semestre = tda.id_semestre
JOIN carga_lectiva cl
  ON cl.id_cargalectiva = tat.id_carga
 AND cl.id_semestre     = tda.id_semestre
 AND cl.id_car          = tda.id_car
WHERE tda.id_car = ? 
  AND tda.id_semestre = ?
GROUP BY d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
ORDER BY d.apepa_doce, d.apema_doce, d.nom_doce
";
$stmt = $conexion->conexion->prepare($sql_tutores);
$stmt->bind_param("ii", $id_car, $id_semestre);
$stmt->execute();
$tutores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// === GUARDARON (>=1) Y ENVIARON (=2) — por DOCENTE que figura en TPC ===
$sql_guardaron = "
SELECT DISTINCT tpc.id_docente
FROM tutoria_plan_compartido tpc
WHERE tpc.id_car = ? 
  AND tpc.id_semestre = ?
  AND tpc.estado_envio >= 1
";
$stmt3 = $conexion->conexion->prepare($sql_guardaron);
$stmt3->bind_param("ii", $id_car, $id_semestre);
$stmt3->execute();
$docentes_que_guardaron = array_column($stmt3->get_result()->fetch_all(MYSQLI_ASSOC), 'id_docente');

$sql_envios = "
SELECT DISTINCT tpc.id_docente
FROM tutoria_plan_compartido tpc
WHERE tpc.id_car = ? 
  AND tpc.id_semestre = ?
  AND tpc.estado_envio = 2
";
$stmt2 = $conexion->conexion->prepare($sql_envios);
$stmt2->bind_param("ii", $id_car, $id_semestre);
$stmt2->execute();
$docentes_con_envio = array_column($stmt2->get_result()->fetch_all(MYSQLI_ASSOC), 'id_docente');


// Separar enviados y no enviados
$enviaron = [];
$no_enviaron = [];
foreach ($tutores as $docente) {
    if (in_array($docente['id_doce'], $docentes_con_envio)) {
        $enviaron[] = $docente;
    } else {
        $no_enviaron[] = $docente;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Relación de Docentes Tutores</title>
    <style>
        body {
            background-color: white !important;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            color: black;
        }
        .contenedor {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
        }
        h2 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 8px;
        }
        h3 {
            text-align: left;
            font-size: 15px;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th {
            background: #1a5276;
            color: white;
            padding: 8px;
            text-align: left;
        }
        td {
            padding: 8px;
            border: 1px solid #ccc;
        }
        .fila-verde {
            background-color:rgb(177, 243, 192);
            font-size: 15px;
        }
        .fila-roja {
            background-color:rgb(241, 193, 197);
            font-size: 15px;
        }
        .fila-amarilla {
            background-color:rgb(245, 243, 148); /* amarillo suave */
            font-size: 15px;
        }

        .boton-imprimir {
            display: block;
            margin: 0 auto 30px auto;
            background-color: #1a5276;
            color: white;
            padding: 10px 20px;
            border: none;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            float: right;
        }
        

        @media print {
            .boton-imprimir {
                display: none !important;
            }
        }
        
    </style>
</head>
<body>
    <button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>

    <div class="contenedor">
        <h2>RELACIÓN DE DOCENTES TUTORES SEMESTRE <?= htmlspecialchars($nombre_semestre) ?></h2>
        <h2 style="font-weight: normal;">ESCUELA PROFESIONAL DE <?= $nombre_carrera ?></h2>

        <h3>LISTA DE DOCENTES QUE PRESENTARON SU PLAN DE TUTORÍA</h3>
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Docente</th>
                    <th>Correo</th>
                    <th>Ciclo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($enviaron) === 0): ?>
                    <tr><td colspan="4">Ningún docente ha enviado aún.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($enviaron as $doc): ?>
                        <tr class="fila-verde">
                            <td><?= $i++ ?></td>
                            <td><?= strtoupper($doc['abreviatura_doce'] . ' ' . $doc['apepa_doce'] . ' ' . $doc['apema_doce'] . ', ' . $doc['nom_doce']) ?></td>
                            <td><?= $doc['email_doce'] ?></td>
                            <td><?= strtoupper($doc['ciclo']) ?></td>
                            <td><?= in_array($doc['id_doce'], $docentes_que_guardaron) ? 'CUMPLIÓ' : 'NO CUMPLIÓ' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>LISTA DE DOCENTES QUE NO ENVIARON SU PLAN DE TUTORÍA</h3>
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Docente</th>
                    <th>Correo</th>
                    <th>Ciclo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($no_enviaron) === 0): ?>
                    <tr><td colspan="4">Todos los docentes han enviado su plan.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($no_enviaron as $doc): 
                        $cumplio = in_array($doc['id_doce'], $docentes_que_guardaron);
                        $claseFila = $cumplio ? 'fila-amarilla' : 'fila-roja';
                    ?>
                        <tr class="<?= $claseFila ?>">
                            <td><?= $i++ ?></td>
                            <td><?= strtoupper($doc['abreviatura_doce'] . ' ' . $doc['apepa_doce'] . ' ' . $doc['apema_doce'] . ', ' . $doc['nom_doce']) ?></td>
                            <td><?= $doc['email_doce'] ?></td>
                            <td><?= strtoupper($doc['ciclo']) ?></td>
                            <td><?= $cumplio ? 'CUMPLIÓ' : 'NO CUMPLIÓ' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
