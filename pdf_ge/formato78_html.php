<?php  
if (session_status() === PHP_SESSION_NONE) session_start();

$id_semestre = $_SESSION['S_SEMESTRE'] ?? '';
$rol_usuario = $_SESSION['S_ROL'] ?? '';
$docente_sesion = $_SESSION['S_IDUSUARIO'] ?? 0;

// Variables que DEBEN existir desde la vista previa (padre)
$mes_num          = isset($mes_num) ? (int)$mes_num : (int)($_GET['mes_num'] ?? 0);
$id_cargalectiva  = isset($id_cargalectiva) ? (int)$id_cargalectiva : (int)($_GET['id_cargalectiva'] ?? 0);
// $id_plan_tutoria puede existir, pero ya no se usa para filtrar anexos
$id_plan_tutoria  = isset($id_plan_tutoria) ? (int)$id_plan_tutoria : (int)($_GET['id_plan_tutoria'] ?? 0);

// Determinar el docente a mostrar:
// 1) Si la vista previa ya definió $id_doce, úsalo.
// 2) Si no, usar el docente de sesión.
// 3) Si entra Dirección/Coordinación y pasa ?id_docente=, úsalo como fallback.
$id_docente_alcance = isset($id_doce) ? (int)$id_doce : (int)$docente_sesion;
if ($rol_usuario !== 'TUTOR DE AULA' && isset($_GET['id_docente'])) {
    $id_docente_alcance = (int)$_GET['id_docente'];
}

require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (!$mes_num || !$id_cargalectiva || !$id_docente_alcance) {
    echo "<p style='color:red'>Faltan parámetros: mes=$mes_num, carga=$id_cargalectiva, docente=$id_docente_alcance.</p>";
    return;
}

/* ===========================
   SESIONES (F7/F8) POR DOCENTE + CARGA + MES
   =========================== */
$sqlSesiones = "
SELECT 
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
    d.id_doce,
    d.email_doce,
    CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) AS nombre_docente,
    cl.ciclo,
    c.nom_car,
    cl.turno,
    s.color
FROM tutoria_sesiones_tutoria_f78 s
JOIN docente d         ON d.id_doce = s.id_doce
JOIN carga_lectiva cl  ON cl.id_cargalectiva = ?
JOIN carrera c         ON c.id_car = cl.id_car
WHERE s.id_rol = 6
  AND MONTH(s.fecha) = ?
  AND s.id_doce = ?
ORDER BY s.fecha ASC
";
$stmtSes = $conexion->conexion->prepare($sqlSesiones);
$stmtSes->bind_param("iii", $id_cargalectiva, $mes_num, $id_docente_alcance);
if (!$stmtSes->execute()) {
    echo "<p>Error al obtener sesiones F78: " . $stmtSes->error . "</p>";
    return;
}
$resSesiones = $stmtSes->get_result();

$tipo_sesion = [
    1 => 'Presencial',
    4 => 'Google Meet',
    5 => 'Otra(s):'
];

// Helper para contar asignados por sesión (A = asistentes/ausentes registrados en detalle)
$sqlCntAsign = "
    SELECT COUNT(*) AS total
    FROM tutoria_detalle_sesion
    WHERE sesiones_tutoria_id = ?
";
$stmtCntAsign = $conexion->conexion->prepare($sqlCntAsign);

// Helper para listar estudiantes de una sesión
$sqlEstudiantes = "
    SELECT 
        CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu,
        d.marcar_asis_estu
    FROM tutoria_detalle_sesion d
    JOIN estudiante e ON e.id_estu = d.id_estu
    WHERE d.sesiones_tutoria_id = ?
    ORDER BY e.apepa_estu, e.apema_estu, e.nom_estu
";
$stmtEst = $conexion->conexion->prepare($sqlEstudiantes);

// Función para armar URL de evidencias (si existe)
if (!function_exists('evidencia_url')) {
    function evidencia_url($evidencia) {
        $evidencia = trim((string)$evidencia);
        if ($evidencia === '') return '';
        if (strpos($evidencia, 'evidencias_sesion/') !== 0) {
            $evidencia = 'evidencias_sesion/' . $evidencia;
        }
        // Ajusta dominio si es diferente en producción
        return 'https://tutoria.undc.edu.pe/' . htmlspecialchars($evidencia, ENT_QUOTES, 'UTF-8');
    }
}
// Render de cada sesión (F7/F8)
while ($s = $resSesiones->fetch_assoc()):
    $id_sesion = (int)$s['id_sesiones_tuto'];

    // 1) Total asignados (determina si es grupal o individual)
    $total_asignados = 0;
    $stmtCntAsign->bind_param("i", $id_sesion);
    if ($stmtCntAsign->execute()) {
        $r = $stmtCntAsign->get_result()->fetch_assoc();
        $total_asignados = (int)($r['total'] ?? 0);
    }
    $es_grupal = ($total_asignados > 1);

    // 2) Listado de estudiantes (Asistentes/Ausentes)
    $estudiantes = [];
    $stmtEst->bind_param("i", $id_sesion);
    if ($stmtEst->execute()) {
        $resE = $stmtEst->get_result();
        $i = 1;
        while ($e = $resE->fetch_assoc()) {
            $estudiantes[] = [
                'numero'     => $i++,
                'estu'       => $e['estu'],
                'asistencia' => ((int)$e['marcar_asis_estu'] === 1 ? 'A' : 'F')
            ];
        }
    }
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

    <footer style=" margin-top: 300px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>
<?php endwhile; ?>