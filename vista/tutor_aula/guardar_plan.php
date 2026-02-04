<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (!isset($_SESSION['S_SEMESTRE'])) {
    die('No hay semestre en sesión');
}

$id_semestre     = (int) $_SESSION['S_SEMESTRE'];
$id_doce         = (int) ($_SESSION['S_IDUSUARIO'] ?? 0);
$id_cargalectiva = isset($_POST['id_cargalectiva']) ? (int) $_POST['id_cargalectiva'] : 0;
$accion          = $_POST['accion'] ?? 'guardar';

if ($id_doce <= 0 || $id_cargalectiva <= 0) {
    die("Faltan datos: docente o carga lectiva.");
}

/* (A) Verificar que la carga enviada realmente pertenece a este docente en este semestre */
$sqlCheckCL = "
  SELECT cl.ciclo, cl.id_car, cl.id_semestre
  FROM carga_lectiva cl
  JOIN tutoria_asignacion_tutoria tat
    ON tat.id_carga = cl.id_cargalectiva AND tat.id_semestre = ?
  WHERE cl.id_cargalectiva = ? AND tat.id_docente = ?
  LIMIT 1";
$stmtCheckCL = $conexion->conexion->prepare($sqlCheckCL);
$stmtCheckCL->bind_param("iii", $id_semestre, $id_cargalectiva, $id_doce);
$stmtCheckCL->execute();
$datosCurso = $stmtCheckCL->get_result()->fetch_assoc();
if (!$datosCurso) die("La carga no corresponde al docente/semestre.");

$ciclo   = $datosCurso['ciclo'];
$id_car  = (int) $datosCurso['id_car'];

/* (B) Obtener/crear plan compartido por ciclo+car+semestre (1 solo id_plan para todos los docentes del ciclo) */
$sqlPlan = "
  SELECT tp.id_plan_tutoria
  FROM tutoria_plan2 tp
  JOIN carga_lectiva cl ON cl.id_cargalectiva = tp.id_cargalectiva
  WHERE cl.ciclo = ? AND cl.id_car = ? AND cl.id_semestre = ?
  LIMIT 1";
$stmtPlan = $conexion->conexion->prepare($sqlPlan);
$stmtPlan->bind_param("sii", $ciclo, $id_car, $id_semestre);
$stmtPlan->execute();
$plan = $stmtPlan->get_result()->fetch_assoc();

if ($plan) {
    $id_plan_tutoria = (int) $plan['id_plan_tutoria'];
} else {
    $stmtNew = $conexion->conexion->prepare("
      INSERT INTO tutoria_plan2 (id_cargalectiva, id_semestre, id_car)
      VALUES (?, ?, ?)");
    $stmtNew->bind_param("iii", $id_cargalectiva, $id_semestre, $id_car);
    if (!$stmtNew->execute()) die("Error creando plan: " . $stmtNew->error);
    $id_plan_tutoria = (int) $conexion->conexion->insert_id;
}

/* (C) Si este docente YA envió su plan, no permitir cambios */
$sqlMiEstado = "SELECT estado_envio FROM tutoria_plan_compartido
                WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ? 
                LIMIT 1";
$stmtMiEstado = $conexion->conexion->prepare($sqlMiEstado);
$stmtMiEstado->bind_param("iii", $id_plan_tutoria, $id_cargalectiva, $id_doce);
$stmtMiEstado->execute();
$rowMiEstado = $stmtMiEstado->get_result()->fetch_assoc();
$mi_estado = (int)($rowMiEstado['estado_envio'] ?? 0);

if ($mi_estado === 2) {
    $conexion->cerrar();
    die("Este plan ya fue Enviado. No se puede modificar.");
}

/* (D) Asegurar/crear fila del docente en tutoria_plan_compartido */
if ($rowMiEstado) {
    if ($mi_estado === 0) {
        $up0 = $conexion->conexion->prepare("
          UPDATE tutoria_plan_compartido
             SET estado_envio = 1, id_car = ?, id_semestre = ?
           WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?");
        $up0->bind_param("iiiii", $id_car, $id_semestre, $id_plan_tutoria, $id_cargalectiva, $id_doce);
        $up0->execute();
        $mi_estado = 1;
    }
} else {
    $insPC = $conexion->conexion->prepare("
      INSERT INTO tutoria_plan_compartido
        (id_plan_tutoria, id_cargalectiva, id_docente, estado_envio, fecha_envio, id_car, id_semestre)
      VALUES (?, ?, ?, 1, NOW(), ?, ?)");
    $insPC->bind_param("iiiii", $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_car, $id_semestre);
    $insPC->execute();
    $mi_estado = 1;
}

/* (E) Guardar actividades: SOLO de este docente (id_plan, id_carga, id_docente, mes) */
for ($mes = 1; $mes <= 4; $mes++) {
    $campo = "actividad_$mes";
    $descripcion = trim($_POST[$campo] ?? '');

    // Limpia solo tus filas de ese mes
    $del = $conexion->conexion->prepare("
      DELETE FROM tutoria_actividades_plan
       WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?
         AND id_semestre = ? AND id_car = ? AND mes = ?");
    $del->bind_param("iiiiii", $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_semestre, $id_car, $mes);
    $del->execute();

    if ($descripcion !== '') {
        $ins = $conexion->conexion->prepare("
          INSERT INTO tutoria_actividades_plan
            (id_plan_tutoria, id_cargalectiva, id_docente, id_semestre, id_car, mes, descripcion)
          VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iiiiiss", $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_semestre, $id_car, $mes, $descripcion);
        if (!$ins->execute()) die("Error insertando actividad mes $mes: ".$ins->error);
    }
}

/* (F) Comentario (mes=5) solo del docente actual */
$comentario = trim($_POST['actividad_otros'] ?? '');
$mes5 = 5;

$sel5 = $conexion->conexion->prepare("
  SELECT id_actividad_plan
    FROM tutoria_actividades_plan
   WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?
     AND id_semestre = ? AND id_car = ? AND mes = ?");
$sel5->bind_param("iiiiii", $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_semestre, $id_car, $mes5);
$sel5->execute();
$existe5 = $sel5->get_result()->num_rows > 0;

if ($comentario !== '') {
    if ($existe5) {
        $upd5 = $conexion->conexion->prepare("
          UPDATE tutoria_actividades_plan
             SET comentario = ?
           WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?
             AND id_semestre = ? AND id_car = ? AND mes = ?");
        $upd5->bind_param("siiiiii", $comentario, $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_semestre, $id_car, $mes5);
        $upd5->execute();
    } else {
        $ins5 = $conexion->conexion->prepare("
          INSERT INTO tutoria_actividades_plan
            (id_plan_tutoria, id_cargalectiva, id_docente, id_semestre, id_car, mes, comentario)
          VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins5->bind_param("iiiiiss", $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_semestre, $id_car, $mes5, $comentario);
        $ins5->execute();
    }
} else {
    // si el comentario quedó vacío, borra tu fila de mes 5
    if ($existe5) {
        $del5 = $conexion->conexion->prepare("
          DELETE FROM tutoria_actividades_plan
           WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?
             AND id_semestre = ? AND id_car = ? AND mes = ?");
        $del5->bind_param("iiiiii", $id_plan_tutoria, $id_cargalectiva, $id_doce, $id_semestre, $id_car, $mes5);
        $del5->execute();
    }
}

/* (G) Cambiar estado según acción: SOLO mi fila */
$fechaEnvio = date('Y-m-d H:i:s');

if ($accion === 'enviar') {
    $updEstado = $conexion->conexion->prepare("
      UPDATE tutoria_plan_compartido
         SET estado_envio = 2, fecha_envio = ?, id_car = ?, id_semestre = ?
       WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?");
    $updEstado->bind_param("siiiii", $fechaEnvio, $id_car, $id_semestre, $id_plan_tutoria, $id_cargalectiva, $id_doce);
    $updEstado->execute();
} else {
    $updEstado = $conexion->conexion->prepare("
      UPDATE tutoria_plan_compartido
         SET estado_envio = CASE WHEN estado_envio = 2 THEN 2 ELSE 1 END,
             id_car = ?, id_semestre = ?
       WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_docente = ?");
    $updEstado->bind_param("iiiii", $id_car, $id_semestre, $id_plan_tutoria, $id_cargalectiva, $id_doce);
    $updEstado->execute();
}

$conexion->cerrar();

/* Vuelve al formulario */
header("Location: https://tutoria.undc.edu.pe/vista/index.php?pagina=tutor_aula/form_plan_tutoria.php&id_cargalectiva=".$id_cargalectiva);
exit;
