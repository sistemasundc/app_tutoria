<?php  
// formato78_final.php
require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

/* 1) Semestre e id_carga: usar lo que viene del informe si existe */
$id_semestre = isset($semestre)
    ? (int)$semestre
    : (int)($_SESSION['S_SEMESTRE'] ?? 0);

$rol_usuario     = $_SESSION['S_ROL']        ?? '';
$id_doce_sesion  = $_SESSION['S_IDUSUARIO']  ?? null;

/* id_cargalectiva viene desde vista_previa_informe_final.php */
$id_cargalectiva = isset($id_cargalectiva) ? (int)$id_cargalectiva : 0;

if ($rol_usuario !== 'TUTOR DE AULA') {
    $id_doce_sesion = $_GET['id_docente'] ?? $id_doce_sesion;
}

if (!$id_doce_sesion) {
    echo "<p>Error: No se definió el docente.</p>";
    return;
}

/* 2) Consulta de sesiones de rol 6 (TUTOR DE AULA),
      SOLO del semestre e aula actual, sin duplicados */
$sqlSesiones = "
SELECT DISTINCT
    s.id_sesiones_tuto,
    s.tema,
    s.compromiso_indi,
    s.fecha,
    s.horaInicio,
    s.horaFin,
    s.reunion_tipo_otros,
    s.link,
    s.tipo_sesion_id,
    s.evidencia_1,
    s.evidencia_2,
    s.color,

    d.email_doce,
    CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) AS nombre_docente,

    cl.ciclo,
    cl.turno,
    cl.seccion,
    c.nom_car

FROM tutoria_sesiones_tutoria_f78 s

INNER JOIN docente d 
        ON d.id_doce = s.id_doce

INNER JOIN tutoria_asignacion_tutoria ta
        ON ta.id_docente = s.id_doce
       AND ta.id_semestre = s.id_semestre

INNER JOIN carga_lectiva cl
        ON cl.id_cargalectiva = ta.id_carga

INNER JOIN carrera c
        ON c.id_car = cl.id_car

WHERE s.id_rol      = 6
  AND s.id_doce     = ?
  AND s.id_semestre = ?

ORDER BY s.fecha ASC
";



$stmt = $conexion->conexion->prepare($sqlSesiones);
if (!$stmt) {
    die("Error en prepare: " . $conexion->conexion->error);
}
$stmt->bind_param("ii", $id_doce_sesion, $id_semestre);


$stmt->execute();
$resSesiones = $stmt->get_result();

if (!$resSesiones || $resSesiones->num_rows === 0) {
    echo "<p>No hay sesiones registradas para este docente en el semestre seleccionado.</p>";
    return;
}

/* 3) Mapeo de tipo de sesión */
$tipo_sesion = [
    1 => 'Presencial',
    4 => 'Google Meet',
    5 => 'Otra(s):'
];

/* 4) Número de estudiantes asignados por sesión (para saber si es grupal o individual) */
$sql_total_asignados = "
    SELECT sesiones_tutoria_id, COUNT(*) AS total
    FROM tutoria_detalle_sesion
    GROUP BY sesiones_tutoria_id
";
$res_total_asignados = $conexion->conexion->query($sql_total_asignados);
$asignados_por_sesion = [];

while ($fila = $res_total_asignados->fetch_assoc()) {
    $asignados_por_sesion[(int)$fila['sesiones_tutoria_id']] = (int)$fila['total'];
}

$contador = 1; // para el título de “ANEXOS”

/* 5) Mostrar sesiones */
while ($s = $resSesiones->fetch_assoc()):
    $id_sesion = (int)$s['id_sesiones_tuto'];

    // Estudiantes de la sesión
    $sqlEstudiantes = "
        SELECT 
            CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu,
            d.marcar_asis_estu
        FROM tutoria_detalle_sesion d
        INNER JOIN estudiante e ON e.id_estu = d.id_estu
        WHERE d.sesiones_tutoria_id = ?
    ";
    $stmtEst = $conexion->conexion->prepare($sqlEstudiantes);
    $stmtEst->bind_param("i", $id_sesion);
    $stmtEst->execute();
    $resEst = $stmtEst->get_result();

    $estudiantes = [];
    $i = 1;
    while ($e = $resEst->fetch_assoc()) {
        $estudiantes[] = [
            'numero'     => $i++,
            'estu'       => $e['estu'],
            'asistencia' => ($e['marcar_asis_estu'] == 1 ? 'A' : 'F')
        ];
    }
    $stmtEst->close();

    $asignados = $asignados_por_sesion[$id_sesion] ?? 0;
    $es_grupal = ($asignados > 1);
?>
<style>
    .documeto, h3, h2{
        font-family: 'Courier New', monospace; 
        font-size: 16px;
    }
    h3{
         font-size: 19px;
         font-weight: bolder;
    }
    h2{
        font-size: 20px;
         font-weight: bolder;
    }
</style>
<div class="salto-pagina documento">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px;">
      <div style="margin-right: 20px;">
        <img src="../../img/undc.png" alt="Logo UNDC" style="width: 180px;">
      </div>
      <table style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px;">
        <tr>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: left; border: 1px solid #000; width: 300px;">
            <strong>Código:</strong> F-M01.04-VRA-007<br>
            <strong>Fecha de Aprobación:</strong> 07/05/2024
          </td>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: center; border: 1px solid #000; width: 80px;">
            <strong>Versión:</strong> 02
          </td>
        </tr>
      </table>
    </div>
    <br><br>

    <?php if ($contador === 1): ?>
        <h4 style="text-align:center; font-size:16px; margin-bottom: -10px;">
            ANEXOS <br><br>
            <strong>Anexo 1: Registro de la Consejería y Tutoría Académica Grupal y <br> Anexo 2: Registro de la Consejería y Tutoría Académica Individual</strong>
        </h4><br>
    <?php endif; ?>

    <h2 style="text-align: center; color:rgb(30, 82, 41);">
       <strong>Registro de la Consejería y Tutoría Académica <?= $es_grupal ? 'Grupal' : 'Individual' ?></strong>
    </h2>

    <h3>I. DATOS INFORMATIVOS</h3>

    <?php if ($es_grupal): ?>
<pre style=" font-size: 14px;" >
Escuela Profesional: <?= htmlspecialchars($s['nom_car']) ?>         Ciclo: <?= htmlspecialchars($s['ciclo']) ?>      
 
Turno: <?= htmlspecialchars($s['turno']) ?>


Tutor: <?= htmlspecialchars($s['nombre_docente']) ?>


Correo: <?= htmlspecialchars($s['email_doce']) ?>


Fecha de Reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> a <?= htmlspecialchars($s['horaFin']) ?>
</pre>
    <?php else: ?>
<pre style=" font-size: 14px;" >
Escuela Profesional de <?= htmlspecialchars($s['nom_car']) ?> 

Estudiante: <?= htmlspecialchars($estudiantes[0]['estu'] ?? '—') ?> 

Ciclo académico: <?= htmlspecialchars($s['ciclo']) ?>            Turno: <?= htmlspecialchars($s['turno']) ?> 

Tutor: <?= htmlspecialchars($s['nombre_docente']) ?> 

Semestre académico: <?= htmlspecialchars($id_semestre) ?> 

Fecha de reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> 

</pre>
    <?php endif; ?>

    <h3>II. MODALIDAD</h3>
    <p>
        <?php
        foreach ($tipo_sesion as $id => $label) {
            $checked = ($s['tipo_sesion_id'] == $id) ? '&#x2714;' : '&#x25A1;';
            echo "$checked $label &nbsp;&nbsp;&nbsp;";
        }
        ?>
    </p>

    <h3>III. DETALLES DE LA SESIÓN</h3>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
            <tr style="background:rgb(50, 124, 40);  color:aliceblue;">
                <th>MOTIVO O ASUNTO</th>
                <th>COMPROMISOS ASUMIDOS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= nl2br(htmlspecialchars($s['tema'])) ?></td>
                <td><?= nl2br(htmlspecialchars($s['compromiso_indi'])) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if ($es_grupal && !empty($estudiantes)): ?>
        <h3>IV. RELACIÓN DE ESTUDIANTES ASISTENTES</h3>
        <table border="1" cellpadding="4" cellspacing="0" width="100%">
            <thead>
                <tr style="background:rgb(50, 124, 40); color:aliceblue;">
                    <th style="width:30px; text-align:center;">N°</th>
                    <th>Apellidos y Nombres</th>
                    <th style="width:40px; text-align:center;">Asis.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $e): ?>
                <tr>
                    <td style="text-align:center;"><?= $e['numero'] ?></td>
                    <td style="text-align: left;"><?= htmlspecialchars($e['estu']) ?></td>
                    <td style="text-align:center;"><?= $e['asistencia'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <br>
    <p style="text-align: center; font-size: 0.9em;">
        <em>Fecha de registro: <?= htmlspecialchars($s['fecha']) ?> <br>
        <?= htmlspecialchars($s['nombre_docente']) ?><br>
        Correo: <?= htmlspecialchars($s['email_doce']) ?></em>
    </p>
    
    <?php if (!empty($s['evidencia_1']) || !empty($s['evidencia_2'])): ?> 
        <h3>V. EVIDENCIAS</h3>

        <?php
        if (!function_exists('evidencia_url')) {
            function evidencia_url($evidencia) {
                $evidencia = trim($evidencia);
                if (empty($evidencia)) return '';
                if (strpos($evidencia, 'evidencias_sesion/') !== 0) {
                    $evidencia = 'evidencias_sesion/' . $evidencia;
                }
                return 'https://tutoria.undc.edu.pe/' . htmlspecialchars($evidencia);
            }
        }
        ?>

        <?php if (!empty($s['evidencia_1'])): ?>
            <div style="margin: 10px 0; text-align: center;">
                <img src="<?= evidencia_url($s['evidencia_1']) ?>" 
                     alt="Evidencia 1" style="max-width: 100%; height: auto;"><br>
                <span style="font-size: 14px;">Evidencia 1</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($s['evidencia_2'])): ?>
            <div style="margin: 10px 0; text-align: center;">
                <img src="<?= evidencia_url($s['evidencia_2']) ?>" 
                     alt="Evidencia 2" style="max-width: 100%; height: auto;"><br>
                <span style="font-size: 14px;">Evidencia 2</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php 
    $contador++;
endwhile;
