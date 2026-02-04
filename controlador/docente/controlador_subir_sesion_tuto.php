<?php 
while (ob_get_level()) ob_end_clean();
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

session_start();
require '../../modelo/modelo_docente.php';
$MD = new Docente();

header('Content-Type: application/json; charset=utf-8');

// === DATOS POST ===
$id_docente      = $_POST['doc']   ?? null;
$tema            = $_POST['tem']   ?? '';
$compromiso      = $_POST['com']   ?? '';
$tipo_sesion     = $_POST['tip']   ?? '';
$fecha           = $_POST['fec']   ?? '';
$hora_inicio     = $_POST['ini']   ?? '';
$hora_fin        = $_POST['fin']   ?? '';
$link            = $_POST['lik']   ?? '';
$otros           = $_POST['det']   ?? '';
$id_cargalectiva = $_POST['carga'] ?? null;
$ids_estudiantes = $_POST['ida']   ?? '';

// === Validación mínima ===
if (!$id_docente || !$tema || !$tipo_sesion || !$fecha || !$hora_inicio || !$hora_fin || !$id_cargalectiva || !$ids_estudiantes) {
    echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios."]);
    exit;
}

// === Procesar evidencias ===
$evidencia1 = null;
$evidencia2 = null;
$ruta_destino = "../../evidencias_sesion/"; // ruta física
$ruta_bd = "evidencias_sesion/"; // ruta relativa para guardar en BD

if (!file_exists($ruta_destino)) {
    mkdir($ruta_destino, 0777, true);
}

if (isset($_FILES['evidencias']['name']) && is_array($_FILES['evidencias']['name'])) {
    for ($i = 0; $i < count($_FILES['evidencias']['name']); $i++) {
        $nombre = $_FILES['evidencias']['name'][$i];
        $tmp = $_FILES['evidencias']['tmp_name'][$i];
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

        $nuevo_nombre = uniqid("evi_c_") . "." . $ext;
        $ruta_completa = $ruta_destino . $nuevo_nombre;

        if (move_uploaded_file($tmp, $ruta_completa)) {
            $ruta_final = $ruta_bd . $nuevo_nombre;
            if (!$evidencia1) {
                $evidencia1 = $ruta_final;
            } elseif (!$evidencia2) {
                $evidencia2 = $ruta_final;
            }
        }
    }
}

// === Registrar sesión principal ===
$id_sesion = $MD->RegistrarSessionTutoria(
    $id_docente,
    $hora_inicio,
    $hora_fin,
    $tema,
    $compromiso,
    $tipo_sesion,
    $otros,
    $link,
    $fecha,
    $id_cargalectiva,
    $evidencia1,
    $evidencia2
);

// === Verifica si la sesión se insertó correctamente ===
if (!$id_sesion || $id_sesion == 0) {
    echo json_encode(["status" => "error", "message" => "No se pudo registrar la sesión."]);
    exit;
}

// === Registrar detalle por estudiante ===
$ids_array = explode(",", $ids_estudiantes);
$error_estu = 0;

foreach ($ids_array as $id_estu) {
    $ok = $MD->RegistrarDetalleSessionTutoria($id_cargalectiva, $id_sesion, $id_estu);
    if (!$ok) {
        $error_estu++;
    }
}

// === Resultado final ===
if ($error_estu === 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Sesión registrada correctamente.",
        "id_sesion" => $id_sesion
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Sesión creada pero algunos estudiantes no se registraron ($error_estu).",
        "id_sesion" => $id_sesion
    ]);
}

// Limpia toda salida anterior (incluso BOM o espacios de includes)
/* while (ob_get_level()) ob_end_clean();
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../../modelo/modelo_docente.php';
$MD = new Docente();

header('Content-Type: application/json; charset=utf-8');

// === Datos recibidos ===
$doce  = $_POST['doc']   ?? null;
$tema  = $_POST['tem']   ?? null;
$tipo  = $_POST['tip']   ?? null;
$fech  = $_POST['fec']   ?? null;
$inic  = $_POST['ini']   ?? null;
$fina  = $_POST['fin']   ?? null;
$id_cargalectiva = $_POST['carga'] ?? null;

$comp    = $_POST['com'] ?? '';
$link    = $_POST['lik'] ?? '';
$deta    = $_POST['det'] ?? '';
$alumnos = $_POST['ida'] ?? '';

// === Validación ===
if (!$doce || !$tema || !$tipo || !$fech || !$inic || !$fina || !$id_cargalectiva) {
    responder(['status' => 'error', 'message' => 'Faltan datos obligatorios']);
}
if ($tipo == 4 && empty($link)) {
    responder(['status' => 'error', 'message' => 'Debe ingresar el enlace de la sesión']);
}

// === Registrar sesión ===
$id_registro = $MD->RegistrarSessionTutoria($doce, $inic, $fina, $tema, $comp, $tipo, $deta, $link, $fech);
if (!$id_registro) {
    responder(['status' => 'error', 'message' => 'Error al registrar la sesión de tutoría']);
}

// === Registrar detalles ===
$registrados = 0;
if (!empty($alumnos)) {
    $array_id_alumnos = array_filter(array_map('trim', explode(",", $alumnos)));

    foreach ($array_id_alumnos as $id_estudiante) {
        $resultado = $MD->RegistrarDetalleSessionTutoria($id_cargalectiva, $id_registro, $id_estudiante);
        if ($resultado == 1) {
            $registrados++;
        } else {
            responder([
                'status' => 'error',
                'message' => "Error al registrar al alumno ID: $id_estudiante"
            ]);
        }
    }
}

// === Respuesta final ===
responder([
    'status' => 'success',
    'message' => 'Sesión registrada correctamente',
    'registrados' => $registrados
]);

// === Función de respuesta limpia ===
function responder($respuesta) {
    while (ob_get_level()) ob_end_clean(); // limpia buffers acumulados
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
} */
