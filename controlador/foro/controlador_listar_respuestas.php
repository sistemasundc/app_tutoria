<?php
    require '../../modelo/modelo_foro.php';
    $MF = new Foro();


    $id_foro = htmlspecialchars($_POST['for'],ENT_QUOTES,'UTF-8');

    $consulta = $MF->VerForoRespuesta($id_foro);

    echo json_encode($consulta);
?>