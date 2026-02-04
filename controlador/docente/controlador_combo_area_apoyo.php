<?php 
    require '../../modelo/modelo_docente.php';
    $docent = new Docente();

    $id_apoyo_dif = htmlspecialchars($_POST['id_ap'],ENT_QUOTES,'UTF-8');

    $consulta = $docent->AreaApoyo($id_apoyo_dif);

    echo json_encode($consulta);
?>