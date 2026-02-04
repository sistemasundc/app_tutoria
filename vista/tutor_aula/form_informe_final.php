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

function nombreMesEspanol($numeroMes) {
    $meses = [
        4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$numeroMes] ?? '';
}
$semestre = $_SESSION['S_SEMESTRE'];
$id_cargalectiva = $_GET['id_cargalectiva'];
$id_docente_sesion = $_SESSION['S_IDUSUARIO']; 
$anio_actual = date("Y");
$mes_actual = nombreMesEspanol(date('n'));
$fecha_actual = date('d/m/Y');

$conexion = new conexion();
$conexion->conectar();

$sql = "SELECT cl.*, a.nom_asi, s.nomsemestre, c.nom_car, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce, d.dni_doce, d.email_doce
        FROM carga_lectiva cl
        INNER JOIN asignatura a ON cl.id_asi = a.id_asi
        INNER JOIN semestre s ON cl.id_semestre = s.id_semestre
        INNER JOIN carrera c ON cl.id_car = c.id_car
        INNER JOIN docente d ON cl.id_doce = d.id_doce
        WHERE cl.id_cargalectiva = ?";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("i", $id_cargalectiva);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();

//Facultad

$id_facultad = null;

if (in_array($datos['id_car'], [1,2,3])) {
    $id_facultad = 1;
} elseif ($datos['id_car'] == 4) {
    $id_facultad = 2;
} elseif ($datos['id_car'] == 5) {
    $id_facultad = 3;
}

$facultad = 'No definida';

if ($id_facultad) {
    $conexion = new conexion();
    $conexion->conectar();

    $sql_fac = "SELECT fac_nom FROM facultad WHERE id_fac = ?";
    $stmt_fac = $conexion->conexion->prepare($sql_fac);
    $stmt_fac->bind_param("i", $id_facultad);
    $stmt_fac->execute();
    $result_fac = $stmt_fac->get_result();
    if ($row_fac = $result_fac->fetch_assoc()) {
        $facultad = $row_fac['fac_nom'];
    }

}
//AULA ASIGNADA
$aula_asignada = 'No tiene aula asignada';

$sql_aula = "SELECT ciclo, turno, seccion
             FROM carga_lectiva
             WHERE id_cargalectiva = ?";
$stmt_aula = $conexion->conexion->prepare($sql_aula);
$stmt_aula->bind_param("i", $id_cargalectiva);
$stmt_aula->execute();
$res_aula = $stmt_aula->get_result();
if ($row_aula = $res_aula->fetch_assoc()) {
    $aula_asignada = 'CICLO ' . htmlspecialchars($row_aula['ciclo']) .
                     ' - TURNO ' . htmlspecialchars($row_aula['turno']) .
                     ' - SECCIÓN ' . htmlspecialchars($row_aula['seccion']);
}

//TOTAL DE TUTORADOS 
$total_estudiantes = 0;

$sql_count = "SELECT COUNT(*) AS total
              FROM tutoria_asignacion_tutoria
              WHERE id_docente = ?
              AND id_carga = ?
              AND id_semestre = ?";
$stmt_count = $conexion->conexion->prepare($sql_count);
$stmt_count->bind_param(
    "iii",
    $id_docente_sesion,
    $id_cargalectiva,
    $semestre
);
$stmt_count->execute();
$res_count = $stmt_count->get_result();

if ($row_count = $res_count->fetch_assoc()) {
    $total_estudiantes = $row_count['total'];
}

//TOTAL DE SESIONES REGISTRADAS


//TOTAL DE SESIONES EJECUTADAS
//TOTAL DE SESIONES EJECUTADAS (sólo semestre actual)
$total_sesiones = 0;

$sql_sesiones = "SELECT COUNT(*) AS total
                 FROM tutoria_sesiones_tutoria_f78
                 WHERE id_doce = ?
                   AND id_rol = 6
                   AND color = '#00a65a'
                   AND id_semestre = ?
                   /* AND id_cargalectiva = ? */";
$stmt_sesiones = $conexion->conexion->prepare($sql_sesiones);

// Si NO usas id_cargalectiva en esa tabla, deja sólo 2 parámetros:
$stmt_sesiones->bind_param(
    "ii",
    $id_docente_sesion,
    $semestre
);

// Si SÍ existe id_cargalectiva en tutoria_sesiones_tutoria_f78, usa esta en su lugar:
// $stmt_sesiones->bind_param("iii", $datos['id_doce'], $semestre, $id_cargalectiva);

$stmt_sesiones->execute();
$res_sesiones = $stmt_sesiones->get_result();

if ($row_sesiones = $res_sesiones->fetch_assoc()) {
    $total_sesiones = $row_sesiones['total'];
}


//guardao
$guardado = false;
$datos_guardados = [];

$sql_check = "SELECT * FROM tutoria_informe_final_aula 
              WHERE id_cargalectiva = ? AND id_doce = ? AND semestre_id = ?";
$stmt_check = $conexion->conexion->prepare($sql_check);
$stmt_check->bind_param("iii", $id_cargalectiva,  $id_docente_sesion, $semestre);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check && $fila = $result_check->fetch_assoc()) {
    $guardado = true;
    $datos_guardados = $fila;
    $estado_envio = (int) $fila['estado_envio']; // Mover aquí
} else {
    $estado_envio = 1; // Por defecto, si no hay fila, es nuevo y no enviado
}
//DTAOS DOCENTE POR SESION
$sql_docente = "SELECT 
                    abreviatura_doce,
                    apepa_doce,
                    apema_doce,
                    nom_doce,
                    dni_doce,
                    email_doce
                FROM docente
                WHERE id_doce = ?";

$stmt_doc = $conexion->conexion->prepare($sql_docente);
$stmt_doc->bind_param("i", $id_docente_sesion);
$stmt_doc->execute();
$docenteSesion = $stmt_doc->get_result()->fetch_assoc();

if (!$docenteSesion) {
    die('No se encontraron datos del docente en sesión');
}

$conexion->cerrar();
?>

<style>
  * { box-sizing: border-box; }

  body {font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 0;}
  .documento {max-width: 1080px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);}
  h3{font-size: 17px;}
  h4{ font-size: 14px;}
  h3, h4 {  background-color: #0056b3; color: white; padding: 10px; border-radius: 4px; font-weight: bold;}
  input[type="text"], input[type="number"], textarea { width: 100%;  padding: 8px;  margin-top: 5px;  margin-bottom: 15px;  border: 1px solid #ccc;  border-radius: 4px;  font-size: 14px;}
  table {  width: 100%;  border-collapse: collapse;  overflow-x: auto;}
  table th, table td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 13px; min-width: 150px;}
  ul li { margin-bottom: 8px;}
  .btn { padding: 10px 16px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; margin: 5px;}
  .btn-success {  background-color: #28a745;  color: white;}
  .btn-primary { background-color: #007bff; color: white;}
  @media (max-width: 768px) {
    .documento {   padding: 15px }
    table th, table td {   font-size: 12px;   min-width: 120px; }
    input[type="text"], textarea {   font-size: 13px; }
  }
.tabla-generales {  width: 100%;  border-collapse: collapse;  table-layout: auto;}
.tabla-generales td:first-child {  width: 35%;  background: #f9f9f9;  font-weight: bold;}
.tabla-generales td:last-child {
  /* width: 65%; */
}
.tabla-generales td:last-child input[type="text"],
.tabla-generales td:last-child input[type="number"] {  width: 100%; padding: 6px;  border: 1px solid #ccc;  border-radius: 4px;  background-color: #fff;}
.tabla-generales td:last-child {  width: 65%;}
</style>

<div class="documento">
  <!-- ALERTAS -->
<?php if (isset($_GET['estado'])): ?>
  <div style="...">
    <?php if ($_GET['estado'] === 'guardado'): ?>
      ✅ Informe guardado con éxito.
    <?php elseif ($_GET['estado'] === 'enviado'): ?>
      📤 Informe enviado con éxito.
    <?php endif; ?>
  </div>
<?php endif; ?>
  <form method="POST" action="tutor_aula/guardar_informe_final.php">
    <h3>INFORME FINAL DE CUMPLIMIENTO DEL PLAN DE TUTORÍA Y CONSEJERÍA</h3>
    <input type="hidden" name="id_cargalectiva" value="<?= $id_cargalectiva ?>">
    <h4>1. DATOS GENERALES DEL INFORME</h4>
    <div class="table-responsive">
      <table class="tabla-generales">
        <tbody>
          <tr><td><strong>Facultad</strong></td><td>FACULTAD DE <?= htmlspecialchars($facultad) ?></td></tr>
          <tr><td><strong>Escuela Profesional</strong></td><td><?= htmlspecialchars($datos['nom_car']) ?></td></tr>
          <tr><td><strong>Semestre Académico</strong></td><td><?= htmlspecialchars($datos['nomsemestre']) ?></td></tr>
          <tr><td><strong>Modalidad de la tutoría</strong></td><td>GRUPAL E INDIVIDUAL</td></tr>
          <tr><td><strong>Nombre del docente tutor(a)</strong></td><td><?= htmlspecialchars( $docenteSesion['abreviatura_doce'].' '. $docenteSesion['apepa_doce'].' '. $docenteSesion['apema_doce'].' '. $docenteSesion['nom_doce']) ?></td></tr>
          <tr><td><strong>DNI del docente tutor(a)</strong></td><td><?= htmlspecialchars( $docenteSesion['dni_doce']) ?></td></tr>
          <tr><td><strong>Tipo de tutoría </strong></td><td>AULA</td></tr>
          <tr><td><strong>Aula asignada</strong></td><td><?= $aula_asignada ?></td></tr>
          <tr><td><strong>Total estudiantes a cargo</strong></td><td><?= $total_estudiantes ?></td></tr>
          <tr><td><strong>Total sesiones planificadas</strong></td><td>4</td></tr>
          <tr><td><strong>Total sesiones ejecutadas</strong></td><td><?= $total_sesiones ?></td></tr>
          <?php
            $fecha_valida = $datos_guardados['fecha_presentacion'] ?? date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_valida)) {
                $fecha_valida = date('Y-m-d'); // En caso venga algo como '2025' solo
            }
            ?>
            <tr>
              <td><strong>Fecha de presentación del informe</strong></td>
              <td><input type="date" name="fecha_presentacion" value="<?= $fecha_valida ?>"></td>
            </tr>
        </tbody>
      </table>
    </div>
     <input type="hidden" name="aula_asignada" value="<?= $aula_asignada ?>">
    <input type="hidden" name="total_estudiantes" value="<?= $total_estudiantes ?>">
    <input type="hidden" name="total_ejecutadas" value="<?= $total_sesiones ?>">
    <div>
      <h4>2. ACTIVIDADES EJECUTADAS</h4>
      <?php
      $conexion->conectar();

      // Consulta con campos explícitos para evitar conflictos
      $sql_sesiones = "
          SELECT 
              t.id_sesiones_tuto,
              t.fecha,
              t.horainicio,
              t.horaFin,
              t.tema,
              t.observacione,
              t.compromiso_indi,
              t.reunion_tipo_otros,
              t.link,
              t.evidencia_1,
              t.evidencia_2,
              ts.des_tipo AS modalidad
          FROM tutoria_sesiones_tutoria_f78 t
          LEFT JOIN tutoria_tipo_sesion ts ON t.tipo_sesion_id = ts.id_tipo_sesion
          WHERE t.id_doce = ?
            AND t.id_rol = 6
            AND t.color = '#00a65a'
            AND t.id_semestre = ?
          ORDER BY t.fecha ASC
      ";

      $stmt_sesiones = $conexion->conexion->prepare($sql_sesiones);
      $stmt_sesiones->bind_param("ii", $id_docente_sesion, $semestre);

        $stmt_sesiones->execute();
        $res_sesiones = $stmt_sesiones->get_result();



      $n = 1;
      while ($sesion = $res_sesiones->fetch_assoc()) {
          // Asegurar que las horas existan
          $hora_inicio = $sesion['horainicio'] ?? null;
          $hora_fin    = $sesion['horaFin'] ?? null;

          // Calcular duración si ambas horas están presentes y válidas
          if ($hora_inicio && $hora_fin && $hora_inicio !== '00:00:00' && $hora_fin !== '00:00:00') {
              $inicio = strtotime($hora_inicio);
              $fin    = strtotime($hora_fin);

              if ($inicio !== false && $fin !== false && $fin > $inicio) {
                  $diferencia_segundos = $fin - $inicio;
                  $horas   = floor($diferencia_segundos / 3600);
                  $minutos = floor(($diferencia_segundos % 3600) / 60);

                  if ($horas > 0 && $minutos > 0) {
                      $duracion = "{$horas} horas {$minutos} minutos";
                  } elseif ($horas > 0) {
                      $duracion = "{$horas} hora" . ($horas > 1 ? "s" : "");
                  } elseif ($minutos > 0) {
                      $duracion = "{$minutos} minutos";
                  } else {
                      $duracion = "0 minutos";
                  }
              } else {
                  $duracion = "Hora no registrada";
              }
          } else {
              $duracion = "Hora no registrada";
          }

          // Obtener número de participantes
          $sql_part = "
              SELECT COUNT(*) AS total 
              FROM tutoria_detalle_sesion 
              WHERE sesiones_tutoria_id = ?
          ";
          $stmt_part = $conexion->conexion->prepare($sql_part);
          $stmt_part->bind_param("i", $sesion['id_sesiones_tuto']);
          $stmt_part->execute();
          $res_part = $stmt_part->get_result();
          $row_part = $res_part->fetch_assoc();
          $participantes = $row_part['total'] ?? 0;

          // Mostrar bloque HTML
          echo "<div style='border:1px solid #ccc; margin-bottom:15px; padding:10px; border-radius:5px;'>";
          echo "<h5 style='color:#0056b3;'><strong>Actividad N° {$n}</strong></h5>";
          echo "<p><b>Tema abordado:</b> " . htmlspecialchars($sesion['tema']) . "</p>";
          echo "<p><b>Fecha de realización:</b> " . htmlspecialchars($sesion['fecha']) . "</p>";
          echo "<p><b>Duración:</b> {$duracion}</p>";
          echo "<p><b>Número de estudiantes participantes:</b> {$participantes}</p>";
          echo "<p><b>Modalidad:</b> " . htmlspecialchars($sesion['modalidad'] ?? 'Presencial') . "</p>";
          echo "<p><b>Compromiso individual:</b> " . htmlspecialchars($sesion['compromiso_indi'] ?? '-') . "</p>";
          echo "<p><b>Observaciones:</b> " . htmlspecialchars($sesion['observacione'] ?? '-') . "</p>";
          echo "<p><b>Evidencia generada:</b><br>";
          if (!empty($sesion['evidencia_1'])) {
              echo "<img src='/" . htmlspecialchars($sesion['evidencia_1']) . "' alt='Evidencia 1' style='max-width:150px; margin:5px;'>";
          }
          if (!empty($sesion['evidencia_2'])) {
              echo "<img src='/" . htmlspecialchars($sesion['evidencia_2']) . "' alt='Evidencia 2' style='max-width:150px; margin:5px;'><br>";
          }
          echo "</p>";
          echo "</div>";

          $n++;
      }

      $conexion->cerrar();
      ?>

    </div>
    <div>
      <h4>3. RESULTADOS ALCANZADOS</h4>
      <textarea name="resultados" rows="6" placeholder="Ejemplo: 80% de estudiantes atendidos..." required><?= htmlspecialchars($datos_guardados['resultados'] ?? '') ?></textarea>
    </div>
    <div>
    <h4>4. DIFICULTADES IDENTIFICADAS</h4>
      <textarea name="dificultades" rows="6" placeholder="Detalle aquí los factores internos y externos..." required><?= htmlspecialchars($datos_guardados['dificultades'] ?? '') ?></textarea>
    </div>
    <div>
      <h4>5. PROPUESTAS DE MEJORA</h4>
      <textarea name="propuesta" rows="5" placeholder="Detalle aquí sus propuestas de mejora..." required><?= htmlspecialchars($datos_guardados['propuesta_mejora'] ?? '') ?></textarea>
    </div>
    <div>
    <h4>6. CONCLUSIONES GENERALES</h4>
      <textarea name="conclusiones" rows="5" placeholder="Detalle aquí las conclusiones generales..." required><?= htmlspecialchars($datos_guardados['conclusiones'] ?? '') ?></textarea>
    </div>
    <div>
      <h4>7. ANEXOS</h4>
      <ul>
        <li>Registro de Consejería y Tutoría Grupal</li>
        <li>Registro de Consejería y Tutoría Individual</li>
        <li>Formularios de derivación</li>
      </ul>
    </div>
    <div style='text-align:center;'>
      <p> <?= htmlspecialchars( $docenteSesion['abreviatura_doce'].' '. $docenteSesion['apepa_doce'].' '. $docenteSesion['apema_doce'].' '. $docenteSesion['nom_doce']) ?></p>
      <p> <em><?= htmlspecialchars( $docenteSesion['email_doce']) ?></em></p>
    </div>
    <br>
    <?php if ($estado_envio === 2): ?>
        <div style="text-align:center; background:#dff0d8; padding:15px; border-radius:6px; font-weight:bold; color:#3c763d; margin-top:20px;">
          Este informe ya fue enviado al Director de la Escuela Profesional de <strong><?= strtoupper(htmlspecialchars($datos['nom_car'])) ?></strong>.
        </div>
      <?php else: ?>
        <div style="text-align: center;">
          <button type="submit" name="btn_guardar" class="btn btn-success">Guardar</button>
          <button type="submit" name="btn_enviar" class="btn btn-primary">Enviar</button>
        </div>

        <?php if (!$guardado): ?>
          <div style="text-align:center; margin-top:10px;">
            <span style="color:#d9534f; font-weight:bold;">
              ⚠️ Debe guardar el informe antes de poder enviarlo.
            </span>
          </div>
        <?php endif; ?>
    <?php endif; ?>
  </form>
</div>
