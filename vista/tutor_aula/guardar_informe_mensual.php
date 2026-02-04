<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/Lima');
require_once("../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE AULA') {
  die('Acceso denegado');
}

$id_doce     = (int)$_SESSION['S_IDUSUARIO'];
$id_semestre = (int)$_SESSION['S_SEMESTRE'];
$id_car      = isset($_SESSION['S_SCHOOL']) ? (int)$_SESSION['S_SCHOOL'] : null;

$accion          = $_POST['accion'] ?? 'guardar';
$id_plan_tutoria = isset($_POST['id_plan_tutoria']) ? (int)$_POST['id_plan_tutoria'] : null;
$id_cargalectiva = isset($_POST['id_cargalectiva']) ? (int)$_POST['id_cargalectiva'] : null;

$mes_informe     = strtolower(trim($_POST['mes_informe'] ?? '')); // texto: "septiembre"
$mes_post        = $_POST['mes'] ?? null;                          // puede venir número o texto

$numero_informe     = trim($_POST['numero_informe'] ?? '');
$para_director      = trim($_POST['director'] ?? '');
$asunto             = trim($_POST['asunto'] ?? '');
$resultados_finales = trim($_POST['resultados_finales'] ?? '');
$logros             = trim($_POST['logros'] ?? '');
$dificultades       = trim($_POST['dificultades'] ?? '');

if (!$id_plan_tutoria || !$mes_informe || !$id_cargalectiva) {
  die('Faltan datos obligatorios');
}

$estado_envio = ($accion === 'enviar') ? 2 : 1;
$fecha_envio  = ($accion === 'enviar') ? date("Y-m-d H:i:s") : null;

/* ==== Mes texto -> número ==== */
$mapMes = [
  'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
  'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
];

if (is_numeric($mes_post)) {
  $mes_num = (int)$mes_post;
} else {
  $mes_num = $mapMes[strtolower(trim((string)$mes_post ?: $mes_informe))] ?? 0;
}

$cnx = new conexion();
$cnx->conectar();
$cn = $cnx->conexion;

/* ==== Helpers ==== */
$faltan_obligatorios = (
  $numero_informe === '' ||
  $para_director === ''  ||
  $asunto === ''         ||
  $resultados_finales === '' ||
  $logros === '' ||
  $dificultades === ''
);

/* ==== Conteo de sesiones del mes (rol 6) para el docente ==== */
/* total sesiones (00a65a o 3c8dbc) y total COMPLETAS (solo 00a65a) */
$sesionesMes          = 0;
$sesionesCompletasMes = 0;

if ($mes_num > 0) {

  // TOTAL sesiones del mes (00a65a o 3c8dbc)
  $sqlSesTotal = "
    SELECT COUNT(*) AS c
    FROM tutoria_sesiones_tutoria_f78 s
    WHERE s.id_rol = 6
      AND s.id_doce = ?
      AND s.id_semestre = ?
      AND MONTH(s.fecha) = ?
      AND s.color IN ('#00a65a','#3c8dbc')
  ";
  if ($stmt = $cn->prepare($sqlSesTotal)) {
    $stmt->bind_param('iii', $id_doce, $id_semestre, $mes_num);
    $stmt->execute();
    $stmt->bind_result($sesionesMes);
    $stmt->fetch();
    $stmt->close();
  }

  // SOLO sesiones COMPLETAS (color #00a65a)
  $sqlSesOk = "
    SELECT COUNT(*) AS c
    FROM tutoria_sesiones_tutoria_f78 s
    WHERE s.id_rol = 6
      AND s.id_doce = ?
      AND s.id_semestre = ?
      AND MONTH(s.fecha) = ?
      AND s.color = '#00a65a'
  ";
  if ($stmtOk = $cn->prepare($sqlSesOk)) {
    $stmtOk->bind_param('iii', $id_doce, $id_semestre, $mes_num);
    $stmtOk->execute();
    $stmtOk->bind_result($sesionesCompletasMes);
    $stmtOk->fetch();
    $stmtOk->close();
  }
}

/* ==== Validaciones al ENVIAR ==== */
if ($accion === 'enviar') {

  // 1) campos obligatorios
  if ($faltan_obligatorios) {
    $_SESSION['alerta_informe'] = 'faltan_campos';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "https://tutoria.undc.edu.pe/vista/index.php?pagina=tutor_aula/form_informe_mensual.php&id_cargalectiva=$id_cargalectiva&mes=$mes_num"));
    exit;
  }

  // 2) sesiones: debe existir al menos 1 sesión del mes
  //    y al menos 1 sesión COMPLETA (color #00a65a)
  if ($sesionesMes < 1 || $sesionesCompletasMes < 1) {
    $_SESSION['alerta_informe'] = 'sin_sesiones';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "https://tutoria.undc.edu.pe/vista/index.php?pagina=tutor_aula/form_informe_mensual.php&id_cargalectiva=$id_cargalectiva&mes=$mes_num"));
    exit;
  }
}

/* ==== Insertar/Actualizar ==== */
$sqlBuscar = "SELECT id_informe FROM tutoria_informe_mensual 
              WHERE id_plan_tutoria = ? AND mes_informe = ? AND id_cargalectiva = ? AND id_docente = ?";
$stmtBuscar = $cn->prepare($sqlBuscar);
if (!$stmtBuscar) die("Error en prepare (buscar): " . $cn->error);
$stmtBuscar->bind_param("isii", $id_plan_tutoria, $mes_informe, $id_cargalectiva, $id_doce);
$stmtBuscar->execute();
$resBuscar = $stmtBuscar->get_result();
$existe = $resBuscar->fetch_assoc();
$stmtBuscar->close();

if (!$existe) {
  // INSERT
  $sqlInsert = "INSERT INTO tutoria_informe_mensual
    (id_plan_tutoria, id_cargalectiva, mes_informe, numero_informe, para_director, asunto,
     resultados_finales, logros, dificultades, estado_envio, fecha_envio, id_car, id_docente, id_semestre)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
  $stmtIns = $cn->prepare($sqlInsert);
  if (!$stmtIns) die("Error en prepare (insert): " . $cn->error);
  // TIPOS: ii + sssssss + i + s + iii  => "iisssssssisiii"
  $stmtIns->bind_param(
    "iisssssssisiii",
    $id_plan_tutoria, $id_cargalectiva, $mes_informe,
    $numero_informe, $para_director, $asunto,
    $resultados_finales, $logros, $dificultades,
    $estado_envio, $fecha_envio, $id_car, $id_doce, $id_semestre
  );
  if (!$stmtIns->execute()) {
    die("Error al insertar informe: " . $stmtIns->error);
  }
  $stmtIns->close();
} else {
  // UPDATE
  $sqlUpdate = "UPDATE tutoria_informe_mensual
    SET numero_informe = ?, para_director = ?, asunto = ?, resultados_finales = ?,
        logros = ?, dificultades = ?, estado_envio = ?, fecha_envio = ?, id_semestre = ?
    WHERE id_plan_tutoria = ? AND mes_informe = ? AND id_cargalectiva = ? AND id_docente = ?";
  $stmtUpd = $cn->prepare($sqlUpdate);
  if (!$stmtUpd) die("Error en prepare (update): " . $cn->error);
  // TIPOS: ssssss i s i i s i i  => "ssssssisiisii"
  $stmtUpd->bind_param(
    "ssssssisiisii",
    $numero_informe, $para_director, $asunto,
    $resultados_finales, $logros, $dificultades,
    $estado_envio, $fecha_envio, $id_semestre,
    $id_plan_tutoria, $mes_informe, $id_cargalectiva, $id_doce
  );
  if (!$stmtUpd->execute()) {
    die("Error al actualizar informe: " . $stmtUpd->error);
  }
  $stmtUpd->close();
}

/* ==== Mensaje final según acción ==== */
$_SESSION['alerta_informe'] = ($accion === 'enviar') ? 'enviado' : 'guardado';

/* ==== Redirección canónica al formulario ==== */
$mes_redirect = ($mes_num > 0) ? $mes_num : urlencode($mes_informe); // num si existe, si no, texto
$query = http_build_query([
  'pagina'         => 'tutor_aula/form_informe_mensual.php',
  'id_cargalectiva'=> (int)$id_cargalectiva,
  'mes'            => $mes_redirect,
]);

// Usa absoluta si prefieres:
$redirect_url = "https://tutoria.undc.edu.pe/vista/index.php?{$query}#acciones-form";
header("Location: {$redirect_url}");
exit;
