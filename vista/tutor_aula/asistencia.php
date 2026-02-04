<?php
session_start();
if (!isset($_SESSION['S_IDUSUARIO']) || !isset($_SESSION['S_USER']) || !isset($_SESSION['S_ROL'])) {
  http_response_code(401);
  exit('Sesión no válida.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_estu'])) {
  http_response_code(400);
  exit('Falta id_estu.');
}

$id_estu     = (int)$_POST['id_estu'];
$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);

require_once('../../modelo/modelo_conexion.php');
$db = new conexion();
$db->conectar();
$cn = $db->conexion;

/* ---------- Alumno ---------- */
$alumno = ['apepa'=>'','apema'=>'','nombres'=>'','nom_plan'=>''];
$sqlAlumno = "
  SELECT e.apepa_estu, e.apema_estu, e.nom_estu, p.nom_plan
  FROM estudiante e
  JOIN detestudiante d ON d.id_estu=e.id_estu AND d.activo='SI'
  JOIN plan_estudio p   ON p.id_plan=d.id_plan
  WHERE e.id_estu=? LIMIT 1";
if ($st = $cn->prepare($sqlAlumno)) {
  $st->bind_param('i', $id_estu);
  $st->execute();
  $rs = $st->get_result();
  if ($rs && $rs->num_rows) {
    $r = $rs->fetch_assoc();
    $alumno['apepa']    = $r['apepa_estu'] ?? '';
    $alumno['apema']    = $r['apema_estu'] ?? '';
    $alumno['nombres']  = $r['nom_estu']   ?? '';
    $alumno['nom_plan'] = $r['nom_plan']   ?? '';
  }
  $st->close();
}

/* ---------- Cursos del semestre (desde carga_lectiva) ---------- */
$cursos = [];
$sqlCursos = "
  SELECT ae.id_aestu, a.cod_asi, a.nom_asi, cl.ciclo, cl.turno, cl.seccion
  FROM asignacion_estudiante ae
  JOIN carga_lectiva cl ON cl.id_cargalectiva = ae.id_cargalectiva
  JOIN asignatura    a  ON a.id_asi          = ae.id_asi
  WHERE ae.id_estu=? AND ae.id_semestre=? AND ae.anulado=0 AND ae.borrado=0 AND ae.convalidado='NO'
  ORDER BY cl.ciclo, a.nom_asi";
if ($st = $cn->prepare($sqlCursos)) {
  $st->bind_param('ii', $id_estu, $id_semestre);
  $st->execute();
  $rs = $st->get_result();
  while ($r = $rs->fetch_assoc()) $cursos[] = $r;
  $st->close();
}

/* ---------- Asistencia por curso ---------- */
$sqlAsis = "SELECT semana, condicion, porcentaje
            FROM asistencias
            WHERE id_aestu=? AND id_semestre=? AND anulado=0";
$stAsis = $cn->prepare($sqlAsis);

function turnoInicial($t){
  $t = strtoupper(trim($t));
  if ($t==='MAÑANA' || $t==='MANANA') return 'M';
  if ($t==='TARDE')  return 'T';
  if ($t==='NOCHE')  return 'N';
  return mb_substr($t,0,1,'UTF-8');
}
?>
<style>
  .modal-asist-wrap{position:fixed;inset:0;display:grid;place-items:center;z-index:2000}
  .modal-asist{width:min(1100px,95vw);max-height:85vh;overflow:hidden;background:#fff;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .modal-asist__header{background:#2d6b37;color:#fff;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;font-weight:700}
  .modal-asist__close{background:transparent;border:0;color:#fff;font-size:22px;line-height:1;cursor:pointer}
  .modal-asist__body{padding:10px;max-height:75vh;overflow:auto}

  .table-style{width:100%;border-collapse:collapse;font-size:12px}
  .table-style th,.table-style td{border:1px solid #000;padding:4px 6px}
  .table-style thead th{position:sticky;top:0;z-index:1;background:#f2f2f2;color:#000;}
  .table-header{background:#f2f2f2;font-weight:700; }
  .txt-left{text-align:left}.txt-center{text-align:center}
</style>

<div class="modal-asist-wrap" onclick="if(event.target===this) this.remove();">
  <div class="modal-asist" role="dialog" aria-modal="true" aria-label="Asistencia" onclick="event.stopPropagation();">
    <div class="modal-asist__header">
      <div>
        ASISTENCIA — <?= htmlspecialchars($alumno['apepa'].' '.$alumno['apema'].' '.$alumno['nombres'],ENT_QUOTES,'UTF-8') ?>
      </div>
      <!-- Cierra sin depender de scripts externos -->
      <button type="button" class="modal-asist__close" title="Cerrar"
              onclick="this.closest('.modal-asist-wrap').remove()">×</button>
    </div>

    <div class="modal-asist__body">
      <table class="table-style">
        <thead >
          <tr class="table-header">
            <th colspan="22" class="txt-center" >ASISTENCIA</th>
          </tr>
          
          <tr class="table-header">
            <th class="txt-center">CIC</th>
            <th class="txt-center">TUR</th>
            <th class="txt-center">SEC</th>
            <th class="txt-center">COD</th>
            <th class="txt-left">ASIGNATURA</th>
            <?php for ($s=1;$s<=16;$s++): ?>
              <th class="txt-center"><?= $s ?>s</th>
            <?php endfor; ?>
            <th class="txt-center">% FAL</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $prefijoPlan = $alumno['nom_plan'] !== '' ? substr($alumno['nom_plan'],2,2) : '';
          foreach ($cursos as $c) {
            $id_aestu = (int)$c['id_aestu'];

            $sem = array_fill(1,16,'&nbsp;');
            $porcFalta = 0.0;

            if ($stAsis) {
              $stAsis->bind_param('ii', $id_aestu, $id_semestre);
              $stAsis->execute();
              $rsA = $stAsis->get_result();
              while ($a = $rsA->fetch_assoc()) {
                $w = (int)$a['semana'];
                $cond = strtoupper(trim($a['condicion'])); // A/F/J
                if ($w>=1 && $w<=16) $sem[$w] = ($cond==='') ? '&nbsp;' : htmlspecialchars($cond,ENT_QUOTES,'UTF-8');
                if ($cond==='F') $porcFalta += (float)$a['porcentaje'];
              }
            }

            echo '<tr>';
            echo '<td class="txt-center">'.htmlspecialchars($c['ciclo'],ENT_QUOTES,'UTF-8').'</td>';
            echo '<td class="txt-center">'.htmlspecialchars(turnoInicial($c['turno']),ENT_QUOTES,'UTF-8').'</td>';
            echo '<td class="txt-center">'.htmlspecialchars($c['seccion'],ENT_QUOTES,'UTF-8').'</td>';
            $codigo = trim(($prefijoPlan!=='' ? $prefijoPlan.' ' : '').($c['cod_asi'] ?? ''));
            echo '<td class="txt-left">'.htmlspecialchars($codigo,ENT_QUOTES,'UTF-8').'</td>';
            echo '<td class="txt-left">'.htmlspecialchars($c['nom_asi'],ENT_QUOTES,'UTF-8').'</td>';
            for ($i=1;$i<=16;$i++) echo '<td class="txt-center">'.$sem[$i].'</td>';
            $porcTxt = $porcFalta>0 ? rtrim(rtrim(number_format($porcFalta,2,'.',''),'0'),'.').' %' : '&nbsp;';
            echo '<td class="txt-center">'.$porcTxt.'</td>';
            echo '</tr>';
          }
        ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
if ($stAsis) { $stAsis->close(); }
$db->cerrar();
