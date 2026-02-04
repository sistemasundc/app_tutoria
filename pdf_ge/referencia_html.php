<?php 
if (!isset($data_derivaciones) || empty($data_derivaciones)) {
    return;
}

$tipo_sesion = ['', 'Médico', 'Asistente Social', 'Psicólogo', 'Defensoría'];

foreach ($data_derivaciones as $d):
    $fecha = $d['fechaDerivacion'] ?? '';
    $hora = $d['horaDerivacion'] ?? date('H:i:s');
    $anio = !empty($fecha) ? date('Y', strtotime($fecha)) : date('Y');

    // Condicional para rol 6 o rol 2 según campos disponibles
    $tutor = '';
    $correo = '';
    $tipo_id = 0;

    if (
        isset($d['abreviatura_doce'], $d['apepa_doce'], $d['apema_doce'], $d['nom_doce'])
    ) {
        $tutor = strtoupper(trim($d['abreviatura_doce'] . ' ' . $d['apepa_doce'] . ' ' . $d['apema_doce'] . ' ' . $d['nom_doce']));
        $correo = $d['email_doce'] ?? '';
    }

    $estudiante = strtoupper($d['apepa_estu'] . ' ' . $d['apema_estu'] . ' ' . $d['nom_estu']);
    $motivo = nl2br(htmlspecialchars($d['motivo_ref']));
    $tipo_id = isset($d['area_apoyo_id']) ? intval($d['area_apoyo_id']) : 0;
?>
<div class="documento" style="page-break-after: always;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
        <img src="../../img/undc.png" alt="Logo UNDC" style="width: 201px; float: left;">
        </div>
        <table style="float: right; border: 1px solid #000; background-color: #d6f0cd; font-size: 12px; font-family: Arial, sans-serif;">
            <tr>
                <td style="padding: 5px 8px; width: 80px; text-align: center; vertical-align: middle; line-height: 1.4;">
                <strong>Código:</strong> F-M01.04-VRA-006<br>
                <strong>Fecha de Aprobación:</strong> 07/05/2024
                </td>
                <td style="border-left: 1px solid #000; width: 31px; text-align: center; vertical-align: middle;">
                <strong>Versión: 03</strong>
                </td>
            </tr>
        </table>
    </div>
    <br><br>

    <h3 style="text-align:center;">ESCUELA PROFESIONAL DE <?= htmlspecialchars($data_nom_car) ?></h3>
    <h4 style="text-align:center;"><strong>HOJA DE REFERENCIA</strong></h4>
    <p style="text-align:center;"><strong>Año Académico:</strong> <?= $anio ?></p>
    <br><br>

    <pre style=" font-size: 15px;">
Fecha: <?= $fecha ?>                                        Hora: <?= $hora ?> <br>

<strong>Tutor:</strong> <?= $tutor ?> <br>

<strong>Estudiante derivado:</strong> <?= $estudiante ?> <br>

<strong>Motivo de la referencia:</strong> <br>
<?= $motivo ?> <br>

<strong>Dirigido a:</strong><br>
<?php
    for ($i = 1; $i < count($tipo_sesion); $i++) {
        echo ($i === $tipo_id ? '[X]' : '[ ]') . ' ' . $tipo_sesion[$i] . "\n";
    }
?> <br><br><br>
<p style="text-align:center;"><em> Fecha: <?= $fecha ?> </em> 
<?= $tutor ?><br>
<?= $correo ?></p>
</pre>
 <footer style=" margin-top: 300px;">
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
</footer>
</div>
<?php endforeach; ?>
