<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
date_default_timezone_set('America/Lima');
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['TUTOR DE AULA', 'DIRECCION DE ESCUELA', 'SUPERVISION', 'COORDINADOR GENERAL DE TUTORIA', 'DIRECTOR DE DEPARTAMENTO ACADEMICO', 'VICEPRESIDENCIA ACADEMICA','COMITÉ - SUPERVISIÓN'])) {
  die('Acceso no autorizado');
}

if (!isset($_GET['id_cargalectiva'])) {
    die('No se proporcionó el ID de carga lectiva.');
}
function nombreMesEspanol($numeroMes) {
    $meses = [
        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$numeroMes] ?? '';
}
$semestre = $_SESSION['S_SEMESTRE'];
$id_cargalectiva = $_GET['id_cargalectiva'];
$anio_actual = date("Y");
$mes_actual = nombreMesEspanol(date('n'));
$fecha_actual = date('d/m/Y');

$rol_usuario = $_SESSION['S_ROL'] ?? '';
$id_doce_informe = $_SESSION['S_IDUSUARIO'];
$id_doce_sesion  = $_SESSION['S_IDUSUARIO'];

if ($rol_usuario !== 'TUTOR DE AULA' && isset($_GET['id_docente'])) {
    $id_doce_informe = (int) $_GET['id_docente'];
}

if ($rol_usuario !== 'TUTOR DE AULA') {
    if (isset($_GET['id_docente'])) {
        $id_doce_sesion = $_GET['id_docente'];
    } elseif (isset($_GET['id_plan_tutoria'])) {
        $id_plan = intval($_GET['id_plan_tutoria']);
        $stmt_doc = $conexion->conexion->prepare("SELECT id_doce FROM tutoria_informe_final_aula WHERE id_plan_tutoria = ?");
        $stmt_doc->bind_param("i", $id_plan);
        $stmt_doc->execute();
        $stmt_doc->bind_result($id_doce_sesion);
        $stmt_doc->fetch();
        $stmt_doc->close();
    }
}

$conexion = new conexion();
$conexion->conectar();
$id_cargalectiva = $_GET['id_cargalectiva'] ?? null;
$id_plan_tutoria = $_GET['id_plan_tutoria'] ?? null;
$sql = "
SELECT 
    cl.*,
    a.nom_asi,
    s.nomsemestre,
    c.nom_car,

    -- DOCENTE DEL INFORME
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.dni_doce,
    d.email_doce

FROM tutoria_informe_final_aula i

INNER JOIN carga_lectiva cl 
    ON cl.id_cargalectiva = i.id_cargalectiva

INNER JOIN docente d 
    ON d.id_doce = i.id_doce

INNER JOIN asignatura a 
    ON cl.id_asi = a.id_asi

INNER JOIN semestre s 
    ON cl.id_semestre = s.id_semestre

INNER JOIN carrera c 
    ON cl.id_car = c.id_car

WHERE i.id_cargalectiva = ?
  AND i.id_doce = ?
  AND i.semestre_id = ?
";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("iii", $id_cargalectiva, $id_doce_informe, $semestre);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();

if (!$datos) {
    die("No existe informe final registrado para este docente.");
}

$id_doce = $datos['id_doce'];

// Usar SIEMPRE el semestre de la carga lectiva del informe
$semestre = isset($datos['id_semestre']) ? (int)$datos['id_semestre'] : (int)$_SESSION['S_SEMESTRE'];

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
$stmt_count->bind_param("iii", $id_doce_sesion, $id_cargalectiva, $semestre);
$stmt_count->execute();
$res_count = $stmt_count->get_result();

if ($row_count = $res_count->fetch_assoc()) {
    $total_estudiantes = $row_count['total'];
}


//TOTAL DE SESIONES EJECUTADAS
$total_sesiones = 0;

$sql_sesiones = "SELECT COUNT(*) AS total
                 FROM tutoria_sesiones_tutoria_f78
                 WHERE id_doce = ?
                   AND id_rol = 6
                   AND color = '#00a65a'
                   AND id_semestre = ?";
$stmt_sesiones = $conexion->conexion->prepare($sql_sesiones);
$stmt_sesiones->bind_param("ii", $id_doce_sesion, $semestre);
$stmt_sesiones->execute();
$res_sesiones = $stmt_sesiones->get_result();

if ($row_sesiones = $res_sesiones->fetch_assoc()) {
    $total_sesiones = $row_sesiones['total'];
}


//guardao
$guardado = false;
$datos_guardados = [];

$sql_check = "
SELECT * 
FROM tutoria_informe_final_aula
WHERE id_cargalectiva = ?
  AND id_doce = ?
  AND semestre_id = ?
";

$stmt_check = $conexion->conexion->prepare($sql_check);
$stmt_check->bind_param("iii", $id_cargalectiva, $id_doce_informe, $semestre);
$stmt_check->execute();
$result_check = $stmt_check->get_result();


if ($result_check && $fila = $result_check->fetch_assoc()) {
    $guardado = true;
    $datos_guardados = $fila;
    $estado_envio = (int) $fila['estado_envio']; // Mover aquí
} else {
    $estado_envio = 1; // Por defecto, si no hay fila, es nuevo y no enviado
}

// ================================================== ANEXOS =======================================
   $sesionesGrupales = [];
    $sesionesIndividuales = [];
    $derivaciones = [];


    $tiene_grupal = false;
    $tiene_individual = false;
    $tiene_derivacion = false;
    $tiene_contraref = false;

    // F7 - Tutoría Grupal (más de 1 estudiante)
    $sqlF7 = "
        SELECT COUNT(*) AS total
        FROM tutoria_sesiones_tutoria_f78 s
        WHERE s.id_rol = 6 
          AND s.color = '#00a65a'
          AND s.id_doce = ?
          AND s.id_semestre = ?
    ";
    $stmtF7 = $conexion->conexion->prepare($sqlF7);
    $stmtF7->bind_param("ii", $id_doce_sesion, $semestre);
    if ($stmtF7->execute()) {
        $resF7 = $stmtF7->get_result()->fetch_assoc();
        $tiene_grupal = ($resF7['total'] > 0);
    }

    // F8 - Tutoría Individual (1 estudiante)
    $sqlF8 = "
        SELECT COUNT(*) AS total
        FROM tutoria_sesiones_tutoria_f78 s
        JOIN tutoria_detalle_sesion d ON s.id_sesiones_tuto = d.sesiones_tutoria_id
        WHERE s.id_rol = 6 
          AND s.color = '#3c8dbc'
          AND s.id_doce = ?
          AND s.id_semestre = ?
    ";
    $stmtF8 = $conexion->conexion->prepare($sqlF8);
    $stmtF8->bind_param("ii", $id_doce_sesion, $semestre);

    if ($stmtF8->execute()) {
        $resF8 = $stmtF8->get_result()->fetch_assoc();
        $tiene_individual = ($resF8['total'] > 0);
    }

   // F6 - Derivaciones realizadas
    $sqlF6 = "
        SELECT 
            d.fecha AS fechaDerivacion,
            d.hora AS horaDerivacion,
            d.motivo_ref,
            d.area_apoyo_id,
            e.apepa_estu, e.apema_estu, e.nom_estu,
            doc.abreviatura_doce,
            doc.apepa_doce,
            doc.apema_doce,
            doc.nom_doce,
            doc.email_doce
        FROM tutoria_derivacion_tutorado_f6 d
        INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
        INNER JOIN docente doc ON doc.id_doce = d.id_docente
        WHERE d.id_rol = 6
          AND d.id_docente = ?
          AND d.id_semestre = ?
    ";
    $stmtF6 = $conexion->conexion->prepare($sqlF6);
    $stmtF6->bind_param("ii", $id_doce_sesion, $semestre);
    $stmtF6->execute();
    $resF6 = $stmtF6->get_result();

    $data_derivaciones = [];
    while ($row = $resF6->fetch_assoc()) {
        $data_derivaciones[] = $row;
    }

    $tiene_derivacion = count($data_derivaciones) > 0;

    // F6 - Contrarreferencias respondidas
    $data_contraref = []; // arreglo con cada contrarreferencia del mes

    $sqlContraRef = "
        SELECT 
            d.resultado_contra,
            e.apepa_estu, e.apema_estu, e.nom_estu,
            doc.abreviatura_doce, doc.apepa_doce, doc.apema_doce, doc.nom_doce, doc.email_doce,
            a.area_apoyocol,
            a.ape_encargado,
            a.nombre_encargado,
            u.cor_inst AS correo_responsable
        FROM tutoria_derivacion_tutorado_f6 d
        INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
        INNER JOIN docente doc ON doc.id_doce = d.id_docente
        LEFT JOIN tutoria_area_apoyo a ON a.idarea_apoyo = d.area_apoyo_id
        LEFT JOIN tutoria_usuario u ON u.id_usuario = a.id_personal_apoyo
        WHERE d.resultado_contra IS NOT NULL
          AND d.resultado_contra != ''
          AND d.id_rol = 6
          AND d.id_docente = ?
          AND d.id_semestre = ?
    ";
    $stmtContra = $conexion->conexion->prepare($sqlContraRef);
    $stmtContra->bind_param("ii", $id_doce_sesion, $semestre);

    $stmtContra->execute();
    $resContra = $stmtContra->get_result();

    while ($row = $resContra->fetch_assoc()) {
        $row['nombre_estudiante'] = strtoupper("{$row['apepa_estu']} {$row['apema_estu']} {$row['nom_estu']}");
        $row['nombre_tutor'] = strtoupper("{$row['abreviatura_doce']} {$row['apepa_doce']} {$row['apema_doce']} {$row['nom_doce']}");
        $row['correo_tutor'] = $row['email_doce'];

        // Responsable
        $row['nombre_responsable'] = strtoupper("{$row['ape_encargado']} {$row['nombre_encargado']}");
        $row['correo_responsable'] = $row['correo_responsable'] ?? '';

        $data_contraref[] = $row;
    }

    $tiene_contraref = count($data_contraref) > 0;
// Anexos 
$tiene_grupal = $tiene_grupal ?? false;
$tiene_individual = $tiene_individual ?? false;
$tiene_derivacion = count($data_derivaciones) > 0;
$tiene_contraref = count($data_contraref) > 0;

//CONSULTA PA MOSTRAR QUIEN DIO CONFORMIDAD
$conexion = new conexion();
$conexion->conectar();

$sql_rev = "SELECT r.fecha_revision, r.nombre_director, u.cor_inst
            FROM tutoria_revision_director_informe_final r
            LEFT JOIN tutoria_usuario u ON u.id_usuario = r.id_director
            WHERE r.id_cargalectiva = ? 
              AND r.id_semestre = ?
              AND UPPER(r.estado_revision) = 'CONFORME'
            ORDER BY r.id_revision DESC
            LIMIT 1";

$stmt_rev = $conexion->conexion->prepare($sql_rev);
$stmt_rev->bind_param("ii", $id_cargalectiva, $semestre);
$stmt_rev->execute();
$res_rev = $stmt_rev->get_result();
$revision = $res_rev->fetch_assoc();
$conexion->cerrar();
?>
<title>Informe Final - Tutor de Aula</title>
<style>
  * { box-sizing: border-box; }

  body {
    font-family: Arial, sans-serif;
    background: #f8f9fa;
    margin: 0;
    padding: 0;
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
  h3{
    font-size: 17px;
  }
  h4{
    font-size: 14px;
  }
  /* Ajustes para impresión */
@media print {
  body {
    background: white !important;
    margin: 0;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .documento {
    margin: 0;
    padding: 0;
    box-shadow: none !important;
    page-break-after: always;
    width: 100%;
  }

  .boton-imprimir {
    display: none !important;
  }

  @page {
    size: A4 portrait;
    margin: 15mm 20mm 20mm 20mm;
  }
}

  input[type="text"], input[type="number"], textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    overflow-x: auto;

  }

  table th, table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
    font-size: 13px;
    min-width: 150px;
  }

  ul li {
    margin-bottom: 8px;

  }

  .btn {
    padding: 10px 16px;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
    margin: 5px;
  }

  .btn-success {
    background-color: #28a745;
    color: white;
  }

  .btn-primary {
    background-color: #007bff;
    color: white;
  }

  @media (max-width: 768px) {
    .documento {
      padding: 15px;
    }

    table th, table td {
      font-size: 12px;
      min-width: 120px;
    }

    input[type="text"], textarea {
      font-size: 13px;
    }
  }

.tabla-generales {
  width: 100%;
  border-collapse: collapse;
  table-layout: auto;
}

.tabla-generales td:first-child {
  width: 35%;
  background: #f9f9f9;
  font-weight: bold;
}
.tabla-generales td:last-child {
  /* width: 65%; */
}


.tabla-generales td:last-child input[type="text"],
.tabla-generales td:last-child input[type="number"] {
  width: 100%;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #fff;
}

.tabla-generales td:last-child {
  width: 65%;
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
<body>
  

<div class="documento">
       <div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">
        🖨️
      </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px;">
      <!-- Logo a la izquierda -->
      <div style="margin-right: 20px;">
        <img src="../../img/undc.png" alt="Logo UNDC" style="width: 180px;">
      </div>

      <!-- Tabla a la derecha -->
      <table style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px;">
        <tr>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: left; border: 1px solid #000; width: 300px;">
            <strong>Código:</strong> F-M01.04-VRA-019<br>
            <strong>Fecha de Aprobación:</strong> 22/04/2025
          </td>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: center; border: 1px solid #000; width: 80px;">
            <strong>Versión:</strong> 01
          </td>
        </tr>
      </table>
    </div>
    <br><br>
    <h3>INFORME FINAL DE CUMPLIMIENTO DEL PLAN DE TUTORÍA Y CONSEJERÍA</h3>
    <input type="hidden" name="id_cargalectiva" value="<?= $id_cargalectiva ?>">
    <h4>1. DATOS GENERALES DEL INFORME</h4>
    <div class="table-responsive">
      <table class="tabla-generales">
        <tbody>
          <tr><td style="text-align: center;background-color: #006400;color: white;" >ÍTEM</td> <td style="text-align: center;background-color: #006400;color: white;">INFORMACIÓN</td></tr>
          <tr><td><strong>Facultad</strong></td><td>FACULTAD DE <?= htmlspecialchars($facultad) ?></td></tr>
          <tr><td><strong>Escuela Profesional</strong></td><td><?= htmlspecialchars($datos['nom_car']) ?></td></tr>
          <tr><td><strong>Semestre Académico</strong></td><td><?= htmlspecialchars($datos['nomsemestre']) ?></td></tr>
          <tr><td><strong>Modalidad de la tutoría</strong></td><td>GRUPAL E INDIVIDUAL</td></tr>
          <tr><td><strong>Nombre del docente tutor(a)</strong></td><td><?= htmlspecialchars($datos['abreviatura_doce'].' '.$datos['apepa_doce'].' '.$datos['apema_doce'].' '.$datos['nom_doce']) ?></td></tr>
          <tr><td><strong>DNI del docente tutor(a)</strong></td><td><?= htmlspecialchars($datos['dni_doce']) ?></td></tr>
          <tr><td><strong>Tipo de tutoría </strong></td><td>AULA</td></tr>
          <tr><td><strong>Aula asignada</strong></td><td><?= $aula_asignada ?></td></tr>
          <tr><td><strong>Total estudiantes a cargo</strong></td><td><?= $total_estudiantes ?></td></tr>
          <tr><td><strong>Total sesiones planificadas</strong></td><td>4</td></tr>
          <tr><td><strong>Total sesiones ejecutadas</strong></td><td><?= $total_sesiones ?></td></tr>
          <?php
          // Por defecto, sin fecha
          $fecha_valida = null;

          // Si el informe ya está guardado y tiene fecha de presentación
          if (
              $guardado &&
              !empty($datos_guardados['fecha_presentacion']) &&
              $datos_guardados['fecha_presentacion'] !== '0000-00-00'
          ) {
              // Puede venir como 'YYYY-MM-DD' o 'YYYY-MM-DD HH:MM:SS'
              $fecha_bd = substr($datos_guardados['fecha_presentacion'], 0, 10);

              if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_bd)) {
                  $fecha_valida = $fecha_bd;
              }
          }
          ?>
          <tr>
            <td><strong>Fecha de presentación del informe</strong></td>
            <td>
              <?= $fecha_valida ? date('d/m/Y', strtotime($fecha_valida)) : '--'; ?>
            </td>
          </tr>

        </tbody>
      </table>
    </div>
     <input type="hidden" name="aula_asignada" value="<?= $aula_asignada ?>">
    <input type="hidden" name="total_estudiantes" value="<?= $total_estudiantes ?>">
    <input type="hidden" name="total_ejecutadas" value="<?= $total_sesiones ?>">

</div>
<div class="documento">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px;">
      <!-- Logo a la izquierda -->
      <div style="margin-right: 20px;">
        <img src="../../img/undc.png" alt="Logo UNDC" style="width: 180px;">
      </div>

      <!-- Tabla a la derecha -->
      <table style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px;">
        <tr>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: left; border: 1px solid #000; width: 300px;">
            <strong>Código:</strong> F-M01.04-VRA-019<br>
            <strong>Fecha de Aprobación:</strong> 22/04/2025
          </td>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: center; border: 1px solid #000; width: 80px;">
            <strong>Versión:</strong> 01
          </td>
        </tr>
      </table>
    </div>
    <br><br>
        <h4>2. ACTIVIDADES EJECUTADAS</h4>
        <p> </p>
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
      $stmt_sesiones->bind_param("ii", $id_doce_sesion, $semestre);

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

</div>
<div class="documento">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 10px;">
      <!-- Logo a la izquierda -->
      <div style="margin-right: 20px;">
        <img src="../../img/undc.png" alt="Logo UNDC" style="width: 180px;">
      </div>

      <!-- Tabla a la derecha -->
      <table style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 13px;">
        <tr>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: left; border: 1px solid #000; width: 300px;">
            <strong>Código:</strong> F-M01.04-VRA-019<br>
            <strong>Fecha de Aprobación:</strong> 22/04/2025
          </td>
          <td style="background-color: #006400; color: white; padding: 6px 10px; text-align: center; border: 1px solid #000; width: 80px;">
            <strong>Versión:</strong> 01
          </td>
        </tr>
      </table>
    </div>
    <br><br>
    <div>
      <h4>3. RESULTADOS ALCANZADOS</h4>
      <p style="text-align: Justify; padding: 6px 10px; line-height:30px;"><?= nl2br(htmlspecialchars($datos_guardados['resultados'] ?? '')) ?></p>
      <h4>4. DIFICULTADES IDENTIFICADAS</h4>
      <p style="text-align: Justify; padding: 6px 10px; line-height:30px;"><?= nl2br(htmlspecialchars($datos_guardados['dificultades'] ?? '')) ?></p>
      <h4>5. PROPUESTAS DE MEJORA</h4>
      <p style="text-align: Justify; padding: 6px 10px; line-height:30px;"><?= nl2br(htmlspecialchars($datos_guardados['propuesta_mejora'] ?? '')) ?></p>
      <h4>6. CONCLUSIONES GENERALES</h4>
      <p style="text-align: Justify; padding: 6px 10px; line-height:30px;" ><?= nl2br(htmlspecialchars($datos_guardados['conclusiones'] ?? '')) ?></p>
    </div>
    <br>
    <br>
    <br>
    <div style="display: flex; justify-content: space-between; padding: 20px 10px; margin-top: 40px; font-size: 14px;">
      <!-- Tutor -->
      <div style="width: 45%; text-align: center;  padding-top: 10px;">
        <p>
          <em><strong> Fecha de envío:</strong>
            <?= $fecha_valida ? date('d/m/Y', strtotime($fecha_valida)) : '--'; ?>
          </em>
        </p>
        <p><?= htmlspecialchars($datos['abreviatura_doce'].' '.$datos['apepa_doce'].' '.$datos['apema_doce'].' '.$datos['nom_doce']) ?></p>
        <p><em><?= htmlspecialchars($datos['email_doce']) ?></em></p>
      </div>

      <!-- Director -->
      <?php if ($revision): ?>
      <div style="width: 45%; text-align: center;  padding-top: 10px;">
        <p><em><strong>Conformidad:</strong> <?= date('d/m/Y h:i A', strtotime($revision['fecha_revision'])) ?></em></p>
        <p><?= strtoupper($revision['nombre_director']) ?></p>
        <p><em><?= htmlspecialchars($revision['cor_inst']) ?></em></p>
      </div>
      <?php endif; ?>
    </div>
</div>

<!-- AÑEXOS -->
<?php
/* var_dump($tiene_grupal, $tiene_individual, $tiene_derivacion, $tiene_contraref); */
if ($tiene_grupal || $tiene_individual) {
  include("../../pdf_ge/formato78_final.php");
}

if ($tiene_derivacion) {
    $data_nom_car = $datos['nom_car'] ?? '';
    include("../../pdf_ge/referencia_html.php");
}

if ($tiene_contraref) {
    include("../../pdf_ge/contrareferencia_html.php");
}
?>
</body>
