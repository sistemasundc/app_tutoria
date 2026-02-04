<?php
// controlador/tutor_aula/controlador_derivar_alumno.php
session_start();
date_default_timezone_set('America/Lima');

require_once '../../modelo/modelo_docente.php';
require_once '../../modelo/modelo_conexion.php';
require_once '../../vendor/autoload.php';
require_once '../../lib/MailerDerivacion.php';

$MD = new Docente();
$CN = new conexion();
$cn = $CN->conectar();
$cn->set_charset('utf8mb4');

/* ===== ENTRADAS ===== */
$fecha       = date('Y-m-d');
$hora        = date('H:i:s');
$motivo      = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
$area        = (int)($_POST['area'] ?? 0);
$id_estu     = (int)($_POST['estu'] ?? 0);
$id_doce     = (int)($_POST['doce'] ?? 0);
$id_asig     = (int)($_POST['asig'] ?? 0);
$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);

/* ===== 1) ESCRIBIR EN BD ===== */
$ok = $MD->DerivarAlumno_TA(
  $fecha,
  $hora,
  htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'),
  $area,
  $id_estu,
  $id_doce,
  $id_semestre
);

if ($ok == 1) {
  $ok = $MD->UpdateDerivado_TA($id_estu, $id_asig, $area);
}

/* ===== 2) RESPUESTA INMEDIATA ===== */
header('Content-Type: application/json');
if ($ok == 1) {
  echo json_encode(['ok' => true, 'msg' => 'Derivación registrada. Se está notificando al área.']);
} else {
  echo json_encode(['ok' => false, 'msg' => 'No se pudo completar la derivación.']);
}

/* ===== 3) LIBERAR EL REQUEST ===== */
session_write_close();
if (function_exists('fastcgi_finish_request')) {
  fastcgi_finish_request();
} else {
  @ob_end_flush(); @flush();
}

/* ===== 4) ENVÍO DE CORREO (no bloquea al usuario) ===== */
if ($ok == 1) {
  try {
    // Área de apoyo
    $sa = $cn->prepare("
      SELECT des_area_apo AS nombre_area, email
      FROM tutoria_area_apoyo
      WHERE idarea_apoyo = ?
    ");
    $sa->bind_param('i', $area);
    $sa->execute();
    $areaRow = $sa->get_result()->fetch_assoc();
    $sa->close();

    if (!$areaRow || empty($areaRow['email'])) {
      error_log('[MAIL WARN] Área sin email configurado (idarea_apoyo='.$area.')');
      return;
    }

    // Estudiante (asignación más reciente del semestre)
    $se = $cn->prepare("
      SELECT 
        e.apepa_estu, e.apema_estu, e.nom_estu, e.email_estu,
        cl.ciclo       AS ciclo,
        cl.turno       AS turno,
        cl.seccion     AS seccion,
        c.nom_car      AS carrera
      FROM estudiante e
      LEFT JOIN asignacion_estudiante ae
            ON ae.id_estu = e.id_estu
           AND ae.id_semestre = ?
      LEFT JOIN carga_lectiva cl
            ON cl.id_cargalectiva = ae.id_cargalectiva
      LEFT JOIN carrera c
            ON c.id_car = cl.id_car
      WHERE e.id_estu = ?
      ORDER BY ae.id_aestu DESC
      LIMIT 1
    ");
    $se->bind_param('ii', $id_semestre, $id_estu);
    $se->execute();
    $estu = $se->get_result()->fetch_assoc();
    $se->close();

    // Docente
    $sd = $cn->prepare("
      SELECT abreviatura_doce, apepa_doce, apema_doce, nom_doce, email_doce
      FROM docente
      WHERE id_doce = ?
    ");
    $sd->bind_param('i', $id_doce);
    $sd->execute();
    $doc = $sd->get_result()->fetch_assoc();
    $sd->close();

    // Datos para el correo
    $nombre_estu = trim(
      ($estu['apepa_estu'] ?? '') . ' ' .
      ($estu['apema_estu'] ?? '') . ', ' .
      ($estu['nom_estu']  ?? '')
    );

    $nombre_doc = trim(
      (!empty($doc['abreviatura_doce']) ? $doc['abreviatura_doce'].' ' : '') .
      ($doc['apepa_doce'] ?? '') . ' ' .
      ($doc['apema_doce'] ?? '') . ', ' .
      ($doc['nom_doce']   ?? '')
    );

    $data = [
      'area' => $areaRow['nombre_area'] ?? 'Área de Apoyo',
      'estu' => [
        'nombre'  => $nombre_estu,
        'correo'  => $estu['email_estu'] ?? '',
        'carrera' => $estu['carrera']    ?? '',
        'ciclo'   => $estu['ciclo']      ?? '',
        'turno'   => $estu['turno']      ?? '',
        'seccion' => $estu['seccion']    ?? '',
      ],
      'tutor' => [
        'nombre' => $nombre_doc,
        'correo' => $doc['email_doce'] ?? '',
      ],
      'motivo' => $motivo,
      'fecha'  => date('Y-m-d H:i'),
      'link'   => 'https://tutoria.undc.edu.pe/', // cambia a la URL real en producción
    ];

    $to     = preg_split('/\s*,\s*/', $areaRow['email'], -1, PREG_SPLIT_NO_EMPTY);
    $mailer = new MailerDerivacion();
    $sent   = $mailer->enviar($to, $data);

    if (!$sent) {
      error_log('[MAIL ERROR] No se pudo enviar notificación de derivación (id_estu='.$id_estu.').');
    }
  } catch (Throwable $e) {
    error_log('[MAIL EXCEPTION] '.$e->getMessage());
  }
}
