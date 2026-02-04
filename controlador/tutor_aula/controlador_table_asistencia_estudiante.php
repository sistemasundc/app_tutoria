<?php
    require '../../modelo/modelo_docente.php';
    $MU = new Docente();

    session_start();

    $semestre = $_SESSION['S_SEMESTRE'];

    $id_doc = htmlspecialchars($_POST['doce'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');
    $dia = htmlspecialchars($_POST['dia'],ENT_QUOTES,'UTF-8');
    $tipo = htmlspecialchars($_POST['tipo'],ENT_QUOTES,'UTF-8');
    $horainicio = htmlspecialchars($_POST['hora'],ENT_QUOTES,'UTF-8');

    $consulta = $MU->listar_alumnos_asistencia($id_doc, $dia, $id_estu, $tipo, $horainicio, $semestre);
    
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