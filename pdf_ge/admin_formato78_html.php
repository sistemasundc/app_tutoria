<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
session_start();

$mes_texto = isset($_GET['mes']) ? strtolower(trim($_GET['mes'])) : null;

$mapMes = [
    'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5,
    'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10,
    'noviembre' => 11, 'diciembre' => 12
];

$mes_num = $mapMes[$mes_texto] ?? null;

if (!$mes_num) {
    echo "<p>No se puede mostrar el formato 78: faltan datos del mes.</p>";
    return;
}

$id_semestre =32 /* $_SESSION['S_SEMESTRE'] */;
$id_car ="1,2,3,4,5"/*  $_SESSION['S_SCHOOL'] */;
$anio = date("Y");

require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

$sqlSesiones = "
SELECT DISTINCT
    s.id_sesiones_tuto, s.tema, s.compromiso_indi, s.fecha, s.horaInicio, s.horaFin,
    s.reunion_tipo_otros, s.link, s.tipo_sesion_id, s.evidencia_1, s.evidencia_2,
    s.id_rol, d.id_doce, d.email_doce,
    CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) AS nombre_docente,
    cl.ciclo, cl.turno, cl.nom_car,
    s.color
FROM tutoria_sesiones_tutoria_f78 s
INNER JOIN docente d ON d.id_doce = s.id_doce
LEFT JOIN (
    SELECT cl_inner.id_doce, cl_inner.ciclo, cl_inner.turno, cl_inner.id_car, ca.nom_car
    FROM carga_lectiva cl_inner
    JOIN carrera ca ON ca.id_car = cl_inner.id_car
    WHERE cl_inner.id_semestre = ? AND cl_inner.id_car = ?
    GROUP BY cl_inner.id_doce
) cl ON cl.id_doce = s.id_doce
WHERE MONTH(s.fecha) = ? 
  AND YEAR(s.fecha) = ?
  AND s.color = '#00a65a'
  AND d.id_doce IN (
      SELECT id_doce FROM carga_lectiva
      WHERE id_car = ? AND id_semestre = ?
  )
ORDER BY s.fecha ASC
";
$stmtSes = $conexion->conexion->prepare($sqlSesiones);
if (!$stmtSes) {
    die("Error en prepare: " . $conexion->conexion->error);
}
$stmtSes->bind_param(
    "iiiiii",
    $id_semestre,  // para cl.id_semestre
    $id_car,       // para cl.id_car
    $mes_num,      // para MONTH(s.fecha)
    $anio,         // para YEAR(s.fecha)
    $id_car,       // para id_car en IN
    $id_semestre   // para id_semestre en IN
);
$stmtSes->execute();
$resSesiones = $stmtSes->get_result();

$sqlTodosEstudiantes = "
SELECT 
    d.sesiones_tutoria_id,
    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu,
    d.marcar_asis_estu
FROM tutoria_detalle_sesion d
JOIN estudiante e ON e.id_estu = d.id_estu
UNION ALL
SELECT 
    d.sesiones_tutoria_id,
    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estu,
    d.marcar_asis_estu
FROM tutoria_detalle_sesion_curso d
JOIN estudiante e ON e.id_estu = d.id_estu
";
$resTodos = $conexion->conexion->query($sqlTodosEstudiantes);
$estudiantes_por_sesion = [];
while ($fila = $resTodos->fetch_assoc()) {
    $id_sesion = (int)$fila['sesiones_tutoria_id'];
    if (!isset($estudiantes_por_sesion[$id_sesion])) {
        $estudiantes_por_sesion[$id_sesion] = [];
    }
    $estudiantes_por_sesion[$id_sesion][] = [
        'estu' => $fila['estu'],
        'asistencia' => $fila['marcar_asis_estu'] == 1 ? 'A' : 'F'
    ];
}

$tipo_sesion = [
    1 => 'Presencial',
    4 => 'Google Meet',
    5 => 'Otra(s):'
];

$contador = 1; // inicializamos
?>
<title>Anexos 2 y 3 - Formatos F7 - F8</title>
<style>
.documento { width: 210mm; min-height: 297mm; padding: 20mm;  margin: auto; background: white; box-shadow: 0 0 5px rgba(0,0,0,0.1);  box-sizing: border-box; position: relative;}
footer {  position: absolute; bottom: 10mm;  left: 0;  right: 0;  text-align: center;  font-size: 10px;}
@media print {
  body {
    background-color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .documento { 
    background-color: white !important; box-shadow: none !important; margin: 0 !important;padding: 20mm !important;page-break-after: always; page-break-inside: avoid;
  }
  #btn-imprimir {
    display: none !important;
  }
  .boton-imprimir {
    display: none !important; /* ocultar ícono al imprimir */
  }
  .celda-activa {
    background-color: #50aaff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
}
.boton-imprimir { position: fixed; top: 20px; right: 20px; /* ahora esquina superior derecha */z-index: 9999; background-color: #007bff; color: white; padding: 10px 12px;border-radius: 50%; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: background 0.3s;}
.boton-imprimir:hover {background-color: #0056b3;}
@media print {
  .boton-imprimir {
    display: none !important;
  }
}
/* Evidencias más pequeñas y consistentes (A4) */
.evidencias { display: flex;gap: 8mm; justify-content: center; align-items: flex-start; flex-wrap: wrap;margin: 6mm 0;}
.evidencia { width: 90mm;              /* ancho fijo del “card” */ text-align: center;}
.evidencia img { display: block; max-width: 90mm;          /* no excede el card */ max-height: 65mm;         /* limita alto (mantiene proporción) */ width: auto; height: auto; object-fit: contain;      /* evita recortes */ margin: 0 auto;}
.evidencia figcaption { font-size: 12px; margin-top: 2mm;}
@media print {
  .evidencia { width: 80mm; }          /* un pelín más chico al imprimir */
  .evidencia img { max-width: 80mm; max-height: 60mm; }
}
</style>

<?php while ($s = $resSesiones->fetch_assoc()): ?>
<?php 
$id_sesion = (int)$s['id_sesiones_tuto'];
$estudiantes = [];
if (isset($estudiantes_por_sesion[$id_sesion])) {
    $i = 1;
    foreach ($estudiantes_por_sesion[$id_sesion] as $e) {
        $estudiantes[] = [
            'numero' => $i++,
            'estu' => $e['estu'],
            'asistencia' => $e['asistencia']
        ];
    }
}
$es_grupal = count($estudiantes) > 1;
?>

<div class="salto-pagina documento">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
        </div>
        <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
            <tr>
                <td style="padding: 3px 20px; width:180px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-007<br>
                    <strong>Fecha de Aprobación:</strong> 07/05/2024
                </td>
                <td style="border-left: 1px solid #000; width: 50px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 02</strong>
                </td>
            </tr>
        </table>
    </div>
    <div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">
        🖨️
        </div>
    <?php if ($contador === 1): ?>
    <h4 style="text-align:left; font-size:16px; margin-bottom: -10px;">
        <strong>Anexo 2: F.M01.04-VRA-007 Registro de la Consejería y Tutoría Académica Grupal </strong>
    </h4><br>
    <?php endif; ?>

    <h2 style="text-align: center; color:rgb(30, 82, 41);">
       <strong>Registro de la Consejería y Tutoría Académica <?= $es_grupal ? 'Grupal' : 'Individual' ?></strong>
    </h2>

<h3>I. DATOS INFORMATIVOS</h3>
<pre style="font-size: 14px;">
<?php if ($es_grupal): ?>
Escuela Profesional: <?= htmlspecialchars($s['nom_car']) ?>         Ciclo: <?= htmlspecialchars($s['ciclo']) ?>      
Turno: <?= htmlspecialchars($s['turno']) ?>


Tutor: <?= htmlspecialchars($s['nombre_docente']) ?>


Correo: <?= htmlspecialchars($s['email_doce']) ?>


Fecha de Reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> a <?= htmlspecialchars($s['horaFin']) ?>
<?php else: ?>
Escuela Profesional de <?= htmlspecialchars($s['nom_car']) ?> 

Estudiante: <?= htmlspecialchars($estudiantes[0]['estu'] ?? '—') ?> 

Ciclo académico: <?= htmlspecialchars($s['ciclo']) ?>            Turno: <?= htmlspecialchars($s['turno']) ?> 

Tutor: <?= htmlspecialchars($s['nombre_docente']) ?> 

Semestre académico: <?= htmlspecialchars($id_semestre) ?> 

Fecha de reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> 
<?php endif; ?>
</pre>

<h3>II. MODALIDAD</h3>
<p>
<?php foreach ($tipo_sesion as $id => $label): ?>
<?= ($s['tipo_sesion_id'] == $id ? '&#x2714;' : '&#x25A1;') ?> <?= $label ?>&nbsp;&nbsp;&nbsp;
<?php endforeach; ?>
</p>

<h3>III. DETALLES DE LA SESIÓN</h3>
<table border="1" cellpadding="5" cellspacing="0" width="100%">
<thead>
<tr style="background:rgb(50, 124, 40); color:aliceblue;">
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
<td><?= htmlspecialchars($e['estu']) ?></td>
<td style="text-align:center;"><?= $e['asistencia'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<br>
<p style="text-align: center; font-size: 0.9em;">
<em>Fecha de registro: <?= htmlspecialchars($s['fecha']) ?><br>
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

    <div class="evidencias">
        <?php if (!empty($s['evidencia_1'])): ?>
        <figure class="evidencia">
            <a href="<?= evidencia_url($s['evidencia_1']) ?>" target="_blank" rel="noopener">
            <img src="<?= evidencia_url($s['evidencia_1']) ?>" alt="Evidencia 1">
            </a>
            <figcaption>Evidencia 1</figcaption>
        </figure>
        <?php endif; ?>

        <?php if (!empty($s['evidencia_2'])): ?>
        <figure class="evidencia">
            <a href="<?= evidencia_url($s['evidencia_2']) ?>" target="_blank" rel="noopener">
            <img src="<?= evidencia_url($s['evidencia_2']) ?>" alt="Evidencia 2">
            </a>
            <figcaption>Evidencia 2</figcaption>
        </figure>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<footer>
<p>
Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”
</p>
</footer>

</div>

<?php 
$contador++; 
endwhile; 
?>
