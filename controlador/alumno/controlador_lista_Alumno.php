<?php
    require '../../modelo/modelo_alumno.php';
    $MU = new Alumno();

    session_start();

    $semestre = $_SESSION['S_SEMESTRE'];
    
    //$ciclo = htmlspecialchars($_POST['ciclo'],ENT_QUOTES,'UTF-8');
    $consulta = $MU->listar_alumnos($semestre);
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