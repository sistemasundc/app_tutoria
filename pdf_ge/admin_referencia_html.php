<?php  
$mes_url = $_GET['mes'] ?? '';
$mes_map = ['enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];
$mes_num = $mes_map[strtolower($mes_url)] ?? 0;

$sqlDerivaciones = "
SELECT
    d.id_derivaciones AS id_derivacion,
    d.id_estudiante,
    d.id_docente,
    d.fechaDerivacion,
    d.hora, -- CORREGIDO: era d.horaDerivacion, debe ser d.hora
    d.motivo_ref,
    d.area_apoyo_id,
    d.observaciones,
    d.resultado_contra,
    e.apepa_estu, e.apema_estu, e.nom_estu,
    doc.abreviatura_doce, doc.apepa_doce, doc.apema_doce, doc.nom_doce, doc.email_doce
FROM tutoria_derivacion_tutorado_f6 d
INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
INNER JOIN docente doc ON doc.id_doce = d.id_docente
WHERE d.estado = 'atendido'
  AND MONTH(d.fechaDerivacion) = $mes_num
ORDER BY d.fechaDerivacion ASC
";
$resDerivaciones = $conexion->conexion->query($sqlDerivaciones);

if (!$resDerivaciones) {
    echo "<p style='color:red;'>ERROR SQL: " . $conexion->conexion->error . "</p>";
}

$tipo_sesion = ['', 'Médico', 'Asistente Social', 'Psicólogo', 'Defensoría'];
$contador = 0;

while ($d = $resDerivaciones->fetch_assoc()) {
    $contador++;

    // Obtener carrera del docente
    $id_docente = intval($d['id_docente'] ?? 0);
    $data_nom_car = '[NO DEFINIDO]';

    if ($id_docente > 0) {
        $sqlCarrera = "SELECT c.nom_car 
                       FROM docente d
                       INNER JOIN carrera c ON d.id_car = c.id_car
                       WHERE d.id_doce = $id_docente
                       LIMIT 1";
        $resCarrera = $conexion->conexion->query($sqlCarrera);

        if ($resCarrera && ($filaCar = $resCarrera->fetch_assoc())) {
            $data_nom_car = $filaCar['nom_car'] ?? '[NO DEFINIDO]';
        }
    }

    $fecha = $d['fechaDerivacion'] ?? '';
    $hora = $d['horaDerivacion'] ?? date('H:i:s');
    $anio = !empty($fecha) ? date('Y', strtotime($fecha)) : date('Y');

    $tutor = '';
    $correo = '';
    if (isset($d['abreviatura_doce'], $d['apepa_doce'], $d['apema_doce'], $d['nom_doce'])) {
        $tutor = strtoupper(trim($d['abreviatura_doce'] . ' ' . $d['apepa_doce'] . ' ' . $d['apema_doce'] . ' ' . $d['nom_doce']));
        $correo = $d['email_doce'] ?? '';
    }

    $estudiante = strtoupper(($d['apepa_estu'] ?? '') . ' ' . ($d['apema_estu'] ?? '') . ' ' . ($d['nom_estu'] ?? ''));
    $motivo = htmlspecialchars($d['motivo_ref'] ?? '');
    $tipo_id = intval($d['area_apoyo_id'] ?? 0);
?>
<style>
    .documento {
  width: 210mm;
  min-height: 297mm;
  padding: 20mm;
  margin: auto;
  background: white;
  box-shadow: 0 0 5px rgba(0,0,0,0.1);
  position: relative; /* necesario */
  box-sizing: border-box;
}

footer {
  position: absolute;
  bottom: 10mm;
  left: 20mm;
  right: 20mm;
  text-align: center;
  font-size: 10px;
}
</style>

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
    <?php if ($contador === 1): ?>
    <h4 style="text-align:left; font-size:16px; margin-bottom: -10px;">
        <strong>Anexo 1: F.M01.04-VRA-006 Hoja de Referencia y Contrarreferencia</strong>
    </h4><br>
    <?php endif; ?>
    
    <h3 style="text-align:center;">ESCUELA PROFESIONAL DE <?= htmlspecialchars($data_nom_car) ?></h3>
    <h4 style="text-align:center;"><strong>HOJA DE REFERENCIA</strong></h4>
    <p style="text-align:center;"><strong>Año Académico:</strong> <?= $anio ?></p>
    <br><br>

    <pre style="font-size: 15px;">
Fecha: <?= $fecha ?>                                        Hora: <?= $hora ?> <br>

<strong>Tutor:</strong> <?= $tutor ?> <br>

<strong>Estudiante derivado:</strong> <?= $estudiante ?> <br>

<p><strong>Motivo de la referencia:</strong></p>
<p style="white-space: pre-line; font-size: 15px;"><?= $motivo ?></p>

<strong>Dirigido a:</strong><br>
<?php
    for ($i = 1; $i < count($tipo_sesion); $i++) {
        echo ($i === $tipo_id ? '[X]' : '[ ]') . ' ' . $tipo_sesion[$i] . "\n";
    }
?> <br>
<p style="text-align:center;"><em> Fecha: <?= $fecha ?> </em> 
<?= $tutor ?><br>
<?= $correo ?></p>
</pre>
 <footer>
       <p style="text-align: center; font-size:10px;"> Toda copia de este documento, sea del entorno virtual o del documento original en físico es considerada “copia no controlada”</p>
</footer>
</div>

<?php } ?>
