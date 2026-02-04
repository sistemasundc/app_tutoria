<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
date_default_timezone_set('America/Lima');
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

/* =======================
   1) SEGURIDAD
   ======================= */
if (!isset($_SESSION['S_IDUSUARIO']) || ($_SESSION['S_ROL'] ?? '') !== 'TUTOR DE AULA') {
  die("Acceso denegado");
}

$id_doce = (int)$_SESSION['S_IDUSUARIO'];
if ($id_doce !== 400) {
  die("Acceso denegado");
}

/* =======================
   2) FORZAR SEMESTRE 2025-I
   ======================= */
$SEMESTRE_OBJ = 32; // 2025-I (fijo)

/* =======================
   3) ENTRADAS
   ======================= */
$id_cargalectiva = isset($_POST['id_cargalectiva']) ? (int)$_POST['id_cargalectiva'] : 0;
$id_car          = (int)($_SESSION['S_SCHOOL'] ?? 0); // se mantiene como en tu sistema

if ($id_cargalectiva <= 0) {
  die("Falta ID de carga lectiva");
}

$conexion = new conexion();
$conexion->conectar();

/* ==================================================
   4) VALIDAR QUE ESA CARGA ES DEL DOCENTE EN SEM 32
   (Fuente real: tutoria_asignacion_tutoria)
   ================================================== */
$sqlVal = "SELECT 1
           FROM tutoria_asignacion_tutoria
           WHERE id_docente = ?
             AND id_semestre = ?
             AND id_carga = ?
           LIMIT 1";
$stmtVal = $conexion->conexion->prepare($sqlVal);
$stmtVal->bind_param("iii", $id_doce, $SEMESTRE_OBJ, $id_cargalectiva);
$stmtVal->execute();
$okCarga = (bool)$stmtVal->get_result()->fetch_assoc();
$stmtVal->close();

if (!$okCarga) {
  $conexion->cerrar();
  die("Acceso denegado: carga no válida para el docente/semestre.");
}

/* =======================
   5) DATOS DEL FORMULARIO
   ======================= */
$modalidad        = "GRUPAL E INDIVIDUAL";
$tipo_tutoria     = "AULA";
$aula_asignada    = $_POST['aula_asignada'] ?? '';
$total_estu       = (int)($_POST['total_estudiantes'] ?? 0);
$total_planific   = (int)($_POST['total_planificadas'] ?? 4);
$total_ejecut     = (int)($_POST['total_ejecutadas'] ?? 0);

$resultados       = trim($_POST['resultados'] ?? '');
$dificultades     = trim($_POST['dificultades'] ?? '');
$propuesta_mejora = trim($_POST['propuesta'] ?? '');
$conclusiones     = trim($_POST['conclusiones'] ?? '');

$estado_envio = 1; // borrador
$fecha_presentacion = null;

/* =======================
   6) SI PRESIONA ENVIAR
   ======================= */
if (isset($_POST['btn_enviar'])) {
  $estado_envio = 2;

  $fecha_presentacion_form = $_POST['fecha_presentacion'] ?? '';
  if ($fecha_presentacion_form && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_presentacion_form)) {
    $fecha_presentacion = $fecha_presentacion_form;
  } else {
    $fecha_presentacion = date('Y-m-d');
  }

  // validación final
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_presentacion)) {
    $conexion->cerrar();
    die("Error: formato de fecha inválido. Valor recibido: '$fecha_presentacion'");
  }
}

/* =======================
   7) VERIFICAR SI YA EXISTE
   ======================= */
$sql_verificar = "SELECT id_informe_final
                  FROM tutoria_informe_final_aula
                  WHERE id_doce = ?
                    AND id_cargalectiva = ?
                    AND semestre_id = ?";
$stmt = $conexion->conexion->prepare($sql_verificar);
$stmt->bind_param("iii", $id_doce, $id_cargalectiva, $SEMESTRE_OBJ);
$stmt->execute();
$stmt->store_result();
$tiene_dato = ($stmt->num_rows > 0);
$stmt->close();

/* =======================
   8) UPDATE / INSERT
   ======================= */
if ($tiene_dato) {

  if ($estado_envio === 2) {
    $sql_update = "UPDATE tutoria_informe_final_aula SET
      modalidad = ?, tipo_tutoria = ?, aula_asignada = ?,
      total_estu = ?, total_planific = ?, total_ejecut = ?,
      resultados = ?, dificultades = ?, propuesta_mejora = ?,
      conclusiones = ?, estado_envio = ?, fecha_presentacion = ?,
      id_car = ?
      WHERE id_doce = ? AND id_cargalectiva = ? AND semestre_id = ?";

    $stmt_upd = $conexion->conexion->prepare($sql_update);
    $stmt_upd->bind_param(
      "sssiiissssisiiii",
      $modalidad, $tipo_tutoria, $aula_asignada,
      $total_estu, $total_planific, $total_ejecut,
      $resultados, $dificultades, $propuesta_mejora,
      $conclusiones, $estado_envio, $fecha_presentacion,
      $id_car, $id_doce, $id_cargalectiva, $SEMESTRE_OBJ
    );

    if (!$stmt_upd->execute()) {
      $err = $stmt_upd->error;
      $stmt_upd->close();
      $conexion->cerrar();
      die("Error al actualizar: " . $err);
    }
    $stmt_upd->close();

  } else {

    $sql_update = "UPDATE tutoria_informe_final_aula SET
      modalidad = ?, tipo_tutoria = ?, aula_asignada = ?,
      total_estu = ?, total_planific = ?, total_ejecut = ?,
      resultados = ?, dificultades = ?, propuesta_mejora = ?,
      conclusiones = ?, estado_envio = ?,
      id_car = ?
      WHERE id_doce = ? AND id_cargalectiva = ? AND semestre_id = ?";

    $stmt_upd = $conexion->conexion->prepare($sql_update);
    $stmt_upd->bind_param(
      "sssiiissssiiii",
      $modalidad, $tipo_tutoria, $aula_asignada,
      $total_estu, $total_planific, $total_ejecut,
      $resultados, $dificultades, $propuesta_mejora,
      $conclusiones, $estado_envio,
      $id_car, $id_doce, $id_cargalectiva, $SEMESTRE_OBJ
    );

    if (!$stmt_upd->execute()) {
      $err = $stmt_upd->error;
      $stmt_upd->close();
      $conexion->cerrar();
      die("Error al actualizar (sin fecha): " . $err);
    }
    $stmt_upd->close();
  }

} else {

  $sql_insert = "INSERT INTO tutoria_informe_final_aula (
    id_doce, id_cargalectiva, semestre_id, modalidad, tipo_tutoria,
    aula_asignada, total_estu, total_planific, total_ejecut,
    resultados, dificultades, propuesta_mejora, conclusiones,
    estado_envio, fecha_presentacion, id_car
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt_ins = $conexion->conexion->prepare($sql_insert);
  $stmt_ins->bind_param(
    "iiisssiiissssisi",
    $id_doce, $id_cargalectiva, $SEMESTRE_OBJ, $modalidad, $tipo_tutoria,
    $aula_asignada, $total_estu, $total_planific, $total_ejecut,
    $resultados, $dificultades, $propuesta_mejora, $conclusiones,
    $estado_envio, $fecha_presentacion, $id_car
  );

  if (!$stmt_ins->execute()) {
    $err = $stmt_ins->error;
    $stmt_ins->close();
    $conexion->cerrar();
    die("Error al insertar informe: " . $err);
  }
  $stmt_ins->close();
}


/* =======================
   9) REDIRECCIÓN A LANDING
   ======================= */

$conexion->cerrar();

if ($estado_envio === 2) {
    // ENVIAR -> vuelve al LANDING y debe mostrar "regularizado"
    header("Location: ../index.php?pagina=tutor_aula/informe_final_excepcion.php&id_carga={$id_cargalectiva}&msg=enviado");
} else {
    // GUARDAR -> vuelve al MISMO FORM para mostrar "guardado"
    header("Location: ../index.php?pagina=tutor_aula/form_informe_final_excepcion.php&id_cargalectiva={$id_cargalectiva}&estado=guardado");
}
exit;
