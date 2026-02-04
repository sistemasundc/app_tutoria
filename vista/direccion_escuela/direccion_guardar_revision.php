<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

/* ===== AJAX (SIEMPRE ARRIBA) ===== */
$ES_AJAX = isset($_POST['ajax']) && $_POST['ajax'] === '1';

function out_json($arr, $code = 200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr);
  exit;
}

/* ===== Seguridad (si es ajax => JSON) ===== */
if (!isset($_SESSION['S_IDUSUARIO']) || ($_SESSION['S_ROL'] ?? '') !== 'DIRECCION DE ESCUELA') {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'Acceso denegado (sesión/rol).'], 401);
  http_response_code(401);
  exit('Acceso denegado');
}

/* ===== Inputs ===== */
$id_director = (int)($_SESSION['S_IDUSUARIO'] ?? 0);

// IMPORTANTE: aquí usa tu variable real de carrera en sesión:
$id_car = (int)($_SESSION['S_SCHOOL'] ?? 0);

$id_plan_tutoria = (int)($_POST['id_plan_tutoria'] ?? 0);
$id_cargalectiva = (int)($_POST['id_cargalectiva'] ?? 0);
$id_docente      = (int)($_POST['id_docente'] ?? 0);
$id_semestre     = (int)($_POST['id_semestre'] ?? 0);

$accion     = strtoupper(trim($_POST['accion'] ?? ''));  // CONFORME | INCONFORME
$comentario = trim($_POST['comentario'] ?? '');

/* ===== Validaciones ===== */
if (!$id_plan_tutoria || !$id_cargalectiva || !$id_docente || !$id_semestre || !$id_director) {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'Datos incompletos (inputs).'], 400);
  die('Datos incompletos.');
}
if (!$id_car) {
  // si no tienes S_SCHOOL, aquí es donde fallaba en silencio para algunos
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'No se encontró la carrera en sesión (S_SCHOOL).'], 400);
  die('Carrera no definida.');
}
if ($accion !== 'CONFORME' && $accion !== 'INCONFORME') {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'Acción inválida.'], 400);
  die('Acción inválida.');
}
if ($accion === 'INCONFORME' && $comentario === '') {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'El comentario es obligatorio para observar.'], 400);
  die('Comentario obligatorio.');
}

$CN = new conexion();
$cn = $CN->conectar();

try {
  $cn->begin_transaction();

  /* ===== Nombre director ===== */
  $stmt = $cn->prepare("SELECT * FROM tutoria_usuario WHERE id_usuario = ? LIMIT 1");
  if (!$stmt) throw new Exception('Prepare usuario: '.$cn->error);
  $stmt->bind_param('i', $id_director);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $grado   = trim($u['grado']   ?? '');
  $nombres = trim($u['nombres'] ?? ($u['nombre'] ?? ($u['nom_usuario'] ?? '')));
  $apep    = trim($u['apepa']   ?? ($u['apaterno'] ?? ($u['apellido_paterno'] ?? ($u['ape_pat'] ?? ''))));
  $apem    = trim($u['apema']   ?? ($u['amaterno'] ?? ($u['apellido_materno'] ?? ($u['ape_mat'] ?? ''))));
  $nombre_director = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$grado, $nombres, $apep, $apem]))));
  if ($nombre_director === '') $nombre_director = 'DIRECTOR';

  /* ===== Upsert revisión ===== */
  $fecha_actual = date('Y-m-d H:i:s');

  $sql = "
    INSERT INTO tutoria_revision_director
      (id_plan_tutoria, id_cargalectiva, id_docente, id_semestre, id_director,
       estado_revision, comentario, fecha_revision, nombre_director, id_car)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      estado_revision = VALUES(estado_revision),
      comentario      = VALUES(comentario),
      fecha_revision  = VALUES(fecha_revision),
      nombre_director = VALUES(nombre_director),
      id_car          = VALUES(id_car)
  ";
  $stmt = $cn->prepare($sql);
  if (!$stmt) throw new Exception('Prepare upsert: '.$cn->error);

  $stmt->bind_param(
    'iiiiissssi',
    $id_plan_tutoria,
    $id_cargalectiva,
    $id_docente,
    $id_semestre,
    $id_director,
    $accion,
    $comentario,
    $fecha_actual,
    $nombre_director,
    $id_car
  );

  if (!$stmt->execute()) throw new Exception('Execute upsert: '.$stmt->error);
  $stmt->close();

  /* ===== Si es INCONFORME: bajar estado_envio a 1 (consulta segura) =====
     OJO: aquí quitamos id_docente/id_semestre en WHERE porque suele fallar por columnas
  */
  if ($accion === 'INCONFORME') {
    $sqlUpd = "
      UPDATE tutoria_plan_compartido
         SET estado_envio = 1
       WHERE id_plan_tutoria = ?
         AND id_cargalectiva = ?
         AND estado_envio    = 2
       LIMIT 1
    ";
    $stmt2 = $cn->prepare($sqlUpd);
    if (!$stmt2) throw new Exception('Prepare update plan: '.$cn->error);
    $stmt2->bind_param('ii', $id_plan_tutoria, $id_cargalectiva);
    if (!$stmt2->execute()) throw new Exception('Execute update plan: '.$stmt2->error);
    $stmt2->close();
  }

  $cn->commit();

  /* ===== Respuesta AJAX ===== */
  if ($ES_AJAX) {
    out_json([
      'ok'             => true,
      'estado'         => $accion,
      'etiqueta'       => $accion,
      'fecha_revision' => $fecha_actual,
      'comentario'     => $comentario
    ]);
  }

  echo "OK";
  exit;

} catch (Throwable $e) {
  if ($cn) $cn->rollback();
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>$e->getMessage()], 500);
  http_response_code(500);
  exit($e->getMessage());
}
