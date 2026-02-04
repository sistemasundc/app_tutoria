<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

/* --------- Control de acceso --------- */
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

/* ===========================
   1. Resolver carrera y semestre
   =========================== */
$id_car_session   = (int)($_SESSION['S_SCHOOL'] ?? 0);
$semestre_session = (int)($_SESSION['S_SEMESTRE'] ?? 0);

// semestre seleccionado vía GET (selector)
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
    ? (int)$_GET['semestre']
    : $semestre_session;

// éste es el semestre que se usa en las consultas
$id_semestre = $semestre_sel;
$id_car      = $id_car_session;

if (!$id_car || !$id_semestre) {
    die('Error: Sesión sin carrera o semestre.');
}

/* ===========================
   2. Nombre legible del semestre
   Ajusta estos textos al nombre oficial que quieras mostrar
   =========================== */
$mapSemNombre = [
    32 => '2025-1',
    33 => '2025-2',
    35 => '2026-1',
    // agrega más si aplica
];

$nombre_semestre = $mapSemNombre[$id_semestre] ?? ($_SESSION['S_NOMSEMESTRE'] ?? '—');

/* ===========================
   3. Obtener nombre de la carrera
   =========================== */
$nombre_carrera = '……………………………';
if ($id_car) {
    $sqlCarrera   = "SELECT nom_car FROM carrera WHERE id_car = ?";
    $stmtCarrera  = $conexion->conexion->prepare($sqlCarrera);
    $stmtCarrera->bind_param("i", $id_car);
    $stmtCarrera->execute();
    if ($rowCar = $stmtCarrera->get_result()->fetch_assoc()) {
        $nombre_carrera = strtoupper($rowCar['nom_car']);
    }
}

/* ===========================
   4. Obtener tutores con ciclo
   (docentes asignados en este semestre y carrera)
   =========================== */
$sql_tutores = "
SELECT 
    d.id_doce,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.email_doce,
    cl.ciclo
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
GROUP BY d.id_doce
ORDER BY d.apepa_doce, d.apema_doce, d.nom_doce
";
$stmt = $conexion->conexion->prepare($sql_tutores);
$stmt->bind_param("ii", $id_car, $id_semestre);
$stmt->execute();
$tutores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ===========================
   5. Estado de informe final por docente
   (1 = guardado / borrador, 2 = enviado)
   =========================== */
$sql_estado = "
SELECT id_doce, estado_envio, fecha_presentacion 
FROM tutoria_informe_final_aula 
WHERE id_car = ? AND semestre_id = ?
";
$stmtEstado = $conexion->conexion->prepare($sql_estado);
$stmtEstado->bind_param("ii", $id_car, $id_semestre);
$stmtEstado->execute();
$resEstado = $stmtEstado->get_result()->fetch_all(MYSQLI_ASSOC);

/* Indexamos por docente: nos quedamos con el estado más alto */
$estado_docente = [];
foreach ($resEstado as $row) {
    $id_doc    = $row['id_doce'];
    $estado    = (int)$row['estado_envio']; // 1 o 2
    $fecha_env = $row['fecha_presentacion'];

    if (
        !isset($estado_docente[$id_doc]) ||
        $estado > $estado_docente[$id_doc]['estado']
    ) {
        $estado_docente[$id_doc] = [
            'estado' => $estado,
            'fecha'  => $fecha_env
        ];
    }
}

/* ===========================
   6. Clasificación: enviaron / no presentaron
   =========================== */
$enviaron        = [];
$no_presentaron  = [];

foreach ($tutores as $doc) {
    $id_docente   = $doc['id_doce'];
    $estado_envio = $estado_docente[$id_docente]['estado'] ?? null;
    $fecha_envio  = $estado_docente[$id_docente]['fecha'] ?? null;

    $doc['fecha_envio'] = $fecha_envio;

    if ($estado_envio === 2) {
        $doc['estado_final'] = 'CUMPLIÓ';
        $enviaron[] = $doc;
    } elseif ($estado_envio === 1) {
        $doc['estado_final'] = 'GUARDADO (sin envío)';
        $no_presentaron[] = $doc;
    } else {
        $doc['estado_final'] = 'NO CUMPLIÓ';
        $no_presentaron[] = $doc;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Informe Final - Envíos</title>
  <style>
    body {
      background-color: #fff;
      font-family: Arial, sans-serif;
      padding: 30px;
      color:#000;
    }
    .contenedor {
      max-width: 1000px;
      margin: auto;
      position: relative;
    }

    /* botón imprimir */
    .boton-imprimir {
      position:absolute;
      right:0;
      top:0;
      background-color: #1a5276;
      color: #fff;
      padding: 10px 20px;
      border: none;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      font-size:14px;
    }

    /* selector semestre */
    .filtro-semestre {
      position:absolute;
      right:140px;
      top:0;
      font-size:14px;
      font-family:inherit;
      padding:8px 10px;
      border:1px solid #1a5276;
      border-radius:5px;
      background:#fff;
      color:#1a5276;
      font-weight:bold;
      min-width:110px;
    }

    h2 {
      text-align:center;
      margin:6px 0;
    }
    .subtitulo-escuela {
      text-align:center;
      margin-bottom:20px;
      font-weight:bold;
    }

    h3 {
      text-align:left;
      margin-top:30px;
      margin-bottom:8px;
      font-size:15px;
    }

    table {
      width:100%;
      border-collapse:collapse;
      margin-bottom:25px;
      font-size:14px;
    }
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

    .fila-verde     { background-color:#d5f5e3; }
    .fila-roja      { background-color:#f9c0c0; }
    .fila-amarilla  { background-color:rgb(243,236,172); }

    @media print {
      .boton-imprimir,
      .filtro-semestre {
        display:none !important;
      }
    }
  </style>
</head>
<body>

<div class="contenedor">

  <!-- selector semestre -->
  <form method="GET" style="display:inline;">
    <select name="semestre" class="filtro-semestre" onchange="this.form.submit()">
      <?php foreach ($mapSemNombre as $idS => $labelS): ?>
        <option value="<?= $idS ?>" <?= ($idS == $id_semestre ? 'selected' : '') ?>>
          <?= htmlspecialchars($labelS) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <!-- botón imprimir -->
  <button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>

  <!-- título -->
  <h2>INFORMES FINALES - <?= htmlspecialchars($nombre_semestre) ?></h2>
  <div class="subtitulo-escuela">
    ESCUELA PROFESIONAL DE <?= $nombre_carrera ?>
  </div>

  <!-- tabla: cumplieron -->
  <h3>LISTA DE DOCENTES QUE PRESENTARON SU INFORME FINAL</h3>
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

  <!-- tabla: no presentaron -->
  <h3>DOCENTES QUE AÚN NO PRESENTAN SU INFORME FINAL</h3>
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
          $class = ($doc['estado_final'] === 'GUARDADO (sin envío)')
              ? 'fila-amarilla'
              : 'fila-roja';
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
