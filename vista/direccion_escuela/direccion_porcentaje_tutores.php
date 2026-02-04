<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECCION DE ESCUELA', 'DIRECTOR DE DEPARTAMENTO ACADEMICO', 'COMITÉ - SUPERVISIÓN'])) {
  die('Acceso no autorizado');
}

$id_car      = (int)$_SESSION['S_SCHOOL'];
$id_semestre = (int)$_SESSION['S_SEMESTRE'];
$semestre_nombre = $_SESSION['S_SEMESTRE_FECHA'] ?? '';
 
$conexion = new conexion();
$cn = $conexion->conectar();

/* ==================== util: mes nombre -> número ==================== */
function mesNumero($mesNombre) {
  $map = [
    'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
    'julio'=>7,'agosto'=>8,'septiembre'=>9,'setiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
  ];
  $m = strtolower(trim($mesNombre));
  return $map[$m] ?? 1;
}

/* ==================== meses permitidos ==================== */
$MESES_PERMITIDOS = ['abril','mayo','junio','julio'];
/* $MESES_PERMITIDOS = ['agosto','septiembre','octubre','noviembre','diciembre']; */
$meses = $MESES_PERMITIDOS;
$mes_seleccionado = strtolower($_GET['mes'] ?? '');
if (!in_array($mes_seleccionado, $MESES_PERMITIDOS, true)) {
    $mes_seleccionado = 'abril';
}
$mes_num = mesNumero($mes_seleccionado);

/* ==================== nombre de escuela ==================== */
$sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
$stmt = $cn->prepare($sqlCarrera);
$stmt->bind_param("i", $id_car);
$stmt->execute();
$escuela = $stmt->get_result()->fetch_assoc()['nom_car'] ?? 'Escuela';

// Total tutores AULA (rol 6)
$sqlTotalAula = "
SELECT COUNT(DISTINCT pc.id_cargalectiva) AS total
FROM tutoria_plan_compartido pc
WHERE pc.id_car = ? 
  AND pc.id_cargalectiva IN (
    SELECT cl.id_cargalectiva FROM carga_lectiva cl
    WHERE cl.id_semestre = ? AND cl.id_car = ?
  )
";

$stmt = $cn->prepare($sqlTotalAula);
$stmt->bind_param("iii", $id_car, $id_semestre, $id_car);
$stmt->execute();
$total_aula = $stmt->get_result()->fetch_assoc()['total'] ?: 1;

// Cumplimiento AULA - quienes han enviado informe mensual (estado_envio = 2)
/* ==================== CUMPLIMIENTO AULA (tutoria_docente_asignado) ==================== */

// TOTAL docentes asignados como Tutor de Aula en el semestre/carrera
$sqlTotalAula = "
  SELECT COUNT(DISTINCT tda.id_doce) AS total
  FROM tutoria_docente_asignado tda
  WHERE tda.id_semestre = ?
    AND tda.id_car      = ?
";
$stmt = $cn->prepare($sqlTotalAula);
$stmt->bind_param("ii", $id_semestre, $id_car);
$stmt->execute();
$total_aula = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// ENVIADOS (estado_envio=2) por ese mes/semestre/carrera y por docente
$sqlEnvioAula = "
  SELECT COUNT(*) AS enviados
  FROM (
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
";
$stmt = $cn->prepare($sqlEnvioAula);
$stmt->bind_param("iis", $id_semestre, $id_car, $mes_seleccionado);
$stmt->execute();
$enviados_aula = (int)($stmt->get_result()->fetch_assoc()['enviados'] ?? 0);


// Total tutores CURSO
$sqlTotalCurso = "SELECT COUNT(DISTINCT id_doce) AS total FROM carga_lectiva WHERE id_semestre = ? AND id_car = ?  AND tipo='M'
             AND NOT (id_doce=17 AND id_car=1)";
$stmt = $cn->prepare($sqlTotalCurso);
$stmt->bind_param("ii", $id_semestre, $id_car);
$stmt->execute();
$total_curso = $stmt->get_result()->fetch_assoc()['total'] ?: 1;

// Cumplimiento CURSO
$sqlEnvioCurso = "
SELECT COUNT(DISTINCT cl.id_doce) AS enviados
FROM carga_lectiva cl
INNER JOIN tutoria_informe_mensual_curso imc ON cl.id_cargalectiva = imc.id_cargalectiva
WHERE cl.id_semestre = ? AND cl.id_car = ?
  AND imc.estado_envio = 2
  AND LOWER(imc.mes_informe) = ?
";

$stmt = $cn->prepare($sqlEnvioCurso);
if (!$stmt) {
    die("Error en prepare (Curso): " . $cn->error);
}
$stmt->bind_param("iis", $id_semestre, $id_car, $mes_seleccionado);
$stmt->execute();
$enviados_curso = ($stmt->get_result()->fetch_assoc()['enviados'] ?? 0);

// Cálculo final
$porc_aula  = ($total_aula  > 0) ? round(($enviados_aula  / $total_aula)  * 100, 2) : 0;
$porc_curso = ($total_curso > 0) ? round(($enviados_curso / $total_curso) * 100, 2) : 0;

//RANKING DE DOCENETS 
$sqlRanking = "SELECT preg_6 FROM tutoria_encuesta_satisfaccion WHERE escuela_profesional = ?";
$stmt = $cn->prepare($sqlRanking);
$stmt->bind_param("s", $escuela);
$stmt->execute();
$res = $stmt->get_result();

$puntajes = []; // [id_doce => [total, cantidad]]

while ($r = $res->fetch_assoc()) {
    $json = json_decode($r['preg_6'], true);
    if (is_array($json)) {
        foreach ($json as $id_docente => $puntos) {
            $puntajes[$id_docente]['total'] = ($puntajes[$id_docente]['total'] ?? 0) + $puntos;
            $puntajes[$id_docente]['cantidad'] = ($puntajes[$id_docente]['cantidad'] ?? 0) + 1;
        }
    }
}

// Calcular promedios en %
$promedios = [];
foreach ($puntajes as $id => $data) {
    $prom = $data['total'] / $data['cantidad'];
    $porcentaje = round(($prom / 5) * 100, 1);
    $promedios[$id] = $porcentaje;
}

// Obtener nombres de los docentes
$nombres = [];
if (!empty($promedios)) {
    $ids = implode(',', array_map('intval', array_keys($promedios)));
    $sqlDocentes = "SELECT id_doce, CONCAT(UPPER(abreviatura_doce), ' ', UPPER(apepa_doce), ' ', UPPER(apema_doce), ', ', UPPER(nom_doce)) AS nombre 
                    FROM docente WHERE id_doce IN ($ids)";
    $resDoc = $cn->query($sqlDocentes);

    while ($row = $resDoc->fetch_assoc()) {
        $nombres[$row['id_doce']] = $row['nombre'];
    }
}

// Ordenar descendente
arsort($promedios);

// Armar data para gráfico
$dataRanking = [];
foreach ($promedios as $id => $porcentaje) {
    $dataRanking[] = [
        'docente' => $nombres[$id] ?? "Docente $id",
        'promedio' => $porcentaje
    ];
}

// Top 5
$top5 = array_slice($dataRanking, 0, 5);
$completo = json_encode($dataRanking);

/* ==================== ACTIVIDADES – TUTORES DE AULA ==================== */
/* Tu consulta de la imagen 2 (parametrizada a id_car / id_semestre) */
$sqlTutores = "
  SELECT
    d.id_doce,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.email_doce,
    cl.ciclo,
    GROUP_CONCAT(
      DISTINCT CONCAT(cl.turno, ' - ', cl.seccion)
      ORDER BY cl.turno, cl.seccion
      SEPARATOR ' | '
    ) AS turno_seccion
  FROM tutoria_asignacion_tutoria tat
  JOIN docente d
    ON d.id_doce = tat.id_docente
  JOIN carga_lectiva cl
    ON cl.id_cargalectiva = tat.id_carga
  JOIN tutoria_docente_asignado tda
    ON tda.id_doce = tat.id_docente
  AND tda.id_semestre = cl.id_semestre
  WHERE cl.id_car = ?        
    AND cl.id_semestre = ?   
  GROUP BY d.id_doce, cl.ciclo
  ORDER BY FIELD(cl.ciclo,'I','II','III','IV','V','VI','VII','VIII','IX','X'), 
          d.apepa_doce, d.apema_doce, d.nom_doce;
";
$stmt = $cn->prepare($sqlTutores);
$stmt->bind_param('ii', $id_car, $id_semestre);
$stmt->execute();
$resTutores = $stmt->get_result();

$tutores = [];
$ids = [];
while ($r = $resTutores->fetch_assoc()) {
  $ids[] = (int)$r['id_doce'];
  $tutores[] = $r;
}

/* Contar sesiones F78 por mes y semestre para esos tutores (rol 6) */
$sesionesPorDoc = [];
if (!empty($ids)) {
  // placeholders dinámicos
  $ph = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids)+2); // id_semestre, mes, ids...
  $params = [];
  $params[] = $id_semestre;
  $params[] = $mes_num;
  foreach ($ids as $v) { $params[] = $v; }

  $sqlSes = "
    SELECT id_doce, COUNT(*) AS sesiones
    FROM tutoria_sesiones_tutoria_f78
    WHERE id_rol = 6
      AND id_semestre = ?
      AND MONTH(fecha) = ?
      AND id_doce IN ($ph)
    GROUP BY id_doce
  ";
  $stmt = $cn->prepare($sqlSes);

  // bind_param dinámico
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $resS = $stmt->get_result();
  while ($s = $resS->fetch_assoc()) {
    $sesionesPorDoc[(int)$s['id_doce']] = (int)$s['sesiones'];
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Tutores</title>
  <style>
    body {font-family: Arial, sans-serif;background: #f4f6f9;margin: 0;}
    h2 {text-align: center;background-color: #2c3e50;color: white;padding: 15px;font-size: 18px;font-weight: bold;}
    .contenedor {display: grid;grid-template-columns: 1fr 550px; /* tabla ocupa todo | card fija de ~360px */gap: 30px;padding: 30px;align-items: start; }
    .grafico-box {position: relative;background: white;padding: 40px;border-radius: 12px;box-shadow: 0 2px 6px rgba(0,0,0,0.2); text-align: center;}
    .titulo {font-size: 16px;font-weight: bold;margin-bottom: 15px;}
    .dona {--valor: 0;width: 120px;height: 120px;margin: auto;border-radius: 50%;background: conic-gradient(
        rgb(27, 190, 68) calc(var(--valor) * 1%),
        rgb(204, 37, 31) 0
      );
      position: relative;
    }
    .dona::before {content: '';position: absolute;top: 20px;left: 20px;width: 80px;height: 80px;background: white;border-radius: 50%;}
    .porcentajes {margin-top: 12px;font-size: 14px;line-height: 1.6;}
    .porcentajes span {display: inline-block; margin: 3px 5px;}
    .cuadro {display: inline-block;width: 12px; height: 12px; border-radius: 2px; margin-right: 5px;vertical-align: middle;}
    .azul { background-color:rgb(27, 190, 68); }
    .naranja { background-color:rgb(204, 37, 31); }
    .ranking-box {background: white;padding: 20px;margin: 20px 30px;border-radius: 12px;box-shadow: 0 2px 6px rgba(0,0,0,0.2);}
    .ranking-title {font-size: 16px;font-weight: bold; margin-bottom: 15px;text-align: center;}
    .ranking-item {display: flex; align-items: center;margin-bottom: 8px;}
    .ranking-label { width: 40%;font-size: 14px; font-weight: 500; }
    .ranking-bar-container {flex: 1; background: #eee;border-radius: 5px; overflow: hidden;  height: 20px; }
    .ranking-bar { height: 20px;  background: #2e86de; text-align: right;  padding-right: 5px;  color: #fff;  font-size: 12px;   line-height: 20px; }
    .ver-completo-btn { display: block;  margin: 10px auto 0;  padding: 6px 12px;  background:rgb(230, 121, 20);  color: white; border: none;  cursor: pointer;  border-radius: 5px; }
    /* TABLA ACTIVIDADES */
    .tabla { width:100%; border-collapse:collapse; font-size:13px; }
    .tabla th, .tabla td { border:1px solid #2c3e50; padding:8px 10px; }
    .tabla th { color:#ffff; background:#2c3e50; text-align:center;  }
    .tabla td:nth-child(1), .tabla td:nth-child(4), .tabla td:nth-child(5) { text-align:center; }
    .pill-ver { background:#27ae60; color:#fff; border:none; padding:4px 10px; border-radius:12px; cursor:default; }
    .muted { color:#888; }
    /* Card y dona compactas */
    .col-derecha{
      display:flex;
      flex-direction:column;
      gap:20px;
    }

    /* Opcional: versión compacta (si ya la usas, déjala) */
    .grafico-box.compacto{ padding:20px 22px; }
    .dona {--valor: 0; width: 150px;   /* más grande */height: 150px;  /* más grande */ margin: auto; border-radius: 50%; background: conic-gradient(
      rgb(27, 190, 68) calc(var(--valor) * 1%),
      rgb(204, 37, 31) 0
    ); position: relative;}
    .dona::before {content: ''; position: absolute; top: 25px;   /* margen interior más pequeño => dona más gruesa */ left: 25px; width: 100px;   /* centro más pequeño */ height: 100px; background: white;border-radius: 50%;}
    @media (max-width:1100px){
      .contenedor{ grid-template-columns:1fr; }
    }

    .btn-ver { position: absolute; top: 10px; right: 10px; background: #1a5276; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; }
  /* Botón deshabilitado */
    .pill-ver[disabled]{background:#bdbdbd;cursor:not-allowed; opacity:.7;}
    .pill-ver:not([disabled]){ cursor: pointer; }
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
<h2>
  REPORTE DE ACTIVIDAD DE TUTORES - <?= strtoupper($escuela) ?> - <?= strtoupper($mes_seleccionado) ?> - <?= htmlspecialchars($semestre_nombre) ?>
</h2>
  <form method="GET" action="index.php" style="text-align:center; margin-top: 10px;">
    <input type="hidden" name="pagina" value="direccion_escuela/direccion_porcentaje_tutores.php">
    <label for="mes">Selecciona el mes:</label>
    <select name="mes" onchange="this.form.submit()" style="padding: 5px; margin-left: 5px;">
      <?php
      foreach ($meses as $mes) {
          $sel = ($mes === $mes_seleccionado) ? 'selected' : '';
          echo "<option value='$mes' $sel>" . ucfirst($mes) . "</option>";
      }
      ?>
    </select>
  </form>
<div class="contenedor">
  <div class="grafico-box">
    <div class="titulo">ACTIVIDADES - TUTORES DE AULA</div>
    <table class="tabla">
      <thead>
        <tr>
          <th><strong>ID</strong></th>
          <th><strong>CICLO</strong></th>
          <th><strong>TURNO - SECCIÓN</strong></th>
          <th><strong>TUTOR RESPONSABLE</strong></th>
          <th><strong>N° SESIONES</strong></th>
          <th><strong>ACCIÓN</strong></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tutores)): ?>
          <tr><td colspan="6" class="muted">Sin tutores registrados para el semestre seleccionado.</td></tr>
        <?php else: foreach ($tutores as $t):
            $id = (int)$t['id_doce'];
            $nombre = strtoupper(trim(($t['abreviatura_doce']? $t['abreviatura_doce'].' ':'').$t['apepa_doce'].' '.$t['apema_doce'].', '.$t['nom_doce']));
            $ciclo = $t['ciclo'] ?? '';
            $turno_seccion = $t['turno_seccion'] ?? '';
            $ses   = $sesionesPorDoc[$id] ?? '---';
          ?>
            <tr>
              <td><?= $id ?></td> <!-- aquí va el ID del docente -->
              <td><?= htmlspecialchars($ciclo) ?></td>
              <td><?= htmlspecialchars($turno_seccion) ?> </td>
              <td style="text-align:left;"><?= htmlspecialchars($nombre) ?></td>
              <td><strong><?= $ses ?></strong></td>
              <td>
                <button
                  type="button"
                  class="pill-ver btn-sesiones"
                  data-doc="<?= $id ?>"
                  data-nombre="<?= htmlspecialchars($nombre) ?>"
                  <?= $ses > 0 ? '' : 'disabled' ?>
                >VER</button>
              </td>
            </tr>
          <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="col-derecha">
    <div class="grafico-box compacto">
      <button class="btn-ver" onclick="verDetalle(<?= (int)$id_car ?>, '<?= htmlspecialchars($mes_seleccionado) ?>')">VER</button>
      <div class="titulo">Cumplimiento entrega de informes - Tutor de Aula</div>
      <div class="dona dona--sm" style="--valor: <?= $porc_aula ?>;"></div>
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

</div>
<!-- <div class="ranking-box" id="rankingBox">
  <div class="ranking-title">RANKING DE DOCENTES (Encuesta de Satisfacción Estudiantil)</div>
  <div id="rankingContent"></div>
  <button class="ver-completo-btn" id="toggleBtn" onclick="toggleRanking()">Ver Completo</button>
</div>  -->
<div id="sesion-popover" style="display:none"></div>

<div id="evi-modal">
  <div id="evi-box">
    <button id="evi-close" aria-label="Cerrar">&times;</button>
    <div id="evi-gallery"></div>
  </div>
</div>
<script>
/* ==== INIT idempotente: se puede ejecutar N veces sin romper nada ==== */
(function () {
  // 1) Namespace global para permitir re-bind seguro
  if (!window.SISTECU_TUTORES_AULA) window.SISTECU_TUTORES_AULA = {};
  const NS = window.SISTECU_TUTORES_AULA;

  // 2) Mes actual desde PHP
  NS.mes = <?= json_encode($mes_seleccionado) ?>;

  // 3) Asegurar popover único en <body> sin estilos inline de posición
  function ensurePopover() {
    let p = document.getElementById('sesion-popover');
    if (!p) {
      p = document.createElement('div');
      p.id = 'sesion-popover';
      p.innerHTML = '';              // contenido se construye dinámicamente
      document.body.appendChild(p);
    }
    return p;
  }
  const POPOVER = ensurePopover();

  // 4) Cerrar al hacer click fuera
  function onDocClickClose(e) {
    const isBtn = e.target.closest && e.target.closest('.btn-sesiones');
    if (!POPOVER.contains(e.target) && !isBtn) {
      POPOVER.style.display = 'none';
    }
  }

  // 5) Abrir popover (centrado) al presionar "VER"
  async function onDocClickBtn(e) {
    const btn = e.target.closest && e.target.closest('.btn-sesiones');
    if (!btn || btn.disabled) return;
    e.stopPropagation();

    const id_doce = btn.dataset.doc;
    const url = 'direccion_escuela/ajax_sesiones_tutor.php'
      + '?id_doce=' + encodeURIComponent(id_doce)
      + '&mes='     + encodeURIComponent(NS.mes);

    try {
      const res = await fetch(url, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      if (!data.ok) {
        POPOVER.innerHTML =
          '<button class="close-btn" onclick="document.getElementById(\'sesion-popover\').style.display=\'none\'">×</button>'
          + '<div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-weight:bold;">Sesiones</div>'
          + '<div class="s-meta" style="padding:8px;">' + (data.msg || 'No se encontraron sesiones.') + '</div>';
      } else {
        POPOVER.innerHTML = renderPopoverHTML(data);
      }
    } catch (err) {
      console.error('Error cargando sesiones:', err);
      POPOVER.innerHTML =
        '<button class="close-btn" onclick="document.getElementById(\'sesion-popover\').style.display=\'none\'">×</button>'
        + '<div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-weight:bold;">Sesiones</div>'
        + '<div class="s-meta" style="padding:8px;">No se pudo cargar la información (' + String(err) + ').</div>';
    }

    // Mostrar centrado (ya no calculamos top/left)
    POPOVER.style.display = 'block';
  }

  // 6) (Re)bind seguro de listeners
  if (NS.bound) {
    document.removeEventListener('click', NS.onDocClickClose, true);
    document.removeEventListener('click', NS.onDocClickBtn,   true);
    document.removeEventListener('keydown', NS.onEscKey,      true);
  }
  document.addEventListener('click', onDocClickClose, true);
  document.addEventListener('click', onDocClickBtn,   true);

  // 7) Cerrar con ESC
  function onEscKey(e){
    if (e.key === 'Escape') POPOVER.style.display = 'none';
  }
  document.addEventListener('keydown', onEscKey, true);

  // Guardar refs para posible re-bind futuro
  NS.bound = true;
  NS.onDocClickClose = onDocClickClose;
  NS.onDocClickBtn   = onDocClickBtn;
  NS.onEscKey        = onEscKey;

  // 8) Helpers de render
  window.renderStars = function (avg) {
    avg = Math.max(0, Math.min(5, avg || 0));
    const full = Math.floor(avg);
    let html = '<div class="s-stars" style="font-size:16px;line-height:1">';
    for (let i = 0; i < 5; i++) html += '<span class="s-star ' + (i < full ? 'fill' : '') + '" style="' + (i < full ? 'color:#f5c518' : 'color:#d7d7d7') + '">★</span>';
    html += ' <span style="font-size:12px;color:#555">(' + avg.toFixed(1) + '/5)</span></div>';
    return html;
  };

  window.renderPopoverHTML = function (data) {
    const NOMBRE_TIPO = { 1: 'Presencial', 4: 'Google meet', 5: 'Otra(s)' };

    let html = `
      <button class="close-btn" onclick="document.getElementById('sesion-popover').style.display='none'">×</button>
      <div class="s-header" style="text-align:center;background:#2c3e50;color:#fff;padding:8px;font-size:14px;font-weight:bold;">
        Sesiones del mes: <b>${data.mes_label || ''}</b>
      </div>
    `;

    if (!data.sesiones || !data.sesiones.length) {
      return html + '<div class="s-meta" style="padding:8px;">No hay sesiones registradas.</div>';
    }

    const overall = (data.overall && typeof data.overall.avg === 'number') ? data.overall.avg : 0;
    html += '<div class="s-overall" style="text-align:center;margin-top:8px;"><b>Calificación general: </b> ' + renderStars(overall) +'</div>';

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
      const idSesion = s.id_sesion || s.id || s.id_sesiones_tuto; // usa el que envíe tu AJAX

      html += `
        <div class="s-card" style="border:1px solid #e1e1e1;border-radius:8px;padding:10px;margin:8px 0;">
          <div class="s-titulo" style="font-weight:700;font-size:13px;margin-bottom:4px;">
            SESIÓN ${idx + 1}: ${s.tema || ''}
          </div>
          <div class="s-tema"   style="font-size:13px;color:#333;"><i>${s.fecha || ''} (${hi} - ${hf})</i></div>
          <div class="s-tipo"   style="font-size:13px;"><b>Modalidad:</b> ${tipo}</div>
          <div class="s-meta"   style="font-size:12px;color:#666;margin-top:4px;">Valoración de estudiantes (n=${('votos' in s) ? s.votos : 0}):</div>
          ${renderStars(('avg' in s) ? s.avg : 0)}

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
  };

})();
// ====== Visor de evidencias ======
(function(){
  const modal   = document.getElementById('evi-modal');
  const closeBt = document.getElementById('evi-close');
  const gallery = document.getElementById('evi-gallery');

  // === prefijo absoluto para las evidencias ===
  /* const EVI_BASE = 'https://tutoria.undc.edu.pe/'; */
  const EVI_BASE = 'http://localhost/tutoria/';
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
    const url = `direccion_escuela/reporte_envios_informe.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
    window.open(url, '_blank', 'width=1000,height=700');
}

function verDetalle1(id_car, mes) {
    const url = `direccion_escuela/reporte_envios_informe_cursos.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
    window.open(url, '_blank', 'width=1000,height=700');
}

</script>

</body>
</html>