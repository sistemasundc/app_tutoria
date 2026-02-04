<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Lima');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

/* ===== Seguridad ===== */
if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'DIRECCION DE ESCUELA') {
  http_response_code(401);
  exit('Acceso denegado');
}

/* ===== Soporte AJAX ===== */
$ES_AJAX = isset($_POST['ajax']) && $_POST['ajax'] === '1';
function out_json($arr){ header('Content-Type: application/json; charset=utf-8'); echo json_encode($arr); exit; }

/* ===== Inputs ===== */
$id_director     = (int)$_SESSION['S_IDUSUARIO'];
$id_car          = (int)($_SESSION['S_SCHOOL'] ?? 0);

$id_plan_tutoria = (int)($_POST['id_plan_tutoria'] ?? 0);
$id_cargalectiva = (int)($_POST['id_cargalectiva'] ?? 0);
$id_docente      = (int)($_POST['id_docente'] ?? 0);
$id_semestre     = (int)($_POST['id_semestre'] ?? 0);

$accion     = strtoupper(trim($_POST['accion'] ?? ''));   // CONFORME | INCONFORME
$comentario = trim($_POST['comentario'] ?? '');           // obligatorio si INCONFORME

$REDIRECT_BASE = "https://tutoria.undc.edu.pe/vista/index.php?pagina=direccion_escuela/direccion_planes_tutor_aula.php";

/* ===== Validaciones ===== */
if (!$id_plan_tutoria || !$id_cargalectiva || !$id_docente || !$id_semestre || !$id_director || !$id_car) {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'Datos incompletos.']);
  header("Location: {$REDIRECT_BASE}&status=error");
  exit;
}
if ($accion !== 'CONFORME' && $accion !== 'INCONFORME') {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'Acción inválida.']);
  header("Location: {$REDIRECT_BASE}&status=error");
  exit;
}
if ($accion === 'INCONFORME' && $comentario === '') {
  if ($ES_AJAX) out_json(['ok'=>false,'msg'=>'El comentario es obligatorio para observar.']);
  header("Location: {$REDIRECT_BASE}&status=nocomment");
  exit;
}

$CN = new conexion();
$cn = $CN->conectar();

try {
  $cn->begin_transaction();

  /* === Nombre director (tolerante a esquema) === */
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

  /* === Upsert revisión (requiere UNIQUE uk_trd_rev) ===
     ALTER TABLE tutoria_revision_director
     ADD UNIQUE KEY uk_trd_rev (id_plan_tutoria, id_cargalectiva, id_docente, id_semestre, id_director);
  */
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
  if (!$stmt->execute()) throw new Exception('Ejecutando upsert revisión');
  $stmt->close();

  /* === Si es INCONFORME: bajar estado_envio 2 -> 1 para que el docente edite === */
  if ($accion === 'INCONFORME') {
    $sqlUpd = "
      UPDATE tutoria_plan_compartido
         SET estado_envio = 1
       WHERE id_plan_tutoria = ?
         AND id_cargalectiva = ?
         AND id_docente      = ?
         AND id_semestre     = ?
         AND estado_envio    = 2
      LIMIT 1
    ";
    $stmt2 = $cn->prepare($sqlUpd);
    if (!$stmt2) throw new Exception('Prepare update plan: '.$cn->error);
    $stmt2->bind_param('iiii', $id_plan_tutoria, $id_cargalectiva, $id_docente, $id_semestre);
    if (!$stmt2->execute()) throw new Exception('Ejecutando update plan');
    $stmt2->close();
  }

  $cn->commit();

  if ($ES_AJAX) {
    out_json([
      'ok'             => true,
      'estado'         => $accion,          // CONFORME | INCONFORME
      'fecha_revision' => $fecha_actual,
      'comentario'     => $comentario
    ]);
  } else {
    header("Location: {$REDIRECT_BASE}&status=ok");
    exit;
  }

} catch (Throwable $e) {
  if ($cn) { $cn->rollback(); }
  if ($ES_AJAX) {
    out_json(['ok'=>false,'msg'=>'Error al guardar la revisión.']);
  } else {
    header("Location: {$REDIRECT_BASE}&status=error");
    exit;
  }
}
