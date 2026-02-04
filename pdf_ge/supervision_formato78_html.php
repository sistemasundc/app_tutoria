<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$id_semestre      = $_SESSION['S_SEMESTRE']   ?? '';
$rol_usuario      = $_SESSION['S_ROL']        ?? '';
$docente_sesion   = $_SESSION['S_IDUSUARIO']  ?? 0;

/* Parámetros que tu vista podría seguir enviando cuando NO se usa id_sesion */
$mes_num          = isset($mes_num) ? (int)$mes_num : (int)($_GET['mes_num'] ?? 0);
$id_cargalectiva  = isset($id_cargalectiva) ? (int)$id_cargalectiva : (int)($_GET['id_cargalectiva'] ?? 0);
$id_plan_tutoria  = isset($id_plan_tutoria) ? (int)$id_plan_tutoria : (int)($_GET['id_plan_tutoria'] ?? 0);

$id_docente_alcance = isset($id_doce) ? (int)$id_doce : (int)$docente_sesion;
if ($rol_usuario !== 'TUTOR DE AULA' && isset($_GET['id_docente'])) {
  $id_docente_alcance = (int)$_GET['id_docente'];
}

require_once(__DIR__ . '/../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

/* ===== Helpers ===== */
$tipo_sesion = [1 => 'Presencial', 4 => 'Google Meet', 5 => 'Otra(s):'];

if (!function_exists('evidencia_url')) {
  function evidencia_url($evidencia) {
    $evidencia = trim((string)$evidencia);
    if ($evidencia === '') return '';
    if (strpos($evidencia, 'evidencias_sesion/') !== 0) {
      $evidencia = 'evidencias_sesion/' . $evidencia;
    }
    // PROD: https://tutoria.undc.edu.pe/
    return 'https://tutoria.undc.edu.pe/' . htmlspecialchars($evidencia, ENT_QUOTES, 'UTF-8');
  }
}

/* ===========================================================
   RUTA 1: una sola sesión por ?id_sesion=...
   =========================================================== */
$id_sesion_req = (int)($_GET['id_sesion'] ?? 0);
if ($id_sesion_req > 0) {

  $sqlUna = "
      SELECT
        s.id_sesiones_tuto,
        s.tema,
        s.compromiso_indi,
        DATE_FORMAT(s.fecha,'%d/%m/%Y') AS fecha,
        s.horaInicio,
        s.horaFin,
        s.reunion_tipo_otros,
        s.link,
        s.tipo_sesion_id,
        s.evidencia_1,
        s.evidencia_2,
        s.color,
        COALESCE(det.cnt_det,0)        AS cnt_det,

        d.id_doce,
        d.email_doce,
        CONCAT(d.abreviatura_doce,' ', d.apepa_doce,' ', d.apema_doce,' ', d.nom_doce) AS nombre_docente,

        /* ===== Datos académicos desde asignación -> carga_lectiva ===== */
      COALESCE(MAX(cl.ciclo),  '')   AS ciclo,
      COALESCE(MAX(c.nom_car), '')   AS nom_car,
      COALESCE(MAX(cl.turno),  '')   AS turno,
      COALESCE(MAX(cl.seccion), '')  AS seccion

    FROM tutoria_sesiones_tutoria_f78 s
    JOIN docente d
      ON d.id_doce = s.id_doce

    /* Mapear docente+semestre a sus cargas asignadas */
    LEFT JOIN tutoria_asignacion_tutoria ta
      ON ta.id_docente = s.id_doce
    AND ta.id_semestre = s.id_semestre   -- usa el semestre de la sesión

    /* Traer datos de la carga lectiva correspondiente */
    LEFT JOIN carga_lectiva cl
      ON cl.id_cargalectiva = ta.id_carga
    AND cl.id_semestre     = s.id_semestre

    /* Carrera de la carga */
    LEFT JOIN carrera c
      ON c.id_car = cl.id_car

    /* Conteo de detalle de asistencia (si aplica) */
    LEFT JOIN (
      SELECT sesiones_tutoria_id, COUNT(*) AS cnt_det
      FROM tutoria_detalle_sesion
      GROUP BY sesiones_tutoria_id
    ) det
      ON det.sesiones_tutoria_id = s.id_sesiones_tuto

    WHERE s.id_rol = 6
      AND s.id_sesiones_tuto = ?
    GROUP BY
      s.id_sesiones_tuto, s.tema, s.compromiso_indi, s.fecha, s.horaInicio, s.horaFin,
      s.reunion_tipo_otros, s.link, s.tipo_sesion_id, s.evidencia_1, s.evidencia_2,
      s.color, d.id_doce, d.email_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce
    LIMIT 1;

  ";
  $stmtUna = $conexion->conexion->prepare($sqlUna);
  $stmtUna->bind_param("i", $id_sesion_req);
  $stmtUna->execute();
  $resUna = $stmtUna->get_result();
  $s = $resUna->fetch_assoc();

  if (!$s) { echo "<p style='color:red'>Sesión no encontrada.</p>"; exit; }

  // Seguridad: solo si la sesión está completa
  if (strtolower(trim($s['color'] ?? '')) !== '#00a65a') {
    echo "<p style='color:#c00'>La sesión aún no está completa. No se puede generar el formato.</p>";
    exit;
  }

  // ¿es grupal o individual?
  $total_asignados = (int)$s['cnt_det'];
  $es_grupal = ($total_asignados > 1);

  // Permite forzar con ?formato=F7|F8; si no, deduce por $es_grupal
  $formato_param = strtoupper($_GET['formato'] ?? '');
  if ($formato_param !== 'F7' && $formato_param !== 'F8') {
    $formato_param = $es_grupal ? 'F7' : 'F8';
  }

  // Encabezado e info del sello, según formato
  $encabezado_img = ($formato_param === 'F7')
    ? 'pdf/img/undc_f7.JPG'
    : 'pdf/img/undc_f8.JPG';

  // (Opcional) variables para “Código / Versión / Fecha”
  $codigo_formato  = ($formato_param === 'F7') ? 'F-M01.04-VRA-008' : 'F-M01.04-VRA-009';
  $version_formato = '02';
  $fecha_aprob     = '07/05/2024';

  // Estudiantes (para la tabla en grupal)
  $id_sesion = (int)$s['id_sesiones_tuto'];
  $sqlEstudiantes = "
      SELECT CONCAT(e.apepa_estu,' ',e.apema_estu,' ',e.nom_estu) AS estu,
             d.marcar_asis_estu
      FROM tutoria_detalle_sesion d
      JOIN estudiante e ON e.id_estu = d.id_estu
      WHERE d.sesiones_tutoria_id = ?
      ORDER BY e.apepa_estu, e.apema_estu, e.nom_estu
  ";
  $stmtEst = $conexion->conexion->prepare($sqlEstudiantes);
  $stmtEst->bind_param("i", $id_sesion);
  $stmtEst->execute();
  $resE = $stmtEst->get_result();
  $estudiantes = [];
  $i = 1;
  while ($e = $resE->fetch_assoc()) {
    $estudiantes[] = [
      'numero'     => $i++,
      'estu'       => $e['estu'],
      'asistencia' => ((int)$e['marcar_asis_estu'] === 1 ? 'A' : 'F')
    ];
  }

  /* ===== Render UNA sesión ===== */
  ?>
  <style>
    .documeto, h3, h2{ font-family: 'Courier New', monospace; font-size:16px; }
    .documento { width:210mm; min-height:297mm; padding:20mm; margin:auto; background:#fff; box-shadow:0 0 5px rgba(0,0,0,.1); position:relative; box-sizing:border-box; }
    footer { position:absolute; bottom:10mm; left:20mm; right:20mm; text-align:center; font-size:10px; }
    h3{ font-size:19px; font-weight:bolder; }
    h2{ font-size:20px; font-weight:bolder; }
    .boton-imprimir{ position:fixed; top:20px; right:20px; z-index:9999; background:#007bff; color:#fff; padding:10px 12px; border-radius:50%; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,.3); }
    .boton-imprimir:hover{ background:#0056b3; }
    @media print { .boton-imprimir{ display:none !important; } }
    /* Sello superior derecho (Código / Versión / Fecha) - opcional */
  </style>

  <div class="salto-pagina documento">
    <div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">🖨️</div>

    <img src="../img/undc.png" class="logo-superior" alt="Logo UNDC"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <img src="<?= htmlspecialchars($encabezado_img) ?>" class="encabezado-img" alt="Encabezado F7/F8">

    <h2 style="text-align:center;color:rgb(30,82,41);">
      <strong>Registro de la Consejería y Tutoría Académica <?= $es_grupal ? 'Grupal' : 'Individual' ?></strong>
    </h2>

    <h3>I. DATOS INFORMATIVOS</h3>

    <?php if ($es_grupal): ?>
    <pre style="font-size:14px;">
Escuela Profesional: <?= htmlspecialchars($s['nom_car']) ?>         Ciclo: <?= htmlspecialchars($s['ciclo']) ?>      

Turno: <?= htmlspecialchars($s['turno']) ?>                             Sección: <?= htmlspecialchars($s['seccion']) ?>


Tutor: <?= htmlspecialchars($s['nombre_docente']) ?>


Correo: <?= htmlspecialchars($s['email_doce']) ?>


Fecha de Reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> a <?= htmlspecialchars($s['horaFin']) ?>

    </pre>
    <?php else: ?>
    <pre style="font-size:14px;">
Escuela Profesional de <?= htmlspecialchars($s['nom_car']) ?> 

Estudiante: <?= htmlspecialchars($estudiantes[0]['estu'] ?? '—') ?> 

Ciclo académico: <?= htmlspecialchars($s['ciclo']) ?>            Turno: <?= htmlspecialchars($s['turno']) ?>        Sección: <?= htmlspecialchars($s['seccion']) ?>

Tutor: <?= htmlspecialchars($s['nombre_docente']) ?> 

Semestre académico: <?= htmlspecialchars($id_semestre) ?> 

Fecha de reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> 

    </pre>
    <?php endif; ?>

    <h3>II. MODALIDAD</h3>
    <p>
      <?php foreach ($tipo_sesion as $id => $label):
        $checked = ($s['tipo_sesion_id'] == $id) ? '&#x2714;' : '&#x25A1;';
        echo $checked.' '.$label.'&nbsp;&nbsp;&nbsp;';
      endforeach; ?>
    </p>

    <h3>III. DETALLES DE LA SESIÓN</h3>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
      <thead>
        <tr style="background:rgb(50,124,40);color:#fff;">
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
          <tr style="background:rgb(50,124,40);color:#fff;">
            <th style="width:30px;text-align:center;">N°</th>
            <th>Apellidos y Nombres</th>
            <th style="width:40px;text-align:center;">Asis.</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($estudiantes as $e): ?>
          <tr>
            <td style="text-align:center;"><?= $e['numero'] ?></td>
            <td style="text-align:left;"><?= htmlspecialchars($e['estu']) ?></td>
            <td style="text-align:center;"><?= $e['asistencia'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <br>
    <p style="text-align:center;font-size:.9em;">
      <em>Fecha de registro: <?= htmlspecialchars($s['fecha']) ?><br>
      <?= htmlspecialchars($s['nombre_docente']) ?><br>
      Correo: <?= htmlspecialchars($s['email_doce']) ?></em>
    </p>

    <?php if (!empty($s['evidencia_1']) || !empty($s['evidencia_2'])): ?>
      <h3>V. EVIDENCIAS</h3>

      <?php if (!empty($s['evidencia_1'])): ?>
        <div style="margin:10px 0;text-align:center;">
          <img src="<?= evidencia_url($s['evidencia_1']) ?>" alt="Evidencia 1" style="max-width:100%;height:auto;"><br>
          <span style="font-size:14px;">Evidencia 1</span>
        </div>
      <?php endif; ?>

      <?php if (!empty($s['evidencia_2'])): ?>
        <div style="margin:10px 0;text-align:center;">
          <img src="<?= evidencia_url($s['evidencia_2']) ?>" alt="Evidencia 2" style="max-width:100%;height:auto;"><br>
          <span style="font-size:14px;">Evidencia 2</span>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <footer>
      <p>Toda copia de este documento, sea del entorno virtual o del documento original en físico, es considerada “copia no controlada”.</p>
    </footer>
  </div>
  <?php
  exit; // IMPORTANTE: no continuar al fallback
}

/* ===========================================================
   RUTA 2 (fallback): múltiples sesiones por mes/carga/docente
   =========================================================== */
if (!$mes_num || !$id_cargalectiva || !$id_docente_alcance) {
  echo "<p style='color:red'>Faltan parámetros: mes=$mes_num, carga=$id_cargalectiva, docente=$id_docente_alcance.</p>";
  exit;
}

$sqlSesiones = "
  SELECT 
    s.id_sesiones_tuto,
    s.tema,
    s.compromiso_indi,
    DATE_FORMAT(s.fecha,'%d/%m/%Y') AS fecha,
    s.horaInicio,
    s.horaFin,
    s.reunion_tipo_otros,
    s.link,
    s.tipo_sesion_id,
    s.evidencia_1,
    s.evidencia_2,
    s.color,
    d.id_doce,
    d.email_doce,
    CONCAT(d.abreviatura_doce,' ',d.apepa_doce,' ',d.apema_doce,' ',d.nom_doce) AS nombre_docente,
    cl.ciclo,
    c.nom_car,
    cl.turno
  FROM tutoria_sesiones_tutoria_f78 s
  JOIN docente d        ON d.id_doce = s.id_doce
  JOIN carga_lectiva cl ON cl.id_cargalectiva = ?
  JOIN carrera c        ON c.id_car = cl.id_car
  WHERE s.id_rol = 6
    AND MONTH(s.fecha) = ?
    AND s.id_doce = ?
  ORDER BY s.fecha ASC
";
$stmtSes = $conexion->conexion->prepare($sqlSesiones);
$stmtSes->bind_param("iii", $id_cargalectiva, $mes_num, $id_docente_alcance);
$stmtSes->execute();
$resSesiones = $stmtSes->get_result();

/* Contadores / helpers para cada sesión */
$sqlCntAsign = "SELECT COUNT(*) AS total FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = ?";
$stmtCntAsign = $conexion->conexion->prepare($sqlCntAsign);

$sqlEstudiantes = "
  SELECT CONCAT(e.apepa_estu,' ',e.apema_estu,' ',e.nom_estu) AS estu,
         d.marcar_asis_estu
  FROM tutoria_detalle_sesion d
  JOIN estudiante e ON e.id_estu = d.id_estu
  WHERE d.sesiones_tutoria_id = ?
  ORDER BY e.apepa_estu, e.apema_estu, e.nom_estu
";
$stmtEst = $conexion->conexion->prepare($sqlEstudiantes);

/* ===== Render VARIAS sesiones ===== */
while ($s = $resSesiones->fetch_assoc()):
  $id_sesion = (int)$s['id_sesiones_tuto'];

  $stmtCntAsign->bind_param("i", $id_sesion);
  $stmtCntAsign->execute();
  $r = $stmtCntAsign->get_result()->fetch_assoc();
  $total_asignados = (int)($r['total'] ?? 0);
  $es_grupal = ($total_asignados > 1);

  $estudiantes = [];
  $stmtEst->bind_param("i", $id_sesion);
  $stmtEst->execute();
  $resE = $stmtEst->get_result();
  $i = 1;
  while ($e = $resE->fetch_assoc()) {
    $estudiantes[] = [
      'numero'     => $i++,
      'estu'       => $e['estu'],
      'asistencia' => ((int)$e['marcar_asis_estu'] === 1 ? 'A' : 'F')
    ];
  }

  // Encabezado por sesión en fallback:
  $encabezado_img = $es_grupal ? 'pdf/img/undc_f7.JPG' : 'pdf/img/undc_f8.JPG';
?>
<style>
  .documeto, h3, h2{ font-family: 'Courier New', monospace; font-size:16px; }
  h3{ font-size:19px; font-weight:bolder; }
  h2{ font-size:20px; font-weight:bolder; }
</style>
<div class="salto-pagina documento">
  <img src="../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img src="<?= htmlspecialchars($encabezado_img) ?>" class="encabezado-img" alt="Encabezado F7/F8">
  <h2 style="text-align:center;color:rgb(30,82,41);">
    <strong>Registro de la Consejería y Tutoría Académica <?= $es_grupal ? 'Grupal' : 'Individual' ?></strong>
  </h2>

  <h3>I. DATOS INFORMATIVOS</h3>

  <?php if ($es_grupal): ?>
  <pre style="font-size:14px;">
Escuela Profesional: <?= htmlspecialchars($s['nom_car']) ?>         Ciclo: <?= htmlspecialchars($s['ciclo']) ?>      

Turno: <?= htmlspecialchars($s['turno']) ?>


Tutor: <?= htmlspecialchars($s['nombre_docente']) ?>


Correo: <?= htmlspecialchars($s['email_doce']) ?>


Fecha de Reunión: <?= htmlspecialchars($s['fecha']) ?>          Hora: <?= htmlspecialchars($s['horaInicio']) ?> a <?= htmlspecialchars($s['horaFin']) ?>

  </pre>
  <?php else: ?>
  <pre style="font-size:14px;">
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
    <?php foreach ($tipo_sesion as $id => $label):
      $checked = ($s['tipo_sesion_id'] == $id) ? '&#x2714;' : '&#x25A1;';
      echo $checked.' '.$label.'&nbsp;&nbsp;&nbsp;';
    endforeach; ?>
  </p>

  <h3>III. DETALLES DE LA SESIÓN</h3>
  <table border="1" cellpadding="5" cellspacing="0" width="100%">
    <thead>
      <tr style="background:rgb(50,124,40);color:#fff;">
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
        <tr style="background:rgb(50,124,40);color:#fff;">
          <th style="width:30px;text-align:center;">N°</th>
          <th>Apellidos y Nombres</th>
          <th style="width:40px;text-align:center;">Asis.</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $e): ?>
        <tr>
          <td style="text-align:center;"><?= $e['numero'] ?></td>
          <td style="text-align:left;"><?= htmlspecialchars($e['estu']) ?></td>
          <td style="text-align:center;"><?= $e['asistencia'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <br>
  <p style="text-align:center;font-size:.9em;">
    <em>Fecha de registro: <?= htmlspecialchars($s['fecha']) ?><br>
    <?= htmlspecialchars($s['nombre_docente']) ?><br>
    Correo: <?= htmlspecialchars($s['email_doce']) ?></em>
  </p>

  <?php if (!empty($s['evidencia_1']) || !empty($s['evidencia_2'])): ?>
    <h3>V. EVIDENCIAS</h3>

    <?php if (!empty($s['evidencia_1'])): ?>
      <div style="margin:10px 0;text-align:center;">
        <img src="<?= evidencia_url($s['evidencia_1']) ?>" alt="Evidencia 1" style="max-width:100%;height:auto;"><br>
        <span style="font-size:14px;">Evidencia 1</span>
      </div>
    <?php endif; ?>

    <?php if (!empty($s['evidencia_2'])): ?>
      <div style="margin:10px 0;text-align:center;">
        <img src="<?= evidencia_url($s['evidencia_2']) ?>" alt="Evidencia 2" style="max-width:100%;height:auto;"><br>
        <span style="font-size:14px;">Evidencia 2</span>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <footer style="margin-top:300px;">
    <p style="text-align:center;font-size:10px;">Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”.</p>
  </footer>
</div>
<?php endwhile; ?>
