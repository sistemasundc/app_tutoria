<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECCION DE ESCUELA', 'DIRECTOR DE DEPARTAMENTO ACADEMICO', 'COMITÉ - SUPERVISIÓN'])) {
  echo json_encode(['ok'=>false, 'msg'=>'Acceso no autorizado']); exit;
}

$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$id_doce     = (int)($_GET['id_doce'] ?? 0);
$mes_nombre  = strtolower($_GET['mes'] ?? '');
$map = ['enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];
$mes_num = $map[$mes_nombre] ?? 0;

if (!$id_semestre || !$id_doce || !$mes_num) {
  echo json_encode(['ok'=>false, 'msg'=>'Parámetros inválidos']); exit;
}

$cn = (new conexion())->conectar();

/*
  Trae las sesiones del mes del docente (rol 6).
  - SUM(valoracion_tuto) y votos
  - EVIDENCIAS
  - color (para habilitar botón formato)
  - cnt_det = número de estudiantes en el detalle (para deducir F7/F8)
*/
$sql = "
SELECT
  s.id_sesiones_tuto                           AS sesion_id,
  DATE_FORMAT(s.fecha,'%d/%m/%Y')              AS fecha,
  s.horaInicio                                 AS inicio,
  s.horaFin                                    AS fin,
  s.tema                                       AS tema,
  s.tipo_sesion_id                             AS tipo_id,
  CASE
    WHEN s.tipo_sesion_id = 5 AND NULLIF(s.reunion_tipo_otros,'') IS NOT NULL
      THEN CONCAT(tt.des_tipo, ': ', s.reunion_tipo_otros)
    ELSE tt.des_tipo
  END                                          AS tipo,
  COALESCE(SUM(td.valoracion_tuto),0)          AS suma,
  SUM(CASE WHEN td.valoracion_tuto > 0 THEN 1 ELSE 0 END) AS votos,

  /* === EVIDENCIAS === */
  s.evidencia_1,
  s.evidencia_2,
  (
    CASE WHEN s.evidencia_1 IS NOT NULL AND s.evidencia_1 <> '' THEN 1 ELSE 0 END +
    CASE WHEN s.evidencia_2 IS NOT NULL AND s.evidencia_2 <> '' THEN 1 ELSE 0 END
  ) AS evi_count,

  /* === NUEVO: color para habilitar Formato === */
  s.color                                       AS color,

  /* === NUEVO: cantidad de estudiantes en el detalle (para F7/F8) === */
  COALESCE(det.cnt_det, 0)                      AS cnt_det

FROM tutoria_sesiones_tutoria_f78 s
LEFT JOIN tutoria_detalle_sesion td
       ON td.sesiones_tutoria_id = s.id_sesiones_tuto
LEFT JOIN tutoria_tipo_sesion tt
       ON tt.id_tipo_sesion = s.tipo_sesion_id
/* NUEVO: subconsulta que cuenta asistentes por sesión */
LEFT JOIN (
  SELECT d.sesiones_tutoria_id, COUNT(*) AS cnt_det
  FROM tutoria_detalle_sesion d
  GROUP BY d.sesiones_tutoria_id
) det ON det.sesiones_tutoria_id = s.id_sesiones_tuto

WHERE s.id_rol = 6
  AND s.id_semestre = ?
  AND s.id_doce = ?
  AND MONTH(s.fecha) = ?
GROUP BY s.id_sesiones_tuto
ORDER BY s.fecha ASC;
";

$stmt = $cn->prepare($sql);
$stmt->bind_param('iii', $id_semestre, $id_doce, $mes_num);
$stmt->execute();
$res = $stmt->get_result();

 $BASE_URL = 'https://tutoria.undc.edu.pe/'; 
function abs_url($path, $base) {
  $path = trim((string)$path);
  if ($path === '' || strcasecmp($path, 'NULL') === 0) return '';
  if (preg_match('~^https?://~i', $path)) return $path;
  $path = ltrim($path, '/');
  return rtrim($base, '/') . '/' . $path;
}

$sesiones = [];
$total_suma = 0; $total_votos = 0;

while ($r = $res->fetch_assoc()) {
  $votos = (int)$r['votos'];
  $suma  = (float)$r['suma'];
  $avg   = $votos > 0 ? round($suma / $votos, 2) : 0;

  $evi1 = $r['evidencia_1'] ?? '';
  $evi2 = $r['evidencia_2'] ?? '';

  // NUEVO: lógica de formato y habilitado
  $cnt_det       = (int)$r['cnt_det'];
  $es_individual = ($cnt_det === 1);
  $formato       = $es_individual ? 'F8' : 'F7';
  $color         = strtolower((string)($r['color'] ?? ''));
  $formato_enabled = ($color === '#00a65a'); // sólo habilitado si está completa

  $sesiones[] = [
    'id'              => (int)$r['sesion_id'],
    'id_sesion'       => (int)$r['sesion_id'],     // por compatibilidad con el front
    'fecha'           => $r['fecha'],
    'inicio'          => $r['inicio'],
    'fin'             => $r['fin'],
    'tema'            => $r['tema'],
    'tipo_id'         => (int)$r['tipo_id'],
    'tipo'            => $r['tipo'],
    'votos'           => $votos,
    'avg'             => $avg,
    'evi_count'       => (int)$r['evi_count'],
    'evi1'            => abs_url($evi1, $BASE_URL),
    'evi2'            => abs_url($evi2, $BASE_URL),

    // ===== NUEVO: datos para el botón Formato =====
    'color'           => $r['color'],              // p.ej. "#00a65a"
    'cnt_det'         => $cnt_det,                 // 1 => individual
    'es_individual'   => $es_individual,           // boolean
    'formato'         => $formato,                 // "F7" o "F8"
    'formato_enabled' => $formato_enabled          // boolean
  ];

  $total_suma  += $suma;
  $total_votos += $votos;
}

$overall_avg = $total_votos > 0 ? round($total_suma / $total_votos, 2) : 0;

echo json_encode([
  'ok' => true,
  'mes_label' => ucfirst($mes_nombre),
  'sesiones' => $sesiones,
  'overall'  => ['avg' => $overall_avg, 'votos' => $total_votos, 'n_sesiones' => count($sesiones)]
]);
