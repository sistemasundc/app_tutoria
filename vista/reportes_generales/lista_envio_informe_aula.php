<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], [
    'SUPERVISION','COORDINADOR GENERAL DE TUTORIA','DIRECCION DE ESCUELA','VICEPRESIDENCIA ACADEMICA'
])) {
    die('Acceso no autorizado');
}

$id_car = $_GET['id_car'] ?? null;
$mes_filtro = strtolower(trim($_GET['mes'] ?? ''));
$id_semestre = $_SESSION['S_SEMESTRE'] ?? null;
$nombre_semestre = $_SESSION['S_NOMSEMESTRE'] ?? '2025-2';

if (empty($id_car) || empty($id_semestre) || empty($mes_filtro)) {
    die("Error: Parámetros incompletos.");
}

// Obtener nombre de carrera
$nombre_carrera = '………………';
$sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
$stmtCarrera = $conexion->conexion->prepare($sqlCarrera);
$stmtCarrera->bind_param("i", $id_car);
$stmtCarrera->execute();
$resCarrera = $stmtCarrera->get_result();
if ($rowCar = $resCarrera->fetch_assoc()) {
    $nombre_carrera = strtoupper($rowCar['nom_car']);
}

// Obtener tutores asignados por carga única del semestre
$sql_tutores = "
SELECT 
    d.id_doce,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.email_doce,
    cl.ciclo,
    MIN(cl.id_cargalectiva) as id_cargalectiva
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

// Obtener estados de informes por carga lectiva
$sql_estado = "
SELECT id_cargalectiva, estado_envio, fecha_envio 
FROM tutoria_informe_mensual
WHERE id_car = ? AND LOWER(mes_informe) = ?
";
$stmtEstado = $conexion->conexion->prepare($sql_estado);
if (!$stmtEstado) {
    die("Error en consulta de estados: " . $conexion->conexion->error);
}
$stmtEstado->bind_param("is", $id_car, $mes_filtro);
$stmtEstado->execute();
$resEstado = $stmtEstado->get_result()->fetch_all(MYSQLI_ASSOC);

// Indexar por id_cargalectiva
// Indexar por id_cargalectiva
$estado_docente = [];

foreach ($resEstado as $row) {
    $id_carga = $row['id_cargalectiva'];
    $estado = intval($row['estado_envio']);

    // Si no existe o encontramos un estado mayor, lo reemplazamos (2 > 1 > 0/null)
    if (!isset($estado_docente[$id_carga]) || $estado > $estado_docente[$id_carga]['estado']) {
        $estado_docente[$id_carga] = [
            'estado' => $estado,
            'fecha' => $row['fecha_envio']
        ];
    }
}

// Separar según estado
$enviaron = [];
$guardaron = [];
$no_presentaron = [];

foreach ($tutores as $doc) {
    $id_doce = $doc['id_doce'];

    // Buscar todos los estados de informes para este docente
    $estados = [];
    foreach ($estado_docente as $id_carga => $data) {
        // Si la carga pertenece al docente actual
        if ($id_carga == $doc['id_cargalectiva']) {
            $estados[] = $data['estado'];
            $doc['fecha_envio'] = $data['fecha'];
        }
    }

    // Evaluar estado consolidado
    $estado_envio = count($estados) ? max($estados) : null;

    if ($estado_envio === 2) {
        $doc['estado_final'] = 'CUMPLIÓ';
        $enviaron[] = $doc;
    } elseif ($estado_envio === 1) {
        $doc['estado_final'] = 'GUARDADO (sin envío)';
        $guardaron[] = $doc;
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
    <title>Relación de Docentes Tutores</title>
    <style>
        body { background-color: white !important; font-family: Arial, sans-serif; margin: 0; padding: 30px; color: black; }
        .contenedor { padding: 20px; max-width: 1000px; margin: auto; }
        h2 { text-align: center; font-size: 20px; margin-bottom: 8px; }
        h3 { text-align: left; font-size: 15px; margin-top: 30px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th { background: #1a5276; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border: 1px solid #ccc; }
        .fila-verde { background-color:rgb(177, 243, 192); font-size: 15px; }
        .fila-roja { background-color:rgb(241, 193, 197); font-size: 15px; }
        .fila-amarilla { background-color:rgb(245, 243, 148); font-size: 15px; }
        .boton-imprimir { display: block; margin: 0 auto 30px auto; background-color: #1a5276; color: white; padding: 10px 20px; border: none; font-weight: bold; border-radius: 5px; cursor: pointer; float: right; }
        @media print { .boton-imprimir { display: none !important; } }
    </style>
</head>
<body>
    <button class="boton-imprimir" onclick="window.print()">🖸️ Imprimir</button>
    <div class="contenedor">
        <h2>RELACIÓN DE INFORMES MENSUALES MES DE <?= strtoupper($mes_filtro) ?> - SEMESTRE <?= htmlspecialchars($nombre_semestre) ?></h2>
        <h2 style="font-weight: normal;">ESCUELA PROFESIONAL DE <?= $nombre_carrera ?></h2>

        <h3>DOCENTES QUE PRESENTARON INFORME</h3>
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
                <?php if (count($enviaron) === 0): ?>
                    <tr><td colspan="5">Ningún docente ha enviado aún.</td></tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($enviaron as $doc): ?>
                        <tr class="fila-verde">
                            <td><?= $i++ ?></td>
                            <td><?= strtoupper($doc['abreviatura_doce'] . ' ' . $doc['apepa_doce'] . ' ' . $doc['apema_doce'] . ', ' . $doc['nom_doce']) ?></td>
                            <td><?= $doc['email_doce'] ?></td>
                            <td><?= strtoupper($doc['ciclo']) ?></td>
                            <td>CUMPLIÓ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>DOCENTES QUE NO ENVIARON INFORME</h3>
        <table>
            <thead><tr>
                <th>N°</th>
                <th>Docente</th>
                <th>Correo</th>
                <th>Ciclo</th>
                <th>Estado</th>
            </tr></thead>
            <tbody>
                <?php if (count($guardaron) === 0 && count($no_presentaron) === 0): ?>
                    <tr><td colspan="5">Todos los docentes han enviado su informe.</td></tr>
                <?php else: ?>
                    <?php $i = 1; ?>
                    <?php foreach ($guardaron as $doc): ?>
                        <tr class="fila-amarilla">
                            <td><?= $i++ ?></td>
                            <td><?= strtoupper($doc['abreviatura_doce'] . ' ' . $doc['apepa_doce'] . ' ' . $doc['apema_doce'] . ', ' . $doc['nom_doce']) ?></td>
                            <td><?= $doc['email_doce'] ?></td>
                            <td><?= strtoupper($doc['ciclo']) ?></td>
                            <td>GUARDADO (sin envío)</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ( $no_presentaron as $doc): ?>
                        <tr class="fila-roja">
                            <td><?= $i++ ?></td>
                            <td><?= strtoupper($doc['abreviatura_doce'] . ' ' . $doc['apepa_doce'] . ' ' . $doc['apema_doce'] . ', ' . $doc['nom_doce']) ?></td>
                            <td><?= $doc['email_doce'] ?></td>
                            <td><?= strtoupper($doc['ciclo']) ?></td>
                            <td>NO CUMPLIÓ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
