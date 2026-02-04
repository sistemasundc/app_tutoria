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
    die("Acceso denegado");
}

$conexion = new conexion();
$conexion->conectar();

$id_doce         = $_SESSION['S_IDUSUARIO'];
$id_cargalectiva = $_POST['id_cargalectiva'] ?? null;
$semestre_id     = $_SESSION['S_SEMESTRE'];
$id_car          = $_SESSION['S_SCHOOL'];

if (!$id_cargalectiva) {
    die("Falta ID de carga lectiva");
}

// Datos del formulario
$modalidad         = "GRUPAL E INDIVIDUAL";
$tipo_tutoria      = "AULA";
$aula_asignada     = $_POST['aula_asignada'] ?? '';
$total_estu        = intval($_POST['total_estudiantes'] ?? 0);
$total_planific    = intval($_POST['total_planificadas'] ?? 4);
$total_ejecut      = intval($_POST['total_ejecutadas'] ?? 0);
$resultados        = trim($_POST['resultados'] ?? '');
$dificultades      = trim($_POST['dificultades'] ?? '');
$propuesta_mejora  = trim($_POST['propuesta'] ?? '');
$conclusiones      = trim($_POST['conclusiones'] ?? '');
$estado_envio      = 1;
$fecha_presentacion = null;
$fecha_presentacion_form = $_POST['fecha_presentacion'] ?? null;
if (isset($_POST['btn_enviar'])) {
    $estado_envio = 2;

    if ($fecha_presentacion_form && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_presentacion_form)) {
        $fecha_presentacion = $fecha_presentacion_form;
    } else {
        $fecha_presentacion = date('Y-m-d');
    }
}

// Validar formato de fecha si se está enviando
if ($estado_envio === 2 && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_presentacion)) {
    die("Error: formato de fecha inválido. Valor recibido: '$fecha_presentacion'");
}

// Verificar si ya existe el informe
$sql_verificar = "SELECT id_informe_final FROM tutoria_informe_final_aula 
                  WHERE id_doce = ? AND id_cargalectiva = ? AND semestre_id = ?";
$stmt = $conexion->conexion->prepare($sql_verificar);
$stmt->bind_param("iii", $id_doce, $id_cargalectiva, $semestre_id);
$stmt->execute();
$stmt->store_result();
$tiene_dato = $stmt->num_rows > 0;
$stmt->close();

// Si existe, actualizar
if ($tiene_dato) {
    if ($estado_envio === 2) {
        $fecha_presentacion_form = $_POST['fecha_presentacion'] ?? '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_presentacion_form)) {
            $fecha_presentacion = $fecha_presentacion_form;
        } else {
            $fecha_presentacion = date('Y-m-d');
        }

        $sql_update = "UPDATE tutoria_informe_final_aula SET
            modalidad = ?, tipo_tutoria = ?, aula_asignada = ?, 
            total_estu = ?, total_planific = ?, total_ejecut = ?, 
            resultados = ?, dificultades = ?, propuesta_mejora = ?, 
            conclusiones = ?, estado_envio = ?, fecha_presentacion = ?, 
            id_car = ?
            WHERE id_doce = ? AND id_cargalectiva = ? AND semestre_id = ?";

        $stmt_upd = $conexion->conexion->prepare($sql_update);

        $stmt_upd->bind_param(
            "sssiiissssisiiii" , // 16 parámetros, en orden correcto
            $modalidad, $tipo_tutoria, $aula_asignada,
            $total_estu, $total_planific, $total_ejecut,
            $resultados, $dificultades, $propuesta_mejora,
            $conclusiones, $estado_envio, $fecha_presentacion,
            $id_car, $id_doce, $id_cargalectiva, $semestre_id
        );

        if (!$stmt_upd->execute()) {
            die("Error al actualizar: " . $stmt_upd->error);
        }
        $stmt_upd->close();
    } else {
        // UPDATE sin fecha
        $sql_update = "UPDATE tutoria_informe_final_aula SET
            modalidad = ?, tipo_tutoria = ?, aula_asignada = ?, 
            total_estu = ?, total_planific = ?, total_ejecut = ?, 
            resultados = ?, dificultades = ?, propuesta_mejora = ?, 
            conclusiones = ?, estado_envio = ?, 
            id_car = ?
            WHERE id_doce = ? AND id_cargalectiva = ? AND semestre_id = ?";

        $stmt_upd = $conexion->conexion->prepare($sql_update);

        $stmt_upd->bind_param(
            "sssiiissssiiiii",
            $modalidad,
            $tipo_tutoria,
            $aula_asignada,
            $total_estu,
            $total_planific,
            $total_ejecut,
            $resultados,
            $dificultades,
            $propuesta_mejora,
            $conclusiones,
            $estado_envio,
            $id_car,
            $id_doce,
            $id_cargalectiva,
            $semestre_id
        );


        if (!$stmt_upd->execute()) {
            die("Error al actualizar (sin fecha): " . $stmt_upd->error);
        }
        $stmt_upd->close();
    }
} else {
    // INSERTAR NUEVO INFORME
    $sql_insert = "INSERT INTO tutoria_informe_final_aula (
        id_doce, id_cargalectiva, semestre_id, modalidad, tipo_tutoria,
        aula_asignada, total_estu, total_planific, total_ejecut,
        resultados, dificultades, propuesta_mejora, conclusiones,
        estado_envio, fecha_presentacion, id_car
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_ins = $conexion->conexion->prepare($sql_insert);
    $stmt_ins->bind_param(
        "iiisssiiissssisi",
        $id_doce, $id_cargalectiva, $semestre_id, $modalidad, $tipo_tutoria,
        $aula_asignada, $total_estu, $total_planific, $total_ejecut,
        $resultados, $dificultades, $propuesta_mejora, $conclusiones,
        $estado_envio, $fecha_presentacion, $id_car
    );

    if (!$stmt_ins->execute()) {
        die("Error al insertar informe: " . $stmt_ins->error);
    }
    $stmt_ins->close();
}

$conexion->cerrar();

// Redirigir con mensaje
$mensaje = ($estado_envio === 2) ? "enviado" : "guardado";
header("Location: ../index.php?pagina=tutor_aula/form_informe_final.php&id_cargalectiva=$id_cargalectiva&estado=$mensaje");
exit;
?>