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

// Obtener estado de informes finales
$sql_estado = "
SELECT id_doce, estado_envio, fecha_presentacion 
FROM tutoria_informe_final_aula 
WHERE id_car = ? AND semestre_id = ?
";
$stmtEstado = $conexion->conexion->prepare($sql_estado);
$stmtEstado->bind_param("ii", $id_car, $id_semestre);
$stmtEstado->execute();
$resEstado = $stmtEstado->get_result()->fetch_all(MYSQLI_ASSOC);

// Indexar por docente
$estado_docente = [];
foreach ($resEstado as $row) {
    $id = $row['id_doce'];
    $estado = intval($row['estado_envio']);
    if (!isset($estado_docente[$id]) || $estado > $estado_docente[$id]['estado']) {
        $estado_docente[$id] = [
            'estado' => $estado,
            'fecha' => $row['fecha_presentacion']
        ];
    }
}

// Clasificar
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
  <title>Informe Final - Envíos</title>
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
    .fila-amarilla { background-color: rgb(243, 236, 172); }
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
    @media print {
      .boton-imprimir {
        display: none !important;
      }
    }
  </style>
</head>
<body>
  <button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>
  <div class="contenedor">
    <h2>INFORMES FINALES - <?= htmlspecialchars($nombre_semestre) ?></h2>
    <h2>ESCUELA PROFESIONAL DE <?= $nombre_carrera ?></h2>

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
            <td><?= strtoupper("{$doc['abreviatura_doce']} {$doc['apepa_doce']} {$doc['apema_doce']}, {$doc['nom_doce']}") ?></td>
            <td><?= $doc['email_doce'] ?></td>
            <td><?= $doc['ciclo'] ?></td>
            <td><?= $doc['fecha_envio'] ? date('d/m/Y H:i', strtotime($doc['fecha_envio'])) : '--' ?></td>
            <td><?= $doc['estado_final'] ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

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
         <tr class="<?= 
              $doc['estado_final'] == 'GUARDADO (sin envío)' ? 'fila-amarilla' : 'fila-roja' ?>">
            <td><?= $i++ ?></td>
            <td><?= strtoupper("{$doc['abreviatura_doce']} {$doc['apepa_doce']} {$doc['apema_doce']}, {$doc['nom_doce']}") ?></td>
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
