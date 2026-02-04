<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

header('Content-Type: text/plain; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../../modelo/modelo_conexion.php'; // ajusta ruta si es distinta
$conexion = new conexion();
$conexion->conectar();
$cn = $conexion->conexion;

$id_doce  = isset($_POST['id_doce']) ? (int)$_POST['id_doce'] : 0;   // <-- ahora recibimos id_doce
$semestre = isset($_POST['semestre']) ? (int)$_POST['semestre'] : 0;
if (!$semestre && isset($_SESSION['S_SEMESTRE'])) $semestre = (int)$_SESSION['S_SEMESTRE'];

if ($id_doce <= 0 || $semestre <= 0) { echo 0; exit; }

try {
  $cn->begin_transaction();

  // 1) ¿Tiene asignaciones en el semestre? (tutoria_asignacion_tutoria)
  $sqlChk = "SELECT COUNT(*) AS c
             FROM tutoria_asignacion_tutoria
             WHERE id_docente = ? AND id_semestre = ?";
  $stChk = $cn->prepare($sqlChk);
  $stChk->bind_param('ii', $id_doce, $semestre);
  $stChk->execute();
  $c = (int)$stChk->get_result()->fetch_assoc()['c'];

  if ($c > 0) {
    $cn->rollback();
    echo 2; // tiene cargos vigentes -> mostrar "Quite los cargos…"
    exit;
  }

  // 2) No tiene asignaciones: eliminar su registro de ese semestre en tutoria_docente_asignado
  $sqlDel = "DELETE FROM tutoria_docente_asignado
             WHERE id_doce = ? AND id_semestre = ?";
  $stDel = $cn->prepare($sqlDel);
  $stDel->bind_param('ii', $id_doce, $semestre);
  $stDel->execute();

  if ($stDel->affected_rows > 0) {
    $cn->commit();
    echo 1;  // eliminado OK
  } else {
    $cn->rollback();
    echo 0;  // no había fila para ese semestre
  }

} catch (mysqli_sql_exception $e) {
  if ((int)$e->getCode() === 1451) { // FK constraint
    $cn->rollback();
    echo 3;
  } else {
    $cn->rollback();
    echo 0;
  }
} catch (Exception $e) {
  $cn->rollback();
  echo 0;
}
