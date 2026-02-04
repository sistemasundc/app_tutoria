<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');
session_start();
require_once('../../modelo/modelo_conexion.php');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['TUTOR DE AULA', 'DIRECCION DE ESCUELA', 'SUPERVISION', 'COORDINADOR GENERAL DE TUTORIA', 'DIRECTOR DE DEPARTAMENTO ACADEMICO', 'VICEPRESIDENCIA ACADEMICA','COMITÉ - SUPERVISIÓN'])) {
  die('Acceso no autorizado');
}
$conexion = new conexion();
$conexion->conectar();


$id_semestre = $_SESSION['S_SEMESTRE'];
$id_doce = $_SESSION['S_IDUSUARIO'];
$id_plan = $_GET['id_plan'] ?? null;
if (!$id_plan) {
    die("Falta ID del plan de tutoría.");
}

// Obtener id_cargalectiva desde el id_plan (sin ambigüedad)
$sql = "SELECT 
          tp.id_cargalectiva      AS id_carga_plan,
          cl.id_cargalectiva      AS id_carga_real,
          cl.ciclo, 
          cl.id_car, 
          cl.id_semestre
        FROM tutoria_plan2 tp
        JOIN carga_lectiva cl ON cl.id_cargalectiva = tp.id_cargalectiva
        WHERE tp.id_plan_tutoria = ?";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("i", $id_plan);
$stmt->execute();
$result = $stmt->get_result();
$datosPlan = $result->fetch_assoc();
// Resolver id_cargalectiva con prioridad: GET -> último de plan_compartido -> tutoria_plan2
$id_cargalectiva = isset($_GET['id_cargalectiva']) ? (int)$_GET['id_cargalectiva'] : 0;

if ($id_cargalectiva <= 0) {
  // buscar la última carga usada para este plan (prioriza enviados)
  if ($st = $conexion->conexion->prepare("
        SELECT id_cargalectiva
        FROM tutoria_plan_compartido
        WHERE id_plan_tutoria = ?
        ORDER BY (estado_envio = 2) DESC, fecha_envio DESC, id_comp DESC
        LIMIT 1
  ")) {
    $st->bind_param("i", $id_plan);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if ($r && (int)$r['id_cargalectiva'] > 0) {
      $id_cargalectiva = (int)$r['id_cargalectiva'];
    }
    $st->close();
  }
}

if ($id_cargalectiva <= 0) {
  // último fallback: el de tutoria_plan2
  $id_cargalectiva = (int)$datosPlan['id_carga_real'];
}

$ciclo           = $datosPlan['ciclo'];
$id_car          = (int)$datosPlan['id_car'];
$id_semestre     = (int)$datosPlan['id_semestre'];

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_PE.UTF-8', 'spanish'); // agrega soporte español
$meses = [
    'January' => 'enero',    'February' => 'febrero', 'March' => 'marzo',
    'April' => 'abril',      'May' => 'mayo',         'June' => 'junio',
    'July' => 'julio',       'August' => 'agosto',    'September' => 'septiembre',
    'October' => 'octubre',  'November' => 'noviembre', 'December' => 'diciembre'
];

$fecha_actual_dt = new DateTime('now', new DateTimeZone('America/Lima'));
$nombreMes = $fecha_actual_dt->format('F'); // Nombre del mes en inglés
$dia = $fecha_actual_dt->format('d');
$anio = $fecha_actual_dt->format('Y');
$fecha_actual = "$dia de " . $meses[$nombreMes] . " de $anio";
$estado_envio_numerico = 0;
$fecha_envio_final = null;

$sqlEstado = "SELECT fecha_envio
              FROM tutoria_plan_compartido
              WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND estado_envio = 2
              LIMIT 1";
$stmt = $conexion->conexion->prepare($sqlEstado);
$stmt->bind_param("ii", $id_plan, $id_cargalectiva);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$estado_envio_numerico = $row ? 2 : 0;
$fecha_envio_final = $row['fecha_envio'] ?? null;
$fechaEnvioFormateada = '';
if (!empty($fecha_envio_final)) {
    $dt = new DateTime($fecha_envio_final, new DateTimeZone('America/Lima'));
    $fechaEnvioFormateada = $dt->format('d/m/Y h:i A');
}


$sqlCurso = "SELECT 
                cl.*, a.nom_asi, c.nom_car, s.nomsemestre,
                d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
             FROM carga_lectiva cl
             LEFT JOIN asignatura a ON a.id_asi = cl.id_asi
             LEFT JOIN carrera   c ON c.id_car = cl.id_car
             LEFT JOIN semestre  s ON s.id_semestre = cl.id_semestre
             LEFT JOIN docente   d ON d.id_doce  = cl.id_doce
             WHERE cl.id_cargalectiva = ?";

$stmtCurso = $conexion->conexion->prepare($sqlCurso);
$stmtCurso->bind_param("i", $id_cargalectiva);
$stmtCurso->execute();
$curso = $stmtCurso->get_result()->fetch_assoc();
if (!$curso) die("No se halló la carga lectiva.");

$id_doce_carga = (int)($curso['id_doce'] ?? 0);

// datos de contexto
$semestre = $curso['nomsemestre'] ?? '';
$escuela  = $curso['nom_car'] ?? '';
$ciclo    = $curso['ciclo'] ?? '';

// ======== Tutor desde tutoria_asignacion_tutoria (bloque definitivo) ========
// ======== Resolver tutor del plan (prioridad: plan_compartido -> carga_lectiva -> tutoria_asignacion_tutoria) ========
// PRE: $id_plan, $id_cargalectiva (ya resuelto: GET -> plan_compartido -> plan2), $datosPlan listo
$id_semestre_plan = (int)$datosPlan['id_semestre'];

$nombre_tutor = 'DOCENTE NO ASIGNADO';
$correo_tutor = '';
$id_doce_carga = 0;

$sqlTutor = "
SELECT x.*
FROM (
  -- 1) plan_compartido (plan + carga)
  SELECT 
    d.id_doce,
    COALESCE(d.abreviatura_doce,'') AS abrev,
    COALESCE(d.apepa_doce,'')       AS apepa,
    COALESCE(d.apema_doce,'')       AS apema,
    COALESCE(d.nom_doce,'')         AS nombres,
    COALESCE(d.email_doce,'')       AS email,
    1 AS pri_origen,
    CASE WHEN tpc.estado_envio = 2 THEN 0 ELSE 1 END AS pri_estado,
    tpc.fecha_envio AS orden_fecha,
    tpc.id_comp     AS orden_id
  FROM tutoria_plan_compartido tpc
  JOIN docente d ON d.id_doce = tpc.id_docente
  WHERE tpc.id_plan_tutoria = ? AND tpc.id_cargalectiva = ?

  UNION ALL

  -- 2) tutoria_asignacion_tutoria (carga + semestre)
  SELECT
    d.id_doce,
    COALESCE(d.abreviatura_doce,'') AS abrev,
    COALESCE(d.apepa_doce,'')       AS apepa,
    COALESCE(d.apema_doce,'')       AS apema,
    COALESCE(d.nom_doce,'')         AS nombres,
    COALESCE(d.email_doce,'')       AS email,
    2 AS pri_origen,
    0 AS pri_estado,
    tat.fecha        AS orden_fecha,
    tat.id_asignacion AS orden_id
  FROM tutoria_asignacion_tutoria tat
  JOIN docente d ON d.id_doce = tat.id_docente
  WHERE tat.id_carga = ? AND tat.id_semestre = ? AND tat.tipo_asignacion_id IN (1,2)

  UNION ALL

  -- 3) dueño de la carga_lectiva (fallback)
  SELECT
    d.id_doce,
    COALESCE(d.abreviatura_doce,'') AS abrev,
    COALESCE(d.apepa_doce,'')       AS apepa,
    COALESCE(d.apema_doce,'')       AS apema,
    COALESCE(d.nom_doce,'')         AS nombres,
    COALESCE(d.email_doce,'')       AS email,
    3 AS pri_origen,
    0 AS pri_estado,
    NULL AS orden_fecha,
    cl.id_cargalectiva AS orden_id
  FROM carga_lectiva cl
  JOIN docente d ON d.id_doce = cl.id_doce
  WHERE cl.id_cargalectiva = ?
) x
ORDER BY x.pri_origen, x.pri_estado, x.orden_fecha DESC, x.orden_id DESC
LIMIT 1
";

if ($st = $conexion->conexion->prepare($sqlTutor)) {
  // bind: plan, carga ; carga, semestre ; carga
  $st->bind_param("iiiii", $id_plan, $id_cargalectiva, $id_cargalectiva, $id_semestre_plan, $id_cargalectiva);
  $st->execute();
  $t = $st->get_result()->fetch_assoc();
  $st->close();
} else {
  die("No se pudo preparar la consulta de tutor.");
}

if ($t) {
  $id_doce_carga = (int)$t['id_doce'];
  $nombre_tutor  = trim(preg_replace('/\s+/', ' ', $t['abrev'].' '.$t['apepa'].' '.$t['apema'].' '.$t['nombres']));
  $nombre_tutor  = $nombre_tutor !== '' ? mb_strtoupper($nombre_tutor, 'UTF-8') : 'DOCENTE NO ASIGNADO';
  $correo_tutor  = $t['email'] ?? '';
}

// Debug
echo "<!-- TUTOR_DBG plan=$id_plan, carga=$id_cargalectiva, sem=$id_semestre_plan, id_doce=$id_doce_carga, nombre='$nombre_tutor', correo='$correo_tutor' -->";


// ===================== OBTENER ACTIVIDADES (ahora por DOCENTE) =====================
// Prioridad de filtros:
// 1) Por docente + carga + semestre
// 2) Por docente + semestre
// 3) Fallback: por id_plan_tutoria (compatibilidad)

$actividades = ['1' => '', '2' => '', '3' => '', '4' => '', '5' => ''];

// 1) DOCENTE + CARGA + SEMESTRE
$sqlAct = "SELECT mes, descripcion, comentario
           FROM tutoria_actividades_plan
           WHERE id_docente = ? AND id_cargalectiva = ? AND id_semestre = ?
           ORDER BY mes";
$stmtAct = $conexion->conexion->prepare($sqlAct);
$stmtAct->bind_param("iii", $id_doce_carga, $id_cargalectiva, $id_semestre_plan);
$stmtAct->execute();
$resAct = $stmtAct->get_result();

if ($resAct->num_rows === 0) {
  // 2) DOCENTE + SEMESTRE
  $stmtAct->close();
  $sqlAct = "SELECT mes, descripcion, comentario
             FROM tutoria_actividades_plan
             WHERE id_docente = ? AND id_semestre = ?
             ORDER BY mes";
  $stmtAct = $conexion->conexion->prepare($sqlAct);
  $stmtAct->bind_param("ii", $id_doce_carga, $id_semestre_plan);
  $stmtAct->execute();
  $resAct = $stmtAct->get_result();
}

if ($resAct->num_rows === 0) {
  // 3) FALLBACK: por plan (compatibilidad con registros antiguos)
  $stmtAct->close();
  $sqlAct = "SELECT mes, descripcion, comentario
             FROM tutoria_actividades_plan
             WHERE id_plan_tutoria = ?
             ORDER BY mes";
  $stmtAct = $conexion->conexion->prepare($sqlAct);
  $stmtAct->bind_param("i", $id_plan);
  $stmtAct->execute();
  $resAct = $stmtAct->get_result();
}

// Mapear resultados a los 4 meses y el comentario (mes = 5)
while ($fila = $resAct->fetch_assoc()) {
  $mes = (int)$fila['mes'];
  if ($mes >= 1 && $mes <= 4) {
    $actividades[$mes] = $fila['descripcion'] ?? '';
  } elseif ($mes === 5) {
    $actividades[5] = $fila['comentario'] ?? '';
  }
}
$stmtAct->close();
// ================================================================================>



// CONSULTA PARA MOSTRAR QUIÉN DIO CONFORMIDAD (usando datos guardados directamente)
$sqlRev = "SELECT  
              r.fecha_revision,
              r.nombre_director,
              r.id_car,
              u.cor_inst
           FROM tutoria_revision_director r
           INNER JOIN tutoria_usuario u ON u.id_usuario = r.id_director
           WHERE r.id_plan_tutoria = ? 
             AND r.estado_revision = 'CONFORME'
           ORDER BY r.fecha_revision DESC
           LIMIT 1";

$stmtRev = $conexion->conexion->prepare($sqlRev);
$stmtRev->bind_param("i", $id_plan);
$stmtRev->execute();
$resRev = $stmtRev->get_result();
$revision = $resRev->fetch_assoc();

// CONSULTAS DIAGNÓSTICO
// CONSULTAS DIAGNÓSTICO 
$diagnostico = [
  'desaprobados' => 0,
  'inasistencias' => 0,
  'disfuncionales' => 0,
  'conadis' => 0,
  'sisfoh' => 0,
  'apoyo_financiero' => 0,
  'salud' => 0,
  'nee' => 12, // valor estático de ejemplo
  'desercion' => '2%' // valor estático de ejemplo
];

// 1. Desaprobados
$query1 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM (
      SELECT ae.id_estu 
      FROM asignacion_estudiante ae
      WHERE ae.vez_a >= 2 
        AND ae.id_semestre = 32 
        AND ae.id_car = $id_car
      GROUP BY ae.id_estu
  ) AS sub
");
$diagnostico['desaprobados'] = $query1 ? $query1->fetch_assoc()['total'] : 0;

// 2. Inasistencias
$query2 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM asignacion_estudiante ae
  WHERE ae.id_semestre = 32
    AND ae.por_f BETWEEN 18 AND 25 
    AND ae.id_car = $id_car
");
$diagnostico['inasistencias'] = $query2 ? $query2->fetch_assoc()['total'] : 0;

// 3. Disfuncionales
$query3 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM fs_datos fd
  INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
  WHERE fd.ID_SEMESTRE = 30
    AND UPPER(TRIM(fd.DD_CON_AC)) != 'AMBOS PADRES'
    AND ae.id_car = $id_car
");
$diagnostico['disfuncionales'] = $query3 ? $query3->fetch_assoc()['total'] : 0;

// 4. CONADIS
$query4 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM fs_datos fd
  INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
  WHERE fd.ID_SEMESTRE = 32
    AND UPPER(TRIM(fd.DS_DIS_CARNET)) = 'SI'
    AND ae.id_car = $id_car
");
$diagnostico['conadis'] = $query4 ? $query4->fetch_assoc()['total'] : 0;

// 5. SISFOH
$query5 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM fs_datos fd
  INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
  WHERE fd.ID_SEMESTRE = 30 
    AND UPPER(TRIM(fd.DV_SISFOH)) = 'SI'
    AND ae.id_car = $id_car
");
$diagnostico['sisfoh'] = $query5 ? $query5->fetch_assoc()['total'] : 0;

// 6. Apoyo financiero
$query6 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM fs_datos fd
  INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
  WHERE fd.ID_SEMESTRE = 30
    AND UPPER(TRIM(fd.SE_TRAB)) = 'SI'
    AND ae.id_car = $id_car
");
$diagnostico['apoyo_financiero'] = $query6 ? $query6->fetch_assoc()['total'] : 0;

// 7. Salud crónica
$query7 = $conexion->conexion->query("
  SELECT COUNT(*) AS total 
  FROM fs_datos fd
  INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
  WHERE fd.ID_SEMESTRE = 32
    AND UPPER(TRIM(fd.DS_PAD_ENF)) = 'SI'
    AND ae.id_car = $id_car
");
$diagnostico['salud'] = $query7 ? $query7->fetch_assoc()['total'] : 0;


$anio_actual = date("Y");
$dia_actual = date("d");
setlocale(LC_TIME, 'es_ES.UTF-8');
$mes_actual = strftime("%B");

if (empty($docentes) || (count($docentes) === 1 && trim($docentes[0]) === '')) {
    $docentes = [$nombre_tutor !== '' ? $nombre_tutor : 'DOCENTE NO REGISTRADO'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>Plan de Tutoría</title>
  <style>
  body {
    font-family: 'Times New Roman', serif;
    margin: 0;
    padding: 0;
    background: #f0f0f0;
  }

  @media print {
    .documento {
      page-break-after: always;
      page-break-inside: avoid;
    }

    .logo-superior, .encabezado-img {
      position: absolute !important;
      width: auto;
    }
    #btn-imprimir {
      display: none !important;
    }
    .celda-activa {
    background-color: #50aaff !important; /* o el color que uses */
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    }
  }

  .documento {
    width: 210mm;
    min-height: 297mm;
    padding: 20mm;
    margin: auto;
    background: white;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
    box-sizing: border-box;
    page-break-after: always;
    position: relative;
  }

  .no-break {
    page-break-after: avoid;
    page-break-inside: avoid;
  }

  .logo-superior {
    position: absolute;
    top: 40px;
    left: 40px;
    width: 130px;
    max-width: 100%;
  }

  .encabezado-img {
    position: absolute;
    top: 40px;
    right: 40px;
    width: 320px;
    max-width: 100%;
  }

  .encabezado-img2 {
    position: absolute;
    top: 50px;
    right: 20px;
    width: 360px;
    max-width: 100%;
  }
  .footer-img{
    position: absolute;
    top: -70px;
    right: 5px;
    width: 600px;
    max-width: 100%;
  }

  h4, h5 {
    text-align: start;
    margin-bottom: 10px;
  }

  h1, h2, h3 {
    text-align: center;
    margin-bottom: 10px;
  }

  p, ul, li {
    font-size: 14px;
    text-align: justify;
    line-height: 1.6;
  }

  ul {
    padding-left: 30px;
  }

  .tabla-actividades,
  .tabla-cronograma {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 13px;
  }

  .tabla-actividades th,
  .tabla-actividades td,
  .tabla-cronograma th,
  .tabla-cronograma td {
    border: 1px solid black; /* ← líneas horizontales y verticales */
    padding: 6px;
    text-align: center;
    vertical-align: top;
    vertical-align: middle;
  }

  .tabla-cronograma th {
    background-color:rgb(255, 255, 255);
    font-weight: bold;
  }
  .tabla-cronograma {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 13px;
    table-layout: fixed;
    border: 2px solid black;
  }

  .text-center {
    text-align: center;
  }

  .text-end {
    text-align: right;
  }

  .firma-docente {
    text-align: center;
    margin-top: 40px;
    font-weight: bold;
  }

  .seccion {
    margin-top: 30px;
  }

  .titulo-seccion {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 10px;
  }

  .indice-lista {
    list-style: none;
    padding-left: 0;
  }

  .indice-lista li {
    margin-bottom: 4px;
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dotted #000;
  }

  .indice-lista span {
    display: inline-block;
    min-width: 20px;
    text-align: right;
  }

  .documento h1,
  .documento h3,
  .documento h4 {
    margin-top: 50px;
  }

  /* Cronograma: columna actividad */
  .actividad-nombre {
    width: 300px;
    text-align: left;
    vertical-align: top;
    padding: 6px;
  }

  .actividad-texto {
    white-space: pre-line;
    font-size: 13px;
    font-weight: normal; /* <-- ya no negrita */
    line-height: 1.5;
  }


  .col-actividad {
    width: 35%;
    background-color:rgb(92, 174, 241);
    text-align: left;
    padding-left: 10px;
  }

  .col-semana {
    width: 30px;
    font-size: 11px;
    text-align: center;
  }

  /* Botón imprimir */
  #btn-imprimir {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #007bff;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 9999;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    transition: background-color 0.3s ease;
  }

  #btn-imprimir:hover {
    background-color: #0056b3;
  }

  @media screen and (max-width: 768px) {
    .documento {
      width: 100%;
      padding: 10mm;
    }

    .logo-superior,
    .encabezado-img {
      position: static !important;
      display: block;
      margin: 0 auto 15px;
      width: 80%;
      max-width: 280px;
    }

    .documento h1,
    .documento h3,
    .documento h4 {
      font-size: 16px;
    }

    p, ul, li {
      font-size: 13px;
    }

    .tabla-actividades,
    .tabla-cronograma {
      font-size: 12px;
    }

    .col-actividad {
      width: 150px;
    }

    .col-semana {
      font-size: 25px;
    }
  }
  .celda-activa {
  background-color: rgb(92, 174, 241) !important;
  color: white;
  }
  @page {
    size: A4;
    margin: 0;
  }
  .celda-activa {
    background-color: rgb(92, 174, 241);  /* azul institucional */
    color: white;
  }
</style>


</head>
<body>
<!--------------------- PAGINA 1------------------------ -->
<!-- Botón flotante de impresión -->
  <div id="btn-imprimir" onclick="window.print()" title="Imprimir">
    🖨️
  </div>
<div class="documento">
  <!-- Encabezado con logos -->
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img src="../../img/encabezado1.PNG" class="encabezado-img" alt="Encabezado UNDC">

  <!-- Contenido Central -->
  <h1><strong>UNIVERSIDAD NACIONAL DE CAÑETE</strong></h1>
  <h3><strong>VICERRECTORADO ACADÉMICO</strong></h3>
  <h4 style="text-align: center;"><strong>ESCUELA PROFESIONAL DE <?= htmlspecialchars($escuela) ?></strong></h4>

  <!-- Logo Central -->
  <div style="margin-top: 50px; text-align: center;">
    <img src="../../img/logo.png" alt="Logo central" style="width: 250px;">
  </div>

  <!-- Título del documento -->
  <h1 style="margin-top: 50px;"><strong>PLAN DE TUTORÍA</strong></h1>

  <!-- Bloque inferior: docentes, ciclo, año -->
  <div class="bloque-inferior mt-4" style="text-align: center; margin-top: 30px;">
  <h4 style="margin:0; padding:0; text-align:center;"><strong>Tutor(a):</strong></h4>

  <?php if (!empty($nombre_tutor) && $nombre_tutor !== 'DOCENTE NO ASIGNADO'): ?>
    <div style="width:260px; margin:6px auto 0; text-align:center; font-size:14px;">
      <strong><?= htmlspecialchars($nombre_tutor) ?></strong>
    </div>
  <?php else: ?>
    <div style="width:260px; margin:6px auto 0; text-align:center; font-size:14px;">
      <strong>CARGANDO......</strong>
    </div>
  <?php endif; ?>
  <h4 style="margin: 8px 0; line-height: 1.2; text-align: center;"><strong>Ciclo Académico:</strong> <?= htmlspecialchars($ciclo) ?></h4>
  <h4 style="margin: 8px 0; line-height: 1.2; text-align: center;"><strong>Escuela Profesional de :</strong> <?= htmlspecialchars($escuela) ?></h4>
  <h4 style="margin: 28px 0; line-height: 1.2; text-align: center;"><strong>Cañete, Perú</strong></h4>
  <h4 style="margin: 8px 0; line-height: 1.2; text-align: center;"><strong><?= $anio_actual ?></strong></h4>
</div>
  <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>
<!--------------------- PAGINA 2------------------------ -->
<div class="documento">
    <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
    <img src="../../img/encabezado2.PNG" class="encabezado-img2" alt="Encabezado">
    <br></br>
    <br></br>
    <h2>CONTENIDO</h2>

    <ul class="contenido-lista list-unstyled">
      <li><strong>CONTENIDO</strong><span class="page-num"></span></li>
      <li><strong>1. INTRODUCCIÓN</strong><span class="page-num"></span></li>
      <li><strong>2. DIAGNÓSTICO</strong><span class="page-num"></span></li>
      <li class="subitem">2.1 Detección del estudiante en riesgo académico<span class="page-num"></span></li>
      <li class="subitem">2.2 Detección de estudiantes con problemas familiares, de salud, económicos u otros<span class="page-num"></span></li>
      <li class="subitem">2.3 Detección de estudiantes con Necesidades Educativas Especiales (NEE)<span class="page-num"></span></li>
      <li class="subitem">2.4 Detección por condición de deserción (de corresponder)<span class="page-num"></span></li>
      <li class="subitem">2.5 Otros (que considere por la naturaleza de la Escuela Profesional)<span class="page-num"></span></li>
      <li><strong>3. OBJETIVOS</strong><span class="page-num"></span></li>
      <li class="subitem">3.1. OBJETIVO GENERAL<span class="page-num"></span></li>
      <li class="subitem">3.2. OBJETIVOS ESPECÍFICOS<span class="page-num"></span></li>
      <li><strong>4. FECHA Y HORARIO</strong><span class="page-num"></span></li>
      <li><strong>5. ESTRATEGIAS METODOLÓGICAS</strong><span class="page-num"></span></li>
      <li class="subitem">5.1. ACTIVIDADES GRUPALES<span class="page-num"></span></li>
      <li class="subitem">5.2. ACTIVIDADES INDIVIDUALES<span class="page-num"></span></li>
      <li><strong>6. ACTIVIDADES</strong><span class="page-num"></span></li>
      <li><strong>ANEXOS</strong><span class="page-num"></span></li>
      <li class="subitem">ANEXO N° 1: HOJA DE REFERENCIA Y CONTRA-REFERENCIA<span class="page-num"></span></li>
      <li class="subitem">ANEXO N° 2: REGISTRO DE LA CONSEJERÍA Y TUTORÍA ACADÉMICA GRUPAL<span class="page-num"></span></li>
      <li class="subitem">ANEXO N° 3: REGISTRO DE LA CONSEJERÍA Y TUTORÍA ACADÉMICA INDIVIDUAL<span class="page-num"></span></li>
    </ul>
    <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
  </div>
<!---------------------- PAGINA 3------------------------ -->
<div class="documento">
    <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
    <img src="../../img/encabezado2.PNG" class="encabezado-img2" alt="Encabezado">
    <br></br>
    <br></br>

    <h3><strong>1. INTRODUCCIÓN</strong></h3>
    <p>El presente Plan de Tutoría del semestre académico <?= htmlspecialchars($semestre) ?>, dirigido a los estudiantes del ciclo <?= htmlspecialchars($ciclo) ?>, tiene como objetivo primordial la formación integral de los estudiantes, 
    abordando tanto sus aspectos académicos como personales. Para lograrlo, se han 
    diseñado estrategias operativas que promueven un trabajo tanto a nivel grupal como 
    individual, con el propósito de brindar una atención personalizada y efectiva. La tutoría 
    se concibe como un proceso sistemático de orientación y acompañamiento, en el cual se 
    establece una relación cercana entre el tutor y el estudiante o grupo de estudiantes, 
    durante todo su proceso de formación profesional. </p>
        <p>El Plan de Tutoría se basa en el modelo educativo de la Universidad Nacional de Cañete, 
    que considera al ser humano en su totalidad y busca su desarrollo integral. Se 
    fundamenta en una perspectiva filosófica que abarca dimensiones ontológicas, 
    epistemológicas y paradigmáticas, reconociendo la interacción del individuo con su 
    entorno y su búsqueda de conocimiento. Se apoya en bases teóricas y conceptuales que 
    comprenden la naturaleza humana, la esencia de la condición humana y la interacción 
    con el mundo, complementadas con fundamentos antropológicos, sociológicos, 
    psicológicos y pedagógicos. Este enfoque se refleja en el programa de tutoría, diseñado 
    para promover el desarrollo integral de los estudiantes y contribuir a su éxito académico 
    y profesional. </p>
    <p>La tutoría se fundamenta en los ejes estratégicos de la universidad, como la educación 
    centrada en la persona, basada en competencias, la investigación e innovación, la 
    responsabilidad social y la internacionalización. Busca alinear sus acciones con estos 
    ejes, promoviendo la participación activa de los estudiantes, desarrollando habilidades 
    relevantes para su desarrollo personal y profesional, fomentando la investigación y la 
    responsabilidad social, y preparándolos para un entorno globalizado. De esta manera, 
    contribuye a la consecución de los objetivos institucionales y al cumplimiento de la misión 
    y visión de la universidad. </p>

    <h3><strong>2. DIAGNÓSTICO</strong></h3>
    <p>
      El <?= htmlspecialchars($ciclo) ?>
      ciclo académico, de la Escuela Profesional de <?= htmlspecialchars($escuela) ?>
      se inicia bajo las observaciones detectadas desde el I - X ciclo:
    </p>
    <p>(I ciclo)</p>
    <ul>
      <li>45 estudiantes que ingresaron con bajas calificaciones en el examen de admisión 2025.</li>
    </ul>
    <p>(otros)</p>
    <ul style="text-align: left;">
      <li><?= $diagnostico['desaprobados'] ?> Estudiantes desaprobados en dos o más veces una misma asignatura durante el semestre 2025_I.</li>
    </ul>
    <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
  </div>
<!---------------------- PAGINA 4 ------------------------ -->
<div class="documento">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
    <br></br>
    <br></br>
    <ul style="text-align: left;">
      
      <li><?= $diagnostico['inasistencias'] ?> Estudiantes con alto porcentaje de inasistencias (entre 18% y 25%) durante el semestre 2025_I.</li>
      
    </ul>

    <h4 class="text-start"><strong>2.2 Detección de estudiantes con problemas familiares, de salud, económicos u otros matriculados durante el semestre 2025_I.</strong></h4>
    <p class="text-start">Los factores que ayudan a la detección son:</p>
    <ul class="text-start">
      <li><?= $diagnostico['disfuncionales'] ?> Estudiantes que provienen de familias disfuncionales.</li>
      <li><?= $diagnostico['conadis'] ?> Estudiantes con discapacidad (CONADIS).</li>
      <li><?= $diagnostico['sisfoh'] ?> Estudiantes con bajos recursos económicos (SISFOH).</li>
      <li><?= $diagnostico['apoyo_financiero'] ?> Estudiantes con carencia de apoyo financiero para sus estudios.</li>
      <li><?= $diagnostico['salud'] ?> Estudiantes con enfermedades crónicas o condiciones de salud que afectan su desempeño.</li>
    </ul>

    <h4 class="text-start"><strong>2.3 Detección de estudiantes con Necesidades Educativas Especiales (NEE) matriculados durante el semestre 2025_I.</strong></h4>
    <ul style="text-align: left;">
      <li><?= $diagnostico['nee'] ?> Estudiantes con Necesidades Educativas Especiales (NEE).</li>
    </ul>

    <h4 class="text-start"><strong>2.4 Detección de porcentaje de deserción de estudiantes por curso durante el semestre 2025_I.</strong></h4>
    <ul style="text-align: left;">
      <li><?= $diagnostico['desercion'] ?> De deserción de estudiantes que se matricularon y permanecen en el mismo ciclo académico por curso.</li>
    </ul>
    <h4 class="text-start"><strong>2.5 Otros (que considere por la naturaleza de la Escuela Profesional)</strong></h4>
    <p class="text-start"><em>(El docente tutor deberá anexar los datos correspondientes)</em></p>
    <p class="text-start"><?= nl2br(htmlspecialchars($actividades[5])) ?></p>
    <h3 class="text-start mt-4"><strong>3. OBJETIVOS</strong></h3>
    <h5 class="text-start"><strong>3.1 OBJETIVO GENERAL</strong></h5>
    <p style="text-align:justify;">
      Fortalecer el acompañamiento académico, personal y profesional de los estudiantes del ciclo <?= htmlspecialchars($ciclo) ?>
      de la Escuela Profesional de <?= htmlspecialchars($escuela) ?>,
      mediante la estrecha colaboración con el Tutor de Aula;
      con el fin de mejorar su rendimiento académico, promover su desarrollo integral y contribuir a su éxito educativo y profesional.
    </p>
    <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>
<!---------------------- PAGINA 5 ------------------------ -->

<div class="documento">
 <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
    <br></br>

  <h5 class="text-start mt-4"><strong>3.2 OBJETIVOS ESPECÍFICOS</strong></h5>
  <ul class="text-start">
    <li>Facilitar la integración y adaptación de los estudiantes a la vida universitaria, apoyando su desarrollo académico y personal.</li>
    <li>Implementar asesorías individuales y grupales de manera eficiente, derivando a especialistas cuando sea necesario.</li>
    <li>Utilizar datos sobre rendimiento académico, deserción y asistencia para mejorar las acciones de tutoría.</li>
    <li>Evaluar periódicamente el programa de tutoría, elaborando informes detallados y proponiendo mejoras para el avance educativo y profesional de los estudiantes.</li>
  </ul>

  <h4 class="text-start mt-4">4. FECHA Y HORARIO</h4>
  <p class="text-start">La tutoría individual o grupal se realizará de acuerdo al PIT (Plan Individual de Trabajo) y/o en coordinación con el/los estudiante/s.</p>

  <h4 class="text-start mt-4">5. ESTRATEGIAS METODOLÓGICAS</h4>
  <p class="text-start">
    Las estrategias metodológicas que permitirán alcanzar los objetivos establecidos en el presente Plan están establecidas en dos tipos de intervención sobre los estudiantes:
  </p>

  <h5 class="text-start mt-3">5.1. ACTIVIDADES GRUPALES</h5>
  <p class="text-start">
    Esta actividad es desarrollada en el aula por el docente tutor. Se implementarán talleres de identificación de los factores de riesgo y de socialización de contenidos prácticos que permitan reducir los factores de riesgo previamente identificados, a través de un diagnóstico realizado por el Coordinador de la Escuela Profesional.
  </p>
  <p class="text-start">
    Para cada sesión de tutoría grupal realizada por el docente tutor, se generará el registro F-M01-04-VRA-007 "Registro de la Consejería y Tutoría Académica Grupal", donde se registrarán los estudiantes que participaron de la sesión.
  </p>
  <p class="text-start">
    Sea el caso de intervención si se requiere derivar al alumno al servicio de orientación y bienestar universitario u otro servicio de atención especializada, se utilizará el Registro F-M01-04-VRA-006 "Hoja de referencia y Contrarreferencia".
  </p>

  <h5 class="text-start mt-3">5.2. ACTIVIDADES INDIVIDUALES</h5>
  <p class="text-start">
    Son acciones tutoriales del docente tutor en beneficio directo y personalizado del estudiante universitario. Estas acciones se darán a partir de la identificación de las situaciones problemáticas particulares de los estudiantes: Aspergura deserción, problemas de salud física y mental, problemas económicos y sociales, etc. y el seguimiento de los casos derivados a la Dirección de Bienestar Universitario.
  </p>
  <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>
<!---------------------- PAGINA 6 ------------------------ -->
<div class="documento">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img  style="width: 380px;"  src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
     <br></br>
    <br></br>

<p > Para cada sesión de tutoría individual llevada a cabo por el docente tutor, se completará 
el registro F-M01.04-VRA-008 "Registro de la Consejería y Tutoría Académica Individual", 
en el cual se detallará el servicio de tutoría proporcionado al estudiante.</p>
  <p>Sea el caso de intervención si se requiere derivar al alumno al servicio de orientación y 
asesoría por parte del psicopedagogo y/o personal especializado, se utilizará el Registro 
F-M01.04-VRA-006 “Hoja de referencia y Contrarreferencia”. </p>
  <h4 class="text-start mt-5">6. ACTIVIDADES</h4>
  <p>
    En el Artículo 11 del Reglamento General de Tutoría se establecen los lineamientos mínimos del Programa de 
Tutoría Universitaria y, que todo Tutor asignado debe considerar entre las acciones programadas en el Plan de Trabajo. 
Asimismo, debe incorporar otras actividades académicas acorde al diagnóstico realizado o a las problemáticas identificadas a nivel institucional. 
El tutor solo evidenciará los temas del ciclo que le corresponde.
  </p>

    <table class="table table-bordered tabla-actividades">
        <thead><tr><th>Mes</th><th>Actividad</th></tr></thead>
        <tbody >
<!--             <tr><td>Abril</td><td style="text-align: start;">?= nl2br(htmlspecialchars($actividades[1])) ?></td></tr>
            <tr><td>Mayo</td><td style="text-align: start;">?= nl2br(htmlspecialchars($actividades[2])) ?></td></tr>
            <tr><td>Junio</td><td style="text-align: start;">?= nl2br(htmlspecialchars($actividades[3])) ?></td></tr>
            <tr><td>Julio</td><td style="text-align: start;">?= nl2br(htmlspecialchars($actividades[4])) ?></td></tr> -->
            <tr><td>Septiembre</td><td style="text-align: start;"><?= nl2br(htmlspecialchars($actividades[1])) ?></td></tr>
            <tr><td>Octubre</td><td style="text-align: start;"><?= nl2br(htmlspecialchars($actividades[2])) ?></td></tr>
            <tr><td>Noviembre</td><td style="text-align: start;"><?= nl2br(htmlspecialchars($actividades[3])) ?></td></tr>
            <tr><td>Diciembre</td><td style="text-align: start;"><?= nl2br(htmlspecialchars($actividades[4])) ?></td></tr>
        </tbody>
    </table>
    <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>
<!----------- PAGINA 8: Cronograma estructurado ---------------->
<div class="documento"> 
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img src="../../img/encabezado2.PNG" class="encabezado-img" style="width: 320px;" alt="Encabezado">
  
  <h3 class="text-center mt-5"><strong>7. CRONOGRAMA DE ACTIVIDADES</strong></h3>

  <div class="table-wrapper-vertical">
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 13px; border: 1px solid black;" class="table table-cronograma" id="tabla-cronograma">
      <thead>
        <tr>
          <th rowspan="2" class="col-actividad" style="border: 1px solid black; text-align: left; padding: 5px;">ACTIVIDADES POR MES</th>
<!--           <th colspan="4" style="border: 1px solid black;">ABRIL</th>
          <th colspan="4" style="border: 1px solid black;">MAYO</th>
          <th colspan="4" style="border: 1px solid black;">JUNIO</th>
          <th colspan="4" style="border: 1px solid black;">JULIO</th>
          <th colspan="4" style="border: 1px solid black;">AGOSTO</th> -->
          <th colspan="4" style="border: 1px solid black;">SEPTIEMBRE</th>
          <th colspan="4" style="border: 1px solid black;">OCTUBRE</th>
          <th colspan="4" style="border: 1px solid black;">NOVIEMBRE</th>
          <th colspan="4" style="border: 1px solid black;">DICIEMBRE</th>

        </tr>
        <tr>
          <?php for ($m = 1; $m <= 4; $m++): ?>
            <?php for ($s = 1; $s <= 4; $s++): ?>
              <th style="border: 1px solid black;"><?= $s ?></th>
            <?php endfor; ?>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($fila = 1; $fila <= 4; $fila++): ?>
          <tr id="cronograma-fila-<?= $fila ?>">
          <td class="actividad-nombre" style="border: 1px solid black; padding: 4px; text-align: left;
            <?php if ($fila < 4): ?> border-bottom: 2px solid black; <?php endif; ?>">
            <div class="actividad-texto" style="line-height: 1.2; padding: 2px 0;">
              <?= nl2br(htmlspecialchars($actividades[$fila])) ?>
            </div>
          </td>
            <?php for ($w = 1; $w <= 16; $w++): ?>
              <td style="border: 1px solid black; height: 30px;
                <?php if ($fila < 4): ?> border-bottom: 2px solid black; <?php endif; ?>"
                class="semana-<?= $w ?> semana-celda"></td>
            <?php endfor; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
  <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>
<!-- -----------------------PAGINA 9: --------------------------------->
<div class="documento">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
     <br></br>
  <h4 class="mt-4"><strong>8. EVALUACIÓN Y SEGUIMIENTO</strong></h4>
  <p>La evaluación será permanente y entre los indicadores que nos apoyarán podemos mencionar:</p>

  <ul>
    <li><strong>I-M01.04-009</strong> Porcentaje de estudiantes que participan activamente en las actividades de tutoría grupal, incluyendo reuniones, eventos y talleres organizados por el programa.</li>
    <li><strong>I-M01.04-010</strong> Porcentaje de estudiantes que participan activamente en las sesiones de tutoría individual.</li>
  </ul>

  <br><br>
  <div class="text-end" style="text-align: right; margin-right: 50px;">
 <?php
    date_default_timezone_set('America/Lima');

    $meses = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
        '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
        '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
        '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];

    $fechaFinalMostrar = '';

    if (!empty($fecha_envio_final)) {
        $dt_envio = new DateTime($fecha_envio_final);
        $dia = $dt_envio->format('j');
        $mes = $meses[$dt_envio->format('m')];
        $anio = $dt_envio->format('Y');
        $fechaFinalMostrar = "$dia de $mes de $anio";
    }
  ?>

    <?php if (!empty($fechaFinalMostrar)): ?>
      <div style="text-align: right; margin-right: 50px;">
        Cañete, <?= $fechaFinalMostrar ?>
      </div>
    <?php endif; ?>
  </div>

  <?php 
    
echo '<div style="margin-top: 60px; display: flex; justify-content: center; flex-wrap: wrap; gap: 100px;">';

$fechaEnvioFormateada = '';
if (!empty($fecha_envio_final)) {
    $dt = new DateTime($fecha_envio_final, new DateTimeZone('America/Lima'));
    $fechaEnvioFormateada = $dt->format('d/m/Y h:i A');
}

echo "<div style='text-align: center; width: 260px; font-size: 14px;'>";
if ($fechaEnvioFormateada) {
    echo "<br><em><strong>Enviado:</strong> $fechaEnvioFormateada</em><br>";
}
echo "<strong>".(
  ($nombre_tutor !== '' && $nombre_tutor !== 'DOCENTE NO ASIGNADO') 
  ? $nombre_tutor 
  : 'DOCENTE NO ASIGNADO'
)."</strong><br>";
if (!empty($correo_tutor)) {
    echo "Correo: " . htmlspecialchars($correo_tutor);
}
echo "</div>";

echo '</div>';

echo "<div style='text-align: center; margin-top: 30px;'>
        <strong>Tutor del ciclo:</strong> " . htmlspecialchars($ciclo) . "<br>
        <strong>Escuela Profesional de:</strong> " . htmlspecialchars($escuela) . "
      </div>";

  ?>
  <?php if ($revision): ?>
  <div style="margin-top: 20px; font-size: 14px; text-align: center;">
      <div><strong><em>Conforme:</em></strong> <?= date("d/m/Y H:i:s", strtotime($revision['fecha_revision'])) ?></div>
      <div><?= htmlspecialchars($revision['nombre_director']) ?></div>
      <div><strong>Correo:</strong> <?= htmlspecialchars($revision['cor_inst']) ?></div>
  </div>
  <?php endif; ?>

  <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>

<!-- Página 10: Anexo 1 -->
<div class="documento" style="page-break-after: always;">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
     <br></br>
  <?php include '../../pdf_ge/referencia_preview.php'; ?>
</div>

<!-- Página 11: Anexo 1 -->
<div class="documento" style="page-break-after: always;">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
     <br></br>
  <?php include '../../pdf_ge/contrareferencia_preview.php'; ?>
   <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>

<!-- Página 12: Anexo 2 -->
<div class="documento" style="page-break-after: always;">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
     <br></br>
  <?php include '../../pdf_ge/regtutoriagrupal_preview.php'; ?>
   <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC">
</div>
<!-- Página 13: Anexo 3 -->
<div class="documento">
  <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
  <img style="width: 380px;" src="../../img/encabezado2.PNG" class="encabezado-img" alt="Encabezado">
     <br></br>
  <?php include '../../pdf_ge/regtutoriaindiv_preview.php'; ?>

   <img src="../../img/footer_plan_tutoria.PNG" class="footer-img" alt="Footer UNDC" style="display: block; margin: 30px auto 0;">
</div>

</body>
<script>
// Pintado fijo de cronograma (4 semanas por actividad)
document.addEventListener("DOMContentLoaded", function () {
  const filas = document.querySelectorAll("#tabla-cronograma tbody tr");

  filas.forEach((fila, index) => {
    const inicioSemana = 1 + (index * 4); // Empieza en semana 3, luego 7, 11...
    const finSemana = inicioSemana + 3;   // Pinta 4 semanas

    for (let s = inicioSemana; s <= finSemana; s++) {
      const celda = fila.querySelector(`.semana-${s}`);
      if (celda) {
        celda.classList.add("celda-activa");
      }
    }
  });
});

</script>

</html>
