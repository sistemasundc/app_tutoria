<?php
    require '../../modelo/modelo_usuario.php';

    $MU = new Modelo_Usuario();
    $usuario = htmlspecialchars($_POST['user'],ENT_QUOTES,'UTF-8');
    $contra = htmlspecialchars($_POST['pass'],ENT_QUOTES,'UTF-8');
   
    $consulta = $MU->VerificarUsuario($usuario,$contra);
    
    if (empty($consulta)){

        $consulta = $MU->VerificarDocente($usuario,$contra);
       
        if (empty($consulta)){
            $consulta = $MU->VerificarEstudiante($usuario,$contra);
        }
    }

    $data = json_encode($consulta);
    if(count($consulta)>0){
        echo $data;
    }else{
        echo 0;
    }

?>