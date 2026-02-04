<?php
  require '../../modelo/modelo_horario.php';

  $horario = new Horario();

  $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');

  $consulta = $horario->CargarHorarioAlumno($id_estu);

  echo json_encode($consulta);
?> 