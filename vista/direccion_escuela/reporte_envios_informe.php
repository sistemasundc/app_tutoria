<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

/* --------- Guardas de acceso --------- */
if (
  !isset($_SESSION['S_IDUSUARIO']) ||
  !in_array($_SESSION['S_ROL'], [
    'DIRECTOR DE DEPARTAMENTO ACADEMICO',
    'DIRECCION DE ESCUELA',
    'COMITÉ - SUPERVISIÓN'
  ])
) {
  die('Acceso no autorizado');
}

/* =====================================================
   1. Resolver carrera y semestre seleccionado
===================================================== */
$id_car_session   = (int)($_SESSION['S_SCHOOL'] ?? 0);
$semestre_session = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$semestre_nombre = $_SESSION['S_SEMESTRE_FECHA'] ?? '';

// semestre seleccionado por GET, si no, el de sesión
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
  ? (int)$_GET['semestre']
  : $semestre_session;

$id_semestre = $semestre_sel;

// carrera = fija a la escuela del usuario
$id_car = $id_car_session;

if (!$id_car || !$id_semestre) {
  die('Error: Sesión sin carrera o semestre.');
}

/* =====================================================
   2. Nombre visible del semestre
   Ajusta estos labels a tus valores reales
   id_semestre => texto que quieres en el título
===================================================== */
$mapSemNombre = [
  32 => '2025-1',
  33 => '2025-2',
  35 => '2026-1',
  // agrega más si aplica
];

$nombre_semestre = $mapSemNombre[$id_semestre] ?? ($_SESSION['S_NOMSEMESTRE'] ?? '—');

/* =====================================================
   3. Meses permitidos por semestre
   - semestre 32 => abril a agosto
   - semestre 33 => septiembre a diciembre
   IMPORTANTE: las keys van en minúsculas porque las usas en BD
===================================================== */
$meses_por_semestre = [
  32 => [
    'abril'     => 'Abril',
    'mayo'      => 'Mayo',
    'junio'     => 'Junio',
    'julio'     => 'Julio',
  ],
  33 => [
    'septiembre' => 'Septiembre',
    'octubre'    => 'Octubre',
    'noviembre'  => 'Noviembre',
    'diciembre'  => 'Diciembre',
  ],
    35 => [
    'abril'     => 'Abril',
    'mayo'      => 'Mayo',
    'junio'     => 'Junio',
    'julio'     => 'Julio',
  ],
];

/* =====================================================
   4. Resolver mes seleccionado (GET['mes'])
   - Si no viene, tomamos el primer mes permitido del semestre
   - Si viene uno inválido para ese semestre, lo corregimos
===================================================== */
$mes_get = isset($_GET['mes']) && $_GET['mes'] !== '' 
  ? strtolower($_GET['mes']) 
  : '';

$meses_validos = $meses_por_semestre[$id_semestre] ?? [];

if (empty($meses_validos)) {
  // si por algún motivo el semestre no está en el mapa, evitamos crash
  // y forzamos un arreglo vacío
  $mes_filtro = '';
} else {
  if ($mes_get && array_key_exists($mes_get, $meses_validos)) {
    $mes_filtro = $mes_get;
  } else {
    // primer mes válido de ese semestre
    $primer_mes = array_key_first($meses_validos);
    $mes_filtro = $primer_mes;
  }
}

/* =====================================================
   5. Obtener nombre de la carrera
===================================================== */
$nombre_carrera = '……………………………';
if ($id_car > 0) {
  $sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
  $stmtCarrera = $conexion->conexion->prepare($sqlCarrera);
  $stmtCarrera->bind_param("i", $id_car);
  $stmtCarrera->execute();
  if ($rowCar = $stmtCarrera->get_result()->fetch_assoc()) {
    $nombre_carrera = strtoupper($rowCar['nom_car']);
  }
}

/* =========================================================
   6. CONSULTA:
   - base: todos los docentes asignados con ciclo
   - im:   estado de envío del informe mensual (por mes y semestre)
========================================================= */
$sql = "
SELECT 
  base.id_doce,
  base.abreviatura_doce,
  base.apepa_doce,
  base.apema_doce,
  base.nom_doce,
  base.email_doce,
  base.ciclo,
  im.estado_envio,
  im.fecha_envio
FROM (
  SELECT DISTINCT
    d.id_doce,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.email_doce,
    cl.ciclo
  FROM tutoria_docente_asignado tda
  JOIN docente d                   ON d.id_doce = tda.id_doce
  JOIN tutoria_asignacion_tutoria tat
                                   ON tat.id_docente = tda.id_doce
  JOIN carga_lectiva cl            ON cl.id_cargalectiva = tat.id_carga
                                  AND cl.id_semestre   = tda.id_semestre
                                  AND cl.id_car        = tda.id_car
  WHERE tda.id_car = ?
    AND tda.id_semestre = ?
) AS base
LEFT JOIN (
  SELECT 
    im.id_docente,
    MAX(im.estado_envio) AS estado_envio,   -- 2 = enviado; 1 = guardado
    MAX(im.fecha_envio)  AS fecha_envio
  FROM tutoria_informe_mensual im
  WHERE im.id_semestre = ?
    AND LOWER(im.mes_informe) = LOWER(?)
  GROUP BY im.id_docente
) AS im ON im.id_docente = base.id_doce
ORDER BY base.apepa_doce, base.apema_doce, base.nom_doce
";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("iiss", $id_car, $id_semestre, $id_semestre, $mes_filtro);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =====================================================
   7. Clasificar quién cumplió y quién no
===================================================== */
$enviaron = [];
$no_presentaron = [];

foreach ($rows as $doc) {
  $estado = isset($doc['estado_envio']) ? (int)$doc['estado_envio'] : 0;
  $doc['fecha_envio_fmt'] = !empty($doc['fecha_envio'])
      ? date('d/m/Y H:i', strtotime($doc['fecha_envio'])) : '--';

  if ($estado === 2) {
    $doc['estado_final'] = 'CUMPLIÓ';
    $enviaron[] = $doc;
  } elseif ($estado === 1) {
    $doc['estado_final'] = 'GUARDADO (sin envío)';
    $no_presentaron[] = $doc;
  } else {
    $doc['estado_final'] = 'NO CUMPLIÓ';
    $no_presentaron[] = $doc;
  }
}

/* =====================================================
   8. HTML
   - Mostramos 2 selects: semestre y mes
   - Ambos están dentro del mismo <form> para que viajen juntos
===================================================== */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Informe Mensual - Envíos</title>
  <style>
    body {
      background-color: white;
      font-family: Arial, sans-serif;
      padding: 30px;
      color:#000;
    }
    .contenedor {
      max-width: 1000px;
      margin: auto;
      position: relative;
    }

    /* barra superior derecha: imprimir */
    .boton-imprimir {
      position:absolute;
      right:0;
      top:0;
      background-color: #1a5276;
      color: white;
      padding: 10px 20px;
      border: none;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      font-size:14px;
    }

    h2 {
      text-align: center;
      margin: 6px 0;
    }
    .subtitulo-escuela {
      text-align:center;
      margin-bottom:10px;
    }

    /* bloque filtros */
    .filtros {
      display:flex;
      justify-content:center;
      flex-wrap:wrap;
      gap:8px;
      margin-bottom:20px;
      font-size:14px;
      line-height:1.4;
    }
    .filtros select {
      font-size:14px;
      padding:6px 8px;
      border:1px solid #1a5276;
      border-radius:5px;
      color:#1a5276;
      font-weight:bold;
      background:#fff;
      min-width:140px;
    }

    h3 {
      text-align: left;
      margin-top: 30px;
      margin-bottom: 8px;
      font-size:15px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
      font-size:14px;
    }
    th {
      background: #1a5276;
      color: white;
      padding: 8px;
      text-align: left;
      font-size:14px;
    }
    td {
      padding: 8px;
      border: 1px solid #ccc;
      font-size:14px;
    }
    .fila-verde     { background-color: #d5f5e3; }
    .fila-roja      { background-color: #f9c0c0; }
    .fila-amarilla  { background-color: rgb(243, 236, 172); }

    @media print {
      .boton-imprimir,
      .filtros {
        display: none !important;
      }
    }
  </style>
</head>
<body>

<div class="contenedor">

  <button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>

  <h2>INFORMES MENSUALES - <?= htmlspecialchars($nombre_semestre) ?></h2>
  <div class="subtitulo-escuela">
    <strong>ESCUELA PROFESIONAL DE <?= $nombre_carrera ?></strong>
  </div>

  <!-- filtros semestre + mes -->
  <form method="GET" class="filtros">
    <!-- Selección de semestre -->
    <div>
      <label for="semestre"><strong>Semestre:</strong></label><br>
      <select id="semestre" name="semestre" onchange="this.form.submit()">
        <?php foreach ($mapSemNombre as $idS => $labelS): ?>
          <option value="<?= $idS ?>" <?= ($idS == $id_semestre ? 'selected' : '') ?>>
            <?= htmlspecialchars($labelS) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Selección de mes (dinámico según semestre) -->
    <div>
      <label for="mes"><strong>Mes:</strong></label><br>
      <select id="mes" name="mes" onchange="this.form.submit()">
        <?php if (!empty($meses_validos)): ?>
          <?php foreach ($meses_validos as $m_valor => $m_label): ?>
            <option value="<?= $m_valor ?>" <?= ($m_valor === $mes_filtro ? 'selected' : '') ?>>
              <?= $m_label ?>
            </option>
          <?php endforeach; ?>
        <?php else: ?>
          <option value="">(sin meses disponibles)</option>
        <?php endif; ?>
      </select>
    </div>
  </form>

  <h3>LISTA DE DOCENTES QUE PRESENTARON SU INFORME - MES <?= strtoupper($mes_filtro) ?></h3>
  <table>
    <thead>
      <tr>
        <th>N°</th>
        <th>Docente</th>
        <th>Correo</th>
        <th>Ciclo</th>
        <th>Fecha de Envío</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($enviaron)): ?>
        <tr><td colspan="6">Ningún docente ha enviado.</td></tr>
      <?php else: $i = 1; foreach ($enviaron as $doc): ?>
        <tr class="fila-verde">
          <td><?= $i++ ?></td>
          <td><?= strtoupper($doc['abreviatura_doce'].' '.$doc['apepa_doce'].' '.$doc['apema_doce'].', '.$doc['nom_doce']) ?></td>
          <td><?= htmlspecialchars($doc['email_doce']) ?></td>
          <td><?= strtoupper($doc['ciclo']) ?></td>
          <td><?= $doc['fecha_envio'] ? date('d/m/Y H:i', strtotime($doc['fecha_envio'])) : '--' ?></td>
          <td><?= $doc['estado_final'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <h3>DOCENTES QUE AÚN NO PRESENTAN SU INFORME - MES <?= strtoupper($mes_filtro) ?></h3>
  <table>
    <thead>
      <tr>
        <th>N°</th>
        <th>Docente</th>
        <th>Correo</th>
        <th>Ciclo</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($no_presentaron)): ?>
        <tr><td colspan="5">Todos los docentes cumplieron.</td></tr>
      <?php else: $i = 1; foreach ($no_presentaron as $doc): ?>
        <?php
          $class = ($doc['estado_final'] === 'CUMPLIÓ')
            ? 'fila-verde'
            : (($doc['estado_final'] === 'GUARDADO (sin envío)')
                ? 'fila-amarilla'
                : 'fila-roja');
        ?>
        <tr class="<?= $class ?>">
          <td><?= $i++ ?></td>
          <td><?= strtoupper($doc['abreviatura_doce'].' '.$doc['apepa_doce'].' '.$doc['apema_doce'].', '.$doc['nom_doce']) ?></td>
          <td><?= htmlspecialchars($doc['email_doce']) ?></td>
          <td><?= strtoupper($doc['ciclo']) ?></td>
          <td><?= $doc['estado_final'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

</div>

</body>
</html>
