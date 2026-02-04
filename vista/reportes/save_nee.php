<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/Lima');

/* ======= USUARIOS CON PERMISO  ======= */
$rolesPermitidos = ['COORDINADOR GENERAL DE TUTORIA', 'VICEPRESIDENCIA ACADEMICA', 'SUPERVISION'];
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'] ?? '', $rolesPermitidos, true)) {
  echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
  exit;
}

/* ======= CONEXIÓN  ======= */
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (empty($conexion->conexion)) {
  echo json_encode(['ok' => false, 'error' => 'No se pudo conectar a la BD']);
  exit;
}

$cn = $conexion->conexion;
$accion = $_POST['accion'] ?? '';

function carreraNombre($id_car) {
  switch ((string)$id_car) {
    case "1": return "ADMINISTRACIÓN";
    case "2": return "CONTABILIDAD";
    case "3": return "ADMINISTRACIÓN DE TURISMO Y HOTELERÍA";
    case "4": return "INGENIERÍA DE SISTEMAS";
    case "5": return "AGRONOMÍA";
    default:  return "—";
  }
}

try {

  /* ===================== LISTAR ESTUDAINTES NEE ACTIVOS BD ===================== */
  if ($accion === 'listar') {

    $sql = "
      SELECT DISTINCT
        e.id_estu,
        CONCAT(e.apepa_estu,' ',e.apema_estu,', ',e.nom_estu) AS estudiante,
        e.email_estu,
        fm.ciclo_ficham AS ciclo,
        fm.id_semestre,
        cl.id_car,
        CASE cl.id_car
          WHEN 1 THEN 'ADMINISTRACIÓN'
          WHEN 2 THEN 'CONTABILIDAD'
          WHEN 3 THEN 'ADMINISTRACIÓN DE TURISMO Y HOTELERÍA'
          WHEN 4 THEN 'INGENIERÍA DE SISTEMAS'
          WHEN 5 THEN 'AGRONOMÍA'
        END AS carrera
      FROM tutoria_estudiantes_conadis tc
      INNER JOIN estudiante e ON e.id_estu = tc.id_estu
      INNER JOIN ficha_matricula fm
        ON fm.id_estu = e.id_estu
       AND fm.id_semestre = (
          SELECT MAX(id_semestre)
          FROM ficha_matricula
          WHERE id_estu = e.id_estu
       )
      INNER JOIN asignacion_estudiante ae ON ae.id_ficham = fm.id_ficham
      INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = ae.id_cargalectiva
      WHERE IFNULL(tc.estado,1) = 1
      ORDER BY e.id_estu DESC
    ";

    $res = $cn->query($sql);
    if (!$res) throw new Exception($cn->error);

    $data = [];
    while ($row = $res->fetch_assoc()) $data[] = $row;

    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
  }

  /* ===================== BUSCAR ESTUDIANTE ===================== */
  if ($accion === 'buscar_estudiante') {
    $q = trim($_POST['q'] ?? '');
    if (mb_strlen($q) < 2) {
      echo json_encode(['ok' => false, 'error' => 'Consulta muy corta']);
      exit;
    }

    $like = "%{$q}%";

    $sql = "
      SELECT
        e.id_estu,
        e.apepa_estu,
        e.apema_estu,
        e.nom_estu,
        e.email_estu,
        e.dni_estu,
        e.cod_estu,
        fm.id_semestre,
        fm.ciclo_ficham AS ciclo,
        cl.id_car
      FROM estudiante e
      LEFT JOIN ficha_matricula fm
        ON fm.id_estu = e.id_estu
       AND fm.id_semestre = (
          SELECT MAX(id_semestre)
          FROM ficha_matricula
          WHERE id_estu = e.id_estu
       )
      LEFT JOIN asignacion_estudiante ae ON ae.id_ficham = fm.id_ficham
      LEFT JOIN carga_lectiva cl ON cl.id_cargalectiva = ae.id_cargalectiva
      WHERE (
        e.dni_estu LIKE ?
        OR e.cod_estu LIKE ?
        OR e.apepa_estu LIKE ?
        OR e.apema_estu LIKE ?
        OR e.nom_estu LIKE ?
      )
      ORDER BY e.apepa_estu ASC
      LIMIT 30
    ";

    $stmt = $cn->prepare($sql);
    if (!$stmt) throw new Exception($cn->error);

    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($r = $res->fetch_assoc()) {
      $r['estudiante'] = trim(($r['apepa_estu'] ?? '').' '.($r['apema_estu'] ?? '').', '.($r['nom_estu'] ?? ''));
      $r['carrera'] = carreraNombre($r['id_car'] ?? null);
      $data[] = $r;
    }

    $stmt->close();
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
  }

  /* ===================== GUARDAR NEE ===================== */
  if ($accion === 'guardar') {

    $id_estu = (int)($_POST['id_estu'] ?? 0);
    if ($id_estu <= 0) {
      echo json_encode(['ok' => false, 'error' => 'id_estu inválido']);
      exit;
    }

    $stmt = $cn->prepare("SELECT id_conadis, IFNULL(estado,1) AS estado FROM tutoria_estudiantes_conadis WHERE id_estu = ? LIMIT 1");
    $stmt->bind_param("i", $id_estu);
    $stmt->execute();
    $res = $stmt->get_result();
    $ex = $res->fetch_assoc();
    $stmt->close();

    if ($ex) {
      if ((int)$ex['estado'] === 0) {
        $stmt = $cn->prepare("UPDATE tutoria_estudiantes_conadis SET estado=1, fecha_baja=NULL WHERE id_conadis=?");
        $stmt->bind_param("i", $ex['id_conadis']);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true, 'msg' => 'Reactivado']);
        exit;
      }
      echo json_encode(['ok' => false, 'error' => 'El estudiante ya está registrado como NEE']);
      exit;
    }

    $ape_paterno = trim($_POST['ape_paterno'] ?? '');
    $ape_materno = trim($_POST['ape_materno'] ?? '');
    $nombres     = trim($_POST['nombres'] ?? '');
    $dni         = trim($_POST['dni'] ?? '');
    $codigo      = trim($_POST['codigo'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $programa    = trim($_POST['programa'] ?? '');

    $sql = "
      INSERT INTO tutoria_estudiantes_conadis
      (id_estu, ape_paterno, ape_materno, nombres, dni, codigo, email, programa, estado, fecha_registro)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ";

    $stmt = $cn->prepare($sql);
    if (!$stmt) throw new Exception($cn->error);

    $stmt->bind_param("isssssss", $id_estu, $ape_paterno, $ape_materno, $nombres, $dni, $codigo, $email, $programa);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
  }

  /* ===================== DESACTIVAR ESTUDIANTE ===================== */
  if ($accion === 'desactivar') {

    $id_estu = (int)($_POST['id_estu'] ?? 0);
    if ($id_estu <= 0) {
      echo json_encode([
        'ok' => false,
        'error' => 'id_estu inválido',
        'debug' => ['post' => $_POST]
      ]);
      exit;
    }

    // Desactiva todas las filas por si hubiera duplicados
    $stmt = $cn->prepare("UPDATE tutoria_estudiantes_conadis SET estado=0, fecha_baja=NOW() WHERE id_estu=?");
    if (!$stmt) {
      echo json_encode(['ok' => false, 'error' => 'Prepare failed: '.$cn->error]);
      exit;
    }

    $stmt->bind_param("i", $id_estu);
    $stmt->execute();

    $af  = $stmt->affected_rows;
    $err = $stmt->error;
    $stmt->close();

    if ($err) {
      echo json_encode(['ok' => false, 'error' => 'Error UPDATE: '.$err]);
      exit;
    }

    //Verificación real (lo que quedó en BD)
    $chk = $cn->prepare("SELECT id_conadis, id_estu, estado, fecha_baja FROM tutoria_estudiantes_conadis WHERE id_estu=?");
    $chk->bind_param("i", $id_estu);
    $chk->execute();
    $res = $chk->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $chk->close();

    echo json_encode([
      'ok' => true,
      'msg' => 'Desactivado',
      'affected_rows' => $af,
      'debug' => [
        'archivo' => __FILE__,
        'id_estu' => $id_estu,
        'rows' => $rows
      ]
    ]);
    exit;
  }


  echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
  exit;

} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
  exit;
}
