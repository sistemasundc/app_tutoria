<?php
 

    require '../../modelo/modelo_coordinador.php';
    $docent = new Modelo_Coordinador();
    
    $iddocente = htmlspecialchars($_POST['id_usuario'],ENT_QUOTES,'UTF-8');
    $semestre = htmlspecialchars($_POST['anio'],ENT_QUOTES,'UTF-8');
     
    $consulta = $docent->Ver_CargosAsignados($iddocente, $semestre);

    if($consulta){
        echo json_encode($consulta);
    }else{
        echo '{
        "sEcho": 1,
        "iTotalRecords": "0",
        "iTotalDisplayRecords": "0",
        "aaData": []
    }';
    }

?>