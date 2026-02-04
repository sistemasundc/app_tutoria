<?php   
$id_semestre = $_SESSION['S_SEMESTRE'];

if (!isset($mes_num) || !isset($id_cargalectiva_formato78)) {
    echo "<p>No se puede mostrar el formato 78: faltan datos.</p>";
    return;
}

require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

// ✅ Para tutor de curso solo se usa 1 carga
$cargas = [$id_cargalectiva_formato78];
$ids = implode(',', array_map('intval', $cargas));

// Buscar sesiones F78 por tipo y mes
$sqlSesiones = "
SELECT s.id_sesiones_tuto, s.tema, s.compromiso_indi, s.fecha, s.horaInicio, s.horaFin,
       s.reunion_tipo_otros, s.link, s.tipo_sesion_id, s.evidencia_1, s.evidencia_2, d.id_doce, d.email_doce,
       CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) as nombre_docente,
       cl.ciclo, c.nom_car, cl.turno, s.color
FROM tutoria_sesiones_tutoria_f78 s
INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = s.id_carga AND cl.id_cargalectiva = $id_cargalectiva_formato78
INNER JOIN docente d ON d.id_doce = cl.id_doce
INNER JOIN carrera c ON c.id_car = cl.id_car
WHERE s.id_rol = 2 AND MONTH(s.fecha) = $mes_num
ORDER BY s.fecha ASC
";

$resSesiones = $conexion->conexion->query($sqlSesiones);

if (!$resSesiones) {
    echo "<p>Error al obtener sesiones F78: " . $conexion->conexion->error . "</p>";
    return;
}

// Tipos de modalidad
$tipo_sesion = [
    1 => 'Presencial',
    4 => 'Google Meet',
    5 => 'Otra(s):'
];

// Obtener número de estudiantes registrados por sesión
$sql_total_asignados = "
    SELECT sesiones_tutoria_id, COUNT(*) AS total
    FROM tutoria_detalle_sesion_curso
    GROUP BY sesiones_tutoria_id
";
$res_total_asignados = $conexion->conexion->query($sql_total_asignados);
$asignados_por_sesion = [];

while ($fila = $res_total_asignados->fetch_assoc()) {
    $asignados_por_sesion[(int)$fila['sesiones_tutoria_id']] = (int)$fila['total'];
}

// Empieza el ciclo por sesión
while ($s = $resSesiones->fetch_assoc()):
    $id_sesion = $s['id_sesiones_tuto'];

    // Traer estudiantes de la sesión
    $sqlEstudiantes = "
        SELECT 
            CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu,
            d.marcar_asis_estu
        FROM tutoria_detalle_sesion_curso d
        INNER JOIN estudiante e ON e.id_estu = d.id_estu
        WHERE d.sesiones_tutoria_id = $id_sesion
    ";

    $estudiantes = [];
    $resEst = $conexion->conexion->query($sqlEstudiantes);

    if ($resEst) {
        $i = 1;
        while ($e = $resEst->fetch_assoc()) {
            $estudiantes[] = [
                'numero' => $i++,
                'estu' => $e['estu'],
                'asistencia' => $e['marcar_asis_estu'] == 1 ? 'A' : 'F'
            ];
        }
    }

    // Determinar si es grupal
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
    <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
    <img src="../../img/encabezado3.JPG" class="encabezado-img" alt="Encabezado">
    <h2 style="text-align: center; color:rgb(30, 82, 41);">
       <strong> Registro de la Consejería y Tutoría Académica <?= $es_grupal ? 'Grupal' : 'Individual' ?></strong>
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
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
            <thead>
                <tr style="background:rgb(50, 124, 40); color:aliceblue;">
                    <th style="width: 30px; text-align: center;">N°</th>
                    <th style="text-align: left;">Apellidos y Nombres</th>
                    <th style="width: 50px; text-align: center;">Asis.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $e): ?>
                    <tr>
                        <td style="text-align: center;"><?= $e['numero'] ?></td>
                        <td style="text-align: left;"><?= htmlspecialchars($e['estu']) ?></td>
                        <td style="text-align: center;"><?= $e['asistencia'] ?></td>
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
            <div style="text-align:center; margin: 10px 0;">
                <img src="<?= evidencia_url($s['evidencia_1']) ?>" width="400"><br>
                <span style="font-size: 14px;">Evidencia 1</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($s['evidencia_2'])): ?>
            <div style="text-align:center; margin: 10px 0;">
                <img src="<?= evidencia_url($s['evidencia_2']) ?>" width="400"><br>
                <span style="font-size: 14px;">Evidencia 2</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <footer style=" margin-top: 300px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>
<?php endwhile; ?>
