<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

/* ===== Seguridad (LISTADO) ===== */
if (!isset($_SESSION['S_IDUSUARIO']) || ($_SESSION['S_ROL'] ?? '') !== 'DIRECCION DE ESCUELA') {
  die('Acceso no autorizado');
}

// semestre por sesión
$semestre_sesion = (int)($_SESSION['S_SEMESTRE'] ?? 0);

// semestre seleccionado en el filtro (GET) o el de sesión
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
  ? (int)$_GET['semestre']
  : $semestre_sesion;

$id_director  = (int)$_SESSION['S_IDUSUARIO'];

$conexion = new conexion();
$cn = $conexion->conectar();

/*
  ✅ LISTAR POR CARGA LECTIVA (individual)
  ✅ MOSTRAR CONFORMIDAD aunque la haya registrado OTRO director
  ✅ Traer SOLO la ÚLTIMA revisión por (plan+carga+docente+semestre)
*/
$sql = "
SELECT
  cl.ciclo,
  cl.id_cargalectiva,
  cl.id_doce           AS id_docente,
  d.abreviatura_doce,
  d.apepa_doce,
  d.apema_doce,
  d.nom_doce,
  pc.id_plan_tutoria,
  pc.estado_envio,
  pc.fecha_envio,

  /* ✅ estado_revision: ya NO depende de >= fecha_envio */
  CASE
    WHEN trd_especifica.fecha_revision IS NOT NULL
      THEN trd_especifica.estado_revision
    WHEN trd_global.fecha_revision IS NOT NULL
      THEN trd_global.estado_revision
  END AS estado_revision,

  /* ✅ etiqueta visible: CONFORME / INCONFORME / SUBSANADO / REENVIADO */
  CASE
    /* Si existe revisión específica (con carga/docente) */
    WHEN trd_especifica.fecha_revision IS NOT NULL THEN
      CASE
        /* si el plan fue reenviado DESPUÉS de la revisión */
        WHEN pc.fecha_envio IS NOT NULL AND pc.fecha_envio > trd_especifica.fecha_revision
          THEN
            CASE
              WHEN trd_especifica.estado_revision = 'INCONFORME' THEN 'SUBSANADO'
              ELSE 'REENVIADO'
            END
        ELSE trd_especifica.estado_revision
      END

    /* Si existe revisión global (histórica con NULLs) */
    WHEN trd_global.fecha_revision IS NOT NULL THEN
      CASE
        WHEN pc.fecha_envio IS NOT NULL AND pc.fecha_envio > trd_global.fecha_revision
          THEN
            CASE
              WHEN trd_global.estado_revision = 'INCONFORME' THEN 'SUBSANADO'
              ELSE 'REENVIADO'
            END
        ELSE trd_global.estado_revision
      END
  END AS estado_etiqueta,

  /* ✅ comentario */
  CASE
    WHEN trd_especifica.fecha_revision IS NOT NULL THEN trd_especifica.comentario
    WHEN trd_global.fecha_revision IS NOT NULL THEN trd_global.comentario
  END AS comentario_etiqueta,

  /* ✅ fecha revisión vigente (siempre muestra la última revisión encontrada) */
  CASE
    WHEN trd_especifica.fecha_revision IS NOT NULL THEN trd_especifica.fecha_revision
    WHEN trd_global.fecha_revision IS NOT NULL THEN trd_global.fecha_revision
  END AS fecha_revision

FROM tutoria_plan_compartido pc
JOIN carga_lectiva cl
  ON cl.id_cargalectiva = pc.id_cargalectiva
 AND cl.id_semestre = ?
JOIN docente d
  ON d.id_doce = cl.id_doce
JOIN tutoria_usuario u
  ON u.id_car = cl.id_car

/* 1) Revisión ESPECÍFICA (cuando sí hay id_cargalectiva/id_docente) */
LEFT JOIN (
  SELECT r.*
  FROM tutoria_revision_director r
  INNER JOIN (
    SELECT id_plan_tutoria, id_cargalectiva, id_docente, id_semestre, id_car, MAX(fecha_revision) AS max_fecha
    FROM tutoria_revision_director
    WHERE id_cargalectiva IS NOT NULL OR id_docente IS NOT NULL
    GROUP BY id_plan_tutoria, id_cargalectiva, id_docente, id_semestre, id_car
  ) mx
    ON mx.id_plan_tutoria = r.id_plan_tutoria
   AND mx.id_cargalectiva = r.id_cargalectiva
   AND mx.id_docente      = r.id_docente
   AND mx.id_semestre     = r.id_semestre
   AND mx.id_car          = r.id_car
   AND mx.max_fecha       = r.fecha_revision
) trd_especifica
  ON trd_especifica.id_plan_tutoria = pc.id_plan_tutoria
 AND trd_especifica.id_cargalectiva = cl.id_cargalectiva
 AND trd_especifica.id_docente      = cl.id_doce
 AND trd_especifica.id_semestre     = cl.id_semestre
 AND trd_especifica.id_car          = cl.id_car

/* 2) Revisión GLOBAL (histórica: NULLs) */
LEFT JOIN (
  SELECT r.*
  FROM tutoria_revision_director r
  INNER JOIN (
    SELECT id_plan_tutoria, id_semestre, id_car, MAX(fecha_revision) AS max_fecha
    FROM tutoria_revision_director
    WHERE id_cargalectiva IS NULL AND id_docente IS NULL
    GROUP BY id_plan_tutoria, id_semestre, id_car
  ) mx
    ON mx.id_plan_tutoria = r.id_plan_tutoria
   AND mx.id_semestre     = r.id_semestre
   AND mx.id_car          = r.id_car
   AND mx.max_fecha       = r.fecha_revision
) trd_global
  ON trd_global.id_plan_tutoria = pc.id_plan_tutoria
 AND trd_global.id_semestre     = cl.id_semestre
 AND trd_global.id_car          = cl.id_car

WHERE u.id_usuario = ?
ORDER BY cl.ciclo ASC, cl.id_cargalectiva ASC
";


$stmt = $cn->prepare($sql) or die('Error prepare: '.$cn->error);
$stmt->bind_param('ii', $semestre_sel, $id_director);
$stmt->execute();
$res = $stmt->get_result();
?>


<style>
  body{font-family:Arial,sans-serif;background:#ecf0f1;margin:0}
  .container{max-width:98%;margin:40px auto;padding:20px;background:#fff;border-radius:12px;box-shadow:0 0 10px rgba(0,0,0,.1)}
  h3 {
    text-align: center;
    background-color: #154360;
    color: white;
    padding: 15px;
    border-radius: 5px;
    font-size: 20px;
    margin-bottom: 20px;
  }
  table{width:100%;border-collapse:collapse;font-size:14px}
  th,td{padding:10px;border:1px solid #ccc;text-align:center;vertical-align:top}
  th{background:#154360;color:#fff}
  .btn{padding:6px 10px;border:none;border-radius:4px;cursor:pointer;font-size:13px}
  .btn-primary{background:#2980b9;color:#fff}
  .btn-success{background:#27ae60;color:#fff}
  .btn-danger{background:#c0392b;color:#fff}
  .btn-warning{background:#f39c12;color:#fff}
  .text-muted{color:#7f8c8d}
  @media (max-width:768px){
    table,thead,tbody,th,td,tr{display:block}
    tr{margin-bottom:15px}
    th{text-align:left;background:#2c3e50;color:#fff;padding:8px}
    td{border:none;border-bottom:1px solid #ccc;padding-left:50%;position:relative}
    td::before{position:absolute;top:10px;left:10px;width:45%;white-space:nowrap;font-weight:bold;color:#2c3e50}
    td:nth-child(1)::before{content:"Ciclo"}
    td:nth-child(2)::before{content:"Docente"}
    td:nth-child(3)::before{content:"Fecha de Envío"}
    td:nth-child(4)::before{content:"Plan"}
    td:nth-child(5)::before{content:"Estado"}
    td:nth-child(6)::before{content:"Fecha de Conformidad"}
    td:nth-child(7)::before{content:"Acciones"}
    td:nth-child(8)::before{content:"Comentario"}
  }
  .btn-secondary{background:#7f8c8d;color:#fff}
</style>

<div class="container">

  <!-- FILA DE CONTROLES (semestre + botón reporte) -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">

    <div style="display:flex; gap:10px; align-items:center;">
      <label for="semestre" style="font-weight:bold;">Semestre:</label>
      <select id="semestre" name="semestre" class="form-control" style="max-width:180px;"
              onchange="cambiarSemestre()">
        <option value="32" <?= ($semestre_sel == 32 ? 'selected' : '') ?>>2025-I</option>
        <option value="33" <?= ($semestre_sel == 33 ? 'selected' : '') ?>>2025-II</option>
      </select>
    </div>

    <a style="white-space:nowrap;"
      href="direccion_escuela/reporte_envios_planes.php"
      onclick="return abrirVentanaPopup2(this.href);"
      class="btn btn-success btn-sm">
      📋 VER REPORTE
    </a>
  </div>

  <h3>PLANES DE TUTORÍA - TUTORES DE AULA</h3>

  <table>
    <thead>
      <tr>
        <th>Ciclo</th>
        <th>Tutor</th>
        <th>Fecha de Envío</th>
        <th>Plan</th>
        <th>Estado</th>
        <th>Fecha de Conformidad</th>
        <th>Acciones</th>
        <th>Comentario</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($res->num_rows): ?>
      <?php while($row = $res->fetch_assoc()): ?>
        <?php
          $docente_default = trim($row['abreviatura_doce'].' '.$row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']);

          $overrides = [
            5043 => 'DR. FRANCO MEDINA JORGE LAZARO',
          ];

          $id_carg = (int)$row['id_cargalectiva'];
          $docente = $overrides[$id_carg] ?? $docente_default;

          $fechaEnv = !empty($row['fecha_envio'])
                      ? date('d/m/Y H:i', strtotime($row['fecha_envio']))
                      : '<span class="text-muted">--</span>';

          $fechaRev = !empty($row['fecha_revision'])
                      ? date('d/m/Y H:i', strtotime($row['fecha_revision']))
                      : '--';

          $enviado = (
            (int)$row['estado_envio'] === 2 &&
            !empty($row['fecha_envio']) &&
            (int)$row['id_plan_tutoria'] > 0
          );

          $estadoUI = $row['estado_etiqueta'] ?? null; // CONFORME | INCONFORME | SUBSANADO | null
          $clsEstado = 'btn-secondary';
          if ($estadoUI === 'CONFORME')       $clsEstado = 'btn-success';
          elseif ($estadoUI === 'INCONFORME') $clsEstado = 'btn-danger';
          elseif ($estadoUI === 'SUBSANADO')  $clsEstado = 'btn-warning';
          elseif ($estadoUI === 'REENVIADO')  $clsEstado = 'btn-warning';

          $urlPlan = "tutor_aula/vista_prev_plan_tutoria.php"
                   . "?id_plan=" . (int)$row['id_plan_tutoria']
                   . "&id_cargalectiva=" . (int)$row['id_cargalectiva']
                   . "&id_semestre=" . (int)$semestre_sel;
        ?>
        <tr>
          <td><?= htmlspecialchars($row['ciclo'] ?? '—') ?></td>

          <td style="text-align:left"><?= htmlspecialchars($docente) ?></td>

          <td><?= $fechaEnv ?></td>

          <td>
            <?php if ($enviado): ?>
              <a href="#"
                 onclick="return abrirVentanaPopup('<?= $urlPlan ?>');"
                 class="btn btn-primary">
                 Ver Plan
              </a>
            <?php else: ?>
              <span class="text-muted">
                <?= ((int)$row['estado_envio']===1 ? 'En edición' : 'No enviado') ?>
              </span>
            <?php endif; ?>
          </td>

          <td>
            <?php if ($estadoUI): ?>
              <span class="btn <?= $clsEstado ?>"><?= htmlspecialchars($estadoUI) ?></span>
            <?php else: ?>
              <span class="text-muted">Pendiente</span>
            <?php endif; ?>
          </td>

          <td><?= $fechaRev ?></td>

          <td style="text-align: center;">
            <?php if ((int)$row['estado_envio']===2 && empty($row['estado_revision'])): ?>
              <form class="js-revision-form" method="POST" action="direccion_escuela/direccion_guardar_revision.php">
                <input type="hidden" name="id_plan_tutoria" value="<?= (int)$row['id_plan_tutoria'] ?>">
                <input type="hidden" name="id_cargalectiva"  value="<?= (int)$row['id_cargalectiva'] ?>">
                <input type="hidden" name="id_docente"       value="<?= (int)$row['id_docente'] ?>">
                <input type="hidden" name="id_semestre"      value="<?= (int)$semestre_sel ?>">
                <input type="hidden" name="id_director"      value="<?= (int)$id_director ?>">
                <input type="hidden" name="accion"           value="">

                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                  <button type="button" class="btn btn-success js-btn-conforme">Conforme</button>
                  <button type="button" class="btn btn-danger js-btn-observar">Observar</button>
                </div>

                <div style="margin-top:6px">
                  <textarea name="comentario" rows="2"
                            placeholder="Comentario (obligatorio si observa)"
                            style="width:260px;resize:vertical"></textarea>
                </div>
              </form>
            <?php else: ?>
              --
            <?php endif; ?>
          </td>

          <td>
            <?php if (!empty($row['comentario_etiqueta'])): ?>
              <?= htmlspecialchars($row['comentario_etiqueta']) ?>
            <?php elseif ($row['estado_envio']==2 && empty($row['estado_revision'])): ?>
              <span class="text-muted">Sin comentario</span>
            <?php else: ?>
              --
            <?php endif; ?>
          </td>

        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="8" style="text-align:center; font-weight:bold; color:#c0392b;">
          No hay planes registrados.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ================= POPUPS ================= */
function abrirVentanaPopup2(url){
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;
  window.open(url,'VistaPrevTutoria',`width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes`);
  return false;
}
function abrirVentanaPopup(url){
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;
  window.open(url,'VistaPrevTutoria',`width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes`);
  return false;
}

function cambiarSemestre() {
  const semestre = document.getElementById('semestre').value;
  window.location.href =
    "/vista/index.php?pagina=direccion_escuela/direccion_planes_tutor_aula.php&semestre=" + encodeURIComponent(semestre);
}

/* ================= HELPERS ================= */

// arma formdata y fuerza modo ajax
function formToFD(form){
  const fd = new FormData(form);
  fd.set('ajax','1');
  return fd;
}

// fetch robusto: si el PHP devuelve HTML/warnings, lo mostramos (NO se queda “mudo”)
async function postExpectJSON(url, fd){
  const resp = await fetch(url, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  });

  const text = await resp.text(); // lee todo como texto
  let json;

  try {
    json = JSON.parse(text);
  } catch (e) {
    // si vino HTML o warnings, esto te muestra el inicio para ubicar el problema
    throw new Error("El servidor no devolvió JSON.\nRespuesta:\n" + text.substring(0, 300));
  }

  // si el servidor respondió HTTP != 200, igual mostramos msg si existe
  if(!resp.ok){
    throw new Error(json.msg || ("HTTP " + resp.status));
  }

  return json;
}

function actualizarFila(form, payload){
  const tr = form.closest('tr');

  // Estado (col 5)
  const tdEstado = tr.children[4];
  tdEstado.innerHTML =
    `<span class="btn ${payload.estado==='CONFORME' ? 'btn-success' : 'btn-danger'}">`
    + (payload.etiqueta || payload.estado)
    + `</span>`;

  // Fecha conformidad (col 6)
  const tdFecha = tr.children[5];
  tdFecha.textContent = payload.fecha_revision || '--';

  // Acciones (col 7)
  const tdAcciones = tr.children[6];
  tdAcciones.textContent = '--';

  // Comentario (col 8)
  const tdComentario = tr.children[7];
  tdComentario.textContent =
    (payload.comentario || '').trim()
    || (payload.estado==='INCONFORME' ? 'Observado sin comentario' : '--');
}

/* ================= EVENTOS ================= */
document.addEventListener('click', async (ev)=>{
  const btnConforme = ev.target.closest('.js-btn-conforme');
  if(btnConforme){
    ev.preventDefault();
    const form = btnConforme.closest('form');

    const ok = await Swal.fire({
      title: '¿Confirmar conformidad?',
      text: 'Se registrará la conformidad del plan.',
      icon: 'question',
      showCancelButton: true,
      reverseButtons: true,
      confirmButtonText: 'Sí, confirmar',
      cancelButtonText: 'Cancelar'
    }).then(r=>r.isConfirmed);
    if(!ok) return;

    const fd = formToFD(form);
    fd.set('accion','CONFORME');

    try{
      const json = await postExpectJSON(form.action, fd);

      if(json.ok){
        actualizarFila(form, json);
        await Swal.fire({ icon:'success', title:'Registrado', text:'Se guardó la revisión.' });
      }else{
        throw new Error(json.msg || 'No se pudo guardar la revisión');
      }
    }catch(err){
      Swal.fire({ icon:'error', title:'Error', text: err.message });
    }
    return;
  }

  const btnObs = ev.target.closest('.js-btn-observar');
  if(btnObs){
    ev.preventDefault();
    const form = btnObs.closest('form');
    const txt  = form.querySelector('textarea[name="comentario"]');

    if(!txt.value.trim()){
      await Swal.fire({
        icon:'warning',
        title:'Comentario requerido',
        text:'Antes de observar el plan, ingrese un comentario fundamentando la observación.'
      });
      txt.focus();
      return;
    }

    const ok = await Swal.fire({
      title: '¿Observar plan?',
      text: 'El plan quedará observado y el docente podrá corregirlo.',
      icon: 'warning',
      showCancelButton: true,
      reverseButtons: true,
      confirmButtonText: 'Sí, observar',
      cancelButtonText: 'Cancelar'
    }).then(r=>r.isConfirmed);
    if(!ok) return;

    const fd = formToFD(form);
    fd.set('accion','INCONFORME');

    try{
      const json = await postExpectJSON(form.action, fd);

      if(json.ok){
        actualizarFila(form, json);
        await Swal.fire({ icon:'success', title:'Registrado', text:'Se guardó la observación.' });
      }else{
        throw new Error(json.msg || 'No se pudo guardar la observación');
      }
    }catch(err){
      Swal.fire({ icon:'error', title:'Error', text: err.message });
    }
    return;
  }
});
</script>
