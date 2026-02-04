<?php
    require '../../modelo/modelo_alumno.php';
    $MU = new Alumno();

    $id_sesion = htmlspecialchars($_POST['idsesion'],ENT_QUOTES,'UTF-8');
    $valoracion = htmlspecialchars($_POST['val'],ENT_QUOTES,'UTF-8');
    $comentario = htmlspecialchars($_POST['coment'],ENT_QUOTES,'UTF-8');
    $id_estu = htmlspecialchars($_POST['estu'],ENT_QUOTES,'UTF-8');
    
    $consulta = $MU->ValorarTutoria($id_sesion, $valoracion, $comentario, $id_estu);
    echo $consulta;
?>

