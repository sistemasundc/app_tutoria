<?php
session_start();
require '../../modelo/modelo_docente.php';
$MD = new Docente();

$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);

// Captura y limpieza de datos POST
$iddocente     = $_POST['doc'] ?? '';
$tema          = trim($_POST['tem'] ?? '');
$compromiso    = trim($_POST['com'] ?? '');
$tipo_sesion   = $_POST['tip'] ?? '';
$link          = trim($_POST['lik'] ?? '');
$detalle       = trim($_POST['det'] ?? '');
$fecha         = $_POST['fec'] ?? '';
$hora_inicio   = $_POST['ini'] ?? '';
$hora_fin      = $_POST['fin'] ?? '';
$id_carga      = $_POST['carga'] ?? 0;

// Arreglos de estudiantes
$array_asignacion = isset($_POST['alu']) ? array_filter(explode(",", $_POST['alu']), fn($v) => trim($v) !== '') : [];
$array_id_alumnos = isset($_POST['ida']) ? array_filter(explode(",", $_POST['ida']), fn($v) => trim($v) !== '') : [];

// Validación de campos requeridos
if (
    empty($iddocente) || empty($tema) || empty($fecha) ||
    empty($hora_inicio) || empty($hora_fin) || empty($tipo_sesion)
) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para registrar la sesión']);
    exit;
}

// Validación de arrays
if (count($array_asignacion) !== count($array_id_alumnos) || count($array_asignacion) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron correctamente los estudiantes']);
    exit;
}

// Procesar evidencias si existen
$evidencia1 = null;
$evidencia2 = null;
$ruta_destino = "../../evidencias_sesion/";

if (!file_exists($ruta_destino)) {
    mkdir($ruta_destino, 0777, true);
}

if (isset($_FILES['evidencias']['name']) && is_array($_FILES['evidencias']['name'])) {
    for ($i = 0; $i < count($_FILES['evidencias']['name']); $i++) {
        $nombre = $_FILES['evidencias']['name'][$i];
        $tmp = $_FILES['evidencias']['tmp_name'][$i];
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

        $nuevo_nombre = uniqid("evi_") . "." . $ext;
        $ruta_completa = $ruta_destino . $nuevo_nombre;

        if (move_uploaded_file($tmp, $ruta_completa)) {
            if (!$evidencia1) {
                $evidencia1 = $nuevo_nombre;
            } elseif (!$evidencia2) {
                $evidencia2 = $nuevo_nombre;
            }
        }
    }
}

// Registrar la sesión principal (ORDEN CORRECTO)
$id_registro = $MD->RegistrarSessionTutoria_TA(
    $fecha,         // 1
    $hora_inicio,   // 2
    $hora_fin,      // 3
    $tema,          // 4
    $detalle,       // 5
    $link,          // 6
    $compromiso,    // 7
    $tipo_sesion,   // 8
    $iddocente,     // 9
    $id_semestre,   // 10
    $evidencia1,    // 11
    $evidencia2     // 12
);

error_log("✔ Registrando sesión: ID=$id_registro | Total alumnos: " . count($array_asignacion));

if ($id_registro != 0) {
    $errores = 0;
    $fallos = [];

    foreach (array_keys($array_asignacion) as $i) {
        $id_asig = trim($array_asignacion[$i]);
        $id_estu = trim($array_id_alumnos[$i]);

        if (empty($id_asig) || empty($id_estu)) {
            $errores++;
            $fallos[] = "Fila $i vacía";
            error_log("❌ Fila vacía: asig=$id_asig | estu=$id_estu");
            continue;
        }

        $resultado = $MD->RegistrarDetalleSessionTutoria_TA($id_asig, $id_estu, $id_registro);

        if (!$resultado) {
            $errores++;
            $fallos[] = "Asistencia fallida -> Asig: $id_asig - Estu: $id_estu";
            error_log("⚠️ Error al guardar asistencia: Asig=$id_asig - Estu=$id_estu - Sesion=$id_registro");
        }
    }

    if ($errores === 0) {
        echo json_encode(['status' => 'success', 'message' => 'Sesión registrada correctamente']);
    } else {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Sesión registrada, pero algunas asistencias fallaron',
            'fallos' => $fallos
        ]);
    }
} else {
    error_log("❌ No se pudo registrar la sesión");
    echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar la sesión']);
}
