<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();
date_default_timezone_set('America/Lima');

// Usuario logueado
if (!isset($_SESSION['S_IDUSUARIO'])) {
    die('Acceso no autorizado');
}
$id_doce     = $_SESSION['S_IDUSUARIO'];
$id_semestre = $_SESSION['S_SEMESTRE'] ?? '';
$id_usuario  = $_SESSION['S_IDUSUARIO'] ?? '';
$mes_informe = strtolower(trim($_GET['mes'] ?? ''));

// === Inicializar fechas y año
$meses = [
  'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo', 
  'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
  'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
  'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
];

$anio_actual   = date('Y');
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_PE.UTF-8', 'spanish');
$fecha_actual = date('d') . ' de ' . $meses[date('F')] . ' de ' . date('Y');

// Mes desde la URL
$mes_url = $mes_informe;
$mapMes = ['abril' => 4, 'mayo' => 5, 'junio' => 6, 'julio' => 7, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];
$mes_num = $mapMes[$mes_url] ?? date('n');
$mes_nombre = ucfirst(strtolower(array_search($mes_num, $mapMes)));

// === Buscar informe ya registrado
$sql = "SELECT * FROM tutoria_informe_resultados_coordinador_general 
        WHERE mes_informe = ? 
        ORDER BY id_informe DESC LIMIT 1";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("s", $mes_informe);
$stmt->execute();
$res = $stmt->get_result();
$informe_guardado = $res->fetch_assoc();

// === Valores del informe
$formato_informe     = $informe_guardado['numero_informe']     ?? "INFORME N° 001 - $anio_actual - UNDC/";
$asunto              = $informe_guardado['asunto']             ?? "Informe de Resultados del Programa de Tutoría Universitaria - Mes de " . strtolower($mes_nombre);
$nombre_vicepresidente = $informe_guardado['para_vpa']         ?? '';
$nombre_coordinador    = $informe_guardado['de_coordinador']   ?? '';
$no_lectivas            = $informe_guardado['no_lectivas']     ?? '';
$fecha_actual          = $informe_guardado['fecha_registro']   ?? $fecha_actual;

// === DATOS DEL DOCENTE LOGUEADO
$sqlDocente = "SELECT grado, apaterno, amaterno, nombres FROM tutoria_usuario WHERE id_usuario = ?";
$stmtD = $conexion->conexion->prepare($sqlDocente);
$stmtD->bind_param("i", $id_doce);
$stmtD->execute();
$resD = $stmtD->get_result();
$docente = $resD->fetch_assoc();
$nombre_docente = $docente ? strtoupper(trim($docente['grado'].' '.$docente['apaterno'].' '.$docente['amaterno'].' '.$docente['nombres'])) : '';

// === VICEPRESIDENTE ACADÉMICO
$sqlVice = "SELECT grado, apaterno, amaterno, nombres FROM tutoria_usuario WHERE id_usuario = 49 LIMIT 1";
$resVice = $conexion->conexion->query($sqlVice);
$vice = $resVice->fetch_assoc();
$nombre_vicepresidente = $vice ? strtoupper(trim($vice['grado'] . ' ' . $vice['apaterno'] . ' ' . $vice['amaterno'] . ' ' . $vice['nombres'])) : $nombre_vicepresidente;

// === COORDINADOR GENERAL DE TUTORIA
$sqlCoord = "SELECT grado, apaterno, amaterno, nombres FROM tutoria_usuario WHERE id_usuario = 48 LIMIT 1";
$resCoord = $conexion->conexion->query($sqlCoord);
$coord = $resCoord->fetch_assoc();
$nombre_coordinador = $coord ? strtoupper(trim($coord['grado'] . ' ' . $coord['apaterno'] . ' ' . $coord['amaterno'] . ' ' . $coord['nombres'])) : $nombre_coordinador;
// -------------------------GRAFICOS 8. PORCENTAJES DE PARICIPACION DE ESTUDIANTES
$id_semestre = $_SESSION['S_SEMESTRE'];
/* $labelsMeses = ['Abril', 'Mayo', 'Junio', 'Julio', 'Agosto']; */
$labelsMeses = ['Octubre'];
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

/*     for ($m = 4; $m <= 8; $m++) { */
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
?>


<style>
h3 { background-color: #17a2b8; color: white; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 18px; }
p{
    text-align: justify;
    
}
.documento {
  max-width: 1280px;
  width: 95%;
  margin: 30px auto;
  padding: 30px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
}
  input[type=text], textarea { width: 100%; padding: 5px; margin: 5px 0; }

  th, td { padding: 5px; text-align: center; }
  .alert {
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 10px;
  
}

</style>
<div class="documento">
    <form action="reportes/admin_guardar_informe_mensual.php" method="POST">
        <input type="hidden" name="mes_informe" value="<?= htmlspecialchars($_GET['mes']) ?>">
        <input type="hidden" name="mes" value="<?php echo $_GET['mes'] ?? ''; ?>">
        <input type="hidden" name="accion" value="guardar">

       <?php
            $nombre_mes = $_GET['mes'] ?? '';
        ?>
            <input type="hidden" name="mes_informe" value="<?= htmlspecialchars($nombre_mes) ?>">
        <input type="hidden" name="mes" value="<?= $mes_url ?>">

        <h3>INFORME MENSUAL DE RESULTADOS DE TUTORÍA Y CONSEJERÍA UNIVERSITARIA</h3>

        <!-- INFORME N° -->
        <div style="margin-bottom: 10px;">
            <b>Informe Nº:</b> 
            <input type="text" name="numero_informe" value="<?= $formato_informe ?>" style="width: 600px;" >
        </div>

        <!-- PARA -->
        <div style="margin-bottom: 10px;">
            <b>PARA:</b>
            <input type="text" name="para_vpa" value="<?= $nombre_vicepresidente ?>" readonly style="border: none; background: transparent; font-weight: bold; width: 630px; color: #000;">
            <p><strong>Vicepresidente Académico</strong><br>Universidad Nacional de Cañete.</p>
        </div>

        <!-- DE -->
        <div style="margin-bottom: 10px;">
            <b>DE:</b>
            <input type="text" name="de_coordinador" value="<?= $nombre_coordinador ?>" readonly style="border: none; background: transparent; font-weight: bold; width: 650px; color: #000;">
            <p><strong>Coordinador(a) General de Tutoría</strong><br>Universidad Nacional de Cañete.</p>
        </div>

        <!-- ASUNTO -->
        <div style="margin-bottom: 10px;">
            <b>Asunto:</b> 
            <input type="text" name="asunto" value="<?= $asunto ?>" style="width: 630px;" readonly>
        </div>

        <!-- FECHA -->
        <p><strong>FECHA:</strong> <?= $fecha_actual ?></p>
        <hr>
        

        <h3 style="text-align: left;">1. OBJETIVO GENERAL</h3>
        <p>
            Fortalecer el acompañamiento académico, personal y profesional de los estudiantes de las cinco Escuelas Profesionales de la Universidad Nacional de Cañete, 
            mediante la estrecha colaboración con los coordinadores de tutoría de las Escuelas Profesionales y tutores, con el fin de mejorar su rendimiento académico,
            promover su desarrollo integral y contribuir a su éxito educativo y profesional. 
        </p>

        <h3 style="text-align: left;">1.1. Objetivos Espedíficos</h3>
        <ul style="padding-left: 25px; text-align:">
            <b>a.</b> Planificar, dirigir y controlar las acciones de tutoría y consejería de las Escuelas
            Profesionales. <br>
            <b>b.</b> Convocar y dirigir las reuniones con los responsables de las Direcciones de cada
            Escuela Profesional, así como con los docentes tutores. <br>
            <b>c.</b> Planificar y consolidar el plan de tutoría de la universidad. <br>
            <b>d.</b> Informar y consolidar la información sobre el rendimiento académico y asistencia
            de los estudiantes.<br>
            <b>e.</b> Realizar la supervisión y monitoreo inopinado en cumplimiento del plan de tutoría
            de la universidad.<br>
            <b>f.</b> Realizar la evaluación de las acciones de tutoría y consejería universitaria.<br>
            <b>g.</b> Informar mensualmente el cumplimiento de la tutoría y consejería universitaria,
            desarrollado por cada Escuela Profesional y su reconocimiento de las horas no
            lectivas de los docentes a cargo.<br>
            <b>h.</b> Elaborar el informe de resultados del programa de tutoría universitaria,
            consolidando la información proveniente de las distintas Escuelas Profesionales.<br>
            <b>i.</b> Asesorar y coordinar acciones que se presenten en el proceso de cumplimiento
            de la ejecución e implementación de la tutoría y consejería.<br>
            <b>j.</b> Formular acciones de mejora a partir de los resultados obtenidos en el desarrollo
            de la tutoría y consejería en las Escuelas Profesionales, con el objetivo de
            contribuir al logro efectivo de las competencias establecidas en el perfil de
            egreso.<br>
            <b>k.</b> Establecer indicadores e instrumentos para evaluar el desempeño de los docentes tutores. <br>
        </ul>
        <h3 style="text-align: left;">2. ALCANCE</h3>
            <p>
                El alcance corresponde a todos los estduinates de la UNDC, del I al X semestre. Así como a los docente tutores de aula, instancias de apoyo y soporte. Y demas centros de costos
                establecidos en el <b> Artículo 9. Responsables de la tutoría y consejería del Reglamento de Tutoría y Consejería de la Universidad Nacional de Cañete.</b>   
            </p>
        <h3 style="text-align: left;">3. DETALLES DE LOS SERVICIOS OFRECIDOS</h3>
            <p>
                <strong>Tutoría en todas las asignaturas:</strong> El servicio de tutoría estará disponible para todas las asignaturas de las 5 escuelas profesionales de la UNDC, adaptándose a las necesidades específicas de los
                estudiantes en cada caso, y complementándose con las asesorías académicas para la
                obtención del grado académico y el título profesional. 
            </p>
            <p>
                <strong>Derivación a servicios adicionales:</strong> Además de la tutoría académica, se proporcionará orientación y derivación a otros servicios de apoyo, como asesoramiento psicológico, 
                talleres de habilidades de estudio, grupos de estudio, servicios de asistencia académica, entre otros, registrando el mismo en el  F-M01.04-  VRA-006  Hoja  de  Referencia 
                y contrarreferencia. 
            </p>
            <p>
                <strong>Seguimiento y evaluación integral: </strong>Se realizará un seguimiento continuo del progreso académico y emocional de los estudiantes que participan en el programa de tutoría, 
                utilizando herramientas de evaluación adecuadas para identificar áreas de mejora y brindar el apoyo necesario a través del servicio de psicopedagogía. 
            </p>
            <p>
                <strong>Promoción de bienestar integral:</strong> Además de la tutoría académica, se fomentará el
                bienestar integral de los estudiantes, promoviendo prácticas saludables de autocuidado y ofreciendo recursos para el manejo del estrés y la ansiedad, 
                a través de talleres de música, deporte, danza y teatro.

            </p>
<!--         <h3 style="text-align: left;">4. DETALLE DE PERSONAL RESPONSABLE Y PERFIL DEL PERSONAL A CARGO DE BRINDAR EL SERVICIO DE TUTORÍA </h3>
            <ol type="a" style="list-style-type: lower-alpha; padding-left: 20px; ">
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
        <h3 style="text-align: left;">7. PORCENTAJE DE PARTICIPACIÓN DE LOS ESTUDIANTES</h3>
        <?php foreach ($datosPorCarrera as $index => $data): ?>
            <p><strong> 7.<?= $index + 1 ?>. Escuela Profesional de <?= $data['nombre'] ?></strong></p>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 40px;">
                <!-- Gráfico grupal -->
                
                <div style="flex: 1 1 300px; max-width: 460px; background: white; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                    <h4 style="text-align:center;">Participación en Tutoría Grupal</h4>
                    <?php foreach ($labelsMeses as $i => $mes): ?>
                        <div style="margin: 10px 0;">
                            <strong><?= $mes ?> (<?= $data['grupal'][$i] ?>%)</strong>
                            <div style="height: 20px; background: #4e73df; color: white; padding-left: 8px; border-radius: 4px; width: <?= $data['grupal'][$i] ?>%;">
                                <?= $data['grupal'][$i] ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Gráfico individual -->
                <div style="flex: 1 1 300px; max-width: 460px; background: white; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                    <h4 style="text-align:center;">Participación en Tutoría Individual</h4>
                    <?php foreach ($labelsMeses as $i => $mes): ?>
                        <div style="margin: 10px 0;">
                            <strong><?= $mes ?> (<?= $data['individual'][$i] ?>%)</strong>
                            <div style="height: 20px; background: #1cc88a; color: white; padding-left: 8px; border-radius: 4px; width: <?= $data['individual'][$i] ?>%;">
                                <?= $data['individual'][$i] ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <h3 style="text-align: left;">8. CUMPLIMIENTO DE HORAS NO LECTIVAS</h3>
            <textarea name="no_lectivas" rows="5" style="width:100%; font-size:14px;"><?= htmlspecialchars($no_lectivas) ?></textarea>
        <h3 style="text-align: left;">9. RESULTADOS </h3>
            <div style="position: relative; padding: 60px 20px 20px 20px; border: 1px solid #ccc; margin-bottom: 30px; background-color: #fff;">

            <!-- Leyenda superior derecha -->
            <div style="position: absolute; top: 10px; right: 20px; font-size: 13px;">
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #e74c3c; margin-right: 5px;"></span> Promedio de faltas
                <span style="margin-left: 20px;"></span>
                <span style="display: inline-block; width: 15px; height: 15px; background-color: #27ae60; margin-right: 5px;"></span> Promedio de rendimiento
            </div>

            <!-- Contenedor gráfico -->
            <div style="position: relative; height: 220px; padding-bottom: 40px;">

                <!-- Línea base negra fija -->
                <div style="position: absolute; bottom: 0px; left: 0; right: 0; height: 2px; background: black; z-index: 1;"></div>

                <!-- Contenedor de barras -->
                <div style="display: flex; justify-content: center; align-items: flex-end; gap: 60px; height: 120%; position: relative; z-index: 2;">

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
                    <div style="margin-top: 6px; font-size: 12px; width: 130px; line-height: 1.2;">
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
        <h3 style="text-align: left;">12. ANEXOS</h3>
        <p>Anexo 1: F.M01.04-VRA-006 Hoja de Referencia y Contrarreferencia</p>
        <p>Anexo 2: F.M01.04-VRA-007 Registro de la Consejería y Tutoría Académica Grupal</p>
        <p>Anexo 3: F.M01.04-VRA-008 Registro de la Consejería y Tutoría Académica Individual</p>
        <br>
        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" name="accion" value="guardar" class="btn btn-success" style="padding: 10px 25px; font-size: 16px;">
                Guardar Informe
            </button>
           
        </div>
        
    
    </form>

</div>