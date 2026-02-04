<?php
   require '../../modelo/modelo_horario.php';

    $horario = new Horario();

    $idhorario = htmlspecialchars($_POST['id_horario'],ENT_QUOTES,'UTF-8');
    $tipo = htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8');
    $hora = htmlspecialchars($_POST['hora'],ENT_QUOTES,'UTF-8');
    $dia = htmlspecialchars($_POST['dia'],ENT_QUOTES,'UTF-8');
  
    $consulta = $horario->Eliminar_Horario_alumno($idhorario, $tipo,$hora ,$dia);
    echo $consulta;
?>