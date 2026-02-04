<?php
    require '../../modelo/modelo_docente.php';
    $docent = new Docente();

    $horaincio = htmlspecialchars($_POST['inicio'],ENT_QUOTES,'UTF-8');
    $horafinal = htmlspecialchars($_POST['final'],ENT_QUOTES,'UTF-8');
    $id_doce = htmlspecialchars($_POST['iddoce'],ENT_QUOTES,'UTF-8');
    $fecha = htmlspecialchars($_POST['fecha'],ENT_QUOTES,'UTF-8');
  
    $consulta = $docent->AsistenciasGuardadas($horaincio, $horafinal, $id_doce, $fecha);
    $data = json_encode($consulta);
    
    if(count($consulta)>0){
        echo $data;
    }else{
        echo 0;
    }

?>