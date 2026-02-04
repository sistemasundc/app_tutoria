<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'DIRECCION DE ESCUELA') {
  die('Acceso no autorizado');
}

$semestre     = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$id_director  = (int)$_SESSION['S_IDUSUARIO'];

$conexion = new conexion();
$cn = $conexion->conectar();

/*
  ▶️ LISTAR POR CARGA LECTIVA (individual)
  - Una fila por cl.id_cargalectiva
  - Se muestra ciclo, docente, estado/fecha de envío del plan de ESA carga
  - LEFT JOIN a la revisión del director por el combo completo
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

 /* estado vigente solo si la revisión es >= al último envío */
  CASE
    WHEN trd.fecha_revision IS NOT NULL
     AND pc.fecha_envio IS NOT NULL
     AND trd.fecha_revision >= pc.fecha_envio
    THEN trd.estado_revision
  END AS estado_revision,  -- se usa para la lógica de mostrar botones

  /* etiqueta que se muestra en la columna ESTADO: CONFORME | INCONFORME | SUBSANADO */
  CASE
    WHEN trd.fecha_revision IS NOT NULL
     AND pc.fecha_envio IS NOT NULL
     AND trd.fecha_revision >= pc.fecha_envio
      THEN trd.estado_revision
    WHEN trd.estado_revision = 'INCONFORME'
     AND pc.fecha_envio IS NOT NULL
     AND trd.fecha_revision IS NOT NULL
     AND pc.fecha_envio > trd.fecha_revision
      THEN 'SUBSANADO'
  END AS estado_etiqueta,

  /* comentario que se muestra: el vigente o el último INCONFORME (para contexto al subsanar) */
  CASE
    WHEN trd.fecha_revision IS NOT NULL
     AND pc.fecha_envio IS NOT NULL
     AND trd.fecha_revision >= pc.fecha_envio
      THEN trd.comentario
    WHEN trd.estado_revision = 'INCONFORME'
     AND pc.fecha_envio IS NOT NULL
     AND trd.fecha_revision IS NOT NULL
     AND pc.fecha_envio > trd.fecha_revision
      THEN trd.comentario
  END AS comentario_etiqueta,

  /* fecha de revisión solo si es posterior al último envío */
  CASE
    WHEN trd.fecha_revision IS NOT NULL
     AND pc.fecha_envio IS NOT NULL
     AND trd.fecha_revision >= pc.fecha_envio
      THEN trd.fecha_revision
  END AS fecha_revision

FROM tutoria_plan_compartido pc
JOIN carga_lectiva cl
  ON cl.id_cargalectiva = pc.id_cargalectiva
  AND cl.id_semestre = ?
JOIN docente d
  ON d.id_doce = cl.id_doce
JOIN tutoria_usuario u
  ON u.id_car = cl.id_car
LEFT JOIN tutoria_revision_director trd
  ON trd.id_plan_tutoria = pc.id_plan_tutoria
 AND trd.id_cargalectiva = cl.id_cargalectiva
 AND trd.id_docente      = cl.id_doce
 AND trd.id_semestre     = cl.id_semestre
 AND trd.id_director     = ?

WHERE u.id_usuario = ?
ORDER BY cl.ciclo ASC, cl.id_cargalectiva ASC
";

$stmt = $cn->prepare($sql) or die('Error prepare: '.$cn->error);
$stmt->bind_param('iii', $semestre, $id_director, $id_director);
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
  th,td{padding:10px;border:1px solid #ccc;text-align:center}
  th{background:#154360;color:#fff}
  .btn{padding:6px 10px;border:none;border-radius:4px;cursor:pointer;font-size:13px}
  .btn-primary{background:#2980b9;color:#fff}
  .btn-success{background:#27ae60;color:#fff}
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
  }
</style>

<div class="container">
  <h3>PLANES DE TUTORÍA - TUTORES DE AULA</h3>
    <a style="float:right;margin-bottom:15px"
      href="direccion_escuela/reporte_envios_planes.php"
      onclick="return abrirVentanaPopup2(this.href);"
      class="btn btn-success btn-sm">📋 VER REPORTE
    </a>
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
        <!--BOTOM INCONFORME-->
        <th>Comentario</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($res->num_rows): ?>
      <?php while($row = $res->fetch_assoc()): ?>
        <?php
          $docente = trim($row['abreviatura_doce'].' '.$row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']);
          $fechaEnv = !empty($row['fecha_envio']) ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '<span class="text-muted">--</span>';
          $fechaRev = !empty($row['fecha_revision']) ? date('Y-m-d H:i:s', strtotime($row['fecha_revision'])) : '--';
        ?>
        <tr>
          <td><?= htmlspecialchars($row['ciclo'] ?? '—') ?></td>
          <td style="text-align:left"><?= htmlspecialchars($docente) ?></td>
          <td><?= $fechaEnv ?></td>
          <td>
            <?php
              $enviado = ((int)$row['estado_envio'] === 2)
                        && !empty($row['fecha_envio'])
                        && (int)$row['id_plan_tutoria'] > 0;
            ?>
            <?php if ($enviado): ?>
              <a href="#"
                onclick="abrirVentanaPopup('tutor_aula/vista_prev_plan_tutoria.php?id_plan=<?= (int)$row['id_plan_tutoria'] ?>&id_cargalectiva=<?= (int)$row['id_cargalectiva'] ?>'); return false;"
                class="btn btn-primary">Ver Plan</a>
            <?php else: ?>
              <span class="text-muted">
                <?= ((int)$row['estado_envio']===1 ? 'En edición' : 'No enviado') ?>
              </span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $estadoUI = $row['estado_etiqueta'] ?? null; // CONFORME | INCONFORME | SUBSANADO | null
              if ($estadoUI) {
                // Usa A, B o C para obtener $cls:
                $map = ['CONFORME'=>'btn-success','INCONFORME'=>'btn-danger','SUBSANADO'=>'btn-warning'];
                $cls = $map[$estadoUI] ?? 'btn-secondary';
            ?>
                <span class="btn <?= $cls ?>"><?= htmlspecialchars($estadoUI) ?></span>
            <?php } else { ?>
                <span class="text-muted">Pendiente</span>
            <?php } ?>
          </td>
          <td><?= $fechaRev ?></td>
          <td style="text-align: center;">
            <?php if ((int)$row['estado_envio']===2 && empty($row['estado_revision'])): ?>
              <form class="js-revision-form" method="POST" action="direccion_escuela/direccion_guardar_revision.php">
                <input type="hidden" name="id_plan_tutoria" value="<?= (int)$row['id_plan_tutoria'] ?>">
                <input type="hidden" name="id_cargalectiva"  value="<?= (int)$row['id_cargalectiva'] ?>">
                <input type="hidden" name="id_docente"       value="<?= (int)$row['id_docente'] ?>">
                <input type="hidden" name="id_semestre"      value="<?= (int)$semestre ?>">
                <input type="hidden" name="id_director"      value="<?= (int)$id_director ?>">
                <input type="hidden" name="accion"           value=""> <!-- la completa JS -->

                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-success js-btn-conforme">Conforme</button>
                    <button type="button" class="btn btn-danger js-btn-observar" >Observar</button>
              
                </div>

                <div style="margin-top:6px">
                  <textarea name="comentario" rows="2" placeholder="Comentario (obligatorio si observa)" style="width:260px;resize:vertical"></textarea>
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
      <tr><td colspan="7">No hay planes registrados.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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

/* Util: convierte form a FormData */
function formToFD(form){
  const fd = new FormData(form);
  fd.set('ajax','1'); // para que el backend responda JSON
  return fd;
}

/* Util: actualiza la fila (estado, fecha, acciones, comentario) */
function actualizarFila(form, payload){
  const tr = form.closest('tr');
  // Estado (pill)
  const tdEstado = tr.children[4];
  tdEstado.innerHTML = `<span class="btn ${payload.estado==='CONFORME'?'btn-success':'btn-danger'}">${payload.etiqueta || payload.estado}</span>`;
  // Fecha de conformidad
  const tdFecha = tr.children[5];
  tdFecha.textContent = payload.fecha_revision || '--';
  // Acciones -> deshabilitar
  const tdAcciones = tr.children[6];
  tdAcciones.textContent = '--';
  // Comentario visible
  const tdComentario = tr.children[7];
  tdComentario.textContent = (payload.comentario || '').trim() || (payload.estado==='INCONFORME' ? 'Observado sin comentario' : '--');
}

/* Delegación: botón CONFORME */
document.addEventListener('click', async (ev)=>{
  const btn = ev.target.closest('.js-btn-conforme');
  if(!btn) return;
  ev.preventDefault();
  const form = btn.closest('form');

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
    const r = await fetch(form.action, { method:'POST', body: fd, credentials:'same-origin' });
    const json = await r.json();
    if(json.ok){
      actualizarFila(form, json);
      await Swal.fire({icon:'success', title:'Registrado', text:'Se guardó la revisión.'});
    }else{
      throw new Error(json.msg || 'No se pudo guardar la revisión');
    }
  }catch(err){
    Swal.fire({icon:'error', title:'Error', text: err.message});
  }
});

/* Delegación: botón OBSERVAR (requiere comentario) */
document.addEventListener('click', async (ev)=>{
  const btn = ev.target.closest('.js-btn-observar');
  if(!btn) return;
  ev.preventDefault();
  const form = btn.closest('form');
  const txt  = form.querySelector('textarea[name="comentario"]');

  if(!txt.value.trim()){
    await Swal.fire({icon:'warning', title:'Comentario requerido', text:'Antes de observar el plan, ingrese un comentario fundamentando la observación.'});
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
    const r = await fetch(form.action, { method:'POST', body: fd, credentials:'same-origin' });
    const json = await r.json();
    if(json.ok){
      actualizarFila(form, json);
      await Swal.fire({icon:'success', title:'Registrado', text:'Se guardó la observación.'});
    }else{
      throw new Error(json.msg || 'No se pudo guardar la observación');
    }
  }catch(err){
    Swal.fire({icon:'error', title:'Error', text: err.message});
  }
});

/* ❌ Ya no dependemos de ?status=ok|error, se elimina el bloque auto-alert */
</script>


