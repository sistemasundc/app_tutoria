<?php
    require '../../modelo/modelo_foro.php';
    $MF = new Foro();

    $id_foro = htmlspecialchars($_POST['foro'],ENT_QUOTES,'UTF-8');

    $consulta = $MF->DeleteForo($id_foro);
    echo $consulta;
?>