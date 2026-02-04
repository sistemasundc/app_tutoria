<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

/* ==== ROLES ADMIN (ajusta si tu rol se llama distinto) ==== */
$ROLES_OK = [
  'COORDINADOR GENERAL DE TUTORIA',
  'SUPERVISION', 'VICEPRESIDENCIA ACADEMICA'
];
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], $ROLES_OK)) {
  die('Acceso no autorizado');
}

$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$cn = (new conexion())->conectar();

/* ==== Meses permitidos 2025-2 (ajusta) ==== */
$MESES_PERMITIDOS = ['agosto','septiembre','octubre','noviembre','diciembre'];
$mes_seleccionado = strtolower($_GET['mes'] ?? '');
if (!in_array($mes_seleccionado, $MESES_PERMITIDOS, true)) $mes_seleccionado = 'diciembre'; /* AQUI ACTUALIZAR MES ===============================================================*/
$mesMap = ['enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];
$mes_num = $mesMap[$mes_seleccionado] ?? 9;

/* ==== Llenar combo de carreras + opción TODAS ==== */
/* ==== Llenar combo de carreras (sin 'Todas') ==== */
$listaCarreras = [];
$resCar = $cn->query("SELECT id_car, nom_car FROM carrera ORDER BY nom_car");
while ($row = $resCar->fetch_assoc()) { $listaCarreras[] = $row; }

/* ==== Escuela seleccionada ==== */
/* Prioriza ?id_car=..., si no viene usa la escuela de sesión; si aún no hay, toma la primera de la lista */
$id_car = isset($_GET['id_car']) && ctype_digit($_GET['id_car'])
  ? (int)$_GET['id_car']
  : (int)($_SESSION['S_SCHOOL'] ?? 0);

if (!$id_car && !empty($listaCarreras)) {
  $id_car = (int)$listaCarreras[0]['id_car'];
}

/* Etiqueta de escuela */
$escuela_label = (function($cn, $id_car) {
  $st = $cn->prepare("SELECT nom_car FROM carrera WHERE id_car = ?");
  $st->bind_param('i', $id_car);
  $st->execute();
  return $st->get_result()->fetch_assoc()['nom_car'] ?? 'Escuela';
})($cn, $id_car);

/* ================= CUMPLIMIENTO AULA =================
   - Total: tutores asignados como TA en el semestre
   - Enviados: informes mensuales (estado_envio=2) del mes seleccionado
*/
// TOTAL
$st = $cn->prepare("
  SELECT COUNT(DISTINCT tda.id_doce) AS total
  FROM tutoria_docente_asignado tda
  WHERE tda.id_semestre = ? AND tda.id_car = ?
");
$st->bind_param('ii', $id_semestre, $id_car);
$st->execute();
$total_aula = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);

// ENVIADOS
$st = $cn->prepare("
  SELECT COUNT(*) AS enviados FROM (
    SELECT DISTINCT tda.id_doce
    FROM tutoria_docente_asignado tda
    INNER JOIN tutoria_informe_mensual im
            ON im.id_docente = tda.id_doce
           AND im.id_semestre = tda.id_semestre
           AND im.id_car      = tda.id_car
    WHERE tda.id_semestre = ?
      AND tda.id_car      = ?
      AND im.estado_envio = 2
      AND LOWER(im.mes_informe) = ?
  ) x
");
$st->bind_param('iis', $id_semestre, $id_car, $mes_seleccionado);
$st->execute();
$enviados_aula = (int)($st->get_result()->fetch_assoc()['enviados'] ?? 0);

$porc_aula = ($total_aula > 0) ? round($enviados_aula / $total_aula * 100, 2) : 0.0;

/* (Opcional) ================= CUMPLIMIENTO CURSO ================= */
// TOTAL
$st = $cn->prepare("
  SELECT COUNT(DISTINCT id_doce) AS total
  FROM carga_lectiva
  WHERE id_semestre = ? AND id_car = ? AND tipo = 'M'
    AND NOT (id_doce = 17 AND id_car = 1)
");
$st->bind_param('ii', $id_semestre, $id_car);
$st->execute();
$total_curso = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);

// ENVIADOS
$st = $cn->prepare("
  SELECT COUNT(DISTINCT cl.id_doce) AS enviados
  FROM carga_lectiva cl
  INNER JOIN tutoria_informe_mensual_curso imc
          ON cl.id_cargalectiva = imc.id_cargalectiva
  WHERE cl.id_semestre = ? AND cl.id_car = ?
    AND imc.estado_envio = 2
    AND LOWER(imc.mes_informe) = ?
");
$st->bind_param('iis', $id_semestre, $id_car, $mes_seleccionado);
$st->execute();
$enviados_curso = (int)($st->get_result()->fetch_assoc()['enviados'] ?? 0);

$porc_curso = ($total_curso > 0) ? round($enviados_curso / $total_curso * 100, 2) : 0.0;


/* ============ LISTA DE TUTORES DE AULA + SESIONES (para la tabla) =========== */
$sqlTutores = "
    SELECT
      d.id_doce,
      d.abreviatura_doce,
      d.apepa_doce,
      d.apema_doce,
      d.nom_doce,
      d.email_doce,
      cl.ciclo,
      /* Una sola columna con todas las combinaciones turno-sección del MISMO ciclo */
      GROUP_CONCAT(
        DISTINCT CONCAT(cl.turno, ' - ', cl.seccion)
        ORDER BY cl.turno, cl.seccion
        SEPARATOR ' | '
      ) AS turno_seccion
    FROM tutoria_docente_asignado tda
    JOIN docente d
      ON d.id_doce = tda.id_doce
    JOIN (
      SELECT DISTINCT id_docente, id_semestre, id_carga
      FROM tutoria_asignacion_tutoria
    ) tat
      ON tat.id_docente  = tda.id_doce
    AND tat.id_semestre = tda.id_semestre
    JOIN carga_lectiva cl
      ON cl.id_cargalectiva = tat.id_carga
    AND cl.id_semestre     = tda.id_semestre
    AND cl.id_car          = tda.id_car
    WHERE tda.id_semestre = ?
      AND tda.id_car      = ?
    GROUP BY d.id_doce, cl.ciclo
    /* Ordena los ciclos en romano y luego por nombre del docente */
    ORDER BY FIELD(cl.ciclo,'I','II','III','IV','V','VI','VII','VIII','IX','X'),
            d.apepa_doce, d.apema_doce, d.nom_doce;


";
$st = $cn->prepare($sqlTutores);
$st->bind_param('ii', $id_semestre, $id_car);
$st->execute();
$resTutores = $st->get_result();

$tutores = []; $ids = [];
while ($r = $resTutores->fetch_assoc()) {
  $ids[] = (int)$r['id_doce'];
  $tutores[] = $r;
}

/* Contar sesiones F78 del mes por tutor (rol=6) */
$sesionesPorDoc = [];
if (!empty($ids)) {
  $ph = implode(',', array_fill(0,count($ids),'?'));
  $types = str_repeat('i', 2 + count($ids)); // semestre, mes, ids...
  $params = [$id_semestre, $mes_num, ...$ids];

  $sqlSes = "
    SELECT id_doce, COUNT(*) AS sesiones
    FROM tutoria_sesiones_tutoria_f78
    WHERE id_rol=6 AND id_semestre=? AND MONTH(fecha)=? AND id_doce IN ($ph)
    GROUP BY id_doce";
  $st = $cn->prepare($sqlSes);
  $st->bind_param($types, ...$params);
  $st->execute(); $resS = $st->get_result();
  while ($s = $resS->fetch_assoc()) $sesionesPorDoc[(int)$s['id_doce']] = (int)$s['sesiones'];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte Admin – Actividad de Tutores</title>
<style>
  body{font-family:Arial, sans-serif;background:#f4f6f9;margin:0}
  h2{ text-align:center;background:#2c3e50;color:#fff;padding:15px;font-size:18px;font-weight:bold }
  .contenedor {display: grid;grid-template-columns: 1fr 550px; /* tabla ocupa todo | card fija de ~360px */gap: 30px;padding: 30px;align-items: start; }
  .grafico-box {position: relative;background: white;padding: 40px;border-radius: 12px;box-shadow: 0 2px 6px rgba(0,0,0,0.2); text-align: center;}
  .col-derecha{
      display:flex;
      flex-direction:column;
      gap:20px;
    }
    /* Opcional: versión compacta (si ya la usas, déjala) */
  .grafico-box.compacto{ padding:20px 22px; }
  .titulo{font-size:16px;font-weight:bold;margin-bottom:10px}
  .dona{--valor:0;width:150px;height:150px;margin:auto;border-radius:50%;
        background:conic-gradient(rgb(27,190,68) calc(var(--valor)*1%), rgb(204,37,31) 0); position:relative}
  .dona::before{content:'';position:absolute;top:25px;left:25px;width:100px;height:100px;background:#fff;border-radius:50%}
  .porcentajes{margin-top:12px;font-size:14px;line-height:1.6;text-align:center}
  .cuadro{display:inline-block;width:12px;height:12px;border-radius:2px;margin-right:5px;vertical-align:middle}
  .azul{background:rgb(27,190,68)} .naranja{background:rgb(204,37,31)}
  table{width:100%;border-collapse:collapse;font-size:13px;background:#fff}
  th,td{border:1px solid #2c3e50;padding:8px 10px}
  th{background:#2c3e50;color:#fff;text-align:center}
  td:nth-child(1),td:nth-child(4),td:nth-child(5){text-align:center}
  .pill{background:#27ae60;color:#fff;border:none;padding:4px 10px;border-radius:12px}
  .pill[disabled]{background:#bdbdbd;cursor:not-allowed}
  .btn-ver{position:absolute;top:10px;right:10px;background:#1a5276;color:#fff;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;font-size:12px}
  .ranking-title { font-size: 16px;    font-weight: bold;   margin-bottom: 15px;   text-align: center; }
  .ranking-item {  display: flex;    align-items: center;   margin-bottom: 8px;}
  .ranking-label {  width: 40%;  font-size: 14px;  font-weight: 500;}
  .ranking-bar-container {flex: 1; background: #eee;  border-radius: 5px;  overflow: hidden;  height: 20px;}
  .ranking-bar {  height: 20px;  background: #2e86de;  text-align: right;  padding-right: 5px;  color: #fff;  font-size: 12px;  line-height: 20px;}
  .ver-completo-btn { display: block; margin: 10px auto 0;  padding: 6px 12px;  background:rgb(230, 121, 20);  color: white;  border: none;  cursor: pointer;  border-radius: 5px;}
  @media(max-width:1100px){.contenedor{grid-template-columns:1fr}}
 /* Popover */
   #sesion-popover{
      position: fixed !important;      /* centrado en pantalla */
      left: 50% !important;
      top: 50% !important;
      transform: translate(-50%, -50%) !important;
      z-index: 9999;
      width: 420px;
      max-height: 70vh;
      overflow: auto;
      background: #fff;
      border: 2px solid #2c3e50;
      border-radius: 10px;
      box-shadow: 0 20px 38px rgba(0,0,0,.22);
      padding: 12px;
      display: none;
    }
  /* Botón de cierre (X) dentro del popover */
  
  #sesion-popover .close-btn{
    position: absolute;
    top: 6px;
    right: 8px;
    background: #fff;
    border: 0;
    font-size: 40px;
    font-weight: bold;
    color: #A9A9B8;
    cursor: pointer;
    line-height: 1;
  }
  #sesion-popover .close-btn:hover{ color:#c00; }

    /* Tarjeta de sesión dentro del popover */
    .s-card{ border:1px solid #e1e1e1; border-radius:8px; padding:10px; margin:8px 0; }
    .s-titulo{ font-weight:700; font-size:13px; margin-bottom:4px; }
    .s-tema{ font-size:13px; color:#333; }
    .s-meta{ font-size:12px; color:#666; margin-top:4px; }

    /* Estrellas */
    .s-stars{ font-size:16px; line-height:1; }
    .s-star{ color:#d7d7d7; }
    .s-star.fill{ color:#f5c518; }  /* dorado */
    .s-header{ font-weight:700; margin-bottom:8px; }
    .s-overall{ margin-top:8px; font-size:13px; }
    #evi-modal{  position: fixed; inset: 0; background: rgba(0,0,0,.55);  display: none; align-items: center; justify-content: center; z-index: 10000;  }
    #evi-box{    background:#fff; border-radius:12px; max-width:960px; width:92%;    padding:14px 14px 8px; position:relative; box-shadow:0 12px 28px rgba(0,0,0,.22);  }
    #evi-close{ position:absolute; top:8px; right:10px;  background:#fff;           /* fondo blanco */   color:#000;                /* letra negra */   border:1px solid #ddd;   border-radius:8px;   padding:4px 10px; box-shadow:0 2px 6px rgba(0,0,0,.15);  font-size:26px; line-height:1; cursor:pointer;  z-index:2;                 /* por si la imagen pasa por encima */
    }
    #evi-close:hover{   background:#fff;  color:#000;  opacity:.9; }
    /* Galería: 1–2 columnas y tarjetas iguales */
    #evi-gallery{  display: grid;    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));   gap: 16px;   max-height: 70vh;   overflow: auto;  }
    .evi-item{  background:#fff;   border-radius:10px;   box-shadow:0 2px 6px rgba(0,0,0,.12);   padding:10px;   text-align:center;  }
    /* Imagen con tamaño uniforme */
    .evi-img{  width:100%;   height:340px;          /* <-- alto uniforme */   object-fit:cover;      /* recorta para llenar el cuadro */   border-radius:8px;    cursor: zoom-in;   display:block; }
    /* Caption */
    .evi-caption{ font-size:12px; color:#555; margin-top:6px; }
    /* Overlay de zoom */
    .evi-caption{ font-size:12px; color:#555; margin-top:6px; text-align:center; }
    .btn-evi{ background: #27ae60; color:#fff; border:none; padding:4px 10px; border-radius:14px; font-size:12px; float:right;margin-top:-25px; }
    .btn-evi[disabled]{ background:#bdbdbd; cursor:not-allowed; }
    .btn-formato{background:#0d6efd;   color:#fff;   border:none;   padding:4px 10px;    border-radius:14px;   font-size:12px;   margin-right:8px; float:right; margin-top:-60px; }
    .btn-formato[disabled]{ background:#bdbdbd; cursor:not-allowed; }
</style>
</head>
<body>

<h2>REPORTE ADMIN – ACTIVIDAD DE TUTORES - <?= strtoupper($escuela_label) ?> – <?= strtoupper($mes_seleccionado) ?> – 2025-2</h2>

<form method="GET" action="index.php" style="text-align:center;margin:12px 0 0">
  <input type="hidden" name="pagina" value="reportes/admin_porcentaje_tutores.php">
  <label>Escuela: </label>
  <select name="id_car" onchange="this.form.submit()" style="padding:5px;margin-right:12px;">
    <?php foreach($listaCarreras as $c){ $sel = ($c['id_car']==$id_car)?'selected':''; ?>
      <option value="<?= $c['id_car'] ?>" <?= $sel ?>><?= strtoupper($c['nom_car']) ?></option>
    <?php } ?>
  </select>

  <label>Mes: </label>
  <select name="mes" onchange="this.form.submit()" style="padding:5px;">
    <?php foreach($MESES_PERMITIDOS as $m){ $sel = ($m===$mes_seleccionado)?'selected':''; ?>
      <option value="<?= $m ?>" <?= $sel ?>><?= ucfirst($m) ?></option>
    <?php } ?>
  </select>
</form>

<div class="contenedor">
  <div class="grafico-box">
    <div class="titulo">Actividades – Tutores de Aula </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <?php if ($id_car===0): ?><th>Escuela</th><?php endif; ?>
          <th>CICLO</th>
          <th>TURNO - SECCIÓN</th>
          <th>TUTOR</th>
          <th>N° SESIONES</th>
          <th>ACCIÓN</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($tutores)): ?>
        <tr><td colspan="<?= $id_car===0?6:5 ?>">Sin tutores registrados.</td></tr>
      <?php else: foreach($tutores as $t):
        $id=(int)$t['id_doce'];
        $nombre = strtoupper(trim(($t['abreviatura_doce']? $t['abreviatura_doce'].' ':'').$t['apepa_doce'].' '.$t['apema_doce'].', '.$t['nom_doce']));
        $ciclos = $t['ciclo'] ?? '';
        $turno_seccion = $t['turno_seccion'] ?? '';
        $ses = $sesionesPorDoc[$id] ?? 0;
      ?>
        <tr>
          <td><?= $id ?></td>
          <?php if ($id_car===0): ?><td><?= htmlspecialchars($t['escuela']) ?></td><?php endif; ?>
          <td style="text-align:center"><?= htmlspecialchars($ciclos) ?></td> 
          <td style="text-align:center"><?= htmlspecialchars($turno_seccion) ?> </td>
          <td style="text-align:left"><?= htmlspecialchars($nombre) ?></td>
          <td style="text-align:center"><b><?= $ses ?></b></td>
          <td style="text-align:center">
            <button type="button" class="pill btn-sesiones" data-doc="<?= $id ?>" <?= $ses>0?'':'disabled' ?>>VER</button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="col-derecha">
    <div class="grafico-box compacto">
      <button class="btn-ver" onclick="verDetalle(<?= $id_car ?>, '<?= $mes_seleccionado ?>')">VER</button>
        <div class="titulo">Cumplimiento entrega de informes - Tutor de Aula</div>
        <div class="dona" style="--valor: <?= $porc_aula ?>;"></div>
        <div class="porcentajes">
          <span><span class="cuadro azul"></span><strong> CUMPLIMIENTO: <?= $porc_aula ?>%</strong></span><br>
          <span><span class="cuadro naranja"></span><strong> INCUMPLIMIENTO: <?= (100 - $porc_aula) ?>%</strong></span>
        </div>
    </div>

    <!-- <div class="grafico-box compacto">
      <button class="btn-ver" onclick="verDetalle1(?= (int)$id_car ?>, '?= htmlspecialchars($mes_seleccionado) ?>')">VER</button>
      <div class="titulo">Cumplimiento entrega de informes - Tutor de Curso</div>
      <div class="dona dona--sm" style="--valor: ?= $porc_curso ?>;"></div>
      <div class="porcentajes">
        <span><span class="cuadro azul"></span><strong> CUMPLIMIENTO: ?= $porc_curso ?>%</strong></span><br>
        <span><span class="cuadro naranja"></span><strong> INCUMPLIMIENTO: ?= round(100 - $porc_curso, 2) ?>%</strong></span>
      </div>
    </div> -->
    
  </div>
  <!-- <div class="grafico-box compacto" id="rankingBox">
      <div class="ranking-title">RANKING DE DOCENTES (Encuesta de Satisfacción Estudiantil)</div>
      <div id="rankingContent"></div>
      <button class="ver-completo-btn" id="toggleBtn" onclick="toggleRanking()">Ver Completo</button>
  </div> -->
</div>

<div id="sesion-popover"></div>
<div id="evi-modal">
  <div id="evi-box">
    <button id="evi-close" aria-label="Cerrar">&times;</button>
    <div id="evi-gallery"></div>
  </div>
</div>

<script>
(function(){
  if(!window.SISTECU_ADMIN) window.SISTECU_ADMIN = {};
  const NS = window.SISTECU_ADMIN;
  NS.mes = <?= json_encode($mes_seleccionado) ?>;

  const pop = document.getElementById('sesion-popover');
  function stars(avg){
    avg = Math.max(0, Math.min(5, avg||0));
    const full = Math.floor(avg);
    let h = '<div class="s-stars">';
    for(let i=0;i<5;i++) h += '<span class="s-star '+(i<full?'fill':'')+'">★</span>';
    return h + ' <span style="font-size:12px;color:#555">(' + avg.toFixed(1) + '/5)</span></div>';
  }

  function render(data){
    const NOMBRE_TIPO = { 1: 'Presencial', 4: 'Google Meet', 5: 'Otra(s)' };

    let html = `
      <button class="close-btn" onclick="document.getElementById('sesion-popover').style.display='none'">×</button>
      <div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-size:14px;font-weight:bold;">
        Sesiones del mes: <b>${data.mes_label || ''}</b>
      </div>
    `;

    if (!data || !Array.isArray(data.sesiones) || data.sesiones.length === 0) {
      html += '<div class="s-meta" style="padding:8px;">No hay sesiones registradas.</div>';
      return html;                       
    }

    const overall = Number(data.overall && data.overall.avg) || 0;
    html += '<div class="s-overall" style="text-align:center;margin-top:8px;"><b>Calificación general: </b> ' + stars(overall) + '</div>';

    data.sesiones.forEach(function (s, idx) {
      const hi   = s.inicio || s.horaInicio || '';
      const hf   = s.fin    || s.horaFin    || '';
      const tipo = s.tipo || NOMBRE_TIPO[s.tipo_id] || '—';

      // --- NUEVO: deducir F7/F8 y si está completa (color #00a65a) ---
      // Espera (idealmente) que el AJAX ya envíe s.es_individual (true/false),
      // s.formato ('F7'/'F8') y s.color; si no, aplicará "mejores esfuerzos".
      const isIndividual = (typeof s.es_individual === 'boolean')
                            ? s.es_individual
                            : (String(s.formato||'').toUpperCase()==='F8'); // fallback
      const formato = s.formato ? String(s.formato).toUpperCase() : (isIndividual ? 'F8' : 'F7');

      const color = (s.color || '').toLowerCase();
      const completa = (color === '#00a65a'); // habilitado solo si está completa

      // Evidencias (como ya lo tienes)
      const eviCount = s.evi_count || 0;
      const eviDisabled = eviCount === 0 ? 'disabled' : '';
      const badge = eviCount > 0
        ? `<span class="badge" style="background:#fff;color:#27ae60;margin-left:6px">${eviCount}</span>`
        : `<span class="badge" style="background:#bbb;color:#fff;margin-left:6px">0</span>`;

      // --- NUEVO: id de sesión para el formato ---
      const idSesion = s.id || s.id_sesion || s.sesion_id || s.id_sesiones_tuto; // usa el que envíe tu AJAX

      html += `
        <div class="s-card" style="border:1px solid #e1e1e1;border-radius:8px;padding:10px;margin:8px 0;">
          <div class="s-titulo" style="font-weight:700;font-size:13px;margin-bottom:4px;">
            SESIÓN ${idx + 1}: ${s.tema || ''}
          </div>
          <div class="s-tema"   style="font-size:13px;color:#333;"><i>${s.fecha || ''} (${hi} - ${hf})</i></div>
          <div class="s-tipo"   style="font-size:13px;"><b>Modalidad:</b> ${tipo}</div>
          <div class="s-meta"   style="font-size:12px;color:#666;margin-top:4px;">Valoración de estudiantes (n=${('votos' in s) ? s.votos : 0}):</div>
          ${stars(('avg' in s) ? Number(s.avg) : 0)}

          <div style="margin-top:6px;">
            <!-- NUEVO: Botón Formato F7/F8 -->
            <button class="btn-formato"
                    ${completa ? '' : 'disabled'}
                    data-sesion="${idSesion}"
                    data-formato="${formato}">
              Formato ${formato}
            </button>

            <!-- Botón Evidencias (ya existente) -->
            <button class="btn-evi"
                    ${eviDisabled}
                    data-evi1="${s.evi1 ? s.evi1 : ''}"
                    data-evi2="${s.evi2 ? s.evi2 : ''}">
              <i class="fa fa-image"></i> Evidencias
              ${badge}
            </button>
          </div>
        </div>
      `;
    });

    return html;                           
  }

  document.addEventListener('click', async (e)=>{
    const b = e.target.closest('.btn-sesiones');
    if(!b) return;
    if(b.disabled) return;
    e.stopPropagation();

    const id = b.dataset.doc;
    const url = 'reportes/admin_ajax_sesiones_tutor.php'
              + '?id_car=<?= (int)$id_car ?>'
              + '&id_doce=' + encodeURIComponent(id)
              + '&mes=' + encodeURIComponent(NS.mes);

    try{
      const r = await fetch(url, {credentials:'same-origin'});

      // 1) Si no es 200, muestro el cuerpo para ver el error PHP o el HTML
      if (!r.ok) {
        const txt = await r.text();
        console.error('HTTP', r.status, txt);
        pop.innerHTML = `
          <button class="close-btn" onclick="document.getElementById('sesion-popover').style.display='none'">×</button>
          <div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-size:14px;font-weight:bold;">
            Sesiones del mes:
          </div>
          <div class="s-meta" style="padding:8px;color:#c00"><b>Error HTTP ${r.status}</b><br><pre style="white-space:pre-wrap">${txt}</pre></div>
        `;
        pop.style.display='block';
        return;
      }

      // 2) Intento parsear JSON; si falla, enseño el texto crudo
      const txt = await r.text();
      let data;
      try { data = JSON.parse(txt); }
      catch(parseErr){
        console.error('JSON parse error', parseErr, txt);
        pop.innerHTML = `
          <button class="close-btn" onclick="document.getElementById('sesion-popover').style.display='none'">×</button>
          <div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-size:14px;font-weight:bold;">
            Sesiones del mes:
          </div>
          <div class="s-meta" style="padding:8px;color:#c00">
            <b>Respuesta no es JSON válido</b><br><pre style="white-space:pre-wrap">${txt}</pre>
          </div>
        `;
        pop.style.display='block';
        return;
      }

      if (!data || data.ok === false) {
        pop.innerHTML = `
          <button class="close-btn" onclick="document.getElementById('sesion-popover').style.display='none'">×</button>
          <div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-size:14px;font-weight:bold;">
            Sesiones del mes:
          </div>
          <div class="s-meta" style="padding:8px">${(data && data.msg) ? data.msg : 'Error al cargar.'}</div>
        `;
        pop.style.display='block';
        return;
      }

      pop.innerHTML = render(data);
      pop.style.display='block';
    }catch(err){
      console.error('fetch error', err);
      pop.innerHTML = `
        <button class="close-btn" onclick="document.getElementById('sesion-popover').style.display='none'">×</button>
        <div class="s-meta" style="padding:8px">Error de red o CORS.</div>`;
      pop.style.display='block';
    }
  }, true);


  document.addEventListener('click', (e)=>{
    if(pop.style.display!=='block') return;
    if(!pop.contains(e.target) && !e.target.closest('.btn-sesiones')) pop.style.display='none';
  }, true);

  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') pop.style.display='none'; }, true);
})();

// ====== Visor de evidencias ======
(function(){
  const modal   = document.getElementById('evi-modal');
  const closeBt = document.getElementById('evi-close');
  const gallery = document.getElementById('evi-gallery');

  // === prefijo absoluto para las evidencias ===
  /* const EVI_BASE = 'https://tutoria.undc.edu.pe/'; */
  const EVI_BASE = 'https://tutoria.undc.edu.pe/';
  function absEvi(u){
    if (!u) return '';
    return /^https?:\/\//i.test(u) ? u : (EVI_BASE + u.replace(/^\/+/, ''));
  }

  // abrir
 // abrir
  document.addEventListener('click', function(ev){
    const btn = ev.target.closest('.btn-evi');
    if (!btn || btn.disabled) return;

    const e1 = absEvi(btn.getAttribute('data-evi1') || '');
    const e2 = absEvi(btn.getAttribute('data-evi2') || '');
    const arr = [e1, e2].filter(x => x && x !== 'NULL');

    // Tarjetas del mismo tamaño
    gallery.innerHTML = arr.length
      ? arr.map((url, i) => `
          <div class="evi-item">
            <img class="evi-img" src="${url}" data-full="${url}" alt="Evidencia ${i+1}">
            <div class="evi-caption">${url.split('/').pop()}</div>
          </div>
        `).join('')
      : '<div style="padding:40px 0">Sin evidencias.</div>';

    modal.style.display = 'flex';
  });


  // cerrar
  closeBt.addEventListener('click', () => modal.style.display = 'none');
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') modal.style.display = 'none';
  });
})();

// ==== Botón "Formato F7/F8" ====
(function(){
  document.addEventListener('click', function(ev){
    const btn = ev.target.closest('.btn-formato');
    if (!btn || btn.disabled) return;

    const sesionId = btn.getAttribute('data-sesion');
    const formato  = btn.getAttribute('data-formato'); // 'F7' o 'F8'

    if (!sesionId) {
      alert('No se encontró el identificador de sesión.');
      return;
    }

    // Construye la URL al formato de supervisión
    // Puedes añadir más parámetros si luego los necesitas (id_doce, mes, etc.)
    const url = `../pdf_ge/supervision_formato78_html.php?id_sesion=${encodeURIComponent(sesionId)}&formato=${encodeURIComponent(formato)}`;
    window.open(url, '_blank', 'width=1000,height=700');
  }, true);
})();
//RANGINK DOCENTES
/* const rankingData = ?= json_encode($dataRanking) ?>;
let showingAll = false;

function renderRanking(data) {
  const container = document.getElementById('rankingContent');
  container.innerHTML = '';
  data.forEach((d, idx) => {
    const div = document.createElement('div');
    div.classList.add('ranking-item');
    div.innerHTML = `
      <div class="ranking-label">${idx + 1}º - ${d.docente}</div>
      <div class="ranking-bar-container">
        <div class="ranking-bar" style="width:${d.promedio}%; ">
          ${d.promedio.toFixed(1)}%
        </div>
      </div>
    `;
    container.appendChild(div);
  });
}

// Mostrar Top 5 al cargar
renderRanking(rankingData.slice(0,5));

function toggleRanking() {
  const btn = document.getElementById('toggleBtn');
  if (!showingAll) {
    renderRanking(rankingData);
    btn.textContent = 'Mostrar Menos';
    showingAll = true;
  } else {
    renderRanking(rankingData.slice(0,5));
    btn.textContent = 'Ver Completo';
    showingAll = false;
  }
} */
function verDetalle(id_car, mes) {
    const url = `reportes_generales/lista_envio_informe_aula.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
    window.open(url, '_blank', 'width=1000,height=700');
}

function verDetalle1(id_car, mes) {
    const url = `reportes_generales/lista_envio_informe_mensual_cursos.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
    window.open(url, '_blank', 'width=1000,height=700');
}

</script>
</body>
</html>
