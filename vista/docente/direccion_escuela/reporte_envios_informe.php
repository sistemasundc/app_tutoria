<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO', 'DIRECCION DE ESCUELA', 'COMITÉ - SUPERVISIÓN'])) {
    die('Acceso no autorizado');
}

$id_car = $_SESSION['S_SCHOOL'] ?? null;
$id_semestre = $_SESSION['S_SEMESTRE'] ?? null;
$nombre_semestre = $_SESSION['S_NOMSEMESTRE'] ?? '2025-2';
$mes_filtro = $_GET['mes'] ?? 'Septiembre';

$nombre_carrera = '……………………………';
if ($id_car) {
    $sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
    $stmtCarrera = $conexion->conexion->prepare($sqlCarrera);
    $stmtCarrera->bind_param("i", $id_car);
    $stmtCarrera->execute();
    $resCarrera = $stmtCarrera->get_result();
    if ($rowCar = $resCarrera->fetch_assoc()) {
        $nombre_carrera = strtoupper($rowCar['nom_car']);
    }
}

// Obtener tutores
// Obtener tutores solo de la tabla oficial
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
JOIN docente d ON d.id_doce = tat.id_docente
JOIN carga_lectiva cl ON cl.id_cargalectiva = tat.id_carga
JOIN tutoria_docente_asignado tda ON tda.id_doce = tat.id_docente AND tda.id_semestre = cl.id_semestre
WHERE cl.id_car = ? AND cl.id_semestre = ?
GROUP BY d.id_doce
ORDER BY d.apepa_doce, d.apema_doce
";
$stmt = $conexion->conexion->prepare($sql_tutores);
$stmt->bind_param("ii", $id_car, $id_semestre);
$stmt->execute();
$tutores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$types  = "ii";
$params = [$id_car, $id_semestre];

if (!empty($mes_filtro)) {
    $sql_estado = "
    SELECT 
        tat.id_docente AS id_doce,
        MAX(im.estado_envio) AS estado_envio,
        MAX(im.fecha_envio)  AS fecha_envio
    FROM tutoria_asignacion_tutoria tat
    JOIN carga_lectiva cl 
          ON cl.id_cargalectiva = tat.id_carga
    LEFT JOIN tutoria_informe_mensual im
          ON im.id_cargalectiva = tat.id_carga
         AND im.id_docente         = tat.id_docente
         AND im.id_semestre     = tat.id_semestre
         AND LOWER(im.mes_informe) = ?
    WHERE cl.id_car = ?
      AND tat.id_semestre = ?
    GROUP BY tat.id_docente";
    $types  = "sii";
    $params = [strtolower($mes_filtro), $id_car, $id_semestre];
} else {
    $sql_estado = "
    SELECT 
        tat.id_docente AS id_doce,
        MAX(im.estado_envio) AS estado_envio,
        MAX(im.fecha_envio)  AS fecha_envio
    FROM tutoria_asignacion_tutoria tat
    JOIN carga_lectiva cl 
          ON cl.id_cargalectiva = tat.id_carga
    LEFT JOIN tutoria_informe_mensual im
          ON im.id_cargalectiva = tat.id_carga
         AND im.id_docente         = tat.id_docente
         AND im.id_semestre     = tat.id_semestre
    WHERE cl.id_car = ?
      AND tat.id_semestre = ?
    GROUP BY tat.id_docente";
}

$stmtEstado = $conexion->conexion->prepare($sql_estado);
$stmtEstado->bind_param($types, ...$params);
$stmtEstado->execute();
$resEstado = $stmtEstado->get_result()->fetch_all(MYSQLI_ASSOC);

$estado_docente = [];
foreach ($resEstado as $row) {
    $estado_docente[(int)$row['id_doce']] = [
        'estado' => isset($row['estado_envio']) ? (int)$row['estado_envio'] : null,
        'fecha'  => $row['fecha_envio'] ?? null
    ];
}

// Separar
$enviaron = [];
$no_presentaron = [];

foreach ($tutores as $doc) {
    $id = $doc['id_doce'];
    $estado_envio = $estado_docente[$id]['estado'] ?? null;
    $fecha_envio = $estado_docente[$id]['fecha'] ?? null;

    $doc['fecha_envio'] = $fecha_envio;

    if ($estado_envio == 2) {
        $doc['estado_final'] = 'CUMPLIÓ';
        $enviaron[] = $doc;
    } elseif ($estado_envio == 1) {
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
  <title>Informe Mensual - Envíos</title>
  <style>
    body {
      background-color: white;
      font-family: Arial, sans-serif;
      padding: 30px;
    }
    .contenedor {
      max-width: 1000px;
      margin: auto;
    }
    h2, h3 {
      text-align: center;
      margin-bottom: 10px;
    }
    h3 {
      text-align: left;
      margin-top: 30px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
    }
    th {
      background: #1a5276;
      color: white;
      padding: 8px;
      text-align: left;
    }
    td {
      padding: 8px;
      border: 1px solid #ccc;
    }
    .fila-verde { background-color: #d5f5e3; }
    .fila-roja { background-color: #f9c0c0; }
    .fila-amarilla { background-color:rgb(243, 236, 172); }
    .boton-imprimir {
      float: right;
      margin-bottom: 20px;
      background-color: #1a5276;
      color: white;
      padding: 10px 20px;
      border: none;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
    }
    .filtro-mes {
      max-width: 300px;
      margin: 0 auto 20px;
    }
    @media print {
      .boton-imprimir, .filtro-mes {
        display: none !important;
      }
    }
  </style>
</head>
<body>
  <button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>
  <div class="contenedor">
    <h2>INFORMES MENSUALES - <?= htmlspecialchars($nombre_semestre) ?></h2>
    <h2>ESCUELA PROFESIONAL DE <?= $nombre_carrera ?></h2>

    <form method="GET" class="filtro-mes">
      <select name="mes" class="form-control" onchange="this.form.submit()">
        <option value="">-- Todos los meses --</option>
<!--         <option value="abril"  $mes_filtro == 'abril' ? 'selected' : '' ?>>Abril</option>
        <option value="mayo"  $mes_filtro == 'mayo' ? 'selected' : '' ?>>Mayo</option>
        <option value="junio"  $mes_filtro == 'junio' ? 'selected' : '' ?>>Junio</option>
        <option value="julio"  $mes_filtro == 'julio' ? 'selected' : '' ?>>Julio</option> -->
       <!--  <option value="agosto" ?= $mes_filtro == 'agosto' ? 'selected' : '' ?>>Agosto</option> -->
        <option value="septiembre" <?= $mes_filtro == 'septiembre' ? 'selected' : '' ?>>Septiembre</option>
        <option value="octubre" <?= $mes_filtro == 'octubre' ? 'selected' : '' ?>>Octubre</option>
        <option value="noviembre" <?= $mes_filtro == 'noviembre' ? 'selected' : '' ?>>Noviembre</option>
        <option value="diciembre" <?= $mes_filtro == 'diciembre' ? 'selected' : '' ?>>Diciembre</option>
      </select>
    </form>

    <h3>LISTA DE DOCENTES QUE PRESENTARON SU INFORME - MES <?= $mes_filtro ? strtoupper($mes_filtro) : '' ?></h3>
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
            <td><?= $doc['email_doce'] ?></td>
            <td><?= $doc['ciclo'] ?></td>
            <td><?= $doc['fecha_envio'] ? date('d/m/Y H:i', strtotime($doc['fecha_envio'])) : '--' ?></td>
            <td><?= $doc['estado_final'] ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <h3>DOCENTES QUE AÚN NO PRESENTAN SU INFORME - MES <?= $mes_filtro ? strtoupper($mes_filtro) : '' ?></h3>
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
         <tr class="<?= 
              $doc['estado_final'] == 'CUMPLIÓ' ? 'fila-verde' : 
              ($doc['estado_final'] == 'GUARDADO (sin envío)' ? 'fila-amarilla' : 'fila-roja') 
          ?>">
            <td><?= $i++ ?></td>
            <td><?= strtoupper($doc['abreviatura_doce'].' '.$doc['apepa_doce'].' '.$doc['apema_doce'].', '.$doc['nom_doce']) ?></td>
            <td><?= $doc['email_doce'] ?></td>
            <td><?= $doc['ciclo'] ?></td>
            <td><?= $doc['estado_final'] ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
