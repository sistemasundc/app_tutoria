<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion(); 
$conexion->conectar();

// Seguridad y rol
if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE CURSO') {
    die('Acceso no autorizado');
}

$id_doce = $_SESSION['S_IDUSUARIO'];
$id_semestre = $_SESSION['S_SEMESTRE'];
$id_cargalectiva = $_GET['id_cargalectiva'] ?? null;
$mes_url = isset($_GET['mes']) ? strtolower(trim($_GET['mes'])) : null;
//adiciona meses
$mapMes = ['abril' => 4, 'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];
$mes_num = isset($mapMes[$mes_url]) ? $mapMes[$mes_url] : date('n');
$meses = [4 => "Abril", 5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto", 9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"];
$mes_actual = $meses[$mes_num] ?? '';
$anio_actual = date("Y");
$mes_informe_txt = strtolower($mes_actual);

$sqlDatos = "SELECT numero_informe, fecha_envio, estado_envio 
             FROM tutoria_informe_mensual_curso 
             WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?
             ORDER BY fecha_envio DESC 
             LIMIT 1";
$stmtDatos = $conexion->conexion->prepare($sqlDatos);
$stmtDatos->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe_txt);
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
/* $sql = "SELECT cl.*, a.nom_asi, s.nomsemestre, c.nom_car, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.email_doce
        FROM carga_lectiva cl
        INNER JOIN asignatura a ON cl.id_asi = a.id_asi
        INNER JOIN semestre s ON cl.id_semestre = s.id_semestre
        INNER JOIN carrera c ON cl.id_car = c.id_car
        INNER JOIN docente d ON cl.id_doce = d.id_doce
        WHERE cl.id_cargalectiva = ?";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("i", $id_cargalectiva);
$stmt->execute();
$datosCurso = $stmt->get_result()->fetch_assoc(); */

$id_car = $datosCurso['id_car'] ?? null;

$sqlDirector = "SELECT CONCAT(grado, ' ', apaterno, ' ', amaterno, ' ', nombres) AS nombre_director
                FROM tutoria_usuario
                WHERE rol_id = 7 AND id_car = ?";
$stmtDirector = $conexion->conexion->prepare($sqlDirector);
$stmtDirector->bind_param("i", $id_car);
$stmtDirector->execute();
$directorData = $stmtDirector->get_result()->fetch_assoc();
$nombre_director_escuela = $directorData['nombre_director'] ?? '';

$sqlVerifica = "SELECT estado_envio, resultados_finales, logros, dificultades 
                FROM tutoria_informe_mensual_curso 
                WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?";
$stmtVerifica = $conexion->conexion->prepare($sqlVerifica);
$stmtVerifica->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe_txt);
$stmtVerifica->execute();
$resVerifica = $stmtVerifica->get_result()->fetch_assoc();

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
    COUNT(d.id_estu) AS total_estudiantes
  FROM tutoria_sesiones_tutoria_f78 s
  INNER JOIN tutoria_tipo_sesion ts ON s.tipo_sesion_id = ts.id_tipo_sesion
  INNER JOIN tutoria_detalle_sesion_curso d ON d.sesiones_tutoria_id = s.id_sesiones_tuto
  WHERE s.id_rol = 2
    AND s.id_carga = $id_cargalectiva
    AND s.color = '#00a65a'
    AND MONTH(s.fecha) = $mes_num
    AND d.marcar_asis_estu = 1
  GROUP BY s.id_sesiones_tuto
  ORDER BY s.fecha ASC
";

$resSesiones = $conexion->conexion->query($sqlSesiones);
while ($row = $resSesiones->fetch_assoc()) {
    $asistentes = (int)$row['total_estudiantes'];

    $sqlAsignados = "SELECT COUNT(DISTINCT id_estu) AS asignados 
                     FROM tutoria_detalle_sesion_curso 
                     WHERE id_cargalectiva = $id_cargalectiva";
    $resAsignados = $conexion->conexion->query($sqlAsignados);
    $asignados = ($resAsignados && $resAsignados->num_rows > 0) 
                 ? (int)$resAsignados->fetch_assoc()['asignados'] : 0;

    $row['asistentes'] = $asistentes;
    $row['asignados'] = $asignados;
    $row['porcentaje'] = ($asignados > 0) ? round(($asistentes / $asignados) * 100, 2) . '%' : '0%';

   if ($asignados > 1) {
    $sesionesGrupales[] = $row;
    } else {
        // Solo si se asignó 1 estudiante (no si asistió solo 1), es individual
        $sqlEst = "SELECT CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS asistente
                  FROM tutoria_detalle_sesion_curso d
                  INNER JOIN estudiante e ON e.id_estu = d.id_estu
                  WHERE d.sesiones_tutoria_id = " . $row['id_sesiones_tuto'] . " 
                    AND d.marcar_asis_estu = 1
                  LIMIT 1";
        $resEst = $conexion->conexion->query($sqlEst);
        $row['asistente'] = $resEst->fetch_assoc()['asistente'] ?? '—';
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

$conexion->cerrar();
?>



<style>
  h3 { background-color: #17a2b8; color: white; padding: 10px; border-radius: 6px; font-weight: bold; }
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
  
table.informe-tabla {
  width: 100%;
  border-collapse: collapse;
  margin: 15px 0;
  background-color: #fff;
  font-size: 14px;
  box-shadow: 0px 2px 5px rgba(0,0,0,0.05);
  border: 1px solid #ccc;
}

table.informe-tabla thead {
  background-color:rgb(184, 189, 219);
  color: black;
  font-weight: bold;
}

table.informe-tabla th,
table.informe-tabla td {
  border: 1px solid #ccc;
  padding: 8px 10px;
  text-align: center;
  vertical-align: middle;
}

table.informe-tabla tbody tr:nth-child(even) {
  background-color: #f9f9f9;
}

table.informe-tabla input[type="text"] {
  width: 100%;
  border: none;
  background: transparent;
  font-weight: bold;
  text-align: center;
}

table.informe-tabla input[readonly] {
  color: #333;
}
table.informe-tabla {
  width: 100%;
  border-collapse: collapse;
  margin: 15px 0;
  font-size: 14px;
  background: #fff;
  box-shadow: 0px 2px 5px rgba(0,0,0,0.05);
  border: 1px solid #ccc;
}

table.informe-tabla thead {
  background-color: rgb(184, 189, 219);
  color: black;
  font-weight: bold;
}

table.informe-tabla th,
table.informe-tabla td {
  border: 1px solid #ccc;
  padding: 8px 10px;
  text-align: center;
  vertical-align: middle;
}
.alert {
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 10px;
}
.alert-warning { background-color: #fff3cd; color: #856404; }
.alert-danger { background-color: #f8d7da; color: #721c24; }
.alert-success { background-color: #d4edda; color: #155724; }
.alert-info { background-color: #d1ecf1; color: #0c5460; }
</style>

<div class="documento">
  

  <form action="docente/guardar_informe_mensual_curso.php" method="POST">

    <?php
      if (isset($_SESSION['alerta_informe'])) {
          $alerta = $_SESSION['alerta_informe'];
          unset($_SESSION['alerta_informe']);

          if ($alerta === 'sin_sesiones') {
              echo "<div class='alert alert-warning'>⚠ No se encontraron sesiones registradas para el mes seleccionado.⚠</div>";
          } elseif ($alerta === 'faltan_campos') {
              echo "<div class='alert alert-danger'>❌ Complete todos los campos antes de enviar el informe.</div>";
          } elseif ($alerta === 'guardado') {
              echo "<div class='alert alert-success'>✅ Informe guardado correctamente.</div>";
          } elseif ($alerta === 'enviado') {
              echo "<div class='alert alert-success'>📤 Informe enviado correctamente.</div>";
          }
      }
      ?>
    <?php
        if (isset($_GET['guardado'])) {
            echo "<div style='margin-bottom: 15px; padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;'>✅ Informe guardado correctamente.</div>";
        } elseif (isset($_GET['enviado'])) {
            echo "<div style='margin-bottom: 15px; padding: 10px; background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 5px;'>📤 Informe enviado correctamente.</div>";
        }
    ?>
    <input type="hidden" name="id_cargalectiva" id="id_cargalectiva" value="<?= $id_cargalectiva ?>">
    <input type="hidden" name="mes" id="mes" value="<?= strtolower($mes_actual) ?>">
    <h3>INFORME MENSUAL DE TUTORÍA</h3>

    <!-- INFORME N° -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 10px;">
      <b>Informe Nº:</b> <input type="text" name="numero_informe" value="<?= $formato_informe ?>" 
      style="flex: 1; min-width: 250px;" <?= $bloquear ? 'readonly' : '' ?>>
    </div>

    <!-- PARA -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 10px;">
      <label style="min-width: 110px;"><b>Para:</b></label>
      <input 
        style="flex:1; min-width: 250px;" 
        readonly 
        type="text" 
        name="director" 
        value="<?= htmlspecialchars($nombre_director_escuela) ?>">
    </div>
    <p>Responsable de la Dirección de Escuela Profesional de <?= htmlspecialchars($datosCurso['nom_car']) ?>.</p>

    <!-- DE -->
    <div style="margin-bottom: 8px;"><b>De:</b> <?= htmlspecialchars($datosCurso['abreviatura_doce'].' '.$datosCurso['apepa_doce'].' '.$datosCurso['apema_doce'].' '.$datosCurso['nom_doce']) ?></div>

    <!-- ASIGNATURA -->
    <div style="margin-bottom: 8px;"><b>Asignatura:</b> <?= htmlspecialchars($datosCurso['nom_asi']) ?> - 
        <b>Ciclo:</b> <?= htmlspecialchars($datosCurso['ciclo']) ?> - 
        <b>Turno:</b> <?= htmlspecialchars($datosCurso['turno'].'-'.$datosCurso['seccion']) ?>
    </div>

    <!-- ASUNTO -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 10px;">
      Informe Nº: <input type="text" name="asunto" 
      value="Informe del Programa de Tutoría Universitaria - Mes de <?= strtolower($mes_actual) ?>" 
      style="flex: 1; min-width: 300px;" <?= $bloquear ? 'readonly' : '' ?>>
    </div>

    <!-- FECHA CONGELADA -->
    <p><strong>FECHA:</strong> <?= ucfirst($fecha_actual) ?></p>
    <hr>
    <p style="text-align: justify;">
      Me es grato dirigirme a usted, a fin de saludarle cordialmente y, mediante la presente remitir a su despacho el Informe mensual del cumplimiento del Plan de tutoría universitaria, correspondiente al mes de
      <input type="text" name="mes_informe" value="<?= $mes_actual ?>" style="width: 100px;" readonly> del ciclo académico 
      <input type="text" name="ciclo_informe" value=" <?= htmlspecialchars($datosCurso['ciclo']) ?> " style="width: 100px;" readonly>.
    </p>

    <p >Es de señalar que este Informe responde a los indicadores planteados:</p>
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
  Atentamente:<br><br>
  <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 40px;">
   
    <div style="min-width: 280px;">
       <?php if ($estado_envio == 2 && $fecha_envio_detalle): ?>
        <p style="font-style: italic; margin-top: 5px; font-size: 14px;">
          Fecha de Envío: <?= htmlspecialchars($fecha_envio_detalle) ?>
        </p>
      <?php endif; ?>
      <strong><?= htmlspecialchars($datosCurso['abreviatura_doce'] . ' ' . $datosCurso['apepa_doce'] . ' ' . $datosCurso['apema_doce'] . ' ' . $datosCurso['nom_doce']) ?></strong><br>
      <?= htmlspecialchars($datosCurso['email_doce']) ?><br>
    </div>
  </div>


    <h3 style="text-align: left;">1. OBJETIVO</h3>
    <li style="text-align: left;"> Facilitar la integración y adaptación de los estudiantes a la vida universitaria, 
        apoyando su desarrollo académico y personal. </li>
    <li style="text-align: left;"> Implementar asesorías individuales y grupales de manera eficiente, derivando 
        a especialistas cuando sea necesario. </li>
    <li style="text-align: left;"> Utilizar datos sobre rendimiento académico, deserción y asistencia para 
        mejorar las acciones de tutoría. </li>
    <li style="text-align: left;"> Evaluar periódicamente el programa de tutoría, elaborando informes 
        detallados y proponiendo mejoras para el avance educativo y profesional de 
        los estudiantes.</li>

    <h3 style="text-align: left;">2. REPORTE DE ASISTENCIA, ATENCIÓN Y SEGUIMIENTO</h3>
    <!-- Tabla para 2.1 Consejería y Tutoría Académica Grupal -->
    <h4 style="text-align: left;">2.1 Consejería y Tutoría Académica Grupal</h4>
        <table class="informe-tabla">
            <thead>
              <tr>
                <th>N°</th>
                <th>Fecha</th>
                <th>Tema</th>
                <th>Modalidad</th>
                <th>Asignados</th>
                <th>Asistentes</th>
                <th>% Participación</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($sesionesGrupales)): ?>
                <?php foreach ($sesionesGrupales as $i => $sesion): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($sesion['fecha']) ?></td>
                        <td><?= htmlspecialchars($sesion['tema']) ?></td>
                        <td><?= htmlspecialchars($sesion['modalidad']) ?></td>
                        <td><?= $sesion['asignados'] ?></td>
                        <td><?= $sesion['asistentes'] ?></td>
                        <td><?= $sesion['porcentaje'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No se registraron sesiones individuales en este mes.</td>
                      </tr>
                <?php endif; ?>
            </tbody>
        </table>
    

 <h4 style="text-align: left;">2.2 Consejería y Tutoría Académica Individual</h4>
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

   <h3 style="text-align: left;">3. ALUMNOS DERIVADOS A SERVICIOS ESPECIALIZADOS</h3>
    <table class="informe-tabla">
      <colgroup>
        <col style="width: 28%;"> <!-- Alumno -->
        <col style="width: 13%;"> <!-- Dirigido A -->
        <col style="width: 9%;"> <!-- Fecha -->
        <col style="width: 15%;"> <!-- Motivo -->
        <col style="width: 9%;"> <!-- Fecha Contra -->
        <col style="width: 15%;"> <!-- Responsable -->
        <col style="width: 15%;"> <!-- Resultados -->
      </colgroup>
      <thead>
        <tr>
          <th>Alumno</th>
          <th>Dirigido A</th>
          <th>Fecha</th>
          <th>Motivo</th>
          <th>Fecha Contra-Referencia</th>
          <th>Responsable</th>
          <th>Resultados</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($derivaciones)): ?>
          <?php foreach ($derivaciones as $row): ?>
            <tr>
              <td><input type="text" name="alumno[]" value="<?= htmlspecialchars($row['apepa_estu'] . ' ' . $row['apema_estu'] . ' ' . $row['nom_estu']) ?>" readonly></td>
              <td><input type="text" name="dirigido_a[]" value="<?= htmlspecialchars($row['dirigido_a'] ?? 'Pendiente') ?>" readonly></td>
              <td><input type="text" name="fecha_referencia[]" value="<?= htmlspecialchars($row['fechaDerivacion']) ?>" readonly></td>
              <td><input type="text" name="motivo[]" value="<?= htmlspecialchars($row['motivo_ref']) ?>" readonly></td>
              <td><input type="text" name="fecha_contra[]" value="<?= htmlspecialchars($row['fecha']) ?>" readonly></td>
              <td><input type="text" name="responsable[]" value="<?= htmlspecialchars($row['responsable']) ?>" readonly></td>
              <td><input type="text" name="resultados[]" value="<?= htmlspecialchars($row['resultado_contra'] ?? 'Pendiente') ?>" readonly></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">No se registraron derivaciones en este mes.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>


    <h3 style="text-align: left;">4. RESULTADOS</h3>
   <textarea name="resultados_finales" id="resultados_finales" rows="4" <?= $bloquear ? 'readonly' : '' ?>><?= htmlspecialchars($resultados_finales) ?></textarea>

    <h3 style="text-align: left;">5. LOGROS</h3>
    <textarea name="logros" id="logros" rows="4" <?= $bloquear ? 'readonly' : '' ?>><?= htmlspecialchars($logros) ?></textarea>

    <h3 style="text-align: left;">6. DIFICULTADES</h3>
    <textarea name="dificultades" id="dificultades" rows="4"  <?= $bloquear ? 'readonly' : '' ?>><?= htmlspecialchars($dificultades) ?></textarea>

    <h3 style="text-align: left;" >7. ANEXOS</h3>
    <p style="text-align: left;">- F-M01.04-VRA-006 Hoja de Referencia y Contrarreferencia</p>
    <p style="text-align: left;">- F-M01.04-VRA-007 Consejería y Tutoría Grupal</p>
    <p style="text-align: left;">- F-M01.04-VRA-008 Consejería y Tutoría Individual</p>
    
    <a style="background-color: #17a2b8; color:black; pading:2px" href="docente/vista_prev_informe_mensual.php?id_cargalectiva=<?= $id_cargalectiva ?>&mes=<?= $mes_num ?>" 
        class="btn btn-secondary" target="_blank"><strong>Vista Previa</strong>
    </a> 
<!--   <button style="background-color: #34f123ff; color:black; pading:2px"  type="button" class="btn btn-outline-primary btn-sm" onclick="generarResumenIA()">
        <i class="fa fa-magic"></i> <strong>Autocompletar</strong>
    </button>  -->
    <br><br>
        
    <?php if ($resVerifica && $resVerifica['estado_envio'] == 2): ?>
      <div style="background: #dff0d8; padding: 12px 18px; text-align: center; border-radius: 6px; color: #3c763d; font-weight: bold; margin: 25px 0;">
        ✅ Este informe ya fue enviado al Director de la Escuela Profesional de 
        <span style="text-transform: uppercase;"><?= strtoupper(htmlspecialchars($datosCurso['nom_car'])) ?></span>.
      </div>
    <?php endif; ?>

   <?php if ($resVerifica && $resVerifica['estado_envio'] == 2): ?>
  <!-- No mostrar botones si ya fue enviado -->
   
    <?php else: ?>

       
      <div class="actions mt-4">
        <input type="hidden" name="id_cargalectiva" value="<?= $id_cargalectiva ?>">
        <input type="hidden" name="mes_informe" value="<?= strtolower($mes_actual) ?>">

        <button type="submit" name="accion" value="guardar" class="btn btn-success">Guardar</button>

        <?php if ($ya_guardado): ?>
          <button type="submit" name="accion" value="enviar" class="btn btn-primary">Enviar</button>
        <?php else: ?>
          <button class="btn btn-primary" disabled title="Debe guardar el informe antes de poder enviarlo.">Enviar</button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    
   
  </form>
</div>
<script>
  //AUTOCOMPLETAR
function generarResumenIA() {
    const id_carga = document.getElementById('id_cargalectiva')?.value?.trim();
    const mes = document.getElementById('mes')?.value?.trim();

    if (!mes || !id_carga) {
        alert("⚠️ Selecciona un mes válido y asegúrate de tener ID de carga lectiva.");
        return;
    }

    const formData = new FormData();
    formData.append('id_cargalectiva', id_carga);
    formData.append('mes', mes);

    fetch('docente/autocompletar_informe_ia.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status} - ${res.statusText}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.error) {
            alert("⚠️ " + data.error);
        } else {
            document.getElementById('resultados_finales').value = data.resultados || '';
            document.getElementById('logros').value = data.logros || '';
            document.getElementById('dificultades').value = data.dificultades || '';
        }
    })
    .catch(err => {
        console.error("Error al generar resumen con IA:", err);
        alert("❌ Ocurrió un error inesperado. Revisa la consola o comunícate con soporte.");
    });
}

// Calcular % Participación
function calcularPorcentaje() {
  document.querySelectorAll('#tabla-grupal tbody tr').forEach(tr => {
    const asignados = parseInt(tr.querySelector('.asignados').value) || 0;
    const asistentes = parseInt(tr.querySelector('.asistentes').value) || 0;
    const porcentaje = tr.querySelector('.porcentaje');
    if (asignados > 0) {
      porcentaje.value = ((asistentes / asignados) * 100).toFixed(2) + '%';
    } else {
      porcentaje.value = '0%';
    }
  });
}

// Eventos
const inputs = document.querySelectorAll('.asignados, .asistentes');
inputs.forEach(input => input.addEventListener('input', calcularPorcentaje));
</script>
</div>

