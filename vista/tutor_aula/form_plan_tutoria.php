<?php  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

date_default_timezone_set('America/Lima');
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE AULA') {
    die('Acceso no autorizado');
}

if (!isset($_GET['id_cargalectiva'])) {
    die('No se proporcionó el ID de carga lectiva.');
}
$id_cargalectiva = $_GET['id_cargalectiva'];
$id_semestre = $_SESSION['S_SEMESTRE'];
$conexion = new conexion();
$conexion->conectar();

// Obtener datos del curso
$sql = "SELECT 
    cl.*, 
    a.nom_asi,
    s.nomsemestre AS semestre_nombre,
    c.nom_car,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce
FROM carga_lectiva cl
INNER JOIN asignatura a ON cl.id_asi = a.id_asi
INNER JOIN semestre s ON cl.id_semestre = s.id_semestre
INNER JOIN carrera c ON cl.id_car = c.id_car
INNER JOIN docente d ON cl.id_doce = d.id_doce
WHERE cl.id_cargalectiva = ?";

$stmt = $conexion->conexion->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->conexion->error);
}
$stmt->bind_param("i", $id_cargalectiva);
$stmt->execute();
$result = $stmt->get_result();
$datosCurso = $result->fetch_assoc();
if (!$datosCurso) {
    die("No se encontró la carga lectiva solicitada.");
}
$id_doce = (int)$_SESSION['S_IDUSUARIO'];
$ciclo = strtoupper(trim($datosCurso['ciclo']));
$id_car = (int)$datosCurso['id_car'];
$escuela = $datosCurso['nom_car'];
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
$temas_mes = ['mes1' => '', 'mes2' => '', 'mes3' => '', 'mes4' => ''];
$actividad_otros = '';

// Cargar temas por defecto según ciclo
$queryTemas = $conexion->conexion->prepare("SELECT mes1, mes2, mes3, mes4 FROM tutoria_temas_ciclo WHERE ciclo_tem = ?");
$queryTemas->bind_param("s", $ciclo);
$queryTemas->execute();
$resultTemas = $queryTemas->get_result();
$temas_defecto = $resultTemas->fetch_assoc() ?? $temas_mes;

// Verificar si existe un plan compartido por ciclo y carrera
$sqlExistePlan = "
  SELECT tp.id_plan_tutoria
  FROM tutoria_plan2 tp
  JOIN carga_lectiva cl ON cl.id_cargalectiva = tp.id_cargalectiva
  WHERE cl.ciclo = ? AND cl.id_car = ? AND cl.id_semestre = ?
  LIMIT 1
";
$stmtExiste = $conexion->conexion->prepare($sqlExistePlan);
$stmtExiste->bind_param("sii", $ciclo, $id_car, $id_semestre);
$stmtExiste->execute();
$resExiste = $stmtExiste->get_result();
$planExistente = $resExiste->fetch_assoc();

if (!$planExistente || !isset($planExistente['id_plan_tutoria'])) {
    die("No se encontró un plan compartido para este ciclo y carrera.");
}

$id_plan_tutoria = (int)$planExistente['id_plan_tutoria'];



    // Cargar SOLO las actividades del DOCENTE actual (por su carga)
    $sqlActividades = "SELECT mes, descripcion, comentario
                      FROM tutoria_actividades_plan
                      WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?";
    $stmtAct = $conexion->conexion->prepare($sqlActividades);
    $stmtAct->bind_param("iii", $id_plan_tutoria, $id_cargalectiva, $id_doce);
    $stmtAct->execute();
    $resAct = $stmtAct->get_result();
    if ($resAct->num_rows > 0) {
        while ($fila = $resAct->fetch_assoc()) {
            $mes = (int)$fila['mes'];
            if ($mes >= 1 && $mes <= 4) {
                $temas_mes["mes{$mes}"] = $fila['descripcion'];
            } elseif ($mes === 5) {
                $actividad_otros = $fila['comentario'] ?? '';
            }
        }
    } else {
        $temas_mes = $temas_defecto;
    }

    // Verificar si plan ya fue enviado
    // Estado SOLO del docente (su carga)
    $sqlMiEstado = "SELECT estado_envio, fecha_envio
                    FROM tutoria_plan_compartido
                    WHERE id_plan_tutoria = ? AND id_cargalectiva = ?
                    LIMIT 1";
    $stmtMiEstado = $conexion->conexion->prepare($sqlMiEstado);
    $stmtMiEstado->bind_param("ii", $id_plan_tutoria, $id_cargalectiva);
    $stmtMiEstado->execute();
    $resMiEstado = $stmtMiEstado->get_result()->fetch_assoc();


    // Ya no hay “todos guardaron”
    $estado_envio_numerico = (int)($resMiEstado['estado_envio'] ?? 0);
    $fecha_envio_final     = $resMiEstado['fecha_envio'] ?? null;

    $todos_guardaron = false;
    $mensaje_aviso   = "";


// 1. Estudiantes desaprobados en dos o más veces una misma asignatura
$queryDesaprobados = $conexion->conexion->query("
    SELECT COUNT(*) AS total_estudiantes 
    FROM (
        SELECT ae.id_estu 
        FROM asignacion_estudiante ae
        WHERE ae.vez_a >= 2 
          AND ae.id_semestre = 33 
          AND ae.id_car = $id_car
        GROUP BY ae.id_estu
    ) AS subconsulta
");
$total_desaprobados = $queryDesaprobados ? $queryDesaprobados->fetch_assoc()['total_estudiantes'] : 0;

// 2. Estudiantes con inasistencias entre 18% y 25%
$queryInasistencias = $conexion->conexion->query("
    SELECT COUNT(*) AS total_inasistencias 
    FROM asignacion_estudiante ae
    WHERE ae.id_semestre = 33 
      AND ae.por_f BETWEEN 18 AND 25 
      AND ae.id_car = $id_car
");
$total_inasistencias = $queryInasistencias ? $queryInasistencias->fetch_assoc()['total_inasistencias'] : 0;

// 3. Estudiantes con familias disfuncionales
$query1 = $conexion->conexion->query("
    SELECT COUNT(*) AS total 
    FROM fs_datos fd
    INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
    WHERE fd.ID_SEMESTRE = 33 
      AND UPPER(TRIM(fd.DD_CON_AC)) != 'AMBOS PADRES'
      AND ae.id_car = $id_car
");
$disfuncionales = $query1->fetch_assoc()['total'];

// 4. Estudiantes con discapacidad (CONADIS)
$query2 = $conexion->conexion->query("
    SELECT COUNT(*) AS total 
    FROM fs_datos fd
    INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
    WHERE fd.ID_SEMESTRE = 33 
      AND UPPER(TRIM(fd.DS_DIS_CARNET)) = 'SI'
      AND ae.id_car = $id_car
");
$conadis = $query2->fetch_assoc()['total'];

// 5. Estudiantes con bajos ingresos (SISFOH)
$query3 = $conexion->conexion->query("
    SELECT COUNT(*) AS total 
    FROM fs_datos fd
    INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
    WHERE fd.ID_SEMESTRE = 33 
      AND UPPER(TRIM(fd.DV_SISFOH)) = 'SI'
      AND ae.id_car = $id_car
");
$sisfoh = $query3->fetch_assoc()['total'];

// 6. Estudiantes con carencia de apoyo financiero
$query4 = $conexion->conexion->query("
    SELECT COUNT(*) AS total 
    FROM fs_datos fd
    INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
    WHERE fd.ID_SEMESTRE = 33 
      AND UPPER(TRIM(fd.SE_TRAB)) = 'SI'
      AND ae.id_car = $id_car
");
$apoyo_financiero = $query4->fetch_assoc()['total'];

// 7. Estudiantes con problemas de salud crónicos
$query5 = $conexion->conexion->query("
    SELECT COUNT(*) AS total 
    FROM fs_datos fd
    INNER JOIN asignacion_estudiante ae ON fd.ID_ESTU = ae.id_estu
    WHERE fd.ID_SEMESTRE = 33 
      AND UPPER(TRIM(fd.DS_PAD_ENF)) = 'SI'
      AND ae.id_car = $id_car
");
$salud = $query5->fetch_assoc()['total'];


// Inicializar actividades vacías para cronograma
$actividades = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'actividad_' . $i;
    $actividades[$i] = isset($_POST[$key]) ? $_POST[$key] : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>  
  h3{
    background-color: #499cda;
    background-color: #17a2b8;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 16px;
    margin-top: 2px;
    margin-bottom: 10px;
  }
  .documento p, .documento input[type="text"] {
    font-size: 16px;
    text-align: justify;
  }
  .diagnostico-caja {
    width: 30px;
    display: inline-block;
  }
   .documento {
   max-width: 1080px;  /* ancho contenedor*/
   margin: 40px auto;
   padding: 30px;
   background-color: #fff;
   border-radius: 12px;
   box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
  }
  .table-wrapper-horizontal {
    width: 100%;
    overflow-x: hidden;
  }
  .table-cronograma {
    width: 100%;
    font-size: 11px;
    background-color: white;
    table-layout: fixed;
    border-collapse: collapse;
  }
  .table-cronograma th,
  .table-cronograma td {
    text-align: center;
    vertical-align: middle;
    padding: 2px;
    word-break: break-word;
  }
  .col-actividad {
    width: 380px;
    font-size: 11px;
    text-align: left;
  }
  .col-semana {
    padding: 2px;
    font-size: 9px;
    width: 22px;
  }
  .actividad-nombre {
    font-size: 11px;
    text-align: left;
  }
  .actividad-nombre .actividad-texto {
    white-space: pre-wrap;
    word-break: break-word;
    text-align: left;
  }
  .semana-celda {
    height: 30px;
  }
  .titulo1{
    margin-top: 5px;
    margin-left: 50px;
    margin-bottom: -35px;
    font-weight: bold;
    
  }
  .bg-success {
    background-color: #17a2b8 !important; /* azul UNDC */
    color: white !important;
  }
 </style>
 </head>
<body>
<!---------------------- PAGINA 3------------------------ -->
<center><h4 class="titulo1"> REGISTRO DE PLAN DE TUTORÍA</h4></center>
<div class="documento">
  <form method="POST" action="tutor_aula/guardar_plan.php">
    <input type="hidden" name="id_cargalectiva" value="<?= $id_cargalectiva ?>">
    <?php if (isset($id_plan_tutoria)): ?>
      <input type="hidden" name="id_plan_tutoria" value="<?= $id_plan_tutoria ?>">
    <?php endif; ?>
    <h3><strong>1. INTRODUCCIÓN</strong></h3>
    <p>El presente Plan de Tutoría del semestre académico <input style="width: 60px;" type="text" class="diagnostico-caja" value="<?= $datosCurso['semestre_nombre'] ?>" readonly>, dirigido a los estudiantes del <input type="text" class="diagnostico-caja" value="<?= $datosCurso['ciclo'] ?>" readonly> ciclo, tiene como objetivo primordial la formación integral de los estudiantes, 
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
    y visión de la universidad.</p>
    <h3><strong>2. DIAGNÓSTICO</strong></h3> 
    <p>
      El <input type="text" class="diagnostico-caja" value="<?= htmlspecialchars($datosCurso['ciclo']) ?>" readonly> 
      ciclo académico, de la Escuela Profesional de <input style="width: 300px;" type="text" class="diagnostico-caja" value="<?= htmlspecialchars($datosCurso['nom_car']) ?>" readonly> 
      se inicia bajo las observaciones detectadas desde el I - X ciclo:
    </p>
    <p>(I ciclo)</p>
    <ul style="text-align: left;">
      <li>45 <!-- <input type="text" class="diagnostico-caja"> --> Estudiantes que ingresaron con bajas calificaciones en el examen de admisión 2026.</li>
    </ul>
    <p>(otros)</p>
    <ul style="text-align: left;">
      <li><input type="text" value="<?=$total_desaprobados?>" size="3"> Estudiantes desaprobados en dos o más veces una misma asignatura durante el semestre 2025_II.</li>
    </ul>
    <!---------------------- PAGINA 4 ------------------------ -->
    <ul style="text-align: left;">
      <li>
        <input type="text" value="<?= $total_inasistencias ?>" size="3"> Estudiantes con alto porcentaje de inasistencias durante el semestre 2025_II.
      </li>
    </ul>
    <h4 class="text-start"><strong>2.2 Detección de estudiantes con problemas familiares, de salud, económicos u otros matriculados en el semestre 2025_II.</strong></h4>
    <p class="text-start">Los factores que ayudan a la detección son:</p>
    <ul class="text-start">
      <li><input type="text" value="<?= $disfuncionales ?>" size="3"> Estudiantes que provienen de familias disfuncionales.</li>
      <li><input type="text" value="<?= $conadis ?>" size="3"> Estudiantes con problemas de discapacidad (CONADIS).</li>
      <li><input type="text" value="<?= $sisfoh ?>" size="3"> Estudiantes con bajos ingresos económicos para sus estudios (SISFOH).</li>
      <li><input type="text" value="<?= $apoyo_financiero ?>" size="3"> Estudiantes con carencia de apoyo financiero para sus estudios.</li>
      <li><input type="text" value="<?= $salud ?>" size="3"> Estudiantes que presentan problemas de salud crónicos.</li>
    </ul>

    <h4 class="text-start"><strong>2.3 Detección de estudiantes con Necesidades Educativas Especiales (NEE) matriculados durante el semestre 2025_II.</strong></h4>
    <ul style="text-align: left;">
      <li>12 <!-- <input type="text" class="diagnostico-caja"> --> Estudiantes con NEE.</li>
    </ul>

    <h4 class="text-start"><strong>2.4 Detección de porcentaje de deserción de estudiantes por curso durante el semestre 2025_II.</strong></h4>
    <ul style="text-align: left;">
      <li>2% <!-- <input type="text" class="diagnostico-caja"> --> de deserción de estudiantes que se matricularon y permanecen en el mismo ciclo académico por curso.</li>
    </ul>
    <h4 class="text-start"><strong>2.5 Otros (que considere por la naturaleza de la Escuela Profesional)</strong></h4>
    <p class="text-start"><em>(El docente tutor deberá anexar los datos correspondientes)</em></p>
    <textarea name="actividad_otros" class="form-control" <?= ($estado_envio_numerico == 2 ? 'readonly' : '') ?>><?= htmlspecialchars($actividad_otros) ?></textarea>
    <h3 class="text-start mt-4"><strong>3. OBJETIVOS</strong></h3>
    <h5 class="text-start"><strong>3.1 OBJETIVO GENERAL</strong></h5>
    <p style="text-align:justify;">
      Fortalecer el acompañamiento académico, personal y profesional de los estudiantes del <input style="width: 30px;" type="text" class="small-input" value="<?= htmlspecialchars($datosCurso['ciclo']) ?>" readonly> ciclo
      de la Escuela Profesional de <input style="width:300px;" type="text" class="medium-input" value="<?= htmlspecialchars($datosCurso['nom_car']) ?>" readonly>,
      mediante la estrecha colaboración con el Tutor de Aula; 
      con el fin de mejorar su rendimiento académico, promover su desarrollo integral y contribuir a su éxito educativo y profesional.
    </p>

   <!---------------------- PAGINA 5 ------------------------ -->


    <h5 class="text-start mt-4"><strong>3.2 OBJETIVOS ESPECÍFICOS</strong></h5>
    <ul class="text-start">
      <li>Facilitar la integración y adaptación de los estudiantes a la vida universitaria, apoyando su desarrollo académico y personal.</li>
      <li>Implementar asesorías individuales y grupales de manera eficiente, derivando a especialistas cuando sea necesario.</li>
      <li>Utilizar datos sobre rendimiento académico, deserción y asistencia para mejorar las acciones de tutoría.</li>
      <li>Evaluar periódicamente el programa de tutoría, elaborando informes detallados y proponiendo mejoras para el avance educativo y profesional de los estudiantes.</li>
    </ul>

    <h3 class="text-start mt-4">4. FECHA Y HORARIO</h3>
    <p class="text-start">La tutoría individual o grupal se realizará de acuerdo al PIT (Plan Individual de Trabajo) y/o en coordinación con el/los estudiante/s.</p>

    <h3 class="text-start mt-4">5. ESTRATEGIAS METODOLÓGICAS</h3>
    <p class="text-start">
    Las estrategias metodológicas que permitirán alcanzar los objetivos establecidos en el 
    presente Plan están establecidas en dos tipos de intervención sobre los estudiantes: 
    </p>

    <h5 class="text-start mt-3">5.1. ACTIVIDADES GRUPALES</h5>
    <p class="text-start">
      Esta actividad será desarrollada en el aula por el docente tutor. Se implementarán talleres 
      de identificación de los factores de riesgo y de socialización de contenidos prácticos que 
      permitirán reducir los factores de riesgo previamente identificados, a través de un 
      diagnóstico realizado por el Coordinador de la Escuela Profesional.  
    </p>
    <p class="text-start">
      Para cada sesión de tutoría grupal realizada por el docente tutor, se generará el registro 
      F-M01.04-VRA-007 "Registro de la Consejería y Tutoría Académica Grupal", donde se 
      registrarán los estudiantes que participaron de la sesión. 
    </p>
    <p class="text-start">
      Sea el caso de intervención si se requiere derivar al alumno al servicio de orientación y 
      asesoría por parte del psicopedagogo y/o personal especializado, se utilizará el Registro 
      F-M01.04-VRA-006 “Hoja de referencia y Contrarreferencia”.
    </p>

    <h5 class="text-start mt-3">5.2. ACTIVIDADES INDIVIDUALES</h5>
    <p class="text-start">
      Son acciones tutoriales del docente tutor en beneficio directo y personalizado del 
      estudiante universitario. Estas acciones se darán a partir de la Identificación de 
      situaciones problemáticas particulares de los estudiantes: Asignaturas desaprobadas, 
      problemas de salud física y mental, problemas emocionales y sociales, etc. y el 
      seguimiento de los casos derivados a la Dirección de Bienestar Universitario.
    </p>

   <!---------------------- PAGINA 6 ------------------------ -->


    <p > Para cada sesión de tutoría individual llevada a cabo por el docente tutor, se completará 
    el registro F-M01.04-VRA-008 "Registro de la Consejería y Tutoría Académica Individual", 
    en el cual se detallará el servicio de tutoría proporcionado al estudiante. </p>
    <p>Sea el caso de intervención si se requiere derivar al alumno al servicio de orientación y 
    asesoría por parte del psicopedagogo y/o personal especializado, se utilizará el Registro 
    F-M01.04-VRA-006 “Hoja de referencia y Contrarreferencia”.  </p>
      <h3 class="text-start mt-5">6. ACTIVIDADES</h3>
    
    <p>En el Artículo 11 del Reglamento General de Tutoría se establecen los lineamientos 
    mínimos del Programa de Tutoría Universitaria y, que todo Tutor asignado debe 
    considerar entre las acciones programadas en el Plan de Trabajo. Asimismo, debe 
    incorporar otras actividades académicas acorde al diagnóstico realizado o a las 
    problemáticas identificadas a nivel institucional. El tutor solo evidenciará los temas del 
    ciclo que le corresponde.</p>
    <p><strong> NOTA:</strong> Los temas comprendidos por mes en este plan deberán ser desarrollados, sin embargo, podrá añadir temas adicionales de acuerdo a su criterio.</p>

    <!-- ---------------- PAGINA 7: Tabla de Actividades ------------------ -->

    <div id="form-actividades">
      <table class="table table-bordered">
        <thead>
          <tr class="table-secondary text-center">
            <th><center>MES</center></th>
            <th>Actividad Académica</th>
          </tr>
        </thead>
        <tbody>
        <?php for ($i = 1; $i <= 4; $i++): ?>
          <?php
            $campo = "mes$i";
            $contenido = isset($temas_mes[$campo]) ? htmlspecialchars($temas_mes[$campo]) : '';
          ?>
          <tr>
            <td class="text-center">Mes: <?= $i ?></td>
            <td>
            <textarea rows="5" name="actividad_<?= $i ?>" class="form-control actividad-input w-100"
          data-semana="<?= $i ?>" <?= ($estado_envio_numerico == 2 ? 'readonly' : '') ?>><?= $contenido ?></textarea>
            </td>
          </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <!-- ------------- PAGINA 8: CRONOGRAMA ------------------ -->

    <h3 class="text-start mt-5"> <strong>7. CRONOGRAMA DE ACTIVIDADES</strong></h3>

    <div class="table-wrapper-horizontal">
      <table class="table table-bordered table-cronograma" id="tabla-cronograma">
        <thead class="text-center">
          <tr class="bg-primary text-white">
            <th rowspan="2" class="col-actividad">ACTIVIDADES POR MES</th>
            <th colspan="4">ABRIL</th>
            <th colspan="4">MAYO</th>
            <th colspan="4">JUNIO</th>
            <th colspan="4">JULIO</th>
           <!--  <th colspan="4">AGOSTO</th> 
            <th colspan="4">SEPTIEMBRE</th>
            <th colspan="4">OCTUBRE</th>
            <th colspan="4">NOVIEMBRE</th>
            <th colspan="4">DICIEMBRE</th> -->
          </tr>
          <tr>
            <?php for ($m = 1; $m <= 4; $m++): ?>
              <?php for ($s = 1; $s <= 4; $s++): ?>
                <th class="text-center col-semana"><?= $s ?></th>
              <?php endfor; ?>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <?php for ($i = 1; $i <= 4; $i++): ?>
            <tr id="cronograma-fila-<?= $i ?>">
              <td class="actividad-nombre">
                <div class="actividad-texto"><strong></strong></div>
              </td>
              <?php for ($w = 1; $w <= 16; $w++): ?>
                <td class="semana-<?= $w ?> semana-celda"></td>
              <?php endfor; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
    <!-- -----------------------PAGINA 9: --------------------------------->

    <h3 class="mt-4"><strong>8. EVALUACIÓN Y SEGUIMIENTO</strong></h3>
    <p>La evaluación será permanente y entre los indicadores que nos apoyarán podemos mencionar:</p>

    <ul>
      <li><strong>I-M01.04-009</strong> Porcentaje de estudiantes que participan activamente en las actividades de tutoría grupal, incluyendo reuniones, eventos y talleres organizados por el programa.</li>
      <li><strong>I-M01.04-010</strong> Porcentaje de estudiantes que participan activamente en las sesiones de tutoría individual.</li>
    </ul>

    <br><br>
    <div>
    <div class="text-end" style="text-align: right; margin-right: 50px;">
    Cañete, <?php
    $fechaFinalMostrar = $fecha_actual; // por defecto
    if ($estado_envio_numerico == 2 && isset($fecha_envio_final)) {
        $dt_envio = DateTime::createFromFormat('d/m/Y h:i A', $fecha_envio_final, new DateTimeZone('America/Lima'));
        if ($dt_envio) {
            $fechaFinalMostrar = $dt_envio->format('d \d\e F \d\e Y');
            $fechaFinalMostrar = str_replace(
                array_keys($meses),
                array_values($meses),
                $fechaFinalMostrar
            );
        }
    }
    ?>
    <input type="text" style="width: 200px; border: none;" value="<?= $fechaFinalMostrar ?>" readonly>
  </div>


  <?php 
   
    $sqlDocente = "
      SELECT d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce,
            (
              SELECT MAX(tpc.fecha_envio)
              FROM tutoria_plan_compartido tpc
              WHERE tpc.id_plan_tutoria = ?
                AND tpc.id_cargalectiva = ?
                AND tpc.estado_envio = 2
            ) AS fecha_envio
      FROM docente d
      WHERE d.id_doce = ?
      LIMIT 1
    ";
    $stmtDocente = $conexion->conexion->prepare($sqlDocente);
    if (!$stmtDocente) {
        die("Error al preparar SQL de docente: " . $conexion->conexion->error);
    }
    $stmtDocente->bind_param("iii", $id_plan_tutoria, $id_cargalectiva, $id_doce);
    $stmtDocente->execute();
    $fila = $stmtDocente->get_result()->fetch_assoc();

    echo '<div style="margin-top: 40px; display: flex; flex-wrap: wrap; justify-content: center; gap: 60px;">';

    if ($fila) {
        $nombre = strtoupper(trim($fila['abreviatura_doce'].' '.$fila['apepa_doce'].' '.$fila['apema_doce'].' '.$fila['nom_doce']));
        $correo = htmlspecialchars($fila['email_doce']);
        $fechaEnvio = $fila['fecha_envio'];

        echo "<div style='text-align: left; width: 260px; font-size: 14px; border: 1px solid #ccc; padding: 10px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'>";
        if (!empty($fechaEnvio)) {
            $fechaEnvioDT = new DateTime($fechaEnvio, new DateTimeZone('America/Lima'));
            echo "<div style='font-size: 12px; color: gray;'><i>Enviado: " . $fechaEnvioDT->format('d/m/Y h:i A') . "</i></div>";
        }
        echo "<strong>$nombre</strong><br>";
        echo "<span style='font-size: 12px;'>Correo: $correo</span>";
        echo "</div>";
    }
    echo '</div>';

    echo "<div style='text-align: center; margin-top: 30px;'>
            <strong>Tutor del ciclo:</strong> " . htmlspecialchars($ciclo) . "<br>
            <strong>Escuela Profesional de:</strong> " . htmlspecialchars($escuela) . "<br>
          </div>";
    ?>
  </div>
  <div>
  <!-- AVISOS -->
  
  <?php if ($estado_envio_numerico == 2): ?>
      <div style="text-align: center; margin-top: 20px; color: green; font-weight: bold;  background-color:#FAF6AC;">
          Este plan ya fue Enviado al Director de la Escuela Profesional de <?= htmlspecialchars($datosCurso['nom_car']) ?>.
      </div>
  <?php endif; ?>
  <!-- BOTONES ACCIÓN -->
  <?php if ($estado_envio_numerico < 2): ?>
    <div class="actions text-center" style="margin-top: 30px; margin-bottom: 50px;">
      <button type="submit" name="accion" value="guardar" class="btn btn-success"
              style="padding: 8px 20px; font-weight: bold;">Guardar</button>

      <button type="submit" name="accion" value="enviar" class="btn btn-primary"
              style="padding: 8px 20px; margin-left: 10px; font-weight: bold;">Enviar</button>
    </div>
  <?php endif; ?>
  </div>
</form>
  <!-- ---------------------- SCRIPT DE PINTADO ------------------------ -->
  <script>
    const estadoEnvio = <?= (int)$estado_envio_numerico ?>;
    if (estadoEnvio === 2) {
      document.querySelectorAll('.actividad-input, textarea[name="actividad_otros"]').forEach(el => el.readOnly = true);
      document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.disabled = true;
        btn.title = "Este plan ya fue enviado por usted. No se puede editar.";
      });
    }

// ---------------------- PINTADO DE CRONOGRAMA ---------------------- //
    const pintarPorMes = {
      1: [3, 4, 5, 6],     // Abril (semana 3 en adelante)
      2: [7, 8, 9, 10],    // Mayo
      3: [11, 12, 13, 14], // Junio
      4: [15, 16, 17, 18]  // Julio
    }; 
    /* const pintarPorMes = {
      1: [1, 2, 3, 4],     // SEP (semana 3 en adelante)
      2: [5, 6, 7, 8],    // OCT
      3: [9, 10, 11, 12], // NOV
      4: [13, 14, 15, 16]  // DIC
    };*/

    function actualizarCronograma(mes, value) {
      const fila = document.querySelector(`#cronograma-fila-${mes}`);
      if (!fila) return;

      // Mostrar texto en la columna izquierda
      fila.querySelector('.actividad-texto').textContent = value;

      // Limpiar todas las celdas del mes
      pintarPorMes[mes].forEach(semanaReal => {
        const celda = fila.querySelector(`.semana-${semanaReal}`);
        if (celda) {
          celda.classList.remove('bg-success', 'text-white');
          celda.style.backgroundColor = ''; // Quitar color si existía
        }
      });

      // Pintar solo si hay contenido
      if (value !== '') {
        pintarPorMes[mes].forEach(semanaReal => {
          const celda = fila.querySelector(`.semana-${semanaReal}`);
          if (celda) {
            celda.classList.add('bg-success', 'text-white');
            celda.style.backgroundColor = '#17a2b8'; // Color personalizado del coronograma
          }
        });
      }
    }

    // Pintar al escribir y al cargar
    document.querySelectorAll('.actividad-input').forEach(input => {
      const mes = parseInt(input.dataset.semana);

      // Evento: al escribir
      input.addEventListener('input', () => {
        const value = input.value.trim();
        actualizarCronograma(mes, value);
      });

      // Evento: al cargar la página
      const initialValue = input.value.trim();
      actualizarCronograma(mes, initialValue);
    });
  </script>
  
</div>
</body>
</html>