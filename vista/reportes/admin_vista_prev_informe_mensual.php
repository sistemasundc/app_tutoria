<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*––– 1. SESIÓN –––––––––––––––––––––––––––––––––––––––––––––*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*––– 2. SEGURIDAD ––––––––––––––––––––––––––––––––––––––––––*/
if (
    !isset($_SESSION['S_IDUSUARIO']) ||
    ($_SESSION['S_ROL'] ?? '') !== 'COORDINADOR GENERAL DE TUTORIA'
) {
    die('Acceso denegado');
}

/*––– 3. CONEXIÓN –––––––––––––––––––––––––––––––––––––––––––*/
require_once __DIR__ . '/../../modelo/modelo_conexion.php';
$conexion = new conexion();
$conexion->conectar();
date_default_timezone_set('America/Lima');

/*––– 4. PARÁMETROS ––––––––––––––––––––––––––––––––––––––––*/
$mes_url = strtolower(trim($_GET['mes'] ?? ''));        // ej: mayo
$mapMes  = [
    'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,
    'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
];
$anio_actual  = date('Y');
$meses = [
  'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
  'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
  'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
  'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
];

$fecha_hoy = date('d') . ' de ' . $meses[date('F')] . ' de ' . date('Y');

/*–––– 5. OBTENER INFORME –––––––––––––––––––––––––––––––––––*/
$sql = "SELECT * FROM tutoria_informe_resultados_coordinador_general
        WHERE mes_informe = ?
        ORDER BY id_informe DESC LIMIT 1";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param('s', $mes_url);
$stmt->execute();
$informe = $stmt->get_result()->fetch_assoc();

// 6. VARIABLES PARA LA VISTA
$formato_informe       = $informe['numero_informe'] ?? '';
$nombre_vicepresidente = $informe['para_vpa'] ?? '';
$nombre_coordinador    = $informe['de_coordinador'] ?? '';
$asunto                = $informe['asunto'] ?? '';
$fecha_actual          = date('d \d\e F \d\e\l Y'); // puedes personalizarlo si quieres otro formato
$no_lectivas           = $informe['no_lectivas'] ?? '';

if (!$informe) {
    echo "<div style='padding:20px; font-family:Arial; color:#a94442;
           background:#f2dede; border:1px solid #ebccd1;'>
            No se encontró un informe registrado para el mes de
           <strong>".htmlspecialchars($mes_url)."</strong>.
          </div>";
    exit;
}

/*–––– 6. VARIABLES PARA LA VISTA –––––––––––––––––––––––––––*/
$numero_informe = $informe['numero_informe'];
$asunto         = $informe['asunto'];
$para_vpa       = strtoupper($informe['para_vpa']);
$de_coord       = strtoupper($informe['de_coordinador']);
$no_lectivas    = $informe['no_lectivas'] ?? '';
$fecha_registro = date('d/m/Y', strtotime($informe['fecha_registro']));

/*–––– 7. DERIVADOS –––––––––––––––––––––––––––*/
  

// -------------------------GRAFICOS 8. PORCENTAJES DE PARICIPACION DE ESTUDIANTES
$id_semestre = $_SESSION['S_SEMESTRE'];
$labelsMeses = ['Noviembre'];
$datosPorCarrera = [];

$conexion = new conexion();
$cn = $conexion->conectar();

// Consulta todas las carreras
$sqlCarreras = "SELECT id_car, nom_car FROM carrera";
$resCarreras = $cn->query($sqlCarreras);
while ($car = $resCarreras->fetch_assoc()) {
    $id_car = $car['id_car'];
    $nombre_escuela = strtoupper($car['nom_car']);

    // Total asignados por carrera
    $stmtTotal = $cn->prepare("SELECT COUNT(DISTINCT id_estudiante) AS total 
        FROM tutoria_asignacion_tutoria 
        WHERE id_semestre = ? 
        AND id_docente IN (
            SELECT id_doce FROM carga_lectiva WHERE id_car = ? AND id_semestre = ?
        )");
    $stmtTotal->bind_param("iii", $id_semestre, $id_car, $id_semestre);
    $stmtTotal->execute();
    $resTotal = $stmtTotal->get_result();
    $totalEstudiantes = $resTotal->fetch_assoc()['total'] ?: 1;

    $grupal = [];
    $individual = [];
/* 
    for ($m = 4; $m <= 8; $m++) { */
    for ($m = 9; $m <= 12; $m++) {
        // GRUPAL
        $stmtG = $cn->prepare("SELECT COUNT(DISTINCT d.id_estu) AS total 
            FROM tutoria_detalle_sesion d
            INNER JOIN tutoria_sesiones_tutoria_f78 s ON s.id_sesiones_tuto = d.sesiones_tutoria_id
            INNER JOIN tutoria_asignacion_tutoria a ON d.asignacion_id = a.id_asignacion
            INNER JOIN carga_lectiva cl ON a.id_docente = cl.id_doce
            WHERE d.marcar_asis_estu = 1
            AND s.id_rol = 6
            AND cl.id_car = ?
            AND MONTH(s.fecha) = ?
            AND cl.id_semestre = ?");
        $stmtG->bind_param("iii", $id_car, $m, $id_semestre);
        $stmtG->execute();
        $resG = $stmtG->get_result()->fetch_assoc();
        $grupal[] = min(round(($resG['total'] / $totalEstudiantes) * 100, 2), 100);

        // INDIVIDUAL
        $stmtI = $cn->prepare("SELECT COUNT(DISTINCT d.id_estu) AS total 
            FROM tutoria_detalle_sesion_curso d
            INNER JOIN tutoria_sesiones_tutoria_f78 s ON s.id_sesiones_tuto = d.sesiones_tutoria_id
            INNER JOIN carga_lectiva cl ON d.id_cargalectiva = cl.id_cargalectiva
            WHERE d.marcar_asis_estu = 1
            AND s.id_rol = 2
            AND cl.id_car = ?
            AND MONTH(s.fecha) = ?
            AND cl.id_semestre = ?");
        $stmtI->bind_param("iii", $id_car, $m, $id_semestre);
        $stmtI->execute();
        $resI = $stmtI->get_result()->fetch_assoc();
        $individual[] = min(round(($resI['total'] / $totalEstudiantes) * 100, 2), 100);
    }

    $datosPorCarrera[] = [
        'nombre' => $nombre_escuela,
        'grupal' => $grupal,
        'individual' => $individual
    ];
}
// ------------------GRAFICOS DE LOS RESULTDAOS
$id_semestre = $_SESSION['S_SEMESTRE'];
$estadisticas = [];

if (!isset($id_semestre)) {
    die("Error: id_semestre no está definido.");
}

// CONSULTA DE PROMEDIO DE FALTAS
$sql1 = "SELECT c.nom_car AS carrera, ROUND(AVG(asi.porcentaje), 1) AS promedio_faltas
FROM asignacion_estudiante ae
JOIN carga_lectiva cl ON ae.id_cargalectiva = cl.id_cargalectiva
JOIN carrera c ON cl.id_car = c.id_car
JOIN asistencias asi ON asi.id_aestu = ae.id_aestu AND asi.anulado = 0
WHERE ae.id_semestre = $id_semestre
GROUP BY c.nom_car
ORDER BY c.nom_car";

// CONSULTA DE PROMEDIO DE RENDIMIENTO
$sql2 = "SELECT 
  c.nom_car AS carrera, 
  ROUND(
    AVG(
      (
        COALESCE(ae.ntp1_per, 0) + 
        COALESCE(ae.ntp2_per, 0) + 
        COALESCE(ae.exa_par, 0) + 
        COALESCE(ae.ntp3_per, 0) + 
        COALESCE(ae.ntp4_per, 0) + 
        COALESCE(ae.exa_final, 0)
      ) / 6
    ), 
    1
  ) AS promedio_rendimiento
FROM asignacion_estudiante ae
JOIN carga_lectiva cl ON ae.id_cargalectiva = cl.id_cargalectiva
JOIN carrera c ON cl.id_car = c.id_car
WHERE ae.id_semestre = $id_semestre
GROUP BY c.nom_car
ORDER BY c.nom_car";

// Ejecutar consultas
$res1 = $conexion->conexion->query($sql1);
if (!$res1) {
    die("Error en consulta 1: " . $conexion->conexion->error);
}

$res2 = $conexion->conexion->query($sql2);
if (!$res2) {
    die("Error en consulta 2: " . $conexion->conexion->error);
}

// Combinar resultados
while ($falta = $res1->fetch_assoc()) {
    $estadisticas[$falta['carrera']]['faltas'] = $falta['promedio_faltas'];
}
while ($rend = $res2->fetch_assoc()) {
    $estadisticas[$rend['carrera']]['rendimiento'] = $rend['promedio_rendimiento'];
}

//========================ANEXOS==================================
// REFERENCIA :
$mes_url = $_GET['mes'] ?? '';
$mes_map = [
    'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
    'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
    'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
];
$mes_num = $mes_map[strtolower($mes_url)] ?? 0;

// Consulta de derivaciones atendidas
$data_derivaciones = [];

if ($mes_num > 0) {
    $sql = "SELECT 
                f.*, 
                e.nom_estu, e.apepa_estu, e.apema_estu,
                d.nom_doce, d.apepa_doce, d.apema_doce, d.abreviatura_doce, d.email_doce,
                a.area_apoyocol, f.area_apoyo_id
            FROM tutoria_derivacion_tutorado_f6 f
            JOIN estudiante e ON f.id_estudiante = e.id_estu
            JOIN docente d ON f.id_docente = d.id_doce
            JOIN tutoria_area_apoyo a ON f.area_apoyo_id = a.idarea_apoyo
            WHERE f.estado = 'atendido'
              AND MONTH(f.fechaDerivacion) = ?
              AND YEAR(f.fechaDerivacion) = YEAR(NOW())
            ORDER BY f.fechaDerivacion ASC";

    $stmt = $conexion->conexion->prepare($sql);
    $stmt->bind_param("i", $mes_num);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $data_derivaciones[] = $row;
    }
}
// CONTRAREFERENCIA
$anio = intval($_GET['anio'] ?? date("Y"));

$sql = "
SELECT 
    d.*,
    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombre_estudiante,
    CONCAT(doc.abreviatura_doce, ' ', doc.apepa_doce, ' ', doc.apema_doce, ' ', doc.nom_doce) AS nombre_tutor,
    doc.nom_doce AS nombre_responsable,
    a.ape_encargado,
    a.nombre_encargado,
    tu.cor_inst AS email_encargado
FROM tutoria_derivacion_tutorado_f6 d
INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
INNER JOIN docente doc ON doc.id_doce = d.id_docente
LEFT JOIN tutoria_area_apoyo a ON a.idarea_apoyo = d.area_apoyo_id
LEFT JOIN tutoria_usuario tu ON tu.id_usuario = a.id_personal_apoyo
WHERE d.resultado_contra IS NOT NULL
  AND d.resultado_contra != ''
  AND d.estado = 'atendido'
  AND MONTH(d.fechaDerivacion) = ?
  AND YEAR(d.fechaDerivacion) = ?";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $mes_num, $anio);
$stmt->execute();
$data_contraref = $stmt->get_result();

// CONSULTA ÚNICA
$sqlF78 = "
SELECT s.*, d.id_estu
FROM tutoria_sesiones_tutoria_f78 s
LEFT JOIN tutoria_detalle_sesion d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
WHERE MONTH(s.fecha) = ?
  AND YEAR(s.fecha) = ?
  AND s.id_rol IN (2, 6)
ORDER BY s.fecha ASC
";

$stmtF78 = $conexion->conexion->prepare($sqlF78);
$stmtF78->bind_param("ii", $mes_num, $anio);
$stmtF78->execute();
$resF78 = $stmtF78->get_result();
$sesiones_f78 = [];

while ($row = $resF78->fetch_assoc()) {
    $sesiones_f78[] = $row;
}

/* ====== 14. DOCENTES QUE INCUMPLEN ====== */

/* 1) Mes recibido por GET */
$mes_url = strtolower(trim($_GET['mes'] ?? ''));

$mapMes = [
  'enero'      => 1,
  'febrero'    => 2,
  'marzo'      => 3,
  'abril'      => 4,
  'mayo'       => 5,
  'junio'      => 6,
  'julio'      => 7,
  'agosto'     => 8,
  'septiembre' => 9,
  'setiembre'  => 9,
  'octubre'    => 10,
  'noviembre'  => 11,
  'diciembre'  => 12,
];

$mes_num       = $mapMes[$mes_url] ?? 0;
$mes_nombre_uc = $mes_url ? mb_strtoupper($mes_url, 'UTF-8') : '';

/* 2) Semestre de sesión (por si acaso) */
$id_semestre_sesion = (int)($_SESSION['S_SEMESTRE'] ?? 0);

/* 3) Elegir el semestre según el MES seleccionado
   Ajusta estos valores si tus IDs cambian:
   - Abril–Julio  => semestre 32 (2025-I)
   - Septiembre–Diciembre => semestre 33 (2025-II)
*/
$semestre_mes = $id_semestre_sesion; // valor por defecto

if (in_array($mes_num, [4, 5, 6, 7], true)) {
    // Abril, Mayo, Junio, Julio
    $semestre_mes = 32;
} elseif (in_array($mes_num, [9, 10, 11, 12], true)) {
    // Septiembre, Octubre, Noviembre, Diciembre
    $semestre_mes = 33;
}

/* 4) Base de tutores del SEMESTRE del mes seleccionado */
$sql_tutores = "
SELECT 
  d.id_doce,
  d.abreviatura_doce,
  d.apepa_doce,
  d.apema_doce,
  d.nom_doce,
  d.email_doce,
  GROUP_CONCAT(DISTINCT cl.ciclo ORDER BY cl.ciclo SEPARATOR ', ')     AS ciclos,
  GROUP_CONCAT(DISTINCT ca.nom_car ORDER BY ca.nom_car SEPARATOR ', ') AS escuela
FROM tutoria_docente_asignado tda
JOIN docente d 
  ON d.id_doce = tda.id_doce
LEFT JOIN tutoria_asignacion_tutoria tat 
  ON tat.id_docente = tda.id_doce 
  AND tat.id_semestre = tda.id_semestre
LEFT JOIN carga_lectiva cl 
  ON cl.id_cargalectiva = tat.id_carga 
  AND cl.id_semestre = tat.id_semestre
LEFT JOIN carrera ca 
  ON ca.id_car = tda.id_car
WHERE tda.id_semestre = ?
GROUP BY 
  d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
ORDER BY d.apepa_doce, d.apema_doce, d.nom_doce
";

$stmt = $conexion->conexion->prepare($sql_tutores);
$stmt->bind_param("i", $semestre_mes);
$stmt->execute();
$tutores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* 5) Quienes enviaron PLAN (estado_envio >= 2) en ese semestre */
$sql_plan_enviaron = "
  SELECT DISTINCT id_docente
  FROM tutoria_plan_compartido
  WHERE id_semestre = ? AND estado_envio >= 2
";
$stPlan = $conexion->conexion->prepare($sql_plan_enviaron);
$stPlan->bind_param("i", $semestre_mes);
$stPlan->execute();
$doc_env_plan = array_map(
    'intval',
    array_column($stPlan->get_result()->fetch_all(MYSQLI_ASSOC), 'id_docente')
);

/* 6) Quienes enviaron INFORME del mes + semestre seleccionados */
$sql_inf_enviaron = "
  SELECT DISTINCT id_docente
  FROM tutoria_informe_mensual
  WHERE id_semestre = ?
    AND LOWER(TRIM(mes_informe)) = ?
    AND estado_envio = 2
";
$stInf = $conexion->conexion->prepare($sql_inf_enviaron);
$mes_llave = $mes_url; // ya en minúsculas y recortado
$stInf->bind_param("is", $semestre_mes, $mes_llave);
$stInf->execute();
$doc_env_inf = array_map(
    'intval',
    array_column($stInf->get_result()->fetch_all(MYSQLI_ASSOC), 'id_docente')
);

/* 7) Armar listas de NO CUMPLIERON */
$no_plan = [];
$no_inf  = [];

foreach ($tutores as $t) {
  $id = (int)$t['id_doce'];

  // No envió plan en TODO el semestre
  if (!in_array($id, $doc_env_plan, true)) {
    $no_plan[] = $t;
  }

  // No envió informe del MES seleccionado
  if ($mes_num > 0 && !in_array($id, $doc_env_inf, true)) {
    $no_inf[] = $t;
  }
}

/* 8) Helper para nombre del docente */
function nombre_doc($t) {
  return trim($t['abreviatura_doce'].' '.$t['apepa_doce'].', '.$t['apema_doce'].' '.$t['nom_doce']);
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Resultados - CGT</title>
    <style>
        body {font-family: 'Times New Roman', serif;margin: 0; padding: 0; background-color: #f0f0f0;}
        .documento { width: 210mm;  min-height: 297mm;  padding: 20mm;  margin: auto;  background: white; box-shadow: 0 0 5px rgba(0,0,0,0.1); box-sizing: border-box;/* page-break-after: always; */ position: relative;}
        .no-break { page-break-after: avoid; page-break-inside: avoid; }
        .logo-superior { position: absolute;  top: 40px; left: 40px; width: 200px; max-width: 100%;}
        .encabezado-img { position: absolute;  top: 10px; right: 40px; width: 320px; max-width: 100%;}
        .titulo { text-align: center;  font-weight: bold;  font-size: 18px; }
        .seccion { margin-top: 25px; margin-bottom: 25px; }
        table { width: 100%;border-collapse: collapse; margin-top: 10px; table-layout: fixed; /* << ESTO ES LO CLAVE */ }
        td, th {  border: 1px solid #555;   padding: 4px 6px;   font-size: 13px;   word-wrap: break-word;   word-break: break-word;    vertical-align: top;    text-align: center;    }
        p, li, ul { font-size: 15px; line-height: 1.8; /* más espacio entre líneas */ text-align: justify; }
        td:nth-child(3), th:nth-child(3) {text-align: left; }
        .informe-tabla th, .informe-tabla td {  border: 1px solid #333;  padding: 6px;  font-size: 13px;   text-align: center; }
        .informe-tabla th {  background-color: #f0f0f0;  }
        @media print {
        /* Deja que se parta si el contenido no cabe */
            .documento{
                box-shadow: none !important;
                margin: 0 !important;
                padding: 20mm !important;

                /* Quita las dos reglas problemáticas */
                page-break-inside: auto !important;   /* antes: avoid */
                break-inside: auto !important;        /* equivalente moderno */
                page-break-after: auto !important;    /* antes: always */
                break-after: auto !important;
                min-height: auto !important;          /* evita forzar altura fija */
                height: auto !important;
            }

            /* Solo empieza en nueva página a partir de la 2da “documento” */
            .documento + .documento{
                page-break-before: always !important;
                break-before: page !important;
            }

            /* No añadas salto después de la última */
            .documento:last-of-type{
                page-break-after: auto !important;
                break-after: auto !important;
            }

            /* Por si algún hijo grande no quiere partirse */
            .no-break{
                page-break-inside: avoid;
                break-inside: avoid;
            }
              html, body{
                background: #fff !important;
                -webkit-print-color-adjust: exact;   /* opcional */
                print-color-adjust: exact;           /* opcional */
            }

            /* Asegura que cada “documento” también sea blanco */
            .documento{
                background: #fff !important;
                 min-height: calc(297mm - 40mm) !important;  /* 297mm - (20mm arriba + 20mm abajo) */
            }
        }
        .boton-imprimir { position: fixed;top: 20px;  right: 20px; /* ahora esquina superior derecha */ z-index: 9999; background-color: #007bff;color: white; padding: 10px 12px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.3); transition: background 0.3s;}
        .boton-imprimir:hover { background-color: #0056b3;}
        @media print {
            .boton-imprimir {display: none !important;
            }
        }
        h3{font-size:14px}
        h2{font-size:12px}
        .tabla-incumplidos { width:100%; border-collapse:collapse; margin-top:8px; }
        .tabla-incumplidos th, .tabla-incumplidos td { border:1px solid #2b4a5a; padding:6px 8px; font-size:11px; }
        .tabla-incumplidos thead th { background:#0e4e6f; color:#fff; text-align:center; }
        .tabla-incumplidos tbody tr { background:#f8d7da; }         /* rosado suave */
        .tabla-incumplidos td.estado { font-weight:bold; text-align:center; }
        .tabla-incumplidos td.ciclo  { width:140px; }
        .tabla-incumplidos td.escuela{ width:210px; }
        .num { width:40px; text-align:center; }
    </style>
</head>

<Body>

    <div class="documento">
        <div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">
        🖨️
        </div>
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
        <br><br>
       <!-- INFORME N° -->
        <div style="margin-bottom: 10px; text-align: center;">
            <b><u><span style="margin-left: 5px;"><?= htmlspecialchars($informe['numero_informe']) ?></span></u></b> 
        </div>

        <!-- PARA -->
        <div style="margin-bottom: 5px;">
            <b>PARA:</b> 
            <span style="margin-left: 5px;"><strong><?= htmlspecialchars($informe['para_vpa']) ?></strong></span>
        </div>
        <p style="margin-top: 0;"><strong>Vicepresidente Académico</strong><br>Universidad Nacional de Cañete.</p>

        <!-- DE -->
        <div style="margin-bottom: 5px;">
            <b>DE:</b> 
            <span style="margin-left: 5px;"><strong><?= htmlspecialchars($informe['de_coordinador']) ?></strong></span>
        </div>
        <p style="margin-top: 0;"><strong>Coordinador(a) General de Tutoría</strong><br>Universidad Nacional de Cañete.</p>

        <!-- ASUNTO -->
        <div style="margin-bottom: 10px;">
            <b>Asunto:</b> 
            <span style="margin-left: 5px;"><?= htmlspecialchars($informe['asunto']) ?></span>
        </div>

        <!-- FECHA -->
        <p><strong>FECHA:</strong> <?= $fecha_hoy ?></p>
        <hr>

        <h3 style="text-align: left;">1. OBJETIVO GENERAL</h3>
        <p>
            Fortalecer el acompañamiento académico, personal y profesional de los estudiantes de las cinco Escuelas Profesionales de la Universidad Nacional de Cañete, 
            mediante la estrecha colaboración con los coordinadores de tutoría de las Escuelas Profesionales y tutores, con el fin de mejorar su rendimiento académico,
            promover su desarrollo integral y contribuir a su éxito educativo y profesional. 
        </p>

        <h3 style="text-align: left;">1.1. Objetivos Espedíficos</h3>
        <ul style="padding-left: 25px; text-align:">
            <b>a.</b> Planificar, dirigir y controlar las acciones de tutoría y consejería de las Escuelas Profesionales. <br>
            <b>b.</b> Convocar y dirigir las reuniones con los responsables de las Direcciones de cada Escuela Profesional, así como con los docentes tutores. <br>
            <b>c.</b> Planificar y consolidar el plan de tutoría de la universidad. <br>
            <b>d.</b> Informar y consolidar la información sobre el rendimiento académico y asistencia  de los estudiantes.<br>
            <b>e.</b> Realizar la supervisión y monitoreo inopinado en cumplimiento del plan de tutoría de la universidad.<br>
            <b>f.</b> Realizar la evaluación de las acciones de tutoría y consejería universitaria.<br>
            <b>g.</b> Informar mensualmente el cumplimiento de la tutoría y consejería universitaria, desarrollado por cada Escuela Profesional y su reconocimiento de las horas no lectivas de los docentes a cargo.<br>
        </ul>
     </div>
    <div class="documento">
         <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
        <ul>
            <b>h.</b> Elaborar el informe de resultados del programa de tutoría universitaria, consolidando la información proveniente de las distintas Escuelas Profesionales.<br>
            <b>i.</b> Asesorar y coordinar acciones que se presenten en el proceso de cumplimiento  de la ejecución e implementación de la tutoría y consejería.<br>
            <b>j.</b> Formular acciones de mejora a partir de los resultados obtenidos en el desarrollo de la tutoría y consejería en las Escuelas Profesionales, con el objetivo de contribuir al logro efectivo de las competencias establecidas en el perfil de  egreso.<br>
            <b>k.</b> Establecer indicadores e instrumentos para evaluar el desempeño de los docentes tutores. <br>
        </ul>
        <h3 style="text-align: left;">2. ALCANCE</h3>
            <p>
                El alcance corresponde a todos los estduinates de la UNDC, del I al X semestre. Así como a los docente tutores de aula, instancias de apoyo y soporte. Y demas centros de costos
                establecidos en el <b> Artículo 9. Responsables de la tutoría y consejería del Reglamento de Tutoría y Consejería de la Universidad Nacional de Cañete.</b>   
            </p>
        <h3 style="text-align: left;">3. DETALLES DE LOS SERVICIOS OFRECIDOS</h3>
            <ul>
                <li><strong>  Tutoría en todas las asignaturas:</strong> El servicio de tutoría estará disponible para todas las asignaturas de las 5 escuelas profesionales de la UNDC, adaptándose a las necesidades específicas de los
                estudiantes en cada caso, y complementándose con las asesorías académicas para la
                obtención del grado académico y el título profesional. </li>
            
                <li><strong>  Derivación a servicios adicionales:</strong> Además de la tutoría académica, se proporcionará orientación y derivación a otros servicios de apoyo, como asesoramiento psicológico, 
                talleres de habilidades de estudio, grupos de estudio, servicios de asistencia académica, entre otros, registrando el mismo en el  F-M01.04-  VRA-006  Hoja  de  Referencia 
                y contrarreferencia.</li> 

                <li><strong>  Seguimiento y evaluación integral: </strong>Se realizará un seguimiento continuo del progreso académico y emocional de los estudiantes que participan en el programa de tutoría, 
                utilizando herramientas de evaluación adecuadas para identificar áreas de mejora y brindar el apoyo necesario a través del servicio de psicopedagogía. </li>
            </ul>
      
    </div>
    <div class="documento">
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
            <ul>
                <li><strong>Promoción de bienestar integral:</strong> Además de la tutoría académica, se fomentará el
                bienestar integral de los estudiantes, promoviendo prácticas saludables de autocuidado y ofreciendo recursos para el manejo del estrés y la ansiedad, 
                a través de talleres de música, deporte, danza y teatro.</li>
            </ul>
<!--         <h3 style="text-align: left;">4. DETALLE DE PERSONAL RESPONSABLE Y PERFIL DEL PERSONAL A CARGO DE BRINDAR EL SERVICIO DE TUTORÍA </h3>
        <ol type="b" style="list-style-type: lower-alpha; padding-left: 20px; ">
                <li style="list-style-position: outside; margin-bottom: 6px;">Docente con grado académico de Maestro o Doctor.</li>
                <li>Profesional, educador o psicólogo con experiencia en tutoría universitaria.</li>
                <li>Experiencia profesional universitaria mínima de un (1) año.</li>
                <li>Docente con dedicación a tiempo completo que registren en sus horas no lectivas su función como tutor.</li>
                <li>Con dominio de habilidades comunicativas, de coordinación y concertación.</li>
                <li>Con capacidad de realizar trabajo en equipo estableciendo buenas relaciones humanas.</li>
                <li>Con capacidad de diagnosticar situaciones problemáticas y sus posibles soluciones.</li>
                <li>Conocer la realidad del programa, el plan de estudios y otros documentos o gestión.</li>
                <li>Conocimiento y manejo de TIC e internet.</li>
        </ol> -->
        <h3 style="text-align: left;">4. MODALIDAD DE TUTORÍA</h3>
            <p>
                Las estrategias operativas que permitirán alcanzar los objetivos establecidos en
                el presente Plan están establecidas en dos tipos de intervención sobre los estudiantes: 
            </p>
            <p><strong>a.  Actividades grupales </strong></p>
            <p>Esta actividad será desarrollada en el aula por el docente tutor de Aula. Se implementarán talleres de identificación de los factores de riesgo
                 y de socialización de contenidos prácticos que permitirán reducir los factores de riesgo previamente identificados, a través de un
                  diagnóstico realizado por el tutor de aula.
            </p>
            <p>
                Para cada sesión de tutoría grupal realizada por el docente tutor de Aula, se generará el registro F- M01.04-VRA-007 "Registro de la Consejería y 
                Tutoría Académica Grupal", donde se registrarán los estudiantes que participaron de la sesión. 
            </p>
            <p>
                Sea el caso de intervención si se requiere derivar al alumno al servicio de orientación y asesoría por parte del psicopedagogo y/o personal especializado, 
                se utilizará el Registro F-M01.04-VPA-006 “Hoja de referencia y Contrarreferencia”. 
            </p>
            <p><strong>b.  Actividades individuales  </strong></p>
            <p>Son acciones tutoriales del docente tutor de aula en beneficio directo y personalizado del estudiante Universitario salud física y mental, problemas emocionales  y  sociales,  
                etc.  y el seguimiento de los casos derivados a la Dirección de Bienestar Universitario. 
            </p>


    </div>

    <div class="documento">
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>

            <p>
                Para cada sesión de tutoría individual llevada a cabo por el docente tutor de aula, se completará el registro F-M01.04-VRA-008 "Registro de la Consejería y Tutoría Académica Individual",
                 en el cual se detallará el servicio de tutoría proporcionado al estudiante.
            </p>
            <p>
               Sea el caso de intervención si se requiere derivar al alumno al servicio de orientación y
                asesoría por parte del psicopedagogo y/o personal especializado, se utilizará el Registro F- M01.04-VRA-006 “Hoja de referencia y Contrarreferencia”.
            </p>
        <h3 style="text-align: left;">5. QUIÉNES (ESTUDIANTES) HAN RECIBIDO EL SERVICIO </h3>
            <p>
            Durante el mes de septiembre, los estudiantes han participado en sesiones de tutoría académica grupal e individual, según las necesidades identificadas por cada tutor de aula.
            </p>
            <p>
            El detalle específico de las actividades realizadas, los temas tratados, el tipo de sesión (grupal o individual), así como la lista de los estudiantes, se encuentra registrado formalmente en los <strong>Anexos 2 y 3</strong> del presente informe:
            </p>
            <ul>
            <li><strong>Anexo 2:</strong> F-M01.04-VRA-007 - Registro de la Consejería y Tutoría Académica Grupal</li>
            <li><strong>Anexo 3:</strong> F-M01.04-VRA-008 - Registro de la Consejería y Tutoría Académica Individual</li>
            </ul>
            <p>
            Estos anexos constituyen la evidencia documentada del trabajo de tutoria de aula desarrollado durante el mes, garantizando la prestación de un servicio de calidad al estudiante universitario.
            </p>
        <h3 style="text-align: left;">6. ALUMNOS DERIVADOS A ALGÚN TIPO DE SERVICIO</h3>
            <p><strong>6.1. Resultados de las atenciones</strong></p>
            <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-size: 14px;">
                <thead style="background-color: #f2f2f2; text-align: center;">
                    <tr>
                    <th>Estudiante</th>
                    <th>Motivo / Observaciones</th>
                    <th>Docente que derivó</th>
                    <th>Oficina de destino</th>
                    <th>Resultado de la sesión</th>
                    <th>Especialista que atendió</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Conexión y consulta
                    $sql = "
                    SELECT  
                        CONCAT(e.nom_estu, ' ', e.apepa_estu,' ', e.apema_estu) AS estudiante,
                        f.observaciones,
                        CONCAT(d.nom_doce, ' ', d.apepa_doce , ' ', d.apema_doce) AS docente_que_derivo,
                        a.area_apoyocol AS oficina_destino,
                        f.resultado_contra,
                        CONCAT(a.ape_encargado, ' ', a.nombre_encargado) AS especialista_que_atendio
                    FROM tutoria_derivacion_tutorado_f6 f
                    JOIN estudiante e ON f.id_estudiante = e.id_estu
                    JOIN docente d ON f.id_docente = d.id_doce
                    JOIN tutoria_area_apoyo a ON f.area_apoyo_id = a.idarea_apoyo
                    WHERE f.estado = 'atendido'
                    AND MONTH(f.fechaDerivacion) = ?
                    AND YEAR(f.fechaDerivacion) = ?
                    ORDER BY f.fechaDerivacion ASC
                    ";

                    $anio_actual = date('Y');
                    $stmt = $conexion->conexion->prepare($sql);
                    $stmt->bind_param("ii", $mes_num, $anio_actual);
                    $stmt->execute();
                    $resultado = $stmt->get_result();

                    if ($resultado->num_rows > 0) {
                        while ($fila = $resultado->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$fila['estudiante']}</td>";
                            echo "<td>{$fila['observaciones']}</td>";
                            echo "<td>{$fila['docente_que_derivo']}</td>";
                            echo "<td>{$fila['oficina_destino']}</td>";
                            echo "<td>{$fila['resultado_contra']}</td>";
                            echo "<td>{$fila['especialista_que_atendio']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; color:#a94442; padding:8px;'>
                                No se registraron estudiantes derivados a servicios especializados en el presente mes.
                            </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
    </div>
    
    <div class="documento">
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
        <h3 style="text-align: left;">7. PORCENTAJE DE PARTICIPACIÓN DE LOS ESTUDIANTES</h3>
        <?php foreach ($datosPorCarrera as $index => $data): ?>
            <p><strong> 7.<?= $index + 1 ?>. Escuela Profesional de <?= $data['nombre'] ?></strong></p>

            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                <!-- Gráfico grupal -->
                
                <div style="font-size: 12px;flex: 1 1 300px; max-width: 300px; background: white; padding: 4px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.2); ">
                    <h4 style="text-align:center;">Participación en Tutoría Grupal</h4>
                    <?php foreach ($labelsMeses as $i => $mes): ?>
                        <div style="margin: 5px 0;">
                            <strong><?= $mes ?> (<?= $data['grupal'][$i] ?>%)</strong>
                            <div style="font-size: 11px; height: 18px; background: #4e73df; color: white; padding-left: 8px; border-radius: 4px; width: <?= $data['grupal'][$i] ?>%;">
                                <?= $data['grupal'][$i] ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Gráfico individual -->
                <div style="font-size: 12px; flex: 1 1 300px; max-width: 300px; background: white; padding: 4px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                    <h4 style="text-align:center;">Participación en Tutoría Individual</h4>
                    <?php foreach ($labelsMeses as $i => $mes): ?>
                        <div style="margin: 5px 0;">
                            <strong><?= $mes ?> (<?= $data['individual'][$i] ?>%)</strong>
                            <div style="font-size: 11px; height: 18px; background: #1cc88a; color: white; padding-left: 8px; border-radius: 4px; width: <?= $data['individual'][$i] ?>%;">
                                <?= $data['individual'][$i] ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="documento">
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
        <br> <br>
        <h3 style="text-align: left;">8. CUMPLIMIENTO DE HORAS NO LECTIVAS</h3>
            <span style="margin-left: 5px;  text-align: justify;"><?= htmlspecialchars($informe['no_lectivas']) ?></span>
        <h3 style="text-align: left;">9. RESULTADOS </h3>
            <div style="position: relative; padding: 60px 20px 20px 20px; border: 1px solid #ccc; margin-bottom: 30px; background-color: #fff;">

            <!-- Leyenda superior derecha -->
            <div style="position: absolute; top: 10px; right: 20px; font-size: 13px;">
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #e74c3c; margin-right: 5px;"></span> Promedio de faltas
                <span style="margin-left: 20px;"></span>
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #27ae60; margin-right: 5px;"></span> Promedio de rendimiento
            </div>

            <!-- Contenedor gráfico -->
            <div style="position: relative; height: 220px; padding-bottom: 60px; ">

                <!-- Línea base negra fija -->
                <div style="position: absolute; bottom: 0px; left: 0; right: 0; height: 2px; background: black; z-index: 1;"></div>

                <!-- Contenedor de barras -->
                <div style="display: flex; justify-content: center; align-items: flex-end; gap: 5px; height: 120%; position: relative; z-index: 2;">

                <?php foreach ($estadisticas as $carrera => $datos): 
                    $altura_faltas = isset($datos['faltas']) ? $datos['faltas'] * 10 : 0;
                    $altura_rend   = isset($datos['rendimiento']) ? $datos['rendimiento'] * 10 : 0;
                ?>
                <div style="text-align: center;">
                    <!-- Contenedor de las barras -->
                    <div style="position: relative; height: 180px;">
                    <div style="position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); display: flex; gap: 16px; align-items: flex-end;">
                        <!-- Barra faltas -->
                        <div style="width: 20px; height: <?= $altura_faltas ?>px; background-color: #e74c3c; position: relative;">
                        <span style="position: absolute; top: -18px; font-size: 11px; background: #e74c3c; color: white; border-radius: 3px; padding: 1px 4px;">
                            <?= number_format($datos['faltas'], 1) ?>%
                        </span>
                        </div>
                        <!-- Barra rendimiento -->
                        <div style="width: 20px; height: <?= $altura_rend ?>px; background-color: #27ae60; position: relative;">
                        <span style="position: absolute; top: -18px; font-size: 11px; background: #27ae60; color: white; border-radius: 3px; padding: 1px 4px;">
                            <?= number_format($datos['rendimiento'], 1) ?>%
                        </span>
                        </div>
                    </div>
                    </div>
                    <!-- Nombre de la carrera -->
                    <div style="margin-top: 10px; font-size: 12px; width: 130px; line-height: 1.2;">
                    <?= strtoupper($carrera) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Nota inferior -->
            <div style="text-align: center; margin-top: 8px; font-size: 11px; color: #555;">
                * Las alturas representan el porcentaje promedio (%). Ambas métricas están en la misma escala.
            </div>
        </div>
            
    </div>

    <div class="documento">
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
        <br> <br>
        <h3 style="text-align: left;">10. LOGROS</h3>
        <ul style="padding-left: 25px;">
            <li>Se   cumplió   con   las   actividades   de   tutoría de aula de   forma   Grupal e individual, con una alta participación de los estudiantes con el apoyo de los docentes tutores de aula de las escuelas profesionales de la UNDC. </li>
            <li>Asesoría y apoyo a todos los estudiantes involucrados en  la  tutoría,
                con productos de mejora en su formación profesional y continuidad de sus estudios superiores. 
            </li>
            <li>Reducir la cantidad de estudiantes en riesgo académico con el soporte de las orientaciones impartidas por el programa de tutoría universitaria de nuestra institución. </li>
            <li>Reuniones de coordinación con los Docentes Tutores para que puedan
                dar cumplimiento a dar información periódica de sus actividades con los
                sustentos como evidencias. 
            </li>
        </ul>  
        <h3 style="text-align: left;">11. ACCIONES DE MEJORA</h3>
        <ul style="padding-left: 25px;">
            <li>Demora en la asignación y registro de tutores de aula por parte de las direcciones de escuela y departamentos académicos </li>
            <li>Desafíos logísticos, en algunos casos, la programación de las sesiones grupales e individuales no coinciden con los horarios disponibles de todos los estudiantes, afectando la asistencia de manera puntual.</li>
            <li>Los números telefónicos de algunos estudiantes no son los que corresponde, otros estudiantes no contestan a las llamadas por el tutor para coordinar sus actividades.</li>
            <li>El exceso de asignación de docentes tutores de aula en un solo ciclo.</li>
            <li>Asignación de más de un aula a un docente.</li>
            <li>Falta de compromiso de los docentes tutores de aula en realizar la labor tutorial e incumplimiento de sus entregables como planes de trabajo e informes mensuales.</li>
        </ul>
    </div>
    <div class="documento">
        <!-- Página 1: Portada y presentación -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
            </div>
            <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
                <tr>
                    <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                    <strong>Código:</strong> F-M01.04-VRA-013<br>
                    <strong>Fecha de Aprobación:</strong> 15/02/2024
                    </td>
                    <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                    <strong>Versión: 01</strong>
                    </td>
                </tr>
            </table>
        </div>
        <br> <br>
        <h3 style="text-align:left;">12. DOCENTES QUE INCUMPLEN EL ENVÍO DE PLANES E INFORMES MENSUALES</h3>

        <h2 style="text-align:left;">12.1. PLANES DE TUTORÍA</h2>
        <?php if (count($no_plan) === 0): ?>
        <p style="color:#2e7d32;"><strong>Todos los docentes enviaron su plan en el semestre actual.</strong></p>
        <?php else: ?>
        <table class="tabla-incumplidos">
            <thead>
            <tr>
                <th class="num">N°</th>
                <th>Tutor(a)</th>
                <th>Correo</th>
                <th class="ciclo">Ciclo(s)</th>
                <th class="escuela">Escuela Profesional</th>
                <th class="estado">Estado</th>
            </tr>
            </thead>
            <tbody>
            <?php $i=1; foreach ($no_plan as $t): ?>
                <tr>
                <td class="num"><?= $i++ ?></td>
                <td><?= htmlspecialchars(nombre_doc($t)) ?></td>
                <td><?= htmlspecialchars($t['email_doce']) ?></td>
                <td class="ciclo"><?= htmlspecialchars($t['ciclos'] ?: '—') ?></td>
                <td class="escuela"><?= htmlspecialchars($t['escuela'] ?: '—') ?></td>
                <td class="estado">NO CUMPLIÓ</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <h2 style="text-align:left;">12.2. INFORMES QUE CORRESPONDEN AL MES DE <?= $mes_nombre_uc ?: '—' ?></h2>
        <?php if ($mes_num === 0): ?>
        <p style="color:#c0392b;"><strong>No se reconoció el mes seleccionado.</strong> Envíalo como texto: “septiembre”, “octubre”, etc.</p>
        <?php else: ?>
        <?php if (count($no_inf) === 0): ?>
            <p style="color:#2e7d32;"><strong>Todos los docentes enviaron su informe de <?= htmlspecialchars($mes_url) ?>.</strong></p>
        <?php else: ?>
            <table class="tabla-incumplidos">
            <thead>
                <tr>
                <th class="num">N°</th>
                <th>Tutor(a)</th>
                <th>Correo</th>
                <th class="ciclo">Ciclo(s)</th>
                <th class="escuela">Escuela Profesional</th>
                <th class="estado">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach ($no_inf as $t): ?>
                <tr>
                    <td class="num"><?= $i++ ?></td>
                    <td><?= htmlspecialchars(nombre_doc($t)) ?></td>
                    <td><?= htmlspecialchars($t['email_doce']) ?></td>
                    <td class="ciclo"><?= htmlspecialchars($t['ciclos'] ?: '—') ?></td>
                    <td class="escuela"><?= htmlspecialchars($t['escuela'] ?: '—') ?></td>
                    <td class="estado">NO CUMPLIÓ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <!-- ANEXO 1: Derivaciones atendidas -->
    <!-- ANEXO 1: Derivaciones atendidas -->
    <?php
  
        if (!empty($data_derivaciones)) {
            include("../../pdf_ge/admin_referencia_html.php");
        }
    ?>
    <!-- ANEXO 1: Contrarreferencias -->
    <?php
        foreach ($data_contraref as $row) {
            include("../../pdf_ge/admin_contrareferencia_html.php");
        }
    ?>

</Body>

</html>