<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

session_start();
require_once '../../modelo/modelo_docente.php';

$MD = new Docente();

$sesi  = $_POST['ses'] ?? '';
$tema  = $_POST['tem'] ?? '';
$comp  = $_POST['com'] ?? '';
$tipo  = $_POST['tip'] ?? '';
$link  = $_POST['lik'] ?? '';
$otro  = $_POST['det'] ?? '';
$fecha = $_POST['fec'] ?? '';
$inic  = $_POST['ini'] ?? '';
$fina  = $_POST['fin'] ?? '';
$obse  = $_POST['obs'] ?? '';
$array_id = $_POST['ida'] ?? '';
$array_id_alumnos = explode(",", $array_id);

// Configuración de subida
$evidencia_1 = null;
$evidencia_2 = null;
$max_size = 20 * 1024 * 1024; // 20 MB
$ruta_destino = '../../evidencias_sesion/';

// Validar permisos de carpeta
if (!is_dir($ruta_destino) || !is_writable($ruta_destino)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: La carpeta evidencias_sesion no tiene permisos de escritura.'
    ]);
    exit;
}

// Subida archivo 1
if (isset($_FILES['evidencias']['name'][0]) && $_FILES['evidencias']['error'][0] === 0) {
    if ($_FILES['evidencias']['size'][0] > $max_size) {
        echo json_encode([
            'status' => 'error',
            'message' => 'La primera imagen excede el tamaño permitido (20 MB).'
        ]);
        exit;
    }

    $nombre1 = 'evi1_' . uniqid() . '.jpg';
    $ruta1 = $ruta_destino . $nombre1;

    if (move_uploaded_file($_FILES['evidencias']['tmp_name'][0], $ruta1)) {
        $evidencia_1 = 'evidencias_sesion/' . $nombre1;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo mover la primera imagen.'
        ]);
        exit;
    }
}

// Subida archivo 2
if (isset($_FILES['evidencias']['name'][1]) && $_FILES['evidencias']['error'][1] === 0) {
    if ($_FILES['evidencias']['size'][1] > $max_size) {
        echo json_encode([
            'status' => 'error',
            'message' => 'La segunda imagen excede el tamaño permitido (20 MB).'
        ]);
        exit;
    }

    $nombre2 = 'evi2_' . uniqid() . '.jpg';
    $ruta2 = $ruta_destino . $nombre2;

    if (move_uploaded_file($_FILES['evidencias']['tmp_name'][1], $ruta2)) {
        $evidencia_2 = 'evidencias_sesion/' . $nombre2;
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo mover la segunda imagen.'
        ]);
        exit;
    }
}

// Actualizar datos sesión
$actualizado = $MD->ActualizarSessionTutoria_TA(
    $sesi, $inic, $fina, $tema, $comp, $tipo, $otro, $link, $fecha, $obse
);

if (!$actualizado) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo actualizar la sesión de tutoría.'
    ]);
    exit;
}

// Guardar rutas en BD
if ($evidencia_1 || $evidencia_2) {
    require_once '../../modelo/modelo_conexion.php';
    $conexion = new conexion();
    $cn = $conexion->conectar();

    $stmt = $cn->prepare("UPDATE tutoria_sesiones_tutoria_f78 SET evidencia_1 = ?, evidencia_2 = ? WHERE id_sesiones_tuto = ?");
    $stmt->bind_param("ssi", $evidencia_1, $evidencia_2, $sesi);
    if (!$stmt->execute()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al guardar evidencias en la base de datos.'
        ]);
        exit;
    }
    $stmt->close();
}

// Registrar asistencia
$todoOk = true;
foreach ($array_id_alumnos as $id) {
    $ok = $MD->RegistrarAsistenciaTutoria($id, $sesi);
    if ($ok != 1) {
        $todoOk = false;
        break;
    }
}

// Respuesta final
$ids = array_values(array_unique(array_filter(
  array_map('intval', explode(',', $_POST['ida'] ?? '')),
  fn($v) => $v > 0
)));

$fallidos = [];
foreach ($ids as $v) {
  $ok = $MD->RegistrarAsistenciaTutoria($v, (int)$sesi);
  if ($ok != 1) $fallidos[] = $v;
}

if (empty($fallidos)) {
  echo json_encode(['status'=>'success','message'=>'Asistencia y evidencias registradas correctamente.']);
} else {
  echo json_encode([
    'status'=>'warning',
    'message'=>'Sesión actualizada pero algunos registros de asistencia fallaron.',
    'fallidos'=>$fallidos
  ]);
}

/* session_start();
require_once '../../modelo/modelo_docente.php';
$MD = new Docente();
// Validar y sanitizar datos del POST
$sesi  = htmlspecialchars($_POST['ses'] ?? '', ENT_QUOTES, 'UTF-8');
$tema  = htmlspecialchars($_POST['tem'] ?? '', ENT_QUOTES, 'UTF-8');
$comp  = htmlspecialchars($_POST['com'] ?? '', ENT_QUOTES, 'UTF-8');
$tipo  = htmlspecialchars($_POST['tip'] ?? '', ENT_QUOTES, 'UTF-8');
$link  = htmlspecialchars($_POST['lik'] ?? '', ENT_QUOTES, 'UTF-8');
$otro  = htmlspecialchars($_POST['det'] ?? '', ENT_QUOTES, 'UTF-8');
$fecha = htmlspecialchars($_POST['fec'] ?? '', ENT_QUOTES, 'UTF-8');
$inic  = htmlspecialchars($_POST['ini'] ?? '', ENT_QUOTES, 'UTF-8');
$fina  = htmlspecialchars($_POST['fin'] ?? '', ENT_QUOTES, 'UTF-8');
$obse  = htmlspecialchars($_POST['obs'] ?? '', ENT_QUOTES, 'UTF-8'); // opcional
$array_id = htmlspecialchars($_POST['ida'] ?? '', ENT_QUOTES, 'UTF-8');
$array_id_alumnos = explode(",", $array_id);
// Actualizar sesión
$actualizado = $MD->ActualizarSessionTutoria_TA(
    $sesi, $inic, $fina, $tema, $comp, $tipo, $otro, $link, $fecha, $obse
);
if (!$actualizado) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo actualizar la sesión de tutoría.'
    ]);
    exit;
}
// Registrar asistencia
$todoOk = true;
foreach ($array_id_alumnos as $id) {
    $ok = $MD->RegistrarAsistenciaTutoria($id, $sesi);
    if ($ok != 1) {
        $todoOk = false;
        break;
    }
}
header('Content-Type: application/json; charset=utf-8');
if ($todoOk) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Asistencia registrada correctamente.'
    ]);
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'Algunos registros de asistencia fallaron.'
    ]);
}
exit;    */