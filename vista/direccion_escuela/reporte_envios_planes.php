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

/* ==============================
   1. Resolvemos carrera y semestre
============================== */
$id_car_session      = (int)($_SESSION['S_SCHOOL'] ?? 0);
$semestre_session    = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$semestre_nombre = $_SESSION['S_SEMESTRE_FECHA'] ?? '';

// semestre seleccionado por GET (si el usuario usa el filtro)
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
  ? (int)$_GET['semestre']
  : $semestre_session;

// este es el semestre que realmente vamos a usar en las consultas
$id_semestre = $semestre_sel;

// carrera: tomamos SIEMPRE la de la sesión (el director no cambia de escuela)
$id_car = $id_car_session;

if (!$id_car || !$id_semestre) { 
  die('Error: Sesión sin carrera o semestre.');
}

/* ==============================
   2. Mapeamos nombre legible del semestre
   Ajusta este mapa a tus valores reales:
   - clave: id_semestre (32, 33, etc.)
   - valor: cómo quieres que salga impreso
============================== */
$mapSemNombre = [
  32 => '2025-1',
  33 => '2025-2',
  35 => '2026-1',
  // agrega más si aplica
];

// nombre que sale en el título
$nombre_semestre = $mapSemNombre[$id_semestre] ?? ($_SESSION['S_NOMSEMESTRE'] ?? '—');

/* --------- Nombre de carrera --------- */
$nombre_carrera = '……………………………';
$sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
$stmtCarrera = $conexion->conexion->prepare($sqlCarrera);
$stmtCarrera->bind_param("i", $id_car);
$stmtCarrera->execute();
if ($resCar = $stmtCarrera->get_result()->fetch_assoc()) {
  $nombre_carrera = strtoupper($resCar['nom_car']);
}

/* ==========================================================
   3. CONSULTA ÚNICA:
   - base: docentes oficiales (tda) con carga real (tat + cl)
   - plan agregado: estado del plan por docente
============================================================= */
$sql = "
SELECT
  base.id_doce,
  base.abreviatura_doce,
  base.apepa_doce,
  base.apema_doce,
  base.nom_doce,
  base.email_doce,
  base.ciclo,
  p.estado_envio,
  p.fecha_envio
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
    tat.id_docente                      AS id_docente,
    MAX(tpc.estado_envio)              AS estado_envio,  -- 2 enviado, 1 guardado
    MAX(tpc.fecha_envio)               AS fecha_envio
  FROM tutoria_plan_compartido tpc
  JOIN carga_lectiva cl  ON cl.id_cargalectiva = tpc.id_cargalectiva
  JOIN tutoria_asignacion_tutoria tat
                         ON tat.id_carga    = cl.id_cargalectiva
                        AND tat.id_semestre = cl.id_semestre
  WHERE cl.id_car = ?
    AND cl.id_semestre = ?
  GROUP BY tat.id_docente
) AS p ON p.id_docente = base.id_doce
";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("iiii", $id_car, $id_semestre, $id_car, $id_semestre);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* --------- Clasificación: enviados / no enviados --------- */
$enviaron = [];
$no_enviaron = [];

foreach ($rows as $doc) {
  $estado = isset($doc['estado_envio']) ? (int)$doc['estado_envio'] : 0;
  $doc['estado_final'] = ($estado === 2) ? 'CUMPLIÓ'
                     : (($estado === 1) ? 'GUARDADO (sin envío)' : 'NO CUMPLIÓ');

  if ($estado === 2) {
    $enviaron[] = $doc;
  } else {
    $no_enviaron[] = $doc;
  }
}

/* --------- armamos opciones del combo semestre ---------
   Mostramos al menos los semestres que el director puede querer ver.
   Puedes generar esto dinámico desde BD si quieres.
--------------------------------------------------------- */
$opciones_semestre = [
  32 => '2025-1',
  33 => '2025-2',
  35 => '2026-1',
  // agrega más
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Relación de Docentes – Plan de Tutoría</title>
  <style>
    body {
      background:#fff !important;
      font-family: Arial, sans-serif;
      margin:0; padding:30px;
      color:#000;
    }
    .contenedor {
      padding:20px;
      max-width:1100px;
      margin:auto;
      position: relative;
    }

    /* header superior con botón imprimir y selector semestre */
    .acciones-superior{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      flex-wrap:wrap;
      margin-bottom:10px;
    }

    .boton-imprimir {
      background:#1a5276;
      color:#fff;
      padding:10px 20px;
      border:none;
      font-weight:bold;
      border-radius:5px;
      cursor:pointer;
      font-size:14px;
    }
    @media print { .boton-imprimir { display:none !important; } }

    .filtro-semestre{
      font-size:14px;
      font-family:inherit;
      padding:8px 10px;
      border:1px solid #1a5276;
      border-radius:5px;
      background:#fff;
      color:#1a5276;
      font-weight:bold;
    }
    @media print { .filtro-semestre { display:none !important; } }

    h2 { text-align:center; font-size:20px; margin:6px 0; }
    h2.semestre-line { font-weight:normal; text-align:center; font-size:15px; margin:6px 0 20px; }
    h3 { text-align:left; font-size:15px; margin:28px 0 10px; }

    table { width:100%; border-collapse:collapse; margin-bottom:24px; }
    th {
      background:#1a5276;
      color:#fff;
      padding:8px;
      text-align:left;
      font-size:14px;
    }
    td {
      padding:8px;
      border:1px solid #ccc;
      font-size:14px;
    }

    .fila-verde    { background:rgb(177,243,192); font-size:15px; }
    .fila-roja     { background:rgb(241,193,197); font-size:15px; }
    .fila-amarilla { background:rgb(245,243,148); font-size:15px; }
  </style>
</head>
<body>

<div class="contenedor">

  <!-- barra de acciones (Imprimir + Filtro Semestre) -->
  <div class="acciones-superior">
    <form method="GET" id="formSemestre">
      <select name="semestre" class="filtro-semestre" onchange="document.getElementById('formSemestre').submit();">
        <?php foreach ($opciones_semestre as $id => $label): ?>
          <option value="<?= $id ?>" <?= ($id == $id_semestre ? 'selected' : '') ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>
  </div>

  <!-- títulos -->
  <h2>RELACIÓN DE DOCENTES TUTORES – PLAN DE TUTORÍA</h2>
  <h2 class="semestre-line">
    SEMESTRE <?= htmlspecialchars($nombre_semestre) ?> ·
    ESCUELA PROFESIONAL DE <?= $nombre_carrera ?>
  </h2>

  <!-- tabla de CUMPLIERON -->
  <h3>DOCENTES QUE ENVIARON SU PLAN</h3>
  <table>
    <thead>
      <tr>
        <th>Nº</th>
        <th>Docente</th>
        <th>Correo</th>
        <th>Ciclo</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($enviaron)): ?>
      <tr><td colspan="5">Ningún docente ha enviado aún.</td></tr>
    <?php else: $i=1; foreach ($enviaron as $doc): ?>
      <tr class="fila-verde">
        <td><?= $i++ ?></td>
        <td><?= strtoupper($doc['abreviatura_doce'].' '.$doc['apepa_doce'].' '.$doc['apema_doce'].', '.$doc['nom_doce']) ?></td>
        <td><?= htmlspecialchars($doc['email_doce']) ?></td>
        <td><?= strtoupper($doc['ciclo']) ?></td>
        <td><?= $doc['estado_final'] ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- tabla de NO CUMPLIERON -->
  <h3>DOCENTES QUE NO ENVIARON SU PLAN</h3>
  <table>
    <thead>
      <tr>
        <th>Nº</th>
        <th>Docente</th>
        <th>Correo</th>
        <th>Ciclo</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($no_enviaron)): ?>
      <tr><td colspan="5">Todos los docentes enviaron su plan.</td></tr>
    <?php else: $i=1; foreach ($no_enviaron as $doc): ?>
      <?php
        $class = ($doc['estado_final']==='GUARDADO (sin envío)') ? 'fila-amarilla' : 'fila-roja';
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
