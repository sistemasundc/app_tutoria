<?php
session_start();
require_once '../../modelo/modelo_docente.php';
$MD = new Docente();

// Captura segura de variables
$sesi  = htmlspecialchars($_POST['ses'], ENT_QUOTES, 'UTF-8');
$tema  = htmlspecialchars($_POST['tem'], ENT_QUOTES, 'UTF-8');
$comp  = htmlspecialchars($_POST['com'], ENT_QUOTES, 'UTF-8') ?: '-';
$tipo  = htmlspecialchars($_POST['tip'], ENT_QUOTES, 'UTF-8');
$obse  = htmlspecialchars($_POST['obs'], ENT_QUOTES, 'UTF-8');
$link  = htmlspecialchars($_POST['lik'], ENT_QUOTES, 'UTF-8');
$deta  = htmlspecialchars($_POST['det'], ENT_QUOTES, 'UTF-8');
$fech  = htmlspecialchars($_POST['fec'], ENT_QUOTES, 'UTF-8');
$inic  = htmlspecialchars($_POST['ini'], ENT_QUOTES, 'UTF-8');
$final = htmlspecialchars($_POST['fin'], ENT_QUOTES, 'UTF-8');
$array_id = htmlspecialchars($_POST['ida'], ENT_QUOTES, 'UTF-8');
$id_cargalectiva = htmlspecialchars($_POST['carga'], ENT_QUOTES, 'UTF-8');

$array_id_alumnos = explode(",", $array_id);

// Validar que ID de sesión y otros datos esenciales estén completos
if (!$sesi || !$fech || !$inic || !$final || !$tema || empty($array_id_alumnos)) {
    echo json_encode([
        "status" => "error",
        "message" => "Datos incompletos. Verifica los campos obligatorios."
    ]);
    exit;
}
$evidencia_1 = null;
$evidencia_2 = null;
$ruta_destino = '../../evidencias_sesion/';
$ruta_bd = 'evidencias_sesion/';

if (!file_exists($ruta_destino)) {
    mkdir($ruta_destino, 0777, true);
}

if (isset($_FILES['evidencias']) && is_array($_FILES['evidencias']['name'])) {
    for ($i = 0; $i < count($_FILES['evidencias']['name']); $i++) {
        $nombre = $_FILES['evidencias']['name'][$i];
        $tmp = $_FILES['evidencias']['tmp_name'][$i];
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

        $nuevo_nombre = uniqid("evi_c_") . "." . $ext;
        $ruta_completa = $ruta_destino . $nuevo_nombre;

        if (move_uploaded_file($tmp, $ruta_completa)) {
            $ruta_final = $ruta_bd . $nuevo_nombre;
            if (!$evidencia_1) {
                $evidencia_1 = $ruta_final;
            } elseif (!$evidencia_2) {
                $evidencia_2 = $ruta_final;
            }
        }
    }
}
// 1. Registrar asistencias primero
$registrados = 0;
foreach ($array_id_alumnos as $id_estudiante) {
    $ok = $MD->RegistrarAsistenciaTutoria_TC($id_estudiante, $sesi, $id_cargalectiva);
    if (!$ok) {
        echo json_encode([
            "status" => "error",
            "message" => " Error al registrar asistencia del estudiante ID: $id_estudiante"
        ]);
        exit;
    }
    $registrados++;
}

// 2. Actualizar sesión con color según asistencia
$id_sesion_generada = $MD->ActualizarSessionTutoria(
    $sesi, $inic, $final, $tema, $comp, $tipo, $deta, $link, $fech, $obse,
    $evidencia_1, $evidencia_2
);

// 3. Devolver respuesta JSON
if ($id_sesion_generada) {
    echo json_encode([
        "status" => "success",
        "message" => " Sesión actualizada correctamente",
        "registrados" => $registrados
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No se pudo actualizar o insertar la sesión"
    ]);
}
 

