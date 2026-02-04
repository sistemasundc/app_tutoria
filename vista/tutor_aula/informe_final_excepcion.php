<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

/* =======================
   1) SEGURIDAD
   ======================= */
if (!isset($_SESSION['S_IDUSUARIO']) || ($_SESSION['S_ROL'] ?? '') !== 'TUTOR DE AULA') {
  die('Acceso no autorizado');
}

$id_doce = (int)$_SESSION['S_IDUSUARIO'];

/* SOLO DOCENTE AUTORIZADO (400) */
if ($id_doce !== 400) {
  die('Acceso no autorizado');
}

/* =======================
   2) CONFIG EXCEPCIÓN
   ======================= */
$SEMESTRE_OBJ = 32; // 2025-I (fijo para regularización)

/* =======================
   3) CONEXIÓN
   ======================= */
$conexion = new conexion();
$conexion->conectar();

/* =======================
   4) VALIDAR QUE ES TUTOR EN ESE SEMESTRE
   ======================= */
$sqlTutor = "SELECT 1
             FROM tutoria_docente_asignado
             WHERE id_doce = ? AND id_semestre = ?
             LIMIT 1";
$stmtTutor = $conexion->conexion->prepare($sqlTutor);
$stmtTutor->bind_param("ii", $id_doce, $SEMESTRE_OBJ);
$stmtTutor->execute();
$esTutor = (bool)$stmtTutor->get_result()->fetch_assoc();
$stmtTutor->close();

/* =======================
   5) OBTENER CARGAS (id_carga) ASIGNADAS EN TUTORÍA
   ======================= */
$cargas = [];
if ($esTutor) {
  $sqlCargas = "SELECT DISTINCT id_carga
                FROM tutoria_asignacion_tutoria
                WHERE id_docente = ? AND id_semestre = ?
                ORDER BY id_carga DESC";
  $stmtC = $conexion->conexion->prepare($sqlCargas);
  $stmtC->bind_param("ii", $id_doce, $SEMESTRE_OBJ);
  $stmtC->execute();
  $resC = $stmtC->get_result();
  while ($row = $resC->fetch_assoc()) {
    $cargas[] = (int)$row['id_carga'];
  }
  $stmtC->close();
}

/* =======================
   6) SELECCIÓN DE CARGA
   - Si viene por GET, usarla
   - Si no viene y hay 1 sola carga, tomarla automático
   ======================= */
$id_carga_sel = isset($_GET['id_carga']) ? (int)$_GET['id_carga'] : 0;

if ($id_carga_sel === 0 && count($cargas) === 1) {
  $id_carga_sel = $cargas[0];
}

/* =======================
   7) CONSULTAR ESTADO DE ENVÍO (si ya hay carga seleccionada)
   ======================= */
$estado_envio = 0; // 0=no existe registro, 1=guardado, 2=enviado

if ($id_carga_sel > 0 && $esTutor) {

  // Validar que esa carga pertenece al docente en semestre 32
  $sqlVal = "SELECT 1
             FROM tutoria_asignacion_tutoria
             WHERE id_docente = ? AND id_semestre = ? AND id_carga = ?
             LIMIT 1";
  $stmtVal = $conexion->conexion->prepare($sqlVal);
  $stmtVal->bind_param("iii", $id_doce, $SEMESTRE_OBJ, $id_carga_sel);
  $stmtVal->execute();
  $okCarga = (bool)$stmtVal->get_result()->fetch_assoc();
  $stmtVal->close();

  if ($okCarga) {
    $sqlInf = "SELECT estado_envio
               FROM tutoria_informe_final_aula
               WHERE id_cargalectiva = ?
                 AND id_doce = ?
                 AND semestre_id = ?
               ORDER BY id_informe_final DESC
               LIMIT 1";
    $stmtInf = $conexion->conexion->prepare($sqlInf);
    $stmtInf->bind_param("iii", $id_carga_sel, $id_doce, $SEMESTRE_OBJ);
    $stmtInf->execute();
    $rowInf = $stmtInf->get_result()->fetch_assoc();
    $stmtInf->close();

    $estado_envio = (int)($rowInf['estado_envio'] ?? 0);
  } else {
    // Si no pertenece, anular selección
    $id_carga_sel = 0;
    $estado_envio = 0;
  }
}

$conexion->cerrar();

/* Mensajes */
$msg = $_GET['msg'] ?? '';
?>
<div style="max-width:900px;margin:20px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
  <h3 style="background:#0056b3;color:#fff;padding:10px;border-radius:6px;margin-top:0;">
    Regularización de Informe Final (Semestre 2025-I)
  </h3>

  <?php if (!$esTutor): ?>
    <div style="padding:12px;border-radius:6px;background:#f8d7da;color:#842029;">
      El docente no figura como Tutor de Aula asignado en el semestre 2025-I (ID: 32).
    </div>

  <?php elseif (empty($cargas)): ?>
    <div style="padding:12px;border-radius:6px;background:#f8d7da;color:#842029;">
      No se encontró carga lectiva (id_carga) asignada en tutoría para el semestre 2025-I.
    </div>

  <?php else: ?>

    <?php if ($msg === 'enviado'): ?>
      <div style="padding:12px;border-radius:6px;background:#d1e7dd;color:#0f5132;font-weight:bold;">
        ✅ Informe regularizado exitosamente.
      </div><br>
    <?php elseif ($msg === 'guardado'): ?>
      <div style="padding:12px;border-radius:6px;background:#cff4fc;color:#055160;font-weight:bold;">
        💾 Informe guardado correctamente. Ya puede proceder a enviarlo.
      </div><br>
    <?php endif; ?>

    <?php if (count($cargas) > 1 && $id_carga_sel === 0): ?>
      <div style="padding:12px;border-radius:6px;background:#fff3cd;color:#664d03;">
        Seleccione la carga lectiva (aula) para regularizar el Informe Final 2025-I:
      </div><br>

      <?php foreach ($cargas as $c): ?>
        <button class="btn btn-default" style="margin:4px;"
          onclick="cargar_contenido('contenido_principal','tutor_aula/informe_final_excepcion.php?id_carga=<?= (int)$c ?>')">
          Carga <?= (int)$c ?>
        </button>
      <?php endforeach; ?>

    <?php else: ?>

      <?php if ($id_carga_sel === 0): ?>
        <div style="padding:12px;border-radius:6px;background:#fff3cd;color:#664d03;">
          No se pudo determinar la carga lectiva para regularizar.
        </div>

      <?php else: ?>

        <?php if ($estado_envio === 2): ?>
          <div style="padding:12px;border-radius:6px;background:#d1e7dd;color:#0f5132;font-weight:bold;">
            ✅ Este informe ya fue enviado. No requiere acciones adicionales.
          </div>

        <?php else: ?>
          <div style="padding:12px;border-radius:6px;background:#fff3cd;color:#664d03;">
            ⚠️ Usted tiene un envío pendiente de Informe Final del semestre 2025-I.
          </div><br>

          <button class="btn btn-primary"
            onclick="cargar_contenido('contenido_principal','tutor_aula/form_informe_final_excepcion.php?id_cargalectiva=<?= (int)$id_carga_sel ?>')">
            Enviar informe final
          </button>
        <?php endif; ?>

      <?php endif; ?>

    <?php endif; ?>

  <?php endif; ?>
</div>
