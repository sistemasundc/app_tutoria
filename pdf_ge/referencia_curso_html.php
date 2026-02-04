<?php
require_once("index.php"); // contiene DatosReferido y EstudiantesReferido

if (!isset($id_der) || !isset($semestre)) {
    echo "No se proporcionaron datos de referencia.";
    return;
}

$MD = new Docente();
$array_data = $MD->DatosReferido($id_der);
$array_estu = $MD->EstudiantesReferido($id_der);
$tipo_sesion = ['', 'Médico', 'Asistente Social', 'Psicólogo', 'Defensoría'];

$tutor = $array_data[0][1];
$correo = $array_data[0][5];
$fecha = date('Y-m-d');
$hora = date('H:i:s');
?>

<div class="formato-anexo" style="border:1px solid #ccc; padding:20px; margin-top:30px; font-family:sans-serif;">
  <h3 style="text-align:center; margin-bottom: 10px;">F-M01.04-VRA-006 Hoja de Referencia</h3>
  <p><strong>Escuela Profesional:</strong> <?= htmlspecialchars($array_data[0][0]) ?></p>
  <p><strong>Año Académico:</strong> <?= htmlspecialchars($semestre) ?></p>

  <h4>Estudiante(s) Derivado(s):</h4>
  <ul>
    <?php foreach ($array_estu as $row): ?>
      <li><?= htmlspecialchars($row[1]) ?></li>
      <?php break; ?>
    <?php endforeach; ?>
  </ul>

  <h4>Motivo de la referencia:</h4>
  <p><?= nl2br(htmlspecialchars($array_data[0][3])) ?></p>

  <h4>Dirigido a:</h4>
  <ul>
    <?php
    $tipo_s = $array_data[0][4];
    for ($i = 1; $i < count($tipo_sesion) - 1; $i += 2):
        $checked1 = ($tipo_s == $i) ? '(x)' : '( )';
        $checked2 = ($tipo_s == $i + 1) ? '(x)' : '( )';
    ?>
      <li><?= $checked1 ?> <?= $tipo_sesion[$i] ?> &nbsp;&nbsp;&nbsp;&nbsp; <?= $checked2 ?> <?= $tipo_sesion[$i+1] ?></li>
    <?php endfor; ?>
  </ul>

  <h4>Firma del Tutor</h4>
  <p><strong><?= htmlspecialchars($tutor) ?></strong><br>
     Fecha: <?= $fecha ?> - Hora: <?= $hora ?><br>
     Correo: <?= htmlspecialchars($correo) ?></p>
</div>
<div style="margin-top: 40px; text-align: center;">
  <img src="pdf/img/footer.JPG" alt="footer" style="width: 80%; max-width: 700px;">
</div>
