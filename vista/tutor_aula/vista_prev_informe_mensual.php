<?php    
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], [
  'TUTOR DE AULA','DIRECCION DE ESCUELA','SUPERVISION',
  'COORDINADOR GENERAL DE TUTORIA','DIRECTOR DE DEPARTAMENTO ACADEMICO',
  'VICEPRESIDENCIA ACADEMICA','COMITÉ - SUPERVISIÓN'
])) {
  die('Acceso no autorizado');
}

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_PE.UTF-8', 'spanish');
date_default_timezone_set('America/Lima');

$conexion = new conexion(); 
$conexion->conectar();

/* ====== PARÁMETROS ====== */
/* $id_cargalectiva = isset($_GET['id_cargalectiva']) ? (int)$_GET['id_cargalectiva'] : 0;
$id_semestre     = (int)$_SESSION['S_SEMESTRE']; */
$id_cargalectiva = isset($_GET['id_cargalectiva']) ? (int)$_GET['id_cargalectiva'] : 0;

// semestre: primero intento agarrar de GET (lo pasa Dirección),
// si no viene, caigo a sesión como último recurso
$id_semestre = isset($_GET['id_semestre'])
  ? (int)$_GET['id_semestre']
  : (int)($_SESSION['S_SEMESTRE'] ?? 0);

/* Mantienes tu UPDATE (no lo elimino) */
$conexion->conexion->query("
  UPDATE tutoria_sesiones_tutoria_f78 s
  SET s.color = '#3c8dbc'
  WHERE s.id_rol = 6
    AND s.color = '#00a65a'
    AND EXISTS (
        SELECT 1
        FROM tutoria_detalle_sesion d
        WHERE d.sesiones_tutoria_id = s.id_sesiones_tuto
        GROUP BY d.sesiones_tutoria_id
        HAVING COUNT(*) = 1
    )
");  

/* ====== FECHA ====== */
$meses = [
  'January'=>'enero','February'=>'febrero','March'=>'marzo',
  'April'=>'abril','May'=>'mayo','June'=>'junio',
  'July'=>'julio','August'=>'agosto','September'=>'septiembre',
  'October'=>'octubre','November'=>'noviembre','December'=>'diciembre'
];
$now = new DateTime('now', new DateTimeZone('America/Lima'));
$fecha_actual = $now->format('d') . ' de ' . ($meses[$now->format('F')] ?? $now->format('F')) . ' de ' . $now->format('Y');

/* ====== PLAN, CARGA, MES ====== */
$id_plan_tutoria = isset($_GET['id_plan']) ? (int)$_GET['id_plan'] :
                   (isset($_GET['id_plan_tutoria']) ? (int)$_GET['id_plan_tutoria'] : 0);

$mes_param = strtolower(trim($_GET['mes'] ?? ''));
$mes_map = ["abril"=>4,"mayo"=>5,"junio"=>6,"julio"=>7,"agosto"=>8,"septiembre"=>9,"octubre"=>10,"noviembre"=>11,"diciembre"=>12];
$mesTexto = $mes_param;
$mes_num  = $mes_map[$mes_param] ?? 0;

if (!$id_cargalectiva || $mes_num == 0) {
  die("Faltan parámetros válidos. cargalectiva=$id_cargalectiva, mes='$mesTexto'");
}
if (!$id_plan_tutoria) {
  die("No se encontró plan para esta carga.");
}

/* ====== Tutor asignado a ESA carga y semestre (TAT) ====== */
$sqlInfo = "
  SELECT DISTINCT
    d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce,
    tat.id_docente AS tutor_id,
    cl.ciclo, cl.id_car, ca.nom_car
  FROM tutoria_asignacion_tutoria tat
  JOIN docente       d  ON d.id_doce         = tat.id_docente
  JOIN carga_lectiva cl ON cl.id_cargalectiva = tat.id_carga
                        AND cl.id_semestre    = tat.id_semestre
  JOIN carrera       ca ON ca.id_car          = cl.id_car
  WHERE tat.id_carga    = ?
    AND tat.id_semestre = ?
  LIMIT 1
";
$stmtInfo = $conexion->conexion->prepare($sqlInfo);
$stmtInfo->bind_param("ii", $id_cargalectiva, $id_semestre);
$stmtInfo->execute();
$info = $stmtInfo->get_result()->fetch_assoc();

// si no hay info, evitamos Notice asignando valores vacíos en vez de NULL
if (!$info) {
    $info = [
        'abreviatura_doce' => '',
        'apepa_doce'       => '',
        'apema_doce'       => '',
        'nom_doce'         => '',
        'email_doce'       => '',
        'ciclo'            => '',
        'id_car'           => 0,
        'nom_car'          => '',
        'tutor_id'         => 0,
    ];
}

/* ====== Datos de cabecera ====== */
$abreviatura      = $info['abreviatura_doce'];
$apellido_paterno = $info['apepa_doce'];
$apellido_materno = $info['apema_doce'];
$nombres          = $info['nom_doce'];
$email_doce       = $info['email_doce'];
$ciclo            = $info['ciclo'];
$id_car           = (int)$info['id_car'];
$nom_car          = $info['nom_car'];

$id_doce          = (int)$info['tutor_id'];
$id_doce_informe  = $id_doce;


/* ====== Si no mandaron id_plan, traemos el del plan compartido por carga ====== */
if (!$id_plan_tutoria) {
  $sqlPlan = "SELECT id_plan_tutoria FROM tutoria_plan_compartido WHERE id_cargalectiva = ?";
  $stmtPlan = $conexion->conexion->prepare($sqlPlan);
  $stmtPlan->bind_param("i", $id_cargalectiva);
  $stmtPlan->execute();
  $planData = $stmtPlan->get_result()->fetch_assoc();
  if (!$planData) die("No se encontró plan de tutoría para esta carga lectiva.");
  $id_plan_tutoria = (int)$planData['id_plan_tutoria'];
}

/* ====== Director de Escuela ====== */
$sqlDirector = "SELECT CONCAT(grado, ' ', apaterno, ' ', amaterno, ' ', nombres) AS nombre_director
                FROM tutoria_usuario
                WHERE rol_id = 7 AND id_car = ?";
$stmtDirector = $conexion->conexion->prepare($sqlDirector);
$stmtDirector->bind_param("i", $id_car);
$stmtDirector->execute();
$directorData = $stmtDirector->get_result()->fetch_assoc();
$nombre_director_escuela = $directorData['nombre_director'] ?? '';

/* ====== Sesiones (F7/F8) ====== */
$sesionesGrupales = []; 
$sesionesIndividuales = [];

$sqlSesiones = "
  SELECT 
    s.id_sesiones_tuto,
    s.fecha,
    s.tema,
    ts.des_tipo AS modalidad,
    s.id_doce,
    s.color
  FROM tutoria_sesiones_tutoria_f78 s
  INNER JOIN tutoria_tipo_sesion ts ON s.tipo_sesion_id = ts.id_tipo_sesion
  WHERE s.id_rol = 6
    AND s.color IN ('#00a65a', '#3c8dbc')
    AND MONTH(s.fecha) = ?
    AND s.id_doce = ?
  ORDER BY s.fecha ASC
";
$stmt = $conexion->conexion->prepare($sqlSesiones);
$stmt->bind_param("ii", $mes_num, $id_doce);
$stmt->execute();
$resSesiones = $stmt->get_result();

while ($row = $resSesiones->fetch_assoc()) {
  $id_sesion = (int)$row['id_sesiones_tuto'];

  // Asignados
  $stmtAsig = $conexion->conexion->prepare("SELECT COUNT(DISTINCT id_estu) AS asignados FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = ?");
  $stmtAsig->bind_param("i", $id_sesion);
  $stmtAsig->execute();
  $asignados = (int)$stmtAsig->get_result()->fetch_assoc()['asignados'];

  // Asistentes
  $stmtAsis = $conexion->conexion->prepare("SELECT COUNT(*) AS asistentes FROM tutoria_detalle_sesion WHERE sesiones_tutoria_id = ? AND marcar_asis_estu = 1");
  $stmtAsis->bind_param("i", $id_sesion);
  $stmtAsis->execute();
  $asistentes = (int)$stmtAsis->get_result()->fetch_assoc()['asistentes'];

  $porcentaje = ($asignados > 0) ? round(($asistentes / $asignados) * 100, 2) . '%' : '0%';

  $row['asignados']  = $asignados;
  $row['asistentes'] = $asistentes;
  $row['porcentaje'] = $porcentaje;

  if ($asistentes > 1 && $row['color'] === '#00a65a') {
    $sesionesGrupales[] = $row;
  } elseif ($asistentes === 1 && $row['color'] === '#3c8dbc') {
    $stmtEst = $conexion->conexion->prepare("
      SELECT CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS asistente
      FROM tutoria_detalle_sesion d
      INNER JOIN estudiante e ON e.id_estu = d.id_estu
      WHERE d.sesiones_tutoria_id = ? AND d.marcar_asis_estu = 1
      LIMIT 1
    ");
    $stmtEst->bind_param("i", $id_sesion);
    $stmtEst->execute();
    $row['asistente'] = $stmtEst->get_result()->fetch_assoc()['asistente'] ?? '—';
    $sesionesIndividuales[] = $row;
  }
}

/* ====== Derivaciones (F6) ====== */
$derivaciones = []; 
$sqlDerivaciones = "
  SELECT 
      e.apepa_estu, e.apema_estu, e.nom_estu,
      d.fecha AS fechaDerivacion,
      d.motivo_ref,
      d.resultado_contra,
      d.fecha,
      d.estado,
      d.observaciones,
      CONCAT(a.ape_encargado, ' ', a.nombre_encargado) AS responsable,
      a.des_area_apo AS dirigido_a
  FROM tutoria_derivacion_tutorado_f6 d
  INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
  INNER JOIN tutoria_area_apoyo a ON a.idarea_apoyo = d.area_apoyo_id
  WHERE MONTH(d.fecha) = ?
    AND d.id_docente = ?
    AND d.id_rol = 6
  ORDER BY d.fecha ASC
";
$stmtDer = $conexion->conexion->prepare($sqlDerivaciones);
if (!$stmtDer) {
    die("Error al preparar SQL de derivaciones: " . $conexion->conexion->error);
}
$stmtDer->bind_param("ii", $mes_num, $id_doce);  // usa el tutor obtenido de TAT
$stmtDer->execute();
$resDer = $stmtDer->get_result();
while ($row = $resDer->fetch_assoc()) $derivaciones[] = $row;

/* ====== Estado / Datos guardados ====== */
$sqlEstado = "SELECT estado_envio FROM tutoria_informe_mensual 
              WHERE id_plan_tutoria = ? AND mes_informe = ? AND id_cargalectiva = ?";
$stmtEstado = $conexion->conexion->prepare($sqlEstado);
$stmtEstado->bind_param("ssi", $id_plan_tutoria, $mesTexto, $id_cargalectiva);
$stmtEstado->execute();
$resEstado = $stmtEstado->get_result();
$estado_envio = 0;
if ($fila = $resEstado->fetch_assoc()) $estado_envio = (int)$fila['estado_envio'];

$sqlInforme = "SELECT estado_envio, fecha_envio, asunto 
               FROM tutoria_informe_mensual 
               WHERE id_plan_tutoria = ? AND mes_informe = ? AND id_cargalectiva = ?";
$stmtInf = $conexion->conexion->prepare($sqlInforme);
$stmtInf->bind_param("ssi", $id_plan_tutoria, $mesTexto, $id_cargalectiva);
$stmtInf->execute();
$datosEnvioDocente = $stmtInf->get_result()->fetch_assoc();

$fecha_envio = $datosEnvioDocente['fecha_envio'] ?? '';
$asunto      = $datosEnvioDocente['asunto'] ?? ("Informe del Programa de Tutoría Universitaria - Mes de " . ucfirst($mesTexto));

$fecha_envio_larga = '';
if (!empty($fecha_envio)) {
  $dt_envio = new DateTime($fecha_envio);
  $fecha_envio_larga = $dt_envio->format('d') . ' de ' . ($meses[$dt_envio->format('F')] ?? $dt_envio->format('F')) . ' de ' . $dt_envio->format('Y');
}
$fecha_mostrar = $fecha_envio_larga ?: $fecha_actual;

/* Datos guardados para armar cuerpo del informe */
$sqlInformeCargado = "SELECT numero_informe, para_director, asunto, resultados_finales, logros, dificultades
                      FROM tutoria_informe_mensual 
                      WHERE id_plan_tutoria = ? AND mes_informe = ? AND estado_envio IN (1, 2) AND id_cargalectiva = ?";
$stmtInfCargado = $conexion->conexion->prepare($sqlInformeCargado);
if (!$stmtInfCargado) die("Error en prepare(): " . $conexion->conexion->error);
$stmtInfCargado->bind_param("ssi", $id_plan_tutoria, $mesTexto, $id_cargalectiva);
$stmtInfCargado->execute();
$result = $stmtInfCargado->get_result();

$numero_informe = '';
$para_director  = '';
$asunto_guard   = '';
$resultados_finales = '';
$logros = '';
$dificultades = '';

while ($datosCargados = $result->fetch_assoc()) {
  if (empty($numero_informe) && !empty($datosCargados['numero_informe'])) $numero_informe = $datosCargados['numero_informe'];
  if (empty($para_director)  && !empty($datosCargados['para_director']))  $para_director  = $datosCargados['para_director'];
  if (empty($asunto_guard)   && !empty($datosCargados['asunto']))         $asunto_guard   = $datosCargados['asunto'];
  $resultados_finales .= trim($datosCargados['resultados_finales']) . "\n";
  $logros             .= trim($datosCargados['logros']) . "\n";
  $dificultades       .= trim($datosCargados['dificultades']) . "\n";
}
$resultados_finales = trim($resultados_finales);
$logros             = trim($logros);
$dificultades       = trim($dificultades);

$mes_actual = ucfirst($mesTexto);

/* ====== ARMAR $informe SIEMPRE (para evitar Undefined) ====== */
$informe = [
  'numero_informe'     => $numero_informe ?: '',
  'para_director'      => $para_director ?: $nombre_director_escuela,
  'asunto'             => ($asunto_guard ?: $asunto) ?: "Informe del Programa de Tutoría Universitaria - Mes de $mes_actual",
  'fecha_envio'        => $fecha_envio,
  'mes_informe'        => $mes_actual,
  'resultados_finales' => $resultados_finales,
  'logros'             => $logros,
  'dificultades'       => $dificultades
];

/* ====== Conformidad del Director (uso nueva conexión para no pisar la actual) ====== */
require_once('../../modelo/modelo_conexion.php');
$conexion2 = new conexion();
$cn = $conexion2->conectar();

$id_plan = $_GET['id_plan'] ?? ($_GET['id_plan_tutoria'] ?? null);
$mes     = strtolower(trim($_GET['mes'] ?? ''));

$sqlRev = "
    SELECT 
        r.fecha_revision,
        r.nombre_director,
        r.id_car,
        u.cor_inst
    FROM tutoria_revision_director_informe r
    LEFT JOIN tutoria_usuario u 
           ON u.id_usuario = r.id_director
    WHERE r.id_plan_tutoria = ?
      AND LOWER(r.mes_informe) = ?
      AND r.id_car = ?
      AND r.id_semestre = ?
      AND UPPER(r.estado_revision) = 'CONFORME'
    ORDER BY r.id_revision DESC
    LIMIT 1
";

$stmtRev = $cn->prepare($sqlRev);
if (!$stmtRev) {
    die("Error al preparar SQL: " . $cn->error);
}
$stmtRev->bind_param("isii", $id_plan, $mes, $id_car, $id_semestre);

$stmtRev->execute();
$resRev = $stmtRev->get_result();
$revision = $resRev->fetch_assoc();

/* ====== Flags anexos ====== */
$tiene_grupal     = (count($sesionesGrupales) > 0);
$tiene_individual = (count($sesionesIndividuales) > 0);
$tiene_derivacion = (count($derivaciones) > 0);

/* ====================== HTML ====================== */
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Mensual - Tutor de Aula</title>
    <style>
        body {
    font-family: 'Times New Roman', serif;
    margin: 0;
    padding: 0;
     background-color: #f0f0f0;
  }

  .documento {
    width: 210mm;
    min-height: 297mm;
    padding: 20mm;
    margin: auto;
    background: white;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
    box-sizing: border-box;
    /* page-break-after: always; */
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
    width: 200px;
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
        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }
        .seccion {
        margin-top: 25px;
        margin-bottom: 25px;
        }
       table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        table-layout: fixed; /* << ESTO ES LO CLAVE */
        }
        td, th {
            border: 1px solid #555;
            padding: 4px 6px;
            font-size: 13px;
            word-wrap: break-word;
            word-break: break-word;
            vertical-align: top;
            text-align: center;
            }
        p, li {
        font-size: 15px;
        line-height: 1.8; /* más espacio entre líneas */
        text-align: justify;
        }

        
        td:nth-child(3), th:nth-child(3) {
        text-align: left;
        }
        .informe-tabla th, .informe-tabla td {
            border: 1px solid #333;
            padding: 6px;
            font-size: 13px;
            text-align: center;
            }
            .informe-tabla th {
            background-color: #f0f0f0;
            
            }
    @media print {
  body {
    background-color: white !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .documento {
    background-color: white !important;
    box-shadow: none !important;
    margin: 0 !important;
    padding: 20mm !important;
    page-break-after: always;
    page-break-inside: avoid;
  }

  #btn-imprimir {
    display: none !important;
  }

  .boton-imprimir {
    display: none !important; /* ocultar ícono al imprimir */
  }

  .celda-activa {
    background-color: #50aaff !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
.boton-imprimir {
  position: fixed;
  top: 20px;
  right: 20px; /* ahora esquina superior derecha */
  z-index: 9999;
  background-color: #007bff;
  color: white;
  padding: 10px 12px;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  transition: background 0.3s;
}

.boton-imprimir:hover {
  background-color: #0056b3;
}

@media print {
  .boton-imprimir {
    display: none !important;
  }
}
    </style>
</head>
<body>
     <div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">
        🖨️
        </div>
<!-- Página 1: Portada y presentación -->
    <div class="documento" style="page-break-after: always;">
    <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
    <img src="../../img/encabezado3.JPG" class="encabezado-img" alt="Encabezado">
    <br><br>

    <Center><b><u><?= htmlspecialchars($informe['numero_informe']) ?></u></b></Center>
    <p><strong><b>PARA:</b> <?= htmlspecialchars($informe['para_director']) ?></strong></p>
    <p>Responsable de la Dirección de Escuela Profesional de <?= htmlspecialchars($nom_car) ?>.</p>
    <p><strong><b>DE:</b> <?= htmlspecialchars($abreviatura . ' ' . $apellido_paterno . ' ' . $apellido_materno . ' ' . $nombres) ?></strong><br>
    Tutor(a) del ciclo <?= htmlspecialchars($ciclo) ?> de la Escuela Profesional de <?= htmlspecialchars($nom_car) ?>.
    </p>
    <p><b>ASUNTO:</b> <?= htmlspecialchars($asunto) ?></p>
   <p><strong>FECHA:</strong> <?= htmlspecialchars($fecha_envio_larga ?: $fecha_actual) ?></p>

    <hr style="border: 1px solid #000; margin: 20px 0;">

    <p style="margin-top: 40px;">
        Me es grato dirigirme a usted, a fin de saludarle cordialmente y, mediante la presente remitir a su despacho el Informe mensual del cumplimiento del Plan de tutoría universitaria correspondiente al mes de <b><?= $informe['mes_informe'] ?></b>.
        <br><br>
        Es de señalar que este Informe responde a los indicadores planteados:
    </p>

    <ul>
        <li>Consolidado del Registro de la Consejería y Tutoría Académica Grupal.</li>
        <li>Consolidado del Registro de la Consejería y Tutoría Académica Individual.</li>
        <li>Consolidado de Hoja de referencia y Contrarreferencia.</li>
    </ul>
    <br>

     <div style="text-align: center;">
        Atentamente:
        <br><br>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 60px;">
            <div style="min-width: 250px; font-size: 18px;">
                <?php if ($estado_envio == 2): ?>
                    <p style="font-style: italic; font-size: 12px;">
                        Fecha de Envío: <?= htmlspecialchars($fecha_mostrar) ?>
                    </p>
                <?php endif; ?>
                <div style="font-weight: bold; font-size: 13px;">
                    <?= htmlspecialchars($abreviatura . ' ' . $apellido_paterno . ' ' . $apellido_materno . ' ' . $nombres) ?>
                </div>
                <div style="font-size: 12px;">
                    <?= htmlspecialchars($email_doce) ?>
                </div>
            </div>
        </div>
        </div>
        <br><br>
            <?php if ($revision): ?>
                <div style="text-align: center; font-size: 13px; margin-top: 10px;">
                    <p style="text-align: center; margin: 2px 0; font-style: italic; font-weight: normal;">
                        Conformidad: <?= date('d/m/Y h:i A', strtotime($revision['fecha_revision'])) ?>
                    </p>
                    <div style="text-align: center; font-weight: bold;">
                        <?= strtoupper($revision['nombre_director']) ?>
                    </div>
                    <div style="text-align: center; font-size: 12px;">
                        Correo: <?= htmlspecialchars($revision['cor_inst']) ?>
                    </div>
                </div>
            <?php endif; ?>
        
    <footer style=" margin-top: 100px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>
<!-- Página 2: Resultados -->
<div class="documento"  style="page-break-after: always;">
    <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
    <img src="../../img/encabezado3.JPG" class="encabezado-img" alt="Encabezado">
    <br><br>
   <p><strong>1. OBJETIVO</strong></p>
    <li> Facilitar la integración y adaptación de los estudiantes a la vida universitaria, 
        apoyando su desarrollo académico y personal. </li>
    <li> Implementar asesorías individuales y grupales de manera eficiente, derivando 
        a especialistas cuando sea necesario. </li>
    <li> Utilizar datos sobre rendimiento académico, deserción y asistencia para 
        mejorar las acciones de tutoría. </li>
    <li> Evaluar periódicamente el programa de tutoría, elaborando informes 
        detallados y proponiendo mejoras para el avance educativo y profesional de 
        los estudiantes.</li>

        <!-- -------------------TABLAS -------------------------- -->
   <p><strong>2.1 Consejería y Tutoría Académica Grupal</strong></p>

    <table class="informe-tabla">
        <thead>
            <tr>
                <th style="width: 4%;">N°</th>
                <th style="width: 12%;">Fecha</th>
                <th style="width: 20%; text-align: left;">Tema</th>
                <th style="width: 15%;">Modalidad</th>
                <th style="width: 10%;">Asignados</th>
                <th style="width: 10%;">Asistentes</th>
                <th style="width: 15%;">% Participación</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sesionesGrupales)): ?>
                <?php foreach ($sesionesGrupales as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($s['fecha']) ?></td>
                        <td style="text-align: left;"><?= htmlspecialchars($s['tema']) ?></td>
                        <td><?= htmlspecialchars($s['modalidad']) ?></td>
                        <td><?= $s['asignados'] ?></td>
                        <td><?= $s['asistentes'] ?></td>
                        <td><?= $s['porcentaje'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No se registraron sesiones grupales en este mes.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<p><strong>2.2 Consejería y Tutoría Académica Individual</strong></p>
    <table class="informe-tabla">
      <thead>
        <tr>
          <th>N°</th>
          <th>Fecha</th>
          <th>Tema</th>
          <th>Modalidad</th>
          <th>Asistente</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($sesionesIndividuales)): ?>
          <?php foreach ($sesionesIndividuales as $i => $sesion): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($sesion['fecha']) ?></td>
              <td><?= htmlspecialchars($sesion['tema']) ?></td>
              <td><?= htmlspecialchars($sesion['modalidad']) ?></td>
              <td><?= htmlspecialchars($sesion['asistente']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5">No se registraron sesiones individuales en este mes.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <p><strong>3. Alumnos derivados a servicios especializados</strong></p>
    <table class="informe-tabla">
    <thead>
        <tr>
        <th rowspan="2">Alumno</th>
        <th rowspan="2">Dirigido a<br><small>(Área de Apoyo)</small></th>
        <th colspan="2">REFERENCIA</th>
        <th colspan="3">CONTRA-REFERENCIA</th>
        </tr>
        <tr>
        <th>Fecha</th>
        <th>Motivo</th>
        <th>Fecha</th>
        <th>Responsable</th>
        <th>Resultados</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($derivaciones)): ?>
        <?php foreach ($derivaciones as $d): ?>
            <tr>
            <td><?= htmlspecialchars($d['apepa_estu'] . ' ' . $d['apema_estu'] . ' ' . $d['nom_estu']) ?></td>
            <td><?= htmlspecialchars($d['dirigido_a'] ?? 'Pendiente') ?></td>
            <td><?= htmlspecialchars($d['fechaDerivacion']) ?></td>
            <td><?= htmlspecialchars($d['motivo_ref']) ?></td>
            <td><?= ($d['estado'] === 'Pendiente') ? 'Pendiente' : htmlspecialchars($d['fecha']) ?></td>
            <td><?= ($d['estado'] === 'Pendiente') ? 'Pendiente' : htmlspecialchars($d['responsable']) ?></td>
            <td><?= ($d['estado'] === 'Pendiente' || empty($d['resultado_contra'])) ? 'Pendiente' : htmlspecialchars($d['resultado_contra']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="7">No se registraron derivaciones en este mes.</td></tr>
        <?php endif; ?>
    </tbody>
    </table>
    <footer style=" margin-top: 300px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>

<div class="documento"  style="page-break-after: always;">
    <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
    <img src="../../img/encabezado3.JPG" class="encabezado-img" alt="Encabezado">
   <!-- 4. Resultados -->
    <p><strong>4. Resultados</strong></p>
    <ul>
        <?php foreach (explode("\n", $resultados_finales) as $res): ?>
            <?php if (trim($res) !== ''): ?>
                <li><?= htmlspecialchars($res) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- 5. Logros -->
    <p><strong>5. Logros</strong></p>
    <ul>
        <?php foreach (explode("\n", $logros) as $log): ?>
            <?php if (trim($log) !== ''): ?>
                <li><?= htmlspecialchars($log) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- 6. Dificultades -->
    <p><strong>6. Dificultades</strong></p>
    <ul>
        <?php foreach (explode("\n", $dificultades) as $dif): ?>
            <?php if (trim($dif) !== ''): ?>
                <li><?= htmlspecialchars($dif) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <footer style=" margin-top: 300px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>
<!-- Cada anexo es un documento aparte con page-break 
ANEXOS-->
<?php
// Variables que el include necesita SÍ O SÍ:
$mes_num         = (int)$mes_num;
$id_cargalectiva = (int)$id_cargalectiva;
$id_doce         = (int)$id_doce;  // ← el tutor de TAT

if ($tiene_grupal || $tiene_individual) {
  include("../../pdf_ge/formato78_html.php");
}

if ($tiene_derivacion) {
    $data_nom_car = $nom_car;
    include("../../pdf_ge/referencia_html.php");
}

if (!empty($data_contraref)) {
    include("../../pdf_ge/contrareferencia_html.php");
}
?>

</body>
</html>