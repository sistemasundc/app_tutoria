<?php 
ini_set("display_errors", 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['TUTOR DE AULA', 'TUTOR DE CURSO','DIRECCION DE ESCUELA', 'SUPERVISION', 'COORDINADOR GENERAL DE TUTORIA', 'DIRECTOR DE DEPARTAMENTO ACADEMICO', 'VICEPRESIDENCIA ACADEMICA','COMITÉ - SUPERVISIÓN','DEPARTAMENTO ESTUDIOS GENERALES'])) {
  die('Acceso no autorizado');
}

date_default_timezone_set('America/Lima');

$conexion = new conexion(); 
$conexion->conectar();

$id_doce = $_SESSION['S_IDUSUARIO'];
$id_semestre = $_SESSION['S_SEMESTRE'];
$id_cargalectiva = $_GET['id_cargalectiva'] ?? null;
$mes_url = isset($_GET['mes']) ? trim($_GET['mes']) : '';
$anio_actual = date("Y");

$mapMeses = [
    4 => 'abril',
    5 => 'mayo',
    6 => 'junio',
    7 => 'julio',
    8 => 'agosto',
    9 => 'septiembre',
    10 => 'octubre',
    11 => 'noviembre',
    12 => 'diciembre',
];

if (is_numeric($mes_url)) {
    $mes_num = (int)$mes_url;
    $mes_informe_txt = $mapMeses[$mes_num] ?? '';
} else {
    $mes_informe_txt = strtolower($mes_url);
    $mes_num = array_search($mes_informe_txt, $mapMeses);
}

$mes_actual = ucfirst($mes_informe_txt); // Para mostrar "Mayo", "Junio", etc.

$is_director = in_array($_SESSION['S_ROL'], [
  'DIRECCION DE ESCUELA',
  'SUPERVISION',
  'COORDINADOR GENERAL DE TUTORIA',
  'DIRECTOR DE DEPARTAMENTO ACADEMICO',
  'VICEPRESIDENCIA ACADEMICA',
  'COMITÉ - SUPERVISIÓN',
  'DEPARTAMENTO ESTUDIOS GENERALES'
]);

if ($is_director) {
  $sqlDatos = "SELECT numero_informe, fecha_envio, estado_envio 
               FROM tutoria_informe_mensual_curso 
               WHERE id_cargalectiva = ? AND mes_informe = ?
               ORDER BY fecha_envio DESC 
               LIMIT 1";
  $stmtDatos = $conexion->conexion->prepare($sqlDatos);
  $stmtDatos->bind_param("is", $id_cargalectiva, $mes_informe_txt);
} else {
  $sqlDatos = "SELECT numero_informe, fecha_envio, estado_envio 
               FROM tutoria_informe_mensual_curso 
               WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?
               ORDER BY fecha_envio DESC 
               LIMIT 1";
  $stmtDatos = $conexion->conexion->prepare($sqlDatos);
  $stmtDatos->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe_txt);
}
$stmtDatos->execute();
$resDatos = $stmtDatos->get_result()->fetch_assoc();

$numero_informe_guardado = $resDatos['numero_informe'] ?? '';
$fecha_envio_registrada = $resDatos['fecha_envio'] ?? null;
$estado_envio = $resDatos['estado_envio'] ?? 0;

$fecha_actual = '';

if ($estado_envio == 2 && $fecha_envio_registrada) {
    $fecha_obj = new DateTime($fecha_envio_registrada);
    $fecha_obj->modify('-13 hours'); // RESTA 13 HORAS

    $meses = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
        'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
        'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
    ];

    $dia = $fecha_obj->format('d');
    $mes_ingles = $fecha_obj->format('F');
    $mes = $meses[$mes_ingles] ?? $mes_ingles;
    $anio = $fecha_obj->format('Y');

    $fecha_actual = "$dia de $mes de $anio";
} else {
    $now = new DateTime();
    $meses = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
        'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
        'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
    ];

    $dia = $now->format('d');
    $mes_ingles = $now->format('F');
    $mes = $meses[$mes_ingles] ?? $mes_ingles;
    $anio = $now->format('Y');

    $fecha_actual = "$dia de $mes de $anio";
}

$fecha_envio_detalle = '';
if ($estado_envio == 2 && $fecha_envio_registrada) {
    $fecha_original = new DateTime($fecha_envio_registrada);
    $fecha_original->modify('-13 hours'); // Resta 13 horas exactas
    $fecha_envio_detalle = $fecha_original->format('d/m/Y h:i A');
}

if ($numero_informe_guardado) {
    $formato_informe = $numero_informe_guardado;
} else {
    $sqlNum = "SELECT MAX(numero_informe) AS max_num FROM tutoria_informe_mensual_curso WHERE YEAR(fecha_envio) = ?";
    $stmtNum = $conexion->conexion->prepare($sqlNum);
    $stmtNum->bind_param("i", $anio_actual);
    $stmtNum->execute();
    $resNum = $stmtNum->get_result()->fetch_assoc();
    $max_actual = isset($resNum['max_num']) ? intval($resNum['max_num']) : 0;
    $siguiente_num = str_pad($max_actual + 1, 3, '0', STR_PAD_LEFT);
    $formato_informe = "INFORME Nº $siguiente_num - $anio_actual - UNDC/";
}

$ciclo_actual = ($mes_num >= 1 && $mes_num <= 7) ? "$anio_actual-1" : "$anio_actual-2";

$sql = "
SELECT cl.*, a.nom_asi, s.nomsemestre, c.nom_car, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
FROM carga_lectiva cl
INNER JOIN asignatura a ON cl.id_asi = a.id_asi
INNER JOIN semestre s ON cl.id_semestre = s.id_semestre
INNER JOIN carrera c ON cl.id_car = c.id_car
INNER JOIN docente d ON (
    (
        cl.id_doce = d.id_doce
        AND (
            ? IN ('junio', 'julio')
            OR cl.id_cargalectiva != 4945
        )
    )
    OR (
        d.id_doce = 19
        AND cl.id_cargalectiva = 4945
        AND ? IN ('abril', 'mayo')
    )
)
WHERE cl.id_cargalectiva = ?";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ssi", $mes_informe_txt, $mes_informe_txt, $id_cargalectiva);
$stmt->execute();
$datosCurso = $stmt->get_result()->fetch_assoc();
/* 
$sql = "SELECT cl.*, a.nom_asi, s.nomsemestre, c.nom_car, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
        FROM carga_lectiva cl
        INNER JOIN asignatura a ON cl.id_asi = a.id_asi
        INNER JOIN semestre s ON cl.id_semestre = s.id_semestre
        INNER JOIN carrera c ON cl.id_car = c.id_car
        INNER JOIN docente d ON (
            (cl.id_doce = d.id_doce AND ? IN ('junio', 'julio'))
            OR (d.id_doce = 19 AND ? IN ('abril', 'mayo'))
        )
        WHERE cl.id_cargalectiva = ?";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ssi", $mes_informe_txt, $mes_informe_txt, $id_cargalectiva);
$stmt->execute();
$datosCurso = $stmt->get_result()->fetch_assoc(); */

$id_car = $datosCurso['id_car'] ?? null;

// Obtener datos guardados del informe (incluye para_director y asunto)
if ($is_director) {
  $sqlDatos = "SELECT numero_informe, fecha_envio, estado_envio, para_director, asunto
               FROM tutoria_informe_mensual_curso 
               WHERE id_cargalectiva = ? AND mes_informe = ?
               ORDER BY fecha_envio DESC 
               LIMIT 1";
  $stmtDatos = $conexion->conexion->prepare($sqlDatos);
  $stmtDatos->bind_param("is", $id_cargalectiva, $mes_informe_txt);
} else {
  $sqlDatos = "SELECT numero_informe, fecha_envio, estado_envio, para_director, asunto
               FROM tutoria_informe_mensual_curso 
               WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?
               ORDER BY fecha_envio DESC 
               LIMIT 1";
  $stmtDatos = $conexion->conexion->prepare($sqlDatos);
  $stmtDatos->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe_txt);
}
$stmtDatos->execute();
$resDatos = $stmtDatos->get_result()->fetch_assoc();

$nombre_director_escuela = $resDatos['para_director'] ?? '';
$asunto_informe = $resDatos['asunto'] ?? 'Informe del Programa de Tutoría Universitaria - Mes de ' . ucfirst($mes_actual);

$is_director = in_array($_SESSION['S_ROL'], [
  'DIRECCION DE ESCUELA',
  'SUPERVISION',
  'COORDINADOR GENERAL DE TUTORIA',
  'DIRECTOR DE DEPARTAMENTO ACADEMICO',
  'VICEPRESIDENCIA ACADEMICA',
  'COMITÉ - SUPERVISIÓN',
  'DEPARTAMENTO ESTUDIOS GENERALES'
]);

if ($is_director) {
  $sqlVerifica = "SELECT estado_envio, resultados_finales, logros, dificultades 
                  FROM tutoria_informe_mensual_curso 
                  WHERE id_cargalectiva = ? AND mes_informe = ? 
                  ORDER BY fecha_envio DESC LIMIT 1";
  $stmtVerifica = $conexion->conexion->prepare($sqlVerifica);
  $stmtVerifica->bind_param("is", $id_cargalectiva, $mes_informe_txt);
} else {
  $sqlVerifica = "SELECT estado_envio, resultados_finales, logros, dificultades 
                  FROM tutoria_informe_mensual_curso 
                  WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?";
  $stmtVerifica = $conexion->conexion->prepare($sqlVerifica);
  $stmtVerifica->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe_txt);
}
$stmtVerifica->execute();
$resVerifica = $stmtVerifica->get_result()->fetch_assoc();

if ($is_director) {
  $sqlInforme = "SELECT resultados_finales, logros, dificultades 
                 FROM tutoria_informe_mensual_curso 
                 WHERE id_cargalectiva = ? AND mes_informe = ? 
                 ORDER BY fecha_envio DESC LIMIT 1";
  $stmtInforme = $conexion->conexion->prepare($sqlInforme);
  $stmtInforme->bind_param("is", $id_cargalectiva, $mes_informe_txt);
} else {
  $sqlInforme = "SELECT resultados_finales, logros, dificultades 
                 FROM tutoria_informe_mensual_curso 
                 WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?";
  $stmtInforme = $conexion->conexion->prepare($sqlInforme);
  $stmtInforme->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe_txt);
}
$stmtInforme->execute();
$informe = $stmtInforme->get_result()->fetch_assoc();

$ya_guardado = ($resVerifica && in_array($resVerifica['estado_envio'], [1, 2]));
$bloquear = ($resVerifica['estado_envio'] ?? 0) == 2;
$resultados_finales = $resVerifica['resultados_finales'] ?? '';
$logros = $resVerifica['logros'] ?? '';
$dificultades = $resVerifica['dificultades'] ?? '';


// Reporte de asistencia (F7-F8)
$sesionesGrupales = [];
$sesionesIndividuales = [];

$sqlSesiones = "
SELECT 
    s.id_sesiones_tuto,
    s.fecha,
    s.tema,
    ts.des_tipo AS modalidad,
    (
      SELECT COUNT(*) 
      FROM tutoria_detalle_sesion_curso 
      WHERE sesiones_tutoria_id = s.id_sesiones_tuto 
        AND marcar_asis_estu = 1
    ) AS asistentes,
    (
      SELECT COUNT(DISTINCT id_estu) 
      FROM tutoria_detalle_sesion_curso 
      WHERE sesiones_tutoria_id = s.id_sesiones_tuto
    ) AS asignados
FROM tutoria_sesiones_tutoria_f78 s
INNER JOIN tutoria_tipo_sesion ts ON s.tipo_sesion_id = ts.id_tipo_sesion
WHERE s.id_rol = 2
  AND s.id_carga = ?
  AND s.color IN ('#00a65a', '#3c8dbc')
  AND MONTH(s.fecha) = ?
ORDER BY s.fecha ASC
";

$stmtSesiones = $conexion->conexion->prepare($sqlSesiones);
$stmtSesiones->bind_param("ii", $id_cargalectiva, $mes_num);
$stmtSesiones->execute();
$resSesiones = $stmtSesiones->get_result();

$sesionesGrupales = [];
$sesionesIndividuales = [];

while ($row = $resSesiones->fetch_assoc()) {
    $asistentes = (int)$row['asistentes'];
    $asignados = (int)$row['asignados'];

    $row['porcentaje'] = ($asignados > 0) 
        ? round(($asistentes / $asignados) * 100, 2) . '%' 
        : '0%';

    if ($asignados > 1) {
        $sesionesGrupales[] = $row;
    } else {
        // obtener el nombre del estudiante que asistió
        $sqlEst = "
            SELECT CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS asistente
            FROM tutoria_detalle_sesion_curso d
            INNER JOIN estudiante e ON e.id_estu = d.id_estu
            WHERE d.sesiones_tutoria_id = ? 
              AND d.marcar_asis_estu = 1
            LIMIT 1
        ";
        $stmtEst = $conexion->conexion->prepare($sqlEst);
        $stmtEst->bind_param("i", $row['id_sesiones_tuto']);
        $stmtEst->execute();
        $resEst = $stmtEst->get_result();
        $estudiante = $resEst->fetch_assoc();

        $row['asistente'] = $estudiante['asistente'] ?? '—';

        $sesionesIndividuales[] = $row;
    }
}
// Derivaciones (F6)
$derivaciones = [];

$sqlDerivaciones = "
    SELECT 
        e.nom_estu,
        e.apepa_estu,
        e.apema_estu,
        da.fechaDerivacion,
        da.motivo_ref,
        da.fecha,
        da.estado,
        da.resultado_contra,
        a.des_area_apo AS dirigido_a,
        CONCAT(a.nombre_encargado, ' ', a.ape_encargado) AS responsable
    FROM tutoria_derivacion_tutorado_f6 da
    INNER JOIN estudiante e ON da.id_estudiante = e.id_estu
    LEFT JOIN tutoria_area_apoyo a ON da.area_apoyo_id = a.idarea_apoyo
    INNER JOIN asignacion_estudiante ae ON ae.id_estu = da.id_estudiante
    WHERE da.id_rol = 2
      AND da.id_docente = $id_doce
      AND MONTH(da.fechaDerivacion) = $mes_num
      AND ae.id_cargalectiva = $id_cargalectiva
    ORDER BY da.fechaDerivacion ASC
";

$resDerivaciones = $conexion->conexion->query($sqlDerivaciones);
while ($row = $resDerivaciones->fetch_assoc()) {
    $derivaciones[] = $row;
}

$ids_cargas = [$id_cargalectiva];

// ================== ANEXOS =================== 
$tiene_grupal = false;
$tiene_individual = false;
$tiene_derivacion = false;
$tiene_contraref = false;

// Verifica que $ids_cargas esté definido y sea seguro
if (!isset($ids_cargas)) {
    echo "<p>Error: No se definió el listado de cargas lectivas.</p>";
    return;
}

$ids = implode(',', array_map('intval', $ids_cargas));

// F7 - Tutoría Grupal (más de 1 estudiante, asistencia marcada)
$sqlF7 = "
    SELECT COUNT(*) AS total
    FROM tutoria_sesiones_tutoria_f78 s
    WHERE s.id_rol = 2 
    AND s.color = '#00a65a'
    AND MONTH(s.fecha) = $mes_num
    AND s.id_carga IN ($ids)
";
$stmtF7 = $conexion->conexion->prepare($sqlF7);
if ($stmtF7 && $stmtF7->execute()) {
    $resF7 = $stmtF7->get_result()->fetch_assoc();
    $tiene_grupal = ($resF7['total'] > 0);
}

// F8 - Tutoría Individual (usando tutoria_detalle_sesion_curso)
$sqlF8 = "
    SELECT COUNT(DISTINCT s.id_sesiones_tuto) AS total
    FROM tutoria_sesiones_tutoria_f78 s
    JOIN tutoria_detalle_sesion_curso d ON d.id_sesion = s.id_sesiones_tuto
    WHERE s.id_rol = 2 
    AND s.color = '#3c8dbc'
    AND d.marcar_asis_estu = 1
    AND MONTH(s.fecha) = $mes_num
    AND s.id_carga IN ($ids)
";
$stmtF8 = $conexion->conexion->prepare($sqlF8);
if ($stmtF8 && $stmtF8->execute()) {
    $resF8 = $stmtF8->get_result()->fetch_assoc();
    $tiene_individual = ($resF8['total'] > 0);
}

// ================== DERIVACIONES F6 - DATOS COMPLETOS PARA EL FORMATO ===================
$data_derivaciones = [];

$sqlF6data = "
    SELECT 
        d.fecha AS fechaDerivacion,
        d.hora AS horaDerivacion,
        d.motivo_ref,
        d.area_apoyo_id,
        d.estado,
        e.apepa_estu, e.apema_estu, e.nom_estu,
        doc.abreviatura_doce,
        doc.apepa_doce,
        doc.apema_doce,
        doc.nom_doce,
        doc.email_doce
    FROM tutoria_derivacion_tutorado_f6 d
    INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
    INNER JOIN docente doc ON doc.id_doce = d.id_docente
    INNER JOIN asignacion_estudiante ae ON ae.id_estu = d.id_estudiante
    WHERE d.id_rol = 2

      AND MONTH(d.fecha) = ?
      AND ae.id_cargalectiva IN ($ids)
";

$stmtF6data = $conexion->conexion->prepare($sqlF6data);
if (!$stmtF6data) {
    die("Error en prepare derivaciones F6: " . $conexion->conexion->error);
}
$stmtF6data->bind_param("i", $mes_num);
$stmtF6data->execute();
$resF6data = $stmtF6data->get_result();

while ($row = $resF6data->fetch_assoc()) {
    $data_derivaciones[] = $row;
}

$tiene_derivacion = count($data_derivaciones) > 0;
// F6 - Contrarreferencias respondidas
$sqlContra = "
    SELECT COUNT(*) AS total
    FROM tutoria_derivacion_tutorado_f6 d
    WHERE MONTH(d.fecha) = $mes_num
    AND d.resultado_contra IS NOT NULL
    AND d.resultado_contra != ''
    AND d.id_rol = 2
    AND d.id_cargalectiva IN ($ids)
";
$stmtContra = $conexion->conexion->prepare($sqlContra);
if ($stmtContra && $stmtContra->execute()) {
    $resContra = $stmtContra->get_result()->fetch_assoc();
    $tiene_contraref = ($resContra['total'] > 0);
}


    //CONSULTA PA MOSTRAR QUIEN DIO CONFORMIDAD
    // CONSULTA PARA OBTENER LA CONFORMIDAD DEL DIRECTOR
$sqlRev = "SELECT r.fecha_revision, r.nombre_director, u.cor_inst
           FROM tutoria_revision_director_informe_curso r 
           LEFT JOIN tutoria_usuario u ON u.id_usuario = r.id_director
           WHERE id_cargalectiva = ? 
             AND LOWER(mes_informe) = LOWER(?) 
             AND estado_revision = 'CONFORME'
           ORDER BY fecha_revision DESC
           LIMIT 1";

$stmtRev = $conexion->conexion->prepare($sqlRev);
$stmtRev->bind_param("is", $id_cargalectiva, $mes_informe_txt);
$stmtRev->execute();
$resRev = $stmtRev->get_result();
$revision = $resRev->fetch_assoc();

$conexion->cerrar();
?>
<head>
  <title>Informe_mensual</title>
    <style>
        body {
    font-family: 'Times New Roman', serif;
    margin: 0;
    padding: 0;
    background: #f0f0f0;
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
    <div class="documento"  style="page-break-after: always;">
        <img src="../../img/undc.png" class="logo-superior" alt="Logo UNDC">
        <img src="../../img/encabezado3.JPG" class="encabezado-img" alt="Encabezado">
        <br><br>
         <Center><b><u><?= $formato_informe ?></u></b></Center> <br>

        <p><strong><b>PARA:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b> <?= htmlspecialchars($nombre_director_escuela) ?></strong></p>
        <p>Responsable de la Dirección de Escuela Profesional de </b> <?= htmlspecialchars($datosCurso['nom_car']) ?>.</p>

            <div style="margin-bottom: 8px;"><b>De:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b> <?= htmlspecialchars($datosCurso['abreviatura_doce'].' '.$datosCurso['apepa_doce'].' '.$datosCurso['apema_doce'].' '.$datosCurso['nom_doce']) ?></div>
            <div style="margin-bottom: 8px;"><b>Asignatura:</b> <?= htmlspecialchars($datosCurso['nom_asi']) ?> - <b>Ciclo:</b> <?= htmlspecialchars($datosCurso['ciclo']) ?> - <b>Turno:</b> <?= htmlspecialchars($datosCurso['turno'].'-'.$datosCurso['seccion']) ?></div>
            
            <!---------------------ASUNTO------------------->
            <p><strong>ASUNTO:</strong> Informe del Programa de Tutoría Universitaria - Mes de <?= strtolower($mes_actual) ?></p>
            <p><strong>FECHA:</strong> <?= ucfirst($fecha_actual) ?></p>

             <hr style="border: 1px solid #000; margin: 20px 0;">
            <p style="text-align: justify;">
                Me es grato dirigirme a usted, a fin de saludarle cordialmente y, mediante la presente remitir a su despacho el Informe mensual del cumplimiento del Plan de tutoría universitaria, correspondiente al mes de
                <?= $mes_actual ?> del ciclo académico <?= htmlspecialchars($datosCurso['ciclo']) ?>.
            </p>
            <p style="text-align: justify;">Es de señalar que este Informe responde a los indicadores planteados:</p>
            <ul>
                <li>Consolidado del Registro de la Consejería grupal e individual.</li>
                <li>Consolidado de Hoja de referencia y Contrarreferencia.</li>
            </ul>

            <p style="text-align: justify;">
            Agradeciendo la atención que le brinde al presente, hago propicia la oportunidad para reiterarle las muestras de mi especial consideración y estima personal.
            </p>

            <br><br>

            <!-- FIRMAS -->
          <div style="text-align: center;">
          Atentamente:<br><br><br><br>
          <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 40px;">
          
            <div style="min-width: 280px;">
              <?php if ($estado_envio == 2 && $fecha_envio_detalle): ?>
                <p style="font-style: italic; margin-top: 5px; font-size: 14px;">
                  Fecha de Envío: <?= htmlspecialchars($fecha_envio_detalle) ?>
                </p>
              <?php endif; ?>
              <strong><?= htmlspecialchars($datosCurso['abreviatura_doce'] . ' ' . $datosCurso['apepa_doce'] . ' ' . $datosCurso['apema_doce'] . ' ' . $datosCurso['nom_doce']) ?></strong><br>
              <?= htmlspecialchars($datosCurso['email_doce']) ?><br>
              <br>
                <?php if (!empty($revision)): ?>
                  <div style="margin-top: 10px; text-align: center; font-size: 13px;">
                    <p style="margin: 2px 0;"><em><strong>Conformidad:</strong> <?= date('d/m/Y h:i A', strtotime($revision['fecha_revision'])) ?></em></p>
                    <div style="font-weight: bold;"><?= htmlspecialchars($revision['nombre_director']) ?></div>
                    <div style="font-size: 12px;">Correo: <?= htmlspecialchars($revision['cor_inst']) ?></div>
                  </div>
                <?php endif; ?>
            </div>
            
          </div>
        </div>
            <footer style=" margin-top: 70px;">
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

    <p><strong>3. Alumnos Derivados</strong></p>
    <table>
        <thead>
            <tr>
                <th>Alumno</th><th>Dirigido A</th><th>Fecha</th><th>Motivo</th>
                <th>Fecha Contra</th><th>Responsable</th><th>Resultados</th>
            </tr>
        </thead>
        <tbody>
          <?php if (!empty($derivaciones)): ?>
            <?php foreach ($derivaciones as $d): ?>
                <?php foreach ($derivaciones as $d): ?>
                  <tr>
                    <td><?= $d['apepa_estu'] . ' ' . $d['apema_estu'] . ' ' . $d['nom_estu'] ?></td>
                    <td><?= $d['dirigido_a'] ?? 'Pendiente' ?></td>
                    <td><?= $d['fecha'] ?></td> <!-- Fecha de derivación -->
                    <td><?= $d['motivo_ref'] ?></td>
                    <td><?= ($d['estado'] === 'Atendido') ? $d['fechaDerivacion'] : '—' ?></td> <!-- Fecha de contrareferencia -->
                    <td><?= !empty($d['responsable']) ? $d['responsable'] : 'Pendiente' ?></td>
                    <td><?= !empty($d['resultado_contra']) ? $d['resultado_contra'] : 'Pendiente' ?></td>
                  </tr>
                <?php endforeach; ?>
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
   <p><strong>4. Resultados</strong></p>
  <p><?= isset($informe['resultados_finales']) ? nl2br(htmlspecialchars($informe['resultados_finales'])) : '—' ?></p>

  <p><strong>5. Logros</strong></p>
  <p><?= isset($informe['logros']) ? nl2br(htmlspecialchars($informe['logros'])) : '—' ?></p>

  <p><strong>6. Dificultades</strong></p>
  <p><?= isset($informe['dificultades']) ? nl2br(htmlspecialchars($informe['dificultades'])) : '—' ?></p>
      <footer style=" margin-top: 600px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
    </footer>
</div>
<!-- Cada anexo es un documento aparte con page-break -->
<?php
if ($tiene_grupal || $tiene_individual) {
  $id_cargalectiva_formato78 = $id_cargalectiva;
  include("../../pdf_ge/formato78_curso_html.php");
}

if ($tiene_derivacion) {
  $data_derivaciones = $derivaciones;
  $data_nom_car = $datosCurso['nom_car'] ?? '';  // también requerido
  include("../../pdf_ge/referencia_html.php");
}

if ($tiene_contraref) {
    include("../../pdf_ge/contrareferencia_html.php");
}
?>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const nombreDocente = "<?= $datosCurso['apepa_doce'] . ' ' . $datosCurso['apema_doce'] . ' ' . $datosCurso['nom_doce'] ?>";
    const nombreArchivo = `Informe mensual - ${nombreDocente}.pdf`;

    // Detectar si es Chromium para sugerir descarga con nombre
    const isChrome = navigator.userAgent.includes("Chrome");

    if (isChrome) {
      const printBtn = document.getElementById("btn-imprimir");
      if (printBtn) {
        printBtn.addEventListener("click", () => {
          setTimeout(() => {
            document.title = nombreArchivo; // Cambia el título temporalmente
            window.print();
            setTimeout(() => {
              document.title = "Informe de tutoría"; // Restaura título
            }, 2000);
          }, 300);
        });
      }
    }
  });
</script>

</body>
