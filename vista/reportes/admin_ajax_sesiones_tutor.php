<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

header('Content-Type: application/json; charset=utf-8');

$ROLES_OK = ['COORDINADOR GENERAL DE TUTORIA','SUPERVISION','VICEPRESIDENCIA ACADEMICA'];
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], $ROLES_OK)) {
  echo json_encode(['ok'=>false, 'msg'=>'Acceso no autorizado']); exit;
}

/* ===== Parámetros ===== */
$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$id_doce     = (int)($_GET['id_doce'] ?? 0);   // OPCIONAL (0 = todos)
$id_car      = (int)($_GET['id_car'] ?? 0);    // REQUERIDO (>0) para Supervisión por carrera
$mes_nombre  = strtolower($_GET['mes'] ?? '');

$map = ['enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];
$mes_num = $map[$mes_nombre] ?? 0;

if ($id_semestre <= 0 || $mes_num <= 0 || $id_car <= 0) {
  echo json_encode(['ok'=>false, 'msg'=>'Parámetros inválidos']); exit;
}

$cn = (new conexion())->conectar();

/* 
  Notas:
  - Filtramos por carrera via docente (d.id_car).
  - id_doce es opcional: si llega 0 no filtra por docente.
  - Para el "formato" usamos un mapeo simple: tipo_id=1 => F7 (grupal); demás => F8 (individual).
    Ajusta si tu catálogo difiere.
  - cnt_det: cantidad de registros en tutoria_detalle_sesion (asistentes/rating).
*/

$sql = "
SELECT
  s.id_sesiones_tuto                                AS sesion_id,
  s.id_doce                                         AS id_doce,
  s.id_carga                                        AS id_carga,
  tda.id_car                                        AS id_car,              -- << NUEVO: carrera
  DATE_FORMAT(s.fecha,'%d/%m/%Y')                   AS fecha,
  TIME_FORMAT(s.horaInicio,'%H:%i')                 AS inicio,
  TIME_FORMAT(s.horaFin,'%H:%i')                    AS fin,
  s.tema                                            AS tema,
  s.tipo_sesion_id                                  AS tipo_id,
  CASE
    WHEN s.tipo_sesion_id = 5 AND NULLIF(s.reunion_tipo_otros,'') IS NOT NULL
      THEN CONCAT(tt.des_tipo, ': ', s.reunion_tipo_otros)
    ELSE tt.des_tipo
  END                                               AS tipo,
  s.reunion_tipo_otros                              AS modalidad_txt,
  s.color                                           AS color,
  NULLIF(NULLIF(s.evidencia_1,''),'NULL')           AS evi1,
  NULLIF(NULLIF(s.evidencia_2,''),'NULL')           AS evi2,
  (
    (CASE WHEN NULLIF(NULLIF(s.evidencia_1,''),'NULL') IS NOT NULL THEN 1 ELSE 0 END) +
    (CASE WHEN NULLIF(NULLIF(s.evidencia_2,''),'NULL') IS NOT NULL THEN 1 ELSE 0 END)
  )                                                 AS evi_count,
  COALESCE(SUM(td.valoracion_tuto),0)               AS suma,
  SUM(CASE WHEN td.valoracion_tuto > 0 THEN 1 ELSE 0 END) AS votos,
  COUNT(DISTINCT td.id_estu)                  AS cnt_det
FROM tutoria_sesiones_tutoria_f78 s
/* Carrera del docente por semestre (para AULA y para exponer id_car) */
JOIN (
  SELECT DISTINCT id_doce, id_semestre, id_car
  FROM tutoria_docente_asignado
) tda
  ON tda.id_doce  = s.id_doce
 AND tda.id_semestre = s.id_semestre
LEFT JOIN tutoria_detalle_sesion td
       ON td.sesiones_tutoria_id = s.id_sesiones_tuto
LEFT JOIN tutoria_tipo_sesion tt
       ON tt.id_tipo_sesion = s.tipo_sesion_id
WHERE
  s.id_rol      = 6              -- si Admin debe ver Aula+Curso: IN (2,6)
  AND s.id_semestre = ?
  AND s.id_doce     = ?
  AND MONTH(s.fecha)= ?
  /* Filtrado por carrera:
     - AULA  (s.id_carga IS NULL)  -> tda.id_car
     - CURSO (s.id_carga IS NOT NULL) -> carga_lectiva.id_car
  */
  AND (
        (s.id_carga IS NULL     AND tda.id_car = ?)
     OR (s.id_carga IS NOT NULL AND EXISTS (
            SELECT 1
            FROM carga_lectiva cl
            WHERE cl.id_cargalectiva = s.id_carga
              AND cl.id_semestre     = s.id_semestre
              AND cl.id_car          = ?
        ))
      )
GROUP BY
  s.id_sesiones_tuto, s.id_doce, s.id_carga, tda.id_car,
  s.fecha, s.horaInicio, s.horaFin, s.tema,
  s.tipo_sesion_id, tipo, s.reunion_tipo_otros, s.color, evi1, evi2
ORDER BY s.fecha ASC, s.horaInicio ASC;

";

$stmt = $cn->prepare($sql);
if (!$stmt) {
  echo json_encode(['ok'=>false,'msg'=>'Prep error','sql_error'=>$cn->error]); exit;
}

// Orden real de los placeholders: id_semestre, id_doce, mes_num, id_car, id_car
$stmt->bind_param('iiiii', $id_semestre, $id_doce, $mes_num, $id_car, $id_car);

if (!$stmt->execute()) {
  echo json_encode(['ok'=>false,'msg'=>'Exec error','sql_error'=>$stmt->error]); exit;
}
$res = $stmt->get_result();

/* Base para absolutizar rutas de evidencias */
$BASE_URL = 'https://tutoria.undc.edu.pe/'; // cambia a producción cuando subas
function abs_url($p,$b){ $p=trim((string)$p); if($p===''||strcasecmp($p,'NULL')===0)return ''; if(preg_match('~^https?://~i',$p))return $p; return rtrim($b,'/').'/'.ltrim($p,'/'); }

$sesiones=[]; $total_suma=0; $total_votos=0;

while($r = $res->fetch_assoc()){
  $votos = (int)$r['votos'];
  $suma  = (float)$r['suma'];
  $avg   = $votos > 0 ? round($suma/$votos, 2) : 0.0;

  $tipo_id = (int)$r['tipo_id'];
  $formato = ($tipo_id === 1) ? 'F7' : 'F8';       // AJUSTA si tu catálogo cambia
  $formato_enabled = true;                         // si quieres condicionar, usa ($r['cnt_det'] > 0)

  $evi1 = $r['evi1'] ?? '';
  $evi2 = $r['evi2'] ?? '';

   // NUEVO: lógica de formato y habilitado
  $cnt_det       = (int)$r['cnt_det'];
  $es_individual = ($cnt_det === 1);
  $formato       = $es_individual ? 'F8' : 'F7';
  $color         = strtolower((string)($r['color'] ?? ''));
  $formato_enabled = ($color === '#00a65a'); // sólo habilitado si está completa

  $sesiones[] = [
    'id'               => (int)$r['sesion_id'],
    'id_doce'          => (int)$r['id_doce'],
    'id_car'           => (int)$r['id_car'],
    'fecha'            => $r['fecha'],
    'inicio'           => $r['inicio'],
    'fin'              => $r['fin'],
    'tema'             => $r['tema'],
    'tipo_id'          => $tipo_id,
    'tipo'             => $r['tipo'],
    'modalidad'        => $r['modalidad_txt'],
    'color'            => $r['color'],
    'votos'            => $votos,
    'avg'              => $avg,
    'cnt_det'          => (int)$r['cnt_det'],
    'evi_count'        => (int)$r['evi_count'],
    'evi1'             => abs_url($evi1,$BASE_URL),
    'evi2'             => abs_url($evi2,$BASE_URL),
    'formato'          => $formato,               // "F7" / "F8"
    'formato_enabled'  => $formato_enabled        // bool
  ];

  $total_suma  += $suma;
  $total_votos += $votos;
}

echo json_encode([
  'ok'        => true,
  'mes_label' => ucfirst($mes_nombre),
  'sesiones'  => $sesiones,
  'overall'   => [
    'avg'        => $total_votos ? round($total_suma/$total_votos, 2) : 0,
    'votos'      => $total_votos,
    'n_sesiones' => count($sesiones)
  ]
], JSON_UNESCAPED_UNICODE);

ob_end_flush();
exit;
