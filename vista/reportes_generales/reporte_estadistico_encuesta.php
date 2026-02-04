<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

$conexion = new conexion();
$cn = $conexion->conectar();

$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 33);

/* =======================
   PREGUNTAS
======================= */
$preguntas = [
  'q1' => 'Las tutorías fueron útiles',
  'q2' => 'Los temas abordados fueron relevantes',
  'q3' => 'La frecuencia fue adecuada',
  'q4' => 'La atención recibida fue satisfactoria',
  'q5' => 'La retroalimentación fue clara',
  'q6' => 'El acompañamiento académico fue oportuno'
];
/* =======================
   ENCUESTA POR CARRERA (4 y 5)
======================= */
$sqlCarrera = "
SELECT
  c.nom_car AS carrera,
  ROUND(
    AVG(
      (
        (e.q1 >= 4) +
        (e.q2 >= 4) +
        (e.q3 >= 4) +
        (e.q4 >= 4) +
        (e.q5 >= 4) +
        (e.q6 >= 4)
      ) / 6
    ) * 100
  ,2) AS porcentaje_satisfaccion
FROM tutoria_encuesta_estudiantes e
INNER JOIN estudiante est ON est.email_estu = e.email
INNER JOIN asignacion_estudiante ae 
        ON ae.id_estu = est.id_estu
       AND ae.id_semestre = $id_semestre
INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = ae.id_cargalectiva
INNER JOIN carrera c ON c.id_car = cl.id_car
WHERE e.id_semestre = $id_semestre
GROUP BY c.id_car, c.nom_car
ORDER BY c.nom_car;

";

$datosCarrera = [];

$resCarrera = $cn->query($sqlCarrera);
if (!$resCarrera) {
    die("Error SQL Carrera: " . $cn->error);
}

while ($row = $resCarrera->fetch_assoc()) {
    $datosCarrera[] = [
        'carrera' => $row['carrera'],
        'porcentaje' => (float)$row['porcentaje_satisfaccion']
    ];
}


/* =======================
   RESPUESTAS ABIERTAS
======================= */
$comentarios = [];
$sqlTxt = "SELECT q7_texto 
           FROM tutoria_encuesta_estudiantes
           WHERE id_semestre = $id_semestre
           AND q7_texto IS NOT NULL
           AND q7_texto <> ''";
$resTxt = $cn->query($sqlTxt);

while ($r = $resTxt->fetch_assoc()) {
  $comentarios[] = htmlspecialchars($r['q7_texto']);
}
//==============================================INDICADOR 1=====================================================
// (Estudiantes satisfechos o muy satisfechos / total encuestados) * 100
$sqlInd1 = "
  SELECT
    COUNT(*) AS total,
    SUM(
      (
        (q1 + q2 + q3 + q4 + q5 + q6) / 6
      ) >= 4
    ) AS satisfechos
  FROM tutoria_encuesta_estudiantes
  WHERE id_semestre = $id_semestre
  ";
$resInd1 = $cn->query($sqlInd1);
if (!$resInd1) {
    die("Error SQL Indicador 1: " . $cn->error);
}

$row = $resInd1->fetch_assoc();

$total_estudiantes = (int)$row['total'];
$satisfechos = (int)$row['satisfechos'];

$porcentaje1 = $total_estudiantes > 0
    ? round(($satisfechos / $total_estudiantes) * 100, 2)
    : 0;

// =================================== INDICADOR 2 ==========================================================
// Participación en sesiones grupales (rol 6 y 2) SOLO semestre actual
$asistencias = [];

// Aula
$sqlAula = "SELECT id_sesiones_tuto FROM tutoria_sesiones_tutoria_f78 WHERE id_rol=6 AND id_semestre=$id_semestre";
$resAula = $cn->query($sqlAula);
$idsAula = [];
while ($r = $resAula->fetch_assoc()) $idsAula[] = $r['id_sesiones_tuto'];

if (!empty($idsAula)) {
    $listaAula = implode(",", $idsAula);
    $sqlDetAula = "SELECT id_estu, COUNT(*) total, SUM(marcar_asis_estu) asistencias
                   FROM tutoria_detalle_sesion 
                   WHERE sesiones_tutoria_id IN ($listaAula) 
                   GROUP BY id_estu";
    $res = $cn->query($sqlDetAula);
    while ($row = $res->fetch_assoc()) {
        $id = $row['id_estu'];
        $asistencias[$id]['total'] = ($asistencias[$id]['total'] ?? 0) + $row['total'];
        $asistencias[$id]['asistencias'] = ($asistencias[$id]['asistencias'] ?? 0) + $row['asistencias'];
    }
}

// Curso
$sqlCurso = "SELECT id_sesiones_tuto FROM tutoria_sesiones_tutoria_f78 WHERE id_rol=2 AND id_semestre=$id_semestre";
$resCurso = $cn->query($sqlCurso);
$idsCurso = [];
while ($r = $resCurso->fetch_assoc()) $idsCurso[] = $r['id_sesiones_tuto'];

if (!empty($idsCurso)) {
    $listaCurso = implode(",", $idsCurso);
    $sqlDetCurso = "SELECT id_estu, COUNT(*) total, SUM(marcar_asis_estu) asistencias
                    FROM tutoria_detalle_sesion_curso 
                    WHERE sesiones_tutoria_id IN ($listaCurso) 
                    GROUP BY id_estu";
    $res = $cn->query($sqlDetCurso);
    while ($row = $res->fetch_assoc()) {
        $id = $row['id_estu'];
        $asistencias[$id]['total'] = ($asistencias[$id]['total'] ?? 0) + $row['total'];
        $asistencias[$id]['asistencias'] = ($asistencias[$id]['asistencias'] ?? 0) + $row['asistencias'];
    }
}

$total_estudiantes = 0;
$superan_70 = 0;
foreach ($asistencias as $estu) {
    $total_estudiantes++;
    $porcentaje = $estu['total'] > 0 ? $estu['asistencias'] / $estu['total'] : 0;
    if ($porcentaje > 0.70) $superan_70++;
}
$porcentaje_final = $total_estudiantes > 0 ? round(($superan_70 / $total_estudiantes) * 100, 2) : 0;

// ========================================= INDICADOR 3 =====================================================================
// Sesiones individuales SOLO semestre actual
$asistencias = [];

// Aula
$sqlIndivAula = "SELECT sesiones_tutoria_id 
                 FROM tutoria_detalle_sesion 
                 GROUP BY sesiones_tutoria_id 
                 HAVING COUNT(*) = 1";
$res = $cn->query($sqlIndivAula);
$idsIndivAula = [];
while ($row = $res->fetch_assoc()) $idsIndivAula[] = $row['sesiones_tutoria_id'];

if (!empty($idsIndivAula)) {
    $listaAula = implode(",", $idsIndivAula);
    $sqlDetalleAula = "SELECT id_estu, COUNT(*) total, SUM(marcar_asis_estu) asistencias
                       FROM tutoria_detalle_sesion 
                       WHERE sesiones_tutoria_id IN ($listaAula) 
                       GROUP BY id_estu";
    $res = $cn->query($sqlDetalleAula);
    while ($row = $res->fetch_assoc()) {
        $id = $row['id_estu'];
        $asistencias[$id]['total'] = ($asistencias[$id]['total'] ?? 0) + $row['total'];
        $asistencias[$id]['asistencias'] = ($asistencias[$id]['asistencias'] ?? 0) + $row['asistencias'];
    }
}

// Curso
$sqlIndivCurso = "SELECT sesiones_tutoria_id 
                  FROM tutoria_detalle_sesion_curso 
                  GROUP BY sesiones_tutoria_id 
                  HAVING COUNT(*) = 1";
$res = $cn->query($sqlIndivCurso);
$idsIndivCurso = [];
while ($row = $res->fetch_assoc()) $idsIndivCurso[] = $row['sesiones_tutoria_id'];

if (!empty($idsIndivCurso)) {
    $listaCurso = implode(",", $idsIndivCurso);
    $sqlDetalleCurso = "SELECT id_estu, COUNT(*) total, SUM(marcar_asis_estu) asistencias
                        FROM tutoria_detalle_sesion_curso 
                        WHERE sesiones_tutoria_id IN ($listaCurso) 
                        GROUP BY id_estu";
    $res = $cn->query($sqlDetalleCurso);
    while ($row = $res->fetch_assoc()) {
        $id = $row['id_estu'];
        $asistencias[$id]['total'] = ($asistencias[$id]['total'] ?? 0) + $row['total'];
        $asistencias[$id]['asistencias'] = ($asistencias[$id]['asistencias'] ?? 0) + $row['asistencias'];
    }
}

$total_estudiantes = 0;
$superan_70 = 0;
foreach ($asistencias as $estu) {
    $total_estudiantes++;
    $porcentaje3 = $estu['total'] > 0 ? $estu['asistencias'] / $estu['total'] : 0;
    if ($porcentaje3 > 0.70) $superan_70++;
}
$porcentaje_final2 = $total_estudiantes > 0 ? round(($superan_70 / $total_estudiantes) * 100, 2) : 0;

// ========================================================= INDICADOR 4 =================================================================
// Cumplimiento de tutores (preg_6 + informes + sesiones creadas)
// 1) Universo: todos los docentes del semestre actual (rol 2 y 6 ya están en esta tabla)
$docentes = [];
$q = "SELECT DISTINCT id_doce FROM tutoria_docente_asignado WHERE id_semestre = $id_semestre";
$r = $cn->query($q);
while ($row = $r->fetch_assoc()) { $docentes[] = (int)$row['id_doce']; }
$total_tutores = count($docentes);

// 2) Meses con INFORME ENVIADO (=2) por docente (aula + curso), normalizando el id
//    Convertimos mes_informe (texto) a número para cruzarlo con las sesiones (por fecha).
$mapMes = "CASE LOWER(mes_informe)
  WHEN 'enero' THEN 1 WHEN 'febrero' THEN 2 WHEN 'marzo' THEN 3 WHEN 'abril' THEN 4 WHEN 'mayo' THEN 5
  WHEN 'junio' THEN 6 WHEN 'julio' THEN 7 WHEN 'agosto' THEN 8 WHEN 'septiembre' THEN 9
  WHEN 'octubre' THEN 10 WHEN 'noviembre' THEN 11 WHEN 'diciembre' THEN 12 ELSE NULL END";

$infPorDoc = []; // id_doce => set de meses con informe
$sqlInf = "
  SELECT id_docente AS id_doce, $mapMes AS mes_num
  FROM tutoria_informe_mensual
  WHERE id_semestre = $id_semestre AND estado_envio = 2

  UNION ALL

  SELECT id_doce AS id_doce, $mapMes AS mes_num
  FROM tutoria_informe_mensual_curso
  WHERE id_semestre = $id_semestre AND estado_envio = 2
";
$resInf = $cn->query($sqlInf);
while ($row = $resInf->fetch_assoc()) {
  $d = (int)$row['id_doce'];
  $m = (int)$row['mes_num'];
  if ($d && $m) { $infPorDoc[$d][$m] = true; }
}

// 3) Meses con SESIÓN registrada por docente (se usa la columna fecha de f78)
$sesPorDoc = []; // id_doce => set de meses con sesión
$sqlSes = "
  SELECT id_doce, MONTH(fecha) AS mes_num
  FROM tutoria_sesiones_tutoria_f78
  WHERE id_semestre = $id_semestre
  GROUP BY id_doce, MONTH(fecha)
";
$resSes = $cn->query($sqlSes);
while ($row = $resSes->fetch_assoc()) {
  $d = (int)$row['id_doce'];
  $m = (int)$row['mes_num'];
  if ($d && $m) { $sesPorDoc[$d][$m] = true; }
}

// 4) Regla de cumplimiento (mensual):
//    El docente CUMPLE si: (a) tiene >= 1 informe en el semestre y
//    (b) para CADA mes con informe, tiene al menos 1 sesión en ese mismo mes.
$tutores_cumplen = 0;
foreach ($docentes as $id_doce) {
  $mesesInforme = isset($infPorDoc[$id_doce]) ? array_keys($infPorDoc[$id_doce]) : [];
  if (empty($mesesInforme)) { continue; } // no tiene informes => no cumple

  $okMeses = true;
  foreach ($mesesInforme as $m) {
    if (empty($sesPorDoc[$id_doce][$m])) { $okMeses = false; break; }
  }
  if ($okMeses) { $tutores_cumplen++; }
}

$porcentaje_final4 = ($total_tutores > 0) ? round(($tutores_cumplen / $total_tutores) * 100, 2) : 0;
?>
<style>
body {font-family: Arial, sans-serif;background: #f7f7f7;margin: 0;padding: 0;}
.container {max-width: 1300px;margin: 20px auto;background: #fff;padding: 20px;border-radius: 8px;box-shadow: 0 0 10px rgba(0,0,0,0.1);}
h3 {text-align: center;background-color: #2c3e50;color: white;padding: 15px;border-radius: 5px;font-size: 20px;margin-bottom: 20px;}
form {text-align: center;margin-bottom: 20px;}
.grafico-container { display: flex;flex-wrap: wrap; gap: 20px;}
.grafico {position: relative;flex: 1 1 0;overflow-x: auto;background: #fff;padding-bottom: 20px;}
.barras { display: flex; justify-content: flex-start; align-items: flex-end;  height: 300px;  position: relative;  border-bottom: 2px solid #333;  margin-left: 40px;  gap: 20px;  min-width: 600px;}
.carrera {display: flex;  flex-direction: column;  align-items: center;  width: 150px;  text-align: center;  margin: 0 2px;}
.barras-categoria {display: flex;  justify-content: space-between;  align-items: flex-end;  height: 100%;  gap: 2px;}
.barra {width: 12px;}
.eje-y-line {  position: absolute;  left: 40px;  right: 0;  border-top: 1px dashed #ccc;  font-size: 11px;  color: #555;}
.eje-y-label {  position: absolute;  left: 0;  width: 35px;  text-align: right;  font-size: 11px;  color: #555;}
.eje-x-container {  display: flex;  justify-content: flex-start;  gap: 20px;  margin-top: 5px;  margin-left: 40px;  min-width: 600px;}
.eje-x {  font-size: 12px;  font-weight: bold;  white-space: normal;  line-height: 1.1;  width: 150px;}
.leyenda {  width: 200px;  margin: auto;  margin-top: 20px;}
.leyenda-item {  margin-bottom: 5px;}
.cuadro {  width: 15px;  height: 15px;  display: inline-block;  margin-right: 5px;}
@media(max-width: 768px) {
  .container {    margin: 10px;  }
  .carrera {    width: 120px;  }
  .eje-x {    width: 120px; }
  .leyenda {    width: 100%;    text-align: center;  }
}
.reporte-container{
  max-width:1180px;
  margin:20px auto;
  background:#fff;
  border-radius:8px;
  padding:20px;
  box-shadow:0 2px 8px rgba(0,0,0,.1);
  font-family:Arial, sans-serif;
}

.titulo-reporte{
  background:#2c3e50;
  color:#fff;
  padding:15px;
  border-radius:6px;
  text-align:center;
  font-size:20px;
  margin-bottom:20px;
}

.grid-2{
  display:grid;
  grid-template-columns: 2fr 1fr;
  gap:20px;
}

.card{
  border:1px solid #ddd;
  border-radius:6px;
  padding:15px;
}

.card h4{
  margin-top:0;
  border-bottom:1px solid #ddd;
  padding-bottom:8px;
}

.tabla{
  width:100%;
  border-collapse:collapse;
}

.tabla th, .tabla td{
  border:1px solid #ccc;
  padding:8px;
  text-align:center;
}

.tabla th{
  background:#f2f2f2;
}

.comentario{
  background:#f7f7f7;
  border-left:4px solid #0b57d0;
  padding:10px;
  margin-bottom:10px;
  font-size:14px;
}
.comentarios-box{
  height: 380px;
}

.comentarios-scroll{
  height: 320px;
  overflow-y: auto;
  padding-right: 6px;
}

.comentarios-scroll::-webkit-scrollbar{
  width: 6px;
}
.comentarios-scroll::-webkit-scrollbar-thumb{
  background:#0b57d0;
  border-radius:4px;
}

</style>


<div class="container">
  <h3><strong>CUMPLIMIENTO DE INDICADORES - PROGRAMA DE TUTORÍA </strong></h3>
   <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
      <div style="background-color: #2c3e50; color: white; padding: 10px 15px; font-weight: bold; min-width: 260px;">
        Indicador 1:
      </div>
      <div style="flex: 1; padding: 10px;">
        Porcentaje de satisfacción del estudiante con el programa de tutoría académica.
      </div>
      <div style="width: 120px; background: #0b57d0; color: white; text-align: center; font-size: 18px; font-weight: bold;">
        <?= $porcentaje1 ?>%
      </div>
  </div>
  <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
      <div style="background-color: #2c3e50; color: white; padding: 10px 15px; font-weight: bold; min-width: 260px;">
        Indicador 2:
      </div>
      <div style="flex: 1; padding: 10px;">
        Porcentaje de estudiantes que participan activamente en las actividades de tutoría grupal.
      </div>
      <div style="width: 120px; background: #0b57d0; color: white; text-align: center; font-size: 18px; font-weight: bold;">
        <?= $porcentaje_final ?>%
      </div>

  </div>
  <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
      <div style="background-color: #2c3e50; color: white; padding: 10px 15px; font-weight: bold; min-width: 260px;">
        Indicador 3:
      </div>
      <div style="flex: 1; padding: 10px;">
        Porcentaje de estudiantes que participan activamente en las sesiones de tutoría individual.
      </div>
      <div style="width: 120px; background: #0b57d0; color: white; text-align: center; font-size: 18px; font-weight: bold;">
        <?= $porcentaje_final2 ?>%
      </div>

  </div>
  <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid #ccc; border-radius: 6px; overflow: hidden; margin-bottom: 15px;">
      <div style="background-color: #2c3e50; color: white; padding: 10px 15px; font-weight: bold; min-width: 260px;">
        Indicador 4:
      </div>
      <div style="flex: 1; padding: 10px;">
        Porcentaje de tutores que participan activamente en el programa y cumplen con las responsabilidades asignadas.
      </div>
      <div style="width: 120px; background: #0b57d0; color: white; text-align: center; font-size: 18px; font-weight: bold;">
        <?= $porcentaje_final4 ?>%
      </div>

  </div>

</div>
<div class="reporte-container">

  <div class="titulo-reporte">
    <strong>RESULTADOS DE LA ENCUESTA DE SATISFACCIÓN ESTUDIANTIL</strong>
  </div>

  <div class="grid-2">

    <!-- IZQUIERDA: GRÁFICO POR CARRERA -->
    <div class="card">
      <h4><strong>Resultados de satisfacción por carrera</strong></h4>

        <?php foreach ($datosCarrera as $fila): ?>
          <div style="margin-bottom:16px;">
            
            <div style="font-weight:bold;margin-bottom:6px;">
              <?= strtoupper($fila['carrera']) ?>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
              
              <div style="flex:1;background:#e9ecef;height:22px;border-radius:4px;overflow:hidden;">
                <div style="
                  width:<?= $fila['porcentaje'] ?>%;
                  background:#28a745;
                  height:100%;
                "></div>
              </div>

              <div style="min-width:60px;font-weight:bold;">
                <?= $fila['porcentaje'] ?>%
              </div>

            </div>
          </div>
        <?php endforeach; ?>

    </div>

    <!-- DERECHA: COMENTARIOS -->
    <div class="card comentarios-box">
      <h4><strong>Opiniones Anónimas</strong></h4>

      <div class="comentarios-scroll">
        <?php if(empty($comentarios)): ?>
          <p>No se registraron comentarios.</p>
        <?php else: ?>
          <?php foreach($comentarios as $c): ?>
            <div class="comentario">“<?= $c ?>”</div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", () => {
  ajustarFooter();
  window.addEventListener("resize", ajustarFooter);
});
document.querySelectorAll('.card')[1]?.scrollIntoView({
  behavior: 'smooth'
});

function ajustarFooter() {
  const wrapper = document.querySelector(".wrapper");
  const content = document.querySelector(".content-wrapper");
  const footer = document.querySelector(".main-footer");

  if (!wrapper || !content || !footer) return;

  const alturaVentana = window.innerHeight;
  const alturaHeader = document.querySelector(".main-header")?.offsetHeight || 0;
  const alturaFooter = footer.offsetHeight;

  const alturaContenido = content.offsetHeight;

  const alturaOcupada = alturaHeader + alturaFooter + alturaContenido;

  if (alturaOcupada < alturaVentana) {
    content.style.minHeight = (alturaVentana - alturaHeader - alturaFooter) + "px";
  } else {
    content.style.minHeight = "auto";
  }
}
</script>